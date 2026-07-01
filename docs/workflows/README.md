# Norbangroup Portal — Module Workflows (এক নজরে)

**Platform:** Norbangroup ERP-HRM Portal  
**Format:** Management-friendly short workflow guides  
**Updated:** June 2026

---

## Index

| # | Module | File |
|---|--------|------|
| 01 | Commercial — Requirements | [01-commercial-requirements.md](01-commercial-requirements.md) |
| 02 | Master Data (ERP) | [02-masters-erp.md](02-masters-erp.md) |
| 03 | HRM — Overview | [03-hrm-overview.md](03-hrm-overview.md) |
| 04 | HRM — Masters | [04-hrm-masters.md](04-hrm-masters.md) |
| 05 | HRM — Employee | [05-hrm-employee.md](05-hrm-employee.md) |
| 06 | HRM — Recruitment | [06-hrm-recruitment.md](06-hrm-recruitment.md) |
| 07 | HRM — Attendance | [07-hrm-attendance.md](07-hrm-attendance.md) |
| 08 | HRM — Leave | [08-hrm-leave.md](08-hrm-leave.md) |
| 09 | HRM — Performance | [09-hrm-performance.md](09-hrm-performance.md) |
| 10 | HRM — Salary / Payroll | [10-hrm-salary.md](10-hrm-salary.md) |
| 11 | HRM — Compliance | [11-hrm-compliance.md](11-hrm-compliance.md) |
| 12 | HRM — Finance | [12-hrm-finance.md](12-hrm-finance.md) |
| 13 | HRM — RMG Extras | [13-hrm-rmg.md](13-hrm-rmg.md) |
| 14 | Transport (TMS) | [14-tms.md](14-tms.md) |
| 15 | System Admin | [15-admin-system.md](15-admin-system.md) |
| 16 | Employee Portal | [16-employee-portal.md](16-employee-portal.md) |
| 17 | Rental Driver Portal | [17-rental-portal.md](17-rental-portal.md) |
| 18 | Careers (Public) | [18-careers.md](18-careers.md) |

---

## Platform Map

```
Public          →  /  (Requirements)  ·  /careers  (Jobs)
Admin           →  /admin  (ERP · HRM · TMS · Masters · Users)
Employee Portal →  /employee  (Self-service + TMS request)
Rental Driver   →  /rental  (TMS rental trips)
Device API      →  /iclock  (ZKTeco biometric — automatic)
```

---

## Permission Namespaces

| Prefix | Module |
|--------|--------|
| `orders.*` | Commercial requirements |
| `masters.*` | ERP master data |
| `hrm.*` | All HRM hubs |
| `tms.*` | Transport management |
| `users.manage` / `roles.manage` / `settings.manage` | System admin |

---

## Related Full Specs

- [GARMENTS-HRM.md](../../GARMENTS-HRM.md) — HRM technical blueprint
- [transport-management-system-prd.md](../../transport-management-system-prd.md) — TMS full PRD
- [TMS-PHASE2.md](../../TMS-PHASE2.md) — TMS Phase 2 (GPS, WhatsApp)
- [DEPLOY.md](../../DEPLOY.md) — Deployment guide
