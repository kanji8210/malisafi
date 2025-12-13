-- Script de correction pour créer les tables manquantes
-- Malisafi MLS Plugin Database Setup
-- Instructions: Importez ce fichier via phpMyAdmin ou via ligne de commande
-- mysql -u root wordpress < fix-database-tables.sql
-- Table des abonnements (subscriptions)
CREATE TABLE IF NOT EXISTS wp_mf_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    plan_type ENUM(
        'agent_basic',
        'agent_premium',
        'owner_basic',
        'developer'
    ) NOT NULL,
    status ENUM('active', 'canceled', 'expired', 'pending') DEFAULT 'pending',
    stripe_subscription_id VARCHAR(255),
    current_period_start DATETIME,
    current_period_end DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY user_id (user_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Table des limites utilisateur (user limits)
CREATE TABLE IF NOT EXISTS wp_mf_user_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    max_listings INT DEFAULT 0,
    used_listings INT DEFAULT 0,
    featured_listings INT DEFAULT 0,
    can_boost BOOLEAN DEFAULT FALSE,
    analytics_access BOOLEAN DEFAULT FALSE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY user_id (user_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Table principale des propriétés (properties master)
CREATE TABLE IF NOT EXISTS wp_mf_properties (
    property_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    author_id BIGINT UNSIGNED NOT NULL,
    property_type ENUM(
        'residential',
        'commercial',
        'mixed-use',
        'development'
    ) NOT NULL,
    transaction_type ENUM('sale', 'rent', 'lease') NOT NULL,
    price DECIMAL(15, 2),
    price_currency VARCHAR(3) DEFAULT 'USD',
    status ENUM(
        'draft',
        'pending_review',
        'published',
        'rejected',
        'sold',
        'rented'
    ) DEFAULT 'draft',
    full_address TEXT,
    display_address TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    privacy_level ENUM('full', 'approximate', 'area_only') DEFAULT 'full',
    bedrooms INT,
    bathrooms DECIMAL(3, 1),
    area_sqft INT,
    area_sqm INT,
    featured BOOLEAN DEFAULT FALSE,
    views_count INT DEFAULT 0,
    inquiries_count INT DEFAULT 0,
    last_viewed TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY author_id (author_id),
    KEY post_id (post_id),
    KEY idx_location (latitude, longitude),
    KEY idx_price (price),
    KEY idx_status (status),
    KEY idx_author (author_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Table des équipements de propriété (property amenities)
CREATE TABLE IF NOT EXISTS wp_mf_property_amenities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id BIGINT UNSIGNED NOT NULL,
    amenity_type VARCHAR(50) NOT NULL,
    amenity_value VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY property_id (property_id),
    KEY idx_amenity_type (amenity_type)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Table des médias de propriété (property media)
CREATE TABLE IF NOT EXISTS wp_mf_property_media (
    media_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id BIGINT UNSIGNED NOT NULL,
    media_type ENUM(
        'image',
        'virtual_tour',
        'video',
        'floor_plan',
        'document'
    ) NOT NULL,
    media_url TEXT NOT NULL,
    thumbnail_url TEXT,
    display_order INT DEFAULT 0,
    metadata TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY property_id (property_id),
    KEY idx_property_media (property_id, media_type)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Table des demandes de renseignements (inquiries) - TABLE MANQUANTE QUI CAUSE L'ERREUR
CREATE TABLE IF NOT EXISTS wp_mf_inquiries (
    inquiry_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    agent_id BIGINT UNSIGNED NOT NULL,
    inquiry_type ENUM('general', 'tour_request', 'price_negotiation') DEFAULT 'general',
    message TEXT,
    status ENUM('new', 'read', 'replied', 'closed') DEFAULT 'new',
    client_phone VARCHAR(20),
    client_email VARCHAR(255),
    preferred_contact_time ENUM('morning', 'afternoon', 'evening', 'anytime'),
    tour_requested_date DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY property_id (property_id),
    KEY client_id (client_id),
    KEY agent_id (agent_id),
    KEY idx_agent_inquiries (agent_id, status),
    KEY idx_property_inquiries (property_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Table des recherches sauvegardées (saved searches)
CREATE TABLE IF NOT EXISTS wp_mf_saved_searches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    search_name VARCHAR(255),
    search_parameters TEXT NOT NULL,
    notification_frequency ENUM('instant', 'daily', 'weekly', 'none') DEFAULT 'none',
    last_notified TIMESTAMP NULL,
    match_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY user_id (user_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Table des favoris (favorites)
CREATE TABLE IF NOT EXISTS wp_mf_favorites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    property_id BIGINT UNSIGNED NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY user_id (user_id),
    KEY property_id (property_id),
    UNIQUE KEY unique_favorite (user_id, property_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Table de la file de modération (moderation queue)
CREATE TABLE IF NOT EXISTS wp_mf_moderation_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id BIGINT UNSIGNED NOT NULL,
    submitted_by BIGINT UNSIGNED NOT NULL,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM(
        'pending',
        'approved',
        'rejected',
        'changes_requested'
    ) DEFAULT 'pending',
    reviewer_id BIGINT UNSIGNED,
    review_date TIMESTAMP NULL,
    review_notes TEXT,
    rejection_reason TEXT,
    KEY property_id (property_id),
    KEY submitted_by (submitted_by),
    KEY status (status)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Table des rapports de propriétés (property reports)
CREATE TABLE IF NOT EXISTS wp_mf_property_reports (
    report_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id BIGINT UNSIGNED NOT NULL,
    reporter_id BIGINT UNSIGNED NOT NULL,
    report_type ENUM(
        'incorrect_info',
        'inappropriate',
        'duplicate',
        'sold',
        'other'
    ) NOT NULL,
    report_details TEXT,
    status ENUM(
        'pending',
        'reviewed',
        'action_taken',
        'dismissed'
    ) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    reviewed_by BIGINT UNSIGNED,
    admin_notes TEXT,
    KEY property_id (property_id),
    KEY reporter_id (reporter_id),
    KEY status (status)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Table d'analytique (analytics)
CREATE TABLE IF NOT EXISTS wp_mf_analytics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED,
    event_type ENUM(
        'view',
        'contact',
        'favorite',
        'share',
        'inquiry'
    ) NOT NULL,
    event_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_ip VARCHAR(45),
    user_agent TEXT,
    referrer TEXT,
    KEY property_id (property_id),
    KEY user_id (user_id),
    KEY event_type (event_type),
    KEY event_date (event_date)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Message de confirmation
SELECT
    'Toutes les tables ont été créées avec succès!' as Message;