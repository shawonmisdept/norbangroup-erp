# HRM — Overview (এক নজরে)

**Route:** `/admin/hrm`  
**Permission:** `hrm.*` (module-wise view/manage/approve)

---

## মূল কাজ

Multi-unit RMG factory-এর **৫,০০০+ workforce**-এর HR lifecycle — enrollment থেকে payroll, compliance, attendance পর্যন্ত এক portal-এ।

---

## HRM Hub Structure

| Hub | File | মূল কাজ |
|-----|------|---------|
| Dashboard | — | Cross-module KPIs |
| Employee | [05-hrm-employee.md](05-hrm-employee.md) | Enrollment, separation, letters |
| Recruitment | [06-hrm-recruitment.md](06-hrm-recruitment.md) | Job posting, hiring pipeline |
| Attendance | [07-hrm-attendance.md](07-hrm-attendance.md) | Biometric, roster, daily process |
| Leave | [08-hrm-leave.md](08-hrm-leave.md) | Policy, apply, approve, accrual |
| Performance | [09-hrm-performance.md](09-hrm-performance.md) | Review, bonus, increment |
| Salary | [10-hrm-salary.md](10-hrm-salary.md) | Structure, payroll process, close |
| Compliance | [11-hrm-compliance.md](11-hrm-compliance.md) | BD labour law registers, bonus, gratuity |
| Finance | [12-hrm-finance.md](12-hrm-finance.md) | Tax, PF, loan, F&F settlement |
| RMG Extras | [13-hrm-rmg.md](13-hrm-rmg.md) | Gate pass, transfer, production incentive |
| HRM Masters | [04-hrm-masters.md](04-hrm-masters.md) | Building, shift, leave type setup |

---

## Employee Lifecycle (High Level)

```
Recruit → Enroll → Daily HR (Attendance/Leave) → Performance
    → Monthly Payroll → Compliance → Separation → Final Settlement
```

---

## Platforms

| Platform | Access |
|----------|--------|
| **Admin** `/admin/hrm/*` | HR, Accounts, Management |
| **Employee Portal** `/employee/*` | Self-service (leave, payslip, attendance) |
| **Careers** `/careers` | Public job apply → Recruitment hub |
| **Device** `/iclock` | ZKTeco biometric auto-sync |

---

## Multi-Unit Scope

সব HRM record **factory_id**-তে bound — unit-scoped user শুধু নিজ unit দেখে; group admin সব unit।

---

## 🔄 Core Flowchart

```
Setup (Masters) ➔ Employee Enroll ➔ Attendance + Leave ➔ Salary Process ➔ Compliance Reports
```
