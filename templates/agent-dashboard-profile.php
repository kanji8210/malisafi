<?php
/**
 * Agent Dashboard - Profile Editor Section
 */
if (!defined('ABSPATH')) exit;

// Enqueue WordPress media uploader
wp_enqueue_media();

// Check if admin is editing another agent's profile
$edit_agent_id = isset($_GET['edit_agent_id']) && current_user_can('manage_options') ? intval($_GET['edit_agent_id']) : null;
$target_user_id = $edit_agent_id ? $edit_agent_id : $current_user->ID;

// Get agent profile post for the target user
$agent_id = null;
$args = array(
    'post_type' => 'malisafi_agent',
    'post_status' => array('publish', 'pending', 'draft'),
    'meta_query' => array(
        array(
            'key' => '_agent_user_id',
            'value' => $target_user_id,
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

// Get user info for the target user
$target_user = $edit_agent_id ? get_userdata($target_user_id) : $current_user;
if (!$target_user) {
    echo '<div class="error"><p>' . __('User not found.', 'malisafi-mls') . '</p></div>';
    return;
}

// Get agent meta
$agent_photo = $agent_id ? get_post_meta($agent_id, '_agent_photo', true) : '';
$agent_bio = $agent_id ? get_post_meta($agent_id, '_agent_bio', true) : '';
$agent_email = $agent_id ? get_post_meta($agent_id, '_agent_email', true) : $target_user->user_email;
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
        <?php if ($edit_agent_id): ?>
            <h1><?php printf(__('Edit Agent Profile: %s', 'malisafi-mls'), esc_html($target_user->display_name)); ?></h1>
            <p class="subtitle"><?php _e('Admin editing mode - Changes will update this agent\'s profile', 'malisafi-mls'); ?></p>
        <?php else: ?>
            <h1><?php _e('My Profile', 'malisafi-mls'); ?></h1>
            <p class="subtitle"><?php _e('Manage your agent profile information', 'malisafi-mls'); ?></p>
        <?php endif; ?>
    </div>

    <?php if ($agent_id): ?>
        <div class="profile-preview-link">
            <?php if ($edit_agent_id && current_user_can('manage_options')): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=malisafi-agent-management')); ?>" 
                   class="button">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    <?php _e('Back to Agent Management', 'malisafi-mls'); ?>
                </a>
            <?php endif; ?>
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
                    <div class="upload-buttons">
                        <button type="button" class="button button-primary" id="uploadPhotoBtn">
                            <span class="dashicons dashicons-upload"></span>
                            <?php _e('Choose from Library', 'malisafi-mls'); ?>
                        </button>
                        <button type="button" class="button button-primary" id="uploadDirectBtn">
                            <span class="dashicons dashicons-camera"></span>
                            <?php _e('Upload from Device', 'malisafi-mls'); ?>
                        </button>
                        <input type="file" id="directPhotoUpload" accept="image/jpeg,image/png,image/webp" style="display:none;">
                    </div>
                    <input type="hidden" name="agent_photo_id" id="agentPhotoId" value="<?php echo $agent_photo; ?>">
                    <?php if ($agent_photo): ?>
                        <button type="button" class="button button-secondary" id="removePhotoBtn">
                            <span class="dashicons dashicons-trash"></span>
                            <?php _e('Remove Photo', 'malisafi-mls'); ?>
                        </button>
                    <?php endif; ?>
                    <p class="description">
                        <strong><?php _e('Two ways to add your photo:', 'malisafi-mls'); ?></strong><br>
                        • <?php _e('Choose from Library: Select from your media library', 'malisafi-mls'); ?><br>
                        • <?php _e('Upload from Device: Quick upload from your computer or phone', 'malisafi-mls'); ?>
                    </p>
                    <p class="description">
                        <?php _e('Recommended: 400x400px, JPG/PNG/WebP, max 2MB', 'malisafi-mls'); ?>
                    </p>
                    <p class="description">
                        <strong><?php _e('Tip:', 'malisafi-mls'); ?></strong> 
                        <?php _e('A clear, professional headshot helps build trust with clients.', 'malisafi-mls'); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Basic Information -->
        <div class="form-section">
            <h2><?php _e('Basic Information', 'malisafi-mls'); ?></h2>
            <div class="form-grid">
                <div class="form-group">
                    <label><?php _e('First Name', 'malisafi-mls'); ?> *</label>
                    <input type="text" name="agent_first_name" value="<?php echo esc_attr($target_user->first_name); ?>" required>
                </div>
                <div class="form-group">
                    <label><?php _e('Last Name', 'malisafi-mls'); ?> *</label>
                    <input type="text" name="agent_last_name" value="<?php echo esc_attr($target_user->last_name); ?>" required>
                </div>
                <div class="form-group">
                    <label><?php _e('Display Name', 'malisafi-mls'); ?> *</label>
                    <input type="text" name="agent_display_name" value="<?php echo esc_attr($target_user->display_name); ?>" required>
                    <p class="description"><?php _e('This is the name shown to clients', 'malisafi-mls'); ?></p>
                </div>
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
    flex-wrap: wrap;
}

.photo-preview {
    width: 200px;
    height: 200px;
    border-radius: 12px;
    overflow: hidden;
    border: 3px solid #e5e7eb;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.photo-preview:hover {
    border-color: var(--mls-accent, #737d5d);
    transform: scale(1.02);
}

.photo-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
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
    min-width: 250px;
}

.upload-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 12px;
}

.photo-controls .button {
    margin: 0;
    transition: all 0.2s ease;
    flex: 1;
    min-width: 180px;
    justify-content: center;
}

.photo-controls .button .dashicons {
    margin-right: 4px;
}

.photo-controls .button-primary {
    background: var(--mls-accent, #737d5d);
    border-color: var(--mls-accent, #737d5d);
}

.photo-controls .button-primary:hover {
    background: var(--mls-dark, #2c2c2c);
    border-color: var(--mls-dark, #2c2c2c);
}

.photo-controls .button-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.photo-controls .button-secondary {
    margin-top: 8px;
    display: inline-flex;
    align-items: center;
}

.dashicons.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.photo-controls .description {
    margin-top: 12px;
    color: #6b7280;
    font-size: 13px;
    line-height: 1.6;
}

.photo-controls .description strong {
    color: var(--mls-accent, #737d5d);
}
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
    // Option 1: WordPress Media Uploader (Library Selection)
    var mediaUploader;
    
    $('#uploadPhotoBtn').on('click', function(e) {
        e.preventDefault();
        
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        mediaUploader = wp.media({
            title: '<?php _e('Choose Profile Photo', 'malisafi-mls'); ?>',
            button: {
                text: '<?php _e('Use this photo', 'malisafi-mls'); ?>'
            },
            library: {
                type: 'image'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            
            $('#photoPreview').html('<img src="' + attachment.url + '" alt="Profile Photo">');
            $('#agentPhotoId').val(attachment.id);
            
            if ($('#removePhotoBtn').length === 0) {
                var removeBtn = $('<button type="button" class="button button-secondary" id="removePhotoBtn">' +
                    '<span class="dashicons dashicons-trash"></span> ' +
                    '<?php _e('Remove Photo', 'malisafi-mls'); ?>' +
                    '</button>');
                $('.upload-buttons').after(removeBtn);
            }
            
            showMessage('success', '<?php _e('Photo selected successfully!', 'malisafi-mls'); ?>');
        });
        
        mediaUploader.open();
    });

    // Option 2: Direct Upload from Device
    $('#uploadDirectBtn').on('click', function(e) {
        e.preventDefault();
        $('#directPhotoUpload').click();
    });

    $('#directPhotoUpload').on('change', function(e) {
        var file = e.target.files[0];
        if (!file) return;

        // Validate file type
        var allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (allowedTypes.indexOf(file.type) === -1) {
            showMessage('error', '<?php _e('Invalid file type. Only JPG, PNG, and WebP are allowed.', 'malisafi-mls'); ?>');
            return;
        }

        // Validate file size (2MB)
        if (file.size > 2 * 1024 * 1024) {
            showMessage('error', '<?php _e('File too large. Maximum size is 2MB.', 'malisafi-mls'); ?>');
            return;
        }

        // Show preview immediately
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#photoPreview').html('<img src="' + e.target.result + '" alt="Profile Photo">');
        };
        reader.readAsDataURL(file);

        // Upload to WordPress Media Library via AJAX
        var formData = new FormData();
        formData.append('action', 'upload_agent_photo');
        formData.append('nonce', '<?php echo wp_create_nonce('upload_agent_photo'); ?>');
        formData.append('photo', file);

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#uploadDirectBtn').prop('disabled', true).html(
                    '<span class="dashicons dashicons-update spin"></span> ' +
                    '<?php _e('Uploading...', 'malisafi-mls'); ?>'
                );
            },
            success: function(response) {
                if (response.success) {
                    $('#agentPhotoId').val(response.data.attachment_id);
                    $('#photoPreview').html('<img src="' + response.data.url + '" alt="Profile Photo">');
                    
                    if ($('#removePhotoBtn').length === 0) {
                        var removeBtn = $('<button type="button" class="button button-secondary" id="removePhotoBtn">' +
                            '<span class="dashicons dashicons-trash"></span> ' +
                            '<?php _e('Remove Photo', 'malisafi-mls'); ?>' +
                            '</button>');
                        $('.upload-buttons').after(removeBtn);
                    }
                    
                    showMessage('success', '<?php _e('Photo uploaded successfully!', 'malisafi-mls'); ?>');
                } else {
                    showMessage('error', response.data.message || '<?php _e('Upload failed', 'malisafi-mls'); ?>');
                }
            },
            error: function() {
                showMessage('error', '<?php _e('Upload failed. Please try again.', 'malisafi-mls'); ?>');
            },
            complete: function() {
                $('#uploadDirectBtn').prop('disabled', false).html(
                    '<span class="dashicons dashicons-camera"></span> ' +
                    '<?php _e('Upload from Device', 'malisafi-mls'); ?>'
                );
                $('#directPhotoUpload').val(''); // Reset input
            }
        });
    });

    // Remove photo
    $(document).on('click', '#removePhotoBtn', function() {
        $('#photoPreview').html('<div class="photo-placeholder"><span class="dashicons dashicons-businessman"></span></div>');
        $('#agentPhotoId').val('');
        $('#directPhotoUpload').val('');
        $(this).remove();
        showMessage('success', '<?php _e('Photo removed', 'malisafi-mls'); ?>');
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
