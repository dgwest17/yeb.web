# 🚀 Deployment Guide

## How Auto-Deployment Works

1. You push code to GitHub
2. Hostinger detects the change
3. Pulls latest code automatically
4. Site updates in ~30 seconds

## Initial Setup (One-Time)

### Connect GitHub to Hostinger:

1. **Hostinger Dashboard** → **Websites** → **yourenergybest.com**
2. Look for **"Git"** or **"GitHub"** menu item
3. Click **"Connect Repository"**
4. Enter:
   - Repository URL: `https://github.com/YOUR-USERNAME/yourenergybest-site`
   - Branch: `main`
   - Deploy path: `/public_html`
   - Personal access token: [your token]
5. Click **"Connect"** or **"Enable Auto-Deploy"**

### If Hostinger Doesn't Have Built-In GitHub:

Use GitHub Actions instead (I'll provide the workflow file).

## Making Updates

### Via GitHub Desktop (Easy):

1. Make changes to files in your local folder
2. GitHub Desktop shows changed files
3. Write commit message: "Add new pricing page"
4. Click **"Commit to main"**
5. Click **"Push origin"**
6. Wait 30 seconds
7. Refresh your website - changes are live!

### Via GitHub Web (Easiest):

1. Go to your repository on GitHub.com
2. Navigate to the file you want to update
3. Click the pencil icon (Edit)
4. Make changes
5. Scroll down → **"Commit changes"**
6. Auto-deploys immediately

## Content vs Code

**Edit in Admin Panel:**
- Text content
- Testimonials
- Gallery images
- Stats
- FAQ

**Edit via GitHub:**
- New pages
- Layout changes
- New features
- Styling updates
- Navigation changes

## Troubleshooting

**Changes not deploying?**
1. Check Hostinger deployment logs
2. Verify branch name is `main` not `master`
3. Confirm deploy path is correct

**Content disappeared?**
1. Go to Hostinger File Manager
2. Check `/public_html/backups/` folder
3. Restore most recent content.json backup

**Site broken?**
1. GitHub → Repository → "Commits"
2. Find last working version
3. Click "Browse files"
4. Download that version
5. Re-upload to Hostinger

## Security

- Never commit API keys or passwords
- `.gitignore` protects sensitive files
- Keep repository private if possible
- Review changes before pushing

---

Need help? Check the main README.md
