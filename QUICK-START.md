# Quick Start Installation Guide

## 🚀 Get Started in 5 Minutes

### For Multisite Network (Recommended) ⭐

**This plugin is optimized for multisite networks!**

#### Step 1a: Network Activate (2 minutes)

1. **Upload the plugin:**
   - Connect to your server via FTP
   - Navigate to `/wp-content/plugins/`
   - Upload the entire `hope-ignites-fusion-restrictions` folder

2. **Network Activate:**
   - Go to **Network Admin → Plugins**
   - Find "Hope Ignites - Fusion Builder Container Restrictions"
   - Click **"Network Activate"**

3. **Automatic Setup:**
   - ✅ Plugin activates on all existing sites
   - ✅ Default settings applied automatically to each site
   - ✅ Future new sites get auto-configured
   - ✅ Each site can customize their settings independently

4. **View Network Overview:**
   - Go to **Network Admin → Settings → Fusion Restrictions**
   - See status of all sites
   - Access configuration links for each site
   - View default settings being applied

---

### For Single Site Installation

**Option A: Upload via WordPress Admin**
1. Download all files to a folder named: `hope-ignites-fusion-restrictions`
2. Zip the folder
3. Go to WordPress Admin → Plugins → Add New → Upload Plugin
4. Upload the ZIP file
5. Click "Activate Plugin"

**Option B: Upload via FTP/SFTP**
1. Connect to your server via FTP
2. Navigate to `/wp-content/plugins/`
3. Upload the entire `hope-ignites-fusion-restrictions` folder
4. Go to WordPress Admin → Plugins
5. Find "Hope Ignites - Fusion Builder Container Restrictions"
6. Click "Activate"

---

### Step 2: Quick Test (3 minutes)

#### Create Test Page:
1. **As Administrator**, create a new page
2. **Open Fusion Builder**
3. **Add a container**
4. **Edit container settings** (gear icon)
5. **Go to: Advanced → CSS Class**
6. **Add class:** `nhq-locked`
7. **Save** container and page

#### Test Restrictions:
1. **Create test user:**
   - Username: `test_affiliate`
   - Email: `test@hopeignites.org`
   - Role: **Affiliate Contributor** (create this role first using PublishPress Capabilities)

2. **Log out and log in as test user**

3. **Edit the test page**

4. **Look for:**
   - ✅ Yellow border around locked container
   - ✅ Lock icon with message
   - ✅ Hidden edit controls
   - ✅ "This section is managed by NHQ" notice

5. **Try to click the container** → Should show alert

6. **Try to save changes** → Should be blocked if you modified locked content

---

### Step 3: Configure Settings (Optional)

**For Single Sites:**
1. Go to **Settings → Fusion Restrictions**
2. Review the setup instructions
3. Change contact email if needed (default: marketing@hopeignites.org)
4. Check your current role and restriction status

**For Multisite Network:**

*As Network Admin:*
1. Go to **Network Admin → Settings → Fusion Restrictions**
2. View overview of all sites in your network
3. Access individual site configurations
4. See which sites have the plugin active

*As Site Admin:*
1. Go to **Site Dashboard → Settings → Fusion Restrictions**
2. Customize contact email for your specific site
3. View restriction status
4. Each site maintains independent settings

**Multisite Benefits:**
- ✅ **One activation** applies to all sites
- ✅ **New sites** automatically get configured
- ✅ **Per-site customization** available
- ✅ **Centralized management** via network admin
- ✅ **Independent operation** - settings don't affect other sites

---

## 📁 Required File Structure

Your plugin folder should look like this:

```
/wp-content/plugins/hope-ignites-fusion-restrictions/
├── hope-ignites-fusion-restrictions.php  ← Main plugin
├── assets/
│   └── fusion-restrictions.js            ← JavaScript
├── LICENSE                                ← MIT License
├── README.md                              ← Full documentation
└── QUICK-START.md                         ← This file
```

---

## 🎯 Using the Plugin

### To Lock a Container:

**Method 1: Via Fusion Builder UI**
1. Edit page with Fusion Builder
2. Click container's gear icon (⚙️)
3. Go to **Advanced → CSS Class**
4. Add: `nhq-locked` or `nhq-critical`
5. Save

**Method 2: Via Code**
```
[fusion_builder_container class="nhq-locked"]
   Your content here...
[/fusion_builder_container]
```

### Locked Classes Available:
- `nhq-locked` - For general locked content
- `nhq-critical` - For critical sections

### Restricted Roles:
- `affiliate_contributor` - Cannot edit locked containers
- All other roles (Administrator, Editor, etc.) - Full access

---

## ⚠️ Common Issues & Fixes

### "Plugin doesn't work"
- ✅ Clear browser cache (Ctrl+Shift+R)
- ✅ Check user role is exactly: `affiliate_contributor`
- ✅ Check CSS class is exactly: `nhq-locked` or `nhq-critical`
- ✅ Open browser console (F12) to check for errors

### "Can't find Affiliate Contributor role"
- ✅ Install PublishPress Capabilities plugin
- ✅ Go to: PublishPress → Capabilities → Add Role
- ✅ Name: `Affiliate Contributor`
- ✅ Copy from: Contributor or Editor
- ✅ Save
- ✅ **For Multisite:** Create role on each site where you need restrictions, or use a network-wide role management plugin

### "Plugin not working on specific site in network"
- ✅ Check plugin is network activated
- ✅ Verify user has `affiliate_contributor` role on that specific site
- ✅ Check site-specific settings at: Site Dashboard → Settings → Fusion Restrictions
- ✅ Test by switching to that site's admin panel
- ✅ Look for site-specific errors in debug log

### "JavaScript not loading"
- ✅ Check `/assets/fusion-restrictions.js` exists
- ✅ Check file permissions (should be 644)
- ✅ Deactivate and reactivate plugin

### "Changes still saved"
- ✅ This is server-side validation
- ✅ Check PHP error logs
- ✅ Enable WP_DEBUG in wp-config.php
- ✅ Look for errors in debug.log

---

## 🔍 Testing Checklist

Quick tests to verify everything works:

**✅ Test 1: Visual Indicators**
- [ ] Locked container has yellow border
- [ ] Lock message is visible
- [ ] Edit controls are hidden

**✅ Test 2: Click Prevention**
- [ ] Clicking locked container shows alert
- [ ] Cannot access edit settings

**✅ Test 3: Save Validation**
- [ ] Trying to save modified locked content shows error
- [ ] Original content is restored

**✅ Test 4: Non-Locked Content**
- [ ] Can edit and save non-locked containers
- [ ] Regular content works normally

**✅ Test 5: Admin Access**
- [ ] Admins see no restrictions
- [ ] Admins can edit everything

---

## 📞 Need Help?

**Hope Ignites Team:**
- Technology Services: technology@hopeignites.org
- Peter Schweiss: Primary Admin
- Laura Stevens: Secondary Admin

**Plugin Developer:**
- 637 Digital Solutions
- Website: https://637digital.com
- For technical issues and customizations

**Check These First:**
1. Browser console (F12 → Console)
2. WordPress debug.log
3. README.md (full documentation)
4. Settings → Fusion Restrictions

---

## 🎉 You're Done!

The plugin is now active and protecting your content. 

**Next Steps:**
1. ✅ Test thoroughly on staging
2. ✅ Add `nhq-locked` class to production containers
3. ✅ Train Affiliate Contributors on restrictions
4. ✅ Document which sections are locked

**Remember:**
- Admins can edit everything
- Affiliate Contributors see locked sections
- All changes are logged (if using WP Activity Log)

---

**Quick Reference Card:**

| Action | CSS Class | User Role | Result |
|--------|-----------|-----------|---------|
| Lock container | `nhq-locked` | Any | Locked for affiliates |
| Lock critical | `nhq-critical` | Any | Locked for affiliates |
| Full access | Any | Administrator | Can edit all |
| Restricted | Any | affiliate_contributor | Cannot edit locked |

---

**Plugin Version:** 1.0.0  
**Last Updated:** January 2025  
**Author:** Hope Ignites Technology Team
