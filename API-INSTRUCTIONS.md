# 🔗 Claude API Integration - Instructions

## What This Does:
Claude can now READ your live website content before making any changes. This means your admin edits are never overwritten.

---

## Your API Credentials:

**Endpoint:** `https://yourenergybest.com/api.php`  
**API Key:** `yeb_5622d6ee37e38f85c2ea52ca73eb43af`

*(You can also find these in Admin → Global Settings tab)*

---

## How To Use:

### When Asking Claude for Changes:

**BEFORE:**
```
You: "Add a new FAQ section to the Build page"
Claude: [Makes changes, potentially overwrites your edits]
```

**NOW:**
```
You: "First fetch my live content using the API, then add a new FAQ section to Build page"
Claude: [Fetches yourenergybest.com/api.php?key=yeb_5622d6ee37e38f85c2ea52ca73eb43af]
Claude: "I can see your current Build page has 5 FAQs and 6 benefit cards. Adding the new section while preserving everything..."
```

### Example Prompts:

✅ **"Check my live site first, then add a video to the Build page"**  
✅ **"Fetch current content via API, then update the hero title on Home"**  
✅ **"Pull my latest content and add a new customer option card"**  
✅ **"Get my live data, then restructure the testimonials section"**

---

## What Claude Can See:

- All your text edits from admin
- Current gallery images
- All testimonials, FAQs, stats
- Blog posts
- Everything in content.json

## What Claude CANNOT Do:

- ❌ Change your content (read-only)
- ❌ Delete anything
- ❌ Access other files on your server
- ❌ Make more than 10 requests per minute (rate limited)

---

## Security:

- **API key is secret** - Only you and Claude know it
- **Read-only** - Can't modify anything
- **Rate limited** - Max 10 requests/minute
- **No sensitive data** - Only your public website content

---

## If You Need a New API Key:

1. Go to your Hostinger File Manager
2. Edit `/public_html/api.php`
3. Line 9: Change the key to a new random string
4. Update the key in admin.html (search for "yeb_5622")
5. Give Claude the new key

---

## Testing the API:

Open this in your browser (after uploading):
```
https://yourenergybest.com/api.php?key=yeb_5622d6ee37e38f85c2ea52ca73eb43af
```

You should see your full content.json in JSON format.

If you see an error, the API isn't working yet.

---

## Troubleshooting:

**"Invalid API key" error:**
- Make sure you're using the correct key
- Check that api.php uploaded correctly

**"Rate limit exceeded":**
- Wait 60 seconds and try again
- Claude is making too many requests

**"Content file not found":**
- Make sure content.json exists in your public_html folder

---

**Now you can edit in admin AND ask Claude for help without conflicts! 🎉**
