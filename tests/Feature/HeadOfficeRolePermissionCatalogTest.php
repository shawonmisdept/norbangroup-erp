<?php

namespace Tests\Feature;

use App\Support\HeadOfficeRolePermissionCatalog;
use Tests\TestCase;

class HeadOfficeRolePermissionCatalogTest extends TestCase
{
    public function test_gm_designation_gets_full_hrm_module_access(): void
    {
        $permissions = HeadOfficeRolePermissionCatalog::permissionsFor('Admin-GM');

        $this->assertContains('hrm.employees.manage', $permissions);
        $this->assertContains('hrm.leave.approve', $permissions);
        $this->assertContains('hrm.recruitment.postings.approve', $permissions);
    }

    public function test_executive_designation_gets_view_only_orders(): void
    {
        $permissions = HeadOfficeRolePermissionCatalog::permissionsFor('Commercial-Executive');

        $this->assertContains('orders.view', $permissions);
        $this->assertContains('orders.download', $permissions);
        $this->assertNotContains('orders.update', $permissions);
    }

    public function test_manager_designation_gets_orders_update(): void
    {
        $permissions = HeadOfficeRolePermissionCatalog::permissionsFor('Commercial-Manager');

        $this->assertContains('orders.view', $permissions);
        $this->assertContains('orders.update', $permissions);
    }

    public function test_cfo_designation_gets_salary_approve(): void
    {
        $permissions = HeadOfficeRolePermissionCatalog::permissionsFor('Accounts-CFO');

        $this->assertContains('hrm.finance.manage', $permissions);
        $this->assertContains('hrm.salary.approve', $permissions);
    }

    public function test_compliance_manager_override(): void
    {
        $permissions = HeadOfficeRolePermissionCatalog::permissionsFor('Admin-Manager (Comp)');

        $this->assertContains('hrm.compliance.manage', $permissions);
        $this->assertContains('hrm.leave.approve', $permissions);
        $this->assertNotContains('hrm.recruitment.postings.manage', $permissions);
    }

    public function test_merchandiser_designation_is_view_only_for_orders(): void
    {
        $permissions = HeadOfficeRolePermissionCatalog::permissionsFor('Merchandising-Merchandiser');

        $this->assertContains('orders.view', $permissions);
        $this->assertNotContains('orders.update', $permissions);
    }

    public function test_mis_data_entry_gets_orders_and_hrm_dashboard_with_employee_view(): void
    {
        $permissions = HeadOfficeRolePermissionCatalog::permissionsFor('MIS-Data Entry Operator');

        $this->assertContains('orders.view', $permissions);
        $this->assertContains('orders.download', $permissions);
        $this->assertContains('hrm.dashboard.view', $permissions);
        $this->assertContains('hrm.employees.view', $permissions);
        $this->assertNotContains('hrm.employees.manage', $permissions);
        $this->assertNotContains('hrm.leave.view', $permissions);
    }
}
