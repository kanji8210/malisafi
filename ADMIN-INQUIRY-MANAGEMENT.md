# Admin Inquiry Management System

## Overview

Complete admin interface to view, manage, and monitor all property inquiries system-wide. Track read/unread status and email notification delivery in real-time.

## Features

### 1. **Comprehensive Inquiry Dashboard**
- View all inquiries from all agents and properties
- Search by client name, email, phone, or message content
- Sort by date, ID, or status
- Bulk actions for efficient management

### 2. **Email Notification Tracking**
- ✅ **Sent Status**: Green checkmark indicates email delivered successfully
- ❌ **Failed Status**: Red X shows email delivery failed
- Hover over icons to see recipient email address
- Filter inquiries by email delivery status

### 3. **Read/Unread Status Monitoring**
- **🔵 New (Unread)**: Inquiry hasn't been viewed by agent yet
- **👁️ Read**: Agent has viewed the inquiry
- **✅ Replied**: Agent responded to client
- **🔒 Closed**: Inquiry marked as resolved
- **⚠️ Email Failed**: Email notification failed to send

### 4. **Advanced Filtering**
- **Read Status Filter**: Show only new/unread inquiries
- **Email Status Filter**: Show only failed/sent notifications
- **Search**: Find inquiries by client details or message text
- **Combine Filters**: Use multiple filters simultaneously

### 5. **Real-Time Statistics**
- **Unread Count**: Number of inquiries needing agent attention
- **Total Count**: All inquiries in system
- **Email Failed Alert**: Highlighted count of delivery failures

## Access

**Location**: WordPress Admin → Malisafi → Inquiries

**URL**: `yoursite.com/wp-admin/admin.php?page=malisafi-inquiries`

**Required Permission**: `manage_malisafi_inquiries` or `manage_options` (Administrator)

## Interface Layout

### Header Section
```
┌─────────────────────────────────────────────────────────────┐
│ Property Inquiries                                          │
├─────────────────────────────────────────────────────────────┤
│ [Search Box] [Read Status ▼] [Email Status ▼] [Filter]     │
│                                                             │
│ Summary: 23 unread | 156 total                             │
│          ⚠️ 3 email notifications failed                    │
└─────────────────────────────────────────────────────────────┘
```

### Data Table Columns

| Column | Description | Example |
|--------|-------------|---------|
| **☑ Checkbox** | Select for bulk actions | ☑ |
| **ID** | Unique inquiry identifier | `1234` |
| **Property** | Property name (clickable link) | `Villa in Westlands` |
| **Client** | Client name or email | `John Doe` |
| **Contact** | Email + phone number | `john@email.com`<br>`+254712345678` |
| **Message** | First 15 words (click View for full) | `I'm interested in viewing...` |
| **Agent** | Assigned agent name | `Jane Smith` |
| **Email** | Notification delivery status | ✅ or ❌ |
| **Status** | Read/replied status badge | 🔵 New |
| **Date** | Submission date + time ago | `Feb 19, 2026 2:30 PM`<br>`5 minutes ago` |

## Actions

### Individual Actions
Located below each inquiry ID:

- **View**: Opens full inquiry details in modal popup
- **Mark Read**: Changes status from "New" to "Read"
- **Delete**: Permanently removes inquiry (with confirmation)

### Bulk Actions
Select multiple inquiries using checkboxes:

1. Select inquiries using ☑ checkboxes
2. Choose action from dropdown at top of table
3. Click "Apply" button

**Available Bulk Actions**:
- **Mark Read**: Mark selected inquiries as read
- **Delete**: Delete selected inquiries (with confirmation)

## Filtering Examples

### Show Only Unread Inquiries
1. Set **Read Status** to "🔵 New (Unread)"
2. Click **Filter**

### Show Failed Email Notifications
1. Set **Email Notification** to "❌ Failed"
2. Click **Filter**

### Find Specific Client
1. Enter client name/email in **Search** box
2. Click **Search**

### Show Unread with Failed Emails
1. Set **Read Status** to "🔵 New (Unread)"
2. Set **Email Notification** to "❌ Failed"
3. Click **Filter**

## Status Workflow

```
┌──────────┐     Agent Views     ┌──────────┐
│   New    │ ─────────────────> │   Read   │
│ (Unread) │                     │          │
└──────────┘                     └──────────┘
                                      │
                                      │ Agent Replies
                                      ▼
┌──────────┐     Resolution     ┌──────────┐
│  Closed  │ <───────────────── │ Replied  │
└──────────┘                     └──────────┘

┌──────────────┐
│ Email Failed │ (Special status when notification fails)
└──────────────┘
```

## Troubleshooting Email Issues

### Problem: Inquiry shows ❌ (Email Failed)

**Steps to Diagnose**:
1. Click **View** to see full inquiry details
2. Check the recipient email address (shown on hover)
3. Navigate to test page: `yoursite.com/wp-content/plugins/malisafi/test-inquiry.php`
4. Run **Test SMTP Email** to verify email configuration
5. Check **Email Logs** tab for detailed error information

**Common Causes**:
- SMTP not configured (install WP Mail SMTP plugin)
- Invalid agent/agency email address
- Email server rate limiting
- Recipient domain blocking

### Problem: All inquiries showing as unread

**Solution**: Agents need to log in and view their inquiries. Admin viewing does NOT change status to "read" - only the assigned agent can mark as read.

### Problem: Can't see inquiries

**Check**:
1. User role has `manage_malisafi_inquiries` capability
2. At least one inquiry exists in database
3. No active filters hiding all results (reset filters)

## Database Schema

### Table: `wp_mf_inquiries`

**Key Fields**:

```sql
inquiry_id INT PRIMARY KEY
property_id INT -- Property post ID
agent_id INT -- Assigned agent user ID
agency_id INT -- Agency ID (if applicable)
client_name VARCHAR(255) -- Client full name
client_email VARCHAR(255) -- Client email
client_phone VARCHAR(20) -- Client phone
message TEXT -- Inquiry message
status ENUM('new', 'read', 'replied', 'closed', 'email_failed')
email_sent BOOLEAN -- TRUE if notification sent successfully
email_recipient VARCHAR(255) -- Agent/agency email that received notification
created_at TIMESTAMP
updated_at TIMESTAMP
```

## Email Notification System

### How It Works

1. **Client submits inquiry** from property page
2. **System determines recipient**:
   - Priority 1: Property agent email
   - Priority 2: Agency email (if agent has no email)
3. **Email sent** via `wp_mail()` with:
   - Subject: "New Property Inquiry: [Property Name]"
   - Body: Client details + message + property link
   - Reply-To: Client email (for easy response)
4. **Status recorded**:
   - `email_sent = 1` + `status = 'new'` if successful
   - `email_sent = 0` + `status = 'email_failed'` if failed
5. **Logged to database**:
   - Stored in `wp_options` table (`malisafi_inquiry_email_log`)
   - Viewable in test page Email Logs tab
   - Also logged to server error_log

### Email Template

```
Subject: New Property Inquiry: Villa in Westlands

Hello Jane Smith,

You have received a new inquiry about your property.

Property: Villa in Westlands
URL: https://yoursite.com/properties/villa-westlands/

Inquirer Details:
Name: John Doe
Email: john@email.com
Phone: +254712345678

Message:
I'm interested in viewing this property this weekend. 
Are you available Saturday afternoon?

Please respond to this inquiry as soon as possible.

Best regards,
Your Site Name Team
```

## Permissions & Security

### Required Capabilities

**View All Inquiries**:
- `manage_malisafi_inquiries` (assigned to Administrator)
- OR `manage_options` (Administrator)

**Mark as Read/Delete**:
- Same as viewing permissions

**Agents View Own Inquiries**:
- Agents can see their own inquiries via agent dashboard
- Use shortcode `[malisafi_agent_inquiries]` for agent-specific view

### Security Features

- **Nonce Verification**: All actions protected with nonces
- **Capability Checks**: Permission verified on every action
- **SQL Injection Protection**: Prepared statements used throughout
- **XSS Prevention**: All output escaped with `esc_html()`, `esc_url()`
- **CSRF Protection**: WordPress hooks validate request origins

## Agent vs Admin Views

### Admin View (This Page)
- See **ALL** inquiries system-wide
- No filtering by agent
- Can manage any inquiry
- See email delivery status
- Access via `admin.php?page=malisafi-inquiries`

### Agent View (Agent Dashboard)
- See **ONLY** their own inquiries
- Filtered automatically by `agent_id`
- Can mark own inquiries as read/replied
- No email status visibility (agents assume it worked)
- Access via agent dashboard shortcode

## Database Maintenance

### Clean Old Inquiries

```sql
-- Delete inquiries older than 2 years marked as closed
DELETE FROM wp_mf_inquiries 
WHERE status = 'closed' 
AND created_at < DATE_SUB(NOW(), INTERVAL 2 YEAR);
```

### Export Inquiries to CSV

```sql
-- Export all inquiries with property/agent names
SELECT 
    i.inquiry_id,
    p.post_title AS property,
    u.display_name AS agent,
    i.client_name,
    i.client_email,
    i.client_phone,
    i.message,
    i.status,
    CASE WHEN i.email_sent = 1 THEN 'Sent' ELSE 'Failed' END AS email_status,
    i.email_recipient,
    i.created_at
FROM wp_mf_inquiries i
LEFT JOIN wp_posts p ON i.property_id = p.ID
LEFT JOIN wp_users u ON i.agent_id = u.ID
ORDER BY i.created_at DESC;
```

### Check Email Failure Rate

```sql
-- Calculate email success rate
SELECT 
    COUNT(*) AS total_inquiries,
    SUM(CASE WHEN email_sent = 1 THEN 1 ELSE 0 END) AS emails_sent,
    SUM(CASE WHEN email_sent = 0 THEN 1 ELSE 0 END) AS emails_failed,
    ROUND(SUM(CASE WHEN email_sent = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) AS success_rate
FROM wp_mf_inquiries;
```

## Upgrade Instructions

### For Existing Installations

If upgrading from an older version without email tracking:

1. **Backup Database**:
   ```bash
   mysqldump -u username -p database_name wp_mf_inquiries > inquiries_backup.sql
   ```

2. **Run Upgrade Script**:
   - Navigate to: `yoursite.com/wp-admin/admin.php?page=malisafi-upgrade-inquiries-db`
   - Or upload `admin/upgrade-inquiries-db.php` and visit directly
   - Review results page to confirm successful upgrade

3. **Verify Changes**:
   - Visit Inquiries page
   - Confirm "Email" column appears
   - Confirm new filters work
   - Submit test inquiry to verify tracking

### Manual SQL Upgrade

If automatic upgrade fails, run this SQL:

```sql
-- See sql/upgrade-inquiries-email-tracking.sql for full script
ALTER TABLE wp_mf_inquiries 
ADD COLUMN email_sent BOOLEAN DEFAULT TRUE AFTER status;

ALTER TABLE wp_mf_inquiries 
ADD COLUMN email_recipient VARCHAR(255) AFTER email_sent;

-- ... (see full file for complete upgrade)
```

## API Reference

### WP_List_Table Methods

```php
// Get list table instance
require_once MALISAFI_MLS_PATH . 'admin/class-inquiries-list-table.php';
$list_table = new Malisafi_Inquiries_List_Table();
$list_table->prepare_items();
$list_table->display();
```

### Custom Queries

```php
// Get unread inquiries for specific agent
global $wpdb;
$unread = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}mf_inquiries 
     WHERE agent_id = %d AND status = 'new' 
     ORDER BY created_at DESC",
    $agent_id
));

// Get inquiries with failed emails
$failed_emails = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}mf_inquiries 
     WHERE email_sent = 0 
     ORDER BY created_at DESC"
);
```

## Shortcodes

### Agent-Specific Inquiries

```
[malisafi_agent_inquiries]
```
Shows current logged-in agent their inquiries only.

### Agency Inquiries

```
[malisafi_agency_inquiries]
```
Shows all inquiries for agents in the logged-in user's agency.

### Owner Inquiries

```
[malisafi_owner_inquiries]
```
Shows inquiries for properties owned by logged-in property owner.

## Integration with Other Systems

### Analytics Integration

Inquiry data feeds into:
- Agent performance metrics (response rate)
- Property engagement statistics (inquiry count)
- Email delivery health monitoring

### CRM Export

Export inquiries to external CRM:

```php
// Get inquiries in last 30 days
$recent = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}mf_inquiries 
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
);

// Format for CRM import
foreach ($recent as $inquiry) {
    $crm_data[] = [
        'contact_name' => $inquiry->client_name,
        'contact_email' => $inquiry->client_email,
        'contact_phone' => $inquiry->client_phone,
        'lead_source' => 'Property Inquiry',
        'property_interest' => get_the_title($inquiry->property_id),
        'notes' => $inquiry->message,
        'assigned_agent' => get_userdata($inquiry->agent_id)->display_name
    ];
}
```

## Support

For issues or questions:
1. Check email logs: Test page → Email Logs tab
2. Verify SMTP configuration with Test SMTP Email
3. Review server error logs: `wp-content/debug.log`
4. Check database permissions for `wp_mf_inquiries` table

## Changelog

**Version 2.0** - February 2026
- ✅ Added email notification tracking (`email_sent`, `email_recipient`)
- ✅ Added Agent column to show assigned agent
- ✅ Added email status filter
- ✅ Enhanced status badges with colors and icons
- ✅ Added real-time statistics summary
- ✅ Improved date display with "time ago" format
- ✅ Fixed field name issue (`id` → `inquiry_id`)
- ✅ Added email_failed status to ENUM

**Version 1.0** - December 2024
- Initial admin inquiries page
- Basic search and filtering
- Mark read/delete actions
- Modal popup for full inquiry view
