# HRM — Finance (এক নজরে)

**Route:** `/admin/hrm/finance` · `/employee/loans` · `/employee/pf`  
**Permission:** `hrm.finance.view`, `hrm.finance.manage`, `hrm.finance.settlement.*`

---

## মূল কাজ

Employee-level **TDS, PF, loan/advance** manage এবং separation-এ **Full & Final (F&F) settlement**।

---

## ১. Finance Admin

| ধাপ | কাজ |
|-----|-----|
| **Tax** | Assessment year slabs, employee TDS ledger |
| **PF** | PF account, monthly contribution process |
| **Loans** | Advance/loan approve, EMI schedule |
| **Bulk Advance** | Festival advance many employees-এ disburse |
| **F&F** | Separation-এ gratuity + PF + loan + leave encashment calculate |

---

## ২. Employee (Portal)

| ধাপ | কাজ |
|-----|-----|
| **Loan Apply** | Salary advance/emergency loan request |
| **PF View** | PF balance & contribution history |

---

## Sub-modules

| Group | Key | Label | কাজ |
|-------|-----|-------|-----|
| Statutory | `tax` | Income Tax (TDS) | Slabs & employee ledger |
| Statutory | `pf` | Provident Fund | Accounts & contributions |
| Statutory | `pf-report` | PF Employer Report | Monthly employer report + CSV |
| Statutory | `loans` | Loans & Advances | Apply, approve, EMI recovery |
| Statutory | `advance-bulk` | Bulk Festival Advance | Mass advance disburse |
| Exit | `final-settlement` | Final Settlement (F&F) | Exit dues consolidation |
| — | `dashboard` | Dashboard | Loans, TDS, PF, F&F snapshot |

---

## 🔄 Flowchart

```
Loan Apply ➔ Approve ➔ EMI in Payroll
       ↓
PF Monthly Contribution ➔ TDS Deduction
       ↓
Separation ➔ F&F Calculate ➔ Approve ➔ Pay
```

---

## Payroll Link

Loan EMI, TDS, PF → [10-hrm-salary.md](10-hrm-salary.md) salary process-এ auto deduct।
