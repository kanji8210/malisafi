# 🧪 Property Creation Testing Guide

**Date:** January 9, 2026  
**Purpose:** Step-by-step testing of complete property creation flow  
**Time Required:** 10-15 minutes

---

## 🎯 What We're Testing

1. ✅ Admin property form loads correctly
2. ✅ All fields are present and functional
3. ✅ Validation works properly
4. ✅ Data saves to database
5. ✅ Success page displays
6. ✅ Location data persists
7. ✅ Property displays on frontend

---

## 📋 Test Checklist

Use this checklist as you test:

### Phase 1: Access Form
- [ ] Login to WordPress admin
- [ ] Navigate to Properties menu
- [ ] Click "Add New"
- [ ] Form loads without errors

### Phase 2: Fill Basic Info
- [ ] Enter property title
- [ ] Enter price
- [ ] Select currency
- [ ] Select property type
- [ ] Select listing type

### Phase 3: Add Details
- [ ] Enter bedrooms
- [ ] Enter bathrooms
- [ ] Enter size
- [ ] Select size unit
- [ ] Enter year built

### Phase 4: Add Location
- [ ] Select county from dropdown
- [ ] Enter city
- [ ] Enter area (optional)
- [ ] Click "Get My Location" for GPS
- [ ] GPS coordinates populate

### Phase 5: Select Features
- [ ] Check some features (parking, garden, etc.)
- [ ] Check some amenities (WiFi, AC, etc.)

### Phase 6: Submit
- [ ] Click "Create Property"
- [ ] No errors appear
- [ ] Redirects to success page

### Phase 7: Verify Success Page
- [ ] Success icon displays
- [ ] Property summary shows
- [ ] All details visible
- [ ] Three action buttons present

### Phase 8: Verify Data Saved
- [ ] Click "Edit This Property"
- [ ] All fields pre-filled with saved data
- [ ] County shows selected value
- [ ] City shows entered text
- [ ] Features/amenities checked

### Phase 9: View Property
- [ ] Click "View Property"
- [ ] Property page loads
- [ ] Title displays
- [ ] Price shows
- [ ] Location visible

---

## 🚀 Step-by-Step Testing Instructions

### **Step 1: Access the Form**

1. Open your browser
2. Go to: `http://localhost/wordpress/wp-admin/`
3. Login with your admin credentials
4. In left menu, hover over **"Properties"**
5. Click **"Add New"**

**Expected Result:**
- ✅ Custom property form loads
- ✅ All 7 sections visible
- ✅ No errors in browser console (F12 to check)

**If Classic Editor Opens:**
- You'll see a warning banner at the top
- Click the blue button: **"Use Custom Property Form"**
- Form should open

---

### **Step 2: Fill Basic Information**

**Section 1: Basic Information**

Fill in these fields:

1. **Property Title** *(required)*
   ```
   Example: "Modern 3-Bedroom Apartment in Westlands"
   ```

2. **Description** *(optional but recommended)*
   ```
   Example: "Beautiful modern apartment with stunning views of the city. 
   Features include spacious living room, modern kitchen, and private balcony."
   ```

3. **Price** *(required)*
   ```
   Example: 5000000
   ```

4. **Currency** *(required)*
   ```
   Select: KES (Kenyan Shilling)
   ```

5. **Property Type** *(required)*
   ```
   Select: Apartment
   (or House, Land, Commercial, Industrial)
   ```

6. **Listing Type** *(required)*
   ```
   Select: For Sale
   (or For Rent, For Lease)
   ```

7. **Reference ID** *(optional)*
   ```
   Example: PROP-2026-001
   ```

**Expected Result:**
- ✅ All fields accept input
- ✅ Required fields marked with red asterisk (*)
- ✅ Dropdowns populate correctly

---

### **Step 3: Add Property Details**

**Section 2: Property Details**

Fill in these fields:

1. **Bedrooms**
   ```
   Example: 3
   ```

2. **Bathrooms**
   ```
   Example: 2
   ```

3. **Property Size**
   ```
   Example: 120
   ```

4. **Size Unit**
   ```
   Select: Square Meters
   ```

5. **Year Built** *(optional)*
   ```
   Example: 2020
   ```

6. **Parking Spaces** *(optional)*
   ```
   Example: 2
   ```

7. **Floors** *(optional)*
   ```
   Example: 1
   ```

8. **Condition** *(optional)*
   ```
   Select: Excellent
   ```

**Expected Result:**
- ✅ Number fields accept only numbers
- ✅ Dropdowns work smoothly

---

### **Step 4: Add Location (CRITICAL TEST!)**

**Section 3: Location** - *This is what we just fixed!*

Fill in these fields:

1. **County** *(required)* - **TEST THIS CAREFULLY!**
   ```
   Select: Nairobi
   ```
   ✅ **Check:** Dropdown shows all 47 Kenya counties

2. **City/Town** *(required)* - **TEST THIS!**
   ```
   Example: Westlands
   ```

3. **Area/Neighborhood** *(optional)*
   ```
   Example: Parklands
   ```

4. **Street Address** *(optional)*
   ```
   Example: Waiyaki Way, ABC Apartments
   ```

5. **GPS Coordinates** *(optional)* - **TEST THE BUTTON!**
   - Click the **"📍 Use My Location"** button
   - Browser will ask for permission
   - Click "Allow"
   - GPS coordinates should auto-fill
   ```
   Example format: -1.2921, 36.8219
   ```
   ✅ **Check:** Coordinates appear in the field

6. **Postal Code** *(optional)*
   ```
   Example: 00100
   ```

**Expected Result:**
- ✅ County dropdown works
- ✅ City field accepts text
- ✅ GPS button populates coordinates
- ✅ All location fields functional

---

### **Step 5: Select Features & Amenities**

**Section 4: Features & Amenities**

**Select Some Features:**
- [x] Parking
- [x] Garden
- [x] Balcony
- [x] Security

**Select Some Amenities:**
- [x] WiFi
- [x] Air Conditioning
- [x] Backup Generator
- [x] Water Backup

**Expected Result:**
- ✅ Checkboxes are clickable
- ✅ Icons display next to labels
- ✅ Grid layout looks good

---

### **Step 6: Set Featured Image (Optional)**

**Right Sidebar: Featured Image Box**

1. Click **"Set featured image"**
2. Upload an image or select from library
3. Click **"Set featured image"** button
4. Image thumbnail appears

**Expected Result:**
- ✅ Image uploads successfully
- ✅ Thumbnail displays in sidebar

---

### **Step 7: Submit Property**

**Scroll to bottom of form:**

1. Click the large blue button: **"Create Property"**
2. Wait for processing (1-3 seconds)

**Expected Result:**
- ✅ No error messages
- ✅ Page redirects (doesn't stay on form)
- ✅ Success page loads

**If Error Appears:**
- Note the error message
- Check which field is highlighted
- Fill in any missing required fields
- Try again

---

### **Step 8: Verify Success Page** 

**You should now see:**

1. **Success Icon** - Green circle with checkmark
2. **Success Message** - "Property Created Successfully!"
3. **Property Summary Card** with:
   - Property image (if uploaded)
   - Property title
   - Status badge (Published or Pending)
   - Price (e.g., "KES 5,000,000")
   - Property type (e.g., "Apartment")
   - Bedrooms & bathrooms (e.g., "3 bed, 2 bath")
   - Location (e.g., "Westlands, Nairobi")

4. **Info Box** explaining what happens next

5. **Three Action Buttons:**
   - 🔵 **View Property** (blue, primary)
   - ⚪ **Go to My Properties** (white, secondary)
   - ⚪ **Add Another Property** (white, secondary)

**Test Each Button:**

**A. Click "View Property":**
- [ ] Opens in new tab
- [ ] Property page loads
- [ ] All details visible

**B. Go back and click "Go to My Properties":**
- [ ] Returns to properties list
- [ ] Your new property appears in list

**C. Go back and click "Add Another Property":**
- [ ] Form opens again
- [ ] All fields are empty
- [ ] Ready to add another property

**Expected Result:**
- ✅ All three buttons work correctly
- ✅ Navigation is smooth

---

### **Step 9: Verify Location Data Saved (CRITICAL!)**

**This tests our bug fix!**

1. From properties list, hover over your property
2. Click **"Edit"**
3. Custom form opens with saved data

**CHECK THESE FIELDS CAREFULLY:**

Location Section:
- [ ] **County dropdown** shows "Nairobi" (or whatever you selected)
- [ ] **City field** shows "Westlands" (or whatever you entered)
- [ ] **Area field** shows "Parklands" (if you entered it)
- [ ] **GPS field** shows coordinates (if you added them)

Also Check:
- [ ] Title is pre-filled
- [ ] Price is pre-filled
- [ ] Bedrooms/bathrooms pre-filled
- [ ] Features are checked
- [ ] Amenities are checked

**Expected Result:**
- ✅ **ALL location fields show saved data**
- ✅ County dropdown has correct selection
- ✅ City text field has your text
- ✅ GPS coordinates preserved

**If Location Fields Are Empty:**
- ❌ Bug still exists - report back to me!

**If Location Fields Are Filled:**
- ✅ Bug is FIXED! Success!

---

### **Step 10: View Property on Frontend**

1. Click **"View Property"** button
2. Property page opens

**Check These Elements:**

Header:
- [ ] Property title displays
- [ ] Price shows correctly
- [ ] Currency is correct

Details Section:
- [ ] Bedrooms count visible
- [ ] Bathrooms count visible
- [ ] Property size shown
- [ ] Property type displayed

Location Section:
- [ ] **County appears** (e.g., "Nairobi")
- [ ] **City appears** (e.g., "Westlands")
- [ ] Location info is visible

Features/Amenities:
- [ ] Selected features display
- [ ] Selected amenities display
- [ ] Icons show correctly

Agent Info:
- [ ] Agent name displays
- [ ] Contact information available

**Expected Result:**
- ✅ All property data displays correctly
- ✅ Location information is visible
- ✅ Page looks professional

---

## 🐛 Common Issues & Solutions

### Issue 1: "County is required" error
**Solution:** Make sure you selected a county from dropdown, don't leave it as "Select county..."

### Issue 2: GPS button doesn't work
**Solution:** 
- Click "Allow" when browser asks for permission
- If still doesn't work, manually enter coordinates
- Format: `-1.2921, 36.8219` (latitude, longitude)

### Issue 3: Classic editor opens instead of form
**Solution:** Click the blue "Use Custom Property Form" button in the warning banner

### Issue 4: Success page shows "Invalid property"
**Solution:** This means property wasn't created. Check for validation errors and try again.

### Issue 5: Location fields empty when editing
**Solution:** This is the bug we fixed. If still happening:
1. Note which fields are empty
2. Check browser console for errors (F12)
3. Report back with error details

---

## ✅ Success Criteria

### Your Test is Successful If:

1. ✅ Form loads without errors
2. ✅ All fields accept input
3. ✅ Validation works (shows errors for required fields)
4. ✅ Submit redirects to success page
5. ✅ Success page displays property summary
6. ✅ **Location data saves correctly** (CRITICAL)
7. ✅ **County shows in edit form** (CRITICAL)
8. ✅ **City shows in edit form** (CRITICAL)
9. ✅ Property displays on frontend
10. ✅ All action buttons work

### Critical Success Indicators:

🎯 **Primary Goal:** Location data (county, city) must save and display when editing

🎯 **Secondary Goal:** Success page shows after submission

🎯 **Tertiary Goal:** Property displays correctly on frontend

---

## 📊 Test Results Template

Copy and fill this out after testing:

```
PROPERTY CREATION TEST RESULTS
Date: [Date]
Tester: [Your Name]

BASIC FUNCTIONALITY:
[ ] Form loads: YES / NO
[ ] All sections visible: YES / NO
[ ] Required fields marked: YES / NO

DATA ENTRY:
[ ] Title field works: YES / NO
[ ] Price field works: YES / NO
[ ] County dropdown works: YES / NO
[ ] City field works: YES / NO
[ ] GPS button works: YES / NO
[ ] Features checkboxes work: YES / NO

SUBMISSION:
[ ] Submit button works: YES / NO
[ ] Redirects to success page: YES / NO
[ ] Success page displays correctly: YES / NO

CRITICAL TEST - LOCATION DATA:
[ ] County saves and displays in edit: YES / NO ⭐
[ ] City saves and displays in edit: YES / NO ⭐
[ ] GPS coordinates save: YES / NO
[ ] Area/neighborhood saves: YES / NO

FRONTEND DISPLAY:
[ ] Property page loads: YES / NO
[ ] Location info displays: YES / NO
[ ] All details visible: YES / NO

OVERALL RESULT: PASS / FAIL

NOTES/ISSUES:
[Write any issues or observations here]
```

---

## 🎯 What to Report Back

After testing, tell me:

1. **Did the form load correctly?**
2. **Did location data save?** (Most important!)
3. **Did you see the success page?**
4. **Any error messages?**
5. **Did property display on frontend?**

**Quick Report Format:**
```
✅ Form loaded
✅ Location saved (County: Nairobi, City: Westlands)
✅ Success page showed
✅ Property displays correctly
```

Or if issues:
```
❌ County field empty after saving
❌ Got error: [error message]
❌ Success page didn't show
```

---

## 🚀 Ready to Test?

Follow the steps above and let me know:
1. Which step you're on
2. If anything doesn't work as expected
3. If you need clarification on any step

I'm here to help you through each step! 

**Start with Step 1 and report back!** 🎉
