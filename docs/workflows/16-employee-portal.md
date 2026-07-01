# Employee Portal (এক নজরে)

**Route:** `/employee`  
**Auth:** Separate login (`auth:employee`) — HRM employee linked portal account

---

## মূল কাজ

কর্মীর **self-service HR** — admin panel-এর বাইরে mobile-friendly portal থেকে daily HR needs handle করা।

---

## Sub-modules

| Key | Route | Label | কাজ |
|-----|-------|-------|-----|
| `dashboard` | `/employee` | Home | Monthly stats, quick actions, alerts |
| `profile` | `/employee/profile` | Profile | Personal & official info view |
| `attendance` | `/employee/attendance` | Attendance | Monthly attendance history |
| `check-in` | `/employee/check-in` | Mobile Check-in | Gate QR + GPS check-in |
| `late-acceptance` | `/employee/late-acceptance` | Late Acceptance | Late forgiveness apply |
| `leave` | `/employee/leave` | Leave | Apply, cancel; managers approve team |
| `payslips` | `/employee/payslips` | Payslips | View/print closed payslips |
| `loans` | `/employee/loans` | Loans & Advances | Apply & view loan statement |
| `roster` | `/employee/roster` | Shift Roster | Personal shift schedule |
| `pf` | `/employee/pf` | Provident Fund | PF balance & history |
| `performance` | `/employee/performance` | Performance | Own review results |
| `separation` | `/employee/separation` | Separation | Submit resignation; approve team |
| `transport` | `/employee/transport` | Transport | TMS request + driver trips |
| `notifications` | `/employee/notifications` | Notifications | In-app alerts + web push |

---

## Transport Sub-features (Driver)

Driver হলে extra access:
- **My Trips** — start/end assigned trips
- **Daily KM** — morning/evening odometer

→ [14-tms.md](14-tms.md)

---

## Role-based Actions

| Feature | Employee | Line Manager | Driver |
|---------|:--------:|:------------:|:------:|
| Leave apply | ✅ | ✅ | ✅ |
| Leave approve (team) | — | ✅ | — |
| Payslip view | ✅ | ✅ | ✅ |
| Transport request | ✅ | ✅ | ✅ |
| Trip start/end | — | — | ✅ |
| Separation submit | ✅ | ✅ | ✅ |
| Separation approve | — | ✅ | — |

---

## 🔄 Flowchart

```
Employee Login ➔ Dashboard Quick Actions ➔ Module Self-Service ➔ Admin Processing (backend)
```

---

## Account Creation

HR Admin → [05-hrm-employee.md](05-hrm-employee.md) Employee enroll → Portal account create
