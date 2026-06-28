# Transport Management System (TMS)
## Product Requirements Document (PRD)

**Version:** 1.1  
**Platform:** Laravel Web Application (Norbangroup ERP Portal)  
**Date:** June 2026  
**Status:** Ready for development (critical gaps resolved)

---

## 1. Product Overview

### 1.1 Purpose

কোম্পানির Transport Request, Vehicle Management, Driver Management এবং সংশ্লিষ্ট সকল Cost একটি Centralized System এ Manage করা — **existing Norbangroup ERP / HRM portal-এর সাথে integrated**।

### 1.2 Problem Statement

**বর্তমান অবস্থা:**
- WhatsApp Group এ Transport Request করা হয়
- Manual ভাবে Driver Assign করা হয়
- Vehicle Cost, Fuel, Maintenance কোনো System এ নেই
- Driver Overtime Manual Calculate করতে হয়
- কোনো Report বা Summary নেই

**সমস্যা:**
- Request Track করা যায় না
- Cost Visibility নেই
- Accountability নেই
- Data Loss হয়

### 1.3 Solution

Structured Web Application যেখানে —
- Employee **Employee Portal** থেকে Request করবে
- Authority (Admin User) Approve/Reject + Assign করবে
- Driver (HRM Employee) **Employee Portal** থেকে Trip Log করবে
- সব Cost Auto Track হবে
- Report Generate হবে
- Multi-unit factory scope enforce হবে

### 1.4 ERP / HRM Integration (Critical)

TMS **আলাদা application নয়** — Norbangroup order-portal ERP-র module।

```
Norbangroup Portal (portal.norbangroup.com)
├── Commercial ERP — existing
├── HRM Admin — /admin/hrm/...
├── TMS Admin — /admin/tms/...          ← NEW
└── Employee Portal — /employee/...     ← transport module added
```

| Concern | Decision |
|---------|----------|
| **Employee requester** | `EmployeePortalUser` → linked `hrm_employees` record |
| **Driver** | HRM `Employee` with linked `TmsDriver` profile (`employee_id` FK). Driver uses **same Employee Portal** with trip screens |
| **Authority / Admin** | Existing `users` table + `roles.permissions` (`tms.*` keys) |
| **Multi-unit scope** | All TMS tables have `factory_id`; middleware `factory.scope` (same as HRM) |
| **Settings** | TMS-specific keys in dedicated `tms_settings` table (not generic key/value only); optional mirror of office times in unit settings |
| **Notifications** | Laravel database notifications + admin bell + employee bell (same pattern as HRM) |
| **Permissions config** | `config/tms.php` (mirrors `config/hrm.php` structure) |
| **Audit** | `created_by`, `updated_by` on admin actions; status history on requests |

**Reuse (do not duplicate):**
- `factories` master (Unit)
- `departments` (for department-wise reports)
- `EmployeePortalUser` auth guard (`auth:employee`)
- Role/permission UI (`admin/roles`)
- Notification bell partials
- `AppSetting.notify_popup_enabled` as master toggle for in-app alerts

---

## 2. Stakeholders & Roles

| Role | Platform identity | Description | Access |
|------|-------------------|-------------|--------|
| **Administrator** | `User` (Administrator role) | Full TMS setup + all units (if not factory-scoped) | All `tms.*` permissions |
| **Authority (AGM / Transport Admin)** | `User` with `tms.requests.approve` | Approve/reject, assign vehicle & driver | Requests, trips, reports (own unit) |
| **Employee** | `EmployeePortalUser` | Submit & track own transport requests | `/employee/transport/*` |
| **Driver** | `EmployeePortalUser` + `TmsDriver` profile | Start/end assigned trips only | `/employee/transport/trips/*` |

> **Note:** Driver is not a separate login system. A driver is an HRM employee whose portal account has an active `TmsDriver` record. Non-driver employees cannot access trip screens.

---

## 3. MVP Phasing

### Phase 1 — MVP (build first)

| Include | Exclude (Phase 2) |
|---------|-------------------|
| Settings (office time, destinations) | Maintenance + parts |
| Vehicle CRUD + status | GPS tracking |
| Driver CRUD (linked to employee) | SMS / WhatsApp |
| Transport request + approve/reject/cancel | Mobile native app |
| Trip start/end + OT calculation | Payroll integration |
| Fuel entry (per trip) | Budget vs actual |
| Basic reports + Excel export | Advanced cost allocation |

### Phase 2 — Enhancements

- Maintenance module + parts
- SMS / WhatsApp notifications
- GPS tracking
- Driver salary / payroll OT export
- Budget vs actual cost comparison
- Department cost charge-back

---

## 4. Modules & Sub-Modules

### Module 1 — Settings

```
1.1 Office Time Settings (per factory_id)
      ├── Office Start Time
      ├── Office End Time
      └── OT Basis → global_office_time | employee_shift_end (see §8)

1.2 Destination Settings (per factory_id)
      ├── Destination List
      ├── Add / Edit / Delete
      └── Active / Inactive Toggle
```

### Module 2 — Vehicle Management

```
2.1 Vehicle List (scoped by factory_id)
      ├── Own Vehicle List
      └── Rental Vehicle List

2.2 Vehicle Add / Edit / Delete
      ├── factory_id (Unit)
      ├── Name
      ├── Registration Number (unique per factory)
      ├── Type → own / rental
      ├── Fuel Type → gas / petrol / diesel
      ├── Passenger Capacity (max)
      └── Rental Settings (if rental)
            ├── Rental Company Name
            ├── Rental Amount
            ├── Fuel Covered By → company / rental_party
            └── Maintenance Covered By → company / rental_party

2.3 Vehicle Status Management
      └── available / on_trip / maintenance
      (auto: on_trip when trip starts; available when trip completes)
```

### Module 3 — Driver Management

```
3.1 Driver List (scoped by factory_id)

3.2 Driver Add / Edit / Deactivate
      ├── employee_id (FK → hrm_employees, required)
      ├── factory_id
      ├── License Number
      ├── Overtime Rate Per Hour (BDT)
      ├── Overtime Active → Yes / No
      ├── ot_rate_effective_from (optional — for rate history)
      └── Status → active / inactive

3.3 Rules
      └── One employee → one driver profile per factory
```

### Module 4 — Transport Request

```
4.1 New Request (Employee Portal)
      ├── Pickup Location (text, required)
      ├── Destination
      │     ├── Dropdown (predefined, optional)
      │     └── Custom Text (required if no dropdown selected)
      ├── Pickup Date & Time (must be future or today, configurable)
      ├── Purpose (required)
      └── Passenger Count (required, ≤ vehicle capacity on assign)

4.2 My Request List (Employee Portal)
      └── Status tracking (see §5.1)

4.3 Request Management (Admin / Authority)
      ├── Pending requests list (unit-scoped)
      ├── Approve + Assign (single action)
      │     ├── Vehicle (must be available, capacity ≥ passenger_count)
      │     └── Driver (must be active, no other in_progress trip)
      ├── Reject (reason required)
      └── View history / audit trail

4.4 Cancel (Employee)
      └── Only while status = pending
```

**Status lifecycle (§5.1)** replaces simple Pending/Approved/Rejected/Completed only.

### Module 5 — Trip Log

```
5.1 Active Trips (Driver — Employee Portal)

5.2 Trip Start
      ├── Start KM (required, ≥ last recorded odometer for vehicle)
      ├── Duty Start Time (auto — server time, editable by admin only)
      └── Sets request status → in_progress, vehicle → on_trip

5.3 Trip End
      ├── End KM (required, > start_km)
      ├── Duty End Time (auto — server time)
      ├── Total KM (auto)
      ├── OT calculation (auto — see §8)
      └── Sets request status → completed, vehicle → available

5.4 Rules
      ├── One active trip per vehicle at a time
      ├── One active trip per driver at a time
      └── Trip log created on approve; start/end update same record
```

### Module 6 — Fuel Management (Phase 1)

```
6.1 Fuel Entry (per completed trip, optional but encouraged)
      ├── Fuel Type → gas / petrol / diesel
      ├── Quantity (litre for petrol/diesel; kg or cylinder count for gas — field label adapts)
      ├── Unit Price
      ├── Total Amount (auto)
      ├── Receipt Number
      ├── Receipt Photo (optional upload)
      └── Paid By → company / rental_party (validated against vehicle type)

6.2 Fuel Summary
      ├── Per vehicle
      ├── Per trip
      └── Monthly (export Excel)
```

### Module 7 — Maintenance (Phase 2)

```
7.1 Maintenance Log
7.2 Parts Management
7.3 Cost Summary
(deferred — see Phase 2)
```

### Module 8 — Driver Overtime Payment

```
8.1 OT stored on TripLog (source of truth for hours/amount)
8.2 DriverOvertimePayment ledger (optional row per trip)
      ├── payment_status → pending / paid
      ├── paid_at, paid_by
      └── Links to trip_log_id (no duplicate OT calculation)
```

### Module 9 — Reports

```
9.1 Vehicle Reports — daily trips, monthly KM, fuel cost
9.2 Driver Reports — duty summary, OT summary
9.3 Cost Reports — company vs rental_party split
9.4 Request Reports — by date, department, employee, status

All reports: date range filter, factory filter, Excel export (Phase 1)
```

---

## 5. Request Status & Workflow

### 5.1 Status Lifecycle

| Status | Meaning | Who triggers |
|--------|---------|--------------|
| `pending` | Submitted, awaiting authority | Employee |
| `approved` | Approved + vehicle & driver assigned | Authority |
| `rejected` | Rejected with reason | Authority |
| `cancelled` | Cancelled by employee before approval | Employee |
| `in_progress` | Driver started trip | Driver |
| `completed` | Driver ended trip | Driver |

**Allowed transitions:**

```
pending → approved | rejected | cancelled
approved → in_progress (driver start)
in_progress → completed (driver end)
(rejected | cancelled | completed = terminal)
```

### 5.2 Complete Workflow

```
STEP 1 — Employee (Portal)
  └── POST /employee/transport/requests
        ├── Validates factory from employee.factory_id
        └── Status = pending
              │
              ▼ Notify → Authority (tms.requests.approve)

STEP 2 — Authority (Admin)
  └── Review pending list
        ├── REJECT → rejection_reason → Notify Employee
        ├── CANCEL not allowed here
        └── APPROVE + ASSIGN vehicle + driver
              ├── Validates vehicle available + capacity
              ├── Validates driver free (no in_progress trip)
              ├── Creates TripLog (status: not_started)
              └── Status = approved
                    │
                    ▼ Notify → Employee + Driver

STEP 3 — Driver (Portal)
  └── Trip Start → status in_progress, vehicle on_trip
  └── Trip End → status completed, OT calculated, vehicle available
                    │
                    ▼ Notify → Employee + Authority

STEP 4 — Post-trip (Admin / Phase 1 fuel)
  └── Fuel entry linked to trip_log_id

STEP 5 — Reports & OT payment marking
```

---

## 6. Database Models

| # | Table / Model | Key Fields |
|---|---------------|------------|
| 1 | `tms_settings` | `factory_id`, `office_start`, `office_end`, `ot_basis` (global_office_time \| employee_shift_end) |
| 2 | `tms_destinations` | `factory_id`, `name`, `address`, `is_active` |
| 3 | `tms_vehicles` | `factory_id`, `name`, `reg_number`, `type`, `fuel_type`, `passenger_capacity`, `status`, rental fields, `created_by` |
| 4 | `tms_drivers` | `factory_id`, `employee_id`, `license_number`, `ot_rate`, `is_overtime_active`, `ot_rate_effective_from`, `status` |
| 5 | `tms_transport_requests` | `factory_id`, `employee_id`, `pickup_location`, `destination_id`, `destination_custom`, `pickup_at`, `purpose`, `passenger_count`, `status`, `vehicle_id`, `driver_id`, `approved_by`, `approved_at`, `rejected_by`, `rejected_at`, `rejection_reason`, `cancelled_at` |
| 6 | `tms_trip_logs` | `transport_request_id`, `factory_id`, `vehicle_id`, `driver_id`, `start_km`, `end_km`, `total_km`, `duty_start_at`, `duty_end_at`, `ot_hours`, `ot_amount`, `ot_start_at`, `ot_end_at`, `trip_status` (not_started \| in_progress \| completed) |
| 7 | `tms_fuel_logs` | `factory_id`, `vehicle_id`, `trip_log_id`, `fuel_type`, `quantity`, `unit`, `unit_price`, `amount`, `receipt_number`, `receipt_path`, `paid_by` |
| 8 | `tms_transport_request_histories` | `transport_request_id`, `from_status`, `to_status`, `changed_by_user_id`, `changed_by_employee_id`, `notes`, `created_at` |
| 9 | `tms_driver_overtime_payments` | `trip_log_id`, `driver_id`, `amount`, `payment_status`, `paid_at`, `paid_by` |

**Phase 2 tables:** `tms_maintenance_logs`, `tms_maintenance_parts`

**Indexes (required):**
- `(factory_id, status)` on requests
- `(driver_id, trip_status)` on trip logs
- `(vehicle_id, status)` on vehicles
- Unique `(factory_id, reg_number)` on vehicles

**Soft deletes:** `tms_vehicles`, `tms_drivers`, `tms_destinations` (not on requests/trips — audit via history)

---

## 7. Permissions (`tms.*`)

Stored in `roles.permissions` JSON; managed via existing Admin → Roles UI.

| Permission | Description | Typical grant |
|------------|-------------|---------------|
| `tms.dashboard.view` | TMS dashboard KPIs | Admin, Authority |
| `tms.settings.view` | View destinations & office time | Admin |
| `tms.settings.manage` | Edit settings | Admin |
| `tms.vehicles.view` | View vehicles | Admin, Authority |
| `tms.vehicles.manage` | CRUD vehicles | Admin |
| `tms.drivers.view` | View drivers | Admin, Authority |
| `tms.drivers.manage` | CRUD drivers | Admin |
| `tms.requests.view` | View all unit requests | Admin, Authority |
| `tms.requests.approve` | Approve/reject/assign | Authority (AGM) |
| `tms.trips.view` | View trip logs | Admin, Authority |
| `tms.fuel.view` | View fuel logs | Admin, Authority |
| `tms.fuel.manage` | Enter fuel | Admin, Authority |
| `tms.reports.view` | All reports | Admin, Authority |
| `tms.overtime.manage` | Mark OT paid | Admin |

**Employee portal routes** do not use `tms.*` — gated by `auth:employee` + ownership / driver profile check.

**Migration seed:** Administrator gets all `tms.*`; custom "Transport Authority" role gets approve + view subset.

---

## 8. Routes & UI Map

### Admin (`/admin/tms/...`)

| Route | Screen |
|-------|--------|
| `GET /admin/tms` | Dashboard (pending requests, active trips, OT pending) |
| `GET /admin/tms/settings` | Office time + OT basis |
| `GET /admin/tms/destinations` | Destination CRUD |
| `GET /admin/tms/vehicles` | Vehicle list |
| `GET /admin/tms/drivers` | Driver list |
| `GET /admin/tms/requests` | All requests (filter by status) |
| `GET /admin/tms/requests/{id}` | Detail + approve/reject |
| `GET /admin/tms/trips` | Trip log list |
| `GET /admin/tms/fuel` | Fuel entries |
| `GET /admin/tms/reports/*` | Report hub |

Middleware: `auth`, `factory.scope`, `permission:tms.*`

### Employee Portal (`/employee/transport/...`)

| Route | Screen | Access |
|-------|--------|--------|
| `GET /employee/transport` | My requests hub | All employees |
| `GET /employee/transport/requests/create` | New request | All employees |
| `GET /employee/transport/requests/{id}` | Request detail | Owner only |
| `POST /employee/transport/requests/{id}/cancel` | Cancel pending | Owner only |
| `GET /employee/transport/trips` | Driver active trips | Driver profile only |
| `POST /employee/transport/trips/{id}/start` | Start trip | Assigned driver |
| `POST /employee/transport/trips/{id}/end` | End trip | Assigned driver |

---

## 9. Business Rules

| Rule | Description |
|------|-------------|
| BR-01 | Request শুধু authenticated `EmployeePortalUser` (active employee) submit করতে পারবে |
| BR-02 | Approve/reject শুধু `User` with `tms.requests.approve` + same `factory_id` scope |
| BR-03 | Trip start/end শুধু assigned driver (`tms_drivers.employee_id` = portal employee) |
| BR-04 | OT calculate হবে শুধু `is_overtime_active = true` driver-এর |
| BR-05 | OT start = configured basis end time (§10) — **not** always global office end |
| BR-06 | OT end = duty end time |
| BR-07 | Rental vehicle-এ fuel/maintenance `paid_by` must respect vehicle rental settings |
| BR-08 | Destination: dropdown **or** custom text — at least one required |
| BR-09 | Vehicle status auto: `available` ↔ `on_trip` on trip start/end |
| BR-10 | Fuel total = quantity × unit price (auto) |
| BR-11 | One vehicle cannot have two `in_progress` trips |
| BR-12 | One driver cannot have two `in_progress` trips |
| BR-13 | `passenger_count` ≤ assigned vehicle `passenger_capacity` |
| BR-14 | Employee may cancel only `pending` requests |
| BR-15 | `pickup_at` must not be in the past (beyond configurable grace, default 0) |
| BR-16 | `end_km` > `start_km`; start_km ≥ vehicle last end_km |
| BR-17 | Unit-scoped admin users cannot view other factories' TMS data |
| BR-18 | OT payment ledger references `trip_log` — no duplicate OT math in payment table |
| BR-19 | Driver must be `active` HRM employee (`status` in active/probation) to start trip |
| BR-20 | Completed trip cannot be re-opened; corrections via admin note + new adjustment entry (Phase 2) |

---

## 10. Overtime Calculation Logic

### 10.1 OT Basis (per factory setting)

```
ot_basis = global_office_time (default)
  → OT threshold = tms_settings.office_end for pickup date

ot_basis = employee_shift_end
  → OT threshold = employee.shift.end_time on pickup date
  → Fallback to global office_end if employee has no shift
```

### 10.2 Calculation

```
IF driver.is_overtime_active = false
  → ot_hours = 0, ot_amount = 0

ELSE IF duty_end_at <= ot_threshold
  → ot_hours = 0

ELSE
  ot_start_at = ot_threshold (on pickup date)
  ot_end_at   = duty_end_at
  ot_hours    = diff in hours (decimal, 2 places)
  ot_amount   = ot_hours × driver.ot_rate (rate effective on pickup date)

Midnight crossing:
  If duty_end_at is next calendar day, calculation spans midnight correctly.
```

### 10.3 Example (global basis)

```
Office End   : 05:00 PM
Duty End     : 09:00 PM
OT Hours     : 4.00
OT Rate      : 150 BDT/hr
OT Amount    : 600 BDT
```

---

## 11. Notifications

Uses existing notification infrastructure (`database` channel + admin/employee bells).

| Event | Notify | Channel | AppSetting gate |
|-------|--------|---------|-----------------|
| Request submitted | Users with `tms.requests.approve` (unit) | In-app | `notify_popup_enabled` |
| Request approved | Employee + Driver | In-app | `notify_popup_enabled` |
| Request rejected | Employee | In-app | `notify_popup_enabled` |
| Request cancelled | Authority | In-app | `notify_popup_enabled` |
| Trip started | Employee + Authority | In-app | `notify_popup_enabled` |
| Trip completed | Employee + Authority | In-app | `notify_popup_enabled` |
| OT pending payment | Admin | In-app | `notify_popup_enabled` |

**Phase 2:** SMS/WhatsApp via existing `AppSetting` SMS fields.

---

## 12. Acceptance Criteria (MVP)

| # | Criteria |
|---|----------|
| AC-01 | Employee submits request → authority sees it in pending list + notification |
| AC-02 | Authority approves with vehicle + driver → employee & driver notified |
| AC-03 | Authority rejects → employee sees reason |
| AC-04 | Employee cancels pending request → removed from pending queue |
| AC-05 | Driver starts trip → request `in_progress`, vehicle `on_trip` |
| AC-06 | Driver ends trip → request `completed`, vehicle `available`, OT calculated |
| AC-07 | Same vehicle cannot be assigned to two concurrent in-progress trips |
| AC-08 | Factory-scoped user cannot access another unit's requests |
| AC-09 | Non-driver employee cannot access `/employee/transport/trips` |
| AC-10 | Reports export to Excel for date range + factory |
| AC-11 | Passenger count > vehicle capacity blocked at approve time |
| AC-12 | OT = 0 when driver `is_overtime_active = false` |

---

## 13. Future Scope (Phase 2+)

- Maintenance module + parts inventory
- SMS / WhatsApp notification integration
- Progressive Web App optimizations for drivers
- GPS / odometer photo capture on trip start/end
- Monthly payroll OT export to HRM salary
- Budget vs actual cost by department/factory
- Multi-level approval (reporting manager → AGM)

---

## 14. Technical Notes (Laravel)

| Item | Convention |
|------|------------|
| Models namespace | `App\Models\Tms\*` |
| Controllers | `App\Http\Controllers\Admin\Tms\*`, `App\Http\Controllers\Employee\Transport\*` |
| Config | `config/tms.php` |
| Migrations prefix | `tms_*` tables |
| Services | `App\Services\Tms\TransportRequestService`, `TripService`, `OvertimeCalculator` |
| Policies | `TransportRequestPolicy`, `TripLogPolicy` |
| Tests | `tests/Feature/Tms/*` — workflow, OT calc, factory scope, permissions |

---

*Document prepared for internal development use. Version 1.1 — June 2026 (critical gaps resolved for Norbangroup ERP integration)*
