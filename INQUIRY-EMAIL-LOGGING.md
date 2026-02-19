# Inquiry Email Logging System

## Overview

Complete email logging system now tracks **ALL** inquiry-related emails (both test and production) with detailed metadata. Accessible via the test/debug page at `/test-inquiry.php`.

## Features Implemented

### 1. Production Email Logging

All real inquiry emails are now logged automatically with:
- **Timestamp**: When the email was sent
- **Type**: `inquiry_notification` for production inquiries
- **Recipient**: Agent or agency email address
- **Subject**: Email subject line
- **Status**: `sent` or `failed`
- **Property ID**: Link to the property
- **Client Email**: Inquirer's email address

### 2. Dual Log System

Two separate logs merged for comprehensive tracking:

```php
// Test emails (from test-inquiry.php)
'malisafi_email_test_log' → Manual SMTP testing

// Production emails (from actual inquiries)  
'malisafi_inquiry_email_log' → Real inquiry submissions
```

Both logs display together in chronological order (newest first).

### 3. Storage & Retention

- **Location**: WordPress `wp_options` table
- **Capacity**: Last 100 entries per log type
- **Access**: Admin-only via test page
- **Backup**: Also logged to server `error_log` file

## Implementation Details

### Files Modified

#### 1. `includes/class-property-actions-ajax.php`

Added email logging to the `send_inquiry()` method:

```php
// Line ~307: Log email attempt after wp_mail()
self::log_email_attempt(array(
    'to' => $recipient_email,
    'subject' => $subject,
    'status' => $sent ? 'sent' : 'failed',
    'property_id' => $property_id,
    'client_email' => $email,
    'timestamp' => current_time('mysql'),
    'type' => 'inquiry_notification'
));
```

Added new static method `log_email_attempt()`:

```php
// Lines ~695-730: Store logs in wp_options + error_log
public static function log_email_attempt($log_data) {
    // Get existing log
    $log = get_option('malisafi_inquiry_email_log', array());
    
    // Add to beginning
    array_unshift($log, $log_data);
    
    // Keep last 100 entries
    if (count($log) > 100) {
        $log = array_slice($log, 0, 100);
    }
    
    // Save to database
    update_option('malisafi_inquiry_email_log', $log, false);
    
    // Also log to server error_log
    error_log(sprintf(
        'Malisafi Email: %s | To: %s | Subject: %s | Property: %d',
        strtoupper($status_text),
        $to,
        $subject,
        $property_id
    ));
}
```

#### 2. `test-inquiry.php`

Enhanced `get_email_log()` function to merge both logs:

```php
function get_email_log($limit = 50) {
    // Get test email log
    $test_log = get_option('malisafi_email_test_log', array());
    
    // Get production inquiry email log
    $inquiry_log = get_option('malisafi_inquiry_email_log', array());
    
    // Combine and sort by timestamp
    $combined = array_merge($inquiry_log, $test_log);
    usort($combined, function($a, $b) {
        $time_a = strtotime($a['timestamp']);
        $time_b = strtotime($b['timestamp']);
        return $time_b - $time_a;
    });
    
    return array_slice($combined, 0, $limit);
}
```

Updated **Email Logs** table with additional columns:
- Type (🧪 Test or 📧 Inquiry)
- Property ID (clickable link)
- Client Email

Updated **Overview** stats to show combined log count:

```php
$test_log_count = count(get_option('malisafi_email_test_log', array()));
$inquiry_log_count = count(get_option('malisafi_inquiry_email_log', array()));
$email_logs = $test_log_count + $inquiry_log_count;
```

## Usage Guide

### Accessing Logs

1. **Via Test Page**:
   - Direct URL: `yoursite.com/wp-content/plugins/malisafi/test-inquiry.php`
   - Or create page with shortcode: `[malisafi_test_inquiry]`
   - Login as admin required

2. **Navigate to Email Logs Tab**:
   - Click "📝 Email Logs" tab
   - Shows last 50 emails (test + production)

### Log Table Columns

| Column | Description | Example |
|--------|-------------|---------|
| **Timestamp** | When email was sent | `Jan 15, 2025 3:45 PM` |
| **Type** | Test or production | 🧪 Test / 📧 Inquiry |
| **To** | Recipient email | `agent@email.com` |
| **Subject** | Email subject | `New Property Inquiry: Villa...` |
| **Property ID** | Link to property | `#1234` (clickable) |
| **Client Email** | Inquirer's email | `client@email.com` |
| **Status** | Success/failure | ✅ sent / ❌ failed |

### Debugging Email Issues

#### Problem: Inquiries not reaching agents

1. Open test page → **Email Logs** tab
2. Look for recent inquiry emails (📧 Inquiry type)
3. Check **Status** column:
   - ✅ `sent` → Email sent successfully, check spam folder
   - ❌ `failed` → SMTP issue, run SMTP test

#### Problem: No logs appearing

1. Submit a test inquiry from your site
2. Check **Email Logs** tab immediately
3. If nothing appears:
   - Verify `class-property-actions-ajax.php` was updated
   - Check server error logs at `wp-content/debug.log`
   - Run database test to verify inquiries table

#### Problem: Email marked "sent" but not received

1. Check **Recent Inquiries** tab to confirm inquiry saved
2. Verify recipient email in **To** column
3. Test SMTP with "Test SMTP Email" button
4. If SMTP test succeeds, check recipient's spam folder
5. If SMTP test fails, configure SMTP plugin (WP Mail SMTP, etc.)

### Server Log Integration

All emails are also logged to WordPress error log:

```
[15-Jan-2025 15:45:23 UTC] Malisafi Email: SENT | To: agent@email.com | Subject: New Property Inquiry: Villa... | Property: 1234
[15-Jan-2025 15:46:12 UTC] Malisafi Email: FAILED | To: invalid@domain | Subject: New Property Inquiry: Apartment... | Property: 1235
```

Location: `wp-content/debug.log` (if `WP_DEBUG_LOG` enabled)

## Monitoring Recommendations

### Daily Checks

- Review **Email Logs** tab for failed deliveries
- Investigate any `failed` status emails immediately
- Verify agent contact information is current

### Weekly Audits

- Compare inquiry count vs. sent email count
- Check for patterns in failures (specific domains, times)
- Clean old test data with "Clean Test Data" button

### Monthly Reports

- Export email logs for delivery rate analysis
- Review SMTP provider performance
- Update agent email addresses if bouncing

## Troubleshooting

### Common Issues

**1. All emails showing as "failed"**
- **Cause**: SMTP not configured or credentials invalid
- **Fix**: Install WP Mail SMTP plugin, configure with valid SMTP server
- **Test**: Run "Test SMTP Email" on test page

**2. Some emails sent, some failed**
- **Cause**: Rate limiting or invalid recipient addresses
- **Fix**: Check recipient emails in user profiles, verify domain validity
- **Test**: Send test inquiry to specific agent

**3. Emails sent but no log entries**
- **Cause**: Old plugin version without logging code
- **Fix**: Verify `class-property-actions-ajax.php` contains `log_email_attempt()` method
- **Test**: Check file around line 307 for logging call

**4. Logs showing but not in chronological order**
- **Cause**: Server timezone mismatch
- **Fix**: Set WordPress timezone in Settings → General
- **Test**: Submit new inquiry and verify timestamp

### Database Queries

Check raw log data directly:

```sql
-- View test email log
SELECT option_value FROM wp_options WHERE option_name = 'malisafi_email_test_log';

-- View inquiry email log
SELECT option_value FROM wp_options WHERE option_name = 'malisafi_inquiry_email_log';

-- Clear all logs (use cautiously)
DELETE FROM wp_options WHERE option_name IN ('malisafi_email_test_log', 'malisafi_inquiry_email_log');
```

### PHP Code to Clear Logs

Add to functions.php temporarily:

```php
// Clear test logs
delete_option('malisafi_email_test_log');

// Clear inquiry logs
delete_option('malisafi_inquiry_email_log');

// Or clear both
delete_option('malisafi_email_test_log');
delete_option('malisafi_inquiry_email_log');
```

## Security Considerations

- **Admin-Only Access**: Test page checks `manage_options` capability
- **No PII Display**: Logs only show email addresses, not message content
- **Auto-Cleanup**: Logs auto-trim to 100 entries per type
- **Non-Autoloaded**: Logs stored with `autoload=false` for performance

## API Reference

### Log Data Structure

Each log entry contains:

```php
array(
    'timestamp' => '2025-01-15 15:45:23',  // MySQL datetime
    'type' => 'inquiry_notification',      // or 'test_email'
    'to' => 'agent@email.com',             // recipient
    'subject' => 'New Property Inquiry',   // email subject
    'status' => 'sent',                    // or 'failed'
    'property_id' => 1234,                 // property post ID
    'client_email' => 'client@email.com'   // inquirer email
);
```

### Functions

**`Property_Actions_Ajax::log_email_attempt($log_data)`**
- Logs email attempt to database and error_log
- Auto-trims to 100 entries
- Static method, callable anywhere

**`get_email_log($limit = 50)`**
- Retrieves combined logs (test + inquiry)
- Sorted by timestamp (newest first)
- Returns array of log entries

## Integration Examples

### Log Custom Email

```php
// After sending custom wp_mail()
$sent = wp_mail($to, $subject, $message, $headers);

\MalisafiMLS\Property_Actions_Ajax::log_email_attempt(array(
    'to' => $to,
    'subject' => $subject,
    'status' => $sent ? 'sent' : 'failed',
    'type' => 'custom_notification',
    'timestamp' => current_time('mysql')
));
```

### Check Recent Failures

```php
$logs = get_email_log(10);
$failures = array_filter($logs, function($log) {
    return $log['status'] === 'failed';
});

if (!empty($failures)) {
    // Send admin notification about email issues
    wp_mail(get_option('admin_email'), 'Email Delivery Issues', ...);
}
```

### Export Logs to CSV

```php
$logs = get_email_log(100);
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="email-logs.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, array('Timestamp', 'Type', 'To', 'Subject', 'Status', 'Property ID'));

foreach ($logs as $log) {
    fputcsv($output, array(
        $log['timestamp'],
        $log['type'],
        $log['to'],
        $log['subject'],
        $log['status'],
        $log['property_id'] ?? ''
    ));
}
fclose($output);
```

## Support

For issues or questions:
1. Check server error logs: `wp-content/debug.log`
2. Review test page Overview → System Status
3. Run all tests in "Run Tests" tab
4. Check email logs for error patterns
5. Verify SMTP configuration with test email

## Changelog

**Version 2.0** - January 2025
- ✅ Added production inquiry email logging
- ✅ Merged test + production logs in unified view
- ✅ Enhanced log table with type, property ID, client email columns
- ✅ Increased retention to 100 entries per log type
- ✅ Added server error_log integration
- ✅ Improved chronological sorting
- ✅ Updated overview stats to show combined count

**Version 1.0** - December 2024
- Initial test email logging implementation
- Basic SMTP testing functionality
