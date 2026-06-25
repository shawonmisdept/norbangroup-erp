<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeeSeparation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrmEmployeeSeparationTest extends TestCase
{
    use RefreshDatabase;

    private function hrAdmin(): User
    {
        $role = Role::create([
            'name'        => 'HR Separation Admin',
            'permissions' => [
                'hrm.employees.view',
                'hrm.employees.manage',
                'hrm.employees.separation.view',
                'hrm.employees.separation.manage',
                'hrm.employees.separation.approve',
            ],
        ]);

        return User::create([
            'name'     => 'HR Admin',
            'email'    => 'hr-sep@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);
    }

    public function test_separation_index_loads(): void
    {
        $this->actingAs($this->hrAdmin())
            ->get(route('admin.hrm.separations.index'))
            ->assertOk();
    }

    public function test_admin_can_submit_and_approve_separation(): void
    {
        $factory = Factory::create(['name' => 'Sep Factory', 'is_active' => true]);
        $admin = $this->hrAdmin();
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'SEP-W1',
            'name'          => 'Worker One',
            'status'        => 'active',
            'joining_date'  => now()->subYears(6)->toDateString(),
        ]);

        $this->actingAs($admin)->post(route('admin.hrm.separations.store'), [
            'employee_id'      => $employee->id,
            'separation_type'  => 'resigned',
            'application_date' => now()->toDateString(),
            'last_working_day' => now()->addDays(30)->toDateString(),
            'reason'           => 'Personal reasons',
        ])->assertRedirect();

        $separation = EmployeeSeparation::first();
        $this->assertSame('pending', $separation->status);
        $this->assertSame(2, $separation->current_approval_step);

        $clearance = array_fill_keys(array_keys(config('hrm.exit_clearance_departments', [])), '1');
        $this->actingAs($admin)->post(route('admin.hrm.separations.exit-data', $separation), [
            'exit_clearance'       => $clearance,
            'exit_interview_notes' => 'Exit interview completed.',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.hrm.separations.approve', $separation))
            ->assertRedirect();

        $employee->refresh();
        $this->assertSame('resigned', $employee->status);
        $this->assertNotNull($employee->separation_date);
        $this->assertNull($employee->biometric_user_id);
    }

    public function test_cannot_set_resigned_status_via_employee_edit(): void
    {
        $factory = Factory::create(['name' => 'Block Factory', 'is_active' => true]);
        $admin = $this->hrAdmin();
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'BLK-1',
            'name'          => 'Blocked Worker',
            'status'        => 'active',
            'joining_date'  => now()->subYear()->toDateString(),
        ]);

        $this->actingAs($admin)->put(route('admin.hrm.employees.update', $employee), [
            'factory_id'    => $factory->id,
            'employee_code' => 'BLK-1',
            'name'          => 'Blocked Worker',
            'status'        => 'resigned',
            'joining_date'  => $employee->joining_date->format('Y-m-d'),
        ])->assertSessionHasErrors('status');
    }

    public function test_separation_triggers_fnf_notification(): void
    {
        $factory = Factory::create(['name' => 'FNF Sep Factory', 'is_active' => true]);
        $financeRole = Role::create([
            'name'        => 'Finance Sep',
            'permissions' => ['hrm.finance.settlement.manage'],
        ]);
        $financeUser = User::create([
            'name'     => 'Finance',
            'email'    => 'fin-sep@test.com',
            'password' => 'password',
            'role_id'  => $financeRole->id,
        ]);
        $admin = $this->hrAdmin();
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'FNF-S1',
            'name'          => 'FNF Worker',
            'status'        => 'active',
            'joining_date'  => now()->subYears(6)->toDateString(),
        ]);

        $this->actingAs($admin)->post(route('admin.hrm.separations.store'), [
            'employee_id'      => $employee->id,
            'separation_type'  => 'terminated',
            'application_date' => now()->toDateString(),
            'last_working_day' => now()->toDateString(),
            'reason'           => 'Policy violation',
        ]);

        $separation = EmployeeSeparation::first();
        $clearance = array_fill_keys(array_keys(config('hrm.exit_clearance_departments', [])), '1');
        $this->actingAs($admin)->post(route('admin.hrm.separations.exit-data', $separation), [
            'exit_clearance' => $clearance,
        ]);
        $this->actingAs($admin)->post(route('admin.hrm.separations.approve', $separation));

        $this->assertTrue(
            $financeUser->fresh()->notifications()->where('type', \App\Notifications\FinalSettlementPendingNotification::class)->exists()
        );
    }
}
