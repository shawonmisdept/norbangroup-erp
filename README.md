# Norbangroup ERP Portal

Laravel-based commercial ERP portal for **Norbangroup** — public requirement submission, admin dashboard, master data, roles, and app settings.

**Stack:** Laravel 13 · Tailwind CSS v4 · Alpine.js · MySQL

---

## Local development

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run dev
```

Default admin: `admin@norbangroup.com` / `password`

---

## Shared hosting deployment (from Git)

### 1. Clone on server

In cPanel → **Git Version Control** (or SSH):

```bash
git clone https://github.com/shawonmtomis/norbangroup-erp.git
cd norbangroup-erp
```

### 2. Point domain to `/public`

Set the document root to the **`public`** folder inside the project — not the project root.

Example cPanel path: `~/norbangroup-erp/public`

### 3. Environment file

```bash
cp .env.example .env
```

Edit `.env` on the server:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
```

Generate app key (SSH or cPanel Terminal):

```bash
php artisan key:generate
```

### 4. Install PHP dependencies

Requires PHP **8.2+** and Composer (cPanel Terminal or local upload):

```bash
composer install --no-dev --optimize-autoloader
```

### 5. Database & storage

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

Ensure these folders are writable: `storage/`, `bootstrap/cache/`

### 6. Assets

Built frontend assets are included in `public/build/`. No Node.js required on the server.

After local CSS/JS changes, run `npm run build` locally and push to Git.

### 7. Post-deploy optimization

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 8. Updating from Git

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Important

- Never commit `.env` — it contains secrets.
- Upload logos and user files live in `storage/app/public/` on the server.
- For Gmail mail, configure SMTP in **Admin → App Settings**.

---

© Norban Group — A Product of Data State Ltd
