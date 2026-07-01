# Careers — Public Recruitment (এক নজরে)

**Route:** `/careers`  
**Auth:** Public (no login) — OTP verification on apply

---

## মূল কাজ

Public job board — candidate online apply, track status, offer respond। Admin side → [06-hrm-recruitment.md](06-hrm-recruitment.md)

---

## ১. Candidate (Public)

| ধাপ | কাজ |
|-----|-----|
| **Browse** | Published job listings দেখা |
| **Detail** | Job requirements, shift, location |
| **Apply** | Application form + **OTP verify** (mobile) |
| **Track** | Reference number দিয়ে status check |
| **Offer** | Offer letter accept/decline online |

---

## Sub-modules

| Key | Route | Label |
|-----|-------|-------|
| `index` | `/careers` | Job Listings |
| `show` | `/careers/{slug}` | Job Detail |
| `apply` | POST apply | Online Application + OTP |
| `track` | `/careers/track` | Application Status |
| `offer-response` | Offer link | Accept/Decline Offer |

---

## 🔄 Flowchart

```
HR Publish Job (/careers) ➔ Candidate Apply (OTP) ➔ HR Pipeline (Admin)
       ↓
Interview ➔ Offer ➔ Accept/Decline ➔ Convert to Employee
```

---

## Settings Link

Recruitment OTP/SMS → Admin App Settings → Recruitment section

---

## Admin Side

Full hiring pipeline → [06-hrm-recruitment.md](06-hrm-recruitment.md)
