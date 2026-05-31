# Implementation Audit — Complete Guide
## yourenergybest.com

---

## Status Summary

| Feature | Status | What's Left |
|---------|--------|-------------|
| 1. Mobile Nav & Sticky Header | ✅ Fully implemented | Nothing — test on your device |
| 2. Instagram Feed | ✅ Code ready | Need Instagram token (5 min setup) |
| 3. Zoho CRM Integration | ✅ Code ready | Need fresh refresh token (5 min setup) |
| 4. Comparison Table (Editable) | ✅ Fully implemented | Nothing — edit in admin panel |
| 5. Google Reviews | ✅ Code ready | Need Google API key + Place ID (10 min setup) |

---

## 1. MOBILE NAV & STICKY HEADER — ✅ Complete

Everything requested is already implemented:

- **Hamburger → X animation**: CSS transforms on `.nav__toggle.active span`
- **Body scroll lock**: `body.nav-open { overflow: hidden; }` added/removed via JS
- **Smooth open/close**: CSS transitions on `.nav__links` with slide-in animation
- **CTA visible in mobile nav**: "Get Your Custom Quote" button + phone number in menu
- **Accessibility**: `aria-expanded`, `aria-controls`, `aria-label`, focus trap on Tab/Escape
- **Shrink on scroll**: `.nav.shrink` class reduces height to 56px after 120px scroll
- **No layout shift**: `position: fixed` with `body { padding-top: 72px }`

**To verify**: Open your site on mobile, tap the hamburger, check the X animation, scroll the page behind shouldn't move.

---

## 2. INSTAGRAM FEED — Setup Required

### What's Built
- `instagram.php` — Server-side proxy with 1-hour cache
- Frontend: Lazy-loaded grid via IntersectionObserver (only fetches when section scrolls into view)
- Skeleton loaders while fetching
- Graceful fallback if API fails

### Setup Steps (5 minutes)

1. Go to: https://developers.facebook.com/apps/
2. Create an app (type: "Consumer")
3. Add the "Instagram Basic Display" product
4. Add your Instagram account as a test user
5. Generate a User Token → this gives you a short-lived token
6. Exchange for a long-lived token (valid 60 days):

```bash
curl -X GET "https://graph.instagram.com/access_token?grant_type=ig_exchange_token&client_secret=YOUR_APP_SECRET&access_token=YOUR_SHORT_LIVED_TOKEN"
```

7. Copy the long-lived token
8. In Hostinger File Manager → `public_html/`
9. Create file `.instagram_token` (with the dot)
10. Paste ONLY the token (no quotes, no whitespace)
11. Save

### Auto-Refresh (Important)
Long-lived tokens expire after 60 days. To auto-refresh, create a cron job in Hostinger:
- Hostinger → Advanced → Cron Jobs
- Schedule: `0 0 1,15 * *` (1st and 15th of each month)
- Command:
```bash
php /home/YOUR_USER/public_html/refresh-ig-token.php
```

I can create the `refresh-ig-token.php` file if you want.

---

## 3. ZOHO CRM — Setup Required

### What's Built
- **Quote Form → Zoho Lead**: name, email, phone, zip, monthly bill, customer option, UTM params
- **Newsletter Popup → Zoho Lead** (tagged "Newsletter")
- **Dedup check**: Searches by email before creating — updates existing if found
- **UTM tracking**: Captures utm_source/medium/campaign from URL, stores in sessionStorage
- **Local backup**: Every submission saved to `leads_backup.csv` before Zoho attempt
- **Email notification**: `mail()` to info@yourenergybest.com on every submission
- **Monthly Bill dropdown**: Under $100 / $100-$200 / $200-$400 / $400-$700 / $700+
- **Calendly integration**: Success page shows "Schedule Now →" linking to Calendly
- **All "Talk to an Expert" buttons** → link to https://calendly.com/yourenergybest

### Fix the Broken Form (5 minutes)

The form fails because the refresh token is missing/expired:

1. Go to: https://api-console.zoho.com/
2. Click your Self Client (or create one)
3. "Generate Code" tab:
   - Scope: `ZohoCRM.modules.ALL,ZohoCRM.settings.ALL`
   - Duration: 10 minutes
   - Click "Create"
4. Copy the code, then run in terminal:

```bash
curl -X POST "https://accounts.zoho.com/oauth/v2/token" \
  -d "code=PASTE_CODE_HERE" \
  -d "client_id=" \
  -d "client_secret=" \
  -d "redirect_uri=https://yourenergybest.com/auth" \
  -d "grant_type=authorization_code"
```

5. Copy the `refresh_token` from the response
6. In Hostinger File Manager → `public_html/`
7. Create file `.zoho_refresh_token`
8. Paste ONLY the refresh token value
9. Test by submitting the quote form

### Zoho CRM Notifications

To get instant notifications when leads come in:
1. Zoho CRM → Settings → Automation → Workflow Rules
2. Create New Rule → Module: **Leads**
3. When: "A record is **created**"
4. Add Action → "Send Email Notification"
5. To: your email
6. Customize template with merge fields (First Name, Email, Phone, etc.)
7. Save & activate

### Zoho Field Mapping

| Form Field | Zoho Lead Field | Notes |
|-----------|----------------|-------|
| Full Name | First_Name + Last_Name | Auto-split |
| Email | Email | Standard |
| Phone | Phone | Standard |
| Zip Code | Zip_Code | Custom field — create in Zoho if missing |
| Monthly Bill | Description | Appended to description |
| Customer Option | Description | Appended to description |
| UTM params | Description | Appended to description |
| Source | Lead_Source | "Website Quote Form" or "Newsletter Popup" |
| Tag | Tag | "Quote Request" or "Newsletter" |

**If `Zip_Code` fails**: The code auto-retries without it, appending zip to Description instead.

---

## 4. COMPARISON TABLE — ✅ Complete

### What's Built
- **CMS-driven**: Title, subtitle, column headers, and all rows are loaded from `content.json`
- **Admin editable**: New "Utilities vs Solar Comparison Table" section in admin panel (Global tab)
- **Add/remove rows**: Dynamic row management in admin
- Changed from "SDG&E vs Solar" → "California Utilities vs. Going Solar"
- Fallback defaults if no data in content.json yet

### How to Edit
1. Go to your admin panel
2. Click "Global" tab
3. Scroll to "Utilities vs Solar Comparison Table"
4. Edit title, subtitle, column headers, and individual row values
5. Add/remove rows as needed
6. Save

### Data Structure (in content.json)
```json
{
  "comparison": {
    "title": "California Utilities vs. Going Solar",
    "subtitle": "Utility rates have increased 40%+ in three years.",
    "col_utility": "Stay with Utility",
    "col_solar": "Go Solar",
    "rows": [
      { "label": "Monthly Cost", "utility": "~$250+ (rising)", "solar": "~$150 (locked)" },
      { "label": "Annual Rate Increase", "utility": "~8–10%/yr", "solar": "0%" },
      ...
    ]
  }
}
```

---

## 5. GOOGLE REVIEWS — Setup Required

### What's Built
- `reviews.php` — Server-side proxy with 6-hour cache
- Fetches from Google Places API (New)
- Updates trust bar and testimonials rating dynamically
- Graceful fallback to static values (5.0 / 194 reviews)

### Setup Steps (10 minutes)

#### Get Your Google Place ID
1. Go to: https://developers.google.com/maps/documentation/places/web-service/place-id-finder
2. Search "Your Energy Best" or your business address
3. Copy the Place ID (starts with `ChIJ...`)
4. In Hostinger File Manager → `public_html/`
5. Create file `.google_place_id`
6. Paste the Place ID
7. Save

#### Get a Google API Key
1. Go to: https://console.cloud.google.com/
2. Create a project (or use existing)
3. Enable "Places API (New)"
4. Go to Credentials → Create API Key
5. Restrict the key:
   - Application: HTTP referrers → add `yourenergybest.com/*`
   - API: Restrict to "Places API (New)"
6. Copy the API key
7. Create file `.google_api_key` in `public_html/`
8. Paste the key
9. Save

#### Billing Note
Google Places API gives you $200/month free credit (~40,000 requests). With 6-hour caching, your site will make ~4 requests/day = ~120/month. Well within free tier.

---

## Files Changed in This Update

| File | Changes |
|------|---------|
| `index.html` | Already had all nav/IG/reviews/comparison. No changes needed. |
| `quote.html` | Added monthly bill dropdown, select styling |
| `zoho.php` | Added monthly_bill to lead data + CSV backup |
| `admin.html` | Added comparison table admin fields + row management |
| `style.css` | No changes needed |
| `instagram.php` | Already complete from previous update |
| `reviews.php` | Already complete from previous update |

---

## Files to Create on Hostinger (NOT in Git)

These go directly in Hostinger File Manager, not in your repo:

| File | Content |
|------|---------|
| `.zoho_refresh_token` | Your Zoho refresh token |
| `.instagram_token` | Your Instagram long-lived token |
| `.google_api_key` | Your Google API key |
| `.google_place_id` | Your Google Place ID |

All start with a dot (hidden files). All should be in `.gitignore`.
