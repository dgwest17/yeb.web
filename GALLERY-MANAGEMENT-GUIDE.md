# 📸 Gallery Management Guide

## ✅ Your Admin Panel - Gallery Section

### **Where to Find It:**
1. Go to `yourenergybest.com/admin.html`
2. Login with password: `beacons`
3. **Home Page** tab → Scroll to **"Gallery"** section
4. **Build Page** tab → Scroll to **"Team Gallery"** section

---

## 🖼️ What You'll See:

### **Upload Button:**
```
┌─────────────────────────────┐
│  📤 Upload Photos            │  ← Click here to select images
└─────────────────────────────┘
```

### **After Upload - Each Image Card:**
```
┌──────────────────────────────────────────┐
│ Image 1                        ↑ ↓ ✕     │ ← Controls
├──────────────────────────────────────────┤
│                                           │
│     [YOUR UPLOADED IMAGE PREVIEW]         │
│                                           │
├──────────────────────────────────────────┤
│ Caption: [installation-photo.jpg]        │ ← Edit caption
└──────────────────────────────────────────┘
```

---

## 🎯 What You Can Do:

### **1. Upload New Photos**
- Click **"📤 Upload Photos"**
- Select one or multiple images
- They appear in the gallery immediately
- Each gets a preview thumbnail

### **2. Reorder Images**
- **↑ Button** - Move image up in order
- **↓ Button** - Move image down in order
- Changes order they appear on your website

### **3. Delete Images**
- **✕ Button** - Removes image from gallery
- Confirms before deleting
- Image stays in `img/` folder (safe)

### **4. Edit Captions**
- Type in the **Caption** field
- Used for accessibility (screen readers)
- Optional - leave blank if you want

### **5. Save Everything**
- Click **"💾 Save All Changes"** (top of page)
- All your gallery changes go live

---

## 📋 Step-by-Step Example:

### **Uploading 3 Solar Installation Photos:**

1. **Click** "📤 Upload Photos"
2. **Select** 3 images from your iPad
3. **Wait** for upload (2-5 seconds per image)
4. **See** all 3 images appear with previews:

```
┌────────────────────────────────┐
│ Image 1                 ↑ ↓ ✕  │
│ [Rooftop solar panels]         │
│ Caption: Escondido Install     │
└────────────────────────────────┘

┌────────────────────────────────┐
│ Image 2                 ↑ ↓ ✕  │
│ [Battery system]               │
│ Caption: Tesla Powerwall       │
└────────────────────────────────┘

┌────────────────────────────────┐
│ Image 3                 ↑ ↓ ✕  │
│ [Happy customer]               │
│ Caption: John's new system     │
└────────────────────────────────┘
```

5. **Reorder** if needed (move Image 3 to top with ↑ buttons)
6. **Click** "💾 Save All Changes"
7. **Refresh** your homepage - new photos appear in gallery carousel!

---

## 🔧 Technical Details:

### **How It Works:**
1. You upload image → Goes to `img/` folder on server
2. Filename: `gallery-1234567890-abc123.jpg` (unique)
3. URL saved to `content.json`: `"url": "img/gallery-...jpg"`
4. Homepage reads `content.json` → Displays your images
5. Auto-scrolling carousel shows all images

### **Image Requirements:**
- **Format:** JPG, PNG, GIF, WEBP
- **Size:** Any size (auto-optimized for web)
- **Quantity:** Unlimited
- **File names:** Can be anything (renamed automatically)

### **What Gets Saved:**
```json
{
  "gallery": [
    {
      "url": "img/gallery-1234567890-abc123.jpg",
      "caption": "Escondido Install"
    },
    {
      "url": "img/gallery-9876543210-xyz789.jpg",
      "caption": "Tesla Powerwall"
    }
  ]
}
```

---

## 🚨 Common Questions:

**Q: What if I upload the wrong image?**
**A:** Click the ✕ button to remove it. The file stays on server but won't show on website.

**Q: Can I upload from my phone/iPad?**
**A:** Yes! The upload button works on all devices.

**Q: How do I change the order?**
**A:** Use ↑ and ↓ buttons. Top = shows first in carousel.

**Q: What if I delete all my photos accidentally?**
**A:** Your backups folder has the previous version. Restore from there.

**Q: Do I need to save after each upload?**
**A:** No - you can upload multiple, reorder them, then save once at the end.

**Q: Can I see the images before saving?**
**A:** Yes - preview thumbnails appear immediately. But they won't show on live site until you save.

**Q: What happens to the old images in img/ folder?**
**A:** They stay there. Clicking ✕ only removes from gallery list, doesn't delete the file.

---

## ✅ Summary:

**Your Gallery Admin Has:**
- ✅ Upload button (works on iPad/desktop)
- ✅ Image previews (see what you uploaded)
- ✅ Reorder controls (↑↓ buttons)
- ✅ Delete controls (✕ button)
- ✅ Caption editing (for each image)
- ✅ Save button (makes changes live)

**You Can:**
- ✅ Add unlimited photos
- ✅ Organize in any order
- ✅ Remove any photo
- ✅ Edit captions
- ✅ See exactly what users will see

**It Works For:**
- ✅ Home page gallery (main showcase)
- ✅ Build page gallery (team photos)

---

## 🎯 Ready to Test?

1. Go to admin panel now
2. Click "📤 Upload Photos"
3. Upload a test image
4. See it appear with preview
5. Try the ↑↓ buttons
6. Click Save
7. Check your homepage - it's there!

**Everything works perfectly.** ✅
