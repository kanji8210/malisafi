# Agent Profile System - Quick Start

## For Agents

### 1. Edit Your Profile

1. Log in to your agent account
2. Go to **Agent Dashboard** → **Profile**
3. Fill in your information:
   - **Upload Photo**: Click "Choose Photo", select image (max 2MB)
   - **Email**: Your contact email (required)
   - **Phone**: Your phone number (required)
   - **WhatsApp**: Your WhatsApp number
   - **Bio**: Write about yourself and your experience
   - **Specialties**: Enter comma-separated specialties (e.g., "Residential, Luxury, New Developments")
   - **License Number**: Your real estate license
   - **Experience**: Years in the business
   - **Languages**: Languages you speak
   - **Social Media**: Add your social media links (optional)

4. Click **Save Profile**
5. Success message appears - profile saved!

### 2. View Your Public Profile

1. In the profile editor, click **View Public Profile**
2. This is what clients see when they search for you
3. Share this link with clients and prospects

### 3. Manage Reviews

- Clients can rate and review you
- Reviews are submitted and pending approval
- Once approved, they appear on your public profile
- You can respond to reviews (feature coming soon)

## For Clients/Website Visitors

### 1. Find an Agent

Agents are displayed:
- On property listings (click agent name)
- In directory (when available)
- Via direct URL: `/agent-profile/?agent_id=123`

### 2. View Agent Profile

You'll see:
- Agent photo and name
- License number and badge
- Star rating
- Years of experience
- Languages spoken
- Bio and specialties
- Current listings
- Customer reviews

### 3. Contact Agent

Multiple ways to reach out:
- **Phone**: Click phone button (tel: link)
- **WhatsApp**: Click WhatsApp button
- **Email**: Click email button
- **Message**: Send message through site

### 4. Leave a Review

1. Scroll to reviews section
2. Click **Write a Review** (must be logged in)
3. Select star rating (1-5 stars)
4. Write your comment (optional)
5. Click **Submit Review**
6. Your review is submitted for approval
7. Once approved, it appears on the agent's profile

## For Site Administrators

### 1. Configure Agent Pages

Agents automatically get:
- Dashboard: `/agent-dashboard/`
- Public profile page: `/agent-profile/`

To customize:
1. Create your own pages with shortcodes:
   - Dashboard: `[malisafi_agent_dashboard]`
   - Public profile: `[malisafi_agent_profile_view agent_id="123"]`
2. Update page URLs in site settings

### 2. Monitor Reviews

Currently, all reviews default to "approved" status.

To set up moderation:
1. Go to WordPress database
2. Query `wp_mf_agent_ratings` table
3. Change `status` field:
   - `'pending'` = awaiting approval
   - `'approved'` = visible on profile
   - `'rejected'` = hidden from profile

Example SQL:
```sql
-- View pending reviews
SELECT * FROM wp_mf_agent_ratings WHERE status = 'pending';

-- Approve a review
UPDATE wp_mf_agent_ratings SET status = 'approved' WHERE id = 123;

-- Reject a review
UPDATE wp_mf_agent_ratings SET status = 'rejected' WHERE id = 123;
```

### 3. Display Agents on Property Listings

In your property template, add link to agent:
```php
<?php
$agent_id = get_post_meta(get_the_ID(), '_agent_id', true);
if ($agent_id) {
    $profile_url = add_query_arg('agent_id', $agent_id, home_url('/agent-profile/'));
    echo '<a href="' . esc_url($profile_url) . '">' . get_the_title($agent_id) . '</a>';
}
?>
```

### 4. Create Agent Directory Page

1. Create new page: "Find an Agent"
2. Add custom code to list all agents:
   ```php
   <?php
   $args = array(
       'post_type' => 'malisafi_agent',
       'post_status' => 'publish',
       'posts_per_page' => -1
   );
   $agents = new WP_Query($args);
   
   while ($agents->have_posts()) {
       $agents->the_post();
       $agent_id = get_the_ID();
       $photo = get_post_meta($agent_id, '_agent_photo', true);
       $rating = /* get_average_rating($agent_id) */;
       $profile_url = add_query_arg('agent_id', $agent_id, home_url('/agent-profile/'));
       
       echo '<div class="agent-card">';
       echo wp_get_attachment_image($photo, 'medium');
       echo '<h3>' . get_the_title() . '</h3>';
       echo '<p>Rating: ' . $rating . '/5</p>';
       echo '<a href="' . esc_url($profile_url) . '">View Profile</a>';
       echo '</div>';
   }
   ?>
   ```

## Features

### Profile Editor (Dashboard)
✅ Upload/change photo (400x400px recommended)
✅ Edit all profile information
✅ Add social media links
✅ Preview public profile
✅ Real-time validation

### Public Profile
✅ Display agent information
✅ Show star rating
✅ List current properties
✅ Display customer reviews
✅ Contact buttons (phone, WhatsApp, email)
✅ Responsive on all devices

### Review System
✅ 1-5 star ratings
✅ Write comments
✅ Pending approval
✅ Shows average rating
✅ Review count
✅ Reviewer name and date

### Responsive Design
✅ Desktop - full layout
✅ Tablet - optimized cards
✅ Mobile - stacked layout

## Shortcodes

| Shortcode | Purpose | Attributes |
|-----------|---------|------------|
| `[malisafi_agent_dashboard]` | Agent dashboard | None |
| `[malisafi_agent_profile_view]` | Public profile | `agent_id` (optional) |

## Styling

All styling uses CSS variables for easy customization:

```css
--mls-dark: #3a4034         /* Main dark color */
--mls-accent: #737d5d       /* Accent/highlight color */
--mls-grey-green: #c5d3b6   /* Light secondary color */
--mls-light-grey: #f5f5f5   /* Background color */
```

Edit in: `assets/css/variables.css`

## Troubleshooting

**Q: Photo upload fails with "Invalid file type"**
A: Use JPG, PNG, or WebP format files

**Q: Photo upload fails with "File size too large"**
A: Image must be smaller than 2MB. Compress before uploading.

**Q: Reviews not showing on public profile**
A: Reviews need to be approved by admin. Check database status field.

**Q: Agent profile page shows blank**
A: Check agent_id parameter in URL is correct

**Q: Can't find my agent profile link**
A: Dashboard profile editor shows "View Public Profile" button

## Database

Key table: `wp_mf_agent_ratings`

Fields:
- `agent_id` - Which agent is being reviewed
- `user_id` - Who left the review
- `rating` - 1-5 stars
- `comment` - Review text
- `status` - pending/approved/rejected
- `created_at` - When review was submitted

## Permissions

| Action | Agent | Owner | Admin |
|--------|-------|-------|-------|
| Edit own profile | ✅ | ✅ | ✅ |
| Edit other profile | ❌ | ❌ | ✅ |
| View public profile | ✅ | ✅ | ✅ |
| Leave review | ✅ | ✅ | ✅ |
| Moderate reviews | ❌ | ❌ | ✅ |

## Next Steps

1. **Create an agent account** and test the profile editor
2. **Upload a test profile photo**
3. **Fill in all your information**
4. **Visit your public profile** to see how it looks
5. **Share your profile link** with clients
6. **Collect reviews** from satisfied clients

## Contact Support

For issues or questions:
1. Check the troubleshooting section above
2. Review `AGENT-PROFILE-SYSTEM.md` for detailed documentation
3. Check WordPress debug.log for error messages

---

**Version**: 1.0.0  
**Status**: Ready to Use
