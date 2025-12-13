# Modern Property Filters - Documentation

## Overview
The MalisafiMLS plugin now includes a **modern, sleek property filtering system** with:
- **Filters on the left** - Sidebar with search, dropdowns, price ranges, and checkboxes
- **Properties on the right** - Grid/List view with beautiful thumbnails
- **Real-time AJAX filtering** - No page reload needed
- **Responsive design** - Works on all devices

---

## Frontend Usage

### Shortcode
Use the shortcode anywhere on your website:

```
[malisafi_properties_modern]
```

### Where to Use
- Create a new page called "Properties" or "Search Properties"
- Add the shortcode to the page content
- Publish the page

### Features
1. **Search Bar** - Search by keywords
2. **Property Type** - Filter by House, Apartment, Condo, etc.
3. **Status** - For Sale, For Rent, Sold, Rented
4. **Bedrooms/Bathrooms** - Minimum number of rooms
5. **Price Range** - Min and max price filters
6. **Area Range** - Square footage filters
7. **Location** - Filter by city/region
8. **Features** - Pool, Garage, Garden, etc. (checkboxes)
9. **Sorting** - Sort by price, date, area, name
10. **View Toggle** - Switch between Grid and List view

### Active Filters Chips
- Filter selections appear as "chips" above the results
- Click the X on any chip to remove that filter
- "Clear All" button to reset everything

---

## Admin Usage

### Properties List Page
The admin properties list (`admin.php?page=malisafi-properties`) now uses the same modern design:

1. **Filters Sidebar** (Left)
   - Search properties
   - Filter by type
   - Filter by status
   - Filter by location
   - Featured properties checkbox
   - Pending moderation checkbox

2. **Properties Grid** (Right)
   - Property cards with thumbnails
   - Price, title, location
   - Bedrooms, bathrooms, area
   - Status badges (Featured, Pending)
   - Edit/View buttons (appear on hover)
   - Post status and ID information

### View Options
- **Grid View** - Cards in a responsive grid (default)
- **List View** - Full-width cards with more details

---

## Design Features

### Modern & Sleek Style
- Clean white cards with subtle shadows
- Smooth hover animations and transitions
- Color-coded badges (Featured, New, Hot Deal)
- Blue accent color (#3498db) for interactive elements
- Professional typography and spacing

### Responsive Breakpoints
- **Desktop** (1024px+) - Filters on left, properties on right
- **Tablet** (768px-1023px) - Filters stack on top
- **Mobile** (<768px) - Single column layout

### Accessibility
- Keyboard navigation support
- ARIA labels and roles
- Focus states for all interactive elements
- High contrast text and icons

---

## Customization

### Filter Options

#### Property Types
Add/modify property types via WordPress admin:
`Properties > Property Types`

#### Locations
Add/modify locations via WordPress admin:
`Properties > Locations`

#### Features List
Edit the features array in:
`templates/properties-filters.php` (lines 18-29)

```php
$features = array(
    'pool' => 'Swimming Pool',
    'garage' => 'Garage',
    'garden' => 'Garden',
    // Add more features...
);
```

### Styling

#### Main CSS File
`assets/css/property-filters.css`

Key sections to customize:
- **Colors** - Search for `#3498db` (primary blue)
- **Card sizing** - `.property-card-modern` (line ~420)
- **Filter sidebar width** - `.malisafi-filters-sidebar` (line ~18)
- **Grid columns** - `.properties-grid` (line ~410)

#### Quick Color Change
Replace all instances of `#3498db` with your brand color:
```css
/* Primary color */
#3498db → #YOUR_COLOR

/* Hover/active states */
#2980b9 → #YOUR_DARKER_COLOR
```

### JavaScript Customization

#### Main JS File
`assets/js/property-filters.js`

Key functions:
- `applyFilters()` - AJAX filter logic (line ~138)
- `updateResults()` - Update HTML after filtering (line ~197)
- `switchView()` - Toggle grid/list view (line ~431)
- `updateActiveFilters()` - Render filter chips (line ~284)

#### Modify Items Per Page
In `property-filters.js` line ~153:
```javascript
per_page: 12  // Change this number
```

---

## AJAX Endpoint

### Handler Class
`includes/class-property-filters-ajax.php`

### Action Hook
`malisafi_filter_properties`

### Request Parameters
```javascript
{
    action: 'malisafi_filter_properties',
    nonce: 'security_token',
    filters: {
        search: 'keyword',
        property_type: 'apartment',
        status: 'for-sale',
        bedrooms: '3',
        bathrooms: '2',
        price_min: '100000',
        price_max: '500000',
        area_min: '1000',
        area_max: '3000',
        location: 'downtown',
        features: ['pool', 'garage'],
        sort: 'price_asc'
    },
    page: 1,
    per_page: 12
}
```

### Response Format
```javascript
{
    success: true,
    data: {
        html: '<article>...</article>',
        total: 45,
        pages: 4,
        current_page: 1,
        pagination: '<button>...</button>'
    }
}
```

---

## Templates

### Main Template
**File:** `templates/properties-filters.php`
- Complete filter sidebar HTML
- Results header with sort/view toggle
- Properties grid container
- Pagination

### Property Card
**File:** `templates/property-card-modern.php`
- Individual property card design
- Thumbnail with badges
- Price, title, location
- Features (beds, baths, area)
- Favorite button

### Admin Template
**File:** `admin/templates/properties-list-modern.php`
- Same design as frontend
- Additional admin features (Edit/View buttons)
- Property status information

---

## Filter Logic

### Search
Searches in:
- Property title
- Property description
- Property content

### Property Type
Taxonomy: `property_type`
Uses WordPress term slugs

### Status
Meta key: `_malisafi_status`
Values: For Sale, For Rent, Sold, Rented

### Bedrooms/Bathrooms
Meta keys: `_malisafi_bedrooms`, `_malisafi_bathrooms`
Filter: Greater than or equal to selected value

### Price Range
Meta key: `_malisafi_price`
Supports min and max values (numeric comparison)

### Area Range
Meta key: `_malisafi_area`
Supports min and max values (numeric comparison)

### Features
Meta key: `_malisafi_features`
Uses LIKE comparison (comma-separated values)

### Sorting Options
- `date_desc` - Newest first (default)
- `date_asc` - Oldest first
- `price_asc` - Price: Low to High
- `price_desc` - Price: High to Low
- `area_asc` - Smallest first
- `area_desc` - Largest first
- `title_asc` - Name: A-Z
- `title_desc` - Name: Z-A

---

## Browser Support
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers (iOS Safari, Chrome Mobile)

---

## Performance

### Optimization Tips
1. **Limit results per page** - Default 12, recommended max 24
2. **Use object caching** - Enable WordPress object cache
3. **Optimize images** - Use proper thumbnail sizes
4. **Lazy loading** - Add lazy loading for images
5. **Database indexes** - Add indexes on meta keys used for filtering

### Caching
The filter system supports:
- Browser localStorage for user preferences (view mode)
- WordPress transient cache (optional, not enabled by default)

---

## Troubleshooting

### Filters not working
1. Check browser console for JavaScript errors
2. Verify AJAX URL: `console.log(malisafiFilters.ajaxurl)`
3. Check nonce verification in PHP

### No results showing
1. Verify properties exist with `post_status = 'publish'`
2. Check post type is `property`
3. Look for PHP errors in debug log

### Styling issues
1. Clear browser cache
2. Check CSS is enqueued properly
3. Inspect for conflicting styles from theme

### AJAX errors
1. Enable WordPress debug mode
2. Check error logs
3. Verify handler class is loaded

---

## Future Enhancements

Possible additions:
- Map view integration
- Saved searches
- Email alerts for new matches
- Compare properties feature
- Advanced filters (year built, garage spaces, etc.)
- Export results to PDF
- Share filters via URL parameters

---

## Support

For issues or questions:
1. Check this documentation
2. Review the code comments
3. Test with default WordPress theme
4. Check browser console and PHP error logs

---

## Files Reference

### CSS
- `assets/css/property-filters.css` (965 lines)

### JavaScript
- `assets/js/property-filters.js` (510 lines)

### PHP Templates
- `templates/properties-filters.php` (Frontend)
- `templates/property-card-modern.php` (Property card)
- `admin/templates/properties-list-modern.php` (Admin)

### PHP Classes
- `includes/class-property-filters-ajax.php` (AJAX handler)
- `includes/class-shortcodes.php` (Shortcode registration)
- `includes/class-property-manager.php` (Property data)

### WordPress Integration
- `includes/class-core.php` (Load filters system)

---

**Version:** 1.0.0  
**Last Updated:** December 2025  
**Author:** MalisafiMLS Team
