# Fraud Reporting System - Admin Guide

## Table of Contents

1. [System Overview](#system-overview)
2. [Accessing Fraud Reports](#accessing-fraud-reports)
3. [Understanding the Dashboard](#understanding-the-dashboard)
4. [Reviewing Reports](#reviewing-reports)
5. [Creating Manual Suspicions](#creating-manual-suspicions)
6. [Automated Fraud Detection](#automated-fraud-detection)
7. [Best Practices](#best-practices)
8. [Legal Considerations](#legal-considerations)

## System Overview

The Fraud Reporting System consists of three main components:

1. **User-Submitted Reports**: Frontend form for users to report suspicious activity
2. **Admin Review Interface**: Dashboard for reviewing and managing reports
3. **Automated Detection**: System automatically scans for fraudulent patterns

### Capabilities

**Who Can Access**:
- Administrators (full access)
- Moderators with `moderate_malisafi_properties` capability

**Workflow**:
```
User Report → New → Under Review → Resolved/Dismissed
                ↓
      Create Suspicion (optional)
```

## Accessing Fraud Reports

### Navigation

1. Log in to WordPress admin
2. Go to **Analytics** menu
3. Click **Fraud Reports** submenu

**URL**: `/wp-admin/admin.php?page=malisafi-analytics-fraud-reports`

### Required Permissions

Users must have ONE of these capabilities:
- `manage_options` (Administrators)
- `moderate_malisafi_properties` (Moderators)

## Understanding the Dashboard

### Stats Cards (Top Section)

![Stats Overview]

1. **Total Reports**: All-time count of fraud reports
2. **New Reports**: Reports needing attention (status = 'new')
   - Red background if > 0
3. **Under Review**: Reports being investigated
4. **Resolved This Week**: Successfully handled reports in last 7 days

### Filters

- **Status Filter**: new, under_review, resolved, dismissed
- **Type Filter**: 10 fraud categories
- **Pagination**: 20 reports per page

**Tip**: Use filters to prioritize high-risk report types (fake_listing, identity_theft).

### Reports Table

**Columns**:
- **Date**: Submission timestamp
- **Type**: Fraud category (color-coded badge)
- **Reporter**: Email or username (links to user profile if logged in)
- **Agent**: Linked agent post (if applicable)
- **Property**: Linked property post (if applicable)
- **Reason**: Brief description (first 10 words)
- **Status**: Current state (4 possible values)
- **Actions**: View, Create Suspicion

## Reviewing Reports

### Step 1: View Report Details

Click **View** button to open modal with:
- Full reason and details
- Reporter information (email/username)
- Agent/Property links
- Submission date
- IP address (for fraud pattern detection)
- Review history (who reviewed, when, notes)

### Step 2: Investigate

**Verification Steps**:

1. **Check Agent Profile**:
   - Click agent link to view their listings
   - Review ratings and reviews
   - Check registration date
   - Verify contact information

2. **Check Property Details**:
   - View property listing
   - Compare photos with other listings (reverse image search)
   - Verify location on map
   - Check price against market rates

3. **Cross-Reference**:
   - Search for similar reports
   - Check reporter's submission history (if user)
   - Look for patterns (multiple reports on same agent/property)

### Step 3: Take Action

**Options**:

1. **Mark as Under Review**
   - You're investigating but need more time
   - Prevents others from duplicating effort

2. **Create Suspicion**
   - Adds entry to fraud detection system
   - Triggers monitoring for agent/property
   - **Required fields**:
     - Fraud type
     - Confidence score (1-100%)
     - Investigation notes

3. **Mark as Resolved**
   - Issue confirmed and action taken
   - Add admin notes documenting action

4. **Dismiss**
   - False report or not fraudulent
   - Add notes explaining why

### Adding Admin Notes

Always add notes when changing status:

**Good Note Example**:
```
Verified with property owner. Listing was duplicate due to
agent's error. Agent removed extra listings. Educated agent
on platform policies. No malicious intent found.
```

**Bad Note Example**:
```
Checked. OK.
```

## Creating Manual Suspicions

Use when you discover fraud through other means (not from user report).

### How to Create

1. Click **Create Manual Suspicion** button (top toolbar)
2. Fill out form:

**Form Fields**:

- **Fraud Type** (required):
  - `duplicate_listing` - Same property posted multiple times
  - `rapid_edits` - 5+ edits in 10 minutes (unusual)
  - `suspicious_ip` - IP shared by 5+ users or 10+ sessions
  - `spam_content` - Mass posting irrelevant content
  - `fake_images` - Stolen or mismatched photos
  - `price_manipulation` - Unrealistic pricing
  - `multiple_accounts` - One person using many accounts
  - `identity_fraud` - Impersonation or stolen credentials

- **Agent** (optional):
  - Type 2+ characters to search
  - Select from autocomplete dropdown

- **Property** (optional):
  - Type 2+ characters to search
  - Select from autocomplete dropdown

- **Confidence Score** (required):
  - Slider: 1-100%
  - **1-39%**: Low risk (watch, don't act)
  - **40-74%**: Medium risk (investigate further)
  - **75-100%**: High risk (immediate action)

- **Investigation Notes** (required):
  - Describe evidence
  - Document sources
  - List verification steps taken

### From Report Review

When viewing a report, click **Create Suspicion** to:
- Auto-fill agent/property
- Link suspicion to original report
- Update report status to "resolved"

## Automated Fraud Detection

The system automatically scans for 6 patterns:

### 1. Duplicate Listings
- **Trigger**: Same GPS coordinates + similar title (soundex match)
- **Confidence**: 85%
- **Detection Data**: duplicate_count, coordinates, titles

### 2. Rapid Edits
- **Trigger**: 5+ property edits in 10 minutes
- **Confidence**: 70%
- **Detection Data**: edit_count, time_window, property_ids

### 3. Suspicious IPs
- **Trigger**: IP used by 5+ users OR 10+ sessions
- **Confidence**: 60%
- **Detection Data**: user_count, session_count, ip_address

### 4. Highly Reported Agents (NEW)
- **Trigger**: 3+ user reports with status = new/under_review
- **Confidence**: 85%
- **Detection Data**: report_count, report_reasons

### 5. Highly Reported Properties (NEW)
- **Trigger**: 2+ user reports with status = new/under_review
- **Confidence**: 80%
- **Detection Data**: report_count, report_reasons, author_id

### 6. Low-Rated Agents (NEW)
- **Trigger**: Average rating < 2 stars with 3+ reviews
- **Confidence**: 70%
- **Detection Data**: avg_rating, review_count

### Viewing Auto-Detected Fraud

1. Go to **Analytics** → **Fraud Detection**
2. View alerts sorted by confidence
3. Click alert to see detection data
4. Mark as "investigated" or "false_positive"

### Running Manual Scan

Auto-scan runs daily via WP Cron. To run manually:

```php
// In WordPress admin or via WP-CLI
use MalisafiMLS\Analytics\Analytics_Advanced;
$results = Analytics_Advanced::run_fraud_detection_scan();
```

**Returns**:
```php
[
    'duplicates' => 3,
    'rapid_edits' => 1,
    'suspicious_ips' => 2,
    'reported_agents' => 2,
    'reported_properties' => 1,
    'low_rated_agents' => 1
]
```

## Best Practices

### Response Time

- **High Priority** (review within 4 hours):
  - `identity_theft`
  - `fake_agent`
  - `contact_fraud`

- **Medium Priority** (review within 24 hours):
  - `fake_listing`
  - `price_scam`
  - `fake_photos`

- **Low Priority** (review within 48 hours):
  - `spam`
  - `other`

### Investigation Checklist

✅ Verify reporter is not competitor/rival agent  
✅ Check if property actually exists (Google Maps)  
✅ Reverse image search on photos  
✅ Call listed phone number to verify  
✅ Check agent's registration documents  
✅ Search for similar reports across platform  
✅ Document all findings in admin notes  
✅ Take screenshots of evidence before removal  

### Communication

**Do**:
- ✅ Keep reporter informed (if contact info provided)
- ✅ Explain decisions in admin notes
- ✅ Coordinate with team on complex cases
- ✅ Document evidence before content removal

**Don't**:
- ❌ Share reporter's identity with accused party
- ❌ Make accusations without evidence
- ❌ Delete content before documenting
- ❌ Ignore patterns (multiple reports on same user)

### Content Removal

When removing fraudulent content:

1. **Document First**: Screenshot/export data
2. **Notify User**: Email explaining violation
3. **Remove Content**: Trash (don't permanently delete yet)
4. **Update Report**: Mark as resolved with notes
5. **Monitor**: Watch for repeat offenses

**Retention**: Keep trashed content for 30 days for appeals.

## Legal Considerations

### Defamation Protection

- ✅ Reports are user-submitted, not platform statements
- ✅ We act as intermediary (safe harbor)
- ✅ Always investigate before taking action
- ✅ Document legitimate business reasons for removals

### Data Privacy (Kenya ODPC Compliance)

**User Reports**:
- Collect only necessary data
- Store securely in WordPress database
- Don't share reporter identity
- Honor data deletion requests (GDPR/ODPC)

**Fraud Database**:
- IP addresses are stored for security
- Retention: 365 days for suspicions
- Reports: Retained for 2 years for pattern analysis

### False Accusations

If accused party claims false report:

1. **Review Evidence**: Did we verify before acting?
2. **Check Reporter History**: Pattern of false reports?
3. **Document**: Admin notes explaining decision
4. **Appeal Process**: Allow rebuttal with evidence

**Malicious Reporting**:
- Ban reporter's email/IP
- Note in admin logs
- Consider legal action for egregious cases

### Law Enforcement Cooperation

If fraud involves:
- Criminal impersonation
- Financial scams (money theft)
- Identity theft

**Actions**:
1. Preserve all evidence
2. Contact authorities
3. Provide data via legal request only
4. Don't delete content under investigation

## Troubleshooting

### Reports Not Showing

- Check user capability: `moderate_malisafi_properties`
- Verify table exists: `wp_mf_fraud_reports`
- Check filters: Clear status/type filters

### Autocomplete Not Working

- Ensure jQuery UI Autocomplete is loaded
- Check browser console for errors
- Verify AJAX URL is correct
- Test with different browser

### Can't Create Suspicion

- Check user role (Admin/Moderator only)
- Verify `wp_mf_fraud_detection` table exists
- Check PHP error logs
- Ensure Analytics system is activated

## API Reference

For developers integrating with the fraud system:

### Backend Methods

```php
use MalisafiMLS\Analytics\Analytics_Advanced;

// Create user report
Analytics_Advanced::create_fraud_report([
    'report_type' => 'fake_listing',
    'agent_id' => 123,
    'property_id' => 456,
    'reason' => 'Brief description',
    'details' => 'Detailed information',
    'reporter_email' => 'user@example.com'
]);

// Get reports by agent
$reports = Analytics_Advanced::get_reports_by_agent(123, $limit = 50);

// Calculate fraud score
$score = Analytics_Advanced::calculate_fraud_score(
    $user_id = 123,
    $property_id = 456
);
// Returns: ['score' => 75, 'risk_level' => 'high', 'factors' => [...]]

// Run fraud detection scan
$results = Analytics_Advanced::run_fraud_detection_scan();
```

### AJAX Endpoints

- `malisafi_submit_fraud_report` - Submit frontend form
- `malisafi_search_agents` - Autocomplete agents
- `malisafi_search_properties` - Autocomplete properties
- `malisafi_create_manual_suspicion` - Create suspicion (admin)
- `malisafi_update_report_status` - Change report status
- `malisafi_get_report_details` - Get full report data

---

**Version**: 1.0.1  
**Last Updated**: January 2026  
**For Support**: Email dev@malisafi.com
