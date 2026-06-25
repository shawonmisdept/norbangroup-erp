<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\WorkerCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private User $hrAdmin;

    private Factory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create([
            'name'        => 'HR Admin',
            'permissions' => ['hrm.employees.view', 'hrm.employees.manage'],
        ]);

        $this->hrAdmin = User::create([
            'name'     => 'HR Admin',
            'email'    => 'hr-admin@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->factory = Factory::create([
            'name'      => 'Test Factory',
            'is_active' => true,
        ]);
    }

    public function test_hr_admin_can_view_employee_list(): void
    {
        $this->actingAs($this->hrAdmin)
            ->get(route('admin.hrm.employees.index'))
            ->assertOk()
            ->assertSee('Employee Registry');
    }

    public function test_hr_admin_can_enroll_employee(): void
    {
        $category = WorkerCategory::create(['name' => 'Operator', 'is_active' => true]);
        $department = Department::create([
            'name'       => 'Sewing',
            'factory_id' => $this->factory->id,
            'is_active'  => true,
        ]);

        $response = $this->actingAs($this->hrAdmin)
            ->post(route('admin.hrm.employees.store'), [
                'factory_id'         => $this->factory->id,
                'department_id'      => $department->id,
                'worker_category_id' => $category->id,
                'employee_code'      => 'M-E001',
                'name'               => 'Karim Uddin',
                'email'              => 'karim@example.com',
                'phone'              => '01711111111',
                'nid_number'         => '1234567890123',
                'status'             => 'active',
                'joining_date'       => '2026-01-01',
            ]);

        $employee = Employee::first();
        $this->assertNotNull($employee);
        $this->assertSame('Karim Uddin', $employee->name);
        $this->assertSame('M-E001', $employee->employee_code);
        $this->assertSame('karim@example.com', $employee->email);
        $this->assertSame('01711111111', $employee->phone);
        $this->assertSame($department->id, $employee->department_id);

        $response->assertRedirect(route('admin.hrm.employees.show', $employee));
    }

    public function test_employee_index_shows_required_columns(): void
    {
        $department = Department::create([
            'name'       => 'HR',
            'factory_id' => $this->factory->id,
            'is_active'  => true,
        ]);
        $designation = \App\Models\Designation::create([
            'name'          => 'HR Manager',
            'department_id' => $department->id,
            'is_active'     => true,
        ]);

        Employee::create([
            'factory_id'     => $this->factory->id,
            'department_id'  => $department->id,
            'designation_id' => $designation->id,
            'employee_code'  => 'M-E123',
            'name'           => 'Maxamed Iman',
            'status'         => 'active',
        ]);

        $this->actingAs($this->hrAdmin)
            ->get(route('admin.hrm.employees.index'))
            ->assertOk()
            ->assertSee('SN')
            ->assertSee('Employee ID')
            ->assertSee('Employee Name')
            ->assertSee('Department')
            ->assertSee('Designation')
            ->assertSee('M-E123')
            ->assertSee('Maxamed Iman')
            ->assertSee('HR Manager');
    }

    public function test_employee_show_has_setup_and_official_tabs(): void
    {
        $employee = Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => 'M-E999',
            'name'          => 'Tab Test Worker',
            'phone'         => '01700000000',
            'nid_number'    => '9999999999999',
            'status'        => 'active',
        ]);

        $this->actingAs($this->hrAdmin)
            ->get(route('admin.hrm.employees.show', $employee))
            ->assertOk()
            ->assertSee('Employee Setup')
            ->assertSee('Official Setup')
            ->assertSee('Tab Test Worker')
            ->assertSee('01700000000')
            ->assertSee('9999999999999');
    }

    public function test_employee_create_form_has_setup_fields(): void
    {
        $this->actingAs($this->hrAdmin)
            ->get(route('admin.hrm.employees.create'))
            ->assertOk()
            ->assertSee('Employee Setup')
            ->assertSee('Official Setup')
            ->assertSee('Educational History')
            ->assertSee('Employment History')
            ->assertSee('Employee ID')
            ->assertSee('Reporting Person')
            ->assertSee('NID Copy Upload')
            ->assertSee('Nominee ID Upload')
            ->assertSee('name="employee_code"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="reporting_to_id"', false);
    }

    public function test_hr_admin_can_enroll_employee_with_reporting_person(): void
    {
        $department = Department::create([
            'name'       => 'Sewing',
            'factory_id' => $this->factory->id,
            'is_active'  => true,
        ]);

        $manager = Employee::create([
            'factory_id'    => $this->factory->id,
            'department_id' => $department->id,
            'employee_code' => 'M-M001',
            'name'          => 'Line Manager',
            'status'        => 'active',
        ]);

        $response = $this->actingAs($this->hrAdmin)
            ->post(route('admin.hrm.employees.store'), [
                'factory_id'      => $this->factory->id,
                'department_id'   => $department->id,
                'reporting_to_id' => $manager->id,
                'employee_code'   => 'M-E002',
                'name'            => 'Reportee Worker',
                'status'          => 'active',
            ]);

        $employee = Employee::where('employee_code', 'M-E002')->first();
        $this->assertNotNull($employee);
        $this->assertSame($manager->id, $employee->reporting_to_id);

        $response->assertRedirect(route('admin.hrm.employees.show', $employee));
    }

    public function test_employee_cannot_report_to_self(): void
    {
        $employee = Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => 'M-E888',
            'name'          => 'Self Report Test',
            'status'        => 'active',
        ]);

        $this->actingAs($this->hrAdmin)
            ->put(route('admin.hrm.employees.update', $employee), [
                'factory_id'      => $this->factory->id,
                'reporting_to_id' => $employee->id,
                'employee_code'   => 'M-E888',
                'name'            => 'Self Report Test',
                'status'          => 'active',
            ])
            ->assertSessionHasErrors('reporting_to_id');
    }

    public function test_employee_can_be_enrolled_with_documents_and_histories(): void
    {
        $department = Department::create([
            'name'       => 'HR',
            'factory_id' => $this->factory->id,
            'is_active'  => true,
        ]);

        $nidFile = \Illuminate\Http\UploadedFile::fake()->create('nid.pdf', 100, 'application/pdf');
        $nomineePhoto = \Illuminate\Http\UploadedFile::fake()->image('nominee.jpg', 200, 200);

        $response = $this->actingAs($this->hrAdmin)
            ->post(route('admin.hrm.employees.store'), [
                'factory_id'      => $this->factory->id,
                'department_id'   => $department->id,
                'employee_code'   => 'M-E500',
                'name'            => 'Document Worker',
                'status'          => 'active',
                'nid_number'      => '111122223333',
                'nominee_name'    => 'Nominee One',
                'nominee_nid'     => '999988887777',
                'nid_document'    => $nidFile,
                'nominee_photo'   => $nomineePhoto,
                'education_history' => [
                    ['degree' => 'BSc', 'institution' => 'DU', 'passing_year' => '2020', 'result' => '3.50'],
                ],
                'employment_history' => [
                    ['company_name' => 'Old Co', 'designation' => 'Helper', 'joining_date' => '2018-01-01', 'leaving_date' => '2019-12-31'],
                ],
            ]);

        $employee = Employee::first();
        $this->assertNotNull($employee);
        $this->assertNotNull($employee->nid_document);
        $this->assertNotNull($employee->nominee_photo);
        $this->assertSame(1, $employee->educationHistories()->count());
        $this->assertSame('BSc', $employee->educationHistories->first()->degree);
        $this->assertSame(1, $employee->employmentHistories()->count());
        $this->assertSame('Old Co', $employee->employmentHistories->first()->company_name);

        $response->assertRedirect(route('admin.hrm.employees.show', $employee));
    }

    public function test_hr_admin_can_update_employee(): void
    {
        $department = Department::create([
            'name'       => 'Sewing',
            'factory_id' => $this->factory->id,
            'is_active'  => true,
        ]);

        $employee = Employee::create([
            'factory_id'    => $this->factory->id,
            'department_id' => $department->id,
            'employee_code' => 'M-E777',
            'name'          => 'Before Edit',
            'phone'         => '01710000000',
            'email'         => 'before@example.com',
            'status'        => 'active',
        ]);

        $employee->educationHistories()->create([
            'degree' => 'SSC',
            'institution' => 'Local School',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($this->hrAdmin)
            ->put(route('admin.hrm.employees.update', $employee), [
                'factory_id'    => $this->factory->id,
                'department_id' => $department->id,
                'employee_code' => 'M-E777',
                'name'          => 'After Edit',
                'phone'         => '01720000000',
                'email'         => 'after@example.com',
                'status'        => 'active',
                'education_history' => [
                    ['degree' => 'HSC', 'institution' => 'City College', 'passing_year' => '2018'],
                ],
            ]);

        $employee->refresh();
        $this->assertSame('After Edit', $employee->name);
        $this->assertSame('01720000000', $employee->phone);
        $this->assertSame('after@example.com', $employee->email);
        $this->assertSame('HSC', $employee->educationHistories()->first()->degree);

        $response->assertRedirect(route('admin.hrm.employees.show', $employee));

        $this->actingAs($this->hrAdmin)
            ->get(route('admin.hrm.employees.edit', $employee))
            ->assertOk()
            ->assertSee('After Edit')
            ->assertSee('after@example.com')
            ->assertSee('01720000000')
            ->assertSee('HSC');
    }

    public function test_employee_list_supports_search(): void
    {
        Employee::create([
            'factory_id'    => $this->factory->id,
            'employee_code' => $this->factory->code . '-00001',
            'name'          => 'Searchable Worker',
            'status'        => 'active',
        ]);

        $this->actingAs($this->hrAdmin)
            ->get(route('admin.hrm.employees.index', ['search' => 'Searchable']))
            ->assertOk()
            ->assertSee('Searchable Worker');
    }

    public function test_user_without_permission_cannot_access_employees(): void
    {
        $role = Role::create([
            'name'        => 'No HR',
            'permissions' => ['orders.view'],
        ]);

        $user = User::create([
            'name'     => 'No Access',
            'email'    => 'no-hr@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.hrm.employees.index'))
            ->assertForbidden();
    }
}
