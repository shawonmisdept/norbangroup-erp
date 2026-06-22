# Deploy Guide

**Repo:** https://github.com/shawonmisdept/norbangroup-erp  
**Live:** https://portal.norbangroup.com  
**Server path:** `~/portal.norbangroup.com`

---

## Local → Git

```powershell
cd c:\wamp64\www\order-portal
git status
npm run build          # only if CSS/JS changed
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
