<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePromotion;
use App\Models\Hrm\EmployeeServiceHistory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrmEmployeePromotionTest extends TestCase
{
    use RefreshDatabase;

    private function hrAdmin(): User
    {
        $role = Role::create([
            'name'        => 'HR Promotion Admin',
            'permissions' => [
                'hrm.employees.view',
                'hrm.employees.promotion.view',
                'hrm.employees.promotion.manage',
                'hrm.employees.promotion.approve',
            ],
        ]);

        return User::create([
            'name'     => 'HR Promo Admin',
            'email'    => 'hr-promo@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);
    }

    public function test_promotion_index_loads(): void
    {
        $this->actingAs($this->hrAdmin())
            ->get(route('admin.hrm.promotions.index'))
            ->assertOk();
    }

    public function test_admin_can_submit_and_approve_promotion(): void
    {
        $factory = Factory::create(['name' => 'Promo Factory', 'is_active' => true]);
        $dept = Department::create(['factory_id' => $factory->id, 'name' => 'Production', 'is_active' => true]);
        $fromDesignation = Designation::create(['name' => 'Operator', 'is_active' => true]);
        $toDesignation = Designation::create(['name' => 'Line Chief', 'is_active' => true]);
        $admin = $this->hrAdmin();

        $employee = Employee::create([
            'factory_id'     => $factory->id,
            'department_id'  => $dept->id,
            'designation_id' => $fromDesignation->id,
            'employee_code'  => 'PRM-W1',
            'name'           => 'Worker Promo',
            'status'         => 'active',
            'joining_date'   => now()->subYears(2)->toDateString(),
        ]);

        $this->actingAs($admin)->post(route('admin.hrm.promotions.store'), [
            'employee_id'       => $employee->id,
            'movement_type'     => 'promotion',
            'to_designation_id' => $toDesignation->id,
            'effective_date'    => now()->toDateString(),
            'reason'            => 'Outstanding performance',
        ])->assertRedirect();

        $promotion = EmployeePromotion::first();
        $this->assertSame('pending', $promotion->status);
        $this->assertSame($fromDesignation->id, $promotion->from_designation_id);
        $this->assertSame($toDesignation->id, $promotion->to_designation_id);

        $this->actingAs($admin)->post(route('admin.hrm.promotions.approve', $promotion))
            ->assertRedirect();

        $employee->refresh();
        $promotion->refresh();

        $this->assertSame('approved', $promotion->status);
        $this->assertSame($toDesignation->id, $employee->designation_id);

        $history = EmployeeServiceHistory::where('employee_id', $employee->id)
            ->where('event_type', 'promotion')
            ->first();

        $this->assertNotNull($history);
        $this->assertStringContainsString('Line Chief', $history->new_value ?? '');
    }

    public function test_cannot_submit_duplicate_pending_promotion(): void
    {
        $factory = Factory::create(['name' => 'Dup Factory', 'is_active' => true]);
        $from = Designation::create(['name' => 'Helper', 'is_active' => true]);
        $to = Designation::create(['name' => 'Supervisor', 'is_active' => true]);
        $admin = $this->hrAdmin();

        $employee = Employee::create([
            'factory_id'     => $factory->id,
            'designation_id' => $from->id,
            'employee_code'  => 'DUP-1',
            'name'           => 'Dup Worker',
            'status'         => 'active',
            'joining_date'   => now()->subYear()->toDateString(),
        ]);

        EmployeePromotion::create([
            'factory_id'          => $factory->id,
            'employee_id'         => $employee->id,
            'movement_type'       => 'promotion',
            'status'              => 'pending',
            'from_designation_id' => $from->id,
            'to_designation_id'   => $to->id,
            'effective_date'      => now()->toDateString(),
        ]);

        $this->actingAs($admin)->post(route('admin.hrm.promotions.store'), [
            'employee_id'       => $employee->id,
            'movement_type'     => 'promotion',
            'to_designation_id' => $to->id,
            'effective_date'    => now()->toDateString(),
        ])->assertSessionHasErrors('employee_id');
    }

    public function test_reject_promotion_does_not_update_employee(): void
    {
        $factory = Factory::create(['name' => 'Rej Factory', 'is_active' => true]);
        $from = Designation::create(['name' => 'QC', 'is_active' => true]);
        $to = Designation::create(['name' => 'QC Lead', 'is_active' => true]);
        $admin = $this->hrAdmin();

        $employee = Employee::create([
            'factory_id'     => $factory->id,
            'designation_id' => $from->id,
            'employee_code'  => 'REJ-1',
            'name'           => 'Reject Worker',
            'status'         => 'active',
            'joining_date'   => now()->subYear()->toDateString(),
        ]);

        $promotion = EmployeePromotion::create([
            'factory_id'          => $factory->id,
            'employee_id'         => $employee->id,
            'movement_type'       => 'promotion',
            'status'              => 'pending',
            'from_designation_id' => $from->id,
            'to_designation_id'   => $to->id,
            'effective_date'      => now()->toDateString(),
            'created_by'          => $admin->id,
        ]);

        $this->actingAs($admin)->post(route('admin.hrm.promotions.reject', $promotion), [
            'rejection_reason' => 'Not eligible yet',
        ])->assertRedirect();

        $employee->refresh();
        $this->assertSame($from->id, $employee->designation_id);
        $this->assertSame('rejected', $promotion->fresh()->status);
    }
}
