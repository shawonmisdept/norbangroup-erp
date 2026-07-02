# HRM Biometric Device Setup — SpeedFace V5L (ZKTeco ADMS)

Production portal: **https://portal.norbangroup.com**

This guide matches the device menus shown on your SpeedFace unit (Ethernet, Cloud Server, PC Connection).

---

## 1. Ethernet (do this first)

**Menu → COMM. → Ethernet**

| Field | Your device now | Set to |
|-------|-----------------|--------|
| IP Address | `192.168.1.201` | Keep if free on LAN, or enable DHCP |
| Subnet Mask | `255.255.255.0` | Usually correct |
| **Gateway** | `0.0.0.0` ❌ | Router IP, e.g. `192.168.1.1` |
| **DNS** | `0.0.0.0` ❌ | `8.8.8.8` or office DNS |
| DHCP | Off | Optional — turn ON if IT uses DHCP |

**Why:** Without gateway and DNS the device cannot reach the internet or `portal.norbangroup.com`.

---

## 2. Cloud Server (ADMS push)

**Menu → COMM. → Cloud Server Setting**

| Field | Your device now | Production value |
|-------|-----------------|------------------|
| Server Mode | ADMS | ADMS |
| Enable Domain Name | OFF | **ON** |
| **Server Address** | `0.0.0.0` ❌ | `portal.norbangroup.com` |
| **Server Port** | `8081` | **443** (HTTPS) |
| Enable Proxy Server | OFF | OFF unless IT requires proxy |

Device pushes attendance to:

```
https://portal.norbangroup.com/iclock/cdata
```

When connected, a **cloud icon** appears on the device standby screen.

---

## 3. PC Connection (local LAN only)

**Menu → COMM. → PC Connection**

| Field | Value | Notes |
|-------|-------|-------|
| Device ID | `1` | Must match Biometric Devices master if used |
| TCP COMM.Port | `4370` | For ZKTeco desktop software on same network |
| Comm Key | (set) | Local software password — not ADMS cloud |

This is **not** the cloud endpoint. ADMS uses Cloud Server settings above.

---

## 4. Portal configuration

### Admin → HRM → Masters → Biometric Devices

1. Add device with **serial number** exactly as on device label.
2. Assign **factory/unit**.
3. Set status **Active**.

### Employee master

- **Biometric ID** on each employee = PIN/user ID enrolled on the device (face/fingerprint user number).

### Environment (`.env` on server)

```env
HRM_ADMS_PUSH_TOKEN=<strong-random-token>
HRM_ADMS_API_TOKEN=<strong-random-token>
HRM_ADMS_TIMEZONE=+6:00
```

Optional pull sync path (if using pull mode):

```env
HRM_ADMS_PULL_PATH=/api/attendance
HRM_ADMS_SYNC_EVERY=10
```

---

## 5. Verify sync

1. **Admin → HRM → Attendance → Sync** — device should show **Last seen** updating.
2. Enroll a test employee, punch in/out on device.
3. Raw punch appears under attendance sync; daily log processes via queue/scheduler.

If device shows **failed** or **stale**:

- Re-check Gateway + DNS (step 1)
- Re-check Server Address + Port 443 (step 2)
- Confirm firewall allows outbound HTTPS from device subnet
- Confirm device serial is registered in portal

---

## 6. Queue worker (production)

```bash
php artisan queue:work redis --queue=hrm-sync,hrm-attendance,hrm-payroll,hrm-mail --tries=3
```

Schedule (cron):

```bash
php artisan schedule:run
```

Includes ADMS sync and attendance processing commands.

---

*Updated June 2026 — based on SpeedFace V5L field photos.*
