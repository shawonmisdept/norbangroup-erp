# Master Data — ERP (এক নজরে)

**Route:** `/admin/masters`  
**Permission:** `masters.view`, `masters.manage` (বা module-wise `masters.{key}.*`)

---

## মূল কাজ

Commercial ERP ও reporting-এ ব্যবহৃত **reference/lookup data** maintain করা — factory, buyer, fabric, order status ইত্যাদি।

---

## Workflow

```
Admin Setup → CRUD Master Records → Used by Requirements & Reports
```

| ধাপ | কাজ |
|-----|-----|
| **View** | Module list থেকে master table browse |
| **Create/Edit** | নতুন entry add বা existing update |
| **Deactivate/Delete** | অপ্রয়োজনীয় entry remove |

---

## Sub-modules (Groups)

### Organization
| Key | Label |
|-----|-------|
| `factories` | Factories / Units |
| `departments` | Departments |
| `designations` | Designations |
| `company-calendars` | Company Calendars |

### Commercial
| Key | Label |
|-----|-------|
| `buyers` | Buyers |
| `brands` | Brands |
| `seasons` | Seasons |
| `classes` | Buyer Classes |

### Product
| Key | Label |
|-----|-------|
| `items` | Items |
| `colors` | Colors |
| `sizes` | Sizes |
| `accessories-items` | Accessories Items |
| `item-body-parts` | Item Body Parts |

### Material & Fabric
| Key | Label |
|-----|-------|
| `material-types` | Material Types |
| `materials` | Materials |
| `fabric-categories` | Fabric Categories |
| `fabric-types` | Fabric Types |
| `fabrications` | Fabrications |
| `compositions` | Compositions |
| `gsms` | GSM |
| `sustainabilities` | Sustainabilities |

### Order & Shipment
| Key | Label |
|-----|-------|
| `order-types` | Order Types |
| `shipment-modes` | Shipment Modes |
| `shipout-conditions` | Shipout Conditions |
| `shipment-statuses` | Shipment Statuses |

### Production & Status
| Key | Label |
|-----|-------|
| `order-statuses` | Order Statuses |
| `short-order-statuses` | Short Order Statuses |
| `price-statuses` | Price Statuses |
| `yarn-statuses` | Yarn Statuses |
| `woven-statuses` | Woven Statuses |
| `trims-statuses` | Trims Statuses |
| `accessories-statuses` | Accessories Statuses |
| `sample-statuses` | Sample Statuses |
| `garment-production-statuses` | Garment Production Statuses |
| `payment-statuses` | Payment Statuses |

### Finance & Supplier & Sample
| Key | Label |
|-----|-------|
| `banks` | Banks |
| `supplier-types` | Supplier Types |
| `suppliers` | Suppliers |
| `sample-types` | Sample Types |

---

## 🔄 Flowchart

```
Master Setup (One-time) ➔ Daily Maintenance ➔ Used across ERP modules
```

**Note:** HRM-specific masters আলাদা → [04-hrm-masters.md](04-hrm-masters.md)
