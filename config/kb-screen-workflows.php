<?php

/*
| Screen-specific step-by-step KB workflows (section 3).
| Keys: module code → submodule key → workflow_en / workflow_bn (HTML).
*/
return [

    'hrm-attendance' => [

        'overview' => [
            'workflow_en' => '
<h3>Monthly attendance cycle (screen order)</h3>
<ol>
<li><strong>Setup (once / when changed)</strong> — Policy → Gate QR Points → Shift Roster</li>
<li><strong>Every working day</strong> — Device Sync → Punch Logs check → Daily Summary review</li>
<li><strong>When exceptions occur</strong> — Manual Punch / Half Day Entry / Late Acceptance</li>
<li><strong>Month end</strong> — Periods (process → freeze) → Reports export → hand off to Salary</li>
</ol>
<h3>Step-by-step — daily morning routine</h3>
<table><thead><tr><th>Step</th><th>Who</th><th>Do this</th><th>What happens</th></tr></thead><tbody>
<tr><td>1</td><td>IT / HR Officer</td><td>HRM → Attendance → <strong>Device Sync</strong> → Run sync for all devices before 9:00 AM</td><td>Raw punches imported from biometric devices</td></tr>
<tr><td>2</td><td>HR Attendance Officer</td><td>Open <strong>Punch Logs</strong> — filter today, check unmapped punches</td><td>Missing employee mapping flagged; fix in employee profile if needed</td></tr>
<tr><td>3</td><td>HR Attendance Officer</td><td>Open <strong>Daily Summary</strong> — review late, absent, OT flags</td><td>Processed daily log shows status per employee</td></tr>
<tr><td>4</td><td>HR Attendance Officer</td><td>Fix gaps via <strong>Manual Punch</strong> or <strong>Half Day Entry</strong>; approve <strong>Late Acceptance</strong> queue</td><td>Exceptions corrected; employee notified on approval/rejection</td></tr>
<tr><td>5</td><td>HR Manager</td><td>Review <strong>Dashboard</strong> KPIs — open periods, pending approvals</td><td>Management visibility; escalate unresolved items same day</td></tr>
</tbody></table>
<h3>Step-by-step — month end close</h3>
<table><thead><tr><th>Step</th><th>Who</th><th>Do this</th><th>What happens</th></tr></thead><tbody>
<tr><td>1</td><td>HR Attendance Officer</td><td>Confirm all daily exceptions resolved for the month</td><td>No pending manual punch / half-day drafts</td></tr>
<tr><td>2</td><td>HR Attendance Officer</td><td><strong>Periods</strong> → select month → Process attendance</td><td>Monthly totals calculated (present, late, OT, absent)</td></tr>
<tr><td>3</td><td>HR Manager</td><td>Review period summary → Freeze / Close period</td><td>Period locked — no edits without written authorization</td></tr>
<tr><td>4</td><td>HR Attendance Officer</td><td><strong>Reports</strong> → export monthly summary for records</td><td>PDF/Excel archived; Salary module can consume closed period</td></tr>
</tbody></table>
<h3>Important rules</h3>
<ul>
<li>Never close a period with open late-acceptance or unapproved manual punches.</li>
<li>OT must match published shift roster — mismatch blocks payroll.</li>
<li>After period freeze, only HR Manager with written authorization may reopen.</li>
</ul>',
            'workflow_bn' => '
<h3>মাসিক attendance cycle (screen order)</h3>
<ol>
<li><strong>Setup (একবার / পরিবর্তন হলে)</strong> — Policy → Gate QR Points → Shift Roster</li>
<li><strong>প্রতিদিন</strong> — Device Sync → Punch Logs check → Daily Summary review</li>
<li><strong>Exception হলে</strong> — Manual Punch / Half Day Entry / Late Acceptance</li>
<li><strong>মাস শেষ</strong> — Periods (process → freeze) → Reports export → Salary-তে hand off</li>
</ol>
<h3>Step-by-step — প্রতিদিন সকালের routine</h3>
<table><thead><tr><th>Step</th><th>কে</th><th>কী করবেন</th><th>ফলাফল</th></tr></thead><tbody>
<tr><td>১</td><td>IT / HR Officer</td><td>HRM → Attendance → <strong>Device Sync</strong> → সকাল ৯টার আগে সব device sync</td><td>Biometric device থেকে raw punch import</td></tr>
<tr><td>২</td><td>HR Attendance Officer</td><td><strong>Punch Logs</strong> → আজকের filter, unmapped punch check</td><td>Employee mapping missing হলে flag; profile-এ fix</td></tr>
<tr><td>৩</td><td>HR Attendance Officer</td><td><strong>Daily Summary</strong> → late, absent, OT flag review</td><td>Employee-wise processed daily log</td></tr>
<tr><td>৪</td><td>HR Attendance Officer</td><td><strong>Manual Punch</strong> / <strong>Half Day Entry</strong> দিয়ে gap fix; <strong>Late Acceptance</strong> queue approve</td><td>Exception ঠিক; employee-কে approve/reject notification</td></tr>
<tr><td>৫</td><td>HR Manager</td><td><strong>Dashboard</strong> KPI review — open period, pending approval</td><td>Management visibility; unresolved same day escalate</td></tr>
</tbody></table>
<h3>Step-by-step — মাস শেষ close</h3>
<table><thead><tr><th>Step</th><th>কে</th><th>কী করবেন</th><th>ফলাফল</th></tr></thead><tbody>
<tr><td>১</td><td>HR Attendance Officer</td><td>মাসের সব daily exception resolve confirm</td><td>Pending manual punch / half-day draft নেই</td></tr>
<tr><td>২</td><td>HR Attendance Officer</td><td><strong>Periods</strong> → month select → Process attendance</td><td>Monthly total (present, late, OT, absent) calculate</td></tr>
<tr><td>৩</td><td>HR Manager</td><td>Period summary review → Freeze / Close</td><td>Period lock — written authorization ছাড়া edit নয়</td></tr>
<tr><td>৪</td><td>HR Attendance Officer</td><td><strong>Reports</strong> → monthly summary export</td><td>PDF/Excel archive; Salary closed period use করতে পারে</td></tr>
</tbody></table>
<h3>গুরুত্বপূর্ণ নিয়ম</h3>
<ul>
<li>Open late-acceptance বা unapproved manual punch থাকলে period close নয়।</li>
<li>OT published shift roster-এর সাথে match করতে হবে — mismatch payroll block।</li>
<li>Period freeze-এর পর written authorization ছাড়া শুধু HR Manager reopen করতে পারেন।</li>
</ul>',
        ],

        'sync' => [
            'workflow_en' => '
<h3>Step-by-step workflow</h3>
<table><thead><tr><th>Step</th><th>Who</th><th>Action</th><th>Result</th></tr></thead><tbody>
<tr><td>1</td><td>IT / HR Officer</td><td>Menu: HRM → Attendance → <strong>Device Sync</strong></td><td>Device list with last sync time shown</td></tr>
<tr><td>2</td><td>IT / HR Officer</td><td>Verify device status is <strong>Online</strong>; if offline, check network/power first</td><td>Offline device skipped — no false “sync OK”</td></tr>
<tr><td>3</td><td>IT / HR Officer</td><td>Click <strong>Sync Now</strong> (or sync all) for each factory device</td><td>New raw punches queued for import</td></tr>
<tr><td>4</td><td>IT / HR Officer</td><td>Click <strong>Process Today</strong> (if available) after sync completes</td><td>Raw punches converted to daily attendance log</td></tr>
<tr><td>5</td><td>HR Attendance Officer</td><td>Open <strong>Punch Logs</strong> → confirm today’s IN/OUT count looks normal</td><td>Sync failure or unmapped punch visible for same-day fix</td></tr>
</tbody></table>
<h3>If sync fails</h3>
<ul><li>Check device IP, ADMS token, and firewall — retry after fix.</li><li>Do not enter manual punches until sync confirmed failed.</li><li>Report repeated failure to IT same day.</li></ul>',
            'workflow_bn' => '
<h3>Step-by-step workflow</h3>
<table><thead><tr><th>Step</th><th>কে</th><th>কাজ</th><th>ফলাফল</th></tr></thead><tbody>
<tr><td>১</td><td>IT / HR Officer</td><td>Menu: HRM → Attendance → <strong>Device Sync</strong></td><td>Device list ও last sync time দেখাবে</td></tr>
<tr><td>২</td><td>IT / HR Officer</td><td>Device status <strong>Online</strong> verify; offline হলে network/power check</td><td>Offline device skip — ভুল “sync OK” নয়</td></tr>
<tr><td>৩</td><td>IT / HR Officer</td><td><strong>Sync Now</strong> (বা sync all) click</td><td>নতুন raw punch import queue</td></tr>
<tr><td>৪</td><td>IT / HR Officer</td><td>Sync complete-এর পর <strong>Process Today</strong> (যদি থাকে)</td><td>Raw punch daily attendance log-এ convert</td></tr>
<tr><td>৫</td><td>HR Attendance Officer</td><td><strong>Punch Logs</strong> → আজকের IN/OUT count normal কিনা</td><td>Sync fail/unmapped punch same day fix</td></tr>
</tbody></table>
<h3>Sync fail হলে</h3>
<ul><li>Device IP, ADMS token, firewall check — fix-এর পর retry।</li><li>Sync fail confirm না হলে manual punch entry নয়।</li><li>Repeated fail same day IT-কে report।</li></ul>',
        ],

        'manual-punch' => [
            'workflow_en' => '
<h3>Step-by-step workflow</h3>
<table><thead><tr><th>Step</th><th>Who</th><th>Action</th><th>Result</th></tr></thead><tbody>
<tr><td>1</td><td>HR Attendance Officer</td><td>HRM → Attendance → <strong>Manual Punch</strong> → New entry</td><td>Manual punch form opens</td></tr>
<tr><td>2</td><td>HR Attendance Officer</td><td>Select <strong>employee</strong>, <strong>date</strong>, missing <strong>IN</strong> and/or <strong>OUT</strong> time</td><td>Times validated against shift roster</td></tr>
<tr><td>3</td><td>HR Attendance Officer</td><td>Enter <strong>reason</strong> (device failure, forgot punch, official duty) + supporting note</td><td>Audit trail saved — reason mandatory</td></tr>
<tr><td>4</td><td>HR Attendance Officer</td><td>Click <strong>Save / Submit</strong></td><td>Status = Pending approval; HR Manager notified</td></tr>
<tr><td>5</td><td>HR Manager</td><td>Open pending list → <strong>Approve</strong> or <strong>Reject</strong> with comment</td><td>Approved → daily log recalculated; Rejected → officer must correct or escalate</td></tr>
<tr><td>6</td><td>HR Attendance Officer</td><td>Check <strong>Daily Summary</strong> for that employee/date</td><td>Present/late/OT status reflects approved punch</td></tr>
</tbody></table>
<h3>Important rules</h3>
<ul><li>Use only when biometric/mobile punch genuinely missing — not for convenience.</li><li>Duplicate IN/OUT for same day blocked by system.</li><li>After period close, manual punch requires written authorization.</li></ul>',
            'workflow_bn' => '
<h3>Step-by-step workflow</h3>
<table><thead><tr><th>Step</th><th>কে</th><th>কাজ</th><th>ফলাফল</th></tr></thead><tbody>
<tr><td>১</td><td>HR Attendance Officer</td><td>HRM → Attendance → <strong>Manual Punch</strong> → New entry</td><td>Manual punch form open</td></tr>
<tr><td>২</td><td>HR Attendance Officer</td><td><strong>Employee</strong>, <strong>date</strong>, missing <strong>IN</strong> / <strong>OUT</strong> time select</td><td>Shift roster অনুযায়ী time validate</td></tr>
<tr><td>৩</td><td>HR Attendance Officer</td><td><strong>Reason</strong> (device fail, forgot punch, official duty) + note লিখুন</td><td>Audit trail save — reason mandatory</td></tr>
<tr><td>৪</td><td>HR Attendance Officer</td><td><strong>Save / Submit</strong></td><td>Status Pending; HR Manager notified</td></tr>
<tr><td>৫</td><td>HR Manager</td><td>Pending list → <strong>Approve</strong> বা <strong>Reject</strong> (comment সহ)</td><td>Approve → daily log recalc; Reject → officer correct/escalate</td></tr>
<tr><td>৬</td><td>HR Attendance Officer</td><td>ওই employee/date <strong>Daily Summary</strong> check</td><td>Present/late/OT approved punch reflect</td></tr>
</tbody></table>
<h3>গুরুত্বপূর্ণ নিয়ম</h3>
<ul><li>শুধু biometric/mobile punch সত্যিই missing হলে — convenience-তে নয়।</li><li>Same day duplicate IN/OUT system block।</li><li>Period close-এর পর manual punch-এ written authorization।</li></ul>',
        ],

        'late-acceptance' => [
            'workflow_en' => '
<h3>Step-by-step workflow</h3>
<table><thead><tr><th>Step</th><th>Who</th><th>Action</th><th>Result</th></tr></thead><tbody>
<tr><td>1</td><td>Employee</td><td>Employee Portal → <strong>Late Acceptance</strong> → select date + reason → Submit</td><td>Application status = Pending; supervisor/HR notified</td></tr>
<tr><td>2</td><td>HR Attendance Officer</td><td>Admin: HRM → Attendance → <strong>Late Acceptance</strong> → review queue</td><td>See employee, date, late minutes, reason</td></tr>
<tr><td>3</td><td>HR Attendance Officer</td><td>Verify against punch log and policy limit (e.g. 2/month)</td><td>Invalid/over-limit applications flagged for rejection</td></tr>
<tr><td>4</td><td>HR Manager</td><td><strong>Approve</strong> or <strong>Reject</strong> with written reason if rejected</td><td>Approved → late deduction waived for that day; Rejected → employee notified</td></tr>
<tr><td>5</td><td>HR Attendance Officer</td><td>Re-run or verify <strong>Daily Summary</strong> for approved dates</td><td>Attendance status updated before month-end process</td></tr>
</tbody></table>
<h3>Important rules</h3>
<ul><li>Approve only with valid reason — traffic, official duty, documented emergency.</li><li>Reject without reason is not allowed.</li><li>Cannot approve after attendance period is frozen.</li></ul>',
            'workflow_bn' => '
<h3>Step-by-step workflow</h3>
<table><thead><tr><th>Step</th><th>কে</th><th>কাজ</th><th>ফলাফল</th></tr></thead><tbody>
<tr><td>১</td><td>Employee</td><td>Employee Portal → <strong>Late Acceptance</strong> → date + reason → Submit</td><td>Status Pending; supervisor/HR notified</td></tr>
<tr><td>২</td><td>HR Attendance Officer</td><td>Admin: HRM → Attendance → <strong>Late Acceptance</strong> → queue review</td><td>Employee, date, late minutes, reason দেখা</td></tr>
<tr><td>৩</td><td>HR Attendance Officer</td><td>Punch log ও policy limit (যেমন ২/মাস) verify</td><td>Invalid/over-limit reject-এর জন্য flag</td></tr>
<tr><td>৪</td><td>HR Manager</td><td><strong>Approve</strong> বা <strong>Reject</strong> (reject-এ written reason)</td><td>Approve → late deduction waive; Reject → employee notified</td></tr>
<tr><td>৫</td><td>HR Attendance Officer</td><td>Approved date <strong>Daily Summary</strong> verify</td><td>Month-end process-এর আগে status update</td></tr>
</tbody></table>
<h3>গুরুত্বপূর্ণ নিয়ম</h3>
<ul><li>Valid reason ছাড়া approve নয় — traffic, official duty, documented emergency।</li><li>Reason ছাড়া reject allowed নয়।</li><li>Period freeze-এর পর approve করা যাবে না।</li></ul>',
        ],

        'periods' => [
            'workflow_en' => '
<h3>Step-by-step workflow</h3>
<table><thead><tr><th>Step</th><th>Who</th><th>Action</th><th>Result</th></tr></thead><tbody>
<tr><td>1</td><td>HR Attendance Officer</td><td>Before month-end: confirm all daily exceptions closed (manual punch, late acceptance, half-day)</td><td>No pending items block process</td></tr>
<tr><td>2</td><td>HR Attendance Officer</td><td>HRM → Attendance → <strong>Periods</strong> → select year/month</td><td>Period detail with employee list shown</td></tr>
<tr><td>3</td><td>HR Attendance Officer</td><td>Click <strong>Process</strong> / Run attendance process for period</td><td>Monthly totals calculated per employee</td></tr>
<tr><td>4</td><td>HR Manager</td><td>Review totals — spot-check high OT, zero present, anomalies</td><td>Errors corrected via daily screens before freeze</td></tr>
<tr><td>5</td><td>HR Manager</td><td>Click <strong>Freeze / Close</strong> period</td><td>Period locked — edits blocked; Salary can pull data</td></tr>
<tr><td>6</td><td>HR Attendance Officer</td><td>Export from <strong>Reports</strong> for factory records</td><td>Archive PDF/Excel for audit</td></tr>
</tbody></table>
<h3>Important rules</h3>
<ul><li>Close is irreversible without HR Manager written authorization.</li><li>Salary process must not start until period shows Closed.</li><li>Re-open period only for documented payroll-blocking errors.</li></ul>',
            'workflow_bn' => '
<h3>Step-by-step workflow</h3>
<table><thead><tr><th>Step</th><th>কে</th><th>কাজ</th><th>ফলাফল</th></tr></thead><tbody>
<tr><td>১</td><td>HR Attendance Officer</td><td>Month-end-এর আগে সব daily exception close confirm (manual punch, late acceptance, half-day)</td><td>Pending item process block করবে না</td></tr>
<tr><td>২</td><td>HR Attendance Officer</td><td>HRM → Attendance → <strong>Periods</strong> → year/month select</td><td>Employee list সহ period detail</td></tr>
<tr><td>৩</td><td>HR Attendance Officer</td><td><strong>Process</strong> / attendance process run</td><td>Employee-wise monthly total calculate</td></tr>
<tr><td>৪</td><td>HR Manager</td><td>Total review — high OT, zero present, anomaly check</td><td>Freeze-এর আগে daily screen-এ error fix</td></tr>
<tr><td>৫</td><td>HR Manager</td><td><strong>Freeze / Close</strong> click</td><td>Period lock — edit block; Salary data pull</td></tr>
<tr><td>৬</td><td>HR Attendance Officer</td><td><strong>Reports</strong> থেকে export</td><td>Audit-এর জন্য PDF/Excel archive</td></tr>
</tbody></table>
<h3>গুরুত্বপূর্ণ নিয়ম</h3>
<ul><li>Close HR Manager written authorization ছাড়া irreversible।</li><li>Period Closed না দেখালে Salary process start নয়।</li><li>Re-open শুধু payroll-blocking documented error-এ।</li></ul>',
        ],

        'roster' => [
            'workflow_en' => '
<h3>Step-by-step workflow</h3>
<table><thead><tr><th>Step</th><th>Who</th><th>Action</th><th>Result</th></tr></thead><tbody>
<tr><td>1</td><td>HR Attendance Officer</td><td>HRM → Attendance → <strong>Shift Roster</strong> → create or open weekly roster</td><td>Roster grid by employee/line shown</td></tr>
<tr><td>2</td><td>HR Attendance Officer</td><td>Assign shift per employee/day OR upload Excel/CSV template</td><td>Draft roster saved — not yet visible to employees</td></tr>
<tr><td>3</td><td>HR Manager</td><td>Review OT/night shift coverage vs production plan</td><td>Conflicts corrected before publish</td></tr>
<tr><td>4</td><td>HR Attendance Officer</td><td><strong>Publish</strong> roster for the week</td><td>Employees see roster on portal; OT validation uses this data</td></tr>
<tr><td>5</td><td>Line Supervisor</td><td>Verify team roster (view only) — report mismatches to HR</td><td>Production line aligned with attendance policy</td></tr>
</tbody></table>
<h3>Important rules</h3>
<ul><li>Publish at least 3 days before week start when possible.</li><li>Do not change published roster without manager approval — affects OT pay.</li><li>Bulk upload: test 5 rows before full file.</li></ul>',
            'workflow_bn' => '
<h3>Step-by-step workflow</h3>
<table><thead><tr><th>Step</th><th>কে</th><th>কাজ</th><th>ফলাফল</th></tr></thead><tbody>
<tr><td>১</td><td>HR Attendance Officer</td><td>HRM → Attendance → <strong>Shift Roster</strong> → weekly roster create/open</td><td>Employee/line grid দেখাবে</td></tr>
<tr><td>২</td><td>HR Attendance Officer</td><td>Employee/day-wise shift assign অথবা Excel/CSV upload</td><td>Draft roster save — employee-দের কাছে এখনো visible নয়</td></tr>
<tr><td>৩</td><td>HR Manager</td><td>Production plan অনুযায়ী OT/night shift coverage review</td><td>Publish-এর আগে conflict fix</td></tr>
<tr><td>৪</td><td>HR Attendance Officer</td><td>Week-এর roster <strong>Publish</strong></td><td>Employee portal-এ roster; OT validation এই data use</td></tr>
<tr><td>৫</td><td>Line Supervisor</td><td>Team roster verify (view) — mismatch HR-কে report</td><td>Production line attendance policy-র সাথে align</td></tr>
</tbody></table>
<h3>গুরুত্বপূর্ণ নিয়ম</h3>
<ul><li>সম্ভব হলে week start-এর ৩ দিন আগে publish।</li><li>Manager approval ছাড়া published roster change নয় — OT pay affect।</li><li>Bulk upload: full file-এর আগে ৫ row test।</li></ul>',
        ],

    ],

];
