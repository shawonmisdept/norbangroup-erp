# TMS — Transport Management (এক নজরে)

**Route:** `/admin/tms` · `/employee/transport` · `/rental`  
**Permission:** `tms.*` (admin) · Employee Portal (request) · Rental login (vendor driver)

---

## মূল কাজ

পুরো Transport Management System মূলত **৩টি ধাপে** কাজ করে — Employee request, Admin approve/assign, Driver trip execute।

---

## ১. Employee (কর্মী)

**Platform:** Employee Portal → Transport

| ধাপ | কাজ |
|-----|-----|
| **Request** | কোথায় যাবে, কখন যাবে, কতজন যাবে — portal থেকে submit (স্ট্যাটাস: **Pending**) |
| **Edit/Cancel** | Pending থাকলে edit বা cancel |
| **Tracking** | Admin approve করলে driver + vehicle details দেখে |
| **Cancel** | Trip start হওয়ার আগে approved request cancel |

---

## ২. Admin (অ্যাডমিন)

**Platform:** Admin Panel → Transport

| ধাপ | কাজ |
|-----|-----|
| **Approval** | Pending request review |
| **Assign** | Vehicle + Driver select করে approve (স্ট্যাটাস: **Approved**) |
| **Merge** | একই route/date-এর multiple request এক trip-এ merge |
| **Reject** | Reason সহ reject |
| **Monitor** | Active trip, reassign, cancel/abort |
| **Post-Trip** | Fuel entry, OT mark paid, rental charge, reports |

**Setup (one-time):** Settings, destinations, vehicles, company drivers, rental vendors/drivers

---

## ৩. Driver (চালক)

### Company Driver (In-house)
**Platform:** Employee Portal → My Trips + Daily KM

| ধাপ | কাজ |
|-----|-----|
| **Notify** | Trip assign notification |
| **Start** | যাত্রী নিয়ে trip start + start KM (স্ট্যাটাস: **In Progress**) |
| **End** | Destination-এ end KM entry → trip complete (স্ট্যাটাস: **Completed**) |
| **Daily KM** | Morning/evening odometer (optional) |

### Rental Driver (Vendor)
**Platform:** `/rental/login` — same start/end logic, separate login

→ বিস্তারিত: [17-rental-portal.md](17-rental-portal.md)

---

## Sub-modules (Admin)

| Group | Key | Label |
|-------|-----|-------|
| Operations | `requests` | Transport Requests |
| Operations | `trips` | Trip Logs |
| Operations | `odometer` | Daily KM |
| Operations | `fuel` | Fuel Logs |
| Vehicle Mgmt | `vehicles` | Vehicles |
| Vehicle Mgmt | `drivers` | Company Drivers |
| Vehicle Mgmt | `rental_vendors` | Rental Vendors |
| Vehicle Mgmt | `rental_drivers` | Rental Drivers |
| Vehicle Mgmt | `maintenance` | Maintenance Bills |
| Vehicle Mgmt | `maintenance_parts` | Parts Catalog |
| Vehicle Mgmt | `maintenance_posting` | Bill For Posting |
| Vehicle Mgmt | `rental_charges` | Rental KM Charges |
| Setup | `settings` | TMS Settings |
| Setup | `destinations` | Destinations |
| Setup | `gps_tracking` | GPS Tracking (Phase 2) |
| — | `dashboard` | TMS Dashboard |
| — | `reports` | Fleet & Cost Reports |

---

## 🔄 Flowchart

```
Request (Pending) ➔ Admin Approval (Approved) ➔ Driver Start (In Progress) ➔ Trip End (Completed)
       ↓                    ↓
   Rejected/Cancelled    Merge multiple requests
```

---

## Post-Trip

Trip complete হলে system **automatically driver OT calculate** করে। Admin পরবর্তীতে:
- Fuel cost entry
- OT payment mark
- Rental charge reconcile
- Fleet cost reports

---

## Full Spec

[transport-management-system-prd.md](../../transport-management-system-prd.md) · [TMS-PHASE2.md](../../TMS-PHASE2.md)
