# Featured Properties System - User Guide

## Overview
The Featured Properties system allows agents to promote their properties for better visibility. Administrators can also manually feature properties.

## Key Features

### For Agents
- Request featured status for properties
- Pay KSH 500 for 30 days of featured placement
- Automatic expiration after 30 days
- Email notifications on expiry
- Featured badge on property listings

### For Administrators
- Manually feature/unfeature properties
- Set custom expiration dates
- Quick toggle from properties list
- Track payment information
- No charge for admin-featured properties

## Cost & Duration
- **Cost**: KSH 500
- **Duration**: 30 days
- **Payment Methods**: Stripe (M-Pesa coming soon)

## Shortcode Usage

### Basic Usage
```
[malisafi_featured_properties]
```

### With All Options
```
[malisafi_featured_properties count="6" columns="3" orderby="date" order="DESC" show_excerpt="yes" show_features="yes"]
```

### Shortcode Attributes

| Attribute | Default | Options | Description |
|-----------|---------|---------|-------------|
| `count` | 6 | Any number | Number of featured properties to display |
| `columns` | 3 | 1-4 | Number of columns in the grid |
| `rows` | 0 | Any number | Number of rows (0 = auto) |
| `orderby` | date | date, title, rand, price | Sort properties by |
| `order` | DESC | DESC, ASC | Sort order (descending/ascending) |
| `show_excerpt` | yes | yes, no | Show property excerpt |
| `show_features` | yes | yes, no | Show beds/baths/area |

### Examples

**Show 8 properties in 4 columns, ordered by price (high to low)**
```
[malisafi_featured_properties count="8" columns="4" orderby="price" order="DESC"]
```

**Show 3 random properties in 1 column**
```
[malisafi_featured_properties count="3" columns="1" orderby="rand"]
```

**Show 12 properties ordered by oldest first**
```
[malisafi_featured_properties count="12" orderby="date" order="ASC"]
```

**Show 6 properties in 2 columns without excerpts**
```
[malisafi_featured_properties count="6" columns="2" show_excerpt="no"]
```

## For Agents

### How to Feature Your Property

1. **Login to Agent Dashboard**
   - Navigate to your agent dashboard
   - View your properties list

2. **Select Property to Feature**
   - Find the property you want to feature
   - Click "Feature This Property" button

3. **Review Information**
   - Modal will show cost (KSH 500)
   - Duration (30 days)
   - Benefits of featuring

4. **Make Payment**
   - Click "Proceed to Payment"
   - Complete Stripe checkout
   - Return to dashboard after successful payment

5. **Confirmation**
   - Property will immediately show featured badge (★)
   - Featured status visible for 30 days
   - Property appears in featured listings

### Benefits of Featuring

- ★ **Top Placement**: Your property appears first in search results
- ⭐ **Featured Badge**: Stands out with gold star badge
- 📈 **Increased Visibility**: Higher views and inquiries
- 🎯 **Homepage Display**: Featured in homepage widget
- ⏱️ **30-Day Duration**: Active promotion for one month

### Managing Featured Properties

**Check Status**:
- View "Featured" column in your properties list
- See days remaining until expiration
- Track when property was featured

**Renewal**:
- Receive email notification 3 days before expiry
- Re-feature after expiration
- Same KSH 500 cost for renewal

## For Administrators

### Manual Feature Control

**From Properties List**:
1. Go to Properties → All Properties
2. See "Featured" column
3. Click "Make Featured" or "Remove" link
4. Instant toggle with AJAX

**From Property Edit Screen**:
1. Edit any property
2. Find "Featured Property" meta box (right sidebar)
3. Check "Mark as Featured"
4. Set custom expiration date (optional)
5. Save property

### Admin Features

**Meta Box Fields**:
- **Featured Checkbox**: Toggle featured status
- **Expiration Date**: Set custom expiry (or leave empty for default 30 days)
- **Featured Info**: View when featured, expiry date, payment ID

**Bulk Actions** (Coming Soon):
- Feature multiple properties at once
- Remove featured status from multiple properties

### Payment Tracking

**View Payment Information**:
- Each featured property shows payment ID
- Admin-featured: `admin-{timestamp}`
- Agent-paid: Stripe transaction ID
- Track all featured transactions

### Settings (Future Enhancement)

Planned admin settings:
- Customize featured cost
- Set default duration
- Enable/disable auto-expiry
- Configure featured badge style

## Technical Details

### Database Fields

Featured properties use these meta fields:
- `_malisafi_featured`: '1' or '0'
- `_malisafi_featured_date`: When featured started
- `_malisafi_featured_expires`: Expiration date (Y-m-d format)
- `_malisafi_featured_payment_id`: Payment/transaction ID

### Cron Jobs

**Daily Check**: `malisafi_check_featured_expiry`
- Runs daily at midnight
- Checks for expired featured properties
- Automatically removes expired status
- Sends notification emails to property owners

### AJAX Handlers

- `malisafi_request_featured`: Request featured status
- `malisafi_process_featured_payment`: Process payment
- `malisafi_admin_toggle_featured`: Admin quick toggle

### Stripe Integration

**Checkout Session**:
- Creates one-time payment session
- Amount: KSH 500 (in cents for Stripe)
- Success URL: Returns to agent dashboard
- Metadata includes property ID and user ID

**Success Handling**:
- Webhook processes successful payment
- Automatically activates featured status
- Sets 30-day expiration
- Records transaction ID

## Styling

### CSS Classes

Use these classes for custom styling:

```css
/* Featured container */
.malisafi-featured-properties { }

/* Grid with columns */
.featured-container { }
.featured-container.featured-col-1 { }
.featured-container.featured-col-2 { }
.featured-container.featured-col-3 { }
.featured-container.featured-col-4 { }

/* Individual property card */
.featured-property { }

/* Featured badge */
.featured-badge { }

/* Property marked as featured in lists */
.property-card.is-featured { }
```

### Color Variables

Uses global Malisafi color variables:
- `--mls-accent`: Featured badge and buttons
- `--mls-dark`: Featured badge background
- `--mls-text-inverse`: Featured badge text

## Troubleshooting

### Property Not Showing as Featured

**Check**:
1. Meta field `_malisafi_featured` is set to '1'
2. Expiration date hasn't passed
3. Property status is "Published"
4. Clear WordPress cache

### Payment Failed

**Solutions**:
1. Check Stripe API keys are configured
2. Verify Stripe account is active
3. Test with Stripe test mode first
4. Check browser console for errors
5. Contact admin for manual featuring

### Featured Status Disappeared

**Possible Reasons**:
1. 30-day period expired (check email notifications)
2. Admin manually removed it
3. Property was unpublished/trashed
4. Database issue (contact support)

## Support & Development

### For Help
- Check plugin documentation: `QUICK_START.md`
- Review Stripe setup: `STRIPE_SETUP_GUIDE.md`
- Contact admin if issues persist

### Upcoming Features
- M-Pesa payment integration
- Featured packages (7 days, 14 days, 30 days, 90 days)
- Bulk featuring for developers
- Featured analytics dashboard
- Featured renewal reminders
- Featured property comparison report

## Examples in Action

### Homepage Featured Widget
```html
<div class="homepage-featured">
    <h2>Featured Properties</h2>
    [malisafi_featured_properties count="4" columns="4" orderby="rand"]
</div>
```

### Sidebar Featured List
```html
<div class="sidebar-widget">
    <h3>Top Featured</h3>
    [malisafi_featured_properties count="3" columns="1" show_excerpt="no"]
</div>
```

### Dedicated Featured Page
```html
<h1>Featured Properties</h1>
<p>Browse our premium featured listings</p>
[malisafi_featured_properties count="12" columns="3" orderby="date" order="DESC"]
```

---

**Last Updated**: December 2025
**Version**: 1.0
