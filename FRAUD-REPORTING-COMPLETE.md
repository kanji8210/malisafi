# Fraud Reporting System - Implementation Summary

## Overview

Complete fraud reporting and detection system for Malisafi MLS plugin, allowing users to report fraudulent activity and admins to review, investigate, and create manual fraud suspicions.

## System Components

### 1. Database

**New Table**: `wp_mf_fraud_reports`

- **17 Columns**: Complete audit trail from submission to resolution
- **7 Indexes**: Optimized for searches by reporter, agent, property, status, type
- **Status Workflow**: new → under_review → resolved/dismissed
- **Links**: Foreign keys to agents, properties, reviewers, and created suspicions

### 2. Backend API (Analytics_Advanced)

**7 New Methods**:
- `create_fraud_report($data)` - Insert user report
- `get_reports_by_agent($agent_id)` - Retrieve by agent
- `get_reports_by_property($property_id)` - Retrieve by property
- `get_highly_reported_agents()` - Find agents with ≥3 reports
- `get_highly_reported_properties()` - Find properties with ≥2 reports
- `get_low_rated_agents()` - Find agents with <2 stars (≥3 reviews)
- `calculate_fraud_score($user_id, $property_id)` - Multi-factor risk analysis

**Enhanced Auto-Scan**:
- Original: 3 detection types (duplicates, rapid edits, suspicious IPs)
- Enhanced: **6 detection types** (added reported agents/properties, low ratings)
- Returns 6 metrics instead of 3

### 3. Frontend Form (Shortcode)

**Shortcode**: `[malisafi_fraud_report]`

**Features**:
- 10 fraud type dropdown
- jQuery UI Autocomplete for agents/properties (min 2 chars)
- Email field (required if not logged in)
- Character counter on brief description (500 max)
- Detailed textarea for evidence
- Rate limiting (3 reports per IP per day)
- Email notification to admin on submission

**Assets**:
- `fraud-report.css` - Responsive form styling with CSS variables
- `fraud-report.js` - AJAX submission, autocomplete, validation

### 4. Admin Dashboard

**Page**: Analytics → Fraud Reports

**Stats Cards**:
- Total Reports (all-time)
- New Reports (red alert if >0)
- Under Review
- Resolved This Week

**List Table**:
- 20 per page with pagination
- Filters: status, type
- Color-coded badges
- Direct links to agents/properties

**Modals**:
1. **View Details**: Full report with admin notes
2. **Create Suspicion**: Manual fraud suspicion with confidence slider (1-100%)

**Actions**:
- Mark as Under Review / Resolved / Dismissed
- Create Suspicion (links to report)
- Add Admin Notes

### 5. AJAX System

**9 Handlers** (class-fraud-report-ajax.php):

| Action | Purpose | Auth |
|--------|---------|------|
| `malisafi_submit_fraud_report` | Frontend form submission | Public |
| `malisafi_search_agents` | Autocomplete agents | Public |
| `malisafi_search_properties` | Autocomplete properties | Public |
| `malisafi_create_manual_suspicion` | Admin creates suspicion | Admin/Mod |
| `malisafi_update_report_status` | Change report status | Admin/Mod |
| `malisafi_get_fraud_reports` | Fetch reports (filtered) | Admin/Mod |
| `malisafi_get_report_details` | Full report data | Admin/Mod |
| `malisafi_get_agent_name` | Load agent for modal | Admin/Mod |
| `malisafi_get_property_title` | Load property for modal | Admin/Mod |
| `malisafi_link_report_to_suspicion` | Link report→suspicion | Admin/Mod |

**Security**:
- Nonce verification on all handlers
- Capability checks (`moderate_malisafi_properties`)
- Sanitization (sanitize_email, sanitize_text_field, sanitize_textarea_field)
- Rate limiting on submissions

## File Structure

```
malisafi/
├── includes/
│   ├── analytics/
│   │   ├── class-analytics-database.php (UPDATED - fraud_reports table)
│   │   ├── class-analytics-migration.php (UPDATED - migration)
│   │   └── class-analytics-advanced.php (UPDATED - 7 methods + enhanced scan)
│   ├── class-fraud-report-ajax.php (NEW - 9 AJAX handlers)
│   ├── class-fraud-report-shortcode.php (NEW - frontend form)
│   └── class-core.php (UPDATED - load fraud classes)
├── admin/
│   ├── class-admin-fraud-reports.php (NEW - admin page)
│   └── templates/
│       └── modal-create-suspicion.php (NEW - modal template)
├── assets/
│   ├── css/
│   │   ├── fraud-report.css (NEW - frontend styles)
│   │   └── admin-fraud-reports.css (NEW - admin styles)
│   └── js/
│       ├── fraud-report.js (NEW - frontend AJAX)
│       └── admin-fraud-reports.js (NEW - admin AJAX)
├── FRAUD-REPORTING-USER-GUIDE.md (NEW - user documentation)
├── FRAUD-REPORTING-ADMIN-GUIDE.md (NEW - admin documentation)
├── FRAUD-REPORTING-COMPLETE.md (THIS FILE)
└── malisafi-mls.php (UPDATED - plugin constants)
```

## Usage Examples

### Frontend (Users)

**Add form to page**:
```
[malisafi_fraud_report]
```

**Custom title**:
```
[malisafi_fraud_report title="Report Suspicious Activity" show_title="yes"]
```

### Backend (Admins)

**Check fraud score**:
```php
use MalisafiMLS\Analytics\Analytics_Advanced;

$score = Analytics_Advanced::calculate_fraud_score(123, 456);
/*
Returns:
[
    'score' => 85,
    'risk_level' => 'high',
    'factors' => [
        'Fraud alerts: 3',
        'User reports: 2',
        'Low rating: 1.8/5'
    ]
]
*/
```

**Run fraud scan**:
```php
$results = Analytics_Advanced::run_fraud_detection_scan();
/*
Returns:
[
    'duplicates' => 2,
    'rapid_edits' => 1,
    'suspicious_ips' => 0,
    'reported_agents' => 2,       // NEW
    'reported_properties' => 1,   // NEW
    'low_rated_agents' => 1       // NEW
]
*/
```

**Get agent reports**:
```php
$reports = Analytics_Advanced::get_reports_by_agent(123, $limit = 50);
```

## Fraud Detection Thresholds

| Detection Type | Threshold | Confidence | Action |
|----------------|-----------|------------|--------|
| Duplicate Listings | GPS + soundex match | 85% | Auto-flag |
| Rapid Edits | 5 in 10 min | 70% | Auto-flag |
| Suspicious IP | >5 users OR >10 sessions | 60% | Auto-flag |
| **Reported Agents** | **≥3 reports** | **85%** | **Auto-flag** |
| **Reported Properties** | **≥2 reports** | **80%** | **Auto-flag** |
| **Low Ratings** | **<2 stars, ≥3 reviews** | **70%** | **Auto-flag** |

## Report Types

1. **fake_listing** - Property doesn't exist
2. **duplicate_property** - Same property multiple times
3. **misleading_info** - Incorrect details
4. **fake_agent** - Fraudulent agent profile
5. **price_scam** - Unrealistic pricing
6. **fake_photos** - Stolen/mismatched images
7. **contact_fraud** - Fake contact info
8. **identity_theft** - Impersonation
9. **spam** - Mass irrelevant posting
10. **other** - Any other fraud

## Integration Points

### Report → Suspicion Workflow

1. User submits report via frontend form
2. Admin reviews in dashboard
3. Admin clicks "Create Suspicion" from report
4. Modal auto-fills agent/property from report
5. Admin sets confidence score and adds notes
6. Suspicion created in `wp_mf_fraud_detection` table
7. Report updated with `created_suspicion_id`
8. Report status changed to "resolved"

### Auto-Scan Integration

Daily WP Cron job runs `run_fraud_detection_scan()`:
- Checks for new patterns (6 types)
- Creates suspicions automatically
- Emails admin if high-confidence alerts found
- Stores results in analytics dashboard

## Security Features

✅ **Input Validation**: All fields sanitized  
✅ **Rate Limiting**: Max 3 reports per IP per day  
✅ **Nonce Verification**: All AJAX requests  
✅ **Capability Checks**: Admin/Moderator only for sensitive actions  
✅ **IP Logging**: Track submission patterns  
✅ **Privacy Protection**: Reporter identity never shared  
✅ **Email Sanitization**: `is_email()` validation  
✅ **SQL Injection**: `$wpdb->prepare()` for all queries  

## Testing Checklist

### Frontend Testing

- [ ] Submit report as logged-in user
- [ ] Submit report as guest (email required)
- [ ] Test agent autocomplete (min 2 chars)
- [ ] Test property autocomplete (min 2 chars)
- [ ] Verify character counter on reason field
- [ ] Test rate limiting (4th report should fail)
- [ ] Verify email notification sent to admin
- [ ] Test form validation (empty fields)

### Admin Testing

- [ ] Access Fraud Reports page as admin
- [ ] Access as moderator (should work)
- [ ] Access as agent (should be denied)
- [ ] Filter by status (new, under_review, resolved, dismissed)
- [ ] Filter by type (all 10 types)
- [ ] View report details modal
- [ ] Create suspicion from report
- [ ] Create manual suspicion (toolbar button)
- [ ] Update report status (all 4 statuses)
- [ ] Add admin notes
- [ ] Verify pagination (if >20 reports)

### Backend Testing

- [ ] Create report via `create_fraud_report()`
- [ ] Retrieve reports by agent
- [ ] Retrieve reports by property
- [ ] Calculate fraud score (0-100)
- [ ] Run fraud detection scan
- [ ] Verify 6 detection types returned
- [ ] Check agent with ≥3 reports flagged
- [ ] Check property with ≥2 reports flagged
- [ ] Check agent with <2 rating + ≥3 reviews flagged
- [ ] Verify suspicions created in database

## Performance Considerations

- **Autocomplete**: Returns max 10 results
- **Pagination**: 20 reports per page
- **Indexes**: 7 database indexes for fast queries
- **Rate Limiting**: Prevents spam submissions
- **Caching**: Consider object cache for high-traffic sites

## Future Enhancements

### Potential Additions

1. **Email Notifications**: Notify reporter when status changes
2. **Report Dashboard**: Users see their own reports
3. **Advanced Analytics**: Fraud trends over time
4. **Automated Actions**: Auto-suspend after X high-confidence alerts
5. **Machine Learning**: Pattern recognition for fraud types
6. **Export Reports**: CSV download for legal/audit purposes
7. **Appeals System**: Accused party can respond
8. **Reputation Score**: User trustworthiness metric

## Changelog

### Version 1.0.1 (January 2026)

**Added**:
- Fraud reports database table
- User-facing report form with autocomplete
- Admin review dashboard with filters
- Manual suspicion creation modal
- 9 AJAX handlers
- 7 backend methods
- Enhanced fraud detection (3 → 6 types)
- Fraud scoring algorithm
- Complete documentation

**Files Modified**:
- `class-analytics-database.php` (+45 lines)
- `class-analytics-migration.php` (+45 lines)
- `class-analytics-advanced.php` (+300 lines)
- `class-core.php` (+3 requires)
- `malisafi-mls.php` (+2 constants)

**Files Created**:
- 8 new files (classes, templates, assets, docs)

---

**Version**: 1.0.1  
**Date**: January 17, 2026  
**Authors**: Malisafi MLS Team
