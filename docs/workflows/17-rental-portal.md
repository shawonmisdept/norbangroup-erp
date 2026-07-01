# Rental Driver Portal (এক নজরে)

**Route:** `/rental`  
**Auth:** Separate login (`auth:rental_driver`) — TMS rental vendor driver

---

## মূল কাজ

Third-party **rental vendor driver**-এর assigned TMS trip execute করা — company employee driver-এর মতো, কিন্তু আলাদা portal।

---

## Setup (Admin)

```
Admin → TMS → Rental Drivers → Create driver + Portal account
```

→ [14-tms.md](14-tms.md)

---

## Driver Workflow

| ধাপ | কাজ |
|-----|-----|
| **Login** | `/rental/login` |
| **Dashboard** | Profile, default vehicle, active trip alert |
| **Trip Assigned** | Admin approve করলে notification |
| **Start Trip** | Passenger list দেখে trip start |
| **End Trip** | Destination-এ trip end |
| **Daily KM** | Morning/evening odometer (rental billing-এ use) |

---

## Sub-modules

| Key | Route | Label |
|-----|-------|-------|
| `dashboard` | `/rental/dashboard` | Home |
| `trips` | `/rental/trips` | Assigned Trips |
| `odometer` | `/rental/odometer` | Daily KM |
| `notifications` | `/rental/notifications` | Alerts |

---

## 🔄 Flowchart

```
Admin Assign (Approved) ➔ Rental Driver Notify ➔ Start ➔ End ➔ Rental Charge (Admin)
```

---

## vs Company Driver

| | Company Driver | Rental Driver |
|--|----------------|---------------|
| Login | Employee Portal | Rental Portal |
| Identity | HRM Employee | Vendor driver record |
| OT Calc | Yes (company policy) | Rental billing model |
