# Malisafi MLS - Configuration

## Installation Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher
- mod_rewrite enabled (for pretty permalinks)

## Recommended PHP Configuration

```
memory_limit = 256M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
max_input_vars = 3000
```

## Database Tables

The plugin creates the following custom table:

```sql
wp_malisafi_property_views
- id (bigint)
- property_id (bigint)
- user_id (bigint, nullable)
- ip_address (varchar)
- viewed_at (datetime)
```

## Custom Post Meta Keys

### Property Details
- `_malisafi_property_id` - MLS Property ID
- `_malisafi_bedrooms` - Number of bedrooms
- `_malisafi_bathrooms` - Number of bathrooms
- `_malisafi_area` - Property area
- `_malisafi_lot_size` - Lot size
- `_malisafi_year_built` - Year built
- `_malisafi_garage` - Garage spaces

### Pricing
- `_malisafi_price` - Property price
- `_malisafi_featured` - Featured status (0 or 1)

### Location
- `_malisafi_address` - Street address
- `_malisafi_city` - City
- `_malisafi_state` - State/Province
- `_malisafi_zip` - ZIP/Postal code
- `_malisafi_country` - Country
- `_malisafi_latitude` - GPS latitude
- `_malisafi_longitude` - GPS longitude

### Agent Information
- `_malisafi_agent_name` - Agent name
- `_malisafi_agent_email` - Agent email
- `_malisafi_agent_phone` - Agent phone

### Media
- `_malisafi_gallery` - Gallery image IDs (comma-separated)

## Plugin Options

All plugin options are stored with the prefix `malisafi_mls_`:

- `malisafi_mls_currency` (default: USD)
- `malisafi_mls_currency_symbol` (default: $)
- `malisafi_mls_currency_position` (default: before)
- `malisafi_mls_thousand_separator` (default: ,)
- `malisafi_mls_decimal_separator` (default: .)
- `malisafi_mls_price_decimals` (default: 0)
- `malisafi_mls_area_unit` (default: sqft)
- `malisafi_mls_properties_per_page` (default: 12)
- `malisafi_mls_enable_front_end_submission` (default: false)
- `malisafi_mls_google_maps_api_key` (default: empty)
- `malisafi_mls_enable_favorite_properties` (default: true)
- `malisafi_mls_enable_property_comparison` (default: true)
- `malisafi_mls_enable_agent_profiles` (default: true)

## Taxonomies

### malisafi_property_type
Property types (hierarchical)
- Slug: property-type
- Examples: House, Apartment, Condo, Villa, Land, Commercial

### malisafi_property_status
Property status (hierarchical)
- Slug: property-status
- Examples: For Sale, For Rent, Sold, Rented, Pending

### malisafi_property_location
Locations (hierarchical)
- Slug: location
- Examples: Cities, Neighborhoods, Districts

### malisafi_property_features
Property features (non-hierarchical)
- Slug: feature
- Examples: Pool, Garage, Garden, Fireplace, Gym, Security System

## REST API Endpoints

The plugin supports WordPress REST API:

```
GET /wp-json/wp/v2/malisafi_property
GET /wp-json/wp/v2/malisafi_property/{id}
POST /wp-json/wp/v2/malisafi_property
PUT /wp-json/wp/v2/malisafi_property/{id}
DELETE /wp-json/wp/v2/malisafi_property/{id}
```

## Security

- All user inputs are sanitized
- Nonce verification on all forms
- Capability checks for admin functions
- SQL queries use prepared statements
- AJAX requests are protected with nonces

## Performance

- Database queries are optimized with indexes
- Transients used for caching
- Assets minified in production
- Lazy loading for images recommended

## Troubleshooting

### Permalinks not working
1. Go to Settings > Permalinks
2. Click "Save Changes" to flush rewrite rules

### Images not displaying
1. Check file permissions on uploads folder
2. Verify wp-content/uploads is writable

### Google Maps not showing
1. Verify API key is entered in settings
2. Ensure Maps JavaScript API is enabled in Google Cloud Console
3. Check for JavaScript errors in browser console
