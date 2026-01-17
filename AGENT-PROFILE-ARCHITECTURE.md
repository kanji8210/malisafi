# Agent Profile System - Architecture & Flow Diagram

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    AGENT PROFILE SYSTEM                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────────┐         ┌──────────────────────────────┐ │
│  │   AGENT SIDE     │         │    FRONTEND COMPONENTS       │ │
│  ├──────────────────┤         ├──────────────────────────────┤ │
│  │                  │         │                              │ │
│  │ 1. Edit Profile  │────────→│ agent-dashboard-profile.php  │ │
│  │    - Upload photo│         │ (411 lines)                  │ │
│  │    - Add bio     │         │                              │ │
│  │    - Add contact │         │ ┌─────────────────────────┐  │ │
│  │    - Specialties │         │ │ FORM INPUTS             │  │ │
│  │    - Social media│         │ ├─────────────────────────┤  │ │
│  │                  │         │ │ • Photo upload          │  │ │
│  │ 2. Save changes  │         │ │ • Email (required)      │  │ │
│  │                  │         │ │ • Phone (required)      │  │ │
│  │                  │         │ │ • WhatsApp              │  │ │
│  │                  │         │ │ • Bio/description       │  │ │
│  │                  │         │ │ • Specialties (CSV)     │  │ │
│  │                  │         │ │ • License number        │  │ │
│  │                  │         │ │ • Experience (years)    │  │ │
│  │                  │         │ │ • Languages             │  │ │
│  │                  │         │ │ • Social media URLs     │  │ │
│  │                  │         │ └─────────────────────────┘  │ │
│  └──────────────────┘         └──────────────────────────────┘ │
│           │                              │                      │
│           │ AJAX: upload_agent_photo     │                      │
│           │─────────────────────────→────┼──→ class-agent-     │ │
│           │ AJAX: save_agent_profile     │    profile-ajax.php │ │
│           │                              │    (230 lines)       │ │
│           └──────────────────────────────┘                      │
│                           ▼                                     │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │            AJAX HANDLERS (class-agent-profile-ajax.php)  │  │
│  ├──────────────────────────────────────────────────────────┤  │
│  │                                                          │  │
│  │ 1. upload_agent_photo()                                 │  │
│  │    └─→ Validate file (type, size)                      │  │
│  │    └─→ wp_handle_upload()                              │  │
│  │    └─→ wp_insert_attachment()                          │  │
│  │    └─→ Return attachment_id                            │  │
│  │                                                          │  │
│  │ 2. save_agent_profile()                                 │  │
│  │    └─→ Verify nonce & user auth                        │  │
│  │    └─→ Check ownership                                 │  │
│  │    └─→ Create/update malisafi_agent post               │  │
│  │    └─→ Update post meta (all fields)                   │  │
│  │    └─→ Return agent_id                                 │  │
│  │                                                          │  │
│  │ 3. submit_agent_review()                                │  │
│  │    └─→ Verify user logged in                           │  │
│  │    └─→ Validate rating (1-5)                           │  │
│  │    └─→ Check no duplicate review                       │  │
│  │    └─→ Insert into wp_mf_agent_ratings                 │  │
│  │    └─→ Set status='pending' (approval)                 │  │
│  │                                                          │  │
│  └──────────────────────────────────────────────────────────┘  │
│                           │ │ │                                 │
│           ┌───────────────┼─┼─┼────────────────────┐           │
│           │               │ │ │                    │           │
│           ▼               ▼ ▼ ▼                    ▼           │
│  ┌─────────────────┐ ┌─────────────┐   ┌──────────────────┐   │
│  │  WordPress DB   │ │   MySQL     │   │  WordPress Meta  │   │
│  │                 │ │  Tables     │   │   (post_meta)    │   │
│  │ • Attachments   │ │             │   │                  │   │
│  │ • Posts         │ │ wp_mf_agent │   │ _agent_photo     │   │
│  │ • Post Meta     │ │ _ratings    │   │ _agent_bio       │   │
│  │                 │ │             │   │ _agent_email     │   │
│  │ Posts Type:     │ │ Stores:     │   │ _agent_phone     │   │
│  │ malisafi_agent  │ │ • agent_id  │   │ _agent_whatsapp  │   │
│  │                 │ │ • user_id   │   │ _agent_license   │   │
│  │ Post Meta:      │ │ • rating    │   │ _agent_bio       │   │
│  │ _agent_*        │ │ • comment   │   │ _agent_facebook  │   │
│  │                 │ │ • status    │   │ _agent_twitter   │   │
│  │                 │ │ • timestamps│   │ _agent_linkedin  │   │
│  │                 │ │             │   │ _agent_instagram │   │
│  └─────────────────┘ └─────────────┘   └──────────────────┘   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## Client/Public Side Flow

```
┌──────────────────────────────────────────────────────────────────┐
│               PUBLIC AGENT PROFILE VIEW                          │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────────┐                                        │
│  │   CLIENT/VISITOR    │                                        │
│  ├─────────────────────┤                                        │
│  │                     │                                        │
│  │ 1. Find agent       │                                        │
│  │    (search, link,   │                                        │
│  │     direct URL)     │                                        │
│  │                     │                                        │
│  │ 2. View profile     │────→ /agent-profile/?agent_id=123     │
│  │    [malisafi_agent  │      or shortcode:                     │
│  │    _profile_view]   │      [malisafi_agent_profile_view]     │
│  │                     │                                        │
│  │ 3. Read info        │                                        │
│  │ 4. Contact agent    │                                        │
│  │ 5. Leave review     │                                        │
│  │                     │                                        │
│  └─────────────────────┘                                        │
│           │                                                     │
│           └──→ FRONTEND: agent-profile-public.php              │
│               (320 lines)                                       │
│                                                                 │
│  ┌────────────────────────────────────────────────────────┐    │
│  │ COMPONENTS DISPLAYED                                  │    │
│  ├────────────────────────────────────────────────────────┤    │
│  │                                                        │    │
│  │ 1. AGENT HEADER                                       │    │
│  │    ├─ Photo (150x150 circle)                         │    │
│  │    ├─ Name & badge                                   │    │
│  │    ├─ License number                                 │    │
│  │    ├─ Star rating (calculated from reviews)          │    │
│  │    └─ Experience years                               │    │
│  │                                                        │    │
│  │ 2. CONTACT BUTTONS                                    │    │
│  │    ├─ Phone (tel: link)                              │    │
│  │    ├─ WhatsApp (wa.me/ link)                         │    │
│  │    ├─ Email (mailto: link)                           │    │
│  │    └─ Message (form)                                 │    │
│  │                                                        │    │
│  │ 3. STATS BAR                                          │    │
│  │    ├─ Active listings count                          │    │
│  │    ├─ Reviews count                                  │    │
│  │    └─ Years experience                               │    │
│  │                                                        │    │
│  │ 4. BIO & SPECIALTIES                                  │    │
│  │    ├─ Bio text (wpautop formatted)                   │    │
│  │    └─ Specialties (tags)                             │    │
│  │                                                        │    │
│  │ 5. CURRENT LISTINGS GRID                              │    │
│  │    └─ 6 most recent properties                       │    │
│  │       • Photo, title, price, beds/baths              │    │
│  │                                                        │    │
│  │ 6. REVIEWS SECTION                                    │    │
│  │    ├─ Average rating & count                         │    │
│  │    ├─ Review list (approved only)                    │    │
│  │    │  └─ Reviewer name, avatar, rating, comment      │    │
│  │    └─ "Write a Review" button (logged in)            │    │
│  │       └─ Opens review modal                          │    │
│  │          • Star rating selector (1-5)                │    │
│  │          • Comment textarea                          │    │
│  │          • Submit button                             │    │
│  │          └─ AJAX: submit_agent_review action         │    │
│  │             └─ Inserts into wp_mf_agent_ratings      │    │
│  │                (status='pending')                    │    │
│  │                                                        │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                 │
│  ┌────────────────────────────────────────────────────────┐    │
│  │ STYLING (agent-profile-public.css - 500+ lines)       │    │
│  ├────────────────────────────────────────────────────────┤    │
│  │ • Responsive breakpoints (768px, 1024px)             │    │
│  │ • CSS variables for colors                           │    │
│  │ • Hover effects on buttons                           │    │
│  │ • Modal styling                                      │    │
│  │ • Form validation styles                            │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                 │
└──────────────────────────────────────────────────────────────────┘
```

## Data Flow Diagram

```
AGENT SAVES PROFILE:
─────────────────────────────────────────────────────────────

1. Agent fills form
2. Clicks "Save Profile"
3. Browser validates
4. AJAX POST to admin-ajax.php
   └─→ action=save_agent_profile
       ├─ Verify nonce
       ├─ Check user logged in
       ├─ Sanitize inputs
       └─→ HANDLER: save_agent_profile()
           ├─ Check ownership
           ├─ Create/update post (malisafi_agent)
           ├─ Update meta:
           │  ├─ _agent_user_id (current user)
           │  ├─ _agent_photo (attachment ID)
           │  ├─ _agent_bio
           │  ├─ _agent_email
           │  ├─ _agent_phone
           │  ├─ _agent_whatsapp
           │  ├─ _agent_specialties
           │  ├─ _agent_experience
           │  ├─ _agent_license
           │  ├─ _agent_languages
           │  ├─ _agent_facebook
           │  ├─ _agent_twitter
           │  ├─ _agent_linkedin
           │  └─ _agent_instagram
           └─→ Return: { success: true, agent_id: 123 }
5. JavaScript shows success message
6. User can click "View Public Profile"
────────────────────────────────────────────────────────────────────


CLIENT VIEWS AGENT & LEAVES REVIEW:
──────────────────────────────────────────────────────────────────

1. Client visits /agent-profile/?agent_id=123
2. WordPress loads shortcode
   └─→ agent_profile_public($atts)
       ├─ Verify agent_id
       ├─ Enqueue CSS
       └─→ Load template: agent-profile-public.php
           ├─ Query malisafi_agent post by ID
           ├─ Get all _agent_* meta fields
           ├─ Query approved reviews:
           │  SELECT * FROM wp_mf_agent_ratings
           │  WHERE agent_id=123 AND status='approved'
           ├─ Calculate average rating
           ├─ Get agent's current properties
           └─→ Render HTML with:
               ├─ Agent info (from post meta)
               ├─ Review list (from ratings table)
               └─ Review modal (if logged in)

3. Client sees profile, clicks "Write a Review"
4. Review modal opens (for logged-in users)
5. Client enters:
   ├─ Star rating (1-5)
   └─ Comment text
6. Client clicks "Submit Review"
7. AJAX POST: submit_agent_review
   ├─ Verify user logged in
   ├─ Check rating 1-5
   ├─ Check no duplicate:
   │  SELECT id FROM wp_mf_agent_ratings
   │  WHERE agent_id=123 AND user_id=456
   └─→ INSERT into wp_mf_agent_ratings:
       ├─ agent_id
       ├─ user_id
       ├─ rating
       ├─ comment
       ├─ status='pending'
       └─ created_at=NOW()
8. Return: "Thank you! Review pending approval"
9. Admin approves in database
10. Review appears on public profile
────────────────────────────────────────────────────────────────


AVERAGE RATING CALCULATION:
──────────────────────────────────────────────────────────────

SELECT AVG(rating)
FROM wp_mf_agent_ratings
WHERE agent_id=123 AND status='approved'

Example: 4 reviews
├─ User 1: 5 stars
├─ User 2: 4 stars
├─ User 3: 4 stars
└─ User 4: 5 stars
Average: (5+4+4+5)/4 = 4.5 stars ⭐⭐⭐⭐☆
────────────────────────────────────────────────────────────────
```

## File Integration Map

```
CORE INTEGRATION
────────────────
malisafi-mls.php
    ↓
includes/class-core.php
    ├─ require_once class-agent-profile-ajax.php ← NEW
    └─ require_once class-dashboard-shortcodes.php
        └─ init() registers shortcodes
            ├─ [malisafi_agent_dashboard] ← existing
            └─ [malisafi_agent_profile_view] ← NEW
                └─ agent_profile_public()
                    ├─ Enqueue CSS ← NEW
                    └─ Include template ← NEW


TEMPLATE STRUCTURE
──────────────────
Dashboard (via agent_dashboard shortcode):
├─ agent-dashboard-modern.php (main layout)
│  └─ ?section=profile parameter
│      └─ includes agent-dashboard-profile.php ← NEW/UPDATED
│         ├─ Form with photo upload
│         ├─ Inline styles
│         └─ Inline JavaScript
│            ├─ Photo upload AJAX → upload_agent_photo ← NEW
│            └─ Profile save AJAX → save_agent_profile ← NEW

Public Profile (via agent_profile_public shortcode):
├─ agent-profile-public.php ← NEW
│  ├─ Queries agent post meta
│  ├─ Queries wp_mf_agent_ratings
│  ├─ Calculates average rating
│  ├─ Gets current properties
│  └─ Displays:
│     ├─ Agent info
│     ├─ Review list
│     ├─ Properties grid
│     └─ Review modal
└─ agent-profile-public.css ← NEW


AJAX HANDLERS
─────────────
includes/class-agent-profile-ajax.php ← NEW
├─ upload_agent_photo()
│  └─ Called by form file input change
│     ├─ Validates file
│     ├─ Uploads via wp_handle_upload()
│     └─ Creates attachment
├─ save_agent_profile()
│  └─ Called by form submission
│     ├─ Creates/updates malisafi_agent post
│     └─ Updates all _agent_* meta fields
└─ submit_agent_review()
   └─ Called by review modal submit
      └─ Inserts into wp_mf_agent_ratings table


STYLING LAYERS
──────────────
agent-dashboard-modern.css
    └─ Dashboard layout & profile section

agent-profile-public.css ← NEW
    ├─ Agent header styling
    ├─ Contact buttons
    ├─ Review section
    ├─ Properties grid
    ├─ Review modal
    └─ Responsive breakpoints


DATABASE LAYER
──────────────
WordPress Tables:
├─ wp_posts (malisafi_agent post type)
├─ wp_postmeta (_agent_* fields)
├─ wp_users (agent user accounts)
└─ wp_posts (attachments - photos)

Custom Tables:
└─ wp_mf_agent_ratings ← Already created in schema
   ├─ Stores reviews
   ├─ Links agent ↔ user
   ├─ Rating value (1-5)
   ├─ Approval status
   └─ Timestamps
```

## Security Layers

```
NONCE VERIFICATION
──────────────────
Form inputs have nonce fields:
    wp_nonce_field('save_agent_profile', 'agent_profile_nonce')
    wp_nonce_field('upload_agent_photo', 'nonce')

AJAX handlers verify:
    check_ajax_referer('save_agent_profile', 'agent_profile_nonce')
    check_ajax_referer('upload_agent_photo', 'nonce')


USER AUTHENTICATION
───────────────────
Profile editing:
    if (!is_user_logged_in()) exit;
    
Ownership verification:
    $linked_user = get_post_meta($agent_id, '_agent_user_id', true);
    if ($linked_user != $current_user->ID && !current_user_can('manage_options')) {
        wp_send_json_error();
    }

Review submission:
    if (!is_user_logged_in()) exit;


INPUT VALIDATION
────────────────
File upload:
    ├─ Check file type (JPEG, PNG, WebP)
    ├─ Check file size (max 2MB)
    └─ Use wp_handle_upload() for security

Text fields:
    ├─ sanitize_text_field() for text inputs
    ├─ sanitize_email() for emails
    ├─ sanitize_textarea_field() for textareas
    ├─ esc_url_raw() for URLs
    └─ intval() for numbers

Rating validation:
    └─ $rating >= 1 && $rating <= 5


SQL INJECTION PREVENTION
────────────────────────
All database queries use wpdb->prepare():
    
    Example:
    $wpdb->get_var($wpdb->prepare(
        "SELECT AVG(rating) FROM $table WHERE agent_id = %d",
        $agent_id
    ));
```

## Feature Completion Status

```
✅ COMPLETED FEATURES
─────────────────────

Agent Dashboard
├─ [✅] Profile section in dashboard
├─ [✅] Photo upload with preview
├─ [✅] Form for all profile fields
├─ [✅] Save via AJAX (no page reload)
├─ [✅] Success/error messages
├─ [✅] View public profile link
└─ [✅] Responsive mobile design

Public Profile View
├─ [✅] Display agent information
├─ [✅] Show star rating
├─ [✅] Display list of properties
├─ [✅] Show customer reviews
├─ [✅] Contact buttons (phone, WhatsApp, email)
├─ [✅] Review modal with rating selector
├─ [✅] Responsive design
└─ [✅] Performance optimized

Review System
├─ [✅] 5-star rating input
├─ [✅] Comment/text input
├─ [✅] Logged-in users only
├─ [✅] Prevent duplicate reviews
├─ [✅] Pending approval workflow
├─ [✅] Average rating calculation
└─ [✅] Review count display

Security
├─ [✅] Nonce verification
├─ [✅] User authentication
├─ [✅] User ownership checks
├─ [✅] File type/size validation
├─ [✅] Input sanitization
├─ [✅] SQL injection prevention
└─ [✅] XSS prevention (escaping)

Code Quality
├─ [✅] PHP syntax validated
├─ [✅] Consistent naming conventions
├─ [✅] Inline comments
├─ [✅] Proper error handling
├─ [✅] Responsive CSS
└─ [✅] Accessibility considered


🔮 READY FOR FUTURE DEVELOPMENT
─────────────────────────────────

Database fields ready:
├─ helpful_count / not_helpful_count (for voting)
├─ agent_response / agent_responded_at (for replies)
├─ verified_client (for badges)
└─ property_id (for property-specific reviews)

Easy additions:
├─ [ ] Admin review moderation page
├─ [ ] Agent response functionality
├─ [ ] Helpful/unhelpful voting
├─ [ ] Email notifications
├─ [ ] Agent badges (certified, top-rated, etc.)
├─ [ ] Agent directory listing
├─ [ ] Advanced search/filtering
└─ [ ] Mobile app integration
```

---

## Summary

The agent profile system is **fully integrated** into the Malisafi MLS plugin with:

1. **Secure AJAX handlers** for all operations
2. **Complete frontend templates** for agent and client interfaces
3. **Professional styling** with responsive design
4. **Database integration** with existing WordPress structures
5. **Comprehensive documentation** for developers and users
6. **Production-ready code** with validation and error handling

All components work together seamlessly to provide agents with professional profiles and clients with ability to view, contact, and review agents.
