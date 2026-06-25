# Garments HRM — Full Blueprint

**Project:** Norbangroup ERP Portal  
**Industry:** RMG / Garments (Bangladesh)  
**Stack:** Laravel 13 · MySQL · Existing ERP (Users, Roles, Masters)

**Live portal:** https://portal.norbangroup.com

---

## Confirmed Project Parameters

| Parameter | Decision |
|-----------|----------|
| **MVP scope** | Phase 1–4: Employee + Attendance + Leave + Payroll |
| **Biometric system** | ZKTeco ADMS (Attendance Device Management System) |
| **Workforce size** | 5,000+ employees |
| **Organization** | Multi-unit (multiple factories / buildings) |
| **Employee portal** | Separate login — not shared with admin `users` table |
| **ERP integration** | Full integration with existing Norbangroup ERP |

---

## Overview

Full-scale **Garments HRM/HRMS** integrated with existing Norbangroup ERP — built for **multi-unit RMG factories** with **5,000+ workers**. Covers line, shift, OT, wages, ZKTeco biometric attendance, BD labour law, and buyer compliance.

```
Norbangroup Portal (portal.norbangroup.com)
├── Commercial ERP — existing
│   ├── Requirements
│   ├── Master Data (factories, departments, designations…)
│   ├── Users / Roles / Permissions
│   └── App Settings (mail, logos)
│
├── HRM Admin — /admin/hrm/...
│   ├── Organization (multi-unit)
│   ├── Employee Lifecycle
│   ├── Attendance (ZKTeco ADMS)
│   ├── Leave
│   ├── Payroll
│   ├── Tax / PF / Loan / Settlement (post-MVP)
│   └── Reports
│
└── Employee Portal — /employee/... (separate auth)
    ├── My profile
    ├── Attendance view
    ├── Leave apply / balance
    └── Payslip download
```

**Admin routes:** `/admin/hrm/...`  
**Employee routes:** `/employee/...`  
**Permissions:** `hrm.*` (follow existing `orders.view` pattern)

---

## Scale & Architecture Notes (5,000+ / Multi-unit)

- Every core table scoped by `factory_id` / `unit_id`
- Biometric sync via **queued jobs** (not synchronous per punch)
- Attendance logs partitioned or indexed by `(employee_id, date)`
- Payroll run as background job with progress status
- Unit-level HR managers see only their factory data
- Super Admin / Group HR sees all units
- Redis/database queue recommended for production
- Scheduled tasks: ZKTeco sync every 5–15 min per device group

---

## 1. Organization Setup (Multi-unit Factory Structure)

```
Group (Norbangroup)
  └── Factory / Unit          ← maps to existing `factories` master
       └── Building
            └── Floor
                 └── Line / Section
                      └── Sub-section
```

- **Multi-unit:** Each unit has own shifts, holiday calendar, attendance devices
- Buyer-wise line allocation (optional)
- Cost center per line
- Shift: General, Night, OT (unit-specific)
- Holiday calendar (unit + buyer-specific)
- Departments: Production, Cutting, Finishing, IE, HR, Accounts, Compliance
- Cross-unit employee transfer with service history

**Reuse existing masters:** `factories`, `departments`, `designations`

**New HRM masters:** `hrm_buildings`, `hrm_floors`, `hrm_lines`, `hrm_shifts`, `hrm_holidays`

---

## 2. Employee Lifecycle Management

| Feature | Garments detail |
|---------|-----------------|
| Enrollment | NID, birth cert, photo, nominee, emergency contact, blood group |
| Worker category | Operator, Helper, Iron man, QC, Line chief, Supervisor, Staff |
| Employment type | Permanent, Contract, Probation, Trainee, Sub-contract |
| Unit assignment | Primary factory + current line, floor, section |
| Biometric mapping | ZKTeco device user ID ↔ Employee code |
| ID card | Barcode/QR, logo, unit, line, blood group, emergency no |
| HR letters | Appointment, confirmation, transfer, warning, suspension, termination |
| Service history | Unit/line transfer, designation, salary revision, promotion |
| Disciplinary | Show cause, warning, suspension, misconduct log |
| OSD / Movement | Official duty, buyer visit, training |
| Departure | Resignation, termination, layoff, absconding, retirement |
| Compliance docs | Age verification, fire training, safety induction, medical |

**Employee portal account:** Created on enrollment — separate `hrm_employee_portal_users` linked to `hrm_employees`

---

## 3. Attendance System (ZKTeco ADMS)

| Feature | Detail |
|---------|--------|
| Device integration | **ZKTeco ADMS** — push/pull API or scheduled sync |
| Device registry | Per unit: device IP, serial, location (gate/line) |
| Policy | Late grace, early leave, half-day, absent rules (unit-wise) |
| Shift / session | 8hr / 10hr, breaks, night allowance trigger |
| Punch sync | Auto import IN/OUT from ADMS → `hrm_attendance_logs` |
| OT calculation | Daily, holiday, night OT |
| Process cycle | Draft → Process → Reconcile → **Freeze** |
| Manual reconcile | Missing punch, wrong device, holiday work |
| Reports | Unit/line/shift-wise, 360 employee calendar view |
| Compliance | Working hour limits (buyer audit) |

**ZKTeco ADMS integration flow:**

```
ZKTeco Devices (per unit)
    ↓ ADMS API / middleware sync (scheduled job)
hrm_biometric_devices
    ↓
hrm_attendance_raw_punches
    ↓ process job
hrm_attendance_logs (daily summary per employee)
    ↓ freeze
Payroll input
```

**RMG extras:** Line headcount vs attendance, proxy punch flag, gate pass

---

## 4. Leave Management

| Leave type | Notes |
|------------|-------|
| Casual (CL) | Status-wise entitlement |
| Sick (SL) | Medical cert after X days |
| Earned / Annual (EL) | Monthly accrual |
| Maternity | 16 weeks (BD law) |
| Paternity | Policy-based |
| Festival / Eid | Unit holiday calendar |
| Unpaid (LWP) | Payroll deduction |
| Compensatory off | OT day replacement |

- Approval: Line Chief → Supervisor → HR → Factory Manager
- **Employee portal:** apply, balance view, status track
- Unit-scoped leave policy
- Reconcile with attendance before payroll freeze
- EL encashment on separation

---

## 5. Payroll Processing

### Salary-based (Staff / Officers)

Gross = Basic + HRA + Medical + Conveyance + Other  
Deductions = Tax, PF, Loan, Absent, Late, Advance

### Wages-based (Workers — bulk of 5,000+)

- Basic + attendance wage
- OT pay (rate × hours × multiplier)
- Production incentive (line-wise, optional)
- Attendance bonus
- Night shift allowance
- Holiday work pay

### Month-end cycle (per unit)

```
Open Period → Sync Attendance (frozen) → Import Leave
→ Calculate Gross (queued job) → Deductions → Net Pay
→ Review → Approve → FREEZE
→ Bank advise / Cash list → Payslip email
```

### Other items

- Festival bonus (Eid — BD law)
- Performance bonus, arrear, loan EMI
- Advance recovery, canteen deduction (optional)

### Disbursement

- Bank advise file (unit-wise)
- Cash list by line
- Partial / hold salary

**Performance:** Payroll calculation for 5,000+ employees runs as queued batch job with unit-level progress tracking.

---

## 6. Tax Management (Bangladesh) — Post-MVP

- Assessment year (July–June)
- Tax slabs, investment rebate
- Monthly TDS from salary
- Individual tax ledger
- Year-end tax certificate

---

## 7. PF, Gratuity, Loan — Post-MVP

| Module | Use |
|--------|-----|
| PF / CPF | Employee + employer contribution |
| Gratuity | 5+ years — BD formula, accrual ledger |
| Loan | Advance, emergency, EMI schedule |
| Integration | Auto deduct in payroll |

---

## 8. Notifications & Alerts

| Event | Channel |
|-------|---------|
| Late / absent | HR + Line chief |
| Leave apply/approve | Email + employee portal |
| Payslip ready | Email + PDF + portal download |
| Contract expiry | HR (90/30/7 days) |
| Probation end | HR |
| OT limit exceeded | Compliance |
| ZKTeco sync fail | IT / HR (per unit) |
| Settlement pending | Accounts + HR |

**Reuse:** Existing App Settings Gmail + notification bell pattern

---

## 9. Final Settlement — Post-MVP

```
Exit → Notice period → Last working day
→ Attendance finalize → Leave encashment
→ Gratuity → PF → Loan clearance → Tax
→ Settlement sheet → Bank advise
→ Release letter → Clearance (HR, IT, Stores, Accounts, Line Chief)
→ Full & Final payslip
```

---

## 10. Garments-Only Extras

| Module | Purpose |
|--------|---------|
| Manpower planning | Line required vs actual (unit-wise) |
| Worker transfer | Unit / line / floor transfer |
| Contract renewal | Bulk contract workers |
| Canteen / food | Deduction or subsidy |
| Medical / clinic | Visit log |
| Training & compliance | Buyer audit readiness |
| Labour law registers | BD format |
| Sub-contract workers | Agency manpower |
| Production incentive | Line output bonus |

---

## User Roles (Multi-unit)

| Role | Access |
|------|--------|
| Super Admin | All units |
| Group HR Manager | All units — full HRM |
| Unit HR Manager | Single factory/unit only |
| HR Executive | Employee, leave, attendance (unit-scoped) |
| Payroll Officer | Payroll, bank (unit or group) |
| Line Chief / Supervisor | Team L1 approve (own line) |
| Production Manager | Line reports (own unit) |
| Accounts | Disbursement, settlement |
| Compliance Officer | Audit reports (all units) |
| Employee | Self-service portal (own data only) |

---

## Route Structure

### HRM Admin (existing ERP auth — `users` table)

```
/admin/hrm
├── /dashboard
├── /organization       ← units, floors, lines, shifts
├── /employees
├── /attendance
│   ├── /devices        ← ZKTeco device registry
│   ├── /sync           ← manual sync trigger
│   └── /reports
├── /leave
├── /payroll
├── /reports
└── /settings           ← unit policies
```

### Employee Portal (separate auth — `hrm_employee_portal_users`)

```
/employee
├── /login
├── /dashboard
├── /profile
├── /attendance
├── /leave
│   ├── /apply
│   └── /history
└── /payslips
```

---

## Authentication Architecture

| Portal | Guard | Table | Users |
|--------|-------|-------|-------|
| ERP Admin | `web` (existing) | `users` | HR staff, managers, admin |
| Employee Portal | `employee` (new) | `hrm_employee_portal_users` | All 5,000+ workers |

- Employee login: Employee code + password (or OTP later)
- No access to `/admin/*` routes
- Admin users optionally linked to `hrm_employees` for staff who are also employees

---

## ERP Integration

| ERP Module | HRM Integration |
|------------|-----------------|
| **Factories master** | Unit/factory dropdown in all HRM screens |
| **Departments** | Employee department assignment |
| **Designations** | Employee designation / grade |
| **Users & Roles** | HRM admin permissions (`hrm.*`) |
| **App Settings** | Mail for payslip, leave notifications |
| **ERP Sidebar** | New "HRM" menu group |
| **Notification bell** | HRM alerts for admin users |
| **Master Data hub** | Link to factory/department masters |

**Shared database** — same Laravel app, same MySQL, `hrm_*` tables alongside existing tables.

---

## Core Database Tables

### Organization
```
hrm_buildings
hrm_floors
hrm_lines
hrm_shifts
hrm_holidays
hrm_biometric_devices
```

### Employee
```
hrm_employees                  (factory_id, department_id, designation_id)
hrm_employee_portal_users      (separate login)
hrm_employee_line_assignments
hrm_employee_documents
hrm_employee_service_history
```

### Attendance
```
hrm_attendance_policies
hrm_attendance_raw_punches      (from ZKTeco ADMS)
hrm_attendance_logs
hrm_attendance_periods          (draft/process/frozen)
```

### Leave
```
hrm_leave_types
hrm_leave_policies
hrm_leave_balances
hrm_leave_applications
hrm_leave_approvals
```

### Payroll (MVP)
```
hrm_salary_structures
hrm_salary_components
hrm_payroll_periods
hrm_payroll_runs
hrm_payroll_items
```

### Post-MVP
```
hrm_tax_years
hrm_employee_tax_ledgers
hrm_pf_accounts
hrm_loan_accounts
hrm_settlements
hrm_hr_letter_templates
hrm_issued_letters
```

---

## Build Phases

### MVP (Phase 1–4) — Target: ~5–6 months

| Phase | Scope | Est. time |
|-------|-------|-----------|
| **1** | Multi-unit org, Employee master, portal auth, ID card, documents | 6–8 weeks |
| **2** | ZKTeco ADMS sync, attendance policy, process/freeze, reports | 6–8 weeks |
| **3** | Leave policy, approval workflow, employee self-service portal | 4–6 weeks |
| **4** | Payroll (wages + salary), month-end freeze, payslip, bank advise | 8–10 weeks |

### Post-MVP (Phase 5–7) — ~6–8 months additional

| Phase | Scope | Est. time |
|-------|-------|-----------|
| **5** | Tax, PF, loan, festival bonus | 6–8 weeks |
| **6** | HR letters, discipline, final settlement | 6–8 weeks |
| **7** | Compliance reports, buyer audit exports | 4–6 weeks |

**Full system:** ~12–18 months (1 developer)

---

## Bangladesh Compliance Checklist

- [ ] Minimum wage category
- [ ] OT rates (normal / holiday / night)
- [ ] Eid bonus calculation
- [ ] Maternity benefit
- [ ] Gratuity on separation
- [ ] Working hour limits
- [ ] Age verification (child labour prevention)
- [ ] Statutory registers

---

## Integrations

| System | Purpose | Status |
|--------|---------|--------|
| **ZKTeco ADMS** | Biometric punch sync (multi-device, multi-unit) | MVP Phase 2 |
| **Norbangroup ERP** | Shared masters, auth, mail, sidebar | MVP Phase 1 |
| **Bank** | Salary advise CSV/Excel export | MVP Phase 4 |
| **Gmail (App Settings)** | Payslip, leave email | MVP Phase 3–4 |
| **SMS gateway** | Optional alerts | Post-MVP |

---

## Reuse from Current ERP

| Existing | HRM use |
|----------|---------|
| Users, Roles, Permissions | HR admin access (`hrm.*` permissions) |
| Departments, Designations | Employee mapping |
| Factories master | Multi-unit factory structure |
| ERP sidebar + layout | HRM admin UI |
| App Settings + Gmail | Payslip / leave notifications |
| Notification bell | HRM admin alerts |
| Queue (database) | Biometric sync + payroll jobs |

---

## Phase 1 — First Deliverables

1. `hrm_*` migration skeleton + `factory_id` scoping
2. HRM permissions in `config/permissions.php` + sidebar menu
3. Multi-unit org CRUD (building, floor, line)
4. Employee enrollment form + list (5,000+ ready pagination/search)
5. Separate employee portal login (`/employee/login`)
6. Link to existing factories, departments, designations masters

---

© Norban Group — Garments HRM Blueprint
