# 🔒 CONTENT PROTECTION SYSTEM

## The Problem We Solved:
When Claude sends you site updates, your admin edits (gallery photos, testimonials, text changes) could get overwritten if content.json was included in the update package.

---

## ✅ The Solution - 3-Layer Protection:

### **Layer 1: API Fetch Before Changes**
**How it works:**
- Before making ANY structural changes, Claude fetches your live content.json via API
- Claude sees exactly what you have (photos, testimonials, custom text)
- Claude makes changes while preserving your data

**Your command:**
```
"Fetch my live content first, then add a new testimonials section"
```

Claude will:
1. Call: `yourenergybest.com/api.php?key=yeb_5622d6ee37e38f85c2ea52ca73eb43af`
2. See your current data
3. Build changes around YOUR data, not old placeholder data

---

### **Layer 2: Claude Never Sends content.json**
**New Rule:**
- Claude will NEVER include content.json in update packages
- You only get: HTML, CSS, JS, PHP files
- Your content.json stays on your server, safe

**What you'll receive:**
✅ Updated index.html  
✅ New feature.js  
✅ Updated style.css  
❌ NO content.json (your data is safe)

---

### **Layer 3: Automatic Backups**

#### **Server-Side Backups** (Automatic)
Every time you click "Save All Changes" in admin:
- Creates backup in `/backups/` folder
- Named: `content-2026-02-04-153045.json`
- Keeps last 10 backups (auto-deletes older)
- Stored on your server

**Where:** `/public_html/backups/`

#### **Local Backups** (Manual)
Click "📥 Download Backup" button in admin:
- Downloads content.json to your device
- Keep these before asking Claude for major changes
- Upload to Claude if you need to restore

---

## 🚨 IF DATA LOSS HAPPENS:

### Option 1: Restore from Server Backup
1. Go to Hostinger File Manager
2. Navigate to `/public_html/backups/`
3. Find most recent: `content-2026-02-04-XXXXXX.json`
4. Copy it
5. Replace `/public_html/content.json` with it

### Option 2: Restore from Local Backup
1. Find downloaded backup on your device
2. Upload to Claude: "Restore this content.json"
3. Claude will give you the file to upload

### Option 3: Tell Claude What You Had
"I had 5 gallery photos and 3 custom testimonials, rebuild my content.json"

---

## 📋 NEW WORKFLOW WITH CLAUDE:

### ❌ OLD WAY (Risky):
```
You: "Add a new section to home page"
Claude: [Sends full site package including content.json]
Result: Your edits get overwritten
```

### ✅ NEW WAY (Safe):
```
You: "Fetch my live content, then add a new section to home page"
Claude: [Calls API, sees your data, preserves it]
Claude: "I see you have 5 gallery photos and 3 testimonials. Here's ONLY the new section code."
Result: Your edits stay safe
```

---

## 🎯 BEST PRACTICES:

### Before Major Changes:
1. Click "📥 Download Backup" in admin
2. Save it to your device
3. Then ask Claude for changes

### When Asking Claude for Help:
**Start with:** "Fetch my live content first, then..."

**Examples:**
- ✅ "Pull my current data, then add a video section"
- ✅ "Check my live site, then change the gallery layout"
- ✅ "Get my latest content, then restructure the FAQ"

### After Changes:
1. Check your site
2. Verify your edits are still there
3. If something's wrong, restore from backup

---

## 🔐 Protection Features Summary:

| Feature | How It Protects You |
|---------|-------------------|
| **API Fetch** | Claude sees your live data before making changes |
| **No content.json in updates** | Your data never gets replaced |
| **Server backups** | 10 versions saved automatically |
| **Download backup button** | Manual backups to your device |
| **Backup directory** | All versions stored in /backups/ |

---

## 🆘 Emergency Recovery:

**If you upload a package and lose data:**

1. **Stop** - Don't panic
2. **Check** `/public_html/backups/` folder
3. **Find** most recent backup (timestamps in filename)
4. **Restore** - Copy it over content.json
5. **Or contact Claude** with: "Need to restore from backup [date]"

---

## 📞 Questions?

- **"How do I know if my backup worked?"** - Check File Manager → `/backups/` folder
- **"How often should I backup?"** - Before any major changes
- **"Can Claude access my backups?"** - No, only via API and only if you provide the key
- **"What if backups folder gets full?"** - Auto-deletes oldest after 10 files

---

**Your content is now protected. Edit freely in admin, ask Claude for structural changes without fear!** 🎉
