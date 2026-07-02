# TMS Phase 2 — GPS & WhatsApp (Production)

## WhatsApp go-live checklist

### 1. App Settings → WhatsApp Gateway

| Provider | When to use | Required fields |
|----------|-------------|-----------------|
| `meta_cloud` | Meta WhatsApp Business Cloud API | Access token + Phone Number ID |
| `sslwireless` | SSL Wireless (Bangladesh) | API token + Sender/Instance ID |
| `greenweb` | GreenWeb (Bangladesh) | API token |
| `bulksmsbd` | BulkSMSBD.net | API key + Sender ID |
| `custom` | Any HTTP BSP | Custom URL (+ optional token & sender) |
| `log` | Development only | None |

### 2. Enable transport notifications

**Admin → App Settings → Notifications → Transport (TMS)**

- Turn on **WhatsApp — transport updates** (`notify_whatsapp_tms`)

### 3. Optional `.env` overrides

```env
WHATSAPP_SSLWIRELESS_URL=https://...
WHATSAPP_GREENWEB_URL=https://...
WHATSAPP_BULKSMSBD_URL=https://...
```

Defaults are in `config/whatsapp.php`. Confirm exact URL and JSON fields with your BSP before go-live.

### 4. Meta Cloud API notes

- Endpoint: `https://graph.facebook.com/v21.0/{phone_number_id}/messages`
- Phone numbers normalized to `8801XXXXXXXXX`
- Production may require **approved message templates** — session `text` works for dev/testing

### 5. Verify

1. Set provider + credentials in App Settings
2. Submit/reject a test transport request with employee phone on file
3. Check Laravel log (`log` driver) or BSP delivery report

---

## GPS tracking (implemented)

### Admin setup per unit

**TMS → Settings → GPS Tracking**

| Provider | Behaviour |
|----------|-----------|
| **Driver Mobile GPS** | Browser geolocation when driver starts/ends trip (Employee + Rental portals) |
| **GPS Device / Telematics API** | External systems POST positions to API |
| **None** | Disabled |

### Telematics API

**Endpoint:** `POST /api/tms/gps/positions`

**Auth:** Bearer token or header `X-Tms-Gps-Token`

```env
TMS_GPS_API_TOKEN=<generate-strong-random-token>
```

**Example payload:**

```json
{
  "vehicle_id": 12,
  "trip_log_id": 45,
  "latitude": 23.8103,
  "longitude": 90.4125,
  "speed_kmh": 40,
  "heading": 180,
  "accuracy_m": 5,
  "recorded_at": "2026-06-24T10:30:00+06:00"
}
```

**Responses:**

- `200` — `{ "success": true, "id": 123 }`
- `401` — invalid/missing token
- `422` — GPS disabled or provider mismatch for unit

### Driver mobile GPS

- Forms on **Employee → Transport → Trips** and **Rental Driver → Trips**
- JS captures `navigator.geolocation` on submit (`resources/js/tms-trip-gps.js`)
- Stored as `browser_start` / `browser_end` in `tms_gps_positions`

### View positions

**TMS → GPS Tracking** — filter by unit/vehicle  
**TMS → Trips → Show** — positions for that trip with Google Maps links

### Production checklist

- [ ] Enable GPS for each factory in TMS Settings
- [ ] Choose provider (`browser` or `device_api`)
- [ ] Set `TMS_GPS_API_TOKEN` in production `.env` if using telematics
- [ ] Drivers use HTTPS portal (geolocation requires secure context on mobile)
- [ ] Test trip start/end from phone; confirm position in admin

---

## Code reference

| Component | Path |
|-----------|------|
| GPS service | `app/Services/Tms/TmsGpsService.php` |
| Trip integration | `app/Services/Tms/TripService.php` |
| Device API | `app/Http/Controllers/Api/Tms/GpsPositionController.php` |
| Mobile JS | `resources/js/tms-trip-gps.js` |
| WhatsApp messaging | `app/Services/Tms/TmsMessagingService.php` |
| Tests | `tests/Feature/TmsGpsIntegrationTest.php` |

---

*Updated June 2026 — GPS device/mobile integration live.*
