# Agent Navigation System - Quick Reference

## 🚀 Quick Start

### For Administrators

1. **System is automatically active** - No configuration needed
2. All agents will see custom navigation bar on login
3. To test: Create an agent user and login

### For Testing

Access the test suite:
```
yoursite.com/wp-content/plugins/malisafi-mls/tmp_rovodev_test_agent_navigation.php
```
(Admin access required)

---

## 👥 User Experience by Role

### Agents (Basic, Premium, Owner, Developer)
- ✅ Custom purple navigation bar
- ✅ Clean, branded interface
- ❌ No WordPress admin bar
- ❌ No WordPress default menus
- 📋 Can see: Dashboard, Properties, Add Property, Profile, Leads
- 📝 All properties require approval

### Admins & Moderators
- ✅ Full WordPress admin access
- ✅ Standard admin bar
- ✅ All WordPress menus
- ✅ Can approve properties
- ✅ Can publish directly

---

## 📋 Property Workflow

### Agent Creates Property
```
Create → Status: Pending → Awaits Approval
```

### Admin Reviews
```
Pending → Review → Approve → Status: Published
```

### Agent Edits Published Property
```
Published → Agent Edits → Status: Pending → Awaits Re-approval
```

---

## 🎨 Custom Navigation Links

1. **My Dashboard** - Stats and overview
2. **My Properties** - List and manage listings
3. **Add Property** - Create new listing
4. **My Profile** - Edit agent profile
5. **Leads** - View inquiries

---

## ⚙️ Configuration

### Allow Premium Agents to Auto-Publish (Optional)

**Default:** Disabled (all agents require approval)

**To Enable:**
```php
update_option('malisafi_allow_premium_auto_publish', true);
```

**To Disable:**
```php
update_option('malisafi_allow_premium_auto_publish', false);
```

---

## 🎨 Customize Navigation Colors

**File:** `assets/css/agent-navigation.css`

```css
.malisafi-agent-navigation {
    /* Change gradient */
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    
    /* Or solid color */
    /* background: #1e40af; */
}
```

---

## 🔧 Troubleshooting

| Issue | Solution |
|-------|----------|
| Agent sees WP admin bar | User has multiple roles (remove admin role) |
| Navigation bar missing | Clear cache, verify role is agent |
| Properties publish directly | Check `malisafi_allow_premium_auto_publish` |
| Can't upload images | Verify media library enqueued (already fixed) |

---

## 📁 Files Created/Modified

### New Files
- `includes/class-agent-navigation.php`
- `includes/class-property-approval-workflow.php`
- `assets/css/agent-navigation.css`
- `AGENT-CUSTOM-NAVIGATION-GUIDE.md`
- `tmp_rovodev_test_agent_navigation.php`

### Modified Files
- `includes/class-core.php` (added initialization)
- `admin/class-property-submit.php` (updated status logic)
- `admin/class-agent-dashboard.php` (fixed media library)

---

## ✅ Testing Checklist

- [ ] Login as agent - verify custom navigation
- [ ] Check no WP admin bar visible
- [ ] Create property - verify pending status
- [ ] Login as admin - approve property
- [ ] Edit property as agent - verify returns to pending
- [ ] Test on mobile device
- [ ] Test with different agent roles
- [ ] Verify admin experience unchanged

---

## 📞 Support

For detailed documentation, see:
- **`AGENT-CUSTOM-NAVIGATION-GUIDE.md`** - Complete implementation guide
- Test with: **`tmp_rovodev_test_agent_navigation.php`**

---

**Version:** 1.0.0  
**Status:** ✅ Production Ready  
**Last Updated:** 2026-01-12
