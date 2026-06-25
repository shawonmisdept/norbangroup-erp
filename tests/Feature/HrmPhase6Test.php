<?php

namespace Tests\Feature;

use App\Models\Factory;
use App\Models\Hrm\DisciplinaryRecord;
use App\Models\Hrm\Employee;
use App\Models\Hrm\HrLetterTemplate;
use App\Models\Hrm\IssuedLetter;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrmPhase6Test extends TestCase
{
    use RefreshDatabase;

    private function phase6Admin(): User
    {
        $role = Role::create([
            'name'        => 'HR Phase 6 Admin',
            'permissions' => [
                'hrm.employees.view',
                'hrm.employees.manage',
                'hrm.employees.letters.view',
                'hrm.employees.letters.manage',
                'hrm.employees.discipline.view',
                'hrm.employees.discipline.manage',
            ],
        ]);

        return User::create([
            'name'     => 'Phase 6 Admin',
            'email'    => 'hr-p6@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);
    }

    public function test_letters_index_loads_with_seeded_templates(): void
    {
        $this->actingAs($this->phase6Admin())
            ->get(route('admin.hrm.letters.index'))
            ->assertOk()
            ->assertSee('HR Letters');

        $this->assertGreaterThan(0, HrLetterTemplate::count());
    }

    public function test_can_issue_letter_to_employee(): void
    {
        $factory = Factory::create(['name' => 'Letter Factory', 'is_active' => true]);
        $admin = $this->phase6Admin();
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'LTR-W1',
            'name'          => 'Letter Worker',
            'status'        => 'active',
            'joining_date'  => now()->subYear()->toDateString(),
        ]);

        $template = HrLetterTemplate::where('code', 'appointment')->first();
        $this->assertNotNull($template);

        $this->actingAs($admin)->post(route('admin.hrm.letters.store'), [
            'employee_id' => $employee->id,
            'template_id' => $template->id,
            'notes'       => 'Welcome aboard',
        ])->assertRedirect();

        $letter = IssuedLetter::first();
        $this->assertSame($employee->id, $letter->employee_id);
        $this->assertSame('appointment', $letter->letter_type);
        $this->assertStringContainsString($employee->name, $letter->content);

        $this->actingAs($admin)->get(route('admin.hrm.letters.show', $letter))
            ->assertOk()
            ->assertSee('Human Resources Department');

        $this->actingAs($admin)->get(route('admin.hrm.letters.print', $letter))->assertOk();
    }

    public function test_letter_content_is_parsed_into_sections(): void
    {
        $service = app(\App\Services\Hrm\HrLetterService::class);
        $content = "Date: 24 Jun 2026\n\nTo,\nJohn Doe\nEmployee ID: E001\n\nSubject: Warning Letter\n\nDear John,\n\nThis is a warning.\n\nSincerely,\nHuman Resources\nTest Factory";

        $sections = $service->parseLetterSections($content);

        $this->assertSame('24 Jun 2026', $sections['date']);
        $this->assertSame('Warning Letter', $sections['subject']);
        $this->assertTrue(
            collect($sections['body'])->contains(fn (string $p) => str_contains($p, 'This is a warning.'))
        );
    }

    public function test_can_record_and_close_disciplinary_action(): void
    {
        $factory = Factory::create(['name' => 'Disc Factory', 'is_active' => true]);
        $admin = $this->phase6Admin();
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'DIS-W1',
            'name'          => 'Disc Worker',
            'status'        => 'active',
            'joining_date'  => now()->subYear()->toDateString(),
        ]);

        $this->actingAs($admin)->post(route('admin.hrm.discipline.store'), [
            'employee_id'   => $employee->id,
            'action_type'   => 'written_warning',
            'incident_date' => now()->toDateString(),
            'description'   => 'Late arrival repeated',
            'action_taken'  => 'Written warning issued',
        ])->assertRedirect();

        $record = DisciplinaryRecord::first();
        $this->assertSame('open', $record->status);
        $this->assertSame('written_warning', $record->action_type);

        $this->actingAs($admin)->post(route('admin.hrm.discipline.close', $record))
            ->assertRedirect();

        $this->assertSame('closed', $record->fresh()->status);
    }

    public function test_suspension_updates_employee_status(): void
    {
        $factory = Factory::create(['name' => 'Susp Factory', 'is_active' => true]);
        $admin = $this->phase6Admin();
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'SUS-W1',
            'name'          => 'Susp Worker',
            'status'        => 'active',
            'joining_date'  => now()->subYear()->toDateString(),
        ]);

        $this->actingAs($admin)->post(route('admin.hrm.discipline.store'), [
            'employee_id'     => $employee->id,
            'action_type'     => 'suspension',
            'incident_date'   => now()->toDateString(),
            'description'     => 'Policy violation',
            'suspension_from' => now()->toDateString(),
            'suspension_to'   => now()->addDays(7)->toDateString(),
        ])->assertRedirect();

        $this->assertSame('suspended', $employee->fresh()->status);
    }

    public function test_employee_profile_shows_letters_and_discipline_cards(): void
    {
        $factory = Factory::create(['name' => 'Profile Factory', 'is_active' => true]);
        $admin = $this->phase6Admin();
        $employee = Employee::create([
            'factory_id'    => $factory->id,
            'employee_code' => 'PRF-W1',
            'name'          => 'Profile Worker',
            'status'        => 'active',
            'joining_date'  => now()->subYear()->toDateString(),
        ]);

        $template = HrLetterTemplate::where('code', 'warning')->first();
        $this->actingAs($admin)->post(route('admin.hrm.letters.store'), [
            'employee_id' => $employee->id,
            'template_id' => $template->id,
        ]);

        $this->actingAs($admin)->post(route('admin.hrm.discipline.store'), [
            'employee_id'   => $employee->id,
            'action_type'   => 'verbal_warning',
            'incident_date' => now()->toDateString(),
            'description'   => 'Minor issue',
        ]);

        $this->actingAs($admin)->get(route('admin.hrm.employees.show', $employee))
            ->assertOk()
            ->assertSee('HR Letters')
            ->assertSee('Disciplinary');
    }
}
