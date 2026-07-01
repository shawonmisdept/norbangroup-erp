# Commercial ERP — Requirements (এক নজরে)

**Route:** `/` (public) · `/admin/requirements` (admin)  
**Permission:** `orders.view`, `orders.update`, `orders.download`

---

## মূল কাজ

Buyer/client-এর garment requirement digitalভাবে receive করা এবং commercial team-এর review/update করা — WhatsApp/email-এর বদলে structured system।

---

## ১. Public (Buyer / Client)

| ধাপ | কাজ |
|-----|-----|
| **Submit** | Contact info, item name, quantity, notes + techpack/artwork upload |
| **Confirm** | Reference code সহ success page |

**স্ট্যাটাস:** System-এ নতুন requirement record তৈরি → Admin-কে notify

---

## ২. Admin (Commercial Team)

| ধাপ | কাজ |
|-----|-----|
| **Dashboard** | সব submission list — filter by status, search |
| **Review** | Detail দেখা, file preview/download |
| **Update** | Status ও workflow fields update (Quoted, In Production, Approved ইত্যাদি) |
| **Delete** | প্রয়োজনে record remove (`orders.delete`) |

---

## Sub-modules

| Sub-module | Route | কাজ |
|------------|-------|-----|
| Public Form | `/` | Requirement submit |
| Success Page | `/success` | Reference confirmation |
| Requirements List | `/admin/requirements` | Dashboard + filter |
| Detail | `/admin/requirements/{id}` | Full view + files |
| Workflow Update | POST workflow | Status change |

---

## 🔄 Flowchart

```
Buyer Submit (Public) ➔ Pending/New ➔ Admin Review ➔ Status Update ➔ Closed
                              ↓
                        File Download/Preview
```

---

## Notification

- নতুন requirement submit → Admin users (`orders.view`) bell notification
