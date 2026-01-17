<?php
/**
 * Agent Dashboard - Profile Editor Section
 */
if (!defined('ABSPATH')) exit;

// Get agent profile post
$agent_id = null;
$args = array(
    'post_type' => 'malisafi_agent',
    'post_status' => array('publish', 'pending', 'draft'),
    'meta_query' => array(
        array(
            'key' => '_agent_user_id',
            'value' => $current_user->ID,
            'compare' => '='
        )
    ),
    'posts_per_page' => 1
);

$agent_query = new WP_Query($args);
if ($agent_query->have_posts()) {
    $agent_query->the_post();
    $agent_id = get_the_ID();
    wp_reset_postdata();
}

// Get agent meta
$agent_photo = $agent_id ? get_post_meta($agent_id, '_agent_photo', true) : '';
$agent_bio = $agent_id ? get_post_meta($agent_id, '_agent_bio', true) : '';
$agent_email = $agent_id ? get_post_meta($agent_id, '_agent_email', true) : $current_user->user_email;
$agent_phone = $agent_id ? get_post_meta($agent_id, '_agent_phone', true) : '';
$agent_whatsapp = $agent_id ? get_post_meta($agent_id, '_agent_whatsapp', true) : '';
$agent_specialties = $agent_id ? get_post_meta($agent_id, '_agent_specialties', true) : '';
$agent_experience = $agent_id ? get_post_meta($agent_id, '_agent_experience', true) : '';
$agent_license = $agent_id ? get_post_meta($agent_id, '_agent_license', true) : '';
$agent_languages = $agent_id ? get_post_meta($agent_id, '_agent_languages', true) : '';
$agent_facebook = $agent_id ? get_post_meta($agent_id, '_agent_facebook', true) : '';
$agent_twitter = $agent_id ? get_post_meta($agent_id, '_agent_twitter', true) : '';
$agent_linkedin = $agent_id ? get_post_meta($agent_id, '_agent_linkedin', true) : '';
$agent_instagram = $agent_id ? get_post_meta($agent_id, '_agent_instagram', true) : '';

?>
<div class="dashboard-profile-editor">
    <div class="dashboard-header">
        <h1><?php _e('My Profile', 'malisafi-mls'); ?></h1>
        <p class="subtitle"><?php _e('Manage your agent profile information', 'malisafi-mls'); ?></p>
    </div>

    <?php if ($agent_id): ?>
        <div class="profile-preview-link">
            <a href="<?php echo esc_url(add_query_arg('agent_id', $agent_id, home_url('/agent-profile/'))); ?>" 
               class="button" target="_blank">
                <span class="dashicons dashicons-visibility"></span>
                <?php _e('View Public Profile', 'malisafi-mls'); ?>
            </a>
        </div>
    <?php endif; ?>

    <form id="agentProfileForm" class="agent-profile-form" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_agent_profile">
        <input type="hidden" name="agent_id" value="<?php echo $agent_id; ?>">
        <?php wp_nonce_field('save_agent_profile', 'agent_profile_nonce'); ?>

        <!-- Profile Photo -->
        <div class="form-section">
            <h2><?php _e('Profile Photo', 'malisafi-mls'); ?></h2>
            <div class="profile-photo-upload">
                <div class="photo-preview">
                    <?php if ($agent_photo): ?>
                        <img src="<?php echo esc_url(wp_get_attachment_url($agent_photo)); ?>" 
                             id="photoPreview" alt="Profile Photo">
                    <?php else: ?>
                        <div class="photo-placeholder" id="photoPreview">
                            <span class="dashicons dashicons-businessman"></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="photo-controls">
                    <button type="button" class="button" id="uploadPhotoBtn">
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Upload Photo', 'malisafi-mls'); ?>
                    </button>
                    <input type="file" id="agentPhoto" name="agent_photo" accept="image/*" style="display:none;">
                    <input type="hidden" name="agent_photo_id" id="agentPhotoId" value="<?php echo $agent_photo; ?>">
                    <?php if ($agent_photo): ?>
                        <button type="button" class="button" id="removePhotoBtn">
                            <span class="dashicons dashicons-trash"></span>
                            <?php _e('Remove', 'malisafi-mls'); ?>
                        </button>
                    <?php endif; ?>
                    <p class="description"><?php _e('Recommended: 400x400px, max 2MB', 'malisafi-mls'); ?></p>
                </div>
            </div>
        </div>

        <!-- Basic Information -->
        <div class="form-section">
            <h2><?php _e('Basic Information', 'malisafi-mls'); ?></h2>
            <div class="form-grid">
                <div class="form-group">
                    <label><?php _e('Email', 'malisafi-mls'); ?> *</label>
                    <input type="email" name="agent_email" value="<?php echo esc_attr($agent_email); ?>" required>
                </div>
                <div class="form-group">
                    <label><?php _e('Phone Number', 'malisafi-mls'); ?> *</label>
                    <input type="tel" name="agent_phone" value="<?php echo esc_attr($agent_phone); ?>" required>
                </div>
                <div class="form-group">
                    <label><?php _e('WhatsApp Number', 'malisafi-mls'); ?></label>
                    <input type="tel" name="agent_whatsapp" value="<?php echo esc_attr($agent_whatsapp); ?>">
                </div>
                <div class="form-group">
                    <label><?php _e('License Number', 'malisafi-mls'); ?></label>
                    <input type="text" name="agent_license" value="<?php echo esc_attr($agent_license); ?>">
                </div>
                <div class="form-group">
                    <label><?php _e('Years of Experience', 'malisafi-mls'); ?></label>
                    <input type="number" name="agent_experience" value="<?php echo esc_attr($agent_experience); ?>" min="0">
                </div>
                <div class="form-group">
                    <label><?php _e('Languages', 'malisafi-mls'); ?></label>
                    <input type="text" name="agent_languages" value="<?php echo esc_attr($agent_languages); ?>" 
                           placeholder="<?php _e('e.g., English, Swahili, French', 'malisafi-mls'); ?>">
                </div>
            </div>
        </div>

        <!-- Professional Info -->
        <div class="form-section">
            <h2><?php _e('Professional Information', 'malisafi-mls'); ?></h2>
            <div class="form-group">
                <label><?php _e('Bio / About Me', 'malisafi-mls'); ?></label>
                <textarea name="agent_bio" rows="6" placeholder="<?php _e('Tell clients about yourself, your experience, and what makes you unique...', 'malisafi-mls'); ?>"><?php echo esc_textarea($agent_bio); ?></textarea>
            </div>
            <div class="form-group">
                <label><?php _e('Specialties', 'malisafi-mls'); ?></label>
                <input type="text" name="agent_specialties" value="<?php echo esc_attr($agent_specialties); ?>" 
                       placeholder="<?php _e('e.g., Luxury Homes, Commercial, First-time Buyers', 'malisafi-mls'); ?>">
                <p class="description"><?php _e('Separate multiple specialties with commas', 'malisafi-mls'); ?></p>
            </div>
        </div>

        <!-- Social Media -->
        <div class="form-section">
            <h2><?php _e('Social Media', 'malisafi-mls'); ?></h2>
            <div class="form-grid">
                <div class="form-group">
                    <label><?php _e('Facebook URL', 'malisafi-mls'); ?></label>
                    <input type="url" name="agent_facebook" value="<?php echo esc_attr($agent_facebook); ?>" placeholder="https://facebook.com/yourprofile">
                </div>
                <div class="form-group">
                    <label><?php _e('Twitter/X URL', 'malisafi-mls'); ?></label>
                    <input type="url" name="agent_twitter" value="<?php echo esc_attr($agent_twitter); ?>" placeholder="https://twitter.com/yourprofile">
                </div>
                <div class="form-group">
                    <label><?php _e('LinkedIn URL', 'malisafi-mls'); ?></label>
                    <input type="url" name="agent_linkedin" value="<?php echo esc_attr($agent_linkedin); ?>" placeholder="https://linkedin.com/in/yourprofile">
                </div>
                <div class="form-group">
                    <label><?php _e('Instagram URL', 'malisafi-mls'); ?></label>
                    <input type="url" name="agent_instagram" value="<?php echo esc_attr($agent_instagram); ?>" placeholder="https://instagram.com/yourprofile">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="button button-primary button-large">
                <span class="dashicons dashicons-saved"></span>
                <?php _e('Save Profile', 'malisafi-mls'); ?>
            </button>
        </div>

        <div id="profileMessage" class="profile-message" style="display:none;"></div>
    </form>
</div>

<style>
.dashboard-profile-editor {
    max-width: 900px;
}

.profile-preview-link {
    margin-bottom: 30px;
}

.agent-profile-form .form-section {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.agent-profile-form h2 {
    margin: 0 0 20px 0;
    font-size: 20px;
    font-weight: 600;
    color: #1a1a1a;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 12px;
}

.profile-photo-upload {
    display: flex;
    gap: 30px;
    align-items: flex-start;
}

.photo-preview {
    width: 200px;
    height: 200px;
    border-radius: 12px;
    overflow: hidden;
    border: 3px solid #e5e7eb;
}

.photo-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-placeholder {
    width: 100%;
    height: 100%;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
}

.photo-placeholder .dashicons {
    font-size: 80px;
    width: 80px;
    height: 80px;
}

.photo-controls {
    flex: 1;
}

.photo-controls .button {
    margin-right: 10px;
    margin-bottom: 10px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.form-group {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #374151;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="tel"],
.form-group input[type="url"],
.form-group input[type="number"],
.form-group textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 15px;
    transition: border-color 0.2s;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #737d5d;
    box-shadow: 0 0 0 3px rgba(115,125,93,0.1);
}

.form-group .description {
    margin-top: 6px;
    font-size: 13px;
    color: #6b7280;
}

.form-actions {
    margin-top: 30px;
    display: flex;
    justify-content: flex-end;
}

.profile-message {
    margin-top: 20px;
    padding: 15px;
    border-radius: 8px;
    font-weight: 500;
}

.profile-message.success {
    background: #d1fae5;
    color: #065f46;
    border-left: 4px solid #10b981;
}

.profile-message.error {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Photo upload handler
    $('#uploadPhotoBtn').on('click', function() {
        $('#agentPhoto').click();
    });

    $('#agentPhoto').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Preview
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#photoPreview').html('<img src="' + e.target.result + '" alt="Profile Photo">');
            };
            reader.readAsDataURL(file);

            // Upload via AJAX
            const formData = new FormData();
            formData.append('action', 'upload_agent_photo');
            formData.append('nonce', '<?php echo wp_create_nonce('upload_agent_photo'); ?>');
            formData.append('photo', file);

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $('#agentPhotoId').val(response.data.attachment_id);
                        showMessage('success', '<?php _e('Photo uploaded successfully!', 'malisafi-mls'); ?>');
                    } else {
                        showMessage('error', response.data.message);
                    }
                }
            });
        }
    });

    // Remove photo
    $('#removePhotoBtn').on('click', function() {
        $('#photoPreview').html('<div class="photo-placeholder"><span class="dashicons dashicons-businessman"></span></div>');
        $('#agentPhotoId').val('');
        $('#agentPhoto').val('');
        $(this).remove();
    });

    // Form submission
    $('#agentProfileForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#agentProfileForm button[type="submit"]').prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php _e('Saving...', 'malisafi-mls'); ?>');
            },
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data.message);
                    if (response.data.agent_id) {
                        $('input[name="agent_id"]').val(response.data.agent_id);
                    }
                } else {
                    showMessage('error', response.data.message);
                }
            },
            complete: function() {
                $('#agentProfileForm button[type="submit"]').prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> <?php _e('Save Profile', 'malisafi-mls'); ?>');
            }
        });
    });

    function showMessage(type, message) {
        $('#profileMessage')
            .removeClass('success error')
            .addClass(type)
            .html(message)
            .show()
            .delay(5000)
            .fadeOut();
    }
});
</script>
