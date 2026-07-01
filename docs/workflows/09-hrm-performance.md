# HRM — Performance (এক নজরে)

**Route:** `/admin/hrm/performance` · `/employee/performance`  
**Permission:** `hrm.performance.view/manage/rate/approve`, `hrm.performance.bonus.*`, `hrm.performance.increment.*`

---

## মূল কাজ

Employee performance review cycle — rating, approval, score-based **festival bonus** ও **annual increment** run।

---

## ১. HR Admin

| ধাপ | কাজ |
|-----|-----|
| **Open Cycle** | Probation / mid-year / annual review batch |
| **Template** | Scoring criteria & weight setup |
| **Monitor** | Pending ratings track |
| **Approve** | Final review approval |
| **Bonus Run** | Score → bonus % → disburse list |
| **Increment Run** | Score → increment % → salary update |

---

## ২. Rater (Reporting Person)

| ধাপ | কাজ |
|-----|-----|
| **Rate** | Assigned employee review score submit |

---

## ৩. Employee (Portal)

| ধাপ | কাজ |
|-----|-----|
| **View** | Own review results (approved) |

---

## Sub-modules

| Key | Label | কাজ |
|-----|-------|-----|
| `dashboard` | Dashboard | Pending ratings, open cycles |
| `cycles` | Review Cycles | Open/close review batches |
| `templates` | Score Templates | Hybrid criteria configuration |
| `reviews` | Reviews | Rate, approve, track |
| `bonus-bands` | Bonus Bands | Score-to-bonus % mapping |
| `bonus-runs` | Performance Bonus | Mid-year bonus calculation |
| `increment-bands` | Increment Bands | Score-to-increment % |
| `increment-runs` | Annual Increment | Salary revision from reviews |

---

## 🔄 Flowchart

```
Open Cycle ➔ Rater Scores ➔ HR Approves ➔ Bonus Run / Increment Run ➔ Payroll Update
```

---

## Link to Salary

Increment run → [10-hrm-salary.md](10-hrm-salary.md) employee salary structure update।
