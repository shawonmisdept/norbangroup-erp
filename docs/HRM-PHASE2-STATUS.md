# HRM Phase 2 — Status & Remaining Work

**Portal:** https://portal.norbangroup.com  
**Reference:** `GARMENTS-HRM.md` (full blueprint)

---

## Phase 2 scope (post-MVP)

Phase 2 extends HRM beyond core Employee + Attendance + Leave + Payroll into compliance, finance, and production-scale operations.

---

## ✅ Implemented (in codebase)

| Area | Status | Notes |
|------|--------|-------|
| **ZKTeco ADMS / iClock** | Done | `routes/iclock.php`, `ZKTecoIclockController`, push import |
| **Biometric device master** | Done | Admin masters, sync dashboard |
| **Attendance processing** | Done | `ProcessAttendanceCommand`, queued jobs |
| **Employee portal** | Done | Attendance, leave, payslip, loans, PF, roster |
| **Leave workflow** | Done | Apply, approve, balance, maternity rules |
| **Payroll processing** | Done | Grades, increments, process job, payslip mail |
| **PF / Tax / Loan** | Done | Admin + employee views |
| **Final settlement / Gratuity** | Done | Calculators, approval notifications |
| **Compliance registers** | Done | Age, working hours, bonus, gratuity |
| **Notifications** | Done | Mail + in-app for leave, attendance, payroll events |
| **Multi-unit scoping** | Done | Factory scope on controllers/middleware |
| **Device setup docs** | Done | `docs/HRM-DEVICE-SETUP.md` |

---

## 🔧 Production go-live (your device)

From your SpeedFace photos — **must fix on device before sync works:**

1. **Ethernet:** Gateway `0.0.0.0` → router (e.g. `192.168.1.1`); DNS → `8.8.8.8`
2. **Cloud Server:** Address `0.0.0.0` → `portal.norbangroup.com`; Port `8081` → **443**
3. **Portal:** Register device serial in Biometric Devices; map employee Biometric IDs
4. **Server `.env`:** Set `HRM_ADMS_PUSH_TOKEN`, `HRM_ADMS_API_TOKEN`
5. **Queue worker + cron** running on production

See **`docs/HRM-DEVICE-SETUP.md`** for step-by-step.

---

## ⏳ Remaining / optional enhancements

| Item | Priority | Notes |
|------|----------|-------|
| Live map dashboard for devices | Low | ADMS is punch-based, not GPS |
| Bulk biometric user upload from portal → device | Medium | Currently enroll on device |
| Multi-device load balancing | Medium | 5000+ workers — group devices per building |
| Redis queue in production | High | Required at scale |
| Staging device test with real SN | High | Validate `/iclock/cdata` end-to-end |
| Buyer audit report pack (PDF export) | Medium | Registers exist; export polish |
| Mobile check-in geofence (employee portal) | Done in code | `GeofenceValidator`, gate points — enable per factory |
| Head office cross-factory reports | Medium | Partial via permissions |

---

## Quick verification checklist

- [ ] Device cloud icon visible on standby
- [ ] Sync dashboard shows **Last seen** &lt; 15 min
- [ ] Test punch → raw punch → daily log
- [ ] Employee portal shows attendance for test user
- [ ] Payroll test run for one unit
- [ ] Queue worker running (`hrm-sync`, `hrm-attendance`)

---

*Updated June 2026.*
