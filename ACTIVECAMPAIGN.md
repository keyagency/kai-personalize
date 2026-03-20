# ActiveCampaign Integration

## Overview

The Kai Personalize addon integrates with ActiveCampaign to automatically identify visitors from email campaigns and personalize content based on their CRM data.

## How It Works

```
┌─────────────────────┐
│ ActiveCampaign Email│
│   with tracking link│
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│   Visitor clicks    │
│   link → lands on   │
│   your website      │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Browser has cookie  │
│   (vgo_ee, __actc)  │
│   with encoded email│
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ TrackVisitor        │
│ Middleware detects  │
│   cookie            │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ ActiveCampaign API  │
│   Fetches contact   │
│   data:             │
│   - tags            │
│   - lists           │
│   - custom fields   │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Stored as visitor   │
│   attributes (type: │
│   'crm')            │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Available via       │
│ {{ kai:visitor }}   │
│   tag in templates  │
└─────────────────────┘
```

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
# Enable ActiveCampaign integration
KAI_ACTIVECAMPAIGN_ENABLED=true

# ActiveCampaign API credentials
KAI_ACTIVECAMPAIGN_URL=https://your-account.api-us1.com
KAI_ACTIVECAMPAIGN_API_KEY=your_api_key_here

# Cookie name that contains the email (ActiveCampaign default)
KAI_ACTIVECAMPAIGN_COOKIE=vgo_ee

# Cache duration in minutes (default: 1440 = 24 hours)
KAI_ACTIVECAMPAIGN_CACHE_TTL=1440
```

### Finding Your API Credentials

1. **API URL**: Log into ActiveCampaign → Settings → Developer → API
   - Format: `https://{{account}}.api-us1.com` (or similar)
   - Example: `https://example123.api-us1.com`

2. **API Key**: Same page in ActiveCampaign
   - Click "Add a key" or use existing key
   - Copy the key (starts with `user_...`)

## Stored Data

When a visitor from an ActiveCampaign email is detected, these attributes are stored:

### Basic Contact Info

| Attribute | Type | Example |
|-----------|------|---------|
| `ac_contact_id` | string | `"12"` |
| `ac_email` | string | `"user@example.com"` |
| `ac_first_name` | string | `"John"` |
| `ac_last_name` | string | `"Doe"` |
| `ac_phone` | string | `"+31201234567"` |
| `ac_created_at` | timestamp | `"2024-01-15 10:30:00"` |
| `ac_updated_at` | timestamp | `"2024-02-01 14:22:00"` |

### Tags

| Attribute | Type | Example |
|-----------|------|---------|
| `ac_tags` | JSON array | `["VIP", "Newsletter", "Customer"]` |

### Lists

| Attribute | Type | Example |
|-----------|------|---------|
| `ac_lists` | JSON object | `{"Newsletter": {"id": "1", "status": 1}}` |

List status codes:
- `1` = Subscribed
- `2` = Unsubscribed

### Custom Fields

| Attribute | Type | Example |
|-----------|------|---------|
| `ac_custom_fields` | JSON object | `{"member_level": "Gold", "company": "Acme Corp"}` |

## Usage Examples

### Personalize by Tag

```antlers
{{ kai:visitor }}
    {{ if ac_tags contains 'VIP' }}
        <p>Welcome back, VIP member! Here's your exclusive content.</p>
    {{ elseif ac_tags contains 'New Customer' }}
        <p>Welcome! Let us show you around.</p>
    {{ /if }}
{{ /kai:visitor }}
```

### Personalize by List Membership

```antlers
{{ kai:visitor }}
    {{ if ac_lists.newsletter.status == 1 }}
        <p>Thanks for being a subscriber!</p>
        <a href="/exclusive-content">View subscriber content</a>
    {{ /if }}
{{ /kai:visitor }}
```

### Personalize by Custom Field

```antlers
{{ kai:visitor }}
    {{ if ac_custom_fields.member_level == 'Gold' }}
        <section class="gold-only">
            <h2>Gold Member Benefits</h2>
            ...
        </section>
    {{ /if }}
{{ /kai:visitor }}
```

### Condition Tag with AC Data

```antlers
{{ kai:condition attribute="ac_member_level" operator="equals" value="Gold" }}
    <p>Gold member exclusive content</p>
{{ /kai:condition }}
```

### Dynamic Greeting

```antlers
{{ kai:visitor }}
    {{ if ac_first_name }}
        <h1>Welcome back, {{ ac_first_name }}!</h1>
    {{ else }}
        <h1>Welcome!</h1>
    {{ /if }}
{{ /kai:visitor }}
```

### Tag-Based Navigation

```antlers
{{ kai:visitor }}
    {{ if ac_tags contains 'Employee' }}
        {{# Show internal navigation #}}
        <nav class="internal-nav">
            <a href="/internal/dashboard">Dashboard</a>
            <a href="/internal/reports">Reports</a>
        </nav>
    {{ /if }}
{{ /kai:visitor }}
```

## Cookie Decoding

The service automatically handles multiple ActiveCampaign cookie encoding formats:

1. **Base64 encoded** (most common)
2. **URL-decoded + Base64**
3. **Plain text email**
4. **URL-encoded only**

### Cookie Names

The service checks these cookies in order:
1. `vgo_ee` (ActiveCampaign default, configurable)
2. `__actc` (Alternative)
3. `contact_email` (Fallback)

### Testing Cookie Decoding

```php
// In ActiveCampaignService
$email = $service->getEmailFromCookie();
```

## Testing

### Command Line Testing

```bash
# Test API connection only
php artisan kai:test-activecampaign

# Test email lookup
php artisan kai:test-activecampaign --email=user@example.com

# Test cookie-based retrieval (interactive)
php artisan kai:test-activecampaign --test-cookie
```

### Expected Output

```
ActiveCampaign API Test
========================

Testing API connection...
  ✔ Connection successful!

Looking up contact: user@example.com

Contact Data:
+-------------+----------------+
| Field       | Value          |
+-------------+----------------+
| Contact ID  | 12             |
| Email       | user@example.com |
| First Name  | John           |
| Last Name   | Doe            |
+-------------+----------------+

Tags: VIP, Newsletter

Lists:
+---------------------+------------+
| List                | Status     |
+---------------------+------------+
| Newsletter          | Subscribed |
+---------------------+------------+

Custom Fields:
+---------------------+------------+
| Field               | Value      |
+---------------------+------------+
| member_level        | Gold       |
+---------------------+------------+
```

## Privacy & GDPR

### Cookie Consent

When `cookie_consent_required` is enabled in config:
- Only reads AC cookie if user has given consent
- Respects `cookie_consent` and `gdpr_consent` cookies

### Data Retention

- Cache TTL respects AC rate limits (default 24 hours)
- Stored as visitor attributes with type `crm`
- Follows addon-wide retention policies

### Right to be Forgotten

- AC attributes are cleared when visitor data is deleted
- Can be cleared via CP visitor deletion
- No data remains in addon after deletion

### Logging

- API calls are logged to `kai_personalize_api_logs` table
- Sensitive data (email, API key) is masked in logs
- Logs respect retention period

## Troubleshooting

### No Contact Data Appearing

1. **Check configuration:**
   ```bash
   php artisan kai:test-activecampaign
   ```

2. **Verify cookie exists:**
   - Use browser dev tools → Application → Cookies
   - Look for `vgo_ee` or configured cookie name

3. **Check email exists in AC:**
   ```bash
   php artisan kai:test-activecampaign --email=test@example.com
   ```

4. **Verify feature is enabled:**
   ```php
   config('kai-personalize.features.activecampaign') // Should be true
   ```

### API Errors

- **401 Unauthorized**: Check API key
- **404 Not Found**: Check API URL (include `/api/3` path is auto-added)
- **429 Too Many Requests**: Increase cache TTL or check rate limits

### Cookie Not Detected

- Verify cookie name in config matches AC setting
- Check cookie domain/path settings
- Try alternative cookie names: `__actc`, `contact_email`

## API Endpoints Used

| Endpoint | Purpose |
|----------|---------|
| `GET /api/3/contacts` | Find contact by email |
| `GET /api/3/contacts/{id}/contactTags` | Get contact tags |
| `GET /api/3/contacts/{id}/contactLists` | Get list memberships |

## Rate Limits

ActiveCampaign rate limits:
- Free plan: 1,000 requests/day
- Plus plan: 10,000 requests/day
- Professional: 100,000 requests/day

The addon caches results for 24 hours (configurable) to minimize API usage.

## Future Enhancements

Potential additions:
- [ ] Two-way sync (update AC from visitor data)
- [ ] Event tracking (send page views to AC)
- [ ] Automation triggers (trigger AC automations based on behavior)
- [ ] Tag management (add/remove tags via addon)
- [ ] E-commerce integration (order data sync)
