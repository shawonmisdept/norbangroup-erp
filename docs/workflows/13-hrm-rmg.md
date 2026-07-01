# HRM — RMG Extras (এক নজরে)

**Route:** `/admin/hrm/rmg`  
**Permission:** `hrm.rmg.view`, `hrm.rmg.manage` (+ feature-wise permissions)

---

## মূল কাজ

Garments factory-specific HR operations — **gate pass, line transfer, production incentive, buyer audit**, welfare records।

---

## Workflow Areas

### Movement & Gate
| Key | Label | কাজ |
|-----|-------|-----|
| `worker-transfer` | Worker Transfer | Cross-line/unit transfer with approval |
| `osd-movement` | OSD / Movement | Official duty, buyer visit, training |
| `gate-pass` | Gate Pass | Employee gate-out with HR approval |
| `sub-contract` | Sub-contract Workers | Agency manpower register |

### Planning & Compliance
| Key | Label | কাজ |
|-----|-------|-----|
| `manpower-planning` | Manpower Planning | Line headcount plan vs actual |
| `proxy-punch` | Proxy Punch Flags | Suspicious biometric flag review |
| `buyer-holiday` | Buyer Holidays | Buyer-specific holiday calendar |
| `buyer-audit-export` | Buyer Audit Export | Attendance & wage pack for audits |

### Welfare & Training
| Key | Label | কাজ |
|-----|-------|-----|
| `canteen` | Canteen Deductions | Monthly meal charge |
| `medical` | Medical Visits | Factory clinic visit log |
| `training` | Training Records | Safety, fire, compliance training |

### Payroll RMG
| Key | Label | কাজ |
|-----|-------|-----|
| `production-incentive` | Production Incentive | Line output incentive calc & approve |
| `salary-hold` | Salary Hold | Block payroll during investigation |
| `cash-list` | Cash Payment List | Cash worker net-pay export by line |

---

## 🔄 Flowchart

```
Daily Floor Ops (Gate Pass/Transfer) ➔ Production Incentive ➔ Payroll
       ↓
Buyer Audit Visit ➔ Export Register Pack
```

---

## Payroll Link

Production incentive + canteen deduction + salary hold → [10-hrm-salary.md](10-hrm-salary.md)
