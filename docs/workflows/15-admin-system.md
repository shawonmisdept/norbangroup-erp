# System Admin (এক নজরে)

**Route:** `/admin/users` · `/admin/roles` · `/admin/settings`  
**Permission:** `users.manage`, `roles.manage`, `settings.manage`

---

## মূল কাজ

Portal **users, roles, permissions** এবং **system settings** (mail, SMS, WhatsApp, logos, notifications) manage করা।

---

## Sub-modules

| Key | Route | Label | কাজ |
|-----|-------|-------|-----|
| `users` | `/admin/users` | Users | Admin portal user accounts create/edit |
| `roles` | `/admin/roles` | Roles & Permissions | Custom role + module permission assign |
| `settings` | `/admin/settings` | App Settings | Mail, logos, OTP/SMS, WhatsApp gateway, notification toggles |
| `profile` | `/admin/profile` | My Profile | Logged-in user profile |
| `notifications` | `/admin/notifications` | Notifications | In-app notification center |

---

## Workflow

### Users
```
Create User → Assign Role → Assign Factory (optional) → User logs in with scoped access
```

### Roles
```
Create Role → Select Permissions (orders/hrm/tms/masters) → Assign to Users
```

### Settings
```
Configure Mail/SMS/WhatsApp → Enable module notifications → Runtime behavior update
```

---

## Permission Namespaces (Role-এ assign)

| Prefix | Module |
|--------|--------|
| `orders.*` | Commercial requirements |
| `masters.*` | ERP master data |
| `hrm.*` | All HRM hubs |
| `tms.*` | Transport management |

---

## 🔄 Flowchart

```
Super Admin Setup ➔ Roles + Permissions ➔ Users ➔ Module Access Enforced
```

---

## Head Office

Head Office role permissions → `config/head_office_permissions.php` (department × designation matrix)
