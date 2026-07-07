<?php

return [

    /*
    | Module-level KB content (overview articles).
    | Submodule articles are generated from templates in KbArticleSeedBuilder.
    */
    'modules' => [

        'commercial' => [
            'summary_en' => 'Buyer requirement intake and commercial team review.',
            'summary_bn' => 'Buyer requirement গ্রহণ ও commercial team review।',
            'purpose_en'   => '<p>Receive garment production requirements from buyers/clients digitally, store techpack/artwork files, and let the commercial team track status from submission to quote/production.</p>',
            'purpose_bn'   => '<p>Buyer/client-এর garment production requirement digitalভাবে গ্রহণ, techpack/artwork সংরক্ষণ, এবং commercial team-এর submission থেকে quote/production পর্যন্ত status tracking।</p>',
            'audience_en'  => '<table><thead><tr><th>Role</th><th>Department</th><th>Responsibility</th></tr></thead><tbody><tr><td>Buyer / Client</td><td>External</td><td>Submit requirement via public form</td></tr><tr><td>Commercial Executive</td><td>Commercial / Marketing</td><td>Review submissions, download files, update status</td></tr><tr><td>Commercial Manager</td><td>Commercial</td><td>Approve workflow, assign follow-up</td></tr><tr><td>Merchandiser</td><td>Merchandising</td><td>Review techpack & quantity for feasibility</td></tr></tbody></table>',
            'audience_bn'  => '<table><thead><tr><th>Role</th><th>Department</th><th>দায়িত্ব</th></tr></thead><tbody><tr><td>Buyer / Client</td><td>External</td><td>Public form দিয়ে requirement submit</td></tr><tr><td>Commercial Executive</td><td>Commercial / Marketing</td><td>Submission review, file download, status update</td></tr><tr><td>Commercial Manager</td><td>Commercial</td><td>Workflow approve, follow-up assign</td></tr><tr><td>Merchandiser</td><td>Merchandising</td><td>Techpack ও quantity যাচাই</td></tr></tbody></table>',
            'usage_rules_en' => '<ul><li>Every buyer submission must get a response within 24 business hours.</li><li>Status changes must reflect actual commercial progress — do not mark &quot;Quoted&quot; without internal approval.</li><li>Files are confidential; download only for assigned commercial staff.</li><li>Delete records only with manager approval and audit note.</li></ul>',
            'usage_rules_bn' => '<ul><li>প্রতিটি buyer submission-এ ২৪ ঘণ্টার মধ্যে response দিতে হবে।</li><li>Status শুধুমাত্র বাস্তব commercial progress অনুযায়ী বদলাতে হবে — internal approval ছাড়া &quot;Quoted&quot; mark করা যাবে না।</li><li>File confidential; assigned commercial staff ছাড়া download নিষেধ।</li><li>Record delete শুধু manager approval ও audit note সহ।</li></ul>',
        ],

        'masters' => [
            'summary_en' => 'ERP master data setup for factories, buyers, and products.',
            'summary_bn' => 'Factory, buyer, product সহ ERP master data setup।',
            'purpose_en'   => '<p>Maintain shared reference data (factories, buyers, items, UOM, etc.) used across Commercial, HRM, and TMS modules.</p>',
            'purpose_bn'   => '<p>Commercial, HRM ও TMS-এ ব্যবহৃত shared reference data (factory, buyer, item, UOM ইত্যাদি) রক্ষণাবেক্ষণ।</p>',
            'audience_en'  => '<table><thead><tr><th>Role</th><th>Department</th><th>Responsibility</th></tr></thead><tbody><tr><td>System Administrator</td><td>IT / Admin</td><td>Create and maintain master modules</td></tr><tr><td>HR Setup Officer</td><td>HR / Admin</td><td>Factory &amp; organizational masters</td></tr><tr><td>Commercial Admin</td><td>Commercial</td><td>Buyer &amp; product-related masters</td></tr><tr><td>Department Users</td><td>All</td><td>View-only reference during transactions</td></tr></tbody></table>',
            'audience_bn'  => '<table><thead><tr><th>Role</th><th>Department</th><th>দায়িত্ব</th></tr></thead><tbody><tr><td>System Administrator</td><td>IT / Admin</td><td>Master module create ও maintain</td></tr><tr><td>HR Setup Officer</td><td>HR / Admin</td><td>Factory ও organizational master</td></tr><tr><td>Commercial Admin</td><td>Commercial</td><td>Buyer ও product-related master</td></tr><tr><td>Department Users</td><td>All</td><td>Transaction-এ view-only reference</td></tr></tbody></table>',
            'usage_rules_en' => '<ul><li>Do not delete masters linked to live transactions.</li><li>Deactivate instead of delete when possible.</li><li>One person per factory should own master data accuracy.</li><li>Changes to factory/unit masters require management notification.</li></ul>',
            'usage_rules_bn' => '<ul><li>Live transaction-এ linked master delete করা যাবে না।</li><li>সম্ভব হলে delete-এর বদলে deactivate করুন।</li><li>প্রতি factory-তে একজন master data accuracy-র owner থাকবে।</li><li>Factory/unit master পরিবর্তনে management-কে জানাতে হবে।</li></ul>',
        ],

        'hrm-employee' => [
            'summary_en' => 'Employee lifecycle — enrollment, separation, promotion, letters.',
            'summary_bn' => 'কর্মী lifecycle — enrollment, separation, promotion, letters।',
            'purpose_en'   => '<p>Manage the full employee record from joining to exit: profile, ID card, portal account, separation, promotion/demotion, HR letters, and discipline.</p>',
            'purpose_bn'   => '<p>Joining থেকে exit পর্যন্ত সম্পূর্ণ employee record: profile, ID card, portal account, separation, promotion/demotion, HR letters ও discipline।</p>',
            'audience_en'  => '<table><thead><tr><th>Role</th><th>Department</th><th>Responsibility</th></tr></thead><tbody><tr><td>HR Executive</td><td>HR / Admin</td><td>Enroll employees, update profiles, issue letters</td></tr><tr><td>HR Manager</td><td>HR</td><td>Approve separation, promotion, discipline actions</td></tr><tr><td>Admin Officer</td><td>Admin</td><td>ID cards, document verification</td></tr><tr><td>Line Supervisor</td><td>Production</td><td>Recommend promotion/discipline (no system entry)</td></tr></tbody></table>',
            'audience_bn'  => '<table><thead><tr><th>Role</th><th>Department</th><th>দায়িত্ব</th></tr></thead><tbody><tr><td>HR Executive</td><td>HR / Admin</td><td>Employee enroll, profile update, letter issue</td></tr><tr><td>HR Manager</td><td>HR</td><td>Separation, promotion, discipline approve</td></tr><tr><td>Admin Officer</td><td>Admin</td><td>ID card, document verification</td></tr><tr><td>Line Supervisor</td><td>Production</td><td>Promotion/discipline recommend (system entry নয়)</td></tr></tbody></table>',
            'usage_rules_en' => '<ul><li>Employee ID must be unique per factory; verify NID before enrollment.</li><li>Separation cannot proceed without clearance checklist completion.</li><li>Promotion/demotion requires approved document upload.</li><li>Disciplinary records are permanent — edit only with HR Manager approval.</li></ul>',
            'usage_rules_bn' => '<ul><li>Employee ID factory-wise unique; enrollment-এ NID verify 필수।</li><li>Clearance checklist ছাড়া separation proceed করা যাবে না।</li><li>Promotion/demotion-এ approved document upload 필수।</li><li>Disciplinary record permanent — HR Manager approval ছাড়া edit নয়।</li></ul>',
        ],

        'hrm-recruitment' => [
            'summary_en' => 'Job postings, careers portal, and candidate pipeline.',
            'summary_bn' => 'Job posting, careers portal ও candidate pipeline।',
            'purpose_en'   => '<p>Publish vacancies on the careers site, receive applications, run interview pipeline, and convert selected candidates to employees.</p>',
            'purpose_bn'   => '<p>Careers site-এ vacancy publish, application receive, interview pipeline চালানো, এবং selected candidate-কে employee-তে convert।</p>',
            'audience_en'  => '<table><thead><tr><th>Role</th><th>Department</th><th>Responsibility</th></tr></thead><tbody><tr><td>HR Recruiter</td><td>HR</td><td>Post jobs, screen applications, schedule interviews</td></tr><tr><td>HR Manager</td><td>HR</td><td>Approve offers, convert to employee</td></tr><tr><td>Department Head</td><td>Production / Admin</td><td>Interview feedback, manpower requisition</td></tr><tr><td>Candidate</td><td>External</td><td>Apply via careers portal</td></tr></tbody></table>',
            'audience_bn'  => '<table><thead><tr><th>Role</th><th>Department</th><th>দায়িত্ব</th></tr></thead><tbody><tr><td>HR Recruiter</td><td>HR</td><td>Job post, application screen, interview schedule</td></tr><tr><td>HR Manager</td><td>HR</td><td>Offer approve, employee convert</td></tr><tr><td>Department Head</td><td>Production / Admin</td><td>Interview feedback, manpower requisition</td></tr><tr><td>Candidate</td><td>External</td><td>Careers portal দিয়ে apply</td></tr></tbody></table>',
            'usage_rules_en' => '<ul><li>Only approved manpower requisitions may be published.</li><li>Application status must be updated within 7 days of interview.</li><li>Convert to employee only after medical &amp; document verification.</li><li>Close posting when vacancy is filled.</li></ul>',
            'usage_rules_bn' => '<ul><li>Approved manpower requisition ছাড়া publish করা যাবে না।</li><li>Interview-এর ৭ দিনের মধ্যে application status update করতে হবে।</li><li>Medical ও document verification-এর পর employee convert।</li><li>Vacancy fill হলে posting close করুন।</li></ul>',
        ],

        'hrm-attendance' => [
            'summary_en' => 'Biometric attendance, roster, late/OT, and period close.',
            'summary_bn' => 'Biometric attendance, roster, late/OT ও period close।',
            'purpose_en'   => '<p>Record daily attendance from biometric devices and mobile check-in, apply late/OT rules, manage shift roster, and close monthly periods for payroll.</p>',
            'purpose_bn'   => '<p>Biometric device ও mobile check-in থেকে daily attendance record, late/OT rule apply, shift roster manage, এবং payroll-এর জন্য monthly period close।</p>',
            'audience_en'  => '<table><thead><tr><th>Role</th><th>Department</th><th>Responsibility</th></tr></thead><tbody><tr><td>HR Attendance Officer</td><td>HR / Admin</td><td>Daily monitor, manual punch, half-day entry</td></tr><tr><td>HR Manager</td><td>HR</td><td>Policy setup, period close, late acceptance approve</td></tr><tr><td>IT / Admin</td><td>Admin</td><td>Device sync, gate QR setup</td></tr><tr><td>Line Supervisor</td><td>Production</td><td>Roster verification (view)</td></tr><tr><td>Employee</td><td>All workers</td><td>Portal check-in, late acceptance apply</td></tr></tbody></table>',
            'audience_bn'  => '<table><thead><tr><th>Role</th><th>Department</th><th>দায়িত্ব</th></tr></thead><tbody><tr><td>HR Attendance Officer</td><td>HR / Admin</td><td>Daily monitor, manual punch, half-day entry</td></tr><tr><td>HR Manager</td><td>HR</td><td>Policy setup, period close, late acceptance approve</td></tr><tr><td>IT / Admin</td><td>Admin</td><td>Device sync, gate QR setup</td></tr><tr><td>Line Supervisor</td><td>Production</td><td>Roster verification (view)</td></tr><tr><td>Employee</td><td>All workers</td><td>Portal check-in, late acceptance apply</td></tr></tbody></table>',
            'usage_rules_en' => '<ul><li>Run device sync every morning before 9:00 AM.</li><li>Manual punch requires HR Manager approval with reason.</li><li>After period close, no attendance edits without written authorization.</li><li>Late acceptance limited per company policy (typically 2/month).</li><li>OT must match approved roster shift.</li></ul>',
            'usage_rules_bn' => '<ul><li>প্রতিদিন সকাল ৯টার আগে device sync চালাতে হবে।</li><li>Manual punch-এ HR Manager approval ও reason 필수।</li><li>Period close-এর পর written authorization ছাড়া attendance edit নয়।</li><li>Late acceptance company policy অনুযায়ী (সাধারণত ২/মাস)।</li><li>OT approved roster shift-এর সাথে match করতে হবে।</li></ul>',
        ],

        'hrm-leave' => [
            'summary_en' => 'Leave policies, balances, applications, and approvals.',
            'summary_bn' => 'Leave policy, balance, application ও approval।',
            'purpose_en'   => '<p>Configure leave types and entitlements, maintain balances, process employee leave applications with multi-level approval, and run accrual/allocation.</p>',
            'purpose_bn'   => '<p>Leave type ও entitlement configure, balance maintain, employee leave application multi-level approval, এবং accrual/allocation run।</p>',
            'audience_en'  => '<table><thead><tr><th>Role</th><th>Department</th><th>Responsibility</th></tr></thead><tbody><tr><td>HR Leave Officer</td><td>HR</td><td>Policy setup, balance entry, transaction processing</td></tr><tr><td>HR Manager</td><td>HR</td><td>Approve leave, maternity cases</td></tr><tr><td>Line Supervisor</td><td>Production</td><td>First-level leave recommend/reject</td></tr><tr><td>Employee</td><td>All</td><td>Apply leave via employee portal</td></tr></tbody></table>',
            'audience_bn'  => '<table><thead><tr><th>Role</th><th>Department</th><th>দায়িত্ব</th></tr></thead><tbody><tr><td>HR Leave Officer</td><td>HR</td><td>Policy setup, balance entry, transaction process</td></tr><tr><td>HR Manager</td><td>HR</td><td>Leave approve, maternity case</td></tr><tr><td>Line Supervisor</td><td>Production</td><td>First-level leave recommend/reject</td></tr><tr><td>Employee</td><td>All</td><td>Employee portal দিয়ে leave apply</td></tr></tbody></table>',
            'usage_rules_en' => '<ul><li>Leave application minimum 24 hours before start (except emergency).</li><li>Opening balance must be verified at fiscal year start.</li><li>Maternity cases follow statutory rules — HR Manager only may override.</li><li>Bulk entry requires dual verification before save.</li></ul>',
            'usage_rules_bn' => '<ul><li>Leave application start-এর minimum ২৪ ঘণ্টা আগে (emergency ছাড়া)।</li><li>Fiscal year start-এ opening balance verify করতে হবে।</li><li>Maternity case statutory rule অনুযায়ী — HR Manager ছাড়া override নয়।</li><li>Bulk entry save-এর আগে dual verification।</li></ul>',
        ],

        'hrm-performance' => [
            'summary_en' => 'Performance reviews, bonus bands, and annual increments.',
            'summary_bn' => 'Performance review, bonus band ও annual increment।',
            'purpose_en'   => '<p>Run review cycles, score employees on attendance/discipline/quality criteria, and link results to performance bonus and annual increment runs.</p>',
            'purpose_bn'   => '<p>Review cycle চালানো, attendance/discipline/quality criteria-তে score, এবং performance bonus ও annual increment run-এ link।</p>',
            'audience_en'  => '<table><thead><tr><th>Role</th><th>Department</th><th>Responsibility</th></tr></thead><tbody><tr><td>HR Performance Officer</td><td>HR</td><td>Setup cycles, templates, bands</td></tr><tr><td>Line Supervisor</td><td>Production</td><td>Rate manual criteria (quality, behaviour)</td></tr><tr><td>HR Manager</td><td>HR</td><td>Approve reviews, run bonus/increment</td></tr><tr><td>Factory Manager</td><td>Management</td><td>Final sign-off on increment runs</td></tr></tbody></table>',
            'audience_bn'  => '<table><thead><tr><th>Role</th><th>Department</th><th>দায়িত্ব</th></tr></thead><tbody><tr><td>HR Performance Officer</td><td>HR</td><td>Cycle, template, band setup</td></tr><tr><td>Line Supervisor</td><td>Production</td><td>Manual criteria rate (quality, behaviour)</td></tr><tr><td>HR Manager</td><td>HR</td><td>Review approve, bonus/increment run</td></tr><tr><td>Factory Manager</td><td>Management</td><td>Increment run final sign-off</td></tr></tbody></table>',
            'usage_rules_en' => '<ul><li>Review cycle must be closed before bonus/increment run.</li><li>Supervisors cannot rate their own relatives (conflict of interest).</li><li>Auto criteria pull from attendance — verify before final approve.</li><li>Increment run is irreversible after salary close.</li></ul>',
            'usage_rules_bn' => '<ul><li>Bonus/increment run-এর আগে review cycle close করতে হবে।</li><li>Supervisor নিজের relative rate করতে পারবেন না।</li><li>Auto criteria attendance থেকে আসে — final approve-এর আগে verify করুন।</li><li>Salary close-এর পর increment run irreversible।</li></ul>',
        ],

        'hrm-salary' => [
            'summary_en' => 'Salary structure, payroll processing, and period close.',
            'summary_bn' => 'Salary structure, payroll process ও period close।',
            'purpose_en'   => '<p>Define salary heads and grades, assign structures to employees, process monthly payroll from attendance, and close periods for bank payment.</p>',
            'purpose_bn'   => '<p>Salary head ও grade define, employee-তে structure assign, attendance থেকে monthly payroll process, এবং bank payment-এর জন্য period close।</p>',
            'audience_en'  => '<table><thead><tr><th>Role</th><th>Department</th><th>Responsibility</th></tr></thead><tbody><tr><td>HR Payroll Officer</td><td>HR / Accounts</td><td>Structure setup, process payroll, upload</td></tr><tr><td>HR Manager</td><td>HR</td><td>Review payroll, approve close</td></tr><tr><td>Accounts</td><td>Finance</td><td>Bank advise, payment execution</td></tr><tr><td>Factory Manager</td><td>Management</td><td>Authorize salary close</td></tr></tbody></table>',
            'audience_bn'  => '<table><thead><tr><th>Role</th><th>Department</th><th>দায়িত্ব</th></tr></thead><tbody><tr><td>HR Payroll Officer</td><td>HR / Accounts</td><td>Structure setup, payroll process, upload</td></tr><tr><td>HR Manager</td><td>HR</td><td>Payroll review, close approve</td></tr><tr><td>Accounts</td><td>Finance</td><td>Bank advise, payment execution</td></tr><tr><td>Factory Manager</td><td>Management</td><td>Salary close authorize</td></tr></tbody></table>',
            'usage_rules_en' => '<ul><li>Attendance period must be closed before salary process.</li><li>Dual approval required for payroll close.</li><li>No structure change after process start for that month.</li><li>Payslip published only after close — not before.</li></ul>',
            'usage_rules_bn' => '<ul><li>Salary process-এর আগে attendance period close 필수।</li><li>Payroll close-এ dual approval প্রয়োজন।</li><li>Process start-এর পর সেই মাসে structure change নয়।</li><li>Close-এর পর payslip publish — আগে নয়।</li></ul>',
        ],

        'hrm-compliance' => [
            'summary_en' => 'Bangladesh labour law registers, bonus, and gratuity.',
            'summary_bn' => 'Bangladesh labour law register, bonus ও gratuity।',
            'purpose_en'   => '<p>Maintain statutory registers, calculate festival bonus and gratuity, monitor age verification and working hour limits for RMG compliance.</p>',
            'purpose_bn'   => '<p>Statutory register maintain, festival bonus ও gratuity calculate, RMG compliance-এর জন্য age verification ও working hour limit monitor।</p>',
            'audience_en'  => '<table><thead><tr><th>Role</th><th>Department</th><th>Responsibility</th></tr></thead><tbody><tr><td>Compliance Officer</td><td>HR / Compliance</td><td>Registers, bonus/gratuity runs, audits</td></tr><tr><td>HR Manager</td><td>HR</td><td>Approve bonus runs, gratuity settlement</td></tr><tr><td>Factory Manager</td><td>Management</td><td>Sign statutory reports</td></tr><tr><td>Accounts</td><td>Finance</td><td>Payment of bonus/gratuity</td></tr></tbody></table>',
            'audience_bn'  => '<table><thead><tr><th>Role</th><th>Department</th><th>দায়িত্ব</th></tr></thead><tbody><tr><td>Compliance Officer</td><td>HR / Compliance</td><td>Register, bonus/gratuity run, audit</td></tr><tr><td>HR Manager</td><td>HR</td><td>Bonus run approve, gratuity settlement</td></tr><tr><td>Factory Manager</td><td>Management</td><td>Statutory report sign</td></tr><tr><td>Accounts</td><td>Finance</td><td>Bonus/gratuity payment</td></tr></tbody></table>',
            'usage_rules_en' => '<ul><li>Registers exported monthly for factory records.</li><li>Festival bonus follows statutory minimum — no manual override below legal limit.</li><li>Gratuity only for 5+ years service with verified join date.</li><li>Working hour violations must be resolved within 48 hours.</li></ul>',
            'usage_rules_bn' => '<ul><li>Register monthly factory record-এর জন্য export করতে হবে।</li><li>Festival bonus statutory minimum অনুযায়ী — legal limit-এর নিচে override নয়।</li><li>Gratuity শুধু ৫+ বছর service ও verified join date-এ।</li><li>Working hour violation ৪৮ ঘণ্টার মধ্যে resolve করতে হবে।</li></ul>',
        ],

        'hrm-finance' => [
            'summary_en' => 'TDS, PF, loans, advances, and final settlement.',
            'summary_bn' => 'TDS, PF, loan, advance ও final settlement।',
            'purpose_en'   => '<p>Manage income tax ledger, PF contributions, employee loans/advances with EMI recovery, and full &amp; final settlement on exit.</p>',
            'purpose_bn'   => '<p>Income tax ledger, PF contribution, employee loan/advance EMI recovery, এবং exit-এ full &amp; final settlement manage।</p>',
            'audience_en'  => '<table><thead><tr><th>Role</th><th>Department</th><th>Responsibility</th></tr></thead><tbody><tr><td>HR Finance Officer</td><td>HR / Accounts</td><td>TDS, PF, loan entry</td></tr><tr><td>Accounts Manager</td><td>Finance</td><td>PF employer report, payment</td></tr><tr><td>HR Manager</td><td>HR</td><td>Approve loans, final settlement</td></tr><tr><td>Employee</td><td>All</td><td>Apply loan via portal</td></tr></tbody></table>',
            'audience_bn'  => '<table><thead><tr><th>Role</th><th>Department</th><th>দায়িত্ব</th></tr></thead><tbody><tr><td>HR Finance Officer</td><td>HR / Accounts</td><td>TDS, PF, loan entry</td></tr><tr><td>Accounts Manager</td><td>Finance</td><td>PF employer report, payment</td></tr><tr><td>HR Manager</td><td>HR</td><td>Loan approve, final settlement</td></tr><tr><td>Employee</td><td>All</td><td>Portal দিয়ে loan apply</td></tr></tbody></table>',
            'usage_rules_en' => '<ul><li>Loan approval requires HR Manager and Accounts joint sign-off.</li><li>PF contribution runs only after salary close.</li><li>Final settlement calculated only after separation approved.</li><li>Bulk festival advance disbursed once per festival cycle.</li></ul>',
            'usage_rules_bn' => '<ul><li>Loan approval-এ HR Manager ও Accounts joint sign-off।</li><li>PF contribution salary close-এর পর run।</li><li>Final settlement separation approve-এর পর calculate।</li><li>Bulk festival advance প্রতি festival cycle-এ একবার disburse।</li></ul>',
        ],

        'hrm-rmg' => [
            'summary_en' => 'RMG-specific: gate pass, transfers, manpower, buyer audit.',
            'summary_bn' => 'RMG-specific: gate pass, transfer, manpower, buyer audit।',
            'purpose_en'   => '<p>Handle garment factory extras: worker transfers, gate pass, OSD movement, manpower planning, proxy punch review, canteen/medical/training records, and buyer audit exports.</p>',
            'purpose_bn'   => '<p>Garment factory extras: worker transfer, gate pass, OSD movement, manpower planning, proxy punch review, canteen/medical/training record, buyer audit export।</p>',
            'audience_en'  => '<table><thead><tr><th>Role</th><th>Department</th><th>Responsibility</th></tr></thead><tbody><tr><td>HR RMG Officer</td><td>HR / Admin</td><td>Gate pass, transfers, OSD, canteen</td></tr><tr><td>Production Manager</td><td>Production</td><td>Manpower plan, line transfer approve</td></tr><tr><td>Security / Gate</td><td>Admin</td><td>Verify gate pass at exit</td></tr><tr><td>Compliance</td><td>HR / Compliance</td><td>Buyer audit export, training records</td></tr></tbody></table>',
            'audience_bn'  => '<table><thead><tr><th>Role</th><th>Department</th><th>দায়িত্ব</th></tr></thead><tbody><tr><td>HR RMG Officer</td><td>HR / Admin</td><td>Gate pass, transfer, OSD, canteen</td></tr><tr><td>Production Manager</td><td>Production</td><td>Manpower plan, line transfer approve</td></tr><tr><td>Security / Gate</td><td>Admin</td><td>Exit-এ gate pass verify</td></tr><tr><td>Compliance</td><td>HR / Compliance</td><td>Buyer audit export, training record</td></tr></tbody></table>',
            'usage_rules_en' => '<ul><li>Gate pass valid only for approved time window — security must check system.</li><li>Worker transfer effective from next shift unless emergency approved.</li><li>Salary hold requires HR Manager + Factory Manager approval.</li><li>Buyer audit pack generated from closed attendance/payroll periods only.</li></ul>',
            'usage_rules_bn' => '<ul><li>Gate pass শুধু approved time window-এ valid — security system check করবে।</li><li>Worker transfer emergency ছাড়া next shift থেকে effective।</li><li>Salary hold-এ HR Manager + Factory Manager approval।</li><li>Buyer audit pack শুধু closed attendance/payroll period থেকে generate।</li></ul>',
        ],

        'hrm-masters' => [
            'summary_en' => 'HRM-specific masters: shifts, lines, leave types, devices.',
            'summary_bn' => 'HRM master: shift, line, leave type, device।',
            'purpose_en'   => '<p>Configure HRM reference data: buildings, floors, lines, shifts, holidays, worker categories, leave types, and biometric devices per factory.</p>',
            'purpose_bn'   => '<p>HRM reference data configure: building, floor, line, shift, holiday, worker category, leave type, biometric device (factory-wise)।</p>',
            'audience_en'  => '<table><thead><tr><th>Role</th><th>Department</th><th>Responsibility</th></tr></thead><tbody><tr><td>HR Setup Officer</td><td>HR / Admin</td><td>Create and maintain HRM masters</td></tr><tr><td>IT Support</td><td>Admin</td><td>Biometric device registration</td></tr><tr><td>HR Manager</td><td>HR</td><td>Approve structural changes (shifts, lines)</td></tr></tbody></table>',
            'audience_bn'  => '<table><thead><tr><th>Role</th><th>Department</th><th>দায়িত্ব</th></tr></thead><tbody><tr><td>HR Setup Officer</td><td>HR / Admin</td><td>HRM master create ও maintain</td></tr><tr><td>IT Support</td><td>Admin</td><td>Biometric device registration</td></tr><tr><td>HR Manager</td><td>HR</td><td>Structural change approve (shift, line)</td></tr></tbody></table>',
            'usage_rules_en' => '<ul><li>Setup masters before enrolling employees or processing attendance.</li><li>Do not delete lines/shifts with active roster entries.</li><li>One biometric device serial per factory unit.</li><li>Holiday calendar updated before each fiscal year.</li></ul>',
            'usage_rules_bn' => '<ul><li>Employee enroll বা attendance process-এর আগে master setup করুন।</li><li>Active roster entry থাকা line/shift delete নয়।</li><li>প্রতি factory unit-এ এক biometric device serial।</li><li>প্রতি fiscal year-এর আগে holiday calendar update।</li></ul>',
        ],

        'tms' => [
            'summary_en' => 'Transport requests, trips, fuel, maintenance, and fleet reports.',
            'summary_bn' => 'Transport request, trip, fuel, maintenance ও fleet report।',
            'purpose_en'   => '<p>Manage company transport: vehicle and driver roster, employee trip requests, trip logging, fuel/maintenance costs, rental charges, and fleet analytics.</p>',
            'purpose_bn'   => '<p>Company transport manage: vehicle ও driver roster, employee trip request, trip log, fuel/maintenance cost, rental charge, fleet analytics।</p>',
            'audience_en'  => '<table><thead><tr><th>Role</th><th>Department</th><th>Responsibility</th></tr></thead><tbody><tr><td>Transport Authority</td><td>Admin / Transport</td><td>Approve requests, assign vehicles, manage fleet</td></tr><tr><td>Driver</td><td>Transport</td><td>Log trips, odometer, fuel</td></tr><tr><td>Employee</td><td>All</td><td>Submit transport request via portal</td></tr><tr><td>Accounts</td><td>Finance</td><td>Rental charge payment, maintenance posting</td></tr></tbody></table>',
            'audience_bn'  => '<table><thead><tr><th>Role</th><th>Department</th><th>দায়িত্ব</th></tr></thead><tbody><tr><td>Transport Authority</td><td>Admin / Transport</td><td>Request approve, vehicle assign, fleet manage</td></tr><tr><td>Driver</td><td>Transport</td><td>Trip log, odometer, fuel</td></tr><tr><td>Employee</td><td>All</td><td>Portal দিয়ে transport request</td></tr><tr><td>Accounts</td><td>Finance</td><td>Rental charge payment, maintenance posting</td></tr></tbody></table>',
            'usage_rules_en' => '<ul><li>Trip must be linked to approved request — no ad-hoc trips.</li><li>Daily odometer reading mandatory for all active vehicles.</li><li>Fuel issue logged same day as consumption.</li><li>Rental charges verified against trip log before payment.</li></ul>',
            'usage_rules_bn' => '<ul><li>Trip approved request-এ linked — ad-hoc trip নয়।</li><li>সক্রিয় vehicle-এর daily odometer reading mandatory।</li><li>Fuel issue consumption-এর same day log।</li><li>Payment-এর আগে rental charge trip log-এ verify।</li></ul>',
        ],

        'admin-system' => [
            'summary_en' => 'Users, roles, permissions, and application settings.',
            'summary_bn' => 'User, role, permission ও application settings।',
            'purpose_en'   => '<p>Administer portal access: create users, assign roles and permissions, configure app settings (mail, SMS, branding), and maintain system security.</p>',
            'purpose_bn'   => '<p>Portal access administer: user create, role ও permission assign, app settings (mail, SMS, branding) configure, system security maintain।</p>',
            'audience_en'  => '<table><thead><tr><th>Role</th><th>Department</th><th>Responsibility</th></tr></thead><tbody><tr><td>System Administrator</td><td>IT / Management</td><td>Users, roles, settings</td></tr><tr><td>HR Manager</td><td>HR</td><td>Request role changes for HR staff</td></tr><tr><td>Management</td><td>Management</td><td>Approve admin access grants</td></tr></tbody></table>',
            'audience_bn'  => '<table><thead><tr><th>Role</th><th>Department</th><th>দায়িত্ব</th></tr></thead><tbody><tr><td>System Administrator</td><td>IT / Management</td><td>User, role, settings</td></tr><tr><td>HR Manager</td><td>HR</td><td>HR staff-এর role change request</td></tr><tr><td>Management</td><td>Management</td><td>Admin access grant approve</td></tr></tbody></table>',
            'usage_rules_en' => '<ul><li>Administrator role limited to IT and top management only.</li><li>Review user access quarterly — remove leavers same day.</li><li>Test mail/SMS after any settings change.</li><li>Never share admin credentials — individual accounts only.</li></ul>',
            'usage_rules_bn' => '<ul><li>Administrator role শুধু IT ও top management-এ।</li><li>Quarterly user access review — leaver same day remove।</li><li>Settings change-এর পর test mail/SMS।</li><li>Admin credential share নয় — individual account only।</li></ul>',
        ],

    ],

    /*
    | Submodule screen-type hints for auto-generated usage rules.
    */
    'screen_hints' => [
        'dashboard' => [
            'en' => 'Monitor KPIs daily; do not use dashboard for data entry.',
            'bn' => 'KPI daily monitor করুন; dashboard দিয়ে data entry নয়।',
        ],
        'sync' => [
            'en' => 'Run at scheduled times; verify device online before sync.',
            'bn' => 'Schedule অনুযায়ী run করুন; sync-এর আগে device online verify।',
        ],
        'reports' => [
            'en' => 'Export for records only from closed periods.',
            'bn' => 'Record-এর জন্য export শুধু closed period থেকে।',
        ],
        'approve' => [
            'en' => 'Approve/reject within 48 hours with written reason for rejection.',
            'bn' => '৪৮ ঘণ্টার মধ্যে approve/reject; reject-এ written reason।',
        ],
        'bulk' => [
            'en' => 'Dual verification required before bulk save.',
            'bn' => 'Bulk save-এর আগে dual verification।',
        ],
        'upload' => [
            'en' => 'Validate CSV template; test with 5 rows before full upload.',
            'bn' => 'CSV template validate; full upload-এর আগে ৫ row test।',
        ],
        'close' => [
            'en' => 'Irreversible after close — verify all data before confirming.',
            'bn' => 'Close-এর পর irreversible — confirm-এর আগে সব data verify।',
        ],
        'policy' => [
            'en' => 'Policy changes apply from next period unless emergency approved.',
            'bn' => 'Policy change emergency ছাড়া next period থেকে apply।',
        ],
    ],

];
