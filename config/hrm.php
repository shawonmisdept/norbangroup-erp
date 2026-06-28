<?php

use App\Models\Factory;
use App\Models\Hrm\BiometricDevice;
use App\Models\Hrm\Building;
use App\Models\Hrm\EmploymentType;
use App\Models\Hrm\Floor;
use App\Models\Hrm\Holiday;
use App\Models\Hrm\LeaveType;
use App\Models\Hrm\Line;
use App\Models\Hrm\Shift;
use App\Models\Hrm\WorkerCategory;

return [

    'permissions' => [
        'global' => [
            'hrm.masters.view'   => 'View all HRM master modules',
            'hrm.masters.manage' => 'Manage all HRM master modules',
            'hrm.dashboard.view' => 'View HRM dashboard (included in any HRM module access)',
        ],
        'employees' => [
            'hrm.employees.view'              => 'View employees',
            'hrm.employees.manage'            => 'Manage employees (enroll, edit)',
            'hrm.employees.separation.view'   => 'View separation / exit requests',
            'hrm.employees.separation.manage' => 'Initiate separation (resign, terminate)',
            'hrm.employees.separation.approve' => 'Approve separation requests (HR)',
            'hrm.employees.letters.view'      => 'View HR letters & templates',
            'hrm.employees.letters.manage'    => 'Issue HR letters to employees',
            'hrm.employees.discipline.view'   => 'View disciplinary records',
            'hrm.employees.discipline.manage' => 'Record warnings, suspensions & misconduct',
            'hrm.employees.promotion.view'    => 'View promotion & demotion requests',
            'hrm.employees.promotion.manage'  => 'Initiate promotion / demotion requests',
            'hrm.employees.promotion.approve' => 'Approve promotion / demotion requests (HR)',
        ],
        'recruitment' => [
            'hrm.recruitment.postings.view'            => 'View job postings',
            'hrm.recruitment.postings.manage'          => 'Manage job postings',
            'hrm.recruitment.applications.view'       => 'View recruitment applications',
            'hrm.recruitment.applications.manage'    => 'Manage applications & pipeline',
            'hrm.recruitment.applications.convert'   => 'Convert applicant to employee',
        ],
        'attendance' => [
            'hrm.attendance.view'    => 'View attendance & punch logs',
            'hrm.attendance.sync'    => 'Sync ZKTeco SpeedFace / ADMS devices',
            'hrm.attendance.manage'  => 'Manage attendance settings (all sub-modules)',
            'hrm.attendance.approve' => 'Approve late acceptance applications',
        ],
        'leave' => [
            'hrm.leave.view'    => 'View all Leave sub-modules',
            'hrm.leave.manage'  => 'Manage all Leave sub-modules',
            'hrm.leave.approve' => 'Approve leave applications',
        ],
        'salary' => [
            'hrm.salary.view'    => 'View all Salary sub-modules',
            'hrm.salary.manage'  => 'Manage all Salary sub-modules',
            'hrm.salary.approve' => 'Close salary & bank export',
        ],
        'compliance' => [
            'hrm.compliance.view'   => 'View compliance reports & registers',
            'hrm.compliance.manage' => 'Manage bonus, gratuity & compliance runs',
        ],
        'finance' => [
            'hrm.finance.view'   => 'View tax, PF, loan & F&F modules',
            'hrm.finance.manage' => 'Manage tax slabs, PF accounts, loans & final settlement',
        ],
        'rmg' => [
            'hrm.rmg.view'   => 'View RMG extras modules',
            'hrm.rmg.manage' => 'Manage RMG extras modules',
        ],
        'performance' => [
            'hrm.performance.view'    => 'View performance reviews & cycles',
            'hrm.performance.manage'  => 'Manage cycles, templates & HR proxy rating',
            'hrm.performance.rate'    => 'Submit performance ratings (reporting person)',
            'hrm.performance.approve' => 'Approve performance reviews (HR)',
            'hrm.performance.bonus.view'   => 'View performance bonus bands & runs',
            'hrm.performance.bonus.manage' => 'Calculate & approve performance bonus runs',
            'hrm.performance.increment.view'   => 'View performance increment bands & runs',
            'hrm.performance.increment.manage' => 'Calculate & apply annual increments from reviews',
        ],
        // Legacy alias — kept for backward compatibility in role checks
        'payroll' => [
            'hrm.payroll.view'    => 'View payroll (legacy — use Salary)',
            'hrm.payroll.manage'  => 'Manage payroll (legacy — use Salary)',
            'hrm.payroll.approve' => 'Approve payroll (legacy — use Salary)',
        ],
    ],

    'adms' => [
        'pull_path'  => env('HRM_ADMS_PULL_PATH', '/api/attendance'),
        'api_token'  => env('HRM_ADMS_API_TOKEN'),
        'push_token' => env('HRM_ADMS_PUSH_TOKEN'),
        'timeout'    => (int) env('HRM_ADMS_TIMEOUT', 30),
        'sync_every_minutes' => (int) env('HRM_ADMS_SYNC_EVERY', 10),
        'timezone'   => env('HRM_ADMS_TIMEZONE', '+6:00'),
        'device_model' => 'ZKTeco SpeedFace V5L',
    ],

    'employee_options' => [
        'genders' => [
            'male'   => 'Male',
            'female' => 'Female',
            'other'  => 'Other',
        ],
        'blood_groups' => [
            'A+'  => 'A+',
            'A-'  => 'A-',
            'B+'  => 'B+',
            'B-'  => 'B-',
            'AB+' => 'AB+',
            'AB-' => 'AB-',
            'O+'  => 'O+',
            'O-'  => 'O-',
        ],
    ],

    'separation_types' => [
        'resigned' => [
            'label'           => 'Resignation',
            'employee_status' => 'resigned',
            'portal_allowed'  => true,
        ],
        'terminated' => [
            'label'           => 'Termination',
            'employee_status' => 'terminated',
            'portal_allowed'  => false,
        ],
        'retirement' => [
            'label'           => 'Retirement',
            'employee_status' => 'resigned',
            'portal_allowed'  => false,
        ],
        'layoff' => [
            'label'           => 'Layoff',
            'employee_status' => 'terminated',
            'portal_allowed'  => false,
        ],
        'absconding' => [
            'label'           => 'Absconding',
            'employee_status' => 'terminated',
            'portal_allowed'  => false,
        ],
    ],

    'letter_types' => [
        'appointment'  => 'Appointment Letter',
        'confirmation' => 'Confirmation Letter',
        'promotion'    => 'Promotion Letter',
        'transfer'     => 'Transfer Letter',
        'warning'      => 'Warning Letter',
        'suspension'   => 'Suspension Letter',
        'termination'  => 'Termination Letter',
        'experience'   => 'Experience Certificate',
        'relieving'    => 'Relieving Letter',
    ],

    'disciplinary_types' => [
        'show_cause'       => 'Show Cause Notice',
        'verbal_warning'   => 'Verbal Warning',
        'written_warning'  => 'Written Warning',
        'suspension'     => 'Suspension',
        'misconduct'       => 'Misconduct Log',
    ],

    'exit_clearance_departments' => [
        'hr'         => 'HR',
        'it'         => 'IT',
        'stores'     => 'Stores',
        'accounts'   => 'Accounts',
        'line_chief' => 'Line Chief',
    ],

    'recruitment_statuses' => [
        'applied'    => 'Applied',
        'screening'  => 'Screening',
        'interview'  => 'Interview',
        'selected'   => 'Selected',
        'offered'    => 'Offered',
        'hired'      => 'Hired',
        'rejected'   => 'Rejected',
        'withdrawn'  => 'Withdrawn',
    ],

    'recruitment_sources' => [
        'online'    => 'Online Portal',
        'walk_in'   => 'Walk-in',
        'referral'  => 'Referral',
        'hr_manual' => 'HR Manual Entry',
    ],

    'recruitment_referral_sources' => [
        'website'   => 'Company Website',
        'facebook'  => 'Facebook',
        'newspaper' => 'Newspaper',
        'friend'    => 'Friend / Referral',
        'other'     => 'Other',
    ],

    'recruitment_offer_template' => <<<'TXT'
Date: {{date}}

To,
{{candidate_name}}
{{present_address}}

Subject: Offer of Employment — {{job_title}}

Dear {{candidate_name}},

We are pleased to offer you employment with {{factory_name}} for the position of {{designation}} under the {{department}} department.

Position: {{job_title}}
Proposed joining date: {{joining_date}}
Offered salary: {{offered_salary}}

This offer is subject to satisfactory verification of your documents and medical fitness as required by company policy.

Please contact the HR department to confirm your acceptance.

Sincerely,
Human Resources Department
{{factory_name}}
TXT,

    'employee_wizard_steps' => [
        'setup'      => 'Employee Setup',
        'official'   => 'Official Setup',
        'personal'   => 'Personal Info',
        'contact'    => 'Contact & Address',
        'family'     => 'Emergency & Nominee',
        'education'  => 'Educational History',
        'employment' => 'Employment History',
    ],

    'employee_tabs' => [
        'setup'      => 'Employee Setup',
        'official'   => 'Official Setup',
        'personal'   => 'Personal Info',
        'contact'    => 'Contact & Address',
        'family'     => 'Emergency & Nominee',
        'education'  => 'Educational History',
        'employment' => 'Employment History',
        'service'    => 'Service History',
    ],

    'employee_tab_fields' => [
        'setup' => [
            'employee_code', 'name', 'email', 'phone', 'factory_id', 'status',
            'department_id', 'designation_id', 'worker_category_id', 'employment_type_id',
            'building_id', 'floor_id', 'line_id', 'shift_id',
            'joining_date', 'confirmation_date', 'probation_end_date', 'contract_end_date',
            'reporting_to_id', 'enable_portal', 'portal_password', 'notes',
        ],
        'official' => [
            'photo', 'nid_number', 'nid_document', 'birth_certificate_no',
            'birth_certificate_document', 'biometric_user_id',
        ],
        'personal' => ['name_bangla', 'gender', 'date_of_birth', 'blood_group'],
        'contact'  => ['present_address', 'permanent_address'],
        'family'   => [
            'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation',
            'nominee_name', 'nominee_relation', 'nominee_nid', 'nominee_nid_document', 'nominee_photo',
        ],
        'education'  => ['education_history'],
        'employment' => ['employment_history'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Employee sub-modules (lifecycle, letters, discipline)
    |--------------------------------------------------------------------------
    */
    'employee_submodules' => [
        'dashboard' => [
            'label'       => 'Dashboard',
            'description' => 'Headcount, joinings, pending exit & promotion stats',
            'permission'  => 'hrm.employees.view',
            'manage'      => 'hrm.employees.manage',
            'route'       => 'admin.hrm.employee.dashboard',
            'status'      => 'active',
        ],
        'employees' => [
            'label'       => 'Employees',
            'description' => 'Enroll, edit profiles, ID cards & portal accounts',
            'permission'  => 'hrm.employees.view',
            'manage'      => 'hrm.employees.manage',
            'route'       => 'admin.hrm.employees.index',
            'status'      => 'active',
        ],
        'separations' => [
            'label'       => 'Separations',
            'description' => 'Resignation, termination & exit workflow',
            'permission'  => 'hrm.employees.separation.view',
            'manage'      => 'hrm.employees.separation.manage',
            'route'       => 'admin.hrm.separations.index',
            'status'      => 'active',
        ],
        'promotions' => [
            'label'       => 'Promotion / Demotion',
            'description' => 'Designation change & salary revision workflow',
            'permission'  => 'hrm.employees.promotion.view',
            'manage'      => 'hrm.employees.promotion.manage',
            'route'       => 'admin.hrm.promotions.index',
            'status'      => 'active',
        ],
        'letters' => [
            'label'       => 'HR Letters',
            'description' => 'Appointment, confirmation, warning & certificates',
            'permission'  => 'hrm.employees.letters.view',
            'manage'      => 'hrm.employees.letters.manage',
            'route'       => 'admin.hrm.letters.index',
            'status'      => 'active',
        ],
        'discipline' => [
            'label'       => 'Disciplinary',
            'description' => 'Warnings, suspensions & misconduct records',
            'permission'  => 'hrm.employees.discipline.view',
            'manage'      => 'hrm.employees.discipline.manage',
            'route'       => 'admin.hrm.discipline.index',
            'status'      => 'active',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Recruitment sub-modules
    |--------------------------------------------------------------------------
    */
    'recruitment_submodules' => [
        'dashboard' => [
            'label'       => 'Dashboard',
            'description' => 'Pipeline stats, interviews & conversion metrics',
            'permission'  => 'hrm.recruitment.applications.view',
            'manage'      => 'hrm.recruitment.applications.manage',
            'route'       => 'admin.hrm.recruitment.dashboard',
            'status'      => 'active',
        ],
        'postings' => [
            'label'       => 'Job Postings',
            'description' => 'Publish roles on the careers portal',
            'permission'  => 'hrm.recruitment.postings.view',
            'manage'      => 'hrm.recruitment.postings.manage',
            'route'       => 'admin.hrm.recruitment.postings.index',
            'status'      => 'active',
        ],
        'applications' => [
            'label'       => 'Applications',
            'description' => 'Candidate pipeline, interviews & offer letters',
            'permission'  => 'hrm.recruitment.applications.view',
            'manage'      => 'hrm.recruitment.applications.manage',
            'route'       => 'admin.hrm.recruitment.applications.index',
            'status'      => 'active',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Leave sub-modules (separate screens, routes, permissions)
    |--------------------------------------------------------------------------
    */
    'leave_submodules' => [
        'dashboard' => [
            'label'       => 'Dashboard',
            'description' => 'Pending approvals, pipeline & on-leave today',
            'permission'  => 'hrm.leave.view',
            'manage'      => 'hrm.leave.manage',
            'route'       => 'admin.hrm.leave.dashboard',
            'status'      => 'active',
        ],
        'policies' => [
            'label'       => 'Leave Policies',
            'description' => 'Factory-wise entitlement per leave type',
            'permission'  => 'hrm.leave.policies.view',
            'manage'      => 'hrm.leave.policies.manage',
            'route'       => 'admin.hrm.leave.policies.index',
            'status'      => 'active',
        ],
        'rules' => [
            'label'       => 'Leave Rules',
            'description' => 'Eligibility by category, tenure, gender',
            'permission'  => 'hrm.leave.rules.view',
            'manage'      => 'hrm.leave.rules.manage',
            'route'       => 'admin.hrm.leave.rules.index',
            'status'      => 'active',
        ],
        'maternity-rules' => [
            'label'       => 'Maternity Rules',
            'description' => 'Maternity leave duration & pay rules',
            'permission'  => 'hrm.leave.maternity-rules.view',
            'manage'      => 'hrm.leave.maternity-rules.manage',
            'route'       => 'admin.hrm.leave.maternity-rules.index',
            'status'      => 'active',
        ],
        'opening-balances' => [
            'label'       => 'Opening Balance',
            'description' => 'Year-start or join balance per employee',
            'permission'  => 'hrm.leave.opening-balances.view',
            'manage'      => 'hrm.leave.opening-balances.manage',
            'route'       => 'admin.hrm.leave.opening-balances.index',
            'status'      => 'active',
        ],
        'maternity-transactions' => [
            'label'       => 'Maternity Transaction',
            'description' => 'Maternity benefit cases & leave linkage',
            'permission'  => 'hrm.leave.maternity-transactions.view',
            'manage'      => 'hrm.leave.maternity-transactions.manage',
            'route'       => 'admin.hrm.leave.maternity-transactions.index',
            'status'      => 'active',
        ],
        'transactions' => [
            'label'       => 'Leave Transaction',
            'description' => 'Applications, approvals, adjustments',
            'permission'  => 'hrm.leave.transactions.view',
            'manage'      => 'hrm.leave.transactions.manage',
            'route'       => 'admin.hrm.leave.transactions.index',
            'status'      => 'active',
        ],
        'allocation' => [
            'label'       => 'Allocation Process',
            'description' => 'Monthly / yearly leave accrual run',
            'permission'  => 'hrm.leave.allocation.view',
            'manage'      => 'hrm.leave.allocation.manage',
            'route'       => 'admin.hrm.leave.allocation.index',
            'status'      => 'active',
        ],
        'bulk-entry' => [
            'label'       => 'Leave Entry Bulk',
            'description' => 'CSV / Excel bulk leave entry',
            'permission'  => 'hrm.leave.bulk-entry.view',
            'manage'      => 'hrm.leave.bulk-entry.manage',
            'route'       => 'admin.hrm.leave.bulk-entry.index',
            'status'      => 'active',
        ],
    ],

    'leave_nav_groups' => [
        'Setup & Policy'  => ['policies', 'rules', 'maternity-rules'],
        'Balances & Ledger' => ['opening-balances', 'transactions', 'maternity-transactions'],
        'Processing'      => ['allocation', 'bulk-entry'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance sub-modules
    |--------------------------------------------------------------------------
    */
    'performance_submodules' => [
        'dashboard' => [
            'label'       => 'Dashboard',
            'description' => 'Pending ratings, open cycles & bonus/increment runs',
            'permission'  => 'hrm.performance.view',
            'manage'      => 'hrm.performance.manage',
            'route'       => 'admin.hrm.performance.dashboard',
            'status'      => 'active',
        ],
        'cycles' => [
            'label'       => 'Review Cycles',
            'description' => 'Open probation, mid-year & annual review batches',
            'permission'  => 'hrm.performance.view',
            'manage'      => 'hrm.performance.manage',
            'route'       => 'admin.hrm.performance.cycles.index',
            'status'      => 'active',
        ],
        'templates' => [
            'label'       => 'Score Templates',
            'description' => 'Hybrid criteria & weight configuration',
            'permission'  => 'hrm.performance.view',
            'manage'      => 'hrm.performance.manage',
            'route'       => 'admin.hrm.performance.templates.index',
            'status'      => 'active',
        ],
        'reviews' => [
            'label'       => 'Reviews',
            'description' => 'Rate, approve & track employee performance',
            'permission'  => 'hrm.performance.view',
            'manage'      => 'hrm.performance.rate',
            'route'       => 'admin.hrm.performance.reviews.index',
            'status'      => 'active',
        ],
        'bonus-bands' => [
            'label'       => 'Bonus Bands',
            'description' => 'Score-to-bonus % mapping per factory',
            'permission'  => 'hrm.performance.bonus.view',
            'manage'      => 'hrm.performance.bonus.manage',
            'route'       => 'admin.hrm.performance.bonus-bands.index',
            'status'      => 'active',
        ],
        'bonus-runs' => [
            'label'       => 'Performance Bonus',
            'description' => 'Mid-year bonus runs from approved reviews',
            'permission'  => 'hrm.performance.bonus.view',
            'manage'      => 'hrm.performance.bonus.manage',
            'route'       => 'admin.hrm.performance.bonus-runs.index',
            'status'      => 'active',
        ],
        'increment-bands' => [
            'label'       => 'Increment Bands',
            'description' => 'Score-to-increment % for annual salary revision',
            'permission'  => 'hrm.performance.increment.view',
            'manage'      => 'hrm.performance.increment.manage',
            'route'       => 'admin.hrm.performance.increment-bands.index',
            'status'      => 'active',
        ],
        'increment-runs' => [
            'label'       => 'Annual Increment',
            'description' => 'Apply salary increments from annual reviews',
            'permission'  => 'hrm.performance.increment.view',
            'manage'      => 'hrm.performance.increment.manage',
            'route'       => 'admin.hrm.performance.increment-runs.index',
            'status'      => 'active',
        ],
    ],

    'performance' => [
        'minimum_pass_score' => 60,
        'late_day_penalty'   => 5,
        'discipline_penalties' => [
            'verbal_warning'  => 5,
            'written_warning' => 10,
            'show_cause'      => 15,
            'suspension'      => 30,
        ],
        'default_criteria' => [
            ['code' => 'attendance', 'label' => 'Attendance', 'criterion_type' => 'auto', 'weight' => 25, 'sort_order' => 1],
            ['code' => 'punctuality', 'label' => 'Punctuality', 'criterion_type' => 'auto', 'weight' => 15, 'sort_order' => 2],
            ['code' => 'discipline', 'label' => 'Discipline', 'criterion_type' => 'auto', 'weight' => 10, 'sort_order' => 3],
            ['code' => 'work_quality', 'label' => 'Work Quality', 'criterion_type' => 'manual', 'weight' => 30, 'sort_order' => 4],
            ['code' => 'behaviour', 'label' => 'Behaviour & Teamwork', 'criterion_type' => 'manual', 'weight' => 20, 'sort_order' => 5],
        ],
        'bonus_base_default' => 'gross',
        'default_bonus_bands' => [
            ['name' => 'Outstanding', 'min_score' => 90, 'max_score' => 100, 'bonus_percent' => 100, 'sort_order' => 1],
            ['name' => 'Good', 'min_score' => 75, 'max_score' => 89.99, 'bonus_percent' => 75, 'sort_order' => 2],
            ['name' => 'Average', 'min_score' => 60, 'max_score' => 74.99, 'bonus_percent' => 50, 'sort_order' => 3],
            ['name' => 'Poor', 'min_score' => 0, 'max_score' => 59.99, 'bonus_percent' => 0, 'sort_order' => 4],
        ],
        'default_increment_bands' => [
            ['name' => 'Outstanding', 'min_score' => 90, 'max_score' => 100, 'increment_percent' => 10, 'sort_order' => 1],
            ['name' => 'Good', 'min_score' => 75, 'max_score' => 89.99, 'increment_percent' => 7, 'sort_order' => 2],
            ['name' => 'Average', 'min_score' => 60, 'max_score' => 74.99, 'increment_percent' => 5, 'sort_order' => 3],
            ['name' => 'Poor', 'min_score' => 0, 'max_score' => 59.99, 'increment_percent' => 0, 'sort_order' => 4],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance sub-modules (Bangladesh labour law)
    |--------------------------------------------------------------------------
    */
    'compliance_submodules' => [
        'dashboard' => [
            'label'       => 'Dashboard',
            'description' => 'Festival bonus runs & gratuity settlement overview',
            'permission'  => 'hrm.compliance.view',
            'manage'      => 'hrm.compliance.manage',
            'route'       => 'admin.hrm.compliance.dashboard',
            'status'      => 'active',
        ],
        'registers' => [
            'label'       => 'Statutory Registers',
            'description' => 'Attendance, wage, leave & OT registers (BD format CSV)',
            'permission'  => 'hrm.compliance.registers.view',
            'manage'      => 'hrm.compliance.registers.manage',
            'route'       => 'admin.hrm.compliance.registers.index',
            'status'      => 'active',
        ],
        'bonus' => [
            'label'       => 'Festival Bonus',
            'description' => 'Eid / festival bonus calculation runs',
            'permission'  => 'hrm.compliance.bonus.view',
            'manage'      => 'hrm.compliance.bonus.manage',
            'route'       => 'admin.hrm.compliance.bonus.index',
            'status'      => 'active',
        ],
        'gratuity' => [
            'label'       => 'Gratuity Settlement',
            'description' => 'Gratuity on employee separation (5+ years)',
            'permission'  => 'hrm.compliance.gratuity.view',
            'manage'      => 'hrm.compliance.gratuity.manage',
            'route'       => 'admin.hrm.compliance.gratuity.index',
            'status'      => 'active',
        ],
        'age-verification' => [
            'label'       => 'Age Verification',
            'description' => 'Child labour prevention compliance report',
            'permission'  => 'hrm.compliance.age-verification.view',
            'manage'      => 'hrm.compliance.age-verification.manage',
            'route'       => 'admin.hrm.compliance.age-verification.index',
            'status'      => 'active',
        ],
        'working-hours' => [
            'label'       => 'Working Hour Limits',
            'description' => 'Daily & weekly hour limit violations',
            'permission'  => 'hrm.compliance.working-hours.view',
            'manage'      => 'hrm.compliance.working-hours.manage',
            'route'       => 'admin.hrm.compliance.working-hours.index',
            'status'      => 'active',
        ],
    ],

    'compliance_nav_groups' => [
        'Registers & Reports' => ['registers', 'age-verification', 'working-hours'],
        'Benefits'            => ['bonus', 'gratuity'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Finance sub-modules (Tax, PF, Loan)
    |--------------------------------------------------------------------------
    */
    'finance_submodules' => [
        'dashboard' => [
            'label'       => 'Dashboard',
            'description' => 'Loans, TDS, PF & final settlement snapshot',
            'permission'  => 'hrm.finance.view',
            'manage'      => 'hrm.finance.manage',
            'route'       => 'admin.hrm.finance.dashboard',
            'status'      => 'active',
        ],
        'tax' => [
            'label'       => 'Income Tax (TDS)',
            'description' => 'Assessment year slabs & employee TDS ledger',
            'permission'  => 'hrm.finance.tax.view',
            'manage'      => 'hrm.finance.tax.manage',
            'route'       => 'admin.hrm.finance.tax.index',
            'status'      => 'active',
        ],
        'pf' => [
            'label'       => 'Provident Fund',
            'description' => 'PF accounts & monthly contributions',
            'permission'  => 'hrm.finance.pf.view',
            'manage'      => 'hrm.finance.pf.manage',
            'route'       => 'admin.hrm.finance.pf.index',
            'status'      => 'active',
        ],
        'pf-report' => [
            'label'       => 'PF Employer Report',
            'description' => 'Monthly employer PF contribution report & CSV export',
            'permission'  => 'hrm.finance.pf.view',
            'manage'      => 'hrm.finance.pf.manage',
            'route'       => 'admin.hrm.finance.pf.employer-report',
            'status'      => 'active',
        ],
        'loans' => [
            'label'       => 'Loans & Advances',
            'description' => 'Salary advance, emergency loan & EMI recovery',
            'permission'  => 'hrm.finance.loans.view',
            'manage'      => 'hrm.finance.loans.manage',
            'route'       => 'admin.hrm.finance.loans.index',
            'status'      => 'active',
        ],
        'advance-bulk' => [
            'label'       => 'Bulk Festival Advance',
            'description' => 'Disburse salary advance to many employees before Eid/festival',
            'permission'  => 'hrm.finance.loans.view',
            'manage'      => 'hrm.finance.loans.manage',
            'route'       => 'admin.hrm.finance.loans.bulk',
            'status'      => 'active',
        ],
        'final-settlement' => [
            'label'       => 'Final Settlement (F&F)',
            'description' => 'Full & final on exit — gratuity, PF, loans, leave encashment & clearance',
            'permission'  => 'hrm.finance.settlement.view',
            'manage'      => 'hrm.finance.settlement.manage',
            'route'       => 'admin.hrm.finance.final-settlement.index',
            'status'      => 'active',
        ],
    ],

    'finance_nav_groups' => [
        'Statutory Deductions' => ['tax', 'pf', 'pf-report', 'loans', 'advance-bulk'],
        'Exit & Settlement'    => ['final-settlement'],
    ],

    /*
    |--------------------------------------------------------------------------
    | RMG extras sub-modules
    |--------------------------------------------------------------------------
    */
    'rmg_submodules' => [
        'dashboard' => [
            'label'       => 'Dashboard',
            'description' => 'Gate pass, transfers & proxy punch flags',
            'permission'  => 'hrm.rmg.view',
            'manage'      => 'hrm.rmg.manage',
            'route'       => 'admin.hrm.rmg.dashboard',
            'status'      => 'active',
        ],
        'worker-transfer' => [
            'label'       => 'Worker Transfer',
            'description' => 'Cross-line or cross-unit employee transfers with approval',
            'permission'  => 'hrm.rmg.worker-transfer.view',
            'manage'      => 'hrm.rmg.worker-transfer.manage',
            'route'       => 'admin.hrm.rmg.worker-transfer.index',
            'status'      => 'active',
        ],
        'osd-movement' => [
            'label'       => 'OSD / Movement',
            'description' => 'Official duty, buyer visit & training movements',
            'permission'  => 'hrm.rmg.osd-movement.view',
            'manage'      => 'hrm.rmg.osd-movement.manage',
            'route'       => 'admin.hrm.rmg.osd-movement.index',
            'status'      => 'active',
        ],
        'gate-pass' => [
            'label'       => 'Gate Pass',
            'description' => 'Employee gate-out passes with HR approval',
            'permission'  => 'hrm.rmg.gate-pass.view',
            'manage'      => 'hrm.rmg.gate-pass.manage',
            'route'       => 'admin.hrm.rmg.gate-pass.index',
            'status'      => 'active',
        ],
        'manpower-planning' => [
            'label'       => 'Manpower Planning',
            'description' => 'Daily line headcount plan vs attendance variance',
            'permission'  => 'hrm.rmg.manpower-planning.view',
            'manage'      => 'hrm.rmg.manpower-planning.manage',
            'route'       => 'admin.hrm.rmg.manpower-planning.index',
            'status'      => 'active',
        ],
        'proxy-punch' => [
            'label'       => 'Proxy Punch Flags',
            'description' => 'Flag suspicious biometric punches for review',
            'permission'  => 'hrm.rmg.proxy-punch.view',
            'manage'      => 'hrm.rmg.proxy-punch.manage',
            'route'       => 'admin.hrm.rmg.proxy-punch.index',
            'status'      => 'active',
        ],
        'canteen' => [
            'label'       => 'Canteen Deductions',
            'description' => 'Monthly meal count & canteen charge entries',
            'permission'  => 'hrm.rmg.canteen.view',
            'manage'      => 'hrm.rmg.canteen.manage',
            'route'       => 'admin.hrm.rmg.canteen.index',
            'status'      => 'active',
        ],
        'medical' => [
            'label'       => 'Medical Visits',
            'description' => 'Factory clinic visit log per employee',
            'permission'  => 'hrm.rmg.medical.view',
            'manage'      => 'hrm.rmg.medical.manage',
            'route'       => 'admin.hrm.rmg.medical.index',
            'status'      => 'active',
        ],
        'training' => [
            'label'       => 'Training Records',
            'description' => 'Safety, fire & buyer compliance training log',
            'permission'  => 'hrm.rmg.training.view',
            'manage'      => 'hrm.rmg.training.manage',
            'route'       => 'admin.hrm.rmg.training.index',
            'status'      => 'active',
        ],
        'sub-contract' => [
            'label'       => 'Sub-contract Workers',
            'description' => 'Agency manpower register by line',
            'permission'  => 'hrm.rmg.sub-contract.view',
            'manage'      => 'hrm.rmg.sub-contract.manage',
            'route'       => 'admin.hrm.rmg.sub-contract.index',
            'status'      => 'active',
        ],
        'production-incentive' => [
            'label'       => 'Production Incentive',
            'description' => 'Line output incentive calculation & approval',
            'permission'  => 'hrm.rmg.production-incentive.view',
            'manage'      => 'hrm.rmg.production-incentive.manage',
            'route'       => 'admin.hrm.rmg.production-incentive.index',
            'status'      => 'active',
        ],
        'salary-hold' => [
            'label'       => 'Salary Hold',
            'description' => 'Block payroll for employees under investigation',
            'permission'  => 'hrm.rmg.salary-hold.view',
            'manage'      => 'hrm.rmg.salary-hold.manage',
            'route'       => 'admin.hrm.rmg.salary-hold.index',
            'status'      => 'active',
        ],
        'cash-list' => [
            'label'       => 'Cash Payment List',
            'description' => 'Export net-pay cash workers by line for payroll',
            'permission'  => 'hrm.rmg.cash-list.view',
            'manage'      => 'hrm.rmg.cash-list.manage',
            'route'       => 'admin.hrm.rmg.cash-list.index',
            'status'      => 'active',
        ],
        'buyer-audit-export' => [
            'label'       => 'Buyer Audit Export',
            'description' => 'Attendance & wage register pack for buyer audits',
            'permission'  => 'hrm.rmg.buyer-audit-export.view',
            'manage'      => 'hrm.rmg.buyer-audit-export.manage',
            'route'       => 'admin.hrm.rmg.buyer-audit-export.index',
            'status'      => 'active',
        ],
        'buyer-holiday' => [
            'label'       => 'Buyer Holidays',
            'description' => 'Buyer-specific holiday calendar per factory',
            'permission'  => 'hrm.rmg.buyer-holiday.view',
            'manage'      => 'hrm.rmg.buyer-holiday.manage',
            'route'       => 'admin.hrm.rmg.buyer-holiday.index',
            'status'      => 'active',
        ],
    ],

    'rmg_nav_groups' => [
        'Movement & Gate'        => ['worker-transfer', 'osd-movement', 'gate-pass', 'sub-contract'],
        'Planning & Compliance'  => ['manpower-planning', 'proxy-punch', 'buyer-holiday', 'buyer-audit-export'],
        'Welfare & Training'     => ['canteen', 'medical', 'training'],
        'Payroll RMG'            => ['production-incentive', 'salary-hold', 'cash-list'],
    ],

    'finance' => [
        'default_pf_employee_rate' => 7.0,
        'default_pf_employer_rate' => 7.5,
        'default_tax_slabs' => [
            ['min' => 0, 'max' => 350000, 'rate' => 0],
            ['min' => 350001, 'max' => 450000, 'rate' => 5],
            ['min' => 450001, 'max' => 750000, 'rate' => 10],
            ['min' => 750001, 'max' => 1150000, 'rate' => 15],
            ['min' => 1150001, 'max' => 1650000, 'rate' => 20],
            ['min' => 1650001, 'max' => null, 'rate' => 25],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Attendance sub-modules
    |--------------------------------------------------------------------------
    */
    'attendance_submodules' => [
        'dashboard' => [
            'label'       => 'Dashboard',
            'description' => 'Today\'s attendance, late acceptance & open periods',
            'permission'  => 'hrm.attendance.view',
            'manage'      => 'hrm.attendance.manage',
            'route'       => 'admin.hrm.attendance.dashboard',
            'status'      => 'active',
        ],
        'sync' => [
            'label'       => 'Device Sync',
            'description' => 'ZKTeco ADMS sync, process today, device status',
            'permission'  => 'hrm.attendance.sync',
            'manage'      => 'hrm.attendance.sync',
            'route'       => 'admin.hrm.attendance.sync.index',
            'status'      => 'active',
        ],
        'punches' => [
            'label'       => 'Punch Logs',
            'description' => 'Raw biometric IN/OUT punches',
            'permission'  => 'hrm.attendance.view',
            'manage'      => 'hrm.attendance.manage',
            'route'       => 'admin.hrm.attendance.punches',
            'status'      => 'active',
        ],
        'daily' => [
            'label'       => 'Daily Summary',
            'description' => 'Processed attendance with late acceptance status',
            'permission'  => 'hrm.attendance.view',
            'manage'      => 'hrm.attendance.manage',
            'route'       => 'admin.hrm.attendance.daily',
            'status'      => 'active',
        ],
        'periods' => [
            'label'       => 'Periods',
            'description' => 'Monthly process, freeze, employee summary',
            'permission'  => 'hrm.attendance.view',
            'manage'      => 'hrm.attendance.manage',
            'route'       => 'admin.hrm.attendance.periods',
            'status'      => 'active',
        ],
        'policy' => [
            'label'       => 'Policy',
            'description' => 'Late grace, consecutive late rule, deduction basis',
            'permission'  => 'hrm.attendance.policy.view',
            'manage'      => 'hrm.attendance.policy.manage',
            'route'       => 'admin.hrm.attendance.policy.index',
            'status'      => 'active',
        ],
        'late-acceptance' => [
            'label'       => 'Late Acceptance',
            'description' => 'Employee late forgiveness applications',
            'permission'  => 'hrm.attendance.late-acceptance.view',
            'manage'      => 'hrm.attendance.late-acceptance.manage',
            'route'       => 'admin.hrm.attendance.late-acceptance.index',
            'status'      => 'active',
        ],
        'half-day-entry' => [
            'label'       => 'Half Day Entry',
            'description' => 'HR manual first/second half day entries',
            'permission'  => 'hrm.attendance.half-day-entry.view',
            'manage'      => 'hrm.attendance.half-day-entry.manage',
            'route'       => 'admin.hrm.attendance.half-day-entry.index',
            'status'      => 'active',
        ],
        'manual-punch' => [
            'label'       => 'Manual Punch',
            'description' => 'Fix missed IN/OUT punches',
            'permission'  => 'hrm.attendance.manual-punch.view',
            'manage'      => 'hrm.attendance.manual-punch.manage',
            'route'       => 'admin.hrm.attendance.manual-punch.index',
            'status'      => 'active',
        ],
        'gate-points' => [
            'label'       => 'Gate QR Points',
            'description' => 'Print QR codes for mobile gate check-in',
            'permission'  => 'hrm.attendance.gate-points.view',
            'manage'      => 'hrm.attendance.gate-points.manage',
            'route'       => 'admin.hrm.attendance.gate-points.index',
            'status'      => 'active',
        ],
        'reports' => [
            'label'       => 'Reports',
            'description' => 'Monthly summary, department/line breakdown, employee calendar',
            'permission'  => 'hrm.attendance.view',
            'manage'      => 'hrm.attendance.view',
            'route'       => 'admin.hrm.attendance.reports.index',
            'status'      => 'active',
        ],
        'roster' => [
            'label'       => 'Shift Roster',
            'description' => 'Weekly shift assignments by employee & line — bulk Excel/CSV import on roster detail',
            'permission'  => 'hrm.attendance.roster.view',
            'manage'      => 'hrm.attendance.roster.manage',
            'route'       => 'admin.hrm.attendance.roster.index',
            'status'      => 'active',
        ],
    ],

    'attendance_nav_groups' => [
        'Device & Biometric' => ['sync', 'punches'],
        'Daily Records'      => ['daily', 'periods', 'reports'],
        'Scheduling'         => ['roster'],
        'Rules & Policy'     => ['policy', 'late-acceptance'],
        'HR Adjustments'     => ['half-day-entry', 'manual-punch'],
        'Mobile Check-in'    => ['gate-points'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Salary sub-modules
    |--------------------------------------------------------------------------
    */
    'salary_submodules' => [
        'dashboard' => [
            'label'       => 'Dashboard',
            'description' => 'Salary setup coverage & payroll period status',
            'permission'  => 'hrm.salary.view',
            'manage'      => 'hrm.salary.manage',
            'route'       => 'admin.hrm.salary.dashboard',
            'status'      => 'active',
        ],
        'heads' => [
            'label'       => 'Head',
            'description' => 'Salary components (Basic, HRA, deductions)',
            'permission'  => 'hrm.salary.heads.view',
            'manage'      => 'hrm.salary.heads.manage',
            'route'       => 'admin.hrm.salary.heads.index',
            'status'      => 'active',
        ],
        'grades' => [
            'label'       => 'Grade',
            'description' => 'Salary grades (G1, Staff, Worker…)',
            'permission'  => 'hrm.salary.grades.view',
            'manage'      => 'hrm.salary.grades.manage',
            'route'       => 'admin.hrm.salary.grades.index',
            'status'      => 'active',
        ],
        'grade-details' => [
            'label'       => 'Grade Details',
            'description' => 'Amount per head per grade',
            'permission'  => 'hrm.salary.grade-details.view',
            'manage'      => 'hrm.salary.grade-details.manage',
            'route'       => 'admin.hrm.salary.grade-details.index',
            'status'      => 'active',
        ],
        'employee-salary' => [
            'label'       => 'Employee Salary',
            'description' => 'Assign grade / structure to employee',
            'permission'  => 'hrm.salary.employee-salary.view',
            'manage'      => 'hrm.salary.employee-salary.manage',
            'route'       => 'admin.hrm.salary.employee-salary.index',
            'status'      => 'active',
        ],
        'upload' => [
            'label'       => 'Upload Salary',
            'description' => 'Bulk upload employee salary',
            'permission'  => 'hrm.salary.upload.view',
            'manage'      => 'hrm.salary.upload.manage',
            'route'       => 'admin.hrm.salary.upload.index',
            'status'      => 'active',
        ],
        'process' => [
            'label'       => 'Salary Process',
            'description' => 'Month-end calculation from attendance',
            'permission'  => 'hrm.salary.process.view',
            'manage'      => 'hrm.salary.process.manage',
            'route'       => 'admin.hrm.salary.process.index',
            'status'      => 'active',
        ],
        'close' => [
            'label'       => 'Salary Close',
            'description' => 'Freeze period, lock, bank advise',
            'permission'  => 'hrm.salary.close.view',
            'manage'      => 'hrm.salary.close.manage',
            'route'       => 'admin.hrm.salary.close.index',
            'status'      => 'active',
        ],
        'increment-rules' => [
            'label'       => 'Increment Rule',
            'description' => 'Auto increment rules by grade / tenure',
            'permission'  => 'hrm.salary.increment-rules.view',
            'manage'      => 'hrm.salary.increment-rules.manage',
            'route'       => 'admin.hrm.salary.increment-rules.index',
            'status'      => 'active',
        ],
        'increment-bulk' => [
            'label'       => 'Increment Bulk',
            'description' => 'Bulk increment by filter / selection',
            'permission'  => 'hrm.salary.increment-bulk.view',
            'manage'      => 'hrm.salary.increment-bulk.manage',
            'route'       => 'admin.hrm.salary.increment-bulk.index',
            'status'      => 'active',
        ],
        'increment-upload' => [
            'label'       => 'Increment Upload',
            'description' => 'CSV increment upload',
            'permission'  => 'hrm.salary.increment-upload.view',
            'manage'      => 'hrm.salary.increment-upload.manage',
            'route'       => 'admin.hrm.salary.increment-upload.index',
            'status'      => 'active',
        ],
    ],

    'salary_nav_groups' => [
        'Salary Structure' => ['heads', 'grades', 'grade-details', 'employee-salary'],
        'Payroll Run'      => ['upload', 'process', 'close'],
        'Increments'       => ['increment-rules', 'increment-bulk', 'increment-upload'],
    ],

    'groups' => [
        'Organization'   => ['hrm-buildings', 'hrm-floors', 'hrm-lines'],
        'Work Schedule'  => ['hrm-shifts', 'hrm-holidays'],
        'Employee Setup' => ['hrm-worker-categories', 'hrm-employment-types', 'hrm-leave-types'],
        'Biometric'      => ['hrm-biometric-devices'],
    ],

    'modules' => [

        'hrm-buildings' => [
            'label'        => 'Building',
            'label_plural' => 'Buildings',
            'model'        => Building::class,
            'with'         => ['factory'],
            'fields' => [
                'factory_id'  => ['type' => 'relation', 'label' => 'Factory / Unit', 'required' => true, 'relation' => Factory::class, 'display' => 'name'],
                'name'        => ['type' => 'text', 'label' => 'Building Name', 'required' => true],
                'native_name' => ['type' => 'text', 'label' => 'Native Name', 'placeholder' => 'বাংলা নাম'],
                'description' => ['type' => 'textarea', 'label' => 'Description'],
                'is_active'   => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'native_name', 'factory_id', 'is_active'],
            'search'  => ['name', 'native_name', 'code'],
        ],

        'hrm-floors' => [
            'label'        => 'Floor',
            'label_plural' => 'Floors',
            'model'        => Floor::class,
            'with'         => ['factory', 'building'],
            'fields' => [
                'factory_id'   => ['type' => 'relation', 'label' => 'Factory / Unit', 'required' => true, 'relation' => Factory::class, 'display' => 'name'],
                'building_id'  => ['type' => 'relation', 'label' => 'Building', 'required' => true, 'relation' => Building::class, 'display' => 'name'],
                'name'         => ['type' => 'text', 'label' => 'Floor Name', 'required' => true],
                'native_name'  => ['type' => 'text', 'label' => 'Native Name', 'placeholder' => 'বাংলা নাম'],
                'floor_number' => ['type' => 'number', 'label' => 'Floor Number', 'min' => 0],
                'description'  => ['type' => 'textarea', 'label' => 'Description'],
                'is_active'    => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'native_name', 'building_id', 'floor_number', 'factory_id', 'is_active'],
            'search'  => ['name', 'native_name', 'code'],
        ],

        'hrm-lines' => [
            'label'        => 'Line / Section',
            'label_plural' => 'Lines / Sections',
            'model'        => Line::class,
            'with'         => ['factory', 'floor'],
            'fields' => [
                'factory_id'  => ['type' => 'relation', 'label' => 'Factory / Unit', 'required' => true, 'relation' => Factory::class, 'display' => 'name'],
                'floor_id'    => ['type' => 'relation', 'label' => 'Floor', 'required' => true, 'relation' => Floor::class, 'display' => 'name'],
                'name'        => ['type' => 'text', 'label' => 'Line Name', 'required' => true],
                'native_name' => ['type' => 'text', 'label' => 'Native Name', 'placeholder' => 'বাংলা নাম'],
                'description' => ['type' => 'textarea', 'label' => 'Description'],
                'is_active'   => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'native_name', 'floor_id', 'factory_id', 'is_active'],
            'search'  => ['name', 'native_name', 'code'],
        ],

        'hrm-shifts' => [
            'label'        => 'Shift',
            'label_plural' => 'Shifts',
            'model'        => Shift::class,
            'with'         => ['factory'],
            'fields' => [
                'factory_id'     => ['type' => 'relation', 'label' => 'Factory / Unit', 'required' => true, 'relation' => Factory::class, 'display' => 'name'],
                'name'           => ['type' => 'text', 'label' => 'Shift Name', 'required' => true],
                'start_time'     => ['type' => 'time', 'label' => 'Start Time', 'required' => true],
                'end_time'       => ['type' => 'time', 'label' => 'End Time', 'required' => true],
                'break_minutes'  => ['type' => 'number', 'label' => 'Break (minutes)', 'min' => 0, 'default' => 0],
                'is_night'       => ['type' => 'boolean', 'label' => 'Night Shift', 'default' => false],
                'description'    => ['type' => 'textarea', 'label' => 'Description'],
                'is_active'      => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'factory_id', 'start_time', 'end_time', 'is_night', 'is_active'],
            'search'  => ['name', 'code'],
        ],

        'hrm-holidays' => [
            'label'        => 'Holiday',
            'label_plural' => 'Holidays',
            'model'        => Holiday::class,
            'with'         => ['factory'],
            'fields' => [
                'factory_id'  => ['type' => 'relation', 'label' => 'Factory / Unit', 'required' => true, 'relation' => Factory::class, 'display' => 'name'],
                'name'        => ['type' => 'text', 'label' => 'Holiday Name', 'required' => true],
                'date'        => ['type' => 'date', 'label' => 'Date', 'required' => true],
                'is_optional' => ['type' => 'boolean', 'label' => 'Optional Holiday', 'default' => false],
                'description' => ['type' => 'textarea', 'label' => 'Description'],
                'is_active'   => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns'       => ['code', 'name', 'date', 'factory_id', 'is_optional', 'is_active'],
            'search'        => ['name', 'code'],
            'default_order' => ['date' => 'desc'],
        ],

        'hrm-worker-categories' => [
            'label'        => 'Worker Category',
            'label_plural' => 'Worker Categories',
            'model'        => WorkerCategory::class,
            'fields' => [
                'name'          => ['type' => 'text', 'label' => 'Category Name', 'required' => true, 'placeholder' => 'e.g. Operator, Helper, QC'],
                'native_name'   => ['type' => 'text', 'label' => 'Native Name', 'placeholder' => 'বাংলা নাম'],
                'minimum_wage'  => ['type' => 'number', 'label' => 'Minimum Wage (Daily)', 'min' => 0, 'step' => 0.01, 'placeholder' => 'BD wage board minimum'],
                'description'   => ['type' => 'textarea', 'label' => 'Description'],
                'is_active'     => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'native_name', 'minimum_wage', 'is_active'],
            'search'  => ['name', 'native_name', 'code'],
        ],

        'hrm-employment-types' => [
            'label'        => 'Employment Type',
            'label_plural' => 'Employment Types',
            'model'        => EmploymentType::class,
            'fields' => [
                'name'        => ['type' => 'text', 'label' => 'Type Name', 'required' => true, 'placeholder' => 'e.g. Permanent, Contract'],
                'description' => ['type' => 'textarea', 'label' => 'Description'],
                'is_active'   => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'is_active'],
            'search'  => ['name', 'code'],
        ],

        'hrm-leave-types' => [
            'label'        => 'Leave Type',
            'label_plural' => 'Leave Types',
            'model'        => LeaveType::class,
            'fields' => [
                'name'               => ['type' => 'text', 'label' => 'Leave Name', 'required' => true, 'placeholder' => 'e.g. Casual, Sick, Maternity'],
                'native_name'        => ['type' => 'text', 'label' => 'Native Name', 'placeholder' => 'বাংলা নাম'],
                'is_paid'            => ['type' => 'boolean', 'label' => 'Paid Leave', 'default' => true],
                'max_days_per_year'  => ['type' => 'number', 'label' => 'Max Days / Year', 'min' => 0],
                'description'        => ['type' => 'textarea', 'label' => 'Description'],
                'is_active'          => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'native_name', 'is_paid', 'max_days_per_year', 'is_active'],
            'search'  => ['name', 'native_name', 'code'],
        ],

        'hrm-biometric-devices' => [
            'label'        => 'Biometric Device',
            'label_plural' => 'Biometric Devices',
            'model'        => BiometricDevice::class,
            'with'         => ['factory'],
            'fields' => [
                'factory_id'    => ['type' => 'relation', 'label' => 'Factory / Unit', 'required' => true, 'relation' => Factory::class, 'display' => 'name'],
                'name'          => ['type' => 'text', 'label' => 'Device Name', 'required' => true],
                'device_serial' => ['type' => 'text', 'label' => 'Serial Number (SN)', 'placeholder' => 'SpeedFace V5L device SN'],
                'device_model'  => ['type' => 'text', 'label' => 'Device Model', 'placeholder' => 'ZKTeco SpeedFace V5L'],
                'ip_address'    => ['type' => 'text', 'label' => 'IP Address', 'placeholder' => '192.168.1.100'],
                'adms_url'      => ['type' => 'text', 'label' => 'ZKTeco ADMS URL'],
                'location'      => ['type' => 'text', 'label' => 'Location', 'placeholder' => 'e.g. Main Gate, Line 5'],
                'description'   => ['type' => 'textarea', 'label' => 'Description'],
                'is_active'     => ['type' => 'boolean', 'label' => 'Active', 'default' => true],
            ],
            'columns' => ['code', 'name', 'factory_id', 'ip_address', 'location', 'is_active'],
            'search'  => ['name', 'code', 'device_serial', 'ip_address'],
        ],

    ],

    'relation_columns' => [
        'factory_id'  => ['relation' => 'factory', 'display' => 'name'],
        'building_id' => ['relation' => 'building', 'display' => 'name'],
        'floor_id'    => ['relation' => 'floor', 'display' => 'name'],
    ],

    /*
    | Queue names for background HRM jobs (production: QUEUE_CONNECTION=redis).
    | Run workers: php artisan queue:work redis --queue=hrm-sync,hrm-attendance,hrm-payroll,hrm-mail
    */
    'queues' => [
        'sync'       => env('HRM_QUEUE_SYNC', 'hrm-sync'),
        'attendance' => env('HRM_QUEUE_ATTENDANCE', 'hrm-attendance'),
        'payroll'    => env('HRM_QUEUE_PAYROLL', 'hrm-payroll'),
        'mail'       => env('HRM_QUEUE_MAIL', 'hrm-mail'),
    ],

    'sync' => [
        'stale_push_minutes' => (int) env('HRM_DEVICE_STALE_MINUTES', 60),
    ],

];
