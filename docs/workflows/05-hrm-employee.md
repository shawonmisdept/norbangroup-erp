# HRM — Employee (এক নজরে)

**Route:** `/admin/hrm/employee` · `/admin/hrm/employees`  
**Permission:** `hrm.employees.*`, `hrm.employees.separation.*`, `hrm.employees.promotion.*`, `hrm.employees.letters.*`, `hrm.employees.discipline.*`

---

## মূল কাজ

কর্মীর **পুরো lifecycle** — join থেকে exit পর্যন্ত profile, promotion, letters, discipline manage করা।

---

## ১. HR Admin

| ধাপ | কাজ |
|-----|-----|
| **Enroll** | Wizard দিয়ে employee profile create (personal, official, salary link) |
| **Portal Account** | Employee Portal login create |
| **ID Card** | Print/export employee ID |
| **Service Events** | Promotion, letters, discipline record |
| **Separation** | Resignation/termination initiate → approve → clearance |

---

## ২. Employee (Portal — limited)

| ধাপ | কাজ |
|-----|-----|
| **Resignation** | Separation request submit |
| **Approve** | Line manager/HR team member approve team resignation |

---

## Sub-modules

| Key | Label | কাজ |
|-----|-------|-----|
| `dashboard` | Dashboard | Headcount, joinings, pending exit stats |
| `employees` | Employees | Enroll, edit, ID card, portal account |
| `separations` | Separations | Resignation, termination, exit workflow |
| `promotions` | Promotion / Demotion | Designation change + salary revision |
| `letters` | HR Letters | Appointment, confirmation, warning, certificate |
| `discipline` | Disciplinary | Warning, suspension, misconduct record |

---

## 🔄 Flowchart

```
Enroll (Active) ➔ Service Events (Promotion/Letters/Discipline)
       ↓
Separation Request ➔ HR Approve ➔ Clearance ➔ Final Settlement (Finance hub)
```

---

## Post-Exit

Separation approve হলে → [12-hrm-finance.md](12-hrm-finance.md) Final Settlement workflow-তে যায়।
