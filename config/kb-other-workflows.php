<?php

require_once __DIR__ . '/kb-workflow-helper.php';

/*
| Non-HRM module workflows + HRM module-level short overviews.
| Modules without submodule KB articles use expanded overview tables here.
*/
return [

    'commercial' => [
        'overview' => kb_workflow_pair(
            [['step' => '1', 'who' => 'Buyer', 'action' => 'Public requirement form — item, quantity, techpack/artwork upload', 'result' => 'Order created — status <strong>New</strong>'],
             ['step' => '2', 'who' => 'Commercial Executive', 'action' => 'Commercial → Requirements → open order → assign to self', 'result' => 'Status → <strong>Under Review</strong>'],
             ['step' => '3', 'who' => 'Commercial Executive', 'action' => 'Download techpack, review feasibility, add internal notes', 'result' => 'Ready for costing'],
             ['step' => '4', 'who' => 'Commercial Executive', 'action' => 'Move to <strong>Commercial Quote</strong> → fill costing form (price/pc, lead time, payment terms)', 'result' => 'Internal quote draft saved'],
             ['step' => '5', 'who' => 'Commercial Manager', 'action' => 'Review costing → publish quote to buyer', 'result' => 'Status → <strong>Quoted</strong>'],
             ['step' => '6', 'who' => 'Commercial Manager', 'action' => 'Buyer confirms → mark <strong>Approved</strong>', 'result' => 'Production handoff authorised'],
             ['step' => '7', 'who' => 'Commercial Executive', 'action' => 'Update status through production lifecycle', 'result' => '<strong>In Production</strong> → <strong>Shipped</strong> → <strong>Closed</strong>'],
             ['step' => '—', 'who' => 'Commercial Manager', 'action' => 'Reject / cancel at any pre-production stage with reason', 'result' => 'Status <strong>Cancelled</strong> (terminal)']],
            ['Commercial Quote status unlocks the costing form only.', 'Quote valid-until date must be set before publishing.', 'Cannot revert from In Production without manager override.', 'Assign every order to a responsible executive.'],
            [['step' => '১', 'who' => 'Buyer', 'action' => 'Public requirement form — item, quantity, techpack/artwork upload', 'result' => 'Order তৈরি — status <strong>New</strong>'],
             ['step' => '২', 'who' => 'Commercial Executive', 'action' => 'Commercial → Requirements → order open → self-এ assign', 'result' => 'Status → <strong>Under Review</strong>'],
             ['step' => '৩', 'who' => 'Commercial Executive', 'action' => 'Techpack download, feasibility review, internal note', 'result' => 'Costing-এর জন্য ready'],
             ['step' => '৪', 'who' => 'Commercial Executive', 'action' => '<strong>Commercial Quote</strong>-এ move → costing form (price/pc, lead time, payment terms)', 'result' => 'Internal quote draft save'],
             ['step' => '৫', 'who' => 'Commercial Manager', 'action' => 'Costing review → buyer-কে quote publish', 'result' => 'Status → <strong>Quoted</strong>'],
             ['step' => '৬', 'who' => 'Commercial Manager', 'action' => 'Buyer confirm → <strong>Approved</strong> mark', 'result' => 'Production handoff authorised'],
             ['step' => '৭', 'who' => 'Commercial Executive', 'action' => 'Production lifecycle-এ status update', 'result' => '<strong>In Production</strong> → <strong>Shipped</strong> → <strong>Closed</strong>'],
             ['step' => '—', 'who' => 'Commercial Manager', 'action' => 'Pre-production stage-এ reason সহ reject/cancel', 'result' => 'Status <strong>Cancelled</strong> (terminal)']],
            ['Commercial Quote status-এ costing form unlock।', 'Publish-এর আগে quote valid-until date set করুন।', 'In Production থেকে revert manager override ছাড়া নয়।', 'প্রতিটি order responsible executive-এ assign।'],
        ),
    ],

    'masters' => [
        'overview' => kb_workflow_pair(
            [['step' => '1', 'who' => 'ERP Admin', 'action' => '<strong>Organization</strong> — Factories, departments, designations, company calendar', 'result' => 'Multi-factory structure ready'],
             ['step' => '2', 'who' => 'ERP Admin', 'action' => '<strong>Commercial</strong> — Buyers, brands, seasons, buyer classes', 'result' => 'Order intake masters ready'],
             ['step' => '3', 'who' => 'ERP Admin', 'action' => '<strong>Product</strong> — Items, colors, sizes, accessories, body parts', 'result' => 'Style/SKU catalogue ready'],
             ['step' => '4', 'who' => 'ERP Admin', 'action' => '<strong>Material &amp; Fabric</strong> — Fabric types, GSM, composition, sustainability', 'result' => 'BOM and costing references ready'],
             ['step' => '5', 'who' => 'ERP Admin', 'action' => '<strong>Order &amp; Shipment</strong> — Order types, shipment modes, shipment statuses', 'result' => 'Commercial order lifecycle labels ready'],
             ['step' => '6', 'who' => 'ERP Admin', 'action' => '<strong>Production &amp; Status</strong> — Order/yarn/woven/trims/garment production statuses', 'result' => 'Production tracking labels ready'],
             ['step' => '7', 'who' => 'ERP Admin', 'action' => '<strong>Finance &amp; Supplier</strong> — Banks, supplier types, suppliers', 'result' => 'Payment and sourcing masters ready'],
             ['step' => '8', 'who' => 'Module users', 'action' => 'Commercial, HRM, TMS consume masters read-only in dropdowns', 'result' => 'Consistent data across modules']],
            ['Setup once before module go-live — order matters (factory before department).', 'Deactivate instead of delete when linked to transactions.', 'Factory GPS fields feed HRM mobile check-in geofence.', 'Review inactive masters quarterly.'],
            [['step' => '১', 'who' => 'ERP Admin', 'action' => '<strong>Organization</strong> — Factory, department, designation, company calendar', 'result' => 'Multi-factory structure ready'],
             ['step' => '২', 'who' => 'ERP Admin', 'action' => '<strong>Commercial</strong> — Buyer, brand, season, buyer class', 'result' => 'Order intake master ready'],
             ['step' => '৩', 'who' => 'ERP Admin', 'action' => '<strong>Product</strong> — Item, color, size, accessory, body part', 'result' => 'Style/SKU catalogue ready'],
             ['step' => '৪', 'who' => 'ERP Admin', 'action' => '<strong>Material &amp; Fabric</strong> — Fabric type, GSM, composition, sustainability', 'result' => 'BOM ও costing reference ready'],
             ['step' => '৫', 'who' => 'ERP Admin', 'action' => '<strong>Order &amp; Shipment</strong> — Order type, shipment mode, shipment status', 'result' => 'Commercial order lifecycle label ready'],
             ['step' => '৬', 'who' => 'ERP Admin', 'action' => '<strong>Production &amp; Status</strong> — Order/yarn/woven/trims/garment production status', 'result' => 'Production tracking label ready'],
             ['step' => '৭', 'who' => 'ERP Admin', 'action' => '<strong>Finance &amp; Supplier</strong> — Bank, supplier type, supplier', 'result' => 'Payment ও sourcing master ready'],
             ['step' => '৮', 'who' => 'Module user', 'action' => 'Commercial, HRM, TMS dropdown-এ master read-only consume', 'result' => 'Module জুড়ে consistent data']],
            ['Module go-live-এর আগে একবার setup — order matter (factory আগে department)।', 'Transaction linked হলে delete নয়, deactivate।', 'Factory GPS field HRM mobile check-in geofence feed।', 'Inactive master quarterly review।'],
        ),
    ],

    'admin-system' => [
        'overview' => kb_workflow_pair(
            [['step' => '1', 'who' => 'System Admin', 'action' => '<strong>Users</strong> → New user — name, email, password', 'result' => 'Account created (inactive until role assigned)'],
             ['step' => '2', 'who' => 'System Admin', 'action' => 'Set <strong>factory scope</strong> — all factories or specific unit(s)', 'result' => 'User sees only scoped data'],
             ['step' => '3', 'who' => 'System Admin', 'action' => 'Assign <strong>role</strong> (HR Officer, Transport Manager, etc.)', 'result' => 'Permissions applied; user can login'],
             ['step' => '4', 'who' => 'System Admin', 'action' => '<strong>Roles</strong> → create/edit role → tick permission bundles', 'result' => 'Reusable role template for department'],
             ['step' => '5', 'who' => 'System Admin', 'action' => '<strong>Settings</strong> → mail/SMS/WhatsApp/branding → Save', 'result' => 'App-wide config updated'],
             ['step' => '6', 'who' => 'System Admin', 'action' => 'Send test mail / SMS / WhatsApp after every settings change', 'result' => 'Notification channel verified'],
             ['step' => '7', 'who' => 'System Admin', 'action' => 'On staff separation — deactivate user same day', 'result' => 'Access revoked immediately'],
             ['step' => '8', 'who' => 'System Admin', 'action' => 'Quarterly access review — remove orphan accounts', 'result' => 'Security audit trail maintained']],
            ['Principle of least privilege — assign minimum permissions needed.', 'Never share admin credentials.', 'Factory scope is mandatory for multi-unit deployments.', 'Test notifications before payroll/leave go-live.'],
            [['step' => '১', 'who' => 'System Admin', 'action' => '<strong>Users</strong> → New user — name, email, password', 'result' => 'Account তৈরি (role assign না হলে inactive)'],
             ['step' => '২', 'who' => 'System Admin', 'action' => '<strong>Factory scope</strong> set — সব factory বা specific unit', 'result' => 'User শুধু scoped data দেখে'],
             ['step' => '৩', 'who' => 'System Admin', 'action' => '<strong>Role</strong> assign (HR Officer, Transport Manager, etc.)', 'result' => 'Permission apply; login possible'],
             ['step' => '৪', 'who' => 'System Admin', 'action' => '<strong>Roles</strong> → create/edit → permission bundle tick', 'result' => 'Department-এর জন্য reusable role template'],
             ['step' => '৫', 'who' => 'System Admin', 'action' => '<strong>Settings</strong> → mail/SMS/WhatsApp/branding → Save', 'result' => 'App-wide config update'],
             ['step' => '৬', 'who' => 'System Admin', 'action' => 'প্রতি settings change-এর পর test mail/SMS/WhatsApp', 'result' => 'Notification channel verify'],
             ['step' => '৭', 'who' => 'System Admin', 'action' => 'Staff separation-এ same day user deactivate', 'result' => 'Access immediately revoke'],
             ['step' => '৮', 'who' => 'System Admin', 'action' => 'Quarterly access review — orphan account remove', 'result' => 'Security audit trail maintain']],
            ['Least privilege — minimum permission assign।', 'Admin credential share নয়।', 'Multi-unit deployment-এ factory scope mandatory।', 'Payroll/leave go-live-এর আগে notification test।'],
        ),
    ],

    'hrm-masters' => [
        'overview' => kb_workflow_pair(
            [['step' => '1', 'who' => 'HR Admin', 'action' => '<strong>Organization</strong> — Buildings → Floors → Lines (per factory)', 'result' => 'Physical layout for employee assignment'],
             ['step' => '2', 'who' => 'HR Admin', 'action' => '<strong>Work Schedule</strong> — Shifts (start/end/break) + Holidays (annual calendar)', 'result' => 'Attendance and roster rules ready'],
             ['step' => '3', 'who' => 'HR Admin', 'action' => '<strong>Employee Setup</strong> — Worker categories (min wage), employment types, leave types', 'result' => 'Enrollment and leave policy inputs ready'],
             ['step' => '4', 'who' => 'IT / HR Admin', 'action' => '<strong>Biometric</strong> — Register devices (IP, model, factory link)', 'result' => 'ADMS sync can pull punches'],
             ['step' => '5', 'who' => 'HR Admin', 'action' => 'Verify: employee can be assigned line + shift without error', 'result' => 'Go-live checklist passed'],
             ['step' => '6', 'who' => 'HR Admin', 'action' => 'Annual: update holiday calendar before fiscal year', 'result' => 'Attendance processor uses correct off-days'],
             ['step' => '7', 'who' => 'HR Admin', 'action' => 'Deactivate obsolete shifts/lines — never delete if roster-linked', 'result' => 'Historical data preserved']],
            ['Complete steps 1–4 before first employee enrollment.', 'Shift times must match attendance policy grace windows.', 'Biometric device factory must match employee factory.', 'Holiday changes require attendance reprocess for affected dates.'],
            [['step' => '১', 'who' => 'HR Admin', 'action' => '<strong>Organization</strong> — Building → Floor → Line (factory-wise)', 'result' => 'Employee assign-এর জন্য physical layout'],
             ['step' => '২', 'who' => 'HR Admin', 'action' => '<strong>Work Schedule</strong> — Shift (start/end/break) + Holiday (annual calendar)', 'result' => 'Attendance ও roster rule ready'],
             ['step' => '৩', 'who' => 'HR Admin', 'action' => '<strong>Employee Setup</strong> — Worker category (min wage), employment type, leave type', 'result' => 'Enrollment ও leave policy input ready'],
             ['step' => '৪', 'who' => 'IT / HR Admin', 'action' => '<strong>Biometric</strong> — Device register (IP, model, factory link)', 'result' => 'ADMS sync punch pull করতে পারে'],
             ['step' => '৫', 'who' => 'HR Admin', 'action' => 'Verify: employee line + shift assign error ছাড়া হয়', 'result' => 'Go-live checklist pass'],
             ['step' => '৬', 'who' => 'HR Admin', 'action' => 'বার্ষিক: fiscal year-এর আগে holiday calendar update', 'result' => 'Attendance processor সঠিক off-day use'],
             ['step' => '৭', 'who' => 'HR Admin', 'action' => 'Obsolete shift/line deactivate — roster-linked হলে delete নয়', 'result' => 'Historical data preserve']],
            ['প্রথম employee enrollment-এর আগে step ১–৪ complete।', 'Shift time attendance policy grace window-এর সাথে match।', 'Biometric device factory employee factory-এর সাথে match।', 'Holiday change-এ affected date-এর attendance reprocess।'],
        ),
    ],

    'hrm-employee' => [
        'overview' => [
            'workflow_en' => '<h3>HRM Employee — Short Workflow</h3><ol><li><strong>Enroll</strong> — Create employee, NID verify, assign line/shift, portal account.</li><li><strong>Active life</strong> — Profile updates, letters, discipline, promotion/demotion requests.</li><li><strong>Exit</strong> — Separation request → clearance → approved → inactive; triggers F&amp;F in Finance.</li></ol><p>Key statuses: Active → Separation Pending → Resigned/Terminated.</p>',
            'workflow_bn' => '<h3>HRM Employee — সংক্ষিপ্ত Workflow</h3><ol><li><strong>Enroll</strong> — Employee create, NID verify, line/shift assign, portal account।</li><li><strong>Active life</strong> — Profile update, letter, discipline, promotion/demotion request।</li><li><strong>Exit</strong> — Separation → clearance → approve → inactive; Finance-এ F&amp;F trigger।</li></ol><p>মূল status: Active → Separation Pending → Resigned/Terminated।</p>',
        ],
    ],

    'hrm-recruitment' => [
        'overview' => [
            'workflow_en' => '<h3>Recruitment — Short Workflow</h3><ol><li><strong>HR</strong> publishes job posting (after manpower approval if required).</li><li><strong>Candidate</strong> applies via careers portal.</li><li><strong>HR</strong> screens → interview → offer → convert to employee.</li></ol><p>Pipeline: Applied → Screening → Interview → Selected → Offered → Hired (or Rejected/Withdrawn).</p>',
            'workflow_bn' => '<h3>Recruitment — সংক্ষিপ্ত Workflow</h3><ol><li><strong>HR</strong> job posting publish (প্রয়োজনে manpower approval পর)।</li><li><strong>Candidate</strong> careers portal দিয়ে apply।</li><li><strong>HR</strong> screen → interview → offer → employee convert।</li></ol><p>Pipeline: Applied → Screening → Interview → Selected → Offered → Hired (বা Rejected/Withdrawn)।</p>',
        ],
    ],

    'hrm-leave' => [
        'overview' => [
            'workflow_en' => '<h3>Leave — Short Workflow</h3><ol><li><strong>Setup</strong> — Policies, rules, opening balances, allocation run.</li><li><strong>Employee</strong> applies via portal (Pending).</li><li><strong>Supervisor/HR</strong> approves or rejects → balance deducted on approve.</li><li><strong>HR</strong> bulk entry / maternity cases as exceptions.</li></ol><p>Status: Pending → Approved / Rejected. Balance maintained per leave type.</p>',
            'workflow_bn' => '<h3>Leave — সংক্ষিপ্ত Workflow</h3><ol><li><strong>Setup</strong> — Policy, rule, opening balance, allocation run।</li><li><strong>Employee</strong> portal দিয়ে apply (Pending)।</li><li><strong>Supervisor/HR</strong> approve/reject → approve-এ balance কাটা।</li><li><strong>HR</strong> bulk entry / maternity exception।</li></ol><p>Status: Pending → Approved / Rejected। Leave type-wise balance maintain।</p>',
        ],
    ],

    'hrm-performance' => [
        'overview' => [
            'workflow_en' => '<h3>Performance — Short Workflow</h3><ol><li><strong>HR</strong> opens review cycle with score template.</li><li><strong>Supervisor</strong> rates manual criteria; system pulls attendance/discipline auto scores.</li><li><strong>HR</strong> approves reviews → runs performance bonus or annual increment.</li></ol><p>Cycle must close before bonus/increment run.</p>',
            'workflow_bn' => '<h3>Performance — সংক্ষিপ্ত Workflow</h3><ol><li><strong>HR</strong> review cycle ও score template open।</li><li><strong>Supervisor</strong> manual criteria rate; attendance/discipline auto score।</li><li><strong>HR</strong> review approve → performance bonus বা annual increment run।</li></ol><p>Bonus/increment run-এর আগে cycle close mandatory।</p>',
        ],
    ],

    'hrm-salary' => [
        'overview' => [
            'workflow_en' => '<h3>Salary / Payroll — Short Workflow</h3><ol><li><strong>Setup</strong> — Heads, grades, employee salary assignment.</li><li><strong>Prerequisite</strong> — Attendance period must be closed.</li><li><strong>Process</strong> — Month-end salary process from attendance.</li><li><strong>Close</strong> — Dual approval → payslip publish → bank/cash export.</li></ol><p>Status: Open Period → Processed → Closed (irreversible).</p>',
            'workflow_bn' => '<h3>Salary / Payroll — সংক্ষিপ্ত Workflow</h3><ol><li><strong>Setup</strong> — Head, grade, employee salary assign।</li><li><strong>Prerequisite</strong> — Attendance period close mandatory।</li><li><strong>Process</strong> — Month-end attendance থেকে salary process।</li><li><strong>Close</strong> — Dual approval → payslip publish → bank/cash export।</li></ol><p>Status: Open Period → Processed → Closed (irreversible)।</p>',
        ],
    ],

    'hrm-compliance' => [
        'overview' => [
            'workflow_en' => '<h3>Compliance — Short Workflow</h3><ol><li><strong>Registers</strong> — Export statutory attendance/wage/leave/OT registers (BD format).</li><li><strong>Festival Bonus</strong> — Calculate run after salary close; statutory minimum enforced.</li><li><strong>Gratuity</strong> — On separation (5+ years service).</li><li><strong>Monitor</strong> — Age verification, working hour limit violations.</li></ol>',
            'workflow_bn' => '<h3>Compliance — সংক্ষিপ্ত Workflow</h3><ol><li><strong>Register</strong> — Statutory attendance/wage/leave/OT register export (BD format)।</li><li><strong>Festival Bonus</strong> — Salary close-এর পর calculate run; statutory minimum enforce।</li><li><strong>Gratuity</strong> — Separation-এ (৫+ বছর service)।</li><li><strong>Monitor</strong> — Age verification, working hour violation।</li></ol>',
        ],
    ],

    'hrm-finance' => [
        'overview' => [
            'workflow_en' => '<h3>Finance — Short Workflow</h3><ol><li><strong>TDS</strong> — Tax slabs + employee ledger from payroll.</li><li><strong>PF</strong> — Monthly contribution after salary close.</li><li><strong>Loans</strong> — Employee applies → HR+Accounts approve → EMI recovery in payroll.</li><li><strong>F&amp;F</strong> — Final settlement after separation approved.</li></ol>',
            'workflow_bn' => '<h3>Finance — সংক্ষিপ্ত Workflow</h3><ol><li><strong>TDS</strong> — Tax slab + payroll থেকে employee ledger।</li><li><strong>PF</strong> — Salary close-এর পর monthly contribution।</li><li><strong>Loan</strong> — Employee apply → HR+Accounts approve → payroll-এ EMI recovery।</li><li><strong>F&amp;F</strong> — Separation approve-এর পর final settlement।</li></ol>',
        ],
    ],

    'hrm-rmg' => [
        'overview' => [
            'workflow_en' => '<h3>RMG Extras — Short Workflow</h3><ol><li><strong>Movement</strong> — Gate pass, worker transfer, OSD — employee request → HR approve → security verify at gate.</li><li><strong>Planning</strong> — Manpower plan vs attendance variance; proxy punch review.</li><li><strong>Welfare</strong> — Canteen, medical, training records.</li><li><strong>Payroll RMG</strong> — Production incentive, salary hold, cash list, buyer audit export.</li></ol><p>Gate pass / transfer: Pending → Approved / Rejected. Edit while pending only.</p>',
            'workflow_bn' => '<h3>RMG Extras — সংক্ষিপ্ত Workflow</h3><ol><li><strong>Movement</strong> — Gate pass, worker transfer, OSD — employee request → HR approve → gate-এ security verify।</li><li><strong>Planning</strong> — Manpower plan vs attendance variance; proxy punch review।</li><li><strong>Welfare</strong> — Canteen, medical, training record।</li><li><strong>Payroll RMG</strong> — Production incentive, salary hold, cash list, buyer audit export।</li></ol><p>Gate pass/transfer: Pending → Approved / Rejected। Pending-এ edit।</p>',
        ],
    ],

];
