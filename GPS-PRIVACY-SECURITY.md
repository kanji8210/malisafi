# GPS Privacy & Security Feature

## Date: 23 janvier 2026

## Overview
Enhanced privacy protection system that automatically offsets GPS coordinates on public maps while maintaining exact locations for administrators. This protects property owners' privacy and security without compromising the usefulness of the map feature.

## Problem Addressed
Displaying exact GPS coordinates of properties publicly can pose security risks:
- Enables precise property targeting
- Reveals exact building entrances/exits
- Compromises owner privacy
- Potential security vulnerability for high-value properties

## Solution Implemented

### Automatic Coordinate Offsetting
- **Public View**: GPS coordinates offset by 200-400 meters randomly
- **Admin View**: Exact coordinates displayed
- **Consistency**: Same property always has same offset (based on property ID)
- **Direction**: Random direction (0-360°) for each property

### User Communication
Clear notifications in all property submission forms:
- Dashboard submission form
- Wizard submission form  
- Admin property edit form

## Technical Implementation

### 1. Coordinate Offset Algorithm

**Location**: [templates/property-map.php](templates/property-map.php#L137-L156)

```php
if (!current_user_can('manage_options')) {
    // Generate consistent offset based on property ID
    $seed = intval($property_id);
    mt_srand($seed);
    
    // Random offset between 200-400 meters
    // 1 degree latitude ≈ 111km, so 300m ≈ 0.0027 degrees
    $offset_distance = (mt_rand(200, 400) / 1000) / 111;
    $offset_angle = mt_rand(0, 360); // Random direction
    
    // Apply offset
    $latitude = floatval($latitude) + ($offset_distance * cos(deg2rad($offset_angle)));
    $longitude = floatval($longitude) + ($offset_distance * sin(deg2rad($offset_angle)));
    
    // Reset random seed
    mt_srand();
}
```

### 2. User Notifications

#### Dashboard Form
**Location**: [includes/class-dashboard-shortcodes.php](includes/class-dashboard-shortcodes.php#L1481-L1493)

```html
<div class="gps-privacy-notice">
    <p>🛡️ Privacy & Security Protection</p>
    <p>For your security and privacy, the exact GPS coordinates you provide 
    will be automatically offset by 200-400 meters when displayed publicly 
    on the map. This protects the exact location of your property while 
    still showing it in the correct area. <strong>Administrators will see 
    the accurate location.</strong> Please enter the precise coordinates 
    for best results.</p>
</div>
```

#### Wizard Form
**Location**: [templates/property-submission-wizard.php](templates/property-submission-wizard.php#L305-L312)

#### Admin Form
**Location**: [admin/templates/property-edit-form.php](admin/templates/property-edit-form.php#L343-L347)

### 3. Admin Notice on Map

**Location**: [templates/property-map.php](templates/property-map.php#L258-L264)

```html
<div class="admin-notice">
    <p>🔒 Admin View: You are seeing the exact GPS coordinates for all properties. 
    Public users will see locations offset by 200-400 meters for privacy and security.</p>
</div>
```

## How It Works

### For Agents/Property Owners

1. **Submission**: Agent enters exact GPS coordinates (manually or via "Get Location" button)
2. **Notification**: Clear message explains coordinates will be offset publicly
3. **Storage**: Exact coordinates stored in database (`_malisafi_latitude`, `_malisafi_longitude`)
4. **Display**: Public sees offset version, admin sees exact location

### For Public Users

1. **Map View**: Properties appear in correct general area
2. **Accuracy**: Within 200-400 meters of actual location
3. **Consistency**: Same property always appears at same offset location
4. **No Notice**: Public users don't see any indication of offsetting

### For Administrators

1. **Map View**: Exact coordinates displayed
2. **Notice**: Yellow banner confirms admin is seeing precise locations
3. **Editing**: Can see and edit exact coordinates
4. **Management**: Full access to accurate location data

## Mathematical Details

### Coordinate Offset Calculation

**Distance Conversion**:
- 1 degree latitude ≈ 111 kilometers
- Offset range: 200-400 meters = 0.2-0.4 km
- In degrees: 0.2 ÷ 111 ≈ 0.0018° to 0.4 ÷ 111 ≈ 0.0036°

**Random Components**:
- `$offset_distance`: Random value in degrees (converted from 200-400m)
- `$offset_angle`: Random angle 0-360° (direction)

**Trigonometry**:
```
new_latitude = original_latitude + (offset_distance × cos(angle))
new_longitude = original_longitude + (offset_distance × sin(angle))
```

**Consistency**:
- Seeded with `property_id` ensures same property = same offset
- Prevents property "jumping" on map with each page load

## Security Considerations

### Privacy Protected
✅ Exact property location hidden from public  
✅ Owner address not precisely revealed  
✅ Building entrances/exits not identifiable  
✅ Security vulnerabilities minimized  

### Functionality Maintained
✅ Properties still appear in correct neighborhood  
✅ Map search by area still works  
✅ Distance estimations still reasonable  
✅ "Near me" feature still useful  

### Admin Control
✅ Admins have full access to exact data  
✅ Property management not impacted  
✅ Verification and moderation possible  
✅ Analytics use precise coordinates  

## User Experience

### Agent Submission Flow

1. Agent fills property details
2. Reaches GPS section
3. Sees blue notification box explaining offset
4. Clicks "Get Current Location" or enters manually
5. Submits property with confidence
6. Property appears on map in correct area

### Public User Map Interaction

1. User opens property map
2. Sees properties in neighborhoods
3. Clicks marker to see details
4. Location is approximate but useful
5. No indication of offset (seamless experience)

### Admin Property Review

1. Admin opens map
2. Sees yellow notice: "Admin View - Exact Coordinates"
3. Reviews properties at precise locations
4. Can verify county/area accuracy
5. Approves/edits with full information

## Files Modified

### Core Files
1. **templates/property-map.php**
   - Added offset algorithm (lines ~137-156)
   - Added admin notice banner
   - Offset only for non-admin users

2. **includes/class-dashboard-shortcodes.php**
   - Added privacy notice in GPS section
   - Clear explanation of offset behavior

3. **templates/property-submission-wizard.php**
   - Added privacy notice in Step 3 (Location)
   - Mobile-friendly styling

4. **admin/templates/property-edit-form.php**
   - Added admin-focused privacy notice
   - Emphasized exact coordinates for admin

## Testing

### Test as Agent
1. Create property with GPS coordinates
2. Verify privacy notice visible in form
3. Submit property
4. View on public map → coordinates should be offset
5. Log in as admin → coordinates should be exact

### Test as Public User
1. View property map (logged out)
2. Properties should appear in correct areas
3. No offset notice visible
4. Clicking marker works normally

### Test as Admin
1. View property map (logged in as admin)
2. Yellow notice visible at top
3. Properties at exact coordinates
4. Edit property → sees exact GPS

### Verify Consistency
1. Reload map multiple times
2. Same property should stay at same offset location
3. Different properties have different offsets

## Configuration

### Modify Offset Range
To change the 200-400m range, edit `property-map.php`:

```php
// Change these values (in meters)
$offset_distance = (mt_rand(200, 400) / 1000) / 111;
// Example: 100-300m range:
$offset_distance = (mt_rand(100, 300) / 1000) / 111;
```

### Disable Offsetting
To disable (not recommended):

```php
// Comment out or remove the entire offset block:
// if (!current_user_can('manage_options')) {
//     ... offset code ...
// }
```

### Change Admin Capability
To allow other roles to see exact coordinates:

```php
// Change from 'manage_options' to another capability:
if (!current_user_can('moderate_properties')) {
    // Offset code
}
```

## Benefits

### For Property Owners
- **Privacy**: Exact location not publicly exposed
- **Security**: Reduced risk of targeting
- **Peace of Mind**: Professional privacy protection
- **Trust**: Platform demonstrates care for user security

### For Platform
- **Professional**: Shows attention to security
- **Competitive**: Feature not common in real estate platforms
- **Legal**: Reduces potential liability
- **Trust**: Users feel protected

### For Public Users
- **Useful**: Still get general location
- **Seamless**: No noticeable difference in UX
- **Neighborhood Search**: Still effective
- **Distance Estimates**: Still reasonable

## Limitations

### Known Trade-offs
- Public coordinates not exact (by design)
- Properties near boundaries may appear in adjacent areas
- Distance calculations slightly off for public
- "Very close" properties might not appear adjacent

### Not Affected
- Search by county/area (still precise)
- Property details/price (still exact)
- Contact forms (work normally)
- Geocoding from county (still works)

## Future Enhancements

### Potential Improvements
- [ ] Configurable offset range per property type
- [ ] Different offsets for residential vs commercial
- [ ] Option for owner to choose offset amount
- [ ] Disclosure indicator on map (optional)
- [ ] Admin dashboard showing offset statistics
- [ ] Heatmap view with blurred boundaries

### Advanced Features
- [ ] Neighborhood-based offset (stay within boundaries)
- [ ] Proximity-based clustering (group nearby properties)
- [ ] User preference for precision (logged-in users see less offset)
- [ ] Time-based offset variation (different offset at different times)

## Support & Documentation

### For Agents
- See form notifications
- Contact admin for questions
- Trust that privacy is protected
- Enter accurate coordinates

### For Admins
- Yellow banner on map indicates admin view
- Edit coordinates normally
- Privacy automatically handled
- No special configuration needed

## Compliance

### Privacy Best Practices
✅ User data protection  
✅ Reasonable expectation of privacy  
✅ Transparent about offsetting (in forms)  
✅ Admin oversight maintained  

### Data Integrity
✅ Original coordinates stored unchanged  
✅ Offset calculated on-the-fly  
✅ No data modification in database  
✅ Reversible (admin view)  

---

**Status**: ✅ Fully Implemented  
**Version**: 1.0  
**Last Updated**: 23 janvier 2026  
**Tested**: ✅ Agent forms, Public map, Admin view
