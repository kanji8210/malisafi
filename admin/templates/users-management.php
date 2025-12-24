<?php
/**
 * Users Management Template
 *
 * @package MalisafiMLS
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current action
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Display messages
if (isset($_GET['message'])) {
    $messages = array(
        'user_added' => __('User successfully added.', 'malisafi-mls'),
        'user_updated' => __('User successfully updated.', 'malisafi-mls'),
        'user_deleted' => __('User successfully deleted.', 'malisafi-mls')
    );
    
    if (isset($messages[$_GET['message']])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($messages[$_GET['message']]) . '</p></div>';
    }
}

if (isset($_GET['error'])) {
    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html(urldecode($_GET['error'])) . '</p></div>';
}
?>

<div class="wrap malisafi-users-page">
    
    <?php if ($action === 'list') : ?>
        
        <!-- Users List View -->
        <h1 class="wp-heading-inline"><?php _e('Malisafi Users', 'malisafi-mls'); ?></h1>
        <a href="<?php echo admin_url('admin.php?page=malisafi-users&action=add'); ?>" class="page-title-action">
            <?php _e('Add New User', 'malisafi-mls'); ?>
        </a>
        <hr class="wp-header-end">
        
        <!-- Filters -->
        <div class="tablenav top">
            <div class="alignleft actions">
                <form method="get" action="">
                    <input type="hidden" name="page" value="malisafi-users">
                    
                    <select name="role" id="role-filter">
                        <option value=""><?php _e('All Roles', 'malisafi-mls'); ?></option>
                        <?php foreach (Malisafi_User_Manager::get_available_roles() as $role_key => $role_name) : ?>
                            <option value="<?php echo esc_attr($role_key); ?>" <?php selected(isset($_GET['role']) ? $_GET['role'] : '', $role_key); ?>>
                                <?php echo esc_html($role_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="submit" class="button" value="<?php _e('Filter', 'malisafi-mls'); ?>">
                </form>
            </div>
        </div>
        
        <?php
        // Get users
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $args = array('paged' => $paged, 'number' => 20);
        
        if (isset($_GET['role']) && !empty($_GET['role'])) {
            $args['role'] = sanitize_text_field($_GET['role']);
            $args['role__in'] = array(sanitize_text_field($_GET['role']));
        }
        
        $users_data = Malisafi_User_Manager::get_malisafi_users($args);
        $users = $users_data['users'];
        $total = $users_data['total'];
        ?>
        
        <!-- Users Table -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all">
                    </th>
                    <th class="manage-column column-username"><?php _e('Username', 'malisafi-mls'); ?></th>
                    <th class="manage-column column-name"><?php _e('Name', 'malisafi-mls'); ?></th>
                    <th class="manage-column column-email"><?php _e('Email', 'malisafi-mls'); ?></th>
                    <th class="manage-column column-role"><?php _e('Role', 'malisafi-mls'); ?></th>
                    <th class="manage-column column-subscription"><?php _e('Subscription', 'malisafi-mls'); ?></th>
                    <th class="manage-column column-registered"><?php _e('Registered', 'malisafi-mls'); ?></th>
                    <th class="manage-column column-actions"><?php _e('Actions', 'malisafi-mls'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)) : ?>
                    <?php foreach ($users as $user) : ?>
                        <tr>
                            <td class="check-column">
                                <input type="checkbox" name="users[]" value="<?php echo esc_attr($user->ID); ?>">
                            </td>
                            <td class="column-username">
                                <strong><?php echo esc_html($user->user_login); ?></strong>
                            </td>
                            <td class="column-name">
                                <?php echo esc_html($user->first_name . ' ' . $user->last_name); ?>
                            </td>
                            <td class="column-email">
                                <a href="mailto:<?php echo esc_attr($user->user_email); ?>">
                                    <?php echo esc_html($user->user_email); ?>
                                </a>
                            </td>
                            <td class="column-role">
                                <?php 
                                $roles = $user->roles;
                                if (!empty($roles)) {
                                    echo Malisafi_User_Manager::get_role_badge($roles[0]);
                                }
                                ?>
                            </td>
                            <td class="column-subscription">
                                <?php if ($user->subscription) : ?>
                                    <span class="subscription-status status-<?php echo esc_attr($user->subscription->status); ?>">
                                        <?php echo esc_html(ucfirst($user->subscription->status)); ?>
                                    </span>
                                <?php else : ?>
                                    <span style="color: #8c8f94;"><?php _e('N/A', 'malisafi-mls'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-registered">
                                <?php echo date_i18n('M j, Y', strtotime($user->user_registered)); ?>
                            </td>
                            <td class="column-actions">
                                <a href="<?php echo admin_url('admin.php?page=malisafi-users&action=edit&user_id=' . $user->ID); ?>" class="button button-small">
                                    <?php _e('Edit', 'malisafi-mls'); ?>
                                </a>
                                <?php if ($user->ID !== get_current_user_id()) : ?>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=malisafi_delete_user&user_id=' . $user->ID), 'malisafi_delete_user_' . $user->ID); ?>" 
                                       class="button button-small button-link-delete" 
                                       onclick="return confirm('<?php _e('Are you sure you want to delete this user?', 'malisafi-mls'); ?>');">
                                        <?php _e('Delete', 'malisafi-mls'); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <?php _e('No users found.', 'malisafi-mls'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($users_data['pages'] > 1) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $users_data['pages'],
                        'current' => $paged
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>
        
    <?php elseif ($action === 'add') : ?>
        
        <!-- Add User Form -->
        <h1><?php _e('Add New User', 'malisafi-mls'); ?></h1>
        <hr class="wp-header-end">
        
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="malisafi-user-form" id="add-user-form">
            <input type="hidden" name="action" value="malisafi_add_user">
            <?php wp_nonce_field('malisafi_add_user', 'malisafi_user_nonce'); ?>
            
            <h2><?php _e('Account Information', 'malisafi-mls'); ?></h2>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="user_role"><?php _e('Role', 'malisafi-mls'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <select name="user_role" id="user_role" required>
                                <option value=""><?php _e('Select Role', 'malisafi-mls'); ?></option>
                                <?php foreach (Malisafi_User_Manager::get_available_roles() as $role_key => $role_name) : ?>
                                    <option value="<?php echo esc_attr($role_key); ?>">
                                        <?php echo esc_html($role_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Select the user role first to determine which fields to display.', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="username"><?php _e('Username', 'malisafi-mls'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" name="username" id="username" class="regular-text" required>
                            <p class="description"><?php _e('Username cannot be changed after creation.', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="email"><?php _e('Email', 'malisafi-mls'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="email" name="email" id="email" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="password"><?php _e('Password', 'malisafi-mls'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="password" name="password" id="password" class="regular-text" required>
                            <button type="button" class="button" id="generate-password"><?php _e('Generate', 'malisafi-mls'); ?></button>
                            <p class="description"><?php _e('Minimum 8 characters.', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <h2><?php _e('Personal Information', 'malisafi-mls'); ?></h2>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="first_name"><?php _e('First Name', 'malisafi-mls'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" name="first_name" id="first_name" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="last_name"><?php _e('Last Name', 'malisafi-mls'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" name="last_name" id="last_name" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="phone"><?php _e('Phone', 'malisafi-mls'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="tel" name="phone" id="phone" class="regular-text" placeholder="+254..." required>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Agent-Specific Fields (Hidden by default) -->
            <div id="agent-fields" style="display: none;">
                <h2><?php _e('Agent Professional Information', 'malisafi-mls'); ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="agency_name"><?php _e('Agency Name', 'malisafi-mls'); ?> <span class="required agent-required">*</span></label>
                            </th>
                            <td>
                                <input type="text" name="agency_name" id="agency_name" class="regular-text">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="license_number"><?php _e('License Number', 'malisafi-mls'); ?> <span class="required agent-required">*</span></label>
                            </th>
                            <td>
                                <input type="text" name="license_number" id="license_number" class="regular-text">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="years_experience"><?php _e('Years of Experience', 'malisafi-mls'); ?> <span class="required agent-required">*</span></label>
                            </th>
                            <td>
                                <input type="number" name="years_experience" id="years_experience" class="regular-text" min="0" max="50">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="agent_county"><?php _e('Operating County', 'malisafi-mls'); ?> <span class="required agent-required">*</span></label>
                            </th>
                            <td>
                                <select name="agent_county" id="agent_county" class="regular-text">
                                    <option value=""><?php _e('Select County', 'malisafi-mls'); ?></option>
                                    <?php
                                    if (function_exists('malisafi_get_kenya_counties')) {
                                        foreach (malisafi_get_kenya_counties() as $county) {
                                            echo '<option value="' . esc_attr($county) . '">' . esc_html($county) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="business_address"><?php _e('Business Address', 'malisafi-mls'); ?> <span class="required agent-required">*</span></label>
                            </th>
                            <td>
                                <input type="text" name="business_address" id="business_address" class="regular-text">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="city"><?php _e('City', 'malisafi-mls'); ?> <span class="required agent-required">*</span></label>
                            </th>
                            <td>
                                <input type="text" name="city" id="city" class="regular-text">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="national_id"><?php _e('National ID', 'malisafi-mls'); ?> <span class="required agent-required">*</span></label>
                            </th>
                            <td>
                                <input type="text" name="national_id" id="national_id" class="regular-text">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label><?php _e('Specializations', 'malisafi-mls'); ?> <span class="required agent-required">*</span></label>
                            </th>
                            <td>
                                <label><input type="checkbox" name="specializations[]" value="residential"> <?php _e('Residential', 'malisafi-mls'); ?></label><br>
                                <label><input type="checkbox" name="specializations[]" value="commercial"> <?php _e('Commercial', 'malisafi-mls'); ?></label><br>
                                <label><input type="checkbox" name="specializations[]" value="land"> <?php _e('Land', 'malisafi-mls'); ?></label><br>
                                <label><input type="checkbox" name="specializations[]" value="rental"> <?php _e('Rental Management', 'malisafi-mls'); ?></label><br>
                                <label><input type="checkbox" name="specializations[]" value="investment"> <?php _e('Investment Properties', 'malisafi-mls'); ?></label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="agent_bio"><?php _e('Professional Bio', 'malisafi-mls'); ?> <span class="required agent-required">*</span></label>
                            </th>
                            <td>
                                <textarea name="agent_bio" id="agent_bio" rows="5" class="large-text"></textarea>
                                <p class="description"><?php _e('Minimum 100 characters. Describe your experience and expertise.', 'malisafi-mls'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="office_phone"><?php _e('Office Phone', 'malisafi-mls'); ?></label>
                            </th>
                            <td>
                                <input type="tel" name="office_phone" id="office_phone" class="regular-text">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="whatsapp"><?php _e('WhatsApp Number', 'malisafi-mls'); ?></label>
                            </th>
                            <td>
                                <input type="tel" name="whatsapp" id="whatsapp" class="regular-text">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="website"><?php _e('Website', 'malisafi-mls'); ?></label>
                            </th>
                            <td>
                                <input type="url" name="website" id="website" class="regular-text" placeholder="https://">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="languages"><?php _e('Languages Spoken', 'malisafi-mls'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="languages" id="languages" class="regular-text" placeholder="e.g., English, Swahili">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="service_areas"><?php _e('Service Areas', 'malisafi-mls'); ?></label>
                            </th>
                            <td>
                                <textarea name="service_areas" id="service_areas" rows="3" class="large-text" placeholder="List the areas/neighborhoods you serve"></textarea>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="commission_rate"><?php _e('Commission Rate (%)', 'malisafi-mls'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="commission_rate" id="commission_rate" class="small-text" min="0" max="100" step="0.1">
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <h3><?php _e('Social Media', 'malisafi-mls'); ?></h3>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="facebook"><?php _e('Facebook URL', 'malisafi-mls'); ?></label>
                            </th>
                            <td>
                                <input type="url" name="facebook" id="facebook" class="regular-text" placeholder="https://facebook.com/...">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="twitter"><?php _e('Twitter URL', 'malisafi-mls'); ?></label>
                            </th>
                            <td>
                                <input type="url" name="twitter" id="twitter" class="regular-text" placeholder="https://twitter.com/...">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="linkedin"><?php _e('LinkedIn URL', 'malisafi-mls'); ?></label>
                            </th>
                            <td>
                                <input type="url" name="linkedin" id="linkedin" class="regular-text" placeholder="https://linkedin.com/in/...">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="instagram"><?php _e('Instagram URL', 'malisafi-mls'); ?></label>
                            </th>
                            <td>
                                <input type="url" name="instagram" id="instagram" class="regular-text" placeholder="https://instagram.com/...">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="youtube"><?php _e('YouTube URL', 'malisafi-mls'); ?></label>
                            </th>
                            <td>
                                <input type="url" name="youtube" id="youtube" class="regular-text" placeholder="https://youtube.com/...">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <h2><?php _e('Notification Settings', 'malisafi-mls'); ?></h2>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="send_notification"><?php _e('Send Notification', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="send_notification" id="send_notification" value="1" checked>
                            <label for="send_notification"><?php _e('Send user an email about their account.', 'malisafi-mls'); ?></label>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Add User', 'malisafi-mls'); ?>">
                <a href="<?php echo admin_url('admin.php?page=malisafi-users'); ?>" class="button">
                    <?php _e('Cancel', 'malisafi-mls'); ?>
                </a>
            </p>
        </form>
        
    <?php elseif ($action === 'edit' && $user_id) : ?>
        
        <!-- Edit User Form -->
        <?php 
        $edit_user = get_user_by('id', $user_id);
        if (!$edit_user) {
            echo '<div class="notice notice-error"><p>' . __('User not found.', 'malisafi-mls') . '</p></div>';
            return;
        }
        ?>
        
        <h1><?php printf(__('Edit User: %s', 'malisafi-mls'), esc_html($edit_user->user_login)); ?></h1>
        <hr class="wp-header-end">
        
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="malisafi-user-form">
            <input type="hidden" name="action" value="malisafi_edit_user">
            <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
            <?php wp_nonce_field('malisafi_edit_user', 'malisafi_user_nonce'); ?>
            
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label><?php _e('Username', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <strong><?php echo esc_html($edit_user->user_login); ?></strong>
                            <p class="description"><?php _e('Username cannot be changed.', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="email"><?php _e('Email', 'malisafi-mls'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="email" name="email" id="email" class="regular-text" value="<?php echo esc_attr($edit_user->user_email); ?>" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="first_name"><?php _e('First Name', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="first_name" id="first_name" class="regular-text" value="<?php echo esc_attr($edit_user->first_name); ?>">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="last_name"><?php _e('Last Name', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="last_name" id="last_name" class="regular-text" value="<?php echo esc_attr($edit_user->last_name); ?>">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="phone"><?php _e('Phone', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <input type="tel" name="phone" id="phone" class="regular-text" value="<?php echo esc_attr(get_user_meta($user_id, 'phone', true)); ?>">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="user_role"><?php _e('Role', 'malisafi-mls'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <select name="user_role" id="user_role" required>
                                <?php 
                                $current_role = !empty($edit_user->roles) ? $edit_user->roles[0] : '';
                                foreach (Malisafi_User_Manager::get_available_roles() as $role_key => $role_name) : 
                                ?>
                                    <option value="<?php echo esc_attr($role_key); ?>" <?php selected($current_role, $role_key); ?>>
                                        <?php echo esc_html($role_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="password"><?php _e('New Password', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <input type="password" name="password" id="password" class="regular-text">
                            <button type="button" class="button" id="generate-password"><?php _e('Generate', 'malisafi-mls'); ?></button>
                            <p class="description"><?php _e('Leave blank to keep current password.', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Update User', 'malisafi-mls'); ?>">
                <a href="<?php echo admin_url('admin.php?page=malisafi-users'); ?>" class="button">
                    <?php _e('Cancel', 'malisafi-mls'); ?>
                </a>
            </p>
        </form>
        
    <?php endif; ?>
    
</div>

<style>
.malisafi-users-page {
    max-width: 1400px;
}

.malisafi-users-page .wp-list-table {
    margin-top: 20px;
}

.malisafi-users-page .column-username {
    width: 12%;
}

.malisafi-users-page .column-name {
    width: 15%;
}

.malisafi-users-page .column-email {
    width: 18%;
}

.malisafi-users-page .column-role {
    width: 12%;
}

.malisafi-users-page .column-subscription {
    width: 10%;
}

.malisafi-users-page .column-registered {
    width: 10%;
}

.malisafi-users-page .column-actions {
    width: 15%;
}

.malisafi-users-page .subscription-status {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}

.malisafi-users-page .status-active {
    background-color: #00a32a;
    color: white;
}

.malisafi-users-page .status-pending {
    background-color: #dba617;
    color: white;
}

.malisafi-users-page .status-expired,
.malisafi-users-page .status-cancelled {
    background-color: #d63638;
    color: white;
}

.malisafi-user-form {
    max-width: 800px;
    margin-top: 20px;
}

.malisafi-user-form .required {
    color: #d63638;
}

.malisafi-user-form #generate-password {
    margin-left: 10px;
}

.button-link-delete {
    color: #b32d2e !important;
}

.button-link-delete:hover {
    color: #d63638 !important;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Generate password
    $('#generate-password').on('click', function(e) {
        e.preventDefault();
        var length = 12;
        var charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
        var password = "";
        for (var i = 0; i < length; i++) {
            password += charset.charAt(Math.floor(Math.random() * charset.length));
        }
        $('#password').val(password);
        $('#password').attr('type', 'text');
    });
    
    // Select all checkbox
    $('#cb-select-all').on('change', function() {
        $('input[name="users[]"]').prop('checked', $(this).prop('checked'));
    });
    
    // Show/hide agent fields based on role selection
    $('#user_role').on('change', function() {
        var role = $(this).val();
        var isAgent = role && (role.indexOf('agent') !== -1);
        
        if (isAgent) {
            $('#agent-fields').slideDown(300);
            // Make agent fields required
            $('#agent-fields input[type="text"], #agent-fields input[type="number"], #agent-fields select, #agent-fields textarea').each(function() {
                var $field = $(this);
                if ($field.siblings('label').find('.agent-required').length || $field.closest('tr').find('.agent-required').length) {
                    $field.prop('required', true);
                }
            });
            // At least one specialization required
            $('input[name="specializations[]"]').attr('data-required-group', 'specializations');
        } else {
            $('#agent-fields').slideUp(300);
            // Remove required from agent fields
            $('#agent-fields input, #agent-fields select, #agent-fields textarea').prop('required', false);
            $('#agent-fields input[type="checkbox"]').prop('checked', false);
        }
    });
    
    // Form validation
    $('#add-user-form').on('submit', function(e) {
        var isAgent = $('#user_role').val().indexOf('agent') !== -1;
        
        if (isAgent) {
            // Check if at least one specialization is selected
            if ($('input[name="specializations[]"]:checked').length === 0) {
                e.preventDefault();
                alert('<?php _e('Please select at least one specialization.', 'malisafi-mls'); ?>');
                return false;
            }
            
            // Check bio length
            var bio = $('#agent_bio').val();
            if (bio.length < 100) {
                e.preventDefault();
                alert('<?php _e('Professional bio must be at least 100 characters.', 'malisafi-mls'); ?>');
                $('#agent_bio').focus();
                return false;
            }
        }
        
        // Check password length
        var password = $('#password').val();
        if (password.length < 8) {
            e.preventDefault();
            alert('<?php _e('Password must be at least 8 characters long.', 'malisafi-mls'); ?>');
            $('#password').focus();
            return false;
        }
        
        return true;
    });
    
    // Bio character counter
    $('#agent_bio').on('input', function() {
        var length = $(this).val().length;
        var minLength = 100;
        var $description = $(this).siblings('.description');
        
        if (length < minLength) {
            $description.html('<?php _e('Minimum 100 characters.', 'malisafi-mls'); ?> ' + length + '/100');
            $description.css('color', '#d63638');
        } else {
            $description.html(length + ' <?php _e('characters', 'malisafi-mls'); ?>');
            $description.css('color', '#00a32a');
        }
    });
    
    // Real-time validation styling
    $('input[required], select[required], textarea[required]').on('blur', function() {
        if ($(this).val() === '') {
            $(this).css('border-color', '#d63638');
        } else {
            $(this).css('border-color', '');
        }
    });
    
    // Email format validation
    $('#email').on('blur', function() {
        var email = $(this).val();
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            $(this).css('border-color', '#d63638');
            $(this).siblings('.description').remove();
            $(this).after('<p class="description" style="color: #d63638;"><?php _e('Please enter a valid email address.', 'malisafi-mls'); ?></p>');
        } else {
            $(this).css('border-color', '');
            $(this).siblings('.description').remove();
        }
    });
});
</script>
