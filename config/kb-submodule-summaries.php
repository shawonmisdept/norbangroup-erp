<?php

/*
| Submodule short summaries for Knowledge Base articles (summary_en / summary_bn).
| Plain text — shown at top of each sub-module article.
| Format: who does what + key status/outcome in one or two sentences.
*/
return [

    'hrm-employee' => [
        'dashboard' => [
            'summary_en' => 'HR views headcount, joinings, pending exits and promotions — monitor only, no data entry.',
            'summary_bn' => 'HR headcount, joining, pending exit ও promotion দেখে — শুধু monitor, data entry নয়।',
        ],
        'employees' => [
            'summary_en' => 'Enroll new workers, edit profiles, issue ID cards and enable employee portal accounts.',
            'summary_bn' => 'নতুন কর্মী enroll, profile edit, ID card ও employee portal account enable।',
        ],
        'separations' => [
            'summary_en' => 'Employee/HR initiates exit → clearance checklist → HR approves → status Resigned/Terminated.',
            'summary_bn' => 'Employee/HR exit initiate → clearance checklist → HR approve → Resigned/Terminated status।',
        ],
        'promotions' => [
            'summary_en' => 'Initiate promotion or demotion with document → HR approves → designation/salary updated.',
            'summary_bn' => 'Promotion/demotion document সহ initiate → HR approve → designation/salary update।',
        ],
        'letters' => [
            'summary_en' => 'Issue appointment, confirmation, warning, transfer and experience certificates from templates.',
            'summary_bn' => 'Template থেকে appointment, confirmation, warning, transfer ও experience certificate issue।',
        ],
        'discipline' => [
            'summary_en' => 'Record verbal/written warnings, suspensions and misconduct — permanent audit trail.',
            'summary_bn' => 'Verbal/written warning, suspension, misconduct record — permanent audit trail।',
        ],
    ],

    'hrm-recruitment' => [
        'dashboard' => [
            'summary_en' => 'Pipeline KPIs: open postings, interviews scheduled, conversion to employee.',
            'summary_bn' => 'Pipeline KPI: open posting, scheduled interview, employee conversion।',
        ],
        'postings' => [
            'summary_en' => 'Create and publish vacancies on careers site — Draft → Published → Closed when filled.',
            'summary_bn' => 'Careers site-এ vacancy create ও publish — Draft → Published → fill হলে Closed।',
        ],
        'applications' => [
            'summary_en' => 'Screen candidates, schedule interviews, update pipeline status, convert hired to employee.',
            'summary_bn' => 'Candidate screen, interview schedule, pipeline status update, hired-কে employee convert।',
        ],
    ],

    'hrm-attendance' => [
        'dashboard' => [
            'summary_en' => 'Today\'s attendance KPIs, pending late acceptance and open periods — morning review screen.',
            'summary_bn' => 'আজকের attendance KPI, pending late acceptance, open period — সকালে review screen।',
        ],
        'sync' => [
            'summary_en' => 'IT/HR syncs biometric devices every morning → raw punches imported for processing.',
            'summary_bn' => 'IT/HR প্রতিদিন সকালে biometric device sync → raw punch import।',
        ],
        'punches' => [
            'summary_en' => 'View raw IN/OUT punches — fix unmapped employees before daily summary process.',
            'summary_bn' => 'Raw IN/OUT punch দেখা — daily summary-এর আগে unmapped employee fix।',
        ],
        'daily' => [
            'summary_en' => 'Processed daily log per employee: present, late, absent, OT flags after punch processing.',
            'summary_bn' => 'Employee-wise processed daily log: present, late, absent, OT flag।',
        ],
        'periods' => [
            'summary_en' => 'Month-end: process attendance → review totals → freeze period for payroll.',
            'summary_bn' => 'মাস শেষ: attendance process → total review → payroll-এর জন্য period freeze।',
        ],
        'policy' => [
            'summary_en' => 'Configure late grace, consecutive late rules and deduction basis per factory.',
            'summary_bn' => 'Factory-wise late grace, consecutive late rule ও deduction basis configure।',
        ],
        'late-acceptance' => [
            'summary_en' => 'Employee applies for late forgiveness → HR approves/rejects → late deduction waived if approved.',
            'summary_bn' => 'Employee late forgiveness apply → HR approve/reject → approve হলে late deduction waive।',
        ],
        'half-day-entry' => [
            'summary_en' => 'HR enters first/second half-day manually when biometric does not reflect half-day leave.',
            'summary_bn' => 'Biometric half-day reflect না করলে HR manually first/second half-day entry।',
        ],
        'manual-punch' => [
            'summary_en' => 'HR adds missing IN/OUT with reason → Manager approves → daily log recalculated.',
            'summary_bn' => 'HR missing IN/OUT reason সহ add → Manager approve → daily log recalc।',
        ],
        'gate-points' => [
            'summary_en' => 'Print QR codes for mobile gate check-in geofence points per factory.',
            'summary_bn' => 'Factory-wise mobile gate check-in geofence QR code print।',
        ],
        'reports' => [
            'summary_en' => 'Export monthly summary, line breakdown and employee calendar from closed periods.',
            'summary_bn' => 'Closed period থেকে monthly summary, line breakdown, employee calendar export।',
        ],
        'roster' => [
            'summary_en' => 'Assign weekly shifts per employee → publish → employees see roster; OT validation uses this.',
            'summary_bn' => 'Employee-wise weekly shift assign → publish → employee roster দেখে; OT validation এ data use।',
        ],
    ],

    'hrm-leave' => [
        'dashboard' => [
            'summary_en' => 'Pending leave approvals, employees on leave today and allocation pipeline overview.',
            'summary_bn' => 'Pending leave approval, আজ leave-এ employee, allocation pipeline overview।',
        ],
        'policies' => [
            'summary_en' => 'Factory-wise leave entitlement days per leave type — basis for balance and accrual.',
            'summary_bn' => 'Factory-wise leave type অনুযায়ী entitlement day — balance ও accrual-এর ভিত্তি।',
        ],
        'rules' => [
            'summary_en' => 'Eligibility rules by worker category, tenure and gender for each leave type.',
            'summary_bn' => 'Worker category, tenure, gender অনুযায়ী leave type eligibility rule।',
        ],
        'maternity-rules' => [
            'summary_en' => 'Statutory maternity duration and pay rules — HR Manager override only.',
            'summary_bn' => 'Statutory maternity duration ও pay rule — শুধু HR Manager override।',
        ],
        'opening-balances' => [
            'summary_en' => 'Set year-start or join-date leave balance per employee before transactions.',
            'summary_bn' => 'Transaction-এর আগে employee-wise year-start বা join-date leave balance set।',
        ],
        'maternity-transactions' => [
            'summary_en' => 'Track maternity benefit cases linked to leave balance and payroll.',
            'summary_bn' => 'Maternity benefit case track — leave balance ও payroll-এর সাথে link।',
        ],
        'transactions' => [
            'summary_en' => 'Employee applies leave → Supervisor/HR approves or rejects → balance updated.',
            'summary_bn' => 'Employee leave apply → Supervisor/HR approve/reject → balance update।',
        ],
        'allocation' => [
            'summary_en' => 'Run monthly/yearly accrual to add earned leave days to all eligible employees.',
            'summary_bn' => 'Monthly/yearly accrual run — eligible employee-দের earned leave day add।',
        ],
        'bulk-entry' => [
            'summary_en' => 'CSV/Excel bulk leave entry — dual verification before save.',
            'summary_bn' => 'CSV/Excel bulk leave entry — save-এর আগে dual verification।',
        ],
    ],

    'hrm-performance' => [
        'dashboard' => [
            'summary_en' => 'Open cycles, pending ratings and bonus/increment run status at a glance.',
            'summary_bn' => 'Open cycle, pending rating, bonus/increment run status এক নজরে।',
        ],
        'cycles' => [
            'summary_en' => 'Open probation, mid-year or annual review batch for a factory period.',
            'summary_bn' => 'Factory period-এর জন্য probation, mid-year বা annual review batch open।',
        ],
        'templates' => [
            'summary_en' => 'Define score criteria weights — auto (attendance) and manual (quality, behaviour).',
            'summary_bn' => 'Score criteria weight define — auto (attendance) ও manual (quality, behaviour)।',
        ],
        'reviews' => [
            'summary_en' => 'Supervisor rates, HR approves — cycle must close before bonus/increment.',
            'summary_bn' => 'Supervisor rate, HR approve — bonus/increment-এর আগে cycle close mandatory।',
        ],
        'bonus-bands' => [
            'summary_en' => 'Map review score ranges to performance bonus percentage per factory.',
            'summary_bn' => 'Review score range থেকে performance bonus % map (factory-wise)।',
        ],
        'bonus-runs' => [
            'summary_en' => 'Calculate mid-year performance bonus from approved reviews → payroll handoff.',
            'summary_bn' => 'Approved review থেকে mid-year performance bonus calculate → payroll handoff।',
        ],
        'increment-bands' => [
            'summary_en' => 'Map annual review scores to salary increment percentage bands.',
            'summary_bn' => 'Annual review score থেকে salary increment % band map।',
        ],
        'increment-runs' => [
            'summary_en' => 'Apply annual salary increments from closed review cycle — irreversible after salary close.',
            'summary_bn' => 'Closed review cycle থেকে annual increment apply — salary close-এর পর irreversible।',
        ],
    ],

    'hrm-salary' => [
        'dashboard' => [
            'summary_en' => 'Salary structure coverage and current payroll period status per factory.',
            'summary_bn' => 'Salary structure coverage ও current payroll period status (factory-wise)।',
        ],
        'heads' => [
            'summary_en' => 'Define salary components: Basic, HRA, allowances and deduction heads.',
            'summary_bn' => 'Salary component define: Basic, HRA, allowance ও deduction head।',
        ],
        'grades' => [
            'summary_en' => 'Salary grades (G1, Staff, Worker) used to group standard pay structures.',
            'summary_bn' => 'Salary grade (G1, Staff, Worker) — standard pay structure group।',
        ],
        'grade-details' => [
            'summary_en' => 'Amount per salary head for each grade — template for employee assignment.',
            'summary_bn' => 'প্রতি grade-এ salary head-wise amount — employee assign-এর template।',
        ],
        'employee-salary' => [
            'summary_en' => 'Assign grade or custom structure to each employee before payroll process.',
            'summary_bn' => 'Payroll process-এর আগে employee-wise grade বা custom structure assign।',
        ],
        'banks' => [
            'summary_en' => 'Maintain banks used for employee salary transfer and bank advise export.',
            'summary_bn' => 'Employee salary transfer ও bank advise export-এর bank maintain।',
        ],
        'upload' => [
            'summary_en' => 'Bulk upload employee salary structures via CSV — validate before save.',
            'summary_bn' => 'CSV দিয়ে bulk employee salary structure upload — save-এর আগে validate।',
        ],
        'process' => [
            'summary_en' => 'Month-end calculation from closed attendance — generates draft payroll per employee.',
            'summary_bn' => 'Closed attendance থেকে month-end calculate — employee-wise draft payroll।',
        ],
        'close' => [
            'summary_en' => 'Dual approval freezes period, publishes payslips, exports bank/cash payment files.',
            'summary_bn' => 'Dual approval-এ period freeze, payslip publish, bank/cash payment file export।',
        ],
        'bank-ledger' => [
            'summary_en' => 'Bank-wise payment register for closed payroll periods — accounts reconciliation.',
            'summary_bn' => 'Closed payroll period-এর bank-wise payment register — accounts reconciliation।',
        ],
        'increment-rules' => [
            'summary_en' => 'Auto increment rules by grade and tenure — runs separately from performance increment.',
            'summary_bn' => 'Grade ও tenure অনুযায়ী auto increment rule — performance increment থেকে আলাদা।',
        ],
        'increment-bulk' => [
            'summary_en' => 'Apply increment to filtered employee set in one batch with preview.',
            'summary_bn' => 'Filter করা employee set-এ এক batch increment apply (preview সহ)।',
        ],
        'increment-upload' => [
            'summary_en' => 'CSV upload of increment amounts — dual verify before applying to structures.',
            'summary_bn' => 'Increment amount CSV upload — structure-এ apply-এর আগে dual verify।',
        ],
    ],

    'hrm-compliance' => [
        'dashboard' => [
            'summary_en' => 'Overview of festival bonus runs, gratuity settlements and open compliance tasks.',
            'summary_bn' => 'Festival bonus run, gratuity settlement ও open compliance task overview।',
        ],
        'registers' => [
            'summary_en' => 'Export Bangladesh-format statutory registers: attendance, wage, leave, OT.',
            'summary_bn' => 'Bangladesh-format statutory register export: attendance, wage, leave, OT।',
        ],
        'bonus' => [
            'summary_en' => 'Festival bonus calculation run — statutory minimum enforced, no override below legal limit.',
            'summary_bn' => 'Festival bonus calculate run — statutory minimum enforce, legal limit-এর নিচে override নয়।',
        ],
        'gratuity' => [
            'summary_en' => 'Gratuity settlement on separation for 5+ years verified service.',
            'summary_bn' => '৫+ বছর verified service-এ separation-এ gratuity settlement।',
        ],
        'age-verification' => [
            'summary_en' => 'Child labour prevention report — flag under-age or missing DOB employees.',
            'summary_bn' => 'Child labour prevention report — under-age বা missing DOB employee flag।',
        ],
        'working-hours' => [
            'summary_en' => 'Monitor daily/weekly hour limit violations — resolve within 48 hours.',
            'summary_bn' => 'Daily/weekly hour limit violation monitor — ৪৮ ঘণ্টার মধ্যে resolve।',
        ],
    ],

    'hrm-finance' => [
        'dashboard' => [
            'summary_en' => 'Snapshot of open loans, TDS ledger, PF contributions and pending F&F cases.',
            'summary_bn' => 'Open loan, TDS ledger, PF contribution, pending F&F case snapshot।',
        ],
        'tax' => [
            'summary_en' => 'Assessment year tax slabs and per-employee TDS ledger from payroll.',
            'summary_bn' => 'Assessment year tax slab ও payroll থেকে employee-wise TDS ledger।',
        ],
        'pf' => [
            'summary_en' => 'PF accounts and monthly employee/employer contributions after salary close.',
            'summary_bn' => 'PF account ও salary close-এর পর monthly employee/employer contribution।',
        ],
        'pf-report' => [
            'summary_en' => 'Monthly employer PF contribution report and CSV export for accounts.',
            'summary_bn' => 'Monthly employer PF contribution report ও accounts-এর জন্য CSV export।',
        ],
        'loans' => [
            'summary_en' => 'Employee applies loan/advance → HR+Accounts approve → EMI recovered in payroll.',
            'summary_bn' => 'Employee loan/advance apply → HR+Accounts approve → payroll-এ EMI recovery।',
        ],
        'advance-bulk' => [
            'summary_en' => 'Disburse festival advance to many employees in one batch before Eid.',
            'summary_bn' => 'Eid-এর আগে এক batch-এ অনেক employee-কে festival advance disburse।',
        ],
        'final-settlement' => [
            'summary_en' => 'Full & final on exit: gratuity, PF, loans, leave encashment after separation approved.',
            'summary_bn' => 'Exit-এ F&F: gratuity, PF, loan, leave encashment — separation approve-এর পর।',
        ],
    ],

    'hrm-rmg' => [
        'dashboard' => [
            'summary_en' => 'RMG KPI hub: pending gate passes, transfers, proxy punch flags and quick actions.',
            'summary_bn' => 'RMG KPI hub: pending gate pass, transfer, proxy punch flag ও quick action।',
        ],
        'worker-transfer' => [
            'summary_en' => 'Cross-line/unit transfer request → HR approves → effective next shift (emergency excepted).',
            'summary_bn' => 'Cross-line/unit transfer request → HR approve → next shift থেকে effective (emergency ছাড়া)।',
        ],
        'osd-movement' => [
            'summary_en' => 'Official duty, buyer visit and training movement log with approval.',
            'summary_bn' => 'Official duty, buyer visit, training movement log (approval সহ)।',
        ],
        'gate-pass' => [
            'summary_en' => 'Employee requests gate-out pass → HR approves → security verifies at exit within time window.',
            'summary_bn' => 'Employee gate-out pass request → HR approve → exit-এ security time window verify।',
        ],
        'manpower-planning' => [
            'summary_en' => 'Daily line headcount plan vs actual attendance variance — production alignment.',
            'summary_bn' => 'Daily line headcount plan vs actual attendance variance — production alignment।',
        ],
        'proxy-punch' => [
            'summary_en' => 'Review suspicious biometric punches — mark reviewed or dismissed with audit note.',
            'summary_bn' => 'Suspicious biometric punch review — reviewed বা dismissed (audit note সহ)।',
        ],
        'canteen' => [
            'summary_en' => 'Monthly meal count and canteen deduction entries for payroll recovery.',
            'summary_bn' => 'Monthly meal count ও canteen deduction entry — payroll recovery-এর জন্য।',
        ],
        'medical' => [
            'summary_en' => 'Factory clinic visit log per employee for welfare and compliance records.',
            'summary_bn' => 'Employee-wise factory clinic visit log — welfare ও compliance record।',
        ],
        'training' => [
            'summary_en' => 'Safety, fire drill and buyer compliance training attendance log.',
            'summary_bn' => 'Safety, fire drill, buyer compliance training attendance log।',
        ],
        'sub-contract' => [
            'summary_en' => 'Agency/sub-contract worker register by line for headcount compliance.',
            'summary_bn' => 'Line-wise agency/sub-contract worker register — headcount compliance।',
        ],
        'production-incentive' => [
            'summary_en' => 'Line output incentive draft → approve → payroll deduction/addition as configured.',
            'summary_bn' => 'Line output incentive draft → approve → configured অনুযায়ী payroll-এ apply।',
        ],
        'salary-hold' => [
            'summary_en' => 'Block payroll for employees under investigation — HR Manager + Factory Manager approval.',
            'summary_bn' => 'Investigation-এর employee-এর payroll block — HR Manager + Factory Manager approval।',
        ],
        'cash-list' => [
            'summary_en' => 'Export net-pay cash workers by line for closed payroll disbursement.',
            'summary_bn' => 'Closed payroll disbursement-এর জন্য line-wise cash worker net-pay export।',
        ],
        'buyer-audit-export' => [
            'summary_en' => 'Generate attendance and wage register pack from closed periods for buyer audits.',
            'summary_bn' => 'Buyer audit-এর জন্য closed period থেকে attendance ও wage register pack generate।',
        ],
        'buyer-holiday' => [
            'summary_en' => 'Buyer-specific holiday calendar per factory — affects roster and attendance.',
            'summary_bn' => 'Factory-wise buyer-specific holiday calendar — roster ও attendance affect।',
        ],
    ],

    'tms' => [
        'dashboard' => [
            'summary_en' => 'Pending transport requests, active trips, OT and rental payment summary — daily monitor.',
            'summary_bn' => 'Pending transport request, active trip, OT ও rental payment summary — daily monitor।',
        ],
        'settings' => [
            'summary_en' => 'Transport policy: office end time, OT basis, rates and system configuration.',
            'summary_bn' => 'Transport policy: office end time, OT basis, rate ও system configuration।',
        ],
        'destinations' => [
            'summary_en' => 'Standard destination master list — employees pick from portal request form.',
            'summary_bn' => 'Standard destination master — employee portal request form-এ pick।',
        ],
        'vehicles' => [
            'summary_en' => 'Fleet register with asset details, paper expiry dates, renewal history, allocated user and primary driver. Papers Status report tracks fitness/tax/insurance. Expired papers warn on trip approve but do not block.',
            'summary_bn' => 'Asset detail, paper expiry, renewal history, allocated user ও primary driver সহ fleet register। Papers Status report fitness/tax/insurance track করে। Expired paper trip approve-এ warning দেয়, block করে না।',
        ],
        'rental_vendors' => [
            'summary_en' => 'Third-party rental vendor contracts, contacts and linked rental vehicles.',
            'summary_bn' => 'Third-party rental vendor contract, contact ও linked rental vehicle।',
        ],
        'drivers' => [
            'summary_en' => 'Company driver roster linked to employees — licence and OT eligibility.',
            'summary_bn' => 'Employee-linked company driver roster — licence ও OT eligibility।',
        ],
        'rental_drivers' => [
            'summary_en' => 'Rental vendor drivers with separate portal login at /rental/login.',
            'summary_bn' => 'Rental vendor driver — আলাদা portal login /rental/login-এ।',
        ],
        'requests' => [
            'summary_en' => 'Admin approves/rejects/merges pending requests; reassign or cancel before trip start. Status: Pending → Approved/Rejected/Cancelled.',
            'summary_bn' => 'Admin pending request approve/reject/merge; trip start-এর আগে reassign/cancel। Status: Pending → Approved/Rejected/Cancelled।',
        ],
        'trips' => [
            'summary_en' => 'Trip log: Not Started → driver Start (In Progress) → End with KM (Completed). Admin can Abort in-progress trips.',
            'summary_bn' => 'Trip log: Not Started → driver Start (In Progress) → KM দিয়ে End (Completed)। In-progress trip admin Abort করতে পারে।',
        ],
        'odometer' => [
            'summary_en' => 'Daily morning/evening KM readings per vehicle — mandatory for fleet tracking.',
            'summary_bn' => 'Vehicle-wise daily morning/evening KM reading — fleet tracking-এ mandatory।',
        ],
        'fuel' => [
            'summary_en' => 'Manual fuel issue and consumption log — link to completed trip; same-day entry rule.',
            'summary_bn' => 'Manual fuel issue ও consumption log — completed trip link; same-day entry rule।',
        ],
        'maintenance' => [
            'summary_en' => 'Vehicle service bills, parts and workshop register — routine, repair or accident.',
            'summary_bn' => 'Vehicle service bill, parts, workshop register — routine, repair বা accident।',
        ],
        'maintenance_posting' => [
            'summary_en' => 'Queue of closed maintenance bills pending finance posting and payment.',
            'summary_bn' => 'Finance posting ও payment-এর জন্য pending closed maintenance bill queue।',
        ],
        'maintenance_parts' => [
            'summary_en' => 'Reusable parts and services catalog for faster maintenance bill entry.',
            'summary_bn' => 'দ্রুত maintenance bill entry-এর reusable parts ও services catalog।',
        ],
        'rental_charges' => [
            'summary_en' => 'Rental vehicle KM charges from trips — verify against trip log before Mark Paid.',
            'summary_bn' => 'Trip থেকে rental vehicle KM charge — Mark Paid-এর আগে trip log verify।',
        ],
        'reports' => [
            'summary_en' => 'Fleet analytics: trip cost, fuel consumption, odometer trends and driver OT summary.',
            'summary_bn' => 'Fleet analytics: trip cost, fuel consumption, odometer trend, driver OT summary।',
        ],
        'device_api' => [
            'summary_en' => 'GPS/telematics API setup — vehicle location history from external device feed.',
            'summary_bn' => 'GPS/telematics API setup — external device feed থেকে vehicle location history।',
        ],
    ],

];
