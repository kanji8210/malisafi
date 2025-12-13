-- Create property reports table for moderation system
-- Run this SQL in phpMyAdmin if the table wasn't created during plugin activation
CREATE TABLE IF NOT EXISTS `wp_mf_property_reports` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `property_id` BIGINT UNSIGNED NOT NULL,
    `reporter_id` BIGINT UNSIGNED NOT NULL,
    `reason` VARCHAR(50) NOT NULL,
    `details` TEXT,
    `status` ENUM('pending', 'reviewed', 'dismissed') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `property_id` (`property_id`),
    KEY `reporter_id` (`reporter_id`),
    KEY `status` (`status`),
    KEY `idx_property_reports` (`property_id`, `status`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;