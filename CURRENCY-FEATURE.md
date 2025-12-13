# Currency Selection Feature

## Overview
Properties can now be priced in either USD ($) or KES (KSh), providing flexibility for international and local markets.

## Feature Details

### Admin Interface
When creating or editing a property, users will see:
- **Price field**: Enter the numeric value
- **Currency dropdown**: Select between USD ($) or KES (KSh)
- Default currency: USD

### Frontend Display
Properties display with the appropriate currency symbol:
- **USD**: Displays as `$ 1,000,000`
- **KES**: Displays as `KSh 5,000,000`

### Database Storage
- **Meta Key**: `_malisafi_currency`
- **Values**: `USD` or `KES`
- **Default**: `USD` (if not set)

### Files Modified
1. **includes/class-post-types.php**
   - Added currency dropdown in `render_pricing_meta_box()`
   - Saves currency selection in `save_property_meta()`

2. **templates/property-card-modern.php**
   - Reads currency from post meta
   - Displays appropriate currency symbol

### Usage Example

#### In Property Edit Screen:
```
Price: [500000]
Currency: [KES ▼]
☑ Featured Property
```

#### Frontend Display:
```
KSh 500,000
Beautiful 3BR Apartment
📍 Nairobi
🏠 3 Beds | 🚿 2 Baths | 📏 1,200 sq ft
```

### API Reference

#### Get Property Currency
```php
$currency = get_post_meta($property_id, '_malisafi_currency', true);
if (empty($currency)) {
    $currency = 'USD'; // Default
}
```

#### Display Formatted Price
```php
$price = get_post_meta($property_id, '_malisafi_price', true);
$currency = get_post_meta($property_id, '_malisafi_currency', true);
$symbol = ($currency === 'KES') ? 'KSh' : '$';
$formatted = $symbol . ' ' . number_format(floatval($price));
```

### Future Enhancements
- Add more currencies (EUR, GBP, etc.)
- Currency conversion API integration
- User preference for currency display
- Multi-currency filtering

---
**Last Updated**: December 3, 2025
**Version**: 1.0.0
