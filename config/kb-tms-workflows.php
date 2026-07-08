<?php

require_once __DIR__ . '/kb-workflow-helper.php';

/*
| TMS submodule step-by-step KB workflows.
*/
return [

    'tms' => [

        'overview' => [
            'workflow_en' => '
<h3>TMS — Short Workflow (3 roles)</h3>
<h4>1. Employee</h4>
<ul>
<li><strong>Request:</strong> Employee Portal → Transport — submit pickup, destination, time, passengers, purpose. Status: <strong>Pending</strong>.</li>
<li><strong>Edit:</strong> Pending requests can be edited.</li>
<li><strong>Cancel:</strong> Employee can cancel while <strong>Pending</strong> or <strong>Approved</strong> (before trip starts).</li>
<li><strong>Track:</strong> After admin approval, view assigned driver &amp; vehicle on request detail.</li>
</ul>
<h4>2. Admin (Transport Authority)</h4>
<ul>
<li><strong>Review:</strong> TMS → Requests — check pending queue.</li>
<li><strong>Approve:</strong> Select vehicle + driver (company or rental) → Approve. Request → <strong>Approved</strong>; system creates Trip Log → <strong>Not Started</strong>.</li>
<li><strong>Merge:</strong> Select multiple pending requests on same route → Merge &amp; Approve into one trip (multiple passengers).</li>
<li><strong>Reject:</strong> Reject with reason → Request <strong>Rejected</strong> (terminal).</li>
<li><strong>Cancel:</strong> Cancel approved request before trip start → Request <strong>Cancelled</strong>; trip removed if last passenger.</li>
<li><strong>Reassign:</strong> Change driver/vehicle while trip is <strong>Not Started</strong>.</li>
<li><strong>Abort:</strong> Force-close an <strong>In Progress</strong> trip (emergency).</li>
</ul>
<h4>3. Driver</h4>
<ul>
<li><strong>Company driver:</strong> Employee Portal → Transport → My Trips (<code>/employee/transport/trips</code>).</li>
<li><strong>Rental driver:</strong> Separate Rental Driver Portal (<code>/rental/login</code>) — not the employee portal.</li>
<li><strong>Start:</strong> On notification, start trip with KM (own vehicle). Trip → <strong>In Progress</strong>; requests → <strong>In Progress</strong>.</li>
<li><strong>End:</strong> Enter end KM at destination. Trip → <strong>Completed</strong>; requests → <strong>Completed</strong>.</li>
<li><strong>Rental vehicle:</strong> No trip-level KM — use Daily KM Log (odometer) separately.</li>
</ul>
<h3>Status flowchart (Request + Trip)</h3>
<p>Two parallel status layers exist:</p>
<table><thead><tr><th>Layer</th><th>Happy path</th><th>Terminal / side paths</th></tr></thead><tbody>
<tr><td><strong>Transport Request</strong></td><td>Pending → Approved → In Progress → Completed</td><td>Rejected · Cancelled</td></tr>
<tr><td><strong>Trip Log</strong></td><td>Not Started → In Progress → Completed</td><td>Aborted (admin, in-progress only)</td></tr>
</tbody></table>
<p><strong>Key gap:</strong> After admin approve, request is <em>Approved</em> but trip is <em>Not Started</em> until the driver presses Start.</p>
<h3>Post-trip</h3>
<ul>
<li><strong>OT (automatic):</strong> On trip end, system calculates driver overtime via <code>DriverPayCalculator</code>. If pay &gt; 0, creates pending OT payment; admin notified.</li>
<li><strong>Fuel (manual):</strong> Admin enters fuel issue in TMS → Fuel, optionally linked to completed trip.</li>
<li><strong>Payments:</strong> Admin marks OT paid (Trips screen); rental charges marked paid separately.</li>
</ul>
<h3>Notifications</h3>
<table><thead><tr><th>Event</th><th>Notified</th></tr></thead><tbody>
<tr><td>Employee submits request</td><td>Admin (tms.requests.approve)</td></tr>
<tr><td>Admin approves</td><td>Employee + assigned driver</td></tr>
<tr><td>Admin rejects</td><td>Employee</td></tr>
<tr><td>Trip started / completed</td><td>Employee + Admin</td></tr>
<tr><td>OT due</td><td>Admin (tms.overtime.manage)</td></tr>
</tbody></table>',
            'workflow_bn' => '
<h3>TMS — সংক্ষিপ্ত Workflow (৩ ধাপ)</h3>
<h4>১. Employee (কর্মী)</h4>
<ul>
<li><strong>রিকোয়েস্ট:</strong> Employee Portal → Transport — pickup, destination, time, passengers, purpose দিয়ে submit। স্ট্যাটাস: <strong>Pending</strong>।</li>
<li><strong>Edit:</strong> Pending রিকোয়েস্ট edit করা যায়।</li>
<li><strong>Cancel:</strong> <strong>Pending</strong> বা <strong>Approved</strong> (trip শুরু হওয়ার আগে) employee cancel করতে পারে।</li>
<li><strong>ট্র্যাকিং:</strong> Admin approve করলে request detail-এ assigned driver ও vehicle দেখা যায়।</li>
</ul>
<h4>২. Admin (Transport Authority)</h4>
<ul>
<li><strong>রিভিউ:</strong> TMS → Requests — pending queue check।</li>
<li><strong>Approve:</strong> Vehicle + driver (company/rental) select → Approve। Request → <strong>Approved</strong>; Trip Log তৈরি → <strong>Not Started</strong>।</li>
<li><strong>Merge:</strong> একই রুটের একাধিক pending request select → Merge &amp; Approve — এক trip-এ একাধিক passenger।</li>
<li><strong>Reject:</strong> Reason সহ reject → Request <strong>Rejected</strong> (terminal)।</li>
<li><strong>Cancel:</strong> Trip start-এর আগে approved request cancel → <strong>Cancelled</strong>; শেষ passenger হলে trip delete।</li>
<li><strong>Reassign:</strong> Trip <strong>Not Started</strong> থাকলে driver/vehicle পরিবর্তন।</li>
<li><strong>Abort:</strong> <strong>In Progress</strong> trip admin force-close (emergency)।</li>
</ul>
<h4>৩. Driver (চালক)</h4>
<ul>
<li><strong>Company driver:</strong> Employee Portal → Transport → My Trips (<code>/employee/transport/trips</code>)।</li>
<li><strong>Rental driver:</strong> আলাদা Rental Driver Portal (<code>/rental/login</code>) — employee portal নয়।</li>
<li><strong>Start:</strong> Notification পেয়ে trip start (own vehicle-এ KM সহ)। Trip → <strong>In Progress</strong>; requests → <strong>In Progress</strong>।</li>
<li><strong>End:</strong> গন্তব্যে end KM দিয়ে trip শেষ। Trip → <strong>Completed</strong>; requests → <strong>Completed</strong>।</li>
<li><strong>Rental vehicle:</strong> Trip-level KM নয় — Daily KM Log (odometer) আলাদা screen-এ।</li>
</ul>
<h3>স্ট্যাটাস ফ্লোচার্ট (Request + Trip)</h3>
<p>দুই ধরনের status layer:</p>
<table><thead><tr><th>Layer</th><th>মূল পথ</th><th>Terminal / side path</th></tr></thead><tbody>
<tr><td><strong>Transport Request</strong></td><td>Pending → Approved → In Progress → Completed</td><td>Rejected · Cancelled</td></tr>
<tr><td><strong>Trip Log</strong></td><td>Not Started → In Progress → Completed</td><td>Aborted (admin, in-progress)</td></tr>
</tbody></table>
<p><strong>মাঝের অবস্থা:</strong> Admin approve-এর পর request <em>Approved</em>, কিন্তু driver Start না চাপা পর্যন্ত trip <em>Not Started</em>।</p>
<h3>Post-Trip</h3>
<ul>
<li><strong>OT (অটো):</strong> Trip end-এ <code>DriverPayCalculator</code> দিয়ে driver OT হিসাব। Pay &gt; 0 হলে pending OT payment; admin notified।</li>
<li><strong>Fuel (ম্যানুয়াল):</strong> Admin TMS → Fuel-এ entry, completed trip link করা যায়।</li>
<li><strong>Payment:</strong> Admin OT Mark Paid (Trips); rental charge আলাদা screen-এ paid mark।</li>
</ul>
<h3>Notifications</h3>
<table><thead><tr><th>ঘটনা</th><th>কাকে জানায়</th></tr></thead><tbody>
<tr><td>Employee request submit</td><td>Admin (tms.requests.approve)</td></tr>
<tr><td>Admin approve</td><td>Employee + assigned driver</td></tr>
<tr><td>Admin reject</td><td>Employee</td></tr>
<tr><td>Trip start / complete</td><td>Employee + Admin</td></tr>
<tr><td>OT due</td><td>Admin (tms.overtime.manage)</td></tr>
</tbody></table>',
        ],

        'requests' => [
            'workflow_en' => '
<h3>Step-by-step — Transport Requests</h3>
<table><thead><tr><th>Step</th><th>Who</th><th>Action</th><th>Result</th></tr></thead><tbody>
<tr><td>1</td><td>Employee</td><td>Portal → Transport → New request</td><td>Status Pending; admin notified</td></tr>
<tr><td>2</td><td>Transport Officer</td><td>TMS → Requests → review pending</td><td>Validate route, time, passenger count</td></tr>
<tr><td>3a</td><td>Transport Officer</td><td><strong>Approve</strong> — pick vehicle + driver</td><td>Request Approved; Trip Log created (Not Started)</td></tr>
<tr><td>3b</td><td>Transport Officer</td><td><strong>Merge</strong> — select 2+ pending → Merge &amp; Approve</td><td>One trip, multiple passengers</td></tr>
<tr><td>3c</td><td>Transport Officer</td><td><strong>Reject</strong> with written reason</td><td>Request Rejected; employee notified</td></tr>
<tr><td>4</td><td>Transport Officer</td><td><strong>Reassign</strong> driver/vehicle (before start)</td><td>Trip updated; new driver notified</td></tr>
<tr><td>5</td><td>Employee or Admin</td><td><strong>Cancel</strong> (Pending or Approved, trip not started)</td><td>Request Cancelled</td></tr>
</tbody></table>
<h3>Important rules</h3>
<ul><li>Reject requires reason — no silent rejection.</li><li>Cannot cancel after trip In Progress — use Abort on trip screen.</li><li>Merge only for compatible pending requests (same factory, similar route/time).</li></ul>',
            'workflow_bn' => '
<h3>Step-by-step — Transport Requests</h3>
<table><thead><tr><th>Step</th><th>কে</th><th>কাজ</th><th>ফলাফল</th></tr></thead><tbody>
<tr><td>১</td><td>Employee</td><td>Portal → Transport → New request</td><td>Status Pending; admin notified</td></tr>
<tr><td>২</td><td>Transport Officer</td><td>TMS → Requests → pending review</td><td>Route, time, passenger validate</td></tr>
<tr><td>৩a</td><td>Transport Officer</td><td><strong>Approve</strong> — vehicle + driver select</td><td>Request Approved; Trip Log (Not Started)</td></tr>
<tr><td>৩b</td><td>Transport Officer</td><td><strong>Merge</strong> — ২+ pending → Merge &amp; Approve</td><td>এক trip, একাধিক passenger</td></tr>
<tr><td>৩c</td><td>Transport Officer</td><td><strong>Reject</strong> (written reason)</td><td>Request Rejected; employee notified</td></tr>
<tr><td>৪</td><td>Transport Officer</td><td><strong>Reassign</strong> (start-এর আগে)</td><td>Trip update; নতুন driver notified</td></tr>
<tr><td>৫</td><td>Employee/Admin</td><td><strong>Cancel</strong> (Pending/Approved, trip not started)</td><td>Request Cancelled</td></tr>
</tbody></table>
<h3>গুরুত্বপূর্ণ নিয়ম</h3>
<ul><li>Reject-এ reason mandatory।</li><li>Trip In Progress হলে cancel নয় — Trip screen-এ Abort।</li><li>Merge শুধু compatible pending request-এ (same factory, similar route/time)।</li></ul>',
        ],

        'trips' => [
            'workflow_en' => '
<h3>Step-by-step — Trip Log</h3>
<table><thead><tr><th>Step</th><th>Who</th><th>Action</th><th>Result</th></tr></thead><tbody>
<tr><td>1</td><td>Driver</td><td>Receive assignment notification → open My Trips (company) or Rental Portal</td><td>Trip shows Not Started</td></tr>
<tr><td>2</td><td>Driver</td><td><strong>Start Trip</strong> — enter start KM (own vehicle)</td><td>Trip In Progress; requests In Progress</td></tr>
<tr><td>3</td><td>Driver</td><td><strong>End Trip</strong> — enter end KM at destination</td><td>Trip Completed; requests Completed; OT auto-calculated</td></tr>
<tr><td>4</td><td>Transport Officer</td><td>Review OT payment → Mark Paid if applicable</td><td>Driver OT settled</td></tr>
<tr><td>—</td><td>Transport Officer</td><td><strong>Abort</strong> (emergency, in-progress only)</td><td>Trip force-closed; requests updated</td></tr>
</tbody></table>
<h3>Driver portal note</h3>
<ul><li>Company drivers: Employee Portal → Transport → Trips</li><li>Rental drivers: <code>/rental/login</code> (separate credentials)</li><li>Rental vehicles: KM on Daily KM screen, not trip start/end</li></ul>',
            'workflow_bn' => '
<h3>Step-by-step — Trip Log</h3>
<table><thead><tr><th>Step</th><th>কে</th><th>কাজ</th><th>ফলাফল</th></tr></thead><tbody>
<tr><td>১</td><td>Driver</td><td>Assignment notification → My Trips (company) বা Rental Portal</td><td>Trip Not Started দেখাবে</td></tr>
<tr><td>২</td><td>Driver</td><td><strong>Start Trip</strong> — start KM (own vehicle)</td><td>Trip In Progress; requests In Progress</td></tr>
<tr><td>৩</td><td>Driver</td><td><strong>End Trip</strong> — end KM</td><td>Trip Completed; requests Completed; OT auto</td></tr>
<tr><td>৪</td><td>Transport Officer</td><td>OT review → Mark Paid</td><td>Driver OT settled</td></tr>
<tr><td>—</td><td>Transport Officer</td><td><strong>Abort</strong> (emergency, in-progress)</td><td>Trip force-close; requests update</td></tr>
</tbody></table>
<h3>Driver portal note</h3>
<ul><li>Company driver: Employee Portal → Transport → Trips</li><li>Rental driver: <code>/rental/login</code> (আলাদা credential)</li><li>Rental vehicle: KM Daily KM screen-এ, trip start/end-এ নয়</li></ul>',
        ],

        'dashboard' => kb_workflow_pair(
            [['step' => '1', 'who' => 'Transport Officer', 'action' => 'Transport (TMS) → <strong>Dashboard</strong> — morning review', 'result' => 'Pending requests, active trips, OT/rental due'],
             ['step' => '2', 'who' => 'Transport Officer', 'action' => 'Click pending request / trip drill-down links', 'result' => 'Same-day action on source screen'],
             ['step' => '3', 'who' => 'Transport Manager', 'action' => 'Review payment summary (OT + rental pending)', 'result' => 'Finance follow-up list']],
            ['Dashboard is monitor-only — no data entry.', 'Review daily before 10:00 AM.', 'Escalate overdue trips same day.'],
            [['step' => '১', 'who' => 'Transport Officer', 'action' => 'Transport (TMS) → <strong>Dashboard</strong> — সকালে review', 'result' => 'Pending request, active trip, OT/rental due'],
             ['step' => '২', 'who' => 'Transport Officer', 'action' => 'Pending request / trip drill-down link click', 'result' => 'Source screen-এ same-day action'],
             ['step' => '৩', 'who' => 'Transport Manager', 'action' => 'Payment summary (OT + rental pending) review', 'result' => 'Finance follow-up list']],
            ['Dashboard monitor-only — data entry নয়।', 'প্রতিদিন সকাল ১০টার আগে review।', 'Overdue trip same day escalate।'],
        ),

        'settings' => kb_workflow_pair(
            [['step' => '1', 'who' => 'Transport Manager', 'action' => 'TMS → <strong>Settings</strong> → review current policy', 'result' => 'Office end time, OT basis, rates shown'],
             ['step' => '2', 'who' => 'Transport Manager', 'action' => 'Update OT basis, night bill rate, pickup grace → Save', 'result' => 'New policy saved'],
             ['step' => '3', 'who' => 'Transport Officer', 'action' => 'Communicate changes to drivers and admin staff', 'result' => 'Consistent OT calculation from effective date']],
            ['Policy change needs Transport Manager approval.', 'Test OT calculation on sample trip after change.', 'Document reason for mid-period override.'],
            [['step' => '১', 'who' => 'Transport Manager', 'action' => 'TMS → <strong>Settings</strong> → current policy review', 'result' => 'Office end time, OT basis, rate'],
             ['step' => '২', 'who' => 'Transport Manager', 'action' => 'OT basis, night bill rate, pickup grace update → Save', 'result' => 'নতুন policy save'],
             ['step' => '৩', 'who' => 'Transport Officer', 'action' => 'Driver ও admin staff-কে change communicate', 'result' => 'Effective date থেকে consistent OT']],
            ['Policy change-এ Transport Manager approval।', 'Change-এর পর sample trip-এ OT test।', 'Mid-period override-এ reason document।'],
        ),

        'destinations' => kb_workflow_pair(
            [['step' => '1', 'who' => 'Transport Officer', 'action' => 'TMS → <strong>Destinations</strong> → list review', 'result' => 'Standard pickup/destination master'],
             ['step' => '2', 'who' => 'Transport Officer', 'action' => 'Add destination name, factory scope, active flag → Save', 'result' => 'Available in employee transport request form'],
             ['step' => '3', 'who' => 'Transport Officer', 'action' => 'Deactivate obsolete destinations instead of delete', 'result' => 'Historical trip data preserved']],
            ['Setup destinations before employee portal go-live.', 'Deactivate — do not delete if linked to past requests.', 'Use consistent naming for merge compatibility.'],
            [['step' => '১', 'who' => 'Transport Officer', 'action' => 'TMS → <strong>Destinations</strong> → list review', 'result' => 'Standard pickup/destination master'],
             ['step' => '২', 'who' => 'Transport Officer', 'action' => 'Destination name, factory scope, active flag add → Save', 'result' => 'Employee transport request form-এ available'],
             ['step' => '৩', 'who' => 'Transport Officer', 'action' => 'Obsolete destination deactivate (delete নয়)', 'result' => 'Historical trip data preserve']],
            ['Employee portal go-live-এর আগে destination setup।', 'Past request linked থাকলে delete নয়, deactivate।', 'Merge compatibility-তে consistent naming।'],
        ),

        'vehicles' => kb_workflow_pair(
            [['step' => '1', 'who' => 'Transport Officer', 'action' => 'TMS → <strong>Vehicles</strong> → New vehicle', 'result' => 'Vehicle register form (basic + purchase + papers)'],
             ['step' => '2', 'who' => 'Transport Officer', 'action' => 'Enter reg, category, model year, CC, fuel, purchase info, paper expiry dates', 'result' => 'Vehicle saved with current papers'],
             ['step' => '3', 'who' => 'Transport Officer', 'action' => 'Assign allocated user + primary driver', 'result' => 'Executive car mapping complete'],
             ['step' => '4', 'who' => 'Transport Officer', 'action' => 'On renewal → vehicle profile → <strong>Record Paper Renewal</strong>', 'result' => 'Renewal history logged; current expiry updated'],
             ['step' => '5', 'who' => 'Transport Manager', 'action' => 'TMS → <strong>Papers Status</strong> report — review yellow/red cells', 'result' => 'Compliance dashboard like fleet spreadsheet'],
             ['step' => '6', 'who' => 'Transport Officer', 'action' => 'Approve trip on vehicle with expired paper', 'result' => '⚠ Warning shown; trip still approvable (not blocked)']],
            ['Expired papers show warning only — do not block trip assign.', 'Route permit can be N/A (blank).', 'Maintenance status still blocks assignment.', 'Renewal history keeps audit trail with optional document scan.'],
            [['step' => '১', 'who' => 'Transport Officer', 'action' => 'TMS → <strong>Vehicles</strong> → New vehicle', 'result' => 'Vehicle form (basic + purchase + papers)'],
             ['step' => '২', 'who' => 'Transport Officer', 'action' => 'Reg, category, model year, CC, fuel, purchase info, paper expiry enter', 'result' => 'Current papers সহ save'],
             ['step' => '৩', 'who' => 'Transport Officer', 'action' => 'Allocated user + primary driver assign', 'result' => 'Executive car mapping complete'],
             ['step' => '৪', 'who' => 'Transport Officer', 'action' => 'Renewal-এ → vehicle profile → <strong>Record Paper Renewal</strong>', 'result' => 'Renewal history log; current expiry update'],
             ['step' => '৫', 'who' => 'Transport Manager', 'action' => 'TMS → <strong>Papers Status</strong> report — yellow/red cell review', 'result' => 'Fleet spreadsheet-এর মতো compliance dashboard'],
             ['step' => '৬', 'who' => 'Transport Officer', 'action' => 'Expired paper vehicle-এ trip approve', 'result' => '⚠ Warning দেখাবে; trip approve হবে (block নয়)']],
            ['Expired paper শুধু warning — trip assign block নয়।', 'Route permit N/A (blank) হতে পারে।', 'Maintenance status এখনও assign block।', 'Renewal history optional document scan সহ audit trail।'],
        ),

        'rental_vendors' => kb_workflow_pair(
            [['step' => '1', 'who' => 'Transport Officer', 'action' => 'TMS → <strong>Rental Vendors</strong> → New vendor', 'result' => 'Vendor contract record'],
             ['step' => '2', 'who' => 'Transport Officer', 'action' => 'Enter name, contact, rate per KM, payment terms', 'result' => 'Vendor active for vehicle/driver link'],
             ['step' => '3', 'who' => 'Accounts', 'action' => 'Verify bank details match payment channel', 'result' => 'Rental payment ready']],
            ['Contract copy on file before first trip.', 'Rate change applies to new trips only.', 'Deactivate ended contracts — do not delete.'],
            [['step' => '১', 'who' => 'Transport Officer', 'action' => 'TMS → <strong>Rental Vendors</strong> → New vendor', 'result' => 'Vendor contract record'],
             ['step' => '২', 'who' => 'Transport Officer', 'action' => 'Name, contact, rate per KM, payment terms enter', 'result' => 'Vehicle/driver link-এর জন্য active'],
             ['step' => '৩', 'who' => 'Accounts', 'action' => 'Bank detail payment channel-এর সাথে match verify', 'result' => 'Rental payment ready']],
            ['প্রথম trip-এর আগে contract copy file-এ।', 'Rate change শুধু নতুন trip-এ apply।', 'Ended contract deactivate — delete নয়।'],
        ),

        'drivers' => kb_workflow_pair(
            [['step' => '1', 'who' => 'Transport Officer', 'action' => 'TMS → <strong>Company Drivers</strong> → New driver', 'result' => 'Driver form'],
             ['step' => '2', 'who' => 'Transport Officer', 'action' => 'Link to employee record, licence no, expiry, OT eligibility', 'result' => 'Driver active for trip assignment'],
             ['step' => '3', 'who' => 'Transport Officer', 'action' => 'Employee uses <strong>Transport → My Trips</strong> on portal', 'result' => 'Driver can start/end assigned trips'],
             ['step' => '4', 'who' => 'HR Officer', 'action' => 'On separation — deactivate driver record same day', 'result' => 'No orphan trip assignments']],
            ['Driver must be linked to active employee.', 'Licence expiry blocks assignment (verify manually).', 'OT only if is_overtime_active enabled.'],
            [['step' => '১', 'who' => 'Transport Officer', 'action' => 'TMS → <strong>Company Drivers</strong> → New driver', 'result' => 'Driver form'],
             ['step' => '২', 'who' => 'Transport Officer', 'action' => 'Employee record link, licence no, expiry, OT eligibility', 'result' => 'Trip assign-এর জন্য active'],
             ['step' => '৩', 'who' => 'Transport Officer', 'action' => 'Employee portal <strong>Transport → My Trips</strong> use', 'result' => 'Assigned trip start/end'],
             ['step' => '৪', 'who' => 'HR Officer', 'action' => 'Separation-এ same day driver deactivate', 'result' => 'Orphan assignment নয়']],
            ['Driver active employee-এর সাথে linked থাকতে হবে।', 'Licence expiry assign block (manually verify)।', 'is_overtime_active থাকলে OT।'],
        ),

        'rental_drivers' => kb_workflow_pair(
            [['step' => '1', 'who' => 'Transport Officer', 'action' => 'TMS → <strong>Rental Drivers</strong> → New driver', 'result' => 'Rental driver record'],
             ['step' => '2', 'who' => 'Transport Officer', 'action' => 'Link vendor, name, mobile, portal login credentials', 'result' => 'Driver can login at <code>/rental/login</code>'],
             ['step' => '3', 'who' => 'Transport Officer', 'action' => 'Assign to rental vehicle trips via Requests approve', 'result' => 'Notification sent to rental driver portal'],
             ['step' => '4', 'who' => 'Transport Officer', 'action' => 'Deactivate when vendor contract ends', 'result' => 'Portal access revoked']],
            ['Separate portal from employee portal — do not mix credentials.', 'Share login only via secure channel.', 'Rental driver cannot access employee HRM data.'],
            [['step' => '১', 'who' => 'Transport Officer', 'action' => 'TMS → <strong>Rental Drivers</strong> → New driver', 'result' => 'Rental driver record'],
             ['step' => '২', 'who' => 'Transport Officer', 'action' => 'Vendor link, name, mobile, portal login credential', 'result' => '<code>/rental/login</code>-এ login'],
             ['step' => '৩', 'who' => 'Transport Officer', 'action' => 'Requests approve-এ rental vehicle trip assign', 'result' => 'Rental driver portal-এ notification'],
             ['step' => '৪', 'who' => 'Transport Officer', 'action' => 'Vendor contract শেষ হলে deactivate', 'result' => 'Portal access revoke']],
            ['Employee portal থেকে আলাদা — credential mix নয়।', 'Login secure channel-এ share।', 'Rental driver HRM data access করতে পারবে না।'],
        ),

        'odometer' => kb_workflow_pair(
            [['step' => '1', 'who' => 'Driver / Transport Officer', 'action' => 'TMS → <strong>Daily KM</strong> → select vehicle + date', 'result' => 'Morning/evening KM entry form'],
             ['step' => '2', 'who' => 'Driver', 'action' => 'Enter morning KM at start of day', 'result' => 'Opening reading saved'],
             ['step' => '3', 'who' => 'Driver', 'action' => 'Enter evening KM at end of day', 'result' => 'Daily distance calculated'],
             ['step' => '4', 'who' => 'Transport Officer', 'action' => 'Review anomalies (negative distance, huge jump)', 'result' => 'Data quality maintained for reports']],
            ['Mandatory for all active own vehicles daily.', 'Rental vehicles use this instead of trip-level KM.', 'Evening KM must be ≥ morning KM.'],
            [['step' => '১', 'who' => 'Driver / Transport Officer', 'action' => 'TMS → <strong>Daily KM</strong> → vehicle + date select', 'result' => 'Morning/evening KM entry form'],
             ['step' => '২', 'who' => 'Driver', 'action' => 'দিন শুরুতে morning KM enter', 'result' => 'Opening reading save'],
             ['step' => '৩', 'who' => 'Driver', 'action' => 'দিন শেষে evening KM enter', 'result' => 'Daily distance calculate'],
             ['step' => '৪', 'who' => 'Transport Officer', 'action' => 'Anomaly (negative distance, huge jump) review', 'result' => 'Report-এর জন্য data quality']],
            ['সক্রিয় own vehicle-এ প্রতিদিন mandatory।', 'Rental vehicle trip-level KM-এর বদলে এটা use।', 'Evening KM ≥ morning KM।'],
        ),

        'fuel' => kb_workflow_pair(
            [['step' => '1', 'who' => 'Transport Officer', 'action' => 'TMS → <strong>Fuel</strong> → New entry', 'result' => 'Fuel issue form'],
             ['step' => '2', 'who' => 'Transport Officer', 'action' => 'Select vehicle, date, litres, cost, paid by (company/rental)', 'result' => 'Fuel log saved'],
             ['step' => '3', 'who' => 'Transport Officer', 'action' => 'Optionally link to completed trip', 'result' => 'Trip cost report includes fuel'],
             ['step' => '4', 'who' => 'Transport Manager', 'action' => 'Monthly fuel vs KM variance review', 'result' => 'Theft/leakage flagged']],
            ['Enter same day as fuel issue — not retroactive without note.', 'Fuel is manual — not auto on trip end.', 'Rental-party fuel marked separately.'],
            [['step' => '১', 'who' => 'Transport Officer', 'action' => 'TMS → <strong>Fuel</strong> → New entry', 'result' => 'Fuel issue form'],
             ['step' => '২', 'who' => 'Transport Officer', 'action' => 'Vehicle, date, litre, cost, paid by (company/rental) select', 'result' => 'Fuel log save'],
             ['step' => '৩', 'who' => 'Transport Officer', 'action' => 'Optional: completed trip link', 'result' => 'Trip cost report-এ fuel include'],
             ['step' => '৪', 'who' => 'Transport Manager', 'action' => 'Monthly fuel vs KM variance review', 'result' => 'Theft/leakage flag']],
            ['Fuel issue same day entry — note ছাড়া retroactive নয়।', 'Fuel manual — trip end-এ auto নয়।', 'Rental-party fuel আলাদা mark।'],
        ),

        'maintenance' => kb_workflow_pair(
            [['step' => '1', 'who' => 'Transport Officer', 'action' => 'TMS → <strong>Maintenance</strong> → New bill', 'result' => 'Service bill form'],
             ['step' => '2', 'who' => 'Transport Officer', 'action' => 'Vehicle, service type, workshop, parts from catalog, amount', 'result' => 'Bill status Open'],
             ['step' => '3', 'who' => 'Transport Manager', 'action' => 'Review bill → Close when work verified', 'result' => 'Bill queued for finance posting'],
             ['step' => '4', 'who' => 'Accounts', 'action' => 'Post payment via <strong>Bill For Posting</strong> screen', 'result' => 'Maintenance cost in fleet reports']],
            ['Set vehicle Maintenance status during major repair.', 'Attach workshop invoice scan.', 'Accident repairs need incident reference.'],
            [['step' => '১', 'who' => 'Transport Officer', 'action' => 'TMS → <strong>Maintenance</strong> → New bill', 'result' => 'Service bill form'],
             ['step' => '২', 'who' => 'Transport Officer', 'action' => 'Vehicle, service type, workshop, parts catalog, amount', 'result' => 'Bill status Open'],
             ['step' => '৩', 'who' => 'Transport Manager', 'action' => 'Bill review → work verify হলে Close', 'result' => 'Finance posting queue'],
             ['step' => '৪', 'who' => 'Accounts', 'action' => '<strong>Bill For Posting</strong> screen-এ payment post', 'result' => 'Fleet report-এ maintenance cost']],
            ['Major repair-এ vehicle Maintenance status set।', 'Workshop invoice scan attach।', 'Accident repair-এ incident reference।'],
        ),

        'maintenance_posting' => kb_workflow_pair(
            [['step' => '1', 'who' => 'Accounts', 'action' => 'TMS → <strong>Bill For Posting</strong> → pending queue', 'result' => 'Closed maintenance bills awaiting payment'],
             ['step' => '2', 'who' => 'Accounts', 'action' => 'Verify bill against workshop invoice', 'result' => 'Amounts match'],
             ['step' => '3', 'who' => 'Accounts Manager', 'action' => 'Mark posted / paid with payment reference', 'result' => 'Bill removed from pending queue']],
            ['Post only closed maintenance bills.', 'Keep payment reference for audit.', 'Dispute returns bill to Maintenance for correction.'],
            [['step' => '১', 'who' => 'Accounts', 'action' => 'TMS → <strong>Bill For Posting</strong> → pending queue', 'result' => 'Payment-awaiting closed bill'],
             ['step' => '২', 'who' => 'Accounts', 'action' => 'Workshop invoice-এর সাথে bill verify', 'result' => 'Amount match'],
             ['step' => '৩', 'who' => 'Accounts Manager', 'action' => 'Payment reference সহ posted/paid mark', 'result' => 'Pending queue থেকে remove']],
            ['শুধু closed maintenance bill post।', 'Audit-এর জন্য payment reference রাখুন।', 'Dispute হলে Maintenance-এ correction।'],
        ),

        'maintenance_parts' => kb_workflow_pair(
            [['step' => '1', 'who' => 'Transport Officer', 'action' => 'TMS → <strong>Parts Catalog</strong> → New part/service', 'result' => 'Catalog item saved'],
             ['step' => '2', 'who' => 'Transport Officer', 'action' => 'Set name, unit (Pcs/Service/Ltr), default rate', 'result' => 'Available in maintenance bill entry'],
             ['step' => '3', 'who' => 'Transport Officer', 'action' => 'Use catalog when creating maintenance bills', 'result' => 'Faster entry; consistent naming']],
            ['Build catalog before bulk maintenance entry.', 'Update rates yearly.', 'Deactivate obsolete parts — do not delete.'],
            [['step' => '১', 'who' => 'Transport Officer', 'action' => 'TMS → <strong>Parts Catalog</strong> → New part/service', 'result' => 'Catalog item save'],
             ['step' => '২', 'who' => 'Transport Officer', 'action' => 'Name, unit (Pcs/Service/Ltr), default rate set', 'result' => 'Maintenance bill entry-এ available'],
             ['step' => '৩', 'who' => 'Transport Officer', 'action' => 'Maintenance bill-এ catalog use', 'result' => 'দ্রুত entry; consistent naming']],
            ['Bulk maintenance entry-এর আগে catalog build।', 'Rate yearly update।', 'Obsolete part deactivate — delete নয়।'],
        ),

        'rental_charges' => kb_workflow_pair(
            [['step' => '1', 'who' => 'Transport Officer', 'action' => 'TMS → <strong>Rental Charges</strong> → pending list', 'result' => 'KM charges from completed rental trips'],
             ['step' => '2', 'who' => 'Transport Officer', 'action' => 'Verify charge against trip log and vendor rate', 'result' => 'Discrepancies flagged'],
             ['step' => '3', 'who' => 'Accounts Manager', 'action' => '<strong>Mark Paid</strong> with payment reference', 'result' => 'Vendor payment recorded'],
             ['step' => '4', 'who' => 'Accounts', 'action' => 'Export paid summary for vendor reconciliation', 'result' => 'Audit trail archived']],
            ['Verify trip KM before marking paid.', 'Payment reference mandatory.', 'Dispute unresolved charges with vendor before pay.'],
            [['step' => '১', 'who' => 'Transport Officer', 'action' => 'TMS → <strong>Rental Charges</strong> → pending list', 'result' => 'Completed rental trip KM charge'],
             ['step' => '২', 'who' => 'Transport Officer', 'action' => 'Trip log ও vendor rate-এর সাথে charge verify', 'result' => 'Discrepancy flag'],
             ['step' => '৩', 'who' => 'Accounts Manager', 'action' => 'Payment reference সহ <strong>Mark Paid</strong>', 'result' => 'Vendor payment record'],
             ['step' => '৪', 'who' => 'Accounts', 'action' => 'Vendor reconciliation-এর জন্য paid summary export', 'result' => 'Audit trail archive']],
            ['Mark paid-এর আগে trip KM verify।', 'Payment reference mandatory।', 'Pay-এর আগে unresolved charge vendor-এর সাথে resolve।'],
        ),

        'reports' => kb_workflow_pair(
            [['step' => '1', 'who' => 'Transport Officer', 'action' => 'TMS → <strong>Reports</strong> → select report type + period', 'result' => 'Filtered fleet dataset'],
             ['step' => '2', 'who' => 'Transport Officer', 'action' => 'Generate trip cost / fuel / odometer / OT summary', 'result' => 'On-screen preview'],
             ['step' => '3', 'who' => 'Transport Manager', 'action' => 'Review anomalies → export PDF/Excel', 'result' => 'Management report archived'],
             ['step' => '4', 'who' => 'Factory Manager', 'action' => 'Monthly fleet cost sign-off', 'result' => 'Budget tracking updated']],
            ['Monthly export mandatory for management review.', 'Cross-check fuel report with Daily KM.', 'OT report reconciles with Trips Mark Paid.'],
            [['step' => '১', 'who' => 'Transport Officer', 'action' => 'TMS → <strong>Reports</strong> → report type + period select', 'result' => 'Filtered fleet dataset'],
             ['step' => '২', 'who' => 'Transport Officer', 'action' => 'Trip cost / fuel / odometer / OT summary generate', 'result' => 'On-screen preview'],
             ['step' => '৩', 'who' => 'Transport Manager', 'action' => 'Anomaly review → PDF/Excel export', 'result' => 'Management report archive'],
             ['step' => '৪', 'who' => 'Factory Manager', 'action' => 'Monthly fleet cost sign-off', 'result' => 'Budget tracking update']],
            ['Management review-এর জন্য monthly export mandatory।', 'Fuel report Daily KM-এর সাথে cross-check।', 'OT report Trips Mark Paid-এর সাথে reconcile।'],
        ),

        'device_api' => kb_workflow_pair(
            [['step' => '1', 'who' => 'IT / Transport Officer', 'action' => 'TMS → <strong>GPS Device / Telematics API</strong> → settings', 'result' => 'Provider config (device_api / browser / none)'],
             ['step' => '2', 'who' => 'IT Officer', 'action' => 'Set TMS_GPS_API_TOKEN in .env; configure vendor POST endpoint', 'result' => 'Telematics can push positions'],
             ['step' => '3', 'who' => 'IT Officer', 'action' => 'Send test position → verify on vehicle history', 'result' => 'GPS tracking live'],
             ['step' => '4', 'who' => 'Transport Officer', 'action' => 'Use location history during trip dispute investigation', 'result' => 'Audit evidence available']],
            ['Rotate API token if compromised.', 'Browser GPS requires driver mobile permission.', 'None disables all GPS features.'],
            [['step' => '১', 'who' => 'IT / Transport Officer', 'action' => 'TMS → <strong>GPS Device / Telematics API</strong> → settings', 'result' => 'Provider config (device_api / browser / none)'],
             ['step' => '২', 'who' => 'IT Officer', 'action' => '.env-এ TMS_GPS_API_TOKEN; vendor POST endpoint configure', 'result' => 'Telematics position push'],
             ['step' => '৩', 'who' => 'IT Officer', 'action' => 'Test position send → vehicle history verify', 'result' => 'GPS tracking live'],
             ['step' => '৪', 'who' => 'Transport Officer', 'action' => 'Trip dispute investigation-এ location history use', 'result' => 'Audit evidence available']],
            ['Compromise হলে API token rotate।', 'Browser GPS-এ driver mobile permission।', 'None সব GPS feature disable।'],
        ),

    ],

];
