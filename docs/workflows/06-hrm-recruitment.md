# HRM — Recruitment (এক নজরে)

**Route:** `/admin/hrm/recruitment` · `/careers` (public)  
**Permission:** `hrm.recruitment.postings.*`, `hrm.recruitment.applications.*`

---

## মূল কাজ

Internal hiring pipeline — job publish, candidate apply, interview, offer, employee conversion।

---

## ১. HR Admin

| ধাপ | কাজ |
|-----|-----|
| **Create Posting** | Job title, department, requirements লিখে draft |
| **Approve/Publish** | Careers site-এ live করা |
| **Pipeline** | Application screening → interview → offer |
| **Convert** | Selected candidate → Employee enrollment |

---

## ২. Candidate (Public — Careers)

| ধাপ | কাজ |
|-----|-----|
| **Browse** | `/careers` — published jobs |
| **Apply** | Online form + OTP verify |
| **Track** | Application status by reference |
| **Offer** | Accept/decline offer letter online |

→ বিস্তারিত: [18-careers.md](18-careers.md)

---

## Sub-modules

| Key | Label | কাজ |
|-----|-------|-----|
| `dashboard` | Dashboard | Pipeline stats, interviews, conversion |
| `postings` | Job Postings | Create, approve, publish on careers |
| `applications` | Applications | Candidate pipeline, interview, offer |

---

## 🔄 Flowchart

```
Job Posting (Draft) ➔ Publish (/careers) ➔ Candidate Apply (OTP)
       ↓
Screening ➔ Interview ➔ Offer ➔ Convert to Employee
```

---

## Templates

Job posting-এ ready templates আছে (Sewing Operator, Helper, QC, Supervisor) — দ্রুত publish করার জন্য।
