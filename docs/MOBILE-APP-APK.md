# Norban Group Portal — Single APK (Employee + Rental Driver)

**Strategy:** Capacitor WebView — one Android app loading the live production web portals.  
**Live URL:** `https://portal.norbangroup.com`  
**Package ID:** `com.norbangroup.portal`  
**Laravel entry:** `/app`  
**Capacitor project:** `mobile-app/` (this repo)

---

## Executive summary

| Item | Decision |
|------|----------|
| App type | Single APK — Employee + Rental Driver |
| Technology | Capacitor + WebView (v1) |
| Backend API | **Not required** — existing web routes + session auth |
| Web change → APK | **Automatic** after server deploy (no Play Store update) |
| APK rebuild | Icon, splash, native plugins, Android SDK only |

---

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│  Norban Portal APK (Capacitor — mobile-app/)            │
│  Native splash → WebView → portal.norbangroup.com/app   │
└──────────────────────────┬──────────────────────────────┘
                           │
              ┌────────────┴────────────┐
              ▼                         ▼
     /employee/login              /rental/login
              │                         │
              └──────── Laravel ────────┘
                    order-portal
```

---

## Software required for APK build

Install these on the **developer PC** (Windows/macOS/Linux). Versions are minimum recommendations.

| Software | Purpose | Download |
|----------|---------|----------|
| **Node.js 18+** | Capacitor CLI, npm | https://nodejs.org |
| **npm** (with Node) | Package manager | Included with Node |
| **Android Studio** | SDK, emulator, signed AAB/APK | https://developer.android.com/studio |
| **Java JDK 17** | Android Gradle builds | Bundled with Android Studio, or Adoptium |
| **Git** | Clone repo | https://git-scm.com |

Optional:

| Software | Purpose |
|----------|---------|
| **Google Play Console** account ($25 one-time) | Publish to Play Store |
| **Real Android phone** + USB debugging | Best testing |

### Android Studio — first-time setup

1. Install **Android Studio** (default options).
2. Open Android Studio → **More Actions → SDK Manager**.
3. **SDK Platforms** tab → install **Android 14 (API 34)** or latest stable.
4. **SDK Tools** tab → ensure checked:
   - Android SDK Build-Tools
   - Android SDK Platform-Tools
   - Android Emulator
5. **Apply** → wait for download.

### Environment variables (Windows)

After SDK install, add (adjust username/path):

```
ANDROID_HOME=C:\Users\<YOU>\AppData\Local\Android\Sdk
```

Add to **Path**:

```
%ANDROID_HOME%\platform-tools
%ANDROID_HOME%\tools
```

Verify in PowerShell:

```powershell
node -v          # v18+
npm -v
java -version    # 17+
adb version
```

---

## Phase overview

| Phase | Name | Duration | Output |
|-------|------|----------|--------|
| **0** | Prerequisites | 1 day | HTTPS live site, portal accounts, icons |
| **1** | Laravel changes | Done in repo | `/app`, manifest, assetlinks template |
| **2** | Capacitor setup | 1–2 days | `mobile-app/` builds on device |
| **3** | WebView + routing | 1 day | Role picker → correct portal |
| **4** | Native plugins (optional) | 2–3 days | Push, GPS, camera if web fails |
| **5** | Testing | 2–3 days | Real devices |
| **6** | Signed APK / Play Store | 1–2 days | Release AAB |

---

## Phase 0 — Prerequisites checklist

### Production server

- [ ] `APP_URL=https://portal.norbangroup.com` (HTTPS)
- [ ] `php artisan migrate --force` up to date
- [ ] `public/build/` deployed after frontend changes
- [ ] `php artisan storage:link`
- [ ] VAPID keys for push (optional but recommended):
  ```env
  VAPID_PUBLIC_KEY=...
  VAPID_PRIVATE_KEY=...
  VAPID_SUBJECT=https://portal.norbangroup.com
  ```
  Generate: `npx web-push generate-vapid-keys`

### Portal accounts (production — no demo seed)

- [ ] Admin user (Admin → Users, or tinker)
- [ ] Employee with portal enabled (Employee ID + password)
- [ ] Rental driver with portal enabled (mobile + password)

### Branding

- [ ] `public/pwa/icon-192.png` and `icon-512.png` (Norban logo)
- [ ] Regenerate placeholders: `php scripts/generate-pwa-icons.php`

---

## Phase 1 — Laravel changes (in this repo)

| File | Purpose |
|------|---------|
| `GET /app` | Mobile landing — choose Employee or Rental |
| `public/app-manifest.webmanifest` | Unified PWA manifest for single app |
| `public/.well-known/assetlinks.json` | Android domain verification (fill SHA after keystore) |
| `resources/views/mobile/landing.blade.php` | Role chooser UI |
| Rental login | `@include('rental.partials.pwa-head')` added |
| `resources/js/portal-shell.js` | Detect native app; hide “Install app” banner |
| `routes/web.php` | Routes for `/app`, manifest, and assetlinks (Laravel + cPanel index.php setups) |

### URLs

| Portal | URL |
|--------|-----|
| **APK entry** | `https://portal.norbangroup.com/app` |
| Employee login | `/employee/login` |
| Rental login | `/rental/login` |
| Deep link | `/app?portal=employee` or `/app?portal=rental` |
| App manifest | `/app-manifest.webmanifest` |
| Asset links | `/.well-known/assetlinks.json` |

### Deploy Phase 1

```bash
git pull
php artisan route:cache
php artisan view:cache
npm run build   # if CSS/JS changed
```

---

## Phase 2 — Build APK (step by step)

All commands from **`mobile-app/`** folder unless noted.

### Step 1 — Install dependencies

```powershell
cd c:\wamp64\www\order-portal\mobile-app
npm install
```

### Step 2 — Configure production URL

Edit `mobile-app/capacitor.config.ts`:

```ts
server: {
  url: 'https://portal.norbangroup.com/app',
  cleartext: false,
}
```

For **local WAMP testing** (advanced): use your LAN IP only if HTTPS is configured; otherwise test against production URL.

### Step 3 — Add Android platform (first time only)

```powershell
npx cap add android
npx cap sync android
```

### Step 4 — Open in Android Studio

```powershell
npx cap open android
```

Wait for Gradle sync to finish.

### Step 5 — Debug APK on phone/emulator

1. Enable **Developer options** + **USB debugging** on phone.
2. Android Studio → device dropdown → select phone.
3. Click **Run** (green play).
4. App opens → `/app` → pick Employee or Rental → login.

Or build debug APK:

```powershell
cd android
.\gradlew assembleDebug
```

Output: `android/app/build/outputs/apk/debug/app-debug.apk`

Share this APK for internal testing (WhatsApp, etc.).

### Step 6 — Release signed AAB (Play Store)

1. Android Studio → **Build → Generate Signed App Bundle or APK**.
2. **Android App Bundle** → Next.
3. **Create new keystore** (save path + passwords securely — **cannot recover**).
4. Build **release** bundle.

Output: `android/app/release/app-release.aab`

5. Copy **SHA-256 certificate fingerprint** from signing report.
6. Update `public/.well-known/assetlinks.json` on server with that fingerprint.
7. Verify: https://portal.norbangroup.com/.well-known/assetlinks.json

### Step 7 — Play Store (optional)

1. https://play.google.com/console
2. Create app → **Norban Group Portal**
3. Upload AAB → Internal testing → add testers
4. Privacy policy URL required

---

## Phase 3 — User flow

```
Open APK
  → https://portal.norbangroup.com/app
  → [ Employee Portal ]  → /employee/login
  → [ Rental Driver   ]  → /rental/login
  → Login → dashboard
  → “Switch portal” on login pages when opened from app
```

---

## When to rebuild APK vs server deploy only

| Change | Server deploy | New APK |
|--------|:-------------:|:-------:|
| Employee details, HR data | ✅ | ❌ |
| New web page / UI / bug fix | ✅ | ❌ |
| CSS/JS (`npm run build` in Laravel) | ✅ | ❌ |
| App icon / splash / name | ❌ | ✅ |
| New Capacitor plugin | ❌ | ✅ |
| Android target SDK update | ❌ | ✅ |
| `assetlinks.json` package change | ❌ | ✅ |

---

## Known gaps & limitations

| Gap | Status | Notes |
|-----|--------|-------|
| Requires internet | By design | WebView loads live site |
| Offline mode | Not in v1 | Future: API + Flutter |
| Rental login PWA head | **Fixed** | Manifest + SW on login page |
| Install banner in APK | **Fixed** | Hidden in native shell |
| Switch portal from login | **Fixed** | Link back to `/app` |
| `assetlinks.json` SHA | **Template** | Fill after keystore created |
| Web Push in WebView | Test on device | May need FCM plugin later |
| GPS / camera / QR | Test on device | Capacitor plugins if blocked |
| iOS | Not in v1 | Same Capacitor project can add iOS later |

---

## Troubleshooting

### Blank white screen

- Check phone has internet.
- Open `https://portal.norbangroup.com/app` in Chrome on same phone.
- Verify SSL certificate valid.
- Check `capacitor.config.ts` `server.url`.

### Login session lost immediately

- Ensure `APP_URL` matches domain exactly.
- Check `SESSION_SECURE_COOKIE=true` in production `.env`.
- `SESSION_SAME_SITE=lax` (default).

### assetlinks.json not verified

- File must be public at `/.well-known/assetlinks.json`
- Correct SHA-256 from **release** keystore
- Wait up to 24h for Google cache

### Gradle / Java errors

- Use JDK 17 in Android Studio: **Settings → Build → Gradle → JDK 17**.

---

## Future v2 (optional)

If WebView limits are hit:

1. Laravel Sanctum REST API
2. Flutter app
3. FCM native push

See team discussion — v1 WebView is the recommended starting point.

---

## Quick command reference

```powershell
# Laravel (project root)
npm run build
php artisan test --filter=MobileAppLandingTest

# Capacitor (mobile-app/)
npm install
npx cap sync android
npx cap open android
cd android; .\gradlew assembleDebug
```

---

*Last updated: July 2026 — Norban Group order-portal mobile app guide.*
