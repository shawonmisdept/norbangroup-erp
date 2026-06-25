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
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Production queue worker (5000+ employees)

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
