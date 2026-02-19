# Production Ready Checklist

## ✅ Completed Production Prep (2026-02-19)

### Code Cleanup
- ✅ **Removed debug notices** from admin inquiries page
- ✅ **Cleaned verbose logging** from Property Actions AJAX class
  - Removed per-request logging on every page load
  - Removed detailed inquiry submission debug logs (now logged only when WP_DEBUG is enabled)
  - Kept critical error logs for production troubleshooting
- ✅ **Removed console.log** from property submission wizard template
- ✅ **Deleted all test files** (test-*.php, debug-*.php, verify-*.php, fix-*.php)

### Database & Features
- ✅ **Inquiries system fully functional**
  - Admin can view all inquiries at `/wp-admin/admin.php?page=malisafi-inquiries`
  - Agent inquiry viewing via `[malisafi_agent_inquiries]` shortcode
  - Email notification tracking (sent/failed status)
  - Search and filtering capabilities
- ✅ **Database schema complete** with all required columns
- ✅ **WP_List_Table properly configured** with column headers

### File Structure
- ✅ **No development artifacts** remaining in root directory
- ✅ **All production files** properly organized

## ⚠️ Optional Optimizations

### JavaScript Console Logs
The following files contain console.log statements for debugging. These are non-critical but can be removed if desired:

**registration-form.js** (~11 console.log statements):
- Mobile device detection logging
- Form validation step logging
- Field validation logging
- Keep: `console.error()` calls for error reporting

**Location**: `assets/js/registration-form.js`

**Action Needed**: 
- Option 1: Leave as-is (logs only appear in browser console, not visible to users)
- Option 2: Remove manually or create minified version without console statements
- Option 3: Wrap in `if (window.malisafiDebug)` condition

### Error Logging Strategy
Current production behavior:
- ✅ Critical errors (nonce failures, database errors) → Always logged
- ✅ Verbose debug info (request details, query dumps) → Only when WP_DEBUG enabled
- ✅ Inquiry submission tracking → Logged for troubleshooting

**Recommendation**: Keep current setup. Error logs are essential for production support.

## 🔧 Pre-Deployment Checklist

### WordPress Settings
- [ ] Set `WP_DEBUG` to `false` in wp-config.php
- [ ] Set `WP_DEBUG_LOG` to `false` (or keep true for error logging)
- [ ] Set `WP_DEBUG_DISPLAY` to `false`
- [ ] Enable object caching if available
- [ ] Clear all transients

### Plugin Settings
- [ ] Configure SMTP for email notifications (currently showing email_failed)
- [ ] Test inquiry submission → agent notification workflow
- [ ] Verify Stripe webhook is active and receiving events
- [ ] Check subscription plans are properly configured
- [ ] Test property submission form from agent account
- [ ] Verify featured image uploads work
- [ ] Test property search filters

### Security
- [ ] Ensure all nonce verifications are in place ✅
- [ ] Check capability checks on admin pages ✅
- [ ] Verify reCAPTCHA configured for inquiry forms
- [ ] Test role permissions (agent_basic, agent_premium, owner, etc.)
- [ ] Review file upload restrictions

### Performance
- [ ] Consider minifying JavaScript files
- [ ] Enable CSS/JS concatenation
- [ ] Test with caching plugin active
- [ ] Optimize database queries if needed
- [ ] Check image optimization for uploaded properties

### Testing Scenarios

#### 1. Inquiry Submission
- [ ] Client submits inquiry on property page
- [ ] Inquiry appears in admin list
- [ ] Agent receives email notification
- [ ] Agent can view inquiry in their dashboard
- [ ] Status can be updated (new → read → replied → closed)

#### 2. Property Submission
- [ ] Agent (basic) submits property → Status: Pending
- [ ] Agent (premium) submits property → Status: Published
- [ ] Moderator can approve/reject pending properties
- [ ] Featured images upload correctly
- [ ] Gallery images upload correctly
- [ ] Kenya location fields (county, setting) work correctly

#### 3. User Registration
- [ ] Client registration redirects to client dashboard
- [ ] Agent registration redirects to agent dashboard
- [ ] Email verification works (if enabled)
- [ ] Account types assigned correctly

#### 4. Stripe Subscriptions
- [ ] Agent can upgrade from basic to premium
- [ ] Webhook receives checkout.session.completed event
- [ ] User role updated after successful payment
- [ ] Subscription recorded in wp_mf_subscriptions table

## 🚀 Deployment Steps

1. **Backup Current Site**
   ```bash
   # Database
   wp db export backup-$(date +%Y%m%d).sql
   
   # Files
   zip -r backup-$(date +%Y%m%d).zip wp-content/plugins/malisafi
   ```

2. **Upload Updated Plugin**
   - Via FTP: Upload entire `malisafi` folder
   - Via Git: Push to repository and pull on server
   - Via WP Admin: Deactivate → Delete → Upload → Activate

3. **Flush Rewrite Rules**
   - Go to Settings → Permalinks
   - Click "Save Changes" (even without changes)

4. **Test Critical Paths**
   - Submit test inquiry
   - Submit test property
   - Register test agent account
   - Process test Stripe payment (use test card)

5. **Monitor Error Logs**
   - Check `wp-content/debug.log` for first 24 hours
   - Watch Stripe webhook events
   - Monitor inquiry email delivery

## 📊 Post-Launch Monitoring

### First 24 Hours
- [ ] Check error logs every 4-6 hours
- [ ] Monitor inquiry submissions
- [ ] Watch email notification success rate
- [ ] Check Stripe webhook success rate

### First Week
- [ ] Review user registration success rate
- [ ] Monitor property submission flow
- [ ] Check database performance
- [ ] Analyze any reported issues

### Ongoing
- [ ] Set up automated backups
- [ ] Configure uptime monitoring
- [ ] Review analytics weekly
- [ ] Update Stripe API keys if needed

## 📝 Known Behavior

### Email Notifications
Currently showing "email_failed" status because SMTP is not configured. This is expected and will be resolved when SMTP settings are added.

**Fix**: Configure SMTP plugin or add SMTP credentials to wp-config.php

### Debug Mode
When `WP_DEBUG` is enabled, additional detailed logs will appear in:
- Browser console (JavaScript errors)
- `wp-content/debug.log` (PHP errors and debugging info)
- Stripe Dashboard (webhook events)

## 🔗 Critical URLs to Test

- Property Search: `/properties/`
- Property Submit: `/add-property/`
- Agent Dashboard: `/agent-dashboard/`
- Client Dashboard: `/client-dashboard/`
- Registration: `/register/`
- Login: `/login/`
- Pricing: `/pricing/`
- Admin Inquiries: `/wp-admin/admin.php?page=malisafi-inquiries`

## 🆘 Rollback Plan

If issues occur after deployment:

1. **Deactivate Plugin**
   - Go to Plugins → Deactivate Malisafi MLS

2. **Restore Backup**
   ```bash
   # Restore database
   wp db import backup-YYYYMMDD.sql
   
   # Restore files
   unzip backup-YYYYMMDD.zip -d wp-content/plugins/
   ```

3. **Clear Cache**
   - Clear WordPress cache
   - Clear browser cache
   - Clear CDN cache if applicable

4. **Reactivate Plugin**
   - Go to Plugins → Activate Malisafi MLS

---

**Status**: ✅ Ready for production deployment
**Last Updated**: 2026-02-19
**Version**: 1.0.0
