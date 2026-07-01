# HRM — Attendance (এক নজরে)

**Route:** `/admin/hrm/attendance` · `/employee/check-in` · `/iclock` (device)  
**Permission:** `hrm.attendance.view`, `hrm.attendance.sync`, `hrm.attendance.manage`, `hrm.attendance.approve`

---

## মূল কাজ

ZKTeco biometric + mobile check-in দিয়ে **৫,০০০+ worker**-এর daily attendance process — late, OT, roster সহ।

---

## ১. System (Automatic)

| ধাপ | কাজ |
|-----|-----|
| **Device Punch** | SpeedFace V5L → `/iclock` API-তে raw punch |
| **Scheduled Sync** | Cron job device থেকে punch pull + process |
| **Daily Log** | Present/Absent/Late/OT calculate |

---

## ২. HR Admin

| ধাপ | কাজ |
|-----|-----|
| **Monitor** | Daily summary, punch logs, device status |
| **Adjust** | Manual punch, half-day entry, late acceptance approve |
| **Roster** | Weekly shift assign (Excel import supported) |
| **Period Close** | Monthly attendance freeze → payroll-এ feed |

---

## ৩. Employee (Portal)

| ধাপ | কাজ |
|-----|-----|
| **View** | Monthly attendance history |
| **Check-in** | Gate QR + GPS mobile check-in |
| **Late Acceptance** | Late forgiveness application submit |

---

## Sub-modules

| Group | Key | Label | কাজ |
|-------|-----|-------|-----|
| Device | `sync` | Device Sync | ADMS sync, process today |
| Device | `punches` | Punch Logs | Raw IN/OUT punches |
| Daily | `daily` | Daily Summary | Processed attendance |
| Daily | `periods` | Periods | Monthly freeze |
| Daily | `reports` | Reports | Monthly/department reports |
| Schedule | `roster` | Shift Roster | Weekly shift assignment |
| Policy | `policy` | Policy | Late grace, deduction rules |
| Policy | `late-acceptance` | Late Acceptance | Forgiveness applications |
| HR Adj. | `half-day-entry` | Half Day Entry | Manual half-day |
| HR Adj. | `manual-punch` | Manual Punch | Fix missed punch |
| Mobile | `gate-points` | Gate QR Points | Print gate QR codes |
| — | `dashboard` | Dashboard | Today stats |

---

## 🔄 Flowchart

```
Biometric Punch ➔ Device Sync ➔ Daily Process ➔ HR Adjustments
       ↓
Monthly Period Freeze ➔ Salary Process
```

---

## Post-Process

Attendance period close → [10-hrm-salary.md](10-hrm-salary.md) payroll calculation-এ input হয়।
