# Malisafi MLS - Advanced Analytics System Design

## 📊 Vue d'ensemble du système

Système d'analytics complet pour Malisafi MLS avec 3 niveaux:
1. **Core Usage Analytics** - Utilisation plateforme et comportement utilisateurs
2. **Property Performance Analytics** - Performance listings et engagement  
3. **Advanced Admin Analytics** - Détection fraude, revenus, santé système

---

## 🗄️ Architecture Base de Données

### Tables Existantes (À améliorer)

#### `wp_mf_analytics` (Existante - Simple)
```sql
- id
- property_id  
- user_id
- action (view, inquiry, favorite, share)
- ip_address
- user_agent
- session_id
- created_at
```

### Nouvelles Tables Requises

#### 1. `wp_mf_user_activity` - Activité Utilisateur Détaillée
```sql
CREATE TABLE wp_mf_user_activity (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    activity_type ENUM(
        'login', 'logout', 'dashboard_visit', 
        'property_add_start', 'property_add_complete', 'property_edit',
        'property_delete', 'profile_edit', 'search', 'filter_use'
    ) NOT NULL,
    activity_data JSON,              -- Données contextuelles
    page_url VARCHAR(500),
    referrer VARCHAR(500),
    time_spent INT UNSIGNED,         -- Secondes
    session_id VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    device_type ENUM('mobile', 'tablet', 'desktop') DEFAULT 'desktop',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_user (user_id),
    KEY idx_activity (activity_type),
    KEY idx_session (session_id),
    KEY idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 2. `wp_mf_property_views` - Vues Propriétés Détaillées
```sql
CREATE TABLE wp_mf_property_views (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id BIGINT UNSIGNED NOT NULL,
    post_id BIGINT UNSIGNED NOT NULL,     -- CPT ID
    user_id BIGINT UNSIGNED,               -- NULL si anonyme
    session_id VARCHAR(255) NOT NULL,
    view_type ENUM('list', 'grid', 'single', 'featured', 'search_result') NOT NULL,
    view_duration INT UNSIGNED,            -- Temps passé (secondes)
    scroll_depth TINYINT UNSIGNED,         -- Scroll % (0-100)
    gallery_viewed BOOLEAN DEFAULT FALSE,
    map_viewed BOOLEAN DEFAULT FALSE,
    contact_clicked BOOLEAN DEFAULT FALSE,
    source VARCHAR(100),                   -- google, direct, facebook, etc.
    referrer VARCHAR(500),
    device_type ENUM('mobile', 'tablet', 'desktop') DEFAULT 'desktop',
    ip_address VARCHAR(45),
    geo_location JSON,                     -- {country, city, region}
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_property (property_id),
    KEY idx_post (post_id),
    KEY idx_user (user_id),
    KEY idx_session (session_id),
    KEY idx_date (created_at),
    KEY idx_source (source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 3. `wp_mf_property_interactions` - Interactions Propriétés
```sql
CREATE TABLE wp_mf_property_interactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED,
    interaction_type ENUM(
        'favorite', 'unfavorite', 'share_email', 'share_social',
        'inquiry', 'phone_click', 'email_click', 'whatsapp_click',
        'virtual_tour', 'download_brochure', 'schedule_visit'
    ) NOT NULL,
    interaction_data JSON,                 -- Métadonnées supplémentaires
    session_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_property (property_id),
    KEY idx_user (user_id),
    KEY idx_type (interaction_type),
    KEY idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 4. `wp_mf_search_analytics` - Analytics Recherches
```sql
CREATE TABLE wp_mf_search_analytics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    session_id VARCHAR(255) NOT NULL,
    search_type ENUM('keyword', 'filter', 'advanced', 'saved') DEFAULT 'keyword',
    search_query TEXT,                     -- Mot-clé cherché
    filters_used JSON,                     -- {location, price, bedrooms, etc.}
    results_count INT UNSIGNED DEFAULT 0,
    first_result_clicked INT,              -- Position du 1er résultat cliqué
    time_to_click INT UNSIGNED,            -- Temps avant 1er clic (secondes)
    results_viewed INT UNSIGNED DEFAULT 0,
    zero_results BOOLEAN DEFAULT FALSE,
    device_type ENUM('mobile', 'tablet', 'desktop') DEFAULT 'desktop',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_user (user_id),
    KEY idx_session (session_id),
    KEY idx_zero_results (zero_results),
    KEY idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 5. `wp_mf_submission_funnel` - Funnel Soumission Propriété
```sql
CREATE TABLE wp_mf_submission_funnel (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    step_name ENUM(
        'form_loaded', 'basic_info', 'pricing', 'details',
        'location', 'amenities', 'images', 'submit_attempt',
        'submit_success', 'submit_error'
    ) NOT NULL,
    step_data JSON,                        -- Données étape
    time_spent INT UNSIGNED,               -- Temps étape (secondes)
    property_id BIGINT UNSIGNED,           -- NULL jusqu'à submit
    error_message TEXT,                    -- Si erreur
    completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_user (user_id),
    KEY idx_session (session_id),
    KEY idx_step (step_name),
    KEY idx_completed (completed),
    KEY idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 6. `wp_mf_fraud_detection` - Détection Fraude
```sql
CREATE TABLE wp_mf_fraud_detection (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    property_id BIGINT UNSIGNED,
    fraud_type ENUM(
        'duplicate_listing', 'rapid_edits', 'suspicious_ip',
        'fake_images', 'price_manipulation', 'spam_content',
        'multiple_accounts', 'stolen_content'
    ) NOT NULL,
    confidence_score TINYINT UNSIGNED,     -- 0-100
    detection_data JSON,                   -- Détails détection
    status ENUM('pending', 'reviewed', 'confirmed', 'false_positive') DEFAULT 'pending',
    reviewed_by BIGINT UNSIGNED,
    reviewed_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_user (user_id),
    KEY idx_property (property_id),
    KEY idx_type (fraud_type),
    KEY idx_status (status),
    KEY idx_score (confidence_score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 7. `wp_mf_revenue_tracking` - Suivi Revenus
```sql
CREATE TABLE wp_mf_revenue_tracking (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    transaction_type ENUM(
        'subscription', 'featured_listing', 'boost',
        'premium_upgrade', 'additional_listings', 'refund'
    ) NOT NULL,
    plan_type VARCHAR(50),                 -- agent_basic, agent_premium, etc.
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    stripe_payment_id VARCHAR(255),
    stripe_invoice_id VARCHAR(255),
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    KEY idx_user (user_id),
    KEY idx_type (transaction_type),
    KEY idx_status (status),
    KEY idx_amount (amount),
    KEY idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 8. `wp_mf_system_health` - Santé Système
```sql
CREATE TABLE wp_mf_system_health (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    metric_type ENUM(
        'api_response_time', 'image_upload', 'cdn_delivery',
        'database_query', 'page_load', 'error_rate',
        'memory_usage', 'disk_space'
    ) NOT NULL,
    metric_value DECIMAL(10,2),
    metric_unit VARCHAR(20),               -- ms, MB, %
    endpoint VARCHAR(255),                 -- Si API
    status ENUM('ok', 'warning', 'critical') DEFAULT 'ok',
    error_details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_type (metric_type),
    KEY idx_status (status),
    KEY idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🔌 Hooks & Event Tracking

### Core Usage Analytics Hooks

#### Tracking Logins et Sessions
```php
// Hook: wp_login
add_action('wp_login', 'malisafi_track_user_login', 10, 2);
function malisafi_track_user_login($user_login, $user) {
    global $wpdb;
    
    // Vérifier si c'est un rôle Malisafi
    $malisafi_roles = ['malisafi_agent_basic', 'malisafi_agent_premium', 'malisafi_owner', 'malisafi_developer'];
    if (!array_intersect($malisafi_roles, $user->roles)) {
        return;
    }
    
    $wpdb->insert(
        $wpdb->prefix . 'mf_user_activity',
        [
            'user_id' => $user->ID,
            'activity_type' => 'login',
            'session_id' => session_id() ?: wp_generate_uuid4(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'device_type' => wp_is_mobile() ? 'mobile' : 'desktop'
        ]
    );
}

// Hook: wp_logout
add_action('wp_logout', 'malisafi_track_user_logout');
function malisafi_track_user_logout($user_id) {
    global $wpdb;
    
    // Calculer temps session total
    $last_activity = $wpdb->get_row($wpdb->prepare("
        SELECT created_at FROM {$wpdb->prefix}mf_user_activity 
        WHERE user_id = %d AND activity_type = 'login'
        ORDER BY created_at DESC LIMIT 1
    ", $user_id));
    
    $time_spent = $last_activity ? (time() - strtotime($last_activity->created_at)) : 0;
    
    $wpdb->insert(
        $wpdb->prefix . 'mf_user_activity',
        [
            'user_id' => $user_id,
            'activity_type' => 'logout',
            'time_spent' => $time_spent,
            'session_id' => session_id() ?: '',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]
    );
}
```

#### Tracking Soumissions Propriété
```php
// Hook: Formulaire chargé
add_action('wp_enqueue_scripts', 'malisafi_track_property_form_load');
function malisafi_track_property_form_load() {
    if (is_page('agent-add-property') || is_page('owner-add-property')) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'mf_submission_funnel',
            [
                'user_id' => get_current_user_id(),
                'session_id' => session_id() ?: wp_generate_uuid4(),
                'step_name' => 'form_loaded'
            ]
        );
        
        // JavaScript tracking pour étapes
        wp_add_inline_script('malisafi-property-submit-form', "
            jQuery(document).ready(function($) {
                // Track section changes
                $('.form-section').on('blur', ':input', function() {
                    var section = $(this).closest('.form-section').find('.form-section-title').text();
                    malisafiTrackFunnelStep(section, $(this).attr('name'), $(this).val());
                });
            });
            
            function malisafiTrackFunnelStep(section, field, value) {
                $.post(malisafiAjax.ajaxurl, {
                    action: 'track_funnel_step',
                    nonce: malisafiAjax.nonce,
                    section: section,
                    field: field,
                    has_value: value ? 1 : 0
                });
            }
        ");
    }
}

// AJAX handler pour étapes funnel
add_action('wp_ajax_track_funnel_step', 'malisafi_ajax_track_funnel_step');
function malisafi_ajax_track_funnel_step() {
    check_ajax_referer('malisafi_nonce', 'nonce');
    
    global $wpdb;
    
    $section_map = [
        'Basic Information' => 'basic_info',
        'Pricing' => 'pricing',
        'Property Details' => 'details',
        'Location' => 'location',
        'Amenities' => 'amenities',
        'Property Images' => 'images'
    ];
    
    $step_name = $section_map[$_POST['section']] ?? 'unknown';
    
    $wpdb->insert(
        $wpdb->prefix . 'mf_submission_funnel',
        [
            'user_id' => get_current_user_id(),
            'session_id' => session_id(),
            'step_name' => $step_name,
            'step_data' => json_encode([
                'field' => sanitize_text_field($_POST['field']),
                'filled' => intval($_POST['has_value'])
            ])
        ]
    );
    
    wp_send_json_success();
}

// Hook: Soumission propriété complète
add_action('malisafi_property_submitted', 'malisafi_track_property_submission', 10, 2);
function malisafi_track_property_submission($property_id, $status) {
    global $wpdb;
    
    // Marquer funnel comme complet
    $wpdb->update(
        $wpdb->prefix . 'mf_submission_funnel',
        ['completed' => 1, 'property_id' => $property_id],
        [
            'user_id' => get_current_user_id(),
            'session_id' => session_id()
        ]
    );
    
    // Log soumission finale
    $wpdb->insert(
        $wpdb->prefix . 'mf_submission_funnel',
        [
            'user_id' => get_current_user_id(),
            'session_id' => session_id(),
            'step_name' => 'submit_success',
            'property_id' => $property_id,
            'completed' => 1
        ]
    );
}
```

### Property Performance Analytics Hooks

#### Tracking Vues Propriété
```php
// Hook: template_redirect (single property)
add_action('template_redirect', 'malisafi_track_property_view');
function malisafi_track_property_view() {
    if (!is_singular('malisafi_property')) {
        return;
    }
    
    global $wpdb, $post;
    
    $property_meta = $wpdb->get_row($wpdb->prepare("
        SELECT * FROM {$wpdb->prefix}mf_properties 
        WHERE post_id = %d
    ", $post->ID));
    
    if (!$property_meta) {
        return;
    }
    
    // Détecter device type
    $device_type = 'desktop';
    if (wp_is_mobile()) {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $device_type = (stripos($ua, 'tablet') !== false) ? 'tablet' : 'mobile';
    }
    
    // Session ID persistant
    if (!session_id()) {
        session_start();
    }
    $session_id = session_id();
    
    $wpdb->insert(
        $wpdb->prefix . 'mf_property_views',
        [
            'property_id' => $property_meta->property_id,
            'post_id' => $post->ID,
            'user_id' => get_current_user_id() ?: null,
            'session_id' => $session_id,
            'view_type' => 'single',
            'source' => $_GET['utm_source'] ?? ($_SERVER['HTTP_REFERER'] ?? 'direct'),
            'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            'device_type' => $device_type,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]
    );
    
    // Incrémenter compteur vues dans mf_properties
    $wpdb->query($wpdb->prepare("
        UPDATE {$wpdb->prefix}mf_properties 
        SET views_count = views_count + 1, last_viewed = NOW()
        WHERE property_id = %d
    ", $property_meta->property_id));
    
    // Aussi dans analytics legacy
    $wpdb->insert(
        $wpdb->prefix . 'mf_analytics',
        [
            'property_id' => $property_meta->property_id,
            'user_id' => get_current_user_id() ?: null,
            'action' => 'view',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'session_id' => $session_id
        ]
    );
}
```

#### Tracking Interactions
```php
// AJAX: Favorite property
add_action('wp_ajax_favorite_property', 'malisafi_ajax_favorite_property');
function malisafi_ajax_favorite_property() {
    check_ajax_referer('malisafi_nonce', 'nonce');
    
    global $wpdb;
    $property_id = intval($_POST['property_id']);
    $user_id = get_current_user_id();
    
    // Ajouter aux favoris (table existante)
    $wpdb->insert(
        $wpdb->prefix . 'mf_favorites',
        [
            'user_id' => $user_id,
            'property_id' => $property_id
        ]
    );
    
    // Track interaction
    $wpdb->insert(
        $wpdb->prefix . 'mf_property_interactions',
        [
            'property_id' => $property_id,
            'user_id' => $user_id,
            'interaction_type' => 'favorite',
            'session_id' => session_id()
        ]
    );
    
    wp_send_json_success();
}

// AJAX: Share property
add_action('wp_ajax_share_property', 'malisafi_ajax_share_property');
add_action('wp_ajax_nopriv_share_property', 'malisafi_ajax_share_property');
function malisafi_ajax_share_property() {
    check_ajax_referer('malisafi_nonce', 'nonce');
    
    global $wpdb;
    $property_id = intval($_POST['property_id']);
    $share_method = sanitize_text_field($_POST['method']); // email, facebook, twitter, whatsapp
    
    $wpdb->insert(
        $wpdb->prefix . 'mf_property_interactions',
        [
            'property_id' => $property_id,
            'user_id' => get_current_user_id() ?: null,
            'interaction_type' => 'share_social',
            'interaction_data' => json_encode(['method' => $share_method]),
            'session_id' => session_id()
        ]
    );
    
    wp_send_json_success();
}
```

#### Tracking Recherches
```php
// Hook: AJAX property search
add_action('wp_ajax_property_filter', 'malisafi_track_search', 5);
add_action('wp_ajax_nopriv_property_filter', 'malisafi_track_search', 5);
function malisafi_track_search() {
    global $wpdb;
    
    $filters = [
        'location' => $_POST['location'] ?? '',
        'min_price' => $_POST['min_price'] ?? '',
        'max_price' => $_POST['max_price'] ?? '',
        'bedrooms' => $_POST['bedrooms'] ?? '',
        'bathrooms' => $_POST['bathrooms'] ?? '',
        'property_type' => $_POST['property_type'] ?? '',
        'property_status' => $_POST['property_status'] ?? ''
    ];
    
    // Nettoyer filters vides
    $filters = array_filter($filters);
    
    $wpdb->insert(
        $wpdb->prefix . 'mf_search_analytics',
        [
            'user_id' => get_current_user_id() ?: null,
            'session_id' => session_id(),
            'search_type' => 'filter',
            'filters_used' => json_encode($filters),
            'device_type' => wp_is_mobile() ? 'mobile' : 'desktop'
        ]
    );
}
```

---

## 📈 Dashboard Admin Analytics

### Vue d'ensemble du Dashboard

Créer une page d'admin complète : **Malisafi Analytics**

#### Structure Menu
```php
// Hook: admin_menu
add_action('admin_menu', 'malisafi_analytics_menu');
function malisafi_analytics_menu() {
    add_menu_page(
        __('Malisafi Analytics', 'malisafi-mls'),
        __('Analytics', 'malisafi-mls'),
        'manage_options',
        'malisafi-analytics',
        'malisafi_analytics_page',
        'dashicons-chart-line',
        30
    );
    
    // Sous-menus
    add_submenu_page(
        'malisafi-analytics',
        __('Overview', 'malisafi-mls'),
        __('Overview', 'malisafi-mls'),
        'manage_options',
        'malisafi-analytics',
        'malisafi_analytics_overview'
    );
    
    add_submenu_page(
        'malisafi-analytics',
        __('User Activity', 'malisafi-mls'),
        __('User Activity', 'malisafi-mls'),
        'manage_options',
        'malisafi-analytics-users',
        'malisafi_analytics_users'
    );
    
    add_submenu_page(
        'malisafi-analytics',
        __('Properties', 'malisafi-mls'),
        __('Properties', 'malisafi-mls'),
        'manage_options',
        'malisafi-analytics-properties',
        'malisafi_analytics_properties'
    );
    
    add_submenu_page(
        'malisafi-analytics',
        __('Searches', 'malisafi-mls'),
        __('Searches', 'malisafi-mls'),
        'manage_options',
        'malisafi-analytics-searches',
        'malisafi_analytics_searches'
    );
    
    add_submenu_page(
        'malisafi-analytics',
        __('Revenue', 'malisafi-mls'),
        __('Revenue', 'malisafi-mls'),
        'manage_options',
        'malisafi-analytics-revenue',
        'malisafi_analytics_revenue'
    );
    
    add_submenu_page(
        'malisafi-analytics',
        __('Fraud Detection', 'malisafi-mls'),
        __('Fraud Detection', 'malisafi-mls'),
        'manage_options',
        'malisafi-analytics-fraud',
        'malisafi_analytics_fraud'
    );
    
    add_submenu_page(
        'malisafi-analytics',
        __('System Health', 'malisafi-mls'),
        __('System Health', 'malisafi-mls'),
        'manage_options',
        'malisafi-analytics-health',
        'malisafi_analytics_health'
    );
}
```

### Requêtes Analytics Principales

#### Métriques Core Usage
```php
class Malisafi_Analytics_Core {
    
    /**
     * Get properties added per role (last 30 days)
     */
    public static function get_properties_by_role($days = 30) {
        global $wpdb;
        
        $sql = "
            SELECT 
                u.role,
                COUNT(p.property_id) as total_properties,
                COUNT(CASE WHEN p.status = 'published' THEN 1 END) as published,
                COUNT(CASE WHEN p.status = 'pending_review' THEN 1 END) as pending
            FROM {$wpdb->prefix}mf_properties p
            INNER JOIN (
                SELECT 
                    u.ID,
                    CASE 
                        WHEN um.meta_value LIKE '%agent_premium%' THEN 'agent_premium'
                        WHEN um.meta_value LIKE '%agent_basic%' THEN 'agent_basic'
                        WHEN um.meta_value LIKE '%owner%' THEN 'owner'
                        WHEN um.meta_value LIKE '%developer%' THEN 'developer'
                        ELSE 'other'
                    END as role
                FROM {$wpdb->users} u
                INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'wp_capabilities'
            ) u ON p.author_id = u.ID
            WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY u.role
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $days));
    }
    
    /**
     * Get login frequency by role
     */
    public static function get_login_frequency($days = 30) {
        global $wpdb;
        
        $sql = "
            SELECT 
                u.role,
                COUNT(DISTINCT ua.user_id) as unique_users,
                COUNT(ua.id) as total_logins,
                ROUND(COUNT(ua.id) / COUNT(DISTINCT ua.user_id), 2) as avg_logins_per_user
            FROM {$wpdb->prefix}mf_user_activity ua
            INNER JOIN (
                SELECT 
                    u.ID,
                    CASE 
                        WHEN um.meta_value LIKE '%agent_premium%' THEN 'agent_premium'
                        WHEN um.meta_value LIKE '%agent_basic%' THEN 'agent_basic'
                        WHEN um.meta_value LIKE '%owner%' THEN 'owner'
                        WHEN um.meta_value LIKE '%developer%' THEN 'developer'
                        ELSE 'other'
                    END as role
                FROM {$wpdb->users} u
                INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'wp_capabilities'
            ) u ON ua.user_id = u.ID
            WHERE ua.activity_type = 'login'
            AND ua.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY u.role
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $days));
    }
    
    /**
     * Get submission funnel metrics
     */
    public static function get_submission_funnel($days = 30) {
        global $wpdb;
        
        $sql = "
            SELECT 
                step_name,
                COUNT(DISTINCT session_id) as sessions_reached,
                COUNT(DISTINCT CASE WHEN completed = 1 THEN session_id END) as sessions_completed,
                ROUND(
                    COUNT(DISTINCT CASE WHEN completed = 1 THEN session_id END) * 100.0 / 
                    COUNT(DISTINCT session_id), 
                    2
                ) as completion_rate,
                ROUND(AVG(time_spent), 2) as avg_time_spent
            FROM {$wpdb->prefix}mf_submission_funnel
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY step_name
            ORDER BY FIELD(step_name, 
                'form_loaded', 'basic_info', 'pricing', 'details', 
                'location', 'amenities', 'images', 'submit_success'
            )
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $days));
    }
    
    /**
     * Get drop-off analysis
     */
    public static function get_dropoff_points($days = 30) {
        global $wpdb;
        
        $sql = "
            SELECT 
                sf1.step_name as from_step,
                sf2.step_name as to_step,
                COUNT(DISTINCT sf1.session_id) as total_sessions,
                COUNT(DISTINCT sf2.session_id) as continued_sessions,
                COUNT(DISTINCT sf1.session_id) - COUNT(DISTINCT sf2.session_id) as dropped,
                ROUND(
                    (COUNT(DISTINCT sf1.session_id) - COUNT(DISTINCT sf2.session_id)) * 100.0 / 
                    COUNT(DISTINCT sf1.session_id), 
                    2
                ) as drop_rate
            FROM {$wpdb->prefix}mf_submission_funnel sf1
            LEFT JOIN {$wpdb->prefix}mf_submission_funnel sf2 
                ON sf1.session_id = sf2.session_id 
                AND sf2.created_at > sf1.created_at
            WHERE sf1.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY sf1.step_name, sf2.step_name
            HAVING dropped > 0
            ORDER BY drop_rate DESC
            LIMIT 10
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $days));
    }
    
    /**
     * Get top contributors by role
     */
    public static function get_top_contributors($role = 'all', $limit = 10) {
        global $wpdb;
        
        $role_filter = '';
        if ($role !== 'all') {
            $role_filter = $wpdb->prepare("AND um.meta_value LIKE %s", '%' . $role . '%');
        }
        
        $sql = "
            SELECT 
                u.ID,
                u.display_name,
                u.user_email,
                COUNT(p.property_id) as total_properties,
                SUM(p.views_count) as total_views,
                SUM(p.inquiries_count) as total_inquiries,
                MAX(p.created_at) as last_property_added
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'wp_capabilities'
            INNER JOIN {$wpdb->prefix}mf_properties p ON u.ID = p.author_id
            WHERE 1=1 {$role_filter}
            GROUP BY u.ID
            ORDER BY total_properties DESC
            LIMIT %d
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $limit));
    }
}
```

#### Métriques Property Performance
```php
class Malisafi_Analytics_Properties {
    
    /**
     * Get property engagement metrics
     */
    public static function get_engagement_metrics($property_id = null) {
        global $wpdb;
        
        $where = $property_id ? $wpdb->prepare("WHERE pv.property_id = %d", $property_id) : "";
        
        $sql = "
            SELECT 
                pv.property_id,
                p.post_id,
                COUNT(DISTINCT pv.id) as total_views,
                COUNT(DISTINCT pv.session_id) as unique_visitors,
                ROUND(AVG(pv.view_duration), 2) as avg_time_on_page,
                ROUND(AVG(pv.scroll_depth), 2) as avg_scroll_depth,
                SUM(CASE WHEN pv.gallery_viewed = 1 THEN 1 ELSE 0 END) as gallery_views,
                SUM(CASE WHEN pv.map_viewed = 1 THEN 1 ELSE 0 END) as map_views,
                SUM(CASE WHEN pv.contact_clicked = 1 THEN 1 ELSE 0 END) as contact_clicks,
                COUNT(DISTINCT pi.id) as total_interactions,
                (
                    SELECT COUNT(*) 
                    FROM {$wpdb->prefix}mf_property_interactions pi2 
                    WHERE pi2.property_id = pv.property_id 
                    AND pi2.interaction_type = 'inquiry'
                ) as inquiries,
                (
                    SELECT COUNT(*) 
                    FROM {$wpdb->prefix}mf_property_interactions pi3 
                    WHERE pi3.property_id = pv.property_id 
                    AND pi3.interaction_type = 'favorite'
                ) as favorites
            FROM {$wpdb->prefix}mf_property_views pv
            LEFT JOIN {$wpdb->prefix}mf_properties p ON pv.property_id = p.property_id
            LEFT JOIN {$wpdb->prefix}mf_property_interactions pi ON pv.property_id = pi.property_id
            {$where}
            GROUP BY pv.property_id
        ";
        
        return $property_id ? $wpdb->get_row($sql) : $wpdb->get_results($sql);
    }
    
    /**
     * Get geographic insights
     */
    public static function get_geographic_insights() {
        global $wpdb;
        
        $sql = "
            SELECT 
                pt.name as location,
                COUNT(DISTINCT p.property_id) as properties_count,
                SUM(p.views_count) as total_views,
                SUM(p.inquiries_count) as total_inquiries,
                ROUND(AVG(p.price), 2) as avg_price
            FROM {$wpdb->prefix}mf_properties p
            INNER JOIN {$wpdb->posts} posts ON p.post_id = posts.ID
            INNER JOIN {$wpdb->term_relationships} tr ON posts.ID = tr.object_id
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            INNER JOIN {$wpdb->terms} pt ON tt.term_id = pt.term_id
            WHERE tt.taxonomy = 'malisafi_property_location'
            GROUP BY pt.term_id
            ORDER BY properties_count DESC
        ";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get conversion metrics
     */
    public static function get_conversion_metrics($days = 30) {
        global $wpdb;
        
        $sql = "
            SELECT 
                DATE(pv.created_at) as date,
                COUNT(DISTINCT pv.id) as total_views,
                COUNT(DISTINCT CASE WHEN pi.interaction_type = 'inquiry' THEN pi.id END) as inquiries,
                COUNT(DISTINCT CASE WHEN pi.interaction_type = 'favorite' THEN pi.id END) as favorites,
                COUNT(DISTINCT CASE WHEN pi.interaction_type LIKE 'share_%' THEN pi.id END) as shares,
                ROUND(
                    COUNT(DISTINCT CASE WHEN pi.interaction_type = 'inquiry' THEN pi.id END) * 100.0 / 
                    COUNT(DISTINCT pv.id), 
                    2
                ) as inquiry_conversion_rate
            FROM {$wpdb->prefix}mf_property_views pv
            LEFT JOIN {$wpdb->prefix}mf_property_interactions pi 
                ON pv.property_id = pi.property_id 
                AND pv.session_id = pi.session_id
            WHERE pv.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(pv.created_at)
            ORDER BY date DESC
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $days));
    }
}
```

#### Métriques Advanced Admin
```php
class Malisafi_Analytics_Advanced {
    
    /**
     * Detect duplicate listings
     */
    public static function detect_duplicate_listings() {
        global $wpdb;
        
        $sql = "
            SELECT 
                p1.property_id as property_1,
                p2.property_id as property_2,
                p1.author_id,
                p1.full_address,
                p1.price,
                p1.bedrooms,
                p1.bathrooms,
                'duplicate_listing' as fraud_type,
                75 as confidence_score
            FROM {$wpdb->prefix}mf_properties p1
            INNER JOIN {$wpdb->prefix}mf_properties p2 
                ON p1.property_id < p2.property_id
                AND (
                    (p1.latitude = p2.latitude AND p1.longitude = p2.longitude)
                    OR SOUNDEX(p1.full_address) = SOUNDEX(p2.full_address)
                )
                AND p1.bedrooms = p2.bedrooms
                AND p1.bathrooms = p2.bathrooms
                AND ABS(p1.price - p2.price) < (p1.price * 0.10)
            WHERE p1.status != 'rejected'
            AND p2.status != 'rejected'
        ";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Detect rapid edits (suspicious activity)
     */
    public static function detect_rapid_edits($threshold = 5, $minutes = 10) {
        global $wpdb;
        
        $sql = "
            SELECT 
                user_id,
                COUNT(DISTINCT property_id) as properties_edited,
                MIN(created_at) as first_edit,
                MAX(created_at) as last_edit,
                TIMESTAMPDIFF(MINUTE, MIN(created_at), MAX(created_at)) as time_span_minutes
            FROM {$wpdb->prefix}mf_user_activity
            WHERE activity_type = 'property_edit'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY user_id
            HAVING properties_edited >= %d
            AND time_span_minutes <= %d
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $threshold, $minutes));
    }
    
    /**
     * Get revenue metrics
     */
    public static function get_revenue_metrics($days = 30) {
        global $wpdb;
        
        $sql = "
            SELECT 
                DATE(created_at) as date,
                transaction_type,
                plan_type,
                COUNT(*) as transactions,
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as revenue,
                SUM(CASE WHEN status = 'refunded' THEN amount ELSE 0 END) as refunds,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failures
            FROM {$wpdb->prefix}mf_revenue_tracking
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(created_at), transaction_type, plan_type
            ORDER BY date DESC
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $days));
    }
    
    /**
     * Get system health metrics
     */
    public static function get_system_health($hours = 24) {
        global $wpdb;
        
        $sql = "
            SELECT 
                metric_type,
                AVG(metric_value) as avg_value,
                MAX(metric_value) as max_value,
                MIN(metric_value) as min_value,
                metric_unit,
                SUM(CASE WHEN status = 'critical' THEN 1 ELSE 0 END) as critical_count,
                SUM(CASE WHEN status = 'warning' THEN 1 ELSE 0 END) as warning_count
            FROM {$wpdb->prefix}mf_system_health
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d HOUR)
            GROUP BY metric_type, metric_unit
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $hours));
    }
}
```

---

## 📊 Visualisations (Chart.js)

### Enqueue Scripts
```php
add_action('admin_enqueue_scripts', 'malisafi_analytics_scripts');
function malisafi_analytics_scripts($hook) {
    if (strpos($hook, 'malisafi-analytics') === false) {
        return;
    }
    
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', [], '4.4.0', true);
    wp_enqueue_script('malisafi-analytics-charts', MALISAFI_MLS_URL . 'assets/js/analytics-charts.js', ['chart-js'], MALISAFI_MLS_VERSION, true);
    
    wp_localize_script('malisafi-analytics-charts', 'malisafiAnalytics', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('malisafi_analytics_nonce')
    ]);
}
```

### Chart Templates
```javascript
// assets/js/analytics-charts.js

// Properties by Role - Pie Chart
function renderPropertiesByRoleChart(data) {
    const ctx = document.getElementById('propertiesByRoleChart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: data.map(d => d.role),
            datasets: [{
                data: data.map(d => d.total_properties),
                backgroundColor: [
                    '#737d5d', // agent_premium
                    '#9ca88a', // agent_basic
                    '#4a5a3a', // owner
                    '#2d3d1d'  // developer
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: 'Properties Added by Role (Last 30 Days)'
                }
            }
        }
    });
}

// Submission Funnel - Bar Chart
function renderSubmissionFunnelChart(data) {
    const ctx = document.getElementById('submissionFunnelChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => d.step_name.replace('_', ' ')),
            datasets: [{
                label: 'Sessions Reached',
                data: data.map(d => d.sessions_reached),
                backgroundColor: '#737d5d'
            }, {
                label: 'Sessions Completed',
                data: data.map(d => d.sessions_completed),
                backgroundColor: '#4a5a3a'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Property Submission Funnel'
                }
            }
        }
    });
}

// Engagement Over Time - Line Chart
function renderEngagementChart(data) {
    const ctx = document.getElementById('engagementChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(d => d.date),
            datasets: [{
                label: 'Views',
                data: data.map(d => d.total_views),
                borderColor: '#737d5d',
                tension: 0.1
            }, {
                label: 'Inquiries',
                data: data.map(d => d.inquiries),
                borderColor: '#4a5a3a',
                tension: 0.1
            }, {
                label: 'Favorites',
                data: data.map(d => d.favorites),
                borderColor: '#9ca88a',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Property Engagement Over Time'
                }
            }
        }
    });
}

// Revenue Tracking - Stacked Bar
function renderRevenueChart(data) {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [...new Set(data.map(d => d.date))],
            datasets: [{
                label: 'Subscription Revenue',
                data: data.filter(d => d.transaction_type === 'subscription').map(d => d.revenue),
                backgroundColor: '#737d5d'
            }, {
                label: 'Featured Listings',
                data: data.filter(d => d.transaction_type === 'featured_listing').map(d => d.revenue),
                backgroundColor: '#4a5a3a'
            }, {
                label: 'Boosts',
                data: data.filter(d => d.transaction_type === 'boost').map(d => d.revenue),
                backgroundColor: '#9ca88a'
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    stacked: true
                },
                y: {
                    stacked: true,
                    beginAtZero: true
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Revenue by Type (KES)'
                }
            }
        }
    });
}
```

---

## 📁 Structure Fichiers Recommandée

```
includes/
├── analytics/
│   ├── class-analytics-core.php           # Core usage metrics
│   ├── class-analytics-properties.php     # Property performance
│   ├── class-analytics-advanced.php       # Fraud, revenue, health
│   ├── class-analytics-tracker.php        # Event tracking hooks
│   └── class-analytics-database.php       # Database tables creation
│
admin/
├── analytics/
│   ├── overview.php                       # Dashboard overview
│   ├── users.php                          # User activity page
│   ├── properties.php                     # Property analytics page
│   ├── searches.php                       # Search analytics page
│   ├── revenue.php                        # Revenue tracking page
│   ├── fraud.php                          # Fraud detection page
│   └── health.php                         # System health page
│
assets/
├── js/
│   ├── analytics-charts.js                # Chart.js visualizations
│   └── analytics-tracking.js              # Frontend event tracking
├── css/
│   └── analytics.css                      # Analytics dashboard styles
```

---

## ⚙️ Implémentation Prioritaire

### Phase 1 (Essentiel - 1 semaine)
1. ✅ Créer tables database (8 nouvelles tables)
2. ✅ Implémenter tracking hooks core (login, property submission)
3. ✅ Dashboard admin basique avec métriques overview
4. ✅ Chart.js pour visualisations basiques

### Phase 2 (Important - 1 semaine)
5. ✅ Property views & interactions tracking complet
6. ✅ Search analytics avec filtres
7. ✅ Submission funnel avec drop-off analysis
8. ✅ Export CSV/PDF des rapports

### Phase 3 (Avancé - 2 semaines)
9. ✅ Fraud detection automatique
10. ✅ Revenue tracking avec Stripe integration
11. ✅ System health monitoring
12. ✅ Email alerts pour métriques critiques

### Phase 4 (Optimisation - 1 semaine)
13. ✅ Performance optimization (indexes, caching)
14. ✅ API REST pour analytics externes
15. ✅ Google Analytics integration
16. ✅ Real-time dashboards (WebSockets optionnel)

---

## 🎯 Prochaines Actions Recommandées

**Besoin de clarification sur:**
1. **Style de visualisations**: Préférez-vous Chart.js, Google Charts, ou autre?
2. **Stockage données**: Combien de temps garder les analytics (30 jours, 90 jours, 1 an)?
3. **Alertes**: Quels seuils déclencher des notifications admin?
4. **Export**: Formats préférés (CSV, PDF, Excel)?
5. **API externe**: Besoin d'exposer analytics via REST API?

**Questions techniques:**
- Utiliser cron jobs WordPress pour rapports automatiques?
- Implémenter data aggregation pour performance (tables summary)?
- Ajouter GDPR compliance (anonymisation IP, consent tracking)?

Confirmez ces points pour commencer l'implémentation! 🚀
