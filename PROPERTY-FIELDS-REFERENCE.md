# Property Fields Reference

## Complete List of All Property Fields

### **Basic Information**
| Field | Type | Required | Meta Key | Notes |
|-------|------|----------|----------|-------|
| Title | Text | Yes | post_title | WordPress core field |
| Description | Textarea | No | post_content | WordPress core field |
| Price | Number | Yes | _malisafi_price | Positive number |
| Currency | Select | Yes | _malisafi_currency | KES, USD, EUR, GBP |
| Property Type | Taxonomy | Yes | malisafi_property_type | house, apartment, land, commercial, industrial |
| Listing Type | Select | Yes | _malisafi_listing_type | sale, rent, lease |

### **Property Details**
| Field | Type | Required | Meta Key | Notes |
|-------|------|----------|----------|-------|
| Bedrooms | Number | No | _malisafi_bedrooms | 0-50 |
| Bathrooms | Number | No | _malisafi_bathrooms | 0-50 |
| Size | Number | No | _malisafi_size | Decimal allowed |
| Size Unit | Select | No | _malisafi_size_unit | sqm, sqft, acres, hectares |
| Year Built | Number | No | _malisafi_year_built | 1800-2030 |
| Condition | Select | No | _malisafi_condition | new, excellent, good, fair, renovation |
| Parking Spaces | Number | No | _malisafi_parking | 0-20 |
| Floors | Number | No | _malisafi_floors | 1-100 |

### **Location Information**
| Field | Type | Required | Meta Key | Notes |
|-------|------|----------|----------|-------|
| Address | Text | No | _malisafi_address | Street address |
| County | Select | Yes | _malisafi_county | 47 Kenyan counties |
| City | Text | Yes | _malisafi_city | City/Town name |
| Area | Text | No | _malisafi_area | Neighborhood |
| GPS Coordinates | Text | No | _malisafi_gps | lat, lng format |
| Postal Code | Text | No | _malisafi_postal_code | |

### **Features & Amenities**
| Field | Type | Required | Meta Key | Notes |
|-------|------|----------|----------|-------|
| Features | Checkbox Array | No | _malisafi_features | parking, garden, balcony, etc. |
| Amenities | Checkbox Array | No | _malisafi_amenities | wifi, ac, heating, etc. |

### **Media**
| Field | Type | Required | Meta Key | Notes |
|-------|------|----------|----------|-------|
| Featured Image | Image | No | _thumbnail_id | WordPress core |
| Gallery | Image Array | Yes (min 1) | _malisafi_gallery_ids | Comma-separated IDs |
| Video URL | URL | No | _malisafi_video_url | YouTube, Vimeo |
| Virtual Tour | URL | No | _malisafi_virtual_tour | 360° tour link |

### **Agent Information**
| Field | Type | Required | Meta Key | Notes |
|-------|------|----------|----------|-------|
| Agent ID | Select | No | _property_agent_id | Link to agent post |
| Agent Name | Text | No | _malisafi_agent_name | Legacy field |
| Agent Email | Email | No | _malisafi_agent_email | Legacy field |
| Agent Phone | Tel | No | _malisafi_agent_phone | Legacy field |

### **Additional Information**
| Field | Type | Required | Meta Key | Notes |
|-------|------|----------|----------|-------|
| Reference ID | Text | No | _malisafi_reference_id | Internal reference |
| Featured | Checkbox | No | _malisafi_featured | 0 or 1 |
| Views Count | Number | No | _malisafi_views | Auto-incremented |
| Inquiries Count | Number | No | _malisafi_inquiries | Auto-counted |

### **SEO & Marketing**
| Field | Type | Required | Meta Key | Notes |
|-------|------|----------|----------|-------|
| Meta Title | Text | No | _malisafi_meta_title | SEO title |
| Meta Description | Textarea | No | _malisafi_meta_description | SEO description |

---

## Available Feature Options

### Features
- parking (Parking)
- garden (Garden)
- balcony (Balcony)
- terrace (Terrace)
- pool (Swimming Pool)
- gym (Gym)
- security (24/7 Security)
- furnished (Furnished)
- pet_friendly (Pet Friendly)
- fireplace (Fireplace)
- storage (Storage Space)
- laundry (Laundry Room)

### Amenities
- wifi (WiFi)
- ac (Air Conditioning)
- heating (Heating)
- elevator (Elevator)
- backup_generator (Backup Generator)
- water_backup (Water Backup)
- playground (Playground)
- clubhouse (Clubhouse)
- cctv (CCTV)
- intercom (Intercom)
- borehole (Borehole)
- solar (Solar Power)

---

## Kenya Counties (47)

1. Nairobi
2. Mombasa
3. Kwale
4. Kilifi
5. Tana River
6. Lamu
7. Taita-Taveta
8. Garissa
9. Wajir
10. Mandera
11. Marsabit
12. Isiolo
13. Meru
14. Tharaka-Nithi
15. Embu
16. Kitui
17. Machakos
18. Makueni
19. Nyandarua
20. Nyeri
21. Kirinyaga
22. Murang'a
23. Kiambu
24. Turkana
25. West Pokot
26. Samburu
27. Trans-Nzoia
28. Uasin Gishu
29. Elgeyo-Marakwet
30. Nandi
31. Baringo
32. Laikipia
33. Nakuru
34. Narok
35. Kajiado
36. Kericho
37. Bomet
38. Kakamega
39. Vihiga
40. Bungoma
41. Busia
42. Siaya
43. Kisumu
44. Homa Bay
45. Migori
46. Kisii
47. Nyamira
