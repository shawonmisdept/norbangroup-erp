# HRM — Salary / Payroll (এক নজরে)

**Route:** `/admin/hrm/salary` · `/employee/payslips`  
**Permission:** `hrm.salary.view`, `hrm.salary.manage`, `hrm.salary.approve`

---

## মূল কাজ

Salary structure define → month-end attendance/leave থেকে **payroll calculate** → close → bank export → employee payslip।

---

## ১. HR / Accounts Admin

| ধাপ | কাজ |
|-----|-----|
| **Structure** | Salary heads, grades, grade details setup |
| **Assign** | Employee-কে grade/structure assign |
| **Process** | Month-end payroll run (attendance + deductions) |
| **Review** | Payroll items verify |
| **Close** | Period lock + bank advise export |
| **Increment** | Rule/bulk/CSV increment apply |

---

## ২. Employee (Portal)

| ধাপ | কাজ |
|-----|-----|
| **Payslip** | Closed period payslip view/print |
| **Notify** | Payslip ready notification |

---

## Sub-modules

| Group | Key | Label | কাজ |
|-------|-----|-------|-----|
| Structure | `heads` | Head | Basic, HRA, deductions |
| Structure | `grades` | Grade | G1, Staff, Worker |
| Structure | `grade-details` | Grade Details | Amount per head per grade |
| Structure | `employee-salary` | Employee Salary | Assign to employee |
| Payroll | `upload` | Upload Salary | Bulk salary upload |
| Payroll | `process` | Salary Process | Month-end calculation |
| Payroll | `close` | Salary Close | Freeze, lock, bank file |
| Increment | `increment-rules` | Increment Rule | Auto increment by tenure |
| Increment | `increment-bulk` | Increment Bulk | Bulk by filter |
| Increment | `increment-upload` | Increment Upload | CSV upload |
| — | `dashboard` | Dashboard | Setup coverage, period status |

---

## 🔄 Flowchart

```
Heads + Grades ➔ Employee Assign ➔ Monthly Process ➔ Review ➔ Close ➔ Payslip + Bank File
```

---

## Inputs

- **Attendance** period close → [07-hrm-attendance.md](07-hrm-attendance.md)
- **Leave** approved days → [08-hrm-leave.md](08-hrm-leave.md)
- **Finance** loan EMI, TDS, PF → [12-hrm-finance.md](12-hrm-finance.md)
- **RMG** production incentive, salary hold → [13-hrm-rmg.md](13-hrm-rmg.md)
