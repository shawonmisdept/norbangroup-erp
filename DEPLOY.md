# Deploy Guide

**Repo:** https://github.com/shawonmisdept/norbangroup-erp  
**Live:** https://portal.norbangroup.com  
**Server path:** `~/portal.norbangroup.com`

---

## Pre-deploy checklist (local)

```powershell
cd c:\wamp64\www\order-portal
php artisan test
npm run build          # required after CSS/JS changes
git status
git add .
git commit -m "Your message"
git push origin main
```

---

## Git → cPanel

```bash
cd ~/portal.norbangroup.com
git pull origin main
```

**After pull:**

```bash
# always
php artisan route:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# if database changed
php artisan migrate --force

# if composer.json changed
php composer.phar install --no-dev --optimize-autoloader
```

**Full deploy (safe default):**

```bash
cd ~/portal.norbangroup.com
git pull origin main
php composer.phar install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan route:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Production — Fresh database + full seed

> **Warning:** This **deletes all portal data** (orders, employees, vehicles, bills, etc.).  
> Production is already running — take a **full backup** before running.  
> `.env`, uploaded files, and App Settings are **not** restored by seed.

### Step 0 — Backup (required)

1. **cPanel → Backup** or **phpMyAdmin → Export** — save the full database as `.sql`
2. Copy `storage/app/public/` if you have uploads (logos, documents)
3. Note current **App Settings** (mail, SMS, logos) — re-enter after seed if needed

### Step 1 — Latest code on server

```bash
cd ~/portal.norbangroup.com
git pull origin main
php composer.phar install --no-dev --optimize-autoloader
```

Confirm seed data files exist:

- `database/seeders/data/tms_vehicles.php`
- `database/seeders/data/tms_maintenance.php`

### Step 2 — Fresh migrate + main seed

```bash
php artisan migrate:fresh --seed --force
```

This seeds:

| Seeder | Data |
|--------|------|
| `AppSettingSeeder` | Default app settings |
| `RolePermissionSeeder` | Roles & permissions |
| `KbSeeder` | Knowledge base |
| Demo users | 6 login accounts (see below) |
| `MasterDataSeeder` | Factories (**NCL**, **HAL**, Head Office, …), HRM masters, employees, salary, order/garment masters |

### Step 3 — TMS seed (required — not in DatabaseSeeder)

Run in order — vehicles first, then company drivers, then maintenance:

```bash
php artisan db:seed --class=Database\\Seeders\\Tms\\VehicleSeeder --force
php artisan db:seed --class=Database\\Seeders\\Tms\\CompanyDriverSeeder --force
php artisan db:seed --class=Database\\Seeders\\Tms\\MaintenanceSeeder --force
```

Expected output:

- **51** vehicles
- **13** company drivers linked to **18** vehicles
- **~2488** maintenance bills (MaintenanceSeeder reports created/updated/pruned counts)

### Step 4 — Rebuild cache

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 5 — Verify

```bash
php database/seeders/scripts/check_db_seed_state.php
php database/seeders/scripts/audit_tms_seed_integrity.php
```

| Check | Expected |
|-------|----------|
| Vehicles | 51 |
| Company drivers | 13 |
| Driver ↔ vehicle links | 18 |
| Maintenance bills | 2400+ |
| `DM-GA-30-0062`, `DM-KHA-23-5772`, `DM-U-4801` | vehicle=yes, bills > 0 |
| Orphan maintenance | 0 |

### Step 6 — Post-seed (manual)

- **Admin → App Settings** — mail, SMS, logos, notifications
- **Admin → Roles** — confirm HRM/TMS permissions
- **HRM → Biometric Devices** — device IP/serial for each factory
- **Queue worker / cron** — see [Production queue worker](#production-queue-worker-5000-employees) above

### Default logins (after seed)

| Role | Email | Password |
|------|-------|----------|
| Administrator | `admin@norbangroup.com` | `password` |
| Management | `mansifsiddiqui@gmail.com` | `password` |
| Transport Authority | `transport@test.com` | `password` |
| HR Manager | `hr-manager@test.com` | `password` |

Change passwords after first login on production.

### One-shot copy-paste (after backup)

```bash
cd ~/portal.norbangroup.com
git pull origin main
php composer.phar install --no-dev --optimize-autoloader
php artisan migrate:fresh --seed --force
php artisan db:seed --class=Database\\Seeders\\Tms\\VehicleSeeder --force
php artisan db:seed --class=Database\\Seeders\\Tms\\CompanyDriverSeeder --force
php artisan db:seed --class=Database\\Seeders\\Tms\\MaintenanceSeeder --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php database/seeders/scripts/check_db_seed_state.php
```

### Common mistakes

1. Skipping **TMS seeders** — portal works but no vehicles/maintenance
2. Running **MaintenanceSeeder before VehicleSeeder** — bills skipped
3. **`migrate:fresh` without backup** — all live data lost
4. Forgetting **App Settings** — mail/notifications stop working

### Factory rename only (NCL / HAL — no full reseed)

If factories still show **Norban Comtex Limited** or **Hornbill Apparel Ltd** after deploy:

```bash
cd ~/portal.norbangroup.com
git pull origin main
php artisan migrate --force
php artisan config:cache
```

Or run the seeder alone (preserves factory IDs and linked data):

```bash
php artisan db:seed --class=Database\\Seeders\\Masters\\FactorySeeder --force
```

Then refresh **Admin → Master Data → Factories** — names should be **NCL** and **HAL**.

---

In server `.env`:

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

Run a persistent worker (Supervisor or cPanel cron every minute):

```bash
php artisan queue:work redis --queue=hrm-sync,hrm-attendance,hrm-payroll,hrm-mail --tries=3 --timeout=120
```

For shared hosting without Redis, keep `QUEUE_CONNECTION=database` and add a cron:

```bash
* * * * * cd ~/portal.norbangroup.com && php artisan queue:work database --stop-when-empty --max-time=55
```

---

## Mail (requirements + payslip emails)

Configure in **Admin → App Settings** or `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

---

## Biometric sync monitoring

- **Admin → HRM → Attendance → Sync** — device status dashboard
- **Sync Failures** — retry failed ADMS pulls (`/admin/hrm/attendance/sync/failures`)
- Ensure device serial matches **Masters → Biometric Devices**

---

## Rules

- Never commit `.env`
- Run `npm run build` locally before push (server has no Node)
- Edit `.env` separately on server when needed

---

## Local dev

```powershell
php artisan serve
npm run dev
```
