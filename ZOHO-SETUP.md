# Zoho CRM Integration — Setup & Fix Guide

## The Problem
Your quote form shows "something went wrong" because the Zoho OAuth token has expired.
Auth codes are one-time-use and expire within minutes. Once the refresh token is lost
(e.g., during a deploy), the system can't authenticate.

## How to Fix It (5 minutes)

### Step 1: Generate a New Auth Code
1. Go to: https://api-console.zoho.com/
2. Click on your existing "Self Client" (or create one)
3. In the "Generate Code" tab:
   - **Scope**: `ZohoCRM.modules.ALL,ZohoCRM.settings.ALL`
   - **Time Duration**: 10 minutes
   - **Description**: "YEB website"
4. Click "Create" → Copy the generated code

### Step 2: Exchange for Refresh Token
Run this in your terminal (replace YOUR_CODE with the code from Step 1):

```bash
curl -X POST "https://accounts.zoho.com/oauth/v2/token" \
  -d "code=YOUR_CODE" \
  -d "client_id=1000.W1ZOGCIIX44GUMUK0827B9V9ZHC12L" \
  -d "client_secret=b7fc12526163429eda966f0f289096708eceb82983" \
  -d "redirect_uri=https://yourenergybest.com/auth" \
  -d "grant_type=authorization_code"
```

You'll get a response like:
```json
{
  "access_token": "1000.xxxx...",
  "refresh_token": "1000.yyyy...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

### Step 3: Save the Refresh Token
1. Copy the `refresh_token` value
2. In Hostinger File Manager, go to `public_html/`
3. Create a file named `.zoho_refresh_token` (note the dot at the start)
4. Paste ONLY the refresh token value (no quotes, no spaces)
5. Save

### Step 4: Test
Submit a test form on your website. Check:
- `zoho_debug.log` in your public_html folder for detailed logs
- `leads_backup.csv` — every submission is saved here as backup
- Your email — you should get a notification at info@yourenergybest.com

## What Changed in the New zoho.php
- **Local backup**: Every form submission is saved to `leads_backup.csv` FIRST, before trying Zoho
- **Email notification**: You get an email at info@yourenergybest.com for every submission
- **Graceful failure**: Even if Zoho API is down, the user sees "success" and you still get the lead
- **Newsletter support**: Newsletter popup sends `action: newsletter` → creates a Zoho Contact (not Lead)
- **Auto-retry**: If `Zip_Code` field causes errors, it retries without it

## Important Files
- `.zoho_refresh_token` — Your OAuth refresh token (DO NOT commit to git)
- `zoho_debug.log` — Debug log for troubleshooting
- `leads_backup.csv` — Backup of all submissions

## Add These to .gitignore
```
.zoho_refresh_token
zoho_debug.log
leads_backup.csv
```

## Zoho CRM Notifications
To get instant notifications when a lead comes in:
1. In Zoho CRM → Settings → Automation → Workflow Rules
2. Create New Rule → Module: Leads → When: "A record is created"
3. Add Action → "Send Email Notification" → To: your email
4. Customize the template with lead fields
5. Save & activate
