# Agent Custom Navigation System - Implementation Guide

## Overview
This guide documents the complete custom navigation and approval workflow system for agents in the Malisafi MLS platform.

## What Has Been Implemented

### 1. Custom Agent Navigation Bar (`class-agent-navigation.php`)

**Features:**
- ✅ Custom branded navigation bar with gradient design
- ✅ Hides WordPress admin bar completely for agents
- ✅ Removes all default WordPress admin menus
- ✅ Shows only agent-relevant links:
  - My Dashboard
  - My Properties
  - Add Property
  - My Profile
  - Leads
- ✅ User info and logout button
- ✅ Fully responsive design
- ✅ Auto-redirects from default WP dashboard to agent dashboard

**Who It Affects:**
- `malisafi_agent_basic`
- `malisafi_agent_premium`
- `malisafi_owner`
- `malisafi_developer`

**Who It Doesn't Affect:**
- `administrator`
- `malisafi_moderator`

### 2. Property Approval Workflow (`class-property-approval-workflow.php`)

**Features:**
- ✅ All agent-created properties start as "Pending Approval"
- ✅ Edited properties automatically return to "Pending Approval"
- ✅ Admins and moderators can publish directly
- ✅ Optional setting to allow premium agents auto-publish
- ✅ Status tracking and notices
- ✅ Clean separation of roles

**Workflow:**
1. Agent creates property → Status: Pending
2. Admin/Moderator approves → Status: Published
3. Agent edits published property → Status: Pending (requires re-approval)
4. Admin/Moderator reviews → Status: Published again

### 3. Integration with Core System

**Files Modified:**
- `includes/class-core.php` - Added initialization of new classes
- `admin/class-property-submit.php` - Updated status logic
- `assets/css/agent-navigation.css` - New styles for navigation and status badges

**New Files Created:**
- `includes/class-agent-navigation.php` - Navigation system
- `includes/class-property-approval-workflow.php` - Approval workflow
- `assets/css/agent-navigation.css` - Styling

## How It Works

### Navigation System

#### For Agents:
```
Login → Redirected to My Dashboard
│
├─ Custom Navigation Bar (always visible)
│  ├─ My Dashboard (stats, recent properties)
│  ├─ My Properties (list of all their properties)
│  ├─ Add Property (create new listing)
│  ├─ My Profile (edit agent profile)
│  └─ Leads (inquiries and contacts)
│
└─ No access to:
   - WordPress Dashboard
   - Posts, Pages, Media (except through wp_enqueue_media)
   - Comments, Appearance, Plugins, Users, Tools, Settings
```

#### For Admins/Moderators:
```
Login → Full WordPress Admin
│
├─ Standard WordPress Admin Bar
├─ All WordPress Menus
└─ Additional Malisafi Menus
   ├─ Malisafi Dashboard
   ├─ Properties (with moderation queue)
   ├─ Agents Management
   └─ Settings
```

### Property Status Workflow

```
┌─────────────────────────────────────────────────────────┐
│                    AGENT ACTIONS                         │
└─────────────────────────────────────────────────────────┘
                            │
                            ▼
              ┌──────────────────────────┐
              │  Create New Property      │
              └──────────────────────────┘
                            │
                            ▼
              ┌──────────────────────────┐
              │  Status: PENDING          │
              │  (awaiting approval)      │
              └──────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────┐
│              ADMIN/MODERATOR REVIEW                      │
└─────────────────────────────────────────────────────────┘
                            │
                    ┌───────┴───────┐
                    ▼               ▼
            ┌──────────┐     ┌──────────┐
            │ APPROVE  │     │ REJECT   │
            └──────────┘     └──────────┘
                    │               │
                    ▼               ▼
        ┌──────────────┐   ┌──────────────┐
        │   PUBLISHED  │   │    TRASH     │
        └──────────────┘   └──────────────┘
                    │
                    │ ◄─── Agent Edits Property
                    ▼
        ┌──────────────────┐
        │  PENDING AGAIN    │
        │ (requires review) │
        └──────────────────┘
```

## Testing Instructions

### Test 1: Agent Login Experience
1. **Login as agent** (`malisafi_agent_basic` or `malisafi_agent_premium`)
2. **Verify:**
   - Custom navigation bar appears at top
   - No WordPress admin bar visible
   - No default WordPress menus (Posts, Pages, etc.)
   - Only see: My Dashboard, My Properties, Add Property, My Profile, Leads
   - Redirected to agent dashboard (not WP dashboard)

### Test 2: Property Creation
1. **As agent**, go to "Add Property"
2. Fill in property details and images
3. **Submit property**
4. **Verify:**
   - Property status is "Pending Approval"
   - Notice appears: "Property submitted for approval"
   - Property NOT visible on public site
5. **As admin**, go to Properties → Pending
6. **Approve property**
7. **Verify:**
   - Status changes to "Published"
   - Property now visible on public site

### Test 3: Property Editing Workflow
1. **As admin**, verify property is published
2. **As agent**, go to "My Properties"
3. **Edit a published property** (change title, price, or description)
4. **Save changes**
5. **Verify:**
   - Status automatically changes to "Pending Approval"
   - Notice: "Property updated - pending approval"
   - Changes NOT visible on public site yet
   - Original version still showing publicly
6. **As admin**, review and approve again
7. **Verify:**
   - Status returns to "Published"
   - Updated version now visible

### Test 4: Admin Experience
1. **Login as administrator**
2. **Verify:**
   - Standard WordPress admin bar present
   - All WordPress menus available
   - Can access Malisafi menus
   - Can publish properties directly (no pending status)
   - Can approve agent properties

### Test 5: Moderator Experience
1. **Login as moderator** (`malisafi_moderator`)
2. **Verify:**
   - Standard admin experience
   - Can approve properties
   - Can publish directly
   - Cannot access all admin settings

## Configuration Options

### Allow Premium Agents Auto-Publish (Optional)

By default, ALL agents (including premium) must go through approval. To allow premium agents to publish directly:

```php
// Add to wp-config.php or via settings
update_option('malisafi_allow_premium_auto_publish', true);
```

**Default:** `false` (all agents require approval)

## Role Permissions Summary

| Feature | Agent Basic | Agent Premium | Owner | Developer | Moderator | Admin |
|---------|-------------|---------------|-------|-----------|-----------|-------|
| Custom Navigation Bar | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ |
| WordPress Admin Bar | ✗ | ✗ | ✗ | ✗ | ✓ | ✓ |
| Create Properties | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Auto-Publish New | ✗ | ✗* | ✗ | ✗ | ✓ | ✓ |
| Edit Own Properties | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Edit Others' Properties | ✗ | ✗ | ✗ | ✗ | ✓ | ✓ |
| Approve Properties | ✗ | ✗ | ✗ | ✗ | ✓ | ✓ |
| Manage Settings | ✗ | ✗ | ✗ | ✗ | ✗ | ✓ |

*Can be enabled via `malisafi_allow_premium_auto_publish` option

## CSS Customization

The navigation bar uses a purple gradient by default. To customize:

**File:** `assets/css/agent-navigation.css`

```css
.malisafi-agent-navigation {
    /* Change gradient colors */
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    
    /* Or use solid color */
    /* background: #1e40af; */
}
```

## Troubleshooting

### Issue: Agent still sees WordPress admin bar
**Solution:** Clear browser cache and ensure user has ONLY agent role (not also admin)

### Issue: Property stays published after agent edits
**Solution:** Check `class-property-approval-workflow.php` is loaded in `class-core.php`

### Issue: Navigation bar not appearing
**Solution:** 
1. Check user role is agent (not admin/moderator)
2. Verify `class-agent-navigation.php` is initialized
3. Clear WordPress cache

### Issue: Agents can't upload images
**Solution:** Ensure `wp_enqueue_media()` is called in agent dashboard (already fixed)

## Security Considerations

✅ **Implemented:**
- Agents cannot access admin-level functions
- Agents cannot publish directly (requires approval)
- Agents can only edit their own properties
- All status changes are tracked
- Proper capability checks on all actions

✅ **Role Separation:**
- Complete UI separation between agents and admins
- Agents never see WordPress admin interface
- Clean, branded experience for agents

## Future Enhancements (Optional)

- [ ] Email notifications when property is approved/rejected
- [ ] Bulk approval interface for moderators
- [ ] Property revision history
- [ ] Agent activity logs
- [ ] Custom rejection reasons with feedback
- [ ] Property preview before approval
- [ ] Analytics dashboard for agents

## Support

For issues or questions:
1. Check this guide
2. Review the code comments in the new files
3. Test with different user roles
4. Verify all files are properly loaded

---

**Last Updated:** 2026-01-12
**Version:** 1.0.0
**Status:** ✅ Production Ready
