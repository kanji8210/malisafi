# Property Types & Listing Status Management Guide

## Overview
MalisafiMLS allows full customization of property types and listing statuses through the WordPress admin interface.

## Accessing Management Screens

### From WordPress Admin Sidebar
Navigate to **Malisafi → Property Types** or **Malisafi → Listing Status**

### From WordPress Default Menus
- **Properties → Property Types** - Manage property categories
- **Properties → Status** - Manage listing statuses

## Default Property Types

The following property types are pre-configured on plugin activation:

1. **Apartment** - Modern apartments and flats
2. **House** - Single-family houses
3. **Villa** - Luxury villas
4. **Townhouse** - Townhouses and row houses
5. **Bungalow** - Single-story bungalows
6. **Mansion** - Large luxury mansions
7. **Land** - Vacant land and plots
8. **Commercial** - Commercial properties
9. **Office** - Office spaces
10. **Shop** - Retail shops
11. **Warehouse** - Warehouses and storage
12. **Farm** - Farms and agricultural land

## Default Listing Statuses

Three main listing statuses are available:

### Active Listings
1. **For Sale** - Property available for purchase
2. **For Rent** - Property available for long-term rental (monthly/yearly)
3. **Short Term Rent** - Property available for short-term rental (daily/weekly - Airbnb type)

### Inactive Listings
4. **Sold** - Property has been sold
5. **Rented** - Property has been rented
6. **Off Market** - Property temporarily off market

## Adding New Property Types

1. Go to **Malisafi → Property Types**
2. Click **"Add New Property Type"**
3. Fill in:
   - **Name**: Display name (e.g., "Studio Apartment")
   - **Slug**: URL-friendly version (auto-generated)
   - **Description**: Brief description of this type
   - **Parent**: Optional - create sub-categories (e.g., "Studio" under "Apartment")
4. Click **"Add New Property Type"**

### Example Use Cases

**Create Sub-categories:**
- Parent: Apartment
  - Child: Studio Apartment
  - Child: 1-Bedroom Apartment
  - Child: Penthouse

**Kenya-Specific Types:**
- Maisonette
- Bedsitter
- Servant Quarter (SQ)
- Gated Community
- Resort Property

## Adding New Listing Statuses

1. Go to **Malisafi → Listing Status**
2. Click **"Add New Status"**
3. Fill in:
   - **Name**: Status name (e.g., "Under Offer")
   - **Slug**: URL-friendly version
   - **Description**: Explanation of this status
4. Click **"Add New Status"**

### Recommended Additional Statuses

- **Under Offer** - Offer accepted, pending completion
- **Lease to Own** - Rent-to-own arrangement
- **Auction** - Property going to auction
- **Coming Soon** - Property will be available soon
- **Reduced Price** - Price recently reduced

## Editing Existing Types/Statuses

1. Navigate to the management screen
2. Click on the type/status name to edit
3. Update fields as needed
4. Click **"Update"**

## Deleting Types/Statuses

1. Hover over the type/status name
2. Click **"Delete"**
3. Confirm deletion

**⚠️ Warning**: Deleting a type/status will remove it from all properties using it.

## Best Practices

### Property Types
- Keep types broad and use sub-categories for specifics
- Use clear, searchable names
- Add descriptions for clarity
- Consider local market needs (Kenya-specific property types)

### Listing Statuses
- Use clear, unambiguous status names
- Maintain consistency across your platform
- Consider your workflow needs
- Keep active vs inactive statuses clear

## Integration with Search & Filters

### Frontend Search Forms
All property types and statuses automatically appear in:
- Property search forms (`[malisafi_search]`)
- Property filter widgets
- Advanced search pages

### Property Submission Forms
Admin and frontend submission forms automatically:
- Display all active property types in dropdowns
- Show relevant listing statuses
- Update dynamically when you add new types

## Hierarchical Structure

### Creating Parent-Child Relationships

**Example: Residential Properties**
```
Residential (Parent)
├── Apartment
│   ├── Studio
│   ├── 1-Bedroom
│   └── Penthouse
├── House
│   ├── Bungalow
│   └── Mansion
└── Villa
```

**Benefits:**
- Better organization
- Improved search filtering
- Clearer categorization

## URL Structure

Types and statuses create clean URLs:
- `/property-type/apartment/` - All apartments
- `/property-type/apartment/penthouse/` - All penthouses
- `/property-status/for-rent/` - All rentals
- `/property-status/short-term-rent/` - All short-term rentals

## Icons & Styling

### Adding Custom Icons (Optional)

You can add icons using CSS or custom fields:

```css
.property-type-apartment:before {
    content: "🏢";
}
.property-type-house:before {
    content: "🏠";
}
.property-status-for-sale:before {
    content: "💰";
}
.property-status-for-rent:before {
    content: "🔑";
}
```

## Bulk Operations

### Import Multiple Types/Statuses
1. Go to management screen
2. Use WordPress import/export tools
3. Or add via code during theme setup

### Export for Backup
1. Use WordPress export functionality
2. Select "Terms and Taxonomies"
3. Download XML file

## Technical Notes

### Taxonomy Names
- Property Types: `malisafi_property_type`
- Listing Status: `malisafi_property_status`

### Programmatic Access
```php
// Get all property types
$types = get_terms('malisafi_property_type');

// Get property status
$statuses = get_terms('malisafi_property_status');

// Add property type to a property
wp_set_object_terms($property_id, 'apartment', 'malisafi_property_type');

// Add listing status
wp_set_object_terms($property_id, 'for-rent', 'malisafi_property_status');
```

## Common Questions

**Q: Can I have multiple statuses per property?**
A: Yes, though typically one status is most appropriate.

**Q: What happens to properties when I delete a type?**
A: Properties lose that categorization but remain published.

**Q: Can I rename types/statuses?**
A: Yes, edit the name without changing the slug to maintain URLs.

**Q: Are types visible to all users?**
A: Yes, they appear in search forms and property listings.

## Support for Rental Types

### For Rent (Long-Term)
- Monthly rentals
- Annual leases
- Corporate housing
- Typical lease duration: 6-12 months

### Short Term Rent (Airbnb Type)
- Daily rentals
- Weekly rentals
- Vacation rentals
- Holiday homes
- Serviced apartments
- Typical duration: 1 day - 1 month

### Configuration Tips
When adding properties:
1. Set status to "For Rent" or "Short Term Rent"
2. Add pricing details (per month vs per night)
3. Include minimum stay requirements in description
4. Add house rules for short-term rentals

## Conclusion

The property types and listing statuses system is fully flexible. Customize it to match your market needs, and it will automatically integrate throughout the entire platform.
