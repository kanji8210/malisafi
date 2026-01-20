# Analytics & Map Fixes - 20 January 2026

## Issues Fixed

### 1. Analytics User Count Issue ✅

**Problem:** Analytics showing 1 user instead of 10

**Root Cause:** The `count_users()` WordPress function counts ALL WordPress users, not just Malisafi-specific users.

**Solution:** Updated [admin/analytics/system-health.php](admin/analytics/system-health.php) to count only users with Malisafi roles:

```php
// Old code (wrong)
$total_users = count_users();

// New code (correct)
$malisafi_roles = array(
    'malisafi_client',
    'malisafi_agent_basic',
    'malisafi_agent_premium',
    'malisafi_owner',
    'malisafi_developer',
    'malisafi_moderator'
);
$malisafi_user_query = new WP_User_Query(array(
    'role__in' => $malisafi_roles,
    'fields' => 'ID'
));
$total_users = $malisafi_user_query->get_total();
```

**Result:** Analytics now correctly shows count of users with Malisafi roles.

---

### 2. Property Map Only Showing 1 Property ✅

**Problem:** Map displays only 1 property when multiple properties exist

**Root Causes:**
1. Query was not filtering for properties with location data
2. Geocoding relied only on taxonomy, not county meta field
3. Properties without GPS coordinates were excluded even if they had county info

**Solutions Applied:**

#### A. Enhanced Query to Include Properties with County Data

**File:** [templates/property-map.php](templates/property-map.php)

```php
// Added meta_query to find properties with GPS OR county
'meta_query' => array(
    'relation' => 'OR',
    array(
        'key' => '_malisafi_latitude',
        'compare' => 'EXISTS'
    ),
    array(
        'key' => '_malisafi_county',
        'compare' => 'EXISTS'
    )
)
```

#### B. Prioritized County Meta Field for Geocoding

**Old:** Only used taxonomy terms for geocoding  
**New:** Checks county meta field first, then falls back to taxonomy

```php
// Priority 1: County meta field (Kenya-specific)
$county = get_post_meta($property_id, '_malisafi_county', true);
if (!empty($county)) {
    $location_name = $county . ', Kenya';
}

// Priority 2: Location taxonomy (fallback)
if (empty($location_name)) {
    $location_terms = wp_get_post_terms($property_id, 'malisafi_property_location');
    if (!empty($location_terms) && !is_wp_error($location_terms)) {
        $location_name = $location_terms[0]->name . ', Kenya';
    }
}
```

#### C. Improved Nominatim Geocoding

Added country code filter for better Kenya-specific results:

```php
$geocode_url = add_query_arg(array(
    'format' => 'json',
    'q' => $location_name,
    'limit' => 1,
    'countrycodes' => 'ke'  // Kenya-specific
), 'https://nominatim.openstreetmap.org/search');
```

#### D. Added Admin Debug Notices

For administrators, visible notices now show:
- Total properties queried
- Properties with valid coordinates
- Properties missing location data

**Result:** Map now displays all properties with either:
- GPS coordinates (`_malisafi_latitude` + `_malisafi_longitude`), OR
- County information (`_malisafi_county`) that can be geocoded

---

## Testing

### Analytics Test
1. Go to **Malisafi → Analytics → System Health**
2. Verify "Total Users" count matches users in **Malisafi → Users** with Malisafi roles
3. Excludes WordPress admin, editor, subscriber, etc.

### Map Test
1. Navigate to page with `[malisafi_property_map]` shortcode
2. All published properties with GPS OR county should appear
3. Admin users see debug info if properties are missing

### Troubleshooting

**If properties still don't appear on map:**

1. Check property has either:
   - GPS coordinates filled in meta fields `_malisafi_latitude` and `_malisafi_longitude`, OR
   - County selected in `_malisafi_county` meta field

2. As admin, check HTML comments and visible notices for debug info

3. Clear transient cache if geocoding seems stuck:
   ```sql
   DELETE FROM wp_options WHERE option_name LIKE '_transient_malisafi_geocode_%';
   ```

4. Verify Nominatim API is accessible (no firewall/hosting restrictions)

---

## Files Modified

1. `admin/analytics/system-health.php` - Fixed user count
2. `templates/property-map.php` - Enhanced property query, geocoding, debug output

## Kenya Location System Compatibility

These fixes fully respect the Kenya location system:
- Uses `_malisafi_county` meta field (47 Kenya counties)
- Falls back to location taxonomy if county not set
- All geocoding includes "Kenya" context
- Country code filter ensures Kenya-specific coordinates

## Performance Notes

- Geocoding results cached for 30 days via WordPress transients
- Only properties with location data are queried
- Uses `fields => 'ids'` for efficient queries
- Nominatim API has rate limits (1 request/second) - caching prevents issues
