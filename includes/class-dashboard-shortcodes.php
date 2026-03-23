<?php
/**
 * Dashboard Shortcodes Class
 *
 * Handles all dashboard-related shortcodes for different user roles
 *
 * @package MalisafiMLS
 * @since 1.0.0
 * @version 1.0.0
 * category includes
 **/

namespace MalisafiMLS;

if (!defined('ABSPATH')) {
	exit;
}

class Dashboard_Shortcodes {
	/**
	 * Initialize the shortcodes
	 */
	public static function init() {
		add_shortcode('malisafi_client_dashboard', [__CLASS__, 'client_dashboard']);
		add_shortcode('malisafi_favorites', [__CLASS__, 'client_favorites']);
		add_shortcode('malisafi_saved_searches', [__CLASS__, 'client_saved_searches']);
		add_shortcode('malisafi_client_inquiries', [__CLASS__, 'client_inquiries']);

		add_shortcode('malisafi_agent_dashboard', [__CLASS__, 'agent_dashboard']);
		add_shortcode('malisafi_agent_properties', [__CLASS__, 'agent_properties']);
		add_shortcode('malisafi_agent_add_property', [__CLASS__, 'agent_add_property']);
		add_shortcode('malisafi_agent_leads', [__CLASS__, 'agent_leads']);
		add_shortcode('malisafi_agent_inquiries', [__CLASS__, 'agent_inquiries']);
		add_shortcode('malisafi_agent_profile_view', [__CLASS__, 'agent_profile_public']);

		add_shortcode('malisafi_owner_dashboard', [__CLASS__, 'owner_dashboard']);
		add_shortcode('malisafi_owner_properties', [__CLASS__, 'owner_properties']);
		add_shortcode('malisafi_owner_inquiries', [__CLASS__, 'owner_inquiries']);

		add_shortcode('malisafi_agency_dashboard', [__CLASS__, 'agency_dashboard']);
		add_shortcode('malisafi_agency_inquiries', [__CLASS__, 'agency_inquiries']);
		add_shortcode('malisafi_agency_agents', [__CLASS__, 'agency_agents']);

		add_shortcode('malisafi_developer_dashboard', [__CLASS__, 'developer_dashboard']);
		add_shortcode('malisafi_developer_projects', [__CLASS__, 'developer_projects']);
		add_shortcode('malisafi_developer_analytics', [__CLASS__, 'developer_analytics']);

		add_shortcode('malisafi_property_submit', [__CLASS__, 'property_submit_form']);
		add_shortcode('malisafi_project_submit', [__CLASS__, 'project_submit_form']);
		add_shortcode('malisafi_login', [__CLASS__, 'login_form']);
		add_shortcode('malisafi_registration', [__CLASS__, 'register_form']);
		add_shortcode('malisafi_account', [__CLASS__, 'account_page']);

		add_action('wp_ajax_malisafi_custom_login', [__CLASS__, 'ajax_custom_login']);
		add_action('wp_ajax_nopriv_malisafi_custom_login', [__CLASS__, 'ajax_custom_login']);
		add_action('admin_post_malisafi_delete_property', [__CLASS__, 'handle_delete_property']);
		add_action('admin_post_malisafi_restore_property', [__CLASS__, 'handle_restore_property']);
		add_action('admin_post_malisafi_delete_property_permanently', [__CLASS__, 'handle_delete_property_permanently']);
	}

	public static function handle_delete_property() {
		if (!is_user_logged_in()) {
			wp_die(__('You must be logged in to delete a property.', 'malisafi-mls'));
		}

		$property_id = isset($_GET['property_id']) ? (int) $_GET['property_id'] : 0;
		if (!$property_id) {
			wp_die(__('Invalid property.', 'malisafi-mls'));
		}

		check_admin_referer('malisafi_delete_property_' . $property_id);

		$property = get_post($property_id);
		if (!$property || $property->post_type !== 'malisafi_property') {
			wp_die(__('Invalid property.', 'malisafi-mls'));
		}

		if ((int) $property->post_author !== get_current_user_id() && !current_user_can('delete_post', $property_id)) {
			wp_die(__('You do not have permission to delete this property.', 'malisafi-mls'));
		}

		wp_trash_post($property_id);

		$redirect = isset($_GET['redirect']) ? esc_url_raw($_GET['redirect']) : wp_get_referer();
		if (!$redirect) {
			$redirect = home_url('/agent-dashboard/?section=properties');
		}
		$redirect = add_query_arg('mf_deleted', '1', $redirect);
		wp_safe_redirect($redirect);
		exit;
	}

	public static function handle_restore_property() {
		if (!is_user_logged_in()) {
			wp_die(__('You must be logged in to restore a property.', 'malisafi-mls'));
		}

		$property_id = isset($_GET['property_id']) ? (int) $_GET['property_id'] : 0;
		if (!$property_id) {
			wp_die(__('Invalid property.', 'malisafi-mls'));
		}

		check_admin_referer('malisafi_restore_property_' . $property_id);

		$property = get_post($property_id);
		if (!$property || $property->post_type !== 'malisafi_property') {
			wp_die(__('Invalid property.', 'malisafi-mls'));
		}

		if ((int) $property->post_author !== get_current_user_id() && !current_user_can('edit_post', $property_id)) {
			wp_die(__('You do not have permission to restore this property.', 'malisafi-mls'));
		}

		wp_untrash_post($property_id);

		$redirect = isset($_GET['redirect']) ? esc_url_raw($_GET['redirect']) : wp_get_referer();
		if (!$redirect) {
			$redirect = home_url('/agent-dashboard/?section=properties');
		}
		$redirect = add_query_arg('mf_restored', '1', $redirect);
		wp_safe_redirect($redirect);
		exit;
	}

	public static function handle_delete_property_permanently() {
		if (!is_user_logged_in()) {
			wp_die(__('You must be logged in to delete a property.', 'malisafi-mls'));
		}

		$property_id = isset($_GET['property_id']) ? (int) $_GET['property_id'] : 0;
		if (!$property_id) {
			wp_die(__('Invalid property.', 'malisafi-mls'));
		}

		check_admin_referer('malisafi_delete_property_permanently_' . $property_id);

		$property = get_post($property_id);
		if (!$property || $property->post_type !== 'malisafi_property') {
			wp_die(__('Invalid property.', 'malisafi-mls'));
		}

		if ((int) $property->post_author !== get_current_user_id() && !current_user_can('delete_post', $property_id)) {
			wp_die(__('You do not have permission to delete this property.', 'malisafi-mls'));
		}

		wp_delete_post($property_id, true);

		$redirect = isset($_GET['redirect']) ? esc_url_raw($_GET['redirect']) : wp_get_referer();
		if (!$redirect) {
			$redirect = home_url('/agent-dashboard/?section=properties');
		}
		$redirect = add_query_arg('mf_deleted_permanently', '1', $redirect);
		wp_safe_redirect($redirect);
		exit;
	}

	private static function require_login() {
		if (is_user_logged_in()) {
			return '';
		}

		$login_url = Page_Manager::get_page_url('login');
		if (!$login_url) {
			$login_url = wp_login_url();
		}

		return '<div class="malisafi-access-denied"><p>' . sprintf(
			__('Please <a href="%s">log in</a> to access this page.', 'malisafi-mls'),
			esc_url($login_url)
		) . '</p></div>';
	}

	public static function property_submit_form($atts) {
		if (!class_exists('MalisafiMLS\\Property_Submission')) {
			return '<div class="malisafi-access-denied"><p>' . __('Property submission is currently unavailable.', 'malisafi-mls') . '</p></div>';
		}

		// Enqueue assets for property submission
		wp_enqueue_style(
			'malisafi-property-submission',
			MALISAFI_MLS_URL . 'assets/css/property-submission.css',
			array('malisafi-mls-variables'),
			MALISAFI_MLS_VERSION
		);

		wp_enqueue_script(
			'malisafi-property-submission',
			MALISAFI_MLS_URL . 'assets/js/property-submission.js',
			array('jquery', 'jquery-ui-sortable'),
			MALISAFI_MLS_VERSION,
			true
		);

		wp_localize_script('malisafi-property-submission', 'malisafiSubmission', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('malisafi_property_submission'),
			'uploadNonce' => wp_create_nonce('malisafi_upload_images'),
			'refNonce' => wp_create_nonce('malisafi_generate_ref_id'),
			'uploadsEnabled' => true,
			'fieldRegistry' => \MalisafiMLS\Property_Submission::get_field_registry(),
			'strings' => array(
				'saving' => __('Saving...', 'malisafi-mls'),
				'saved' => __('Saved', 'malisafi-mls'),
				'error' => __('Error saving', 'malisafi-mls'),
				'uploading' => __('Uploading...', 'malisafi-mls'),
				'uploadError' => __('Upload failed', 'malisafi-mls'),
				'confirmDelete' => __('Are you sure you want to delete this image?', 'malisafi-mls'),
				'submitProperty' => __('Submit Property', 'malisafi-mls'),
				'submitting' => __('Submitting...', 'malisafi-mls'),
				'success' => __('Property submitted successfully!', 'malisafi-mls'),
			)
		));

		return \MalisafiMLS\Property_Submission::render_submission_form($atts);
	}

	public static function project_submit_form($atts) {
		if (!class_exists('MalisafiMLS\\Project_Submission')) {
			return '<div class="malisafi-access-denied"><p>' . __('Project submission is currently unavailable.', 'malisafi-mls') . '</p></div>';
		}

		return \MalisafiMLS\Project_Submission::render_submission_form($atts);
	}

	public static function client_dashboard($atts) {
		$login_check = self::require_login();
		if ($login_check) {
			return $login_check;
		}

		$user_id = get_current_user_id();
		$logout_url = wp_logout_url(home_url());
		$favorites_url = Page_Manager::get_page_url('client_favorites');
		$searches_url = Page_Manager::get_page_url('client_searches');
		$inquiries_url = Page_Manager::get_page_url('client_inquiries');

		ob_start();
		?>
		<div class="malisafi-client-dashboard">
			<div class="dashboard-header">
				<h1><?php _e('My Dashboard', 'malisafi-mls'); ?></h1>
				<a class="button button-secondary" href="<?php echo esc_url($logout_url); ?>">
					<?php _e('Logout', 'malisafi-mls'); ?>
				</a>
			</div>
			<div class="dashboard-cards">
				<div class="dashboard-card">
					<h3><?php _e('Favorites', 'malisafi-mls'); ?></h3>
					<p class="count"><?php echo esc_html(self::get_favorites_count($user_id)); ?></p>
					<?php if ($favorites_url): ?>
						<a href="<?php echo esc_url($favorites_url); ?>" class="button button-secondary"><?php _e('View', 'malisafi-mls'); ?></a>
					<?php endif; ?>
				</div>
				<div class="dashboard-card">
					<h3><?php _e('Saved Searches', 'malisafi-mls'); ?></h3>
					<p class="count"><?php echo esc_html(self::get_saved_searches_count($user_id)); ?></p>
					<?php if ($searches_url): ?>
						<a href="<?php echo esc_url($searches_url); ?>" class="button button-secondary"><?php _e('View', 'malisafi-mls'); ?></a>
					<?php endif; ?>
				</div>
				<div class="dashboard-card">
					<h3><?php _e('Inquiries', 'malisafi-mls'); ?></h3>
					<p class="count"><?php echo esc_html(self::get_inquiries_count($user_id)); ?></p>
					<?php if ($inquiries_url): ?>
						<a href="<?php echo esc_url($inquiries_url); ?>" class="button button-secondary"><?php _e('View', 'malisafi-mls'); ?></a>
					<?php endif; ?>
				</div>
			</div>

			<div class="dashboard-section">
				<h2><?php _e('Recently Viewed', 'malisafi-mls'); ?></h2>
				<?php echo self::get_recent_viewed_properties($user_id); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function client_favorites($atts) {
		$login_check = self::require_login();
		if ($login_check) {
			return $login_check;
		}

		$favorites = get_user_meta(get_current_user_id(), 'malisafi_favorites', true) ?: [];
		if (empty($favorites)) {
			return '<p>' . __('No favorites saved yet.', 'malisafi-mls') . '</p>';
		}

		$query = new \WP_Query([
			'post_type' => 'malisafi_property',
			'post__in' => $favorites,
			'posts_per_page' => 20,
			'orderby' => 'post__in'
		]);

		ob_start();
		?>
		<div class="malisafi-client-favorites">
			<h1><?php _e('My Favorites', 'malisafi-mls'); ?></h1>
			<?php if ($query->have_posts()) : ?>
				<ul class="favorites-list">
					<?php while ($query->have_posts()) : $query->the_post(); ?>
						<li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
					<?php endwhile; ?>
				</ul>
			<?php else : ?>
				<p><?php _e('No favorites found.', 'malisafi-mls'); ?></p>
			<?php endif; ?>
		</div>
		<?php
		wp_reset_postdata();
		return ob_get_clean();
	}

	public static function client_saved_searches($atts) {
		$login_check = self::require_login();
		if ($login_check) {
			return $login_check;
		}

		$searches = get_user_meta(get_current_user_id(), 'malisafi_saved_searches', true) ?: [];
		if (empty($searches)) {
			return '<p>' . __('No saved searches yet.', 'malisafi-mls') . '</p>';
		}

		ob_start();
		?>
		<div class="malisafi-client-searches">
			<h1><?php _e('Saved Searches', 'malisafi-mls'); ?></h1>
			<ul class="saved-searches-list">
				<?php foreach ($searches as $search) : ?>
					<li>
						<span><?php echo esc_html(self::format_search_criteria($search)); ?></span>
						<a class="button button-secondary" href="<?php echo esc_url(self::build_search_url($search)); ?>">
							<?php _e('Run Search', 'malisafi-mls'); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function client_inquiries($atts) {
		$login_check = self::require_login();
		if ($login_check) {
			return $login_check;
		}

		// Enqueue dashboard styles
		wp_enqueue_style('malisafi-dashboards', MALISAFI_MLS_URL . 'assets/css/dashboards.css', array(), MALISAFI_MLS_VERSION);

		global $wpdb;
		$table_name = $wpdb->prefix . 'mf_inquiries';
		$rows = $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE client_id = %d ORDER BY inquiry_id DESC LIMIT 20",
			get_current_user_id()
		), ARRAY_A);

		ob_start();
		?>
		<div class="malisafi-client-inquiries">
			<h1><?php _e('My Inquiries', 'malisafi-mls'); ?></h1>
			<?php if (empty($rows)) : ?>
				<p><?php _e('No inquiries found yet.', 'malisafi-mls'); ?></p>
			<?php else : ?>
				<ul class="inquiries-list">
					<?php foreach ($rows as $row) : ?>
						<?php $property_id = isset($row['property_id']) ? (int) $row['property_id'] : 0; ?>
						<li class="inquiry-item">
							<div class="inquiry-header">
								<?php if ($property_id) : ?>
									<a href="<?php echo esc_url(get_permalink($property_id)); ?>" class="property-link">
										<?php echo esc_html(get_the_title($property_id)); ?>
									</a>
								<?php else : ?>
									<span><?php _e('Property Inquiry', 'malisafi-mls'); ?></span>
								<?php endif; ?>
								<span class="inquiry-status status-<?php echo esc_attr($row['status']); ?>">
									<?php echo esc_html(ucfirst($row['status'])); ?>
								</span>
							</div>
							<div class="inquiry-meta">
								<?php if (!empty($row['created_at'])) : ?>
									<small class="inquiry-date"><?php echo esc_html(date('M j, Y g:i A', strtotime($row['created_at']))); ?></small>
								<?php endif; ?>
								<?php if (!empty($row['inquiry_type'])) : ?>
									<small class="inquiry-type"><?php echo esc_html(ucfirst(str_replace('_', ' ', $row['inquiry_type']))); ?></small>
								<?php endif; ?>
							</div>
							<?php if (!empty($row['message'])) : ?>
								<div class="inquiry-message">
									<?php echo esc_html(wp_trim_words($row['message'], 20)); ?>
								</div>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function agent_dashboard($atts) {
		$login_check = self::require_login();
		if ($login_check) {
			return $login_check;
		}

		$current_user = wp_get_current_user();
		$is_agent = in_array('malisafi_agent_basic', $current_user->roles) || in_array('malisafi_agent_premium', $current_user->roles);
		if (!$is_agent && !current_user_can('administrator')) {
			return '<div class="malisafi-access-denied"><p>' . __('Access restricted to agents only.', 'malisafi-mls') . '</p></div>';
		}

		wp_enqueue_style('agent-dashboard-modern', MALISAFI_MLS_URL . 'assets/css/agent-dashboard-modern.css', array(), MALISAFI_MLS_VERSION);
		wp_enqueue_script('agent-dashboard-modern', MALISAFI_MLS_URL . 'assets/js/agent-dashboard-modern.js', array('jquery'), MALISAFI_MLS_VERSION, true);

		ob_start();
		include MALISAFI_MLS_PATH . 'templates/agent-dashboard-modern.php';
		return ob_get_clean();
	}

	public static function agent_properties($atts) {
		$login_check = self::require_login();
		if ($login_check) {
			return $login_check;
		}

		ob_start();
		include MALISAFI_MLS_PATH . 'templates/agent-dashboard-properties.php';
		return ob_get_clean();
	}

	public static function agent_add_property($atts) {
		return self::property_submit_form($atts);
	}

	public static function agent_leads($atts) {
		$login_check = self::require_login();
		if ($login_check) {
			return $login_check;
		}

		ob_start();
		include MALISAFI_MLS_PATH . 'templates/agent-dashboard-leads.php';
		return ob_get_clean();
	}

	/**
	 * Agent Inquiries shortcode
	 * Shows inquiries received by the logged-in agent
	 */
	public static function agent_inquiries($atts) {
		$login_check = self::require_login();
		if ($login_check) {
			return $login_check;
		}

		$current_user = wp_get_current_user();
		$is_agent = in_array('malisafi_agent_basic', $current_user->roles) || 
		            in_array('malisafi_agent_premium', $current_user->roles);
		
		if (!$is_agent && !current_user_can('administrator')) {
			return '<div class="malisafi-access-denied"><p>' . __('Access restricted to agents only.', 'malisafi-mls') . '</p></div>';
		}

		// Enqueue dashboard styles
		wp_enqueue_style('malisafi-dashboards', MALISAFI_MLS_URL . 'assets/css/dashboards.css', array(), MALISAFI_MLS_VERSION);

		global $wpdb;
		$table_name = $wpdb->prefix . 'mf_inquiries';
		$user_id = get_current_user_id();

		// Get inquiries for this specific agent
		$rows = $wpdb->get_results($wpdb->prepare(
			"SELECT i.*, p.post_title as property_title
			 FROM {$table_name} i
			 LEFT JOIN {$wpdb->posts} p ON i.property_id = p.ID
			 WHERE i.agent_id = %d
			 ORDER BY i.inquiry_id DESC LIMIT 50",
			$user_id
		), ARRAY_A);

		ob_start();
		?>
		<div class="malisafi-agent-inquiries">
			<h1><?php _e('My Inquiries', 'malisafi-mls'); ?></h1>
			<p><?php _e('Property inquiries you have received from potential clients.', 'malisafi-mls'); ?></p>

			<?php if (empty($rows)) : ?>
				<div class="malisafi-no-results">
					<p><?php _e('No inquiries found. Inquiries from clients interested in your properties will appear here.', 'malisafi-mls'); ?></p>
				</div>
			<?php else : ?>
				<div class="inquiries-summary">
					<?php
					$unread_count = count(array_filter($rows, function($r) { return $r['status'] === 'new'; }));
					$total_count = count($rows);
					?>
					<p><strong><?php echo esc_html($unread_count); ?></strong> <?php _e('unread', 'malisafi-mls'); ?> | 
					   <strong><?php echo esc_html($total_count); ?></strong> <?php _e('total inquiries', 'malisafi-mls'); ?></p>
				</div>

				<ul class="agent-inquiries-list">
					<?php foreach ($rows as $row) : ?>
						<?php $property_id = isset($row['property_id']) ? (int) $row['property_id'] : 0; ?>
						<li class="inquiry-item status-<?php echo esc_attr($row['status']); ?>">
							<div class="inquiry-header">
								<?php if ($property_id && !empty($row['property_title'])) : ?>
									<a href="<?php echo esc_url(get_permalink($property_id)); ?>" class="property-link">
										<?php echo esc_html($row['property_title']); ?>
									</a>
								<?php else : ?>
									<span><?php _e('Property Inquiry', 'malisafi-mls'); ?></span>
								<?php endif; ?>
								<span class="inquiry-status badge-<?php echo esc_attr($row['status']); ?>">
									<?php echo esc_html(ucfirst($row['status'])); ?>
								</span>
							</div>

							<div class="inquiry-meta">
								<?php if (!empty($row['created_at'])) : ?>
									<small class="inquiry-date">
										<strong><?php _e('Received:', 'malisafi-mls'); ?></strong> 
										<?php echo esc_html(date('M j, Y \a\t g:i A', strtotime($row['created_at']))); ?>
									</small>
								<?php endif; ?>
								<?php if (!empty($row['inquiry_type'])) : ?>
									<small class="inquiry-type">
										<strong><?php _e('Type:', 'malisafi-mls'); ?></strong> 
										<?php echo esc_html(ucfirst(str_replace('_', ' ', $row['inquiry_type']))); ?>
									</small>
								<?php endif; ?>
							</div>

							<?php if (!empty($row['message'])) : ?>
								<div class="inquiry-message">
									<strong><?php _e('Message:', 'malisafi-mls'); ?></strong>
									<p><?php echo esc_html($row['message']); ?></p>
								</div>
							<?php endif; ?>

							<div class="inquiry-contact">
								<strong><?php _e('Client Contact:', 'malisafi-mls'); ?></strong>
								<?php if (!empty($row['client_name'])) : ?>
									<span class="client-name"><?php echo esc_html($row['client_name']); ?></span>
								<?php endif; ?>
								<?php if (!empty($row['client_email'])) : ?>
									<span class="client-email">
										<a href="mailto:<?php echo esc_attr($row['client_email']); ?>">
											<?php echo esc_html($row['client_email']); ?>
										</a>
									</span>
								<?php endif; ?>
								<?php if (!empty($row['client_phone'])) : ?>
									<span class="client-phone">
										<a href="tel:<?php echo esc_attr($row['client_phone']); ?>">
											<?php echo esc_html($row['client_phone']); ?>
										</a>
									</span>
								<?php endif; ?>
							</div>

							<?php if (!empty($row['preferred_contact_time'])) : ?>
								<div class="inquiry-preferences">
									<small>
										<strong><?php _e('Best time to contact:', 'malisafi-mls'); ?></strong> 
										<?php echo esc_html(ucfirst(str_replace('_', ' ', $row['preferred_contact_time']))); ?>
									</small>
								</div>
							<?php endif; ?>

							<?php if (!empty($row['tour_requested_date'])) : ?>
								<div class="inquiry-tour">
									<small>
										<strong><?php _e('Requested tour date:', 'malisafi-mls'); ?></strong> 
										<?php echo esc_html(date('M j, Y \a\t g:i A', strtotime($row['tour_requested_date']))); ?>
									</small>
								</div>
							<?php endif; ?>

							<?php if ($row['email_sent']) : ?>
								<div class="inquiry-email-status">
									<small class="email-sent">
										✓ <?php _e('Email notification sent', 'malisafi-mls'); ?>
										<?php if (!empty($row['email_recipient'])) : ?>
											<?php _e('to', 'malisafi-mls'); ?> <?php echo esc_html($row['email_recipient']); ?>
										<?php endif; ?>
									</small>
								</div>
							<?php elseif ($row['status'] === 'email_failed') : ?>
								<div class="inquiry-email-status">
									<small class="email-failed">
										⚠ <?php _e('Email notification failed', 'malisafi-mls'); ?>
									</small>
								</div>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function owner_dashboard($atts) {
		$login_check = self::require_login();
		if ($login_check) {
			return $login_check;
		}

		$user_id = get_current_user_id();
		$logout_url = wp_logout_url(home_url());
		$properties_url = Page_Manager::get_page_url('owner_properties');
		$inquiries_url = Page_Manager::get_page_url('owner_inquiries');

		ob_start();
		?>
		<div class="malisafi-owner-dashboard">
			<div class="dashboard-header">
				<h1><?php _e('Owner Dashboard', 'malisafi-mls'); ?></h1>
				<a class="button button-secondary" href="<?php echo esc_url($logout_url); ?>">
					<?php _e('Logout', 'malisafi-mls'); ?>
				</a>
			</div>
			<p><?php _e('Manage your properties and inquiries.', 'malisafi-mls'); ?></p>
			<ul class="dashboard-links">
				<?php if ($properties_url): ?>
					<li><a href="<?php echo esc_url($properties_url); ?>"><?php _e('My Properties', 'malisafi-mls'); ?></a></li>
				<?php endif; ?>
				<?php if ($inquiries_url): ?>
					<li><a href="<?php echo esc_url($inquiries_url); ?>"><?php _e('Inquiries', 'malisafi-mls'); ?></a></li>
				<?php endif; ?>
			</ul>
			<p><?php echo sprintf(__('You have %d properties.', 'malisafi-mls'), esc_html(self::get_user_properties_count($user_id))); ?></p>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function owner_properties($atts) {
		$login_check = self::require_login();
		if ($login_check) {
			return $login_check;
		}

		$query = new \WP_Query([
			'post_type' => 'malisafi_property',
			'author' => get_current_user_id(),
			'posts_per_page' => 20,
			'post_status' => array('publish', 'pending', 'draft')
		]);

		$edit_base = Page_Manager::get_page_url('owner_add_property');

		ob_start();
		?>
		<div class="malisafi-owner-properties">
			<h1><?php _e('My Properties', 'malisafi-mls'); ?></h1>
			<?php if ($query->have_posts()) : ?>
				<div class="owner-properties-list">
					<?php while ($query->have_posts()) : $query->the_post(); ?>
						<?php
						$edit_url = $edit_base ? add_query_arg('property_id', get_the_ID(), $edit_base) : '';
						$status = get_post_status();
						?>
						<div class="property-item">
							<div class="property-info">
								<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
								<div class="property-meta">
									<span class="status status-<?php echo esc_attr($status); ?>">
										<?php echo esc_html(ucfirst($status)); ?>
									</span>
									<span class="date"><?php echo esc_html(get_the_date()); ?></span>
								</div>
							</div>
							<div class="property-actions">
								<?php if ($edit_url) : ?>
									<a class="button button-secondary" href="<?php echo esc_url($edit_url); ?>">
										<?php _e('Edit', 'malisafi-mls'); ?>
									</a>
								<?php endif; ?>
								<a class="button" href="<?php echo esc_url(get_permalink()); ?>">
									<?php _e('View', 'malisafi-mls'); ?>
								</a>
							</div>
						</div>
					<?php endwhile; ?>
				</div>
			<?php else : ?>
				<p><?php _e('No properties found.', 'malisafi-mls'); ?></p>
			<?php endif; ?>
		</div>
		<?php
		wp_reset_postdata();
		return ob_get_clean();
	}

	public static function owner_inquiries($atts) {
		$login_check = self::require_login();
		if ($login_check) {
			return $login_check;
		}

		// Enqueue dashboard styles
		wp_enqueue_style('malisafi-dashboards', MALISAFI_MLS_URL . 'assets/css/dashboards.css', array(), MALISAFI_MLS_VERSION);

		global $wpdb;
		$table_name = $wpdb->prefix . 'mf_inquiries';
		$rows = $wpdb->get_results($wpdb->prepare(
			"SELECT i.* FROM {$table_name} i LEFT JOIN {$wpdb->posts} p ON i.property_id = p.ID WHERE p.post_author = %d ORDER BY i.inquiry_id DESC LIMIT 20",
			get_current_user_id()
		), ARRAY_A);

		ob_start();
		?>
		<div class="malisafi-owner-inquiries">
			<h1><?php _e('Property Inquiries', 'malisafi-mls'); ?></h1>
			<?php if (empty($rows)) : ?>
				<p><?php _e('No inquiries found.', 'malisafi-mls'); ?></p>
			<?php else : ?>
				<ul class="owner-inquiries-list">
					<?php foreach ($rows as $row) : ?>
						<?php $property_id = isset($row['property_id']) ? (int) $row['property_id'] : 0; ?>
						<li class="inquiry-item">
							<div class="inquiry-header">
								<?php if ($property_id) : ?>
									<a href="<?php echo esc_url(get_permalink($property_id)); ?>" class="property-link">
										<?php echo esc_html(get_the_title($property_id)); ?>
									</a>
								<?php else : ?>
									<span><?php _e('Property Inquiry', 'malisafi-mls'); ?></span>
								<?php endif; ?>
								<span class="inquiry-status status-<?php echo esc_attr($row['status']); ?>">
									<?php echo esc_html(ucfirst($row['status'])); ?>
								</span>
							</div>
							<div class="inquiry-meta">
								<?php if (!empty($row['created_at'])) : ?>
									<small class="inquiry-date"><?php echo esc_html(date('M j, Y g:i A', strtotime($row['created_at']))); ?></small>
								<?php endif; ?>
								<?php if (!empty($row['inquiry_type'])) : ?>
									<small class="inquiry-type"><?php echo esc_html(ucfirst(str_replace('_', ' ', $row['inquiry_type']))); ?></small>
								<?php endif; ?>
							</div>
							<?php if (!empty($row['message'])) : ?>
								<div class="inquiry-message">
									<?php echo esc_html(wp_trim_words($row['message'], 20)); ?>
								</div>
							<?php endif; ?>
							<?php if (!empty($row['client_email'])) : ?>
								<div class="inquiry-contact">
									<small><?php _e('From:', 'malisafi-mls'); ?> <?php echo esc_html($row['client_email']); ?>
									<?php if (!empty($row['client_phone'])) : ?>
										| <?php echo esc_html($row['client_phone']); ?>
									<?php endif; ?>
									</small>
								</div>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function agency_dashboard($atts) {
		$login_check = self::require_login();
		if ($login_check) {
			return $login_check;
		}

		$current_user = wp_get_current_user();
		$is_agency = in_array('malisafi_agency', $current_user->roles);
		if (!$is_agency && !current_user_can('administrator')) {
			return '<div class="malisafi-access-denied"><p>' . __('Access restricted to agencies only.', 'malisafi-mls') . '</p></div>';
		}

		$user_id = get_current_user_id();
		$logout_url = wp_logout_url(home_url());
		$inquiries_url = Page_Manager::get_page_url('agency_inquiries');
		$agents_url = Page_Manager::get_page_url('agency_agents');

		// Get agency info
		$agency = \MalisafiMLS\Agency_Manager::get_agency_profile($user_id);
		$agency_name = $agency ? $agency->agency_name : __('Your Agency', 'malisafi-mls');

		// Get stats
		$agent_count = count(\MalisafiMLS\Agency_Manager::get_agency_agents($user_id));
		$inquiry_count = self::get_agency_inquiries_count($user_id);

		ob_start();
		?>
		<div class="malisafi-agency-dashboard">
			<div class="dashboard-header">
				<h1><?php echo esc_html($agency_name); ?> Dashboard</h1>
				<a class="button button-secondary" href="<?php echo esc_url($logout_url); ?>">
					<?php _e('Logout', 'malisafi-mls'); ?>
				</a>
			</div>

			<div class="dashboard-overview">
				<div class="overview-card">
					<h3><?php _e('Agents', 'malisafi-mls'); ?></h3>
					<div class="metric"><?php echo esc_html($agent_count); ?></div>
				</div>
				<div class="overview-card">
					<h3><?php _e('Inquiries', 'malisafi-mls'); ?></h3>
					<div class="metric"><?php echo esc_html($inquiry_count); ?></div>
				</div>
			</div>

			<p><?php _e('Manage your agency, agents, and property inquiries.', 'malisafi-mls'); ?></p>

			<ul class="dashboard-links">
				<?php if ($agents_url): ?>
					<li><a href="<?php echo esc_url($agents_url); ?>"><?php _e('My Agents', 'malisafi-mls'); ?></a></li>
				<?php endif; ?>
				<?php if ($inquiries_url): ?>
					<li><a href="<?php echo esc_url($inquiries_url); ?>"><?php _e('Agent Inquiries', 'malisafi-mls'); ?></a></li>
				<?php endif; ?>
			</ul>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function agency_inquiries($atts) {
		$login_check = self::require_login();
		if ($login_check) {
			return $login_check;
		}

		$current_user = wp_get_current_user();
		$is_agency = in_array('malisafi_agency', $current_user->roles);
		if (!$is_agency && !current_user_can('administrator')) {
			return '<div class="malisafi-access-denied"><p>' . __('Access restricted to agencies only.', 'malisafi-mls') . '</p></div>';
		}

		// Enqueue dashboard styles
		wp_enqueue_style('malisafi-dashboards', MALISAFI_MLS_URL . 'assets/css/dashboards.css', array(), MALISAFI_MLS_VERSION);

		global $wpdb;
		$table_name = $wpdb->prefix . 'mf_inquiries';
		$user_id = get_current_user_id();

		// Get inquiries for all agents in this agency
		$rows = $wpdb->get_results($wpdb->prepare(
			"SELECT i.*, u.display_name as agent_name, u.user_email as agent_email
			 FROM {$table_name} i
			 LEFT JOIN {$wpdb->users} u ON i.agent_id = u.ID
			 WHERE i.agency_id = (
				 SELECT id FROM {$wpdb->prefix}mf_agencies WHERE user_id = %d LIMIT 1
			 )
			 ORDER BY i.inquiry_id DESC LIMIT 50",
			$user_id
		), ARRAY_A);

		ob_start();
		?>
		<div class="malisafi-agency-inquiries">
			<h1><?php _e('Agent Inquiries', 'malisafi-mls'); ?></h1>
			<p><?php _e('Inquiries received by your agents.', 'malisafi-mls'); ?></p>

			<?php if (empty($rows)) : ?>
				<p><?php _e('No inquiries found.', 'malisafi-mls'); ?></p>
			<?php else : ?>
				<ul class="agency-inquiries-list">
					<?php foreach ($rows as $row) : ?>
						<?php $property_id = isset($row['property_id']) ? (int) $row['property_id'] : 0; ?>
						<li class="inquiry-item">
							<div class="inquiry-header">
								<?php if ($property_id) : ?>
									<a href="<?php echo esc_url(get_permalink($property_id)); ?>" class="property-link">
										<?php echo esc_html(get_the_title($property_id)); ?>
									</a>
								<?php else : ?>
									<span><?php _e('Property Inquiry', 'malisafi-mls'); ?></span>
								<?php endif; ?>
								<span class="inquiry-status status-<?php echo esc_attr($row['status']); ?>">
									<?php echo esc_html(ucfirst($row['status'])); ?>
								</span>
							</div>

							<div class="inquiry-agent">
								<small><?php _e('Agent:', 'malisafi-mls'); ?> <?php echo esc_html($row['agent_name'] ?? 'Unknown'); ?>
								(<?php echo esc_html($row['agent_email'] ?? 'N/A'); ?>)</small>
							</div>

							<div class="inquiry-meta">
								<?php if (!empty($row['created_at'])) : ?>
									<small class="inquiry-date"><?php echo esc_html(date('M j, Y g:i A', strtotime($row['created_at']))); ?></small>
								<?php endif; ?>
								<?php if (!empty($row['inquiry_type'])) : ?>
									<small class="inquiry-type"><?php echo esc_html(ucfirst(str_replace('_', ' ', $row['inquiry_type']))); ?></small>
								<?php endif; ?>
							</div>

							<?php if (!empty($row['message'])) : ?>
								<div class="inquiry-message">
									<?php echo esc_html(wp_trim_words($row['message'], 20)); ?>
								</div>
							<?php endif; ?>

							<?php if (!empty($row['client_email'])) : ?>
								<div class="inquiry-contact">
									<small><?php _e('From:', 'malisafi-mls'); ?> <?php echo esc_html($row['client_email']); ?>
									<?php if (!empty($row['client_phone'])) : ?>
										| <?php echo esc_html($row['client_phone']); ?>
									<?php endif; ?>
									</small>
								</div>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function agency_agents($atts) {
		$login_check = self::require_login();
		if ($login_check) {
			return $login_check;
		}

		$current_user = wp_get_current_user();
		$is_agency = in_array('malisafi_agency', $current_user->roles);
		if (!$is_agency && !current_user_can('administrator')) {
			return '<div class="malisafi-access-denied"><p>' . __('Access restricted to agencies only.', 'malisafi-mls') . '</p></div>';
		}

		$user_id = get_current_user_id();
		$agents = \MalisafiMLS\Agency_Manager::get_agency_agents($user_id);

		ob_start();
		?>
		<div class="malisafi-agency-agents">
			<h1><?php _e('My Agents', 'malisafi-mls'); ?></h1>

			<?php if (empty($agents)) : ?>
				<p><?php _e('No agents found.', 'malisafi-mls'); ?></p>
			<?php else : ?>
				<div class="agents-grid">
					<?php foreach ($agents as $agent) : ?>
						<div class="agent-card">
							<h3><?php echo esc_html($agent->display_name); ?></h3>
							<p><?php echo esc_html($agent->user_email); ?></p>
							<div class="agent-stats">
								<?php
								$agent_properties = count(get_posts(array(
									'post_type' => 'malisafi_property',
									'author' => $agent->ID,
									'posts_per_page' => -1
								)));
								$agent_inquiries = get_user_meta($agent->ID, '_malisafi_inquiries', true);
								$agent_inquiries = $agent_inquiries ? count(maybe_unserialize($agent_inquiries)) : 0;
								?>
								<span><?php echo esc_html($agent_properties); ?> properties</span>
								<span><?php echo esc_html($agent_inquiries); ?> inquiries</span>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function developer_dashboard($atts) {
		$login_check = self::require_login();
		if ($login_check) {
			return $login_check;
		}

		$user_id = get_current_user_id();
		$logout_url = wp_logout_url(home_url());
		$projects_url = Page_Manager::get_page_url('developer_projects');
		$add_project_url = Page_Manager::get_page_url('developer_add_project');
		$analytics_url = Page_Manager::get_page_url('developer_analytics');
		$stats = self::get_developer_project_stats($user_id);

		wp_enqueue_style(
			'malisafi-developer-dashboard',
			MALISAFI_MLS_URL . 'assets/css/developer-dashboard.css',
			array(),
			MALISAFI_MLS_VERSION
		);

		$recent_args = array(
			'post_type' => 'malisafi_project',
			'posts_per_page' => 5,
			'post_status' => array('publish', 'pending', 'draft'),
		);

		if (!current_user_can('edit_others_projects')) {
			$recent_args['author'] = $user_id;
		}

		$recent_projects = new \WP_Query($recent_args);

		ob_start();
		?>
		<div class="malisafi-developer-dashboard">
			<div class="developer-header">
				<div class="dashboard-header">
					<h1><?php _e('Developer Dashboard', 'malisafi-mls'); ?></h1>
					<a class="button button-secondary" href="<?php echo esc_url($logout_url); ?>">
						<?php _e('Logout', 'malisafi-mls'); ?>
					</a>
				</div>
				<p><?php _e('Track your projects, milestones, and linked property performance.', 'malisafi-mls'); ?></p>
			</div>

			<div class="dashboard-cards">
				<div class="dashboard-card">
					<h3><?php _e('Projects', 'malisafi-mls'); ?></h3>
					<p class="count"><?php echo esc_html($stats['total_projects']); ?></p>
					<?php if ($projects_url): ?>
						<a href="<?php echo esc_url($projects_url); ?>" class="button button-secondary"><?php _e('View Projects', 'malisafi-mls'); ?></a>
					<?php endif; ?>
				</div>
				<div class="dashboard-card">
					<h3><?php _e('Add Project', 'malisafi-mls'); ?></h3>
					<p><?php _e('Create a new development profile.', 'malisafi-mls'); ?></p>
					<?php if ($add_project_url): ?>
						<a href="<?php echo esc_url($add_project_url); ?>" class="button button-primary"><?php _e('Create Project', 'malisafi-mls'); ?></a>
					<?php endif; ?>
				</div>
				<div class="dashboard-card">
					<h3><?php _e('Linked Units', 'malisafi-mls'); ?></h3>
					<p class="count"><?php echo esc_html($stats['total_units']); ?></p>
					<p class="subtext"><?php _e('Total properties linked', 'malisafi-mls'); ?></p>
				</div>
				<div class="dashboard-card">
					<h3><?php _e('Price Range', 'malisafi-mls'); ?></h3>
					<p class="count"><?php echo esc_html($stats['min_price_formatted']); ?> - <?php echo esc_html($stats['max_price_formatted']); ?></p>
					<p class="subtext"><?php _e('Across linked units', 'malisafi-mls'); ?></p>
				</div>
			</div>

			<div class="developer-analytics-grid">
				<div class="analytics-card">
					<h3><?php _e('Average Unit Price', 'malisafi-mls'); ?></h3>
					<p class="analytics-value"><?php echo esc_html($stats['avg_price_formatted']); ?></p>
					<p class="subtext"><?php _e('Based on linked unit prices', 'malisafi-mls'); ?></p>
				</div>
				<div class="analytics-card">
					<h3><?php _e('Active Projects', 'malisafi-mls'); ?></h3>
					<p class="analytics-value"><?php echo esc_html($stats['active_projects']); ?></p>
					<p class="subtext"><?php _e('Published + pending', 'malisafi-mls'); ?></p>
				</div>
			</div>

			<div class="developer-section">
				<h2><?php _e('Recent Projects', 'malisafi-mls'); ?></h2>
				<?php if ($recent_projects->have_posts()) : ?>
					<ul class="developer-list">
						<?php while ($recent_projects->have_posts()) : $recent_projects->the_post(); ?>
							<?php
							$linked = get_post_meta(get_the_ID(), '_malisafi_project_linked_properties', true);
							if (!is_array($linked)) {
								$linked = $linked ? (array) $linked : array();
							}
							?>
							<li>
								<a href="<?php echo esc_url(get_permalink()); ?>"><?php the_title(); ?></a>
								<span class="muted"><?php echo esc_html(ucfirst(get_post_status())); ?></span>
								<span class="muted"><?php echo esc_html(count($linked)); ?> <?php _e('units', 'malisafi-mls'); ?></span>
							</li>
						<?php endwhile; ?>
					</ul>
				<?php else : ?>
					<p class="muted"><?php _e('No projects created yet.', 'malisafi-mls'); ?></p>
				<?php endif; ?>
			</div>

			<ul class="dashboard-links">
				<?php if ($projects_url): ?>
					<li><a href="<?php echo esc_url($projects_url); ?>"><?php _e('My Projects', 'malisafi-mls'); ?></a></li>
				<?php endif; ?>
				<?php if ($analytics_url): ?>
					<li><a href="<?php echo esc_url($analytics_url); ?>"><?php _e('Analytics', 'malisafi-mls'); ?></a></li>
				<?php endif; ?>
			</ul>
		</div>
		<?php
		wp_reset_postdata();
		return ob_get_clean();
	}

	public static function developer_projects($atts) {
		$login_check = self::require_login();
		if ($login_check) {
			return $login_check;
		}

		wp_enqueue_style(
			'malisafi-developer-dashboard',
			MALISAFI_MLS_URL . 'assets/css/developer-dashboard.css',
			array(),
			MALISAFI_MLS_VERSION
		);

		if (!current_user_can('edit_projects')) {
			return '<div class="malisafi-access-denied"><p>' . __('You do not have permission to view projects.', 'malisafi-mls') . '</p></div>';
		}

		$args = array(
			'post_type' => 'malisafi_project',
			'posts_per_page' => 20,
			'post_status' => array('publish', 'pending', 'draft'),
		);

		if (!current_user_can('edit_others_projects')) {
			$args['author'] = get_current_user_id();
		}

		$query = new \WP_Query($args);
		$add_project_url = Page_Manager::get_page_url('developer_add_project');

		ob_start();
		?>
		<div class="malisafi-developer-projects">
			<h1><?php _e('My Projects', 'malisafi-mls'); ?></h1>
			<?php if ($add_project_url): ?>
				<a href="<?php echo esc_url($add_project_url); ?>" class="button button-primary" style="margin-bottom: 15px;">
					<?php _e('Add Project', 'malisafi-mls'); ?>
				</a>
			<?php endif; ?>

			<?php if ($query->have_posts()) : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php _e('Project', 'malisafi-mls'); ?></th>
							<th><?php _e('Status', 'malisafi-mls'); ?></th>
							<th><?php _e('Linked Units', 'malisafi-mls'); ?></th>
							<th><?php _e('Updated', 'malisafi-mls'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php while ($query->have_posts()) : $query->the_post(); ?>
							<?php
							$linked = get_post_meta(get_the_ID(), '_malisafi_project_linked_properties', true);
							if (!is_array($linked)) {
								$linked = $linked ? (array) $linked : array();
							}
							?>
							<tr>
								<td><a href="<?php echo esc_url(get_permalink()); ?>"><?php the_title(); ?></a></td>
								<td><?php echo esc_html(ucfirst(get_post_status())); ?></td>
								<td><?php echo esc_html(count($linked)); ?></td>
								<td><?php echo esc_html(get_the_modified_date()); ?></td>
							</tr>
						<?php endwhile; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p><?php _e('No projects found yet.', 'malisafi-mls'); ?></p>
			<?php endif; ?>
		</div>
		<?php
		wp_reset_postdata();
		return ob_get_clean();
	}

	public static function developer_analytics($atts) {
		$login_check = self::require_login();
		if ($login_check) {
			return $login_check;
		}

		$user_id = get_current_user_id();
		$stats = self::get_developer_project_stats($user_id);

		wp_enqueue_style(
			'malisafi-developer-dashboard',
			MALISAFI_MLS_URL . 'assets/css/developer-dashboard.css',
			array(),
			MALISAFI_MLS_VERSION
		);

		$args = array(
			'post_type' => 'malisafi_project',
			'posts_per_page' => 20,
			'post_status' => array('publish', 'pending', 'draft'),
		);

		if (!current_user_can('edit_others_projects')) {
			$args['author'] = $user_id;
		}

		$projects = new \WP_Query($args);

		ob_start();
		?>
		<div class="malisafi-developer-analytics">
			<h1><?php _e('Developer Analytics', 'malisafi-mls'); ?></h1>
			<div class="developer-analytics-grid">
				<div class="analytics-card">
					<h3><?php _e('Projects', 'malisafi-mls'); ?></h3>
					<p class="analytics-value"><?php echo esc_html($stats['total_projects']); ?></p>
				</div>
				<div class="analytics-card">
					<h3><?php _e('Linked Units', 'malisafi-mls'); ?></h3>
					<p class="analytics-value"><?php echo esc_html($stats['total_units']); ?></p>
				</div>
				<div class="analytics-card">
					<h3><?php _e('Average Unit Price', 'malisafi-mls'); ?></h3>
					<p class="analytics-value"><?php echo esc_html($stats['avg_price_formatted']); ?></p>
				</div>
				<div class="analytics-card">
					<h3><?php _e('Price Range', 'malisafi-mls'); ?></h3>
					<p class="analytics-value"><?php echo esc_html($stats['min_price_formatted']); ?> - <?php echo esc_html($stats['max_price_formatted']); ?></p>
				</div>
			</div>

			<div class="developer-section">
				<h2><?php _e('Project Overview', 'malisafi-mls'); ?></h2>
				<?php if ($projects->have_posts()) : ?>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php _e('Project', 'malisafi-mls'); ?></th>
								<th><?php _e('Status', 'malisafi-mls'); ?></th>
								<th><?php _e('Linked Units', 'malisafi-mls'); ?></th>
								<th><?php _e('Price Range', 'malisafi-mls'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php while ($projects->have_posts()) : $projects->the_post(); ?>
								<?php
								$linked = get_post_meta(get_the_ID(), '_malisafi_project_linked_properties', true);
								if (!is_array($linked)) {
									$linked = $linked ? (array) $linked : array();
								}
								$min_price = get_post_meta(get_the_ID(), '_malisafi_project_min_price', true);
								$max_price = get_post_meta(get_the_ID(), '_malisafi_project_max_price', true);
								$range = '—';
								if ($min_price !== '' || $max_price !== '') {
									$range = Property_Manager::format_price($min_price) . ' - ' . Property_Manager::format_price($max_price);
								}
								?>
								<tr>
									<td><a href="<?php echo esc_url(get_permalink()); ?>"><?php the_title(); ?></a></td>
									<td><?php echo esc_html(ucfirst(get_post_status())); ?></td>
									<td><?php echo esc_html(count($linked)); ?></td>
									<td><?php echo esc_html($range); ?></td>
								</tr>
							<?php endwhile; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p class="muted"><?php _e('No analytics data yet.', 'malisafi-mls'); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
		wp_reset_postdata();
		return ob_get_clean();
	}
    
	/**
	 * Login Form
	 */
	public static function login_form($atts) {
		if (is_user_logged_in()) {
			return '<p>' . __('You are already logged in.', 'malisafi-mls') . ' <a href="' . wp_logout_url() . '">' . __('Logout', 'malisafi-mls') . '</a></p>';
		}
        
		// Get register page URL
		$register_url = Page_Manager::get_page_url('register');
		if (!$register_url) {
			$register_url = wp_registration_url();
		}
        
		// Check for email verification messages
		$verification_sent = isset($_GET['verification_sent']) && $_GET['verification_sent'] === '1';
		$email_verified = isset($_GET['email_verified']) && $_GET['email_verified'] === '1';
        
		ob_start();
		?>
		<div class="malisafi-login-container">
			<div class="malisafi-login-box">
				<div class="malisafi-login-header">
					<h2><?php _e('Welcome to Malisafi', 'malisafi-mls'); ?></h2>
					<p><?php _e('Login to access your dashboard', 'malisafi-mls'); ?></p>
				</div>
                
				<?php if ($verification_sent): ?>
					<div class="malisafi-notice malisafi-notice-info">
						<p><?php _e('Registration successful! Please check your email and click the verification link to activate your account.', 'malisafi-mls'); ?></p>
					</div>
				<?php elseif ($email_verified): ?>
					<div class="malisafi-notice malisafi-notice-success">
						<p><?php _e('Email verified successfully! You can now log in to your account.', 'malisafi-mls'); ?></p>
					</div>
				<?php endif; ?>
                
				<div id="malisafi-login-messages"></div>
                
				<form id="malisafi-loginform" name="loginform" method="post">
					<p>
						<label for="user_login"><?php _e('Username or Email', 'malisafi-mls'); ?></label>
						<input type="text" name="log" id="user_login" class="input" value="" size="20" autocomplete="username" required />
					</p>
                    
					<p>
						<label for="user_pass"><?php _e('Password', 'malisafi-mls'); ?></label>
						<input type="password" name="pwd" id="user_pass" class="input" value="" size="20" autocomplete="current-password" required />
					</p>
                    
					<p class="login-remember">
						<label>
							<input name="rememberme" type="checkbox" id="rememberme" value="forever" />
							<?php _e('Remember Me', 'malisafi-mls'); ?>
						</label>
					</p>
                    
					<p class="login-submit">
						<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary" value="<?php esc_attr_e('Log In', 'malisafi-mls'); ?>" />
					</p>
				</form>
                
				<div class="malisafi-login-links">
					<p class="register-link">
						<?php _e("Don't have an account?", 'malisafi-mls'); ?> 
						<a href="<?php echo esc_url($register_url); ?>"><?php _e('Register', 'malisafi-mls'); ?></a>
					</p>
					<p class="lost-password-link">
						<a href="<?php echo wp_lostpassword_url(); ?>"><?php _e('Forgot Password?', 'malisafi-mls'); ?></a>
					</p>
				</div>
			</div>
		</div>
        
		<style>
		.malisafi-login-container {
			max-width: 450px;
			margin: 40px auto;
			padding: 20px;
		}
        
		.malisafi-login-box {
			background: #ffffff;
			border-radius: 16px;
			padding: 40px;
			box-shadow: 0 8px 24px rgba(0,0,0,0.12);
			border: 1px solid #e0e0e0;
		}
        
		.malisafi-login-header {
			text-align: center;
			margin-bottom: 30px;
		}
        
		.malisafi-login-header h2 {
			color: #1a1a1a;
			font-size: 28px;
			font-weight: 700;
			margin: 0 0 10px;
		}
        
		.malisafi-login-header p {
			color: #4a4a4a;
			font-size: 14px;
			margin: 0;
		}
        
		#malisafi-loginform label {
			display: block;
			color: #1a1a1a;
			font-weight: 600;
			font-size: 14px;
			margin-bottom: 8px;
		}
        
		#malisafi-loginform input[type="text"],
		#malisafi-loginform input[type="password"] {
			width: 100%;
			padding: 14px 16px;
			border: 2px solid #e0e0e0;
			border-radius: 8px;
			font-size: 16px;
			transition: all 0.3s ease;
			background: #f5f5f5;
			box-sizing: border-box;
		}
        
		#malisafi-loginform input[type="text"]:focus,
		#malisafi-loginform input[type="password"]:focus {
			border-color: #1a1a1a;
			background: #ffffff;
			outline: none;
			box-shadow: 0 0 0 4px rgba(26, 26, 26, 0.1);
		}
        
		#malisafi-loginform .login-remember {
			margin: 15px 0;
		}
        
		#malisafi-loginform .login-remember label {
			display: inline;
			font-weight: 500;
			color: #4a4a4a;
		}
        
		#malisafi-loginform .login-submit {
			margin-top: 20px;
		}
        
		#malisafi-loginform .login-submit input[type="submit"] {
			width: 100%;
			padding: 14px 32px;
			background: #1a1a1a;
			border: none;
			border-radius: 8px;
			color: #ffffff;
			font-size: 16px;
			font-weight: 600;
			letter-spacing: 0.5px;
			text-transform: uppercase;
			cursor: pointer;
			transition: all 0.3s ease;
			box-shadow: 0 4px 12px rgba(26, 26, 26, 0.3);
		}
        
		#malisafi-loginform .login-submit input[type="submit"]:hover {
			background: #000000;
			transform: translateY(-2px);
			box-shadow: 0 6px 20px rgba(26, 26, 26, 0.4);
		}
        
		.malisafi-login-links {
			margin-top: 25px;
			padding-top: 25px;
			border-top: 1px solid #e0e0e0;
			text-align: center;
		}
        
		.malisafi-login-links p {
			margin: 10px 0;
			color: #4a4a4a;
			font-size: 14px;
		}
        
		.malisafi-login-links a {
			color: #1a1a1a;
			font-weight: 600;
			text-decoration: none;
			transition: color 0.3s ease;
		}
        
		.malisafi-login-links a:hover {
			color: #4a4a4a;
		}
        
		#malisafi-login-messages {
			margin-bottom: 20px;
			border-radius: 8px;
			display: none;
		}
        
		#malisafi-login-messages.show {
			display: block;
		}
        
		#malisafi-login-messages.error {
			background: #fee;
			border: 1px solid #c33;
			color: #c33;
			padding: 12px 16px;
		}
        
		#malisafi-login-messages.success {
			background: #efe;
			border: 1px solid #3c3;
			color: #3c3;
			padding: 12px 16px;
		}
        
		#malisafi-loginform.loading {
			opacity: 0.6;
			pointer-events: none;
		}
        
		#malisafi-loginform.loading #wp-submit::after {
			content: "";
			display: inline-block;
			width: 16px;
			height: 16px;
			margin-left: 10px;
			border: 2px solid #ffffff;
			border-top-color: transparent;
			border-radius: 50%;
			animation: spin 0.6s linear infinite;
		}
        
		@keyframes spin {
			to { transform: rotate(360deg); }
		}
        
		@media (max-width: 768px) {
			.malisafi-login-box {
				padding: 30px 20px;
			}
            
			.malisafi-login-container {
				padding: 10px;
			}
		}
		</style>
        
		<script>
		jQuery(document).ready(function($) {
			$('#malisafi-loginform').on('submit', function(e) {
				e.preventDefault();
                
				var $form = $(this);
				var $messages = $('#malisafi-login-messages');
				var $submit = $('#wp-submit');
                
				// Add loading state
				$form.addClass('loading');
				$submit.prop('disabled', true);
                
				// Hide previous messages
				$messages.removeClass('show error success').hide();
                
				$.ajax({
					url: '<?php echo admin_url('admin-ajax.php'); ?>',
					type: 'POST',
					data: {
						action: 'malisafi_custom_login',
						username: $('#user_login').val(),
						password: $('#user_pass').val(),
						remember: $('#rememberme').is(':checked'),
						nonce: '<?php echo wp_create_nonce('malisafi_login_nonce'); ?>'
					},
					success: function(response) {
						$form.removeClass('loading');
						$submit.prop('disabled', false);
                        
						if (response.success) {
							$messages
								.addClass('show success')
								.html('<strong><?php _e('Success!', 'malisafi-mls'); ?></strong> ' + response.data.message)
								.fadeIn();
                            
							// Redirect to dashboard
							setTimeout(function() {
								window.location.href = response.data.redirect;
							}, 1000);
						} else {
							$messages
								.addClass('show error')
								.html('<strong><?php _e('Error:', 'malisafi-mls'); ?></strong> ' + response.data.message)
								.fadeIn();
                            
							// Clear password field
							$('#user_pass').val('').focus();
						}
					},
					error: function() {
						$form.removeClass('loading');
						$submit.prop('disabled', false);
						$messages
							.addClass('show error')
							.html('<strong><?php _e('Error:', 'malisafi-mls'); ?></strong> <?php _e('Connection error. Please try again.', 'malisafi-mls'); ?>')
							.fadeIn();
					}
				});
			});
		});
		</script>
		<?php
		return ob_get_clean();
	}
    
	/**
	 * Register Form
	 */
	public static function register_form($atts) {
		if (is_user_logged_in()) {
			return '<p>' . __('You are already logged in.', 'malisafi-mls') . '</p>';
		}
        
		ob_start();
		?>
		<div class="malisafi-register-form">
			<h2><?php _e('Register', 'malisafi-mls'); ?></h2>
			<form method="post" action="" id="malisafi-register-form">
				<?php wp_nonce_field('malisafi_register', 'malisafi_register_nonce'); ?>
                
				<p>
					<label for="username"><?php _e('Username', 'malisafi-mls'); ?> *</label>
					<input type="text" name="username" id="username" required>
				</p>
                
				<p>
					<label for="email"><?php _e('Email', 'malisafi-mls'); ?> *</label>
					<input type="email" name="email" id="email" required>
				</p>
                
				<p>
					<label for="password"><?php _e('Password', 'malisafi-mls'); ?> *</label>
					<input type="password" name="password" id="password" required>
				</p>
                
				<p>
					<label for="password_confirm"><?php _e('Confirm Password', 'malisafi-mls'); ?> *</label>
					<input type="password" name="password_confirm" id="password_confirm" required>
				</p>
                
				<p>
					<button type="submit" name="malisafi_register" class="button button-primary">
						<?php _e('Register', 'malisafi-mls'); ?>
					</button>
				</p>
			</form>
            
			<p class="login-link">
				<a href="<?php echo Page_Manager::get_page_url('login'); ?>">
					<?php _e('Already have an account? Login', 'malisafi-mls'); ?>
				</a>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}
    
	/**
	 * Account Page
	 */
	public static function account_page($atts) {
		$login_check = self::require_login();
		if ($login_check) return $login_check;
        
		$current_user = wp_get_current_user();
        
		ob_start();
		?>
		<div class="malisafi-account">
			<h1><?php _e('My Account', 'malisafi-mls'); ?></h1>
            
			<div class="account-info">
				<h2><?php _e('Account Information', 'malisafi-mls'); ?></h2>
				<p><strong><?php _e('Username:', 'malisafi-mls'); ?></strong> <?php echo $current_user->user_login; ?></p>
				<p><strong><?php _e('Email:', 'malisafi-mls'); ?></strong> <?php echo $current_user->user_email; ?></p>
				<p><strong><?php _e('Role:', 'malisafi-mls'); ?></strong> <?php echo implode(', ', $current_user->roles); ?></p>
			</div>
            
			<div class="account-actions">
				<h2><?php _e('Quick Links', 'malisafi-mls'); ?></h2>
				<ul>
					<?php if (current_user_can('malisafi_agency')): ?>
						<li><a href="<?php echo Page_Manager::get_page_url('agency_dashboard'); ?>"><?php _e('Agency Dashboard', 'malisafi-mls'); ?></a></li>
					<?php elseif (current_user_can('malisafi_agent_basic') || current_user_can('malisafi_agent_premium')): ?>
						<li><a href="<?php echo Page_Manager::get_page_url('agent_dashboard'); ?>"><?php _e('Agent Dashboard', 'malisafi-mls'); ?></a></li>
					<?php elseif (current_user_can('malisafi_owner')): ?>
						<li><a href="<?php echo Page_Manager::get_page_url('owner_dashboard'); ?>"><?php _e('Owner Dashboard', 'malisafi-mls'); ?></a></li>
					<?php elseif (current_user_can('malisafi_developer')): ?>
						<li><a href="<?php echo Page_Manager::get_page_url('developer_dashboard'); ?>"><?php _e('Developer Dashboard', 'malisafi-mls'); ?></a></li>
					<?php else: ?>
						<li><a href="<?php echo Page_Manager::get_page_url('client_dashboard'); ?>"><?php _e('Client Dashboard', 'malisafi-mls'); ?></a></li>
					<?php endif; ?>
					<li><a href="<?php echo wp_logout_url(home_url()); ?>"><?php _e('Logout', 'malisafi-mls'); ?></a></li>
				</ul>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
    
	// Helper methods
    
	private static function get_favorites_count($user_id) {
		$favorites = get_user_meta($user_id, 'malisafi_favorites', true) ?: [];
		return count($favorites);
	}
    
	private static function get_saved_searches_count($user_id) {
		$searches = get_user_meta($user_id, 'malisafi_saved_searches', true) ?: [];
		return count($searches);
	}
    
	private static function get_inquiries_count($user_id) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mf_inquiries';
		return $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE client_id = %d",
			$user_id
		)) ?: 0;
	}
    
	private static function get_user_inquiries_count($user_id) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mf_inquiries';
		return $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name i
			LEFT JOIN {$wpdb->posts} p ON i.property_id = p.ID
			WHERE p.post_author = %d",
			$user_id
		)) ?: 0;
	}
    
	private static function get_user_properties_count($user_id) {
		global $wpdb;
		$statuses = array('publish','pending','draft');
		$placeholders = implode(',', array_fill(0, count($statuses), '%s'));
		$sql = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_author = %d AND post_status IN ($placeholders)",
			array_merge(array('malisafi_property', $user_id), $statuses)
		);
		$count = (int) $wpdb->get_var($sql);
		return $count;
	}

	private static function get_user_projects_count($user_id) {
		global $wpdb;
		$statuses = array('publish','pending','draft');
		$placeholders = implode(',', array_fill(0, count($statuses), '%s'));
		$sql = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_author = %d AND post_status IN ($placeholders)",
			array_merge(array('malisafi_project', $user_id), $statuses)
		);
		$count = (int) $wpdb->get_var($sql);
		return $count;
	}

	private static function get_agency_inquiries_count($agency_user_id) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mf_inquiries';

		$count = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name} WHERE agency_id = (
				SELECT id FROM {$wpdb->prefix}mf_agencies WHERE user_id = %d LIMIT 1
			)",
			$agency_user_id
		));

		return (int) $count;
	}

	private static function get_developer_project_stats($user_id) {
		$args = array(
			'post_type' => 'malisafi_project',
			'posts_per_page' => -1,
			'post_status' => array('publish', 'pending', 'draft'),
			'fields' => 'ids'
		);

		if (!current_user_can('edit_others_projects')) {
			$args['author'] = $user_id;
		}

		$project_ids = get_posts($args);
		$total_projects = count($project_ids);
		$active_projects = 0;
		$total_units = 0;
		$min_prices = array();
		$max_prices = array();
		$price_values = array();

		foreach ($project_ids as $project_id) {
			$status = get_post_status($project_id);
			if ($status === 'publish' || $status === 'pending') {
				$active_projects++;
			}

			$linked = get_post_meta($project_id, '_malisafi_project_linked_properties', true);
			if (!is_array($linked)) {
				$linked = $linked ? (array) $linked : array();
			}
			$total_units += count($linked);

			$min_price = get_post_meta($project_id, '_malisafi_project_min_price', true);
			$max_price = get_post_meta($project_id, '_malisafi_project_max_price', true);
			if ($min_price !== '') {
				$min_prices[] = (float) $min_price;
			}
			if ($max_price !== '') {
				$max_prices[] = (float) $max_price;
			}

			$snapshot = get_post_meta($project_id, '_malisafi_project_properties_snapshot', true);
			if (is_array($snapshot)) {
				foreach ($snapshot as $item) {
					if (isset($item['price']) && $item['price'] !== '') {
						$price_values[] = (float) $item['price'];
					}
				}
			}
		}

		$min_price_value = !empty($min_prices) ? min($min_prices) : null;
		$max_price_value = !empty($max_prices) ? max($max_prices) : null;
		$avg_price_value = !empty($price_values) ? array_sum($price_values) / count($price_values) : null;

		return array(
			'total_projects' => $total_projects,
			'active_projects' => $active_projects,
			'total_units' => $total_units,
			'min_price' => $min_price_value,
			'max_price' => $max_price_value,
			'avg_price' => $avg_price_value,
			'min_price_formatted' => $min_price_value !== null ? Property_Manager::format_price($min_price_value) : '—',
			'max_price_formatted' => $max_price_value !== null ? Property_Manager::format_price($max_price_value) : '—',
			'avg_price_formatted' => $avg_price_value !== null ? Property_Manager::format_price($avg_price_value) : '—'
		);
	}
    
	private static function get_total_views($user_id) {
		global $wpdb;
		$views_table = $wpdb->prefix . 'malisafi_property_views';
        
		$total = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM $views_table v
			LEFT JOIN {$wpdb->posts} p ON v.property_id = p.ID
			WHERE p.post_author = %d",
			$user_id
		));
        
		return $total ?: 0;
	}
    
	private static function get_recent_viewed_properties($user_id) {
		$viewed = get_user_meta($user_id, 'malisafi_recently_viewed', true) ?: [];
        
		if (empty($viewed)) {
			return '<p>' . __('No recent activity.', 'malisafi-mls') . '</p>';
		}
        
		$args = [
			'post_type' => 'malisafi_property',
			'post__in' => array_slice($viewed, 0, 5),
			'posts_per_page' => 5,
			'orderby' => 'post__in'
		];
        
		$query = new \WP_Query($args);
        
		if (!$query->have_posts()) {
			return '<p>' . __('No recent activity.', 'malisafi-mls') . '</p>';
		}
        
		$output = '<ul class="recent-properties">';
		while ($query->have_posts()) {
			$query->the_post();
			$output .= '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
		}
		$output .= '</ul>';
        
		wp_reset_postdata();
        
		return $output;
	}
    
	private static function render_property_card($property_id) {
		$price = get_post_meta($property_id, 'price', true);
		$bedrooms = get_post_meta($property_id, 'bedrooms', true);
		$bathrooms = get_post_meta($property_id, 'bathrooms', true);
        
		ob_start();
		?>
		<div class="property-card">
			<?php if (has_post_thumbnail($property_id)): ?>
				<div class="property-image">
					<a href="<?php echo get_permalink($property_id); ?>">
						<?php echo get_the_post_thumbnail($property_id, 'medium'); ?>
					</a>
				</div>
			<?php endif; ?>
            
			<div class="property-info">
				<h3><a href="<?php echo get_permalink($property_id); ?>"><?php echo get_the_title($property_id); ?></a></h3>
				<p class="price"><?php echo Property_Manager::format_price($price); ?></p>
				<div class="property-meta">
					<?php if ($bedrooms): ?>
						<span><?php echo $bedrooms; ?> <?php _e('beds', 'malisafi-mls'); ?></span>
					<?php endif; ?>
					<?php if ($bathrooms): ?>
						<span><?php echo $bathrooms; ?> <?php _e('baths', 'malisafi-mls'); ?></span>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
    
	private static function format_search_criteria($search) {
		$criteria = [];
        
		if (!empty($search['min_price'])) {
			$criteria[] = __('Min Price:', 'malisafi-mls') . ' ' . Property_Manager::format_price($search['min_price']);
		}
        
		if (!empty($search['max_price'])) {
			$criteria[] = __('Max Price:', 'malisafi-mls') . ' ' . Property_Manager::format_price($search['max_price']);
		}
        
		if (!empty($search['bedrooms'])) {
			$criteria[] = $search['bedrooms'] . ' ' . __('bedrooms', 'malisafi-mls');
		}
        
		return implode(' | ', $criteria);
	}
    
	private static function build_search_url($search) {
		$url = Page_Manager::get_page_url('property_search');
		$params = [];
        
		if (!empty($search['min_price'])) $params['min_price'] = $search['min_price'];
		if (!empty($search['max_price'])) $params['max_price'] = $search['max_price'];
		if (!empty($search['bedrooms'])) $params['bedrooms'] = $search['bedrooms'];
        
		return add_query_arg($params, $url);
	}
    
	/**
	 * Public Agent Profile View
	 */
	public static function agent_profile_public($atts) {
		$atts = shortcode_atts(array(
			'agent_id' => isset($_GET['agent_id']) ? intval($_GET['agent_id']) : 0
		), $atts);
    
		if (empty($atts['agent_id'])) {
			return '<div class="malisafi-error">' . __('Agent ID is required.', 'malisafi-mls') . '</div>';
		}

		// Enqueue styles
		wp_enqueue_style(
			'agent-profile-public',
			MALISAFI_MLS_URL . 'assets/css/agent-profile-public.css',
			array(),
			MALISAFI_MLS_VERSION
		);
    
		ob_start();
		include MALISAFI_MLS_PATH . 'templates/agent-profile-public.php';
		return ob_get_clean();
	}
    
	/**
	 * AJAX handler for custom login
	 */
	public static function ajax_custom_login() {
		// Verify nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'malisafi_login_nonce')) {
			wp_send_json_error([
				'message' => __('Security check failed. Please refresh the page and try again.', 'malisafi-mls')
			]);
		}
        
		// Get credentials
		$username = sanitize_text_field($_POST['username']);
		$password = $_POST['password'];
		$remember = isset($_POST['remember']) && $_POST['remember'] === 'true';
        
		// Validate inputs
		if (empty($username) || empty($password)) {
			wp_send_json_error([
				'message' => __('Please enter both username and password.', 'malisafi-mls')
			]);
		}
        
		// Attempt authentication
		$user = wp_authenticate($username, $password);
        
		// Check for errors
		if (is_wp_error($user)) {
			$error_code = $user->get_error_code();
            
			// Customize error messages
			switch ($error_code) {
				case 'invalid_username':
					$message = __('The username you entered does not exist. Please check and try again.', 'malisafi-mls');
					break;
				case 'incorrect_password':
				case 'invalid_email':
					$message = __('Incorrect password. Please try again.', 'malisafi-mls');
					break;
				default:
					$message = __('Login failed. Please check your credentials and try again.', 'malisafi-mls');
			}
            
			wp_send_json_error(['message' => $message]);
		}
        
		// Check email verification if enabled
		if (get_option('malisafi_email_verification_enabled') && !\MalisafiMLS\Email_Settings::is_email_verified($user->ID)) {
			wp_send_json_error([
				'message' => __('Please verify your email address before logging in. Check your email for the verification link.', 'malisafi-mls')
			]);
		}
        
		// Log the user in
		wp_clear_auth_cookie();
		wp_set_current_user($user->ID);
		wp_set_auth_cookie($user->ID, $remember);
        
		// Determine redirect URL based on user role
		$redirect_url = home_url();
        
		if (in_array('administrator', $user->roles) || in_array('malisafi_moderator', $user->roles)) {
			$redirect_url = admin_url();
		} elseif (in_array('malisafi_agent_basic', $user->roles) || in_array('malisafi_agent_premium', $user->roles)) {
			$redirect_url = Page_Manager::get_page_url('agent_dashboard') ?: home_url();
		} elseif (in_array('malisafi_owner', $user->roles)) {
			$redirect_url = Page_Manager::get_page_url('owner_dashboard') ?: home_url();
		} elseif (in_array('malisafi_developer', $user->roles)) {
			$redirect_url = Page_Manager::get_page_url('developer_dashboard') ?: home_url();
		} elseif (in_array('malisafi_client', $user->roles)) {
			$redirect_url = Page_Manager::get_page_url('client_dashboard') ?: home_url();
		}
        
		wp_send_json_success([
			'message' => sprintf(__('Welcome back, %s!', 'malisafi-mls'), $user->display_name),
			'redirect' => $redirect_url
		]);
	}
}