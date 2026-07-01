# HRM — Compliance (এক নজরে)

**Route:** `/admin/hrm/compliance`  
**Permission:** `hrm.compliance.view`, `hrm.compliance.manage`

---

## মূল কাজ

Bangladesh labour law অনুযায়ী **statutory registers**, festival bonus, gratuity, age verification, working hour limit compliance।

---

## Workflow

```
Payroll + Attendance Data ➔ Compliance Reports/Runs ➔ Audit Export
```

---

## Sub-modules

| Group | Key | Label | কাজ |
|-------|-----|-------|-----|
| Registers | `registers` | Statutory Registers | Attendance, wage, leave, OT registers (BD CSV) |
| Reports | `age-verification` | Age Verification | Child labour prevention report |
| Reports | `working-hours` | Working Hour Limits | Daily/weekly hour violations |
| Benefits | `bonus` | Festival Bonus | Eid/festival bonus calculation run |
| Benefits | `gratuity` | Gratuity Settlement | 5+ years service gratuity on exit |
| — | `dashboard` | Dashboard | Bonus & gratuity overview |

---

## 🔄 Flowchart

```
Monthly Data ➔ Register Export ➔ Festival Bonus Run
       ↓
Employee Separation ➔ Gratuity Calculation ➔ Final Settlement
```

---

## Audit Use

Buyer audit বা labour inspection-এ register CSV export directly use করা যায়।
