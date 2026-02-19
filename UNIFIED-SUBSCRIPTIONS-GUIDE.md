# Unified Plans & Subscriptions System Guide

## Overview

A single, comprehensive admin interface for managing all aspects of subscriptions and billing in the Malisafi MLS plugin.

## What Changed

### Before
- **Two separate pages**: "Subscriptions" and "Plans" pages with overlapping functionality
- **Confusing navigation**: Users had to jump between pages
- **Unclear status**: Hard to see if Stripe was configured
- **Pricing page error**: Showed "not available" message when Stripe wasn't configured

### After
- **One unified page**: "Plans & Subscriptions" with everything in one place
- **Clear tabs**: Overview, Stripe Settings, Plans, Subscriptions, Assign
- **Visual status**: See at a glance if Stripe is configured and working
- **Easy setup**: Step-by-step configuration in logical tabs

## How to Access

Go to: **WordPress Admin → Malisafi → Plans & Subscriptions**

## Features

### 1. Overview Tab
**What it shows:**
- Stripe configuration status (Configured/Not Configured, Test/Live Mode)
- Number of available plans
- Active subscriptions count
- Public pricing page status
- Quick start guide

**Use it to:**
- Get a quick snapshot of your subscription system
- Check if everything is set up correctly
- Access the pricing page

### 2. Stripe Settings Tab
**What you can do:**
- Switch between Test Mode and Live Mode
- Enter Test API keys for development
- Enter Live API keys for production
- Configure webhook settings
- View your webhook URL

**How to configure:**
1. Go to [https://dashboard.stripe.com/apikeys](https://dashboard.stripe.com/apikeys)
2. Copy your **Publishable Key** (starts with `pk_test_` or `pk_live_`)
3. Copy your **Secret Key** (starts with `sk_test_` or `sk_live_`)
4. Paste them into the corresponding fields
5. For webhooks:
   - Go to [https://dashboard.stripe.com/webhooks](https://dashboard.stripe.com/webhooks)
   - Add the webhook URL shown in the admin (looks like: `https://yoursite.com/wp-json/malisafi/v1/stripe-webhook`)
   - Copy the **Signing Secret** (starts with `whsec_`)
   - Paste it into the Webhook Signing Secret field
6. Click **Save Stripe Settings**

**Test vs Live Mode:**
- **Test Mode**: Use for development - no real charges, test cards work
- **Live Mode**: Use for production - real charges, actual customer payments
- Always start with Test Mode until you're ready to go live

### 3. Subscription Plans Tab
**What you can do:**
- Create and manage subscription plans
- Set pricing, currency (KES/USD), and billing interval
- Configure property limits and featured listings
- Enable/disable plans
- Add Stripe Price IDs
- Define plan features

**Quick Setup:**
1. Click **Initialize Default Plans** button (if no plans exist)
2. This creates 5 pre-configured plans:
   - **Client (Free)** - Basic browsing
   - **Agent Basic (KSh 2,999/mo)** - 10 listings
   - **Agent Premium (KSh 9,999/mo)** - Unlimited listings
   - **Property Owner (KSh 1,999/mo)** - 3 listings
   - **Developer (KSh 19,999/mo)** - Unlimited projects + API
3. Adjust prices, limits, and features as needed
4. Create matching products in Stripe Dashboard
5. Copy Stripe Price IDs back into each plan
6. Click **Save All Plans**

**Creating Stripe Products:**
1. Go to [https://dashboard.stripe.com/products](https://dashboard.stripe.com/products)
2. Click **Add product**
3. Enter product name (e.g., "Agent Basic")
4. Set pricing to match your plan (e.g., KSh 2,999)
5. Select billing interval (monthly/yearly)
6. Click **Add product**
7. Copy the **Price ID** (starts with `price_`)
8. Paste it into the corresponding plan in this admin page

### 4. Active Subscriptions Tab
**What you can see:**
- List of all active subscriptions
- User details (name, email)
- Plan type
- Status
- Start and end dates
- Stripe subscription ID

**Use it to:**
- Monitor current subscriptions
- See who is subscribed to what plan
- Check subscription expiration dates
- View Stripe IDs for reference

### 5. Assign Subscription Tab
**What you can do:**
- Manually assign subscriptions to users
- Bypass Stripe payment
- Set custom expiration dates
- Useful for:
  - Promotional access
  - Testing
  - Team members
  - Special arrangements
  - Comped subscriptions

**How to assign:**
1. Select a user from dropdown
2. Choose the subscription plan
3. Set the end date (when it expires)
4. Click **Assign Subscription**

**What happens:**
- User role is changed to match the plan
- Property limits are applied
- Subscription is added to database
- User gains access immediately
- **Note**: These subscriptions don't auto-renew

## User Flow (Public Side)

### How Users Subscribe

1. User visits the **Pricing Page** (`/pricing-plans/` by default)
2. They see available plans with pricing and features
3. Click "Choose Plan" button
4. Redirected to Stripe Checkout page
5. Enter payment details
6. Payment processed by Stripe
7. Webhook notifies your site
8. User subscription is activated
9. User role is updated
10. User can now access features

### Requirements for Public Subscriptions
- ✅ Stripe API keys configured (Stripe Settings tab)
- ✅ At least one active plan exists (Subscription Plans tab)
- ✅ Stripe Price IDs added to plans (Subscription Plans tab)
- ✅ Stripe products created in Stripe Dashboard
- ✅ Webhook configured in Stripe Dashboard

## Troubleshooting

### Pricing Page Shows "Not Available" Error

**Problem**: Public pricing page displays "Subscriptions are not available at the moment. Please check back later."

**Solution**:
1. Go to **Plans & Subscriptions → Stripe Settings**
2. Enter your Stripe API keys (publishable and secret)
3. Click **Save Stripe Settings**
4. Check the Overview tab - status should show "Configured"
5. Visit pricing page again - plans should now display

### Plans Not Showing in Pricing Page

**Checklist**:
- [ ] Stripe is configured (check Overview tab)
- [ ] At least one plan exists (check Subscription Plans tab)
- [ ] Plans are marked as "Active" (checkboxes in plan editor)
- [ ] Stripe Price IDs are added to plans
- [ ] Matching products exist in Stripe Dashboard

### Payment Not Working

**Test Mode Issues**:
- Use test card: `4242 4242 4242 4242`
- Any future expiration date
- Any 3-digit CVC
- Make sure you're using Test API keys

**Live Mode Issues**:
- Verify Live API keys are correct
- Check Stripe Dashboard for payment errors
- Ensure webhook is configured
- Check webhook logs in Stripe Dashboard

### User Subscription Not Activating

**Check**:
1. Go to Stripe Dashboard → Developers → Webhooks
2. Verify webhook is configured with your site URL
3. Check webhook logs for errors
4. Verify webhook signing secret is correct in admin
5. Check WordPress site can receive webhooks (not blocked by firewall)

### How to Test Without Payment

Use the **Assign Subscription** tab:
1. Select test user
2. Choose a plan
3. Set end date
4. Click Assign
5. Test user now has subscription without payment

## File Locations

**Admin Template**: 
- `admin/templates/unified-subscriptions.php` - The unified interface

**Admin Class**:
- `admin/class-admin-dashboard.php` - Menu registration and render method

**Stripe Integration**:
- `includes/class-stripe.php` - API handling and configuration checks

**Public Template**:
- `templates/pricing-page.php` - Public-facing pricing display

**Pages System**:
- `includes/class-page-manager.php` - Auto-creates pricing page

## Database Tables

**Subscriptions**: `wp_mf_subscriptions`
- Stores active and past subscriptions
- Links users to plans
- Tracks Stripe subscription IDs
- Records start/end dates

**Plans**: WordPress Options
- Stored in `malisafi_mls_plans` option
- Array of plan configurations
- Can be edited in admin

**Stripe Settings**: WordPress Options
- `malisafi_stripe_mode` - test or live
- `malisafi_stripe_test_publishable_key`
- `malisafi_stripe_test_secret_key`
- `malisafi_stripe_live_publishable_key`
- `malisafi_stripe_live_secret_key`
- `malisafi_stripe_webhook_secret`

## Best Practices

### Development Phase
1. Use Test Mode only
2. Use test API keys
3. Test with test cards
4. Verify webhook works
5. Test all user flows
6. Check subscription activation
7. Test plan limits

### Going Live Checklist
- [ ] All testing completed in Test Mode
- [ ] Stripe account fully verified
- [ ] Live API keys obtained
- [ ] Plans configured with correct pricing
- [ ] Stripe products created
- [ ] Price IDs added to plans
- [ ] Switch to Live Mode in admin
- [ ] Save Live API keys
- [ ] Test one real transaction
- [ ] Monitor webhooks
- [ ] Verify subscription activates

### Security
- **Never share Secret Keys**
- Keep webhook signing secret private
- Use HTTPS on production
- Regularly check Stripe Dashboard for issues
- Monitor failed payments
- Check webhook logs regularly

## Support Resources

**Stripe Documentation**:
- [Stripe Dashboard](https://dashboard.stripe.com)
- [API Keys Guide](https://stripe.com/docs/keys)
- [Webhooks Guide](https://stripe.com/docs/webhooks)
- [Testing Cards](https://stripe.com/docs/testing)

**Plugin Documentation**:
- See `STRIPE_SETUP_GUIDE.md` for detailed Stripe setup
- See `DESIGN-SYSTEM.md` for styling customization
- See `PAGES-SYSTEM-GUIDE.md` for page management

## Quick Reference

### Common Tasks

**Add a new plan**:
1. Go to Subscription Plans tab
2. Scroll to bottom
3. Adjust existing plan or add via code
4. Create matching Stripe product
5. Save

**Disable a plan**:
1. Go to Subscription Plans tab
2. Uncheck "Active" for that plan
3. Click Save All Plans

**Give free access to user**:
1. Go to Assign Subscription tab
2. Select user and plan
3. Set far future date
4. Click Assign

**Check subscription status**:
1. Go to Active Subscriptions tab
2. Find user in list
3. View details

**Switch to Live Mode**:
1. Go to Stripe Settings tab
2. Change mode to "Live"
3. Enter Live API keys
4. Save settings

## Summary

The unified Plans & Subscriptions system provides:
- ✅ Single interface for all subscription management
- ✅ Clear visual status indicators
- ✅ Easy Stripe configuration
- ✅ Flexible plan management
- ✅ Manual subscription assignment
- ✅ Real-time subscription monitoring
- ✅ Step-by-step setup guidance

Users can now:
- Subscribe via public pricing page
- Choose from active plans
- Pay with Stripe Checkout
- Get instant access after payment

Admins can:
- Configure Stripe in one place
- Create and manage plans
- Monitor active subscriptions
- Manually assign subscriptions
- See system status at a glance
