# HRM — Leave (এক নজরে)

**Route:** `/admin/hrm/leave` · `/employee/leave`  
**Permission:** `hrm.leave.view`, `hrm.leave.manage`, `hrm.leave.approve`

---

## মূল কাজ

Leave policy setup, balance management, employee application, approval, এবং monthly accrual — attendance/payroll-এর সাথে linked।

---

## ১. Employee (Portal)

| ধাপ | কাজ |
|-----|-----|
| **Apply** | Leave type, date range, reason submit |
| **Track** | Application status দেখা |
| **Cancel** | Pending application cancel |

---

## ২. Approver (Portal/Admin)

| ধাপ | কাজ |
|-----|-----|
| **Review** | Team-এর pending leave list |
| **Approve/Reject** | Decision + comment |

---

## ৩. HR Admin

| ধাপ | কাজ |
|-----|-----|
| **Setup** | Policy, rules, maternity rules configure |
| **Balance** | Opening balance, manual adjustment |
| **Allocation Run** | Monthly/yearly accrual process |
| **Bulk Entry** | CSV/Excel bulk leave entry |

---

## Sub-modules

| Group | Key | Label | কাজ |
|-------|-----|-------|-----|
| Setup | `policies` | Leave Policies | Entitlement per leave type |
| Setup | `rules` | Leave Rules | Eligibility by category/tenure |
| Setup | `maternity-rules` | Maternity Rules | Maternity duration & pay |
| Balance | `opening-balances` | Opening Balance | Year-start balance |
| Balance | `transactions` | Leave Transaction | Applications & adjustments |
| Balance | `maternity-transactions` | Maternity Transaction | Maternity benefit cases |
| Process | `allocation` | Allocation Process | Accrual run |
| Process | `bulk-entry` | Leave Entry Bulk | Bulk CSV entry |
| — | `dashboard` | Dashboard | Pending approvals, on-leave today |

---

## 🔄 Flowchart

```
Policy Setup ➔ Employee Apply (Pending) ➔ Approve/Reject
       ↓
Balance Update ➔ Attendance Mark (Leave) ➔ Payroll Deduction/Encashment
```

---

## Maternity

Maternity leave → separate rules + transaction → benefit calculation service।
