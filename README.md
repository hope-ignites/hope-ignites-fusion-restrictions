# Hope Ignites - Fusion Builder Container Restrictions Plugin

**Version:** 1.0.0  
**Requires:** WordPress 5.5+, Avada Theme  
**Tested up to:** WordPress 6.4  
**License:** GPL-2.0+

## Description

This plugin restricts editing of specific Avada Fusion Builder containers based on user roles and CSS classes. It's designed for the Hope Ignites multisite network to prevent Affiliate Contributors from editing critical sections like headers, footers, and navigation while still allowing them to edit blog posts and event pages.

## Features

- ✅ Lock containers by CSS class (`nhq-locked`, `nhq-critical`)
- ✅ Role-based restrictions (targets `affiliate_contributor` role)
- ✅ Visual indicators (yellow borders, lock icons)
- ✅ Custom restriction messages
- ✅ Server-side validation prevents unauthorized saves
- ✅ Compatible with Fusion Builder backend and live editors
- ✅ Network/Multisite compatible
- ✅ Easy configuration via Settings page

## Installation

### Method 1: Manual Upload

1. Download the plugin files
2. Upload the entire `hope-ignites-fusion-restrictions` folder to `/wp-content/plugins/`
3. Network activate the plugin (for multisite) or activate on individual sites
4. Go to **Settings → Fusion Restrictions** to configure

### Method 2: ZIP Upload

1. Zip the plugin folder:
   ```bash
   zip -r hope-ignites-fusion-restrictions.zip hope-ignites-fusion-restrictions/
   ```
2. Go to **Plugins → Add New → Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Activate the plugin

## File Structure

```
hope-ignites-fusion-restrictions/
├── hope-ignites-fusion-restrictions.php  (Main plugin file)
├── assets/
│   └── fusion-restrictions.js            (JavaScript for restrictions)
└── README.md                              (This file)
```

## How It Works

### Step 1: Mark Containers as Locked

In Avada Fusion Builder, add one of these CSS classes to containers you want to lock:

**Option A: Using Fusion Builder Interface**
1. Edit a page with Fusion Builder
2. Click on a container's settings (gear icon)
3. Go to **Advanced → CSS Class**
4. Add: `nhq-locked` or `nhq-critical`
5. Save the container

**Option B: Using Code Editor**
Add the class to the shortcode:
```
[fusion_builder_container class="nhq-locked"]
   ...content...
[/fusion_builder_container]
```

### Step 2: Assign User Roles

Users with the `affiliate_contributor` role will automatically have restrictions applied.

To create this role, use PublishPress Capabilities:
1. Go to **PublishPress → Capabilities**
2. Click **Add Role**
3. Name: `Affiliate Contributor`
4. Copy capabilities from: `Contributor` or `Editor`
5. Customize permissions as needed
6. Save

### Step 3: Test the Restrictions

1. Create a test page with a locked container
2. Log in as a user with `affiliate_contributor` role
3. Edit the page - locked container should have:
   - Yellow border with diagonal stripes
   - Lock icon with message
   - Hidden edit controls
   - Disabled interactions
4. Try to modify locked content and save
5. Changes should be rejected with an error message

## Configuration

### Settings Page

Go to **Settings → Fusion Restrictions** to view:

- **Current Status**: Shows your role and if restrictions apply to you
- **Restricted Roles**: Currently set to `affiliate_contributor`
- **Locked CSS Classes**: `nhq-locked`, `nhq-critical`
- **Contact Email**: Email shown in lock messages (default: marketing@hopeignites.org)
- **Instructions**: Setup and testing guide

### Customizing for Your Organization

Edit the main plugin file to customize:

**Change Restricted Roles** (Line 33):
```php
private $restricted_roles = array( 'affiliate_contributor', 'another_role' );
```

**Change Locked Classes** (Line 38):
```php
private $locked_classes = array( 'nhq-locked', 'nhq-critical', 'custom-class' );
```

**Change Contact Email** (Line 267):
Default is `marketing@hopeignites.org`, or change via Settings page.

## Testing Checklist

### ✅ Pre-Test Setup
- [ ] Plugin activated on staging site
- [ ] Avada theme is active
- [ ] Created `affiliate_contributor` role (via PublishPress Capabilities)
- [ ] Created test user with this role

### ✅ Test 1: Visual Indicators
- [ ] Login as admin
- [ ] Create test page with Fusion Builder
- [ ] Add container with CSS class `nhq-locked`
- [ ] Save page
- [ ] Login as affiliate_contributor
- [ ] Edit the page
- [ ] Verify locked container has:
  - [ ] Yellow border
  - [ ] Lock message
  - [ ] Hidden edit controls

### ✅ Test 2: Edit Prevention
- [ ] As affiliate_contributor, try to click locked container
- [ ] Should show alert: "This section is managed by NHQ..."
- [ ] Edit controls should be hidden/disabled
- [ ] Try to save page anyway
- [ ] Confirm no changes were made to locked container

### ✅ Test 3: Server-Side Validation
- [ ] As affiliate_contributor, use browser inspector to bypass JavaScript
- [ ] Manually edit locked container content via inspector
- [ ] Try to save page
- [ ] Verify changes are rejected
- [ ] Old content should be restored
- [ ] Error message should appear

### ✅ Test 4: Non-Locked Content
- [ ] As affiliate_contributor, edit non-locked containers
- [ ] Make changes to regular content
- [ ] Save page
- [ ] Verify non-locked changes ARE saved successfully

### ✅ Test 5: Admin Access
- [ ] Login as Administrator
- [ ] Edit same page
- [ ] Verify NO restrictions apply
- [ ] Should be able to edit everything
- [ ] No lock icons or borders visible

### ✅ Test 6: Multiple Locked Classes
- [ ] Create container with `nhq-locked`
- [ ] Create another with `nhq-critical`
- [ ] Verify both are locked for affiliate_contributor
- [ ] Verify both allow editing for admin

## Troubleshooting

### Restrictions Not Applying

**Problem:** Affiliate Contributors can still edit locked containers

**Solutions:**
1. Clear browser cache and hard refresh (Ctrl+Shift+R)
2. Check user role is exactly `affiliate_contributor` (case-sensitive)
3. Verify CSS class is exactly `nhq-locked` or `nhq-critical`
4. Check JavaScript console for errors (F12 → Console)
5. Ensure plugin is activated

### JavaScript Not Loading

**Problem:** No visual indicators appear

**Solutions:**
1. Check if `/assets/fusion-restrictions.js` file exists
2. Verify file permissions (should be readable)
3. Check browser console for 404 errors
4. Try deactivating and reactivating plugin

### Save Validation Not Working

**Problem:** Locked content can be modified and saved

**Solutions:**
1. Check PHP error logs for issues
2. Verify `save_post` hook is firing
3. Ensure user role check is correct
4. Test with debug mode: Add to wp-config.php:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

### Containers Not Detected

**Problem:** Plugin doesn't recognize locked containers

**Solutions:**
1. Verify Avada shortcode structure is correct
2. Check CSS class has no typos
3. Try rebuilding Fusion Builder cache (Avada → Options → Performance)
4. Ensure content is saved as shortcodes, not HTML

## Multisite Considerations

### Network Activation
- Can be network-activated to apply across all sites
- Settings are site-specific
- Works with network-wide roles

### Per-Site Activation
- Can activate individually on each site
- Better for testing on staging sites first

## Compatibility

**Compatible With:**
- ✅ WordPress 5.5+
- ✅ Avada 7.0+
- ✅ WordPress Multisite
- ✅ PublishPress Capabilities
- ✅ PublishPress Permissions Pro
- ✅ WP Activity Log

**Not Compatible With:**
- ❌ Gutenberg Block Editor (use Avada Fusion Builder)
- ❌ Classic Editor without Fusion Builder
- ❌ Page builders other than Fusion Builder

## Support & Contact

**For Hope Ignites Team:**
- Primary Contact: Technology Services
- Email: technology@hopeignites.org
- Documentation: See project wiki

**For Plugin Issues:**
- Check WordPress debug logs
- Review browser console for JavaScript errors
- Contact your developer with error details

## Changelog

### Version 1.0.0 - 2025-01-20
- Initial release
- Container locking by CSS class
- Role-based restrictions
- Visual indicators
- Server-side validation
- Settings page
- Multisite support

## License

This plugin is licensed under GPL-2.0+. You are free to use, modify, and distribute this plugin for Hope Ignites' internal use.

## Credits

Developed for Hope Ignites by the Technology Services team.
Based on Avada Fusion Builder architecture and WordPress plugin standards.

---

**Last Updated:** January 2025  
**Plugin URI:** https://hopeignites.org  
**Author:** Hope Ignites Technology Team
