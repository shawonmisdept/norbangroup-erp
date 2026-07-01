# TMS Phase 2 — GPS & WhatsApp Integration Notes

## WhatsApp (Transport notifications)

### Supported providers (App Settings → WhatsApp Gateway)

| Provider key | Operator | Credentials |
|--------------|----------|-------------|
| `log` | Development | None — writes to Laravel log |
| `meta_cloud` | Meta WhatsApp Cloud API | Access token + Phone Number ID |
| `sslwireless` | SSL Wireless (Bangladesh) | API token + Sender/Instance ID |
| `greenweb` | GreenWeb (Bangladesh) | API token |
| `bulksmsbd` | BulkSMSBD.net | API key + Sender ID |
| `custom` | Any HTTP BSP | Custom URL + optional API token & sender |

Enable delivery under **Notifications → Transport (TMS) → WhatsApp — transport updates**.

### Environment overrides (optional)

Default operator URLs are in `config/whatsapp.php`. Override in `.env` if your vendor gives a different endpoint:

```env
WHATSAPP_SSLWIRELESS_URL=https://...
WHATSAPP_GREENWEB_URL=https://...
WHATSAPP_BULKSMSBD_URL=https://...
```

### Meta Cloud API

- POST `https://graph.facebook.com/v21.0/{phone_number_id}/messages`
- Phone numbers normalized to `8801XXXXXXXXX` format
- Template messages may be required for production — currently sends session `text` type only

### Custom HTTP API

POST JSON body includes: `phone`, `to`, `message`, `text`, and optional `sender` / `sender_id`.

Confirm payload field names with your operator and adjust `HttpWhatsAppGateway` if needed.

### Bangladesh BSP note

SSL Wireless, GreenWeb, and BulkSMSBD WhatsApp endpoints vary by account. **Confirm the exact URL and JSON fields with your provider** before go-live. Update `config/whatsapp.php` or env vars accordingly.

---

## GPS Tracking

### Current behaviour
- **TMS Settings → GPS Tracking** — enable stub mode and select provider
- **TMS → GPS Tracking** — view position history
- Table: `tms_gps_positions`
- Service: `App\Services\Tms\TmsGpsService`

### Future providers
| Provider key | Planned source |
|--------------|----------------|
| `device_api` | Telematics vendor webhook |
| `browser` | Driver mobile geolocation on trip start/end |

---

*Updated June 2026 — WhatsApp multi-operator support added.*
