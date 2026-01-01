            <!-- Rate Agent Modal -->
            <div id="rate-agent-modal" class="malisafi-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Noter cet agent</h3>
                        <button class="modal-close" onclick="document.getElementById('rate-agent-modal').classList.remove('open');">&times;</button>
                    </div>
                    <div class="modal-body">
                        <?php if (!is_user_logged_in()): ?>
                            <div style="text-align:center;padding:20px 0;">
                                <p style="margin-bottom:16px;color:var(--mls-accent);font-weight:500;">Vous devez être connecté pour noter un agent.</p>
                                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="button-primary" style="padding:8px 18px;">Se connecter</a>
                            </div>
                        <?php elseif (!$can_rate): ?>
                            <p>Vous ne pouvez pas noter cet agent (rôle ou restriction).</p>
                        <?php else: ?>
                        <form id="rate-agent-form" method="post">
                            <input type="hidden" name="agent_id" value="<?php echo $author_id; ?>">
                            <div class="form-group">
                                <label for="rating">Votre note :</label>
                                <select name="rating" id="rating" required>
                                    <option value="">Choisir...</option>
                                    <option value="5">5 - Excellent</option>
                                    <option value="4">4 - Très bien</option>
                                    <option value="3">3 - Bien</option>
                                    <option value="2">2 - Moyen</option>
                                    <option value="1">1 - Mauvais</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="review_title">Titre de l'avis :</label>
                                <input type="text" name="review_title" id="review_title" maxlength="255" placeholder="Titre court" required>
                            </div>
                            <div class="form-group">
                                <label for="review_text">Votre avis :</label>
                                <textarea name="review_text" id="review_text" rows="4" placeholder="Votre expérience..." required></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="button-secondary modal-close" onclick="document.getElementById('rate-agent-modal').classList.remove('open');">Annuler</button>
                                <button type="submit" class="button-primary">Envoyer</button>
                            </div>
                        </form>
                        <div id="rate-agent-success" style="display:none;color:var(--mls-accent);margin-top:10px;"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <script>
            document.getElementById('rate-agent-form')?.addEventListener('submit', function(e) {
                e.preventDefault();
                var form = e.target;
                var data = new FormData(form);
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: new URLSearchParams({
                        action: 'malisafi_rate_agent',
                        agent_id: data.get('agent_id'),
                        rating: data.get('rating'),
                        review_title: data.get('review_title'),
                        review_text: data.get('review_text'),
                        nonce: '<?php echo wp_create_nonce('malisafi_agent_nonce'); ?>'
                    })
                })
                .then(r => r.json())
                .then(resp => {
                    if (resp.success) {
                        form.style.display = 'none';
                        document.getElementById('rate-agent-success').style.display = 'block';
                        document.getElementById('rate-agent-success').textContent = 'Merci pour votre avis !';
                    } else {
                        alert(resp.data?.message || 'Erreur lors de l’envoi.');
                    }
                });
            });
            </script>
        foreach ($attachments as $attachment) {
            $gallery_images[] = $attachment->ID;
        }
    }
    
    // Features
    $features = array(
        'pool' => get_post_meta($property_id, '_malisafi_pool', true),
        'gym' => get_post_meta($property_id, '_malisafi_gym', true),
        'garden' => get_post_meta($property_id, '_malisafi_garden', true),
        'balcony' => get_post_meta($property_id, '_malisafi_balcony', true),
        'parking' => get_post_meta($property_id, '_malisafi_parking', true),
        'security' => get_post_meta($property_id, '_malisafi_security', true),
        'elevator' => get_post_meta($property_id, '_malisafi_elevator', true),
        'furnished' => get_post_meta($property_id, '_malisafi_furnished', true),
        'air_conditioning' => get_post_meta($property_id, '_malisafi_air_conditioning', true),
    );
    
    // Format price
    $currency_symbol = ($currency === 'KES') ? 'KSh' : '$';
    $formatted_price = $currency_symbol . ' ' . number_format(floatval($price));
    
    // Check if user has favorited this property
    $user_id = get_current_user_id();
    $is_favorited = false;
    if ($user_id) {
        $favorites = get_user_meta($user_id, '_malisafi_favorites', true);
        $favorites = $favorites ? explode(',', $favorites) : array();
        $is_favorited = in_array($property_id, $favorites);
    }
?>

<div class="malisafi-single-property">
    
    <!-- Property Gallery -->
    <section class="property-gallery">
        <?php 
        // Prepare all images for thumbnails
        $all_images = array();
        $featured_img = get_post_thumbnail_id($property_id);
        
        // Add featured image first
        if ($featured_img) {
            $all_images[] = $featured_img;
        }
        
        // Add gallery images (exclude featured if it's already in gallery)
        if (!empty($gallery_images)) {
            foreach ($gallery_images as $img_id) {
                if ($img_id != $featured_img) {
                    $all_images[] = $img_id;
                }
            }
        }
        ?>
        
        <div class="gallery-main-wrapper">
            <div class="gallery-main">
                <?php if (has_post_thumbnail()) : ?>
                    <div class="main-image-badge-wrapper" style="position:relative;">
                        <img src="<?php echo get_the_post_thumbnail_url($property_id, 'full'); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" class="main-image" data-current-index="0">
                        <?php if ($status) : ?>
                            <span class="badge status" style="position:absolute;top:18px;left:18px;z-index:2;">
                                <?php echo esc_html($status); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php else : ?>
                    <img src="<?php echo plugins_url('malisafi/assets/images/placeholder-property.svg'); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" class="main-image" data-current-index="0">
                <?php endif; ?>

                <?php if (!empty($all_images) && count($all_images) > 1) : ?>
                <button class="gallery-nav gallery-nav-prev" aria-label="Previous Image">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>

                <button class="gallery-nav gallery-nav-next" aria-label="Next Image">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
                <?php endif; ?>

                <?php if (!empty($all_images) && count($all_images) > 1) : ?>
                <div class="gallery-counter">
                    <span class="current">1</span> / <span class="total"><?php echo count($all_images); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php 
        // Display thumbnails below if we have images
        if (!empty($all_images)) : 
        ?>
        <!-- Debug: Total images = <?php echo count($all_images); ?>, Gallery IDs: <?php echo esc_html($gallery_ids); ?> -->
        <div class="gallery-thumbnails">
            <?php foreach ($all_images as $index => $img_id) : 
                $img_url = wp_get_attachment_image_url($img_id, 'medium');
                if ($img_url) :
                    $alt_text = get_post_meta($img_id, '_wp_attachment_image_alt', true);
            ?>
                <div class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>" data-image="<?php echo esc_url(wp_get_attachment_image_url($img_id, 'full')); ?>" style="position:relative;">
                    <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr(get_the_title() . ' - Image ' . ($index + 1)); ?>">
                    <?php if ($index !== 0 && !empty($alt_text)) : ?>
                        <span class="badge alt-badge" style="position:absolute;top:8px;left:8px;z-index:2;background:#fff;color:#1e5277;border:1px solid #1e5277;padding:2px 8px;font-size:12px;border-radius:6px;opacity:0.92;">
                            <?php echo esc_html($alt_text); ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php 
                else:
                    // Debug: image ID exists but no URL found
                    echo '<!-- Image ID ' . $img_id . ' has no URL -->';
                endif;
            endforeach; 
            ?>
        </div>
        <?php 
        else:
            echo '<!-- No gallery images found. Gallery IDs meta: ' . esc_html($gallery_ids) . ' -->';
        endif; 
        ?>
    </section>
    
    <div class="property-content-wrapper">
        
        <!-- Main Content -->
        <div class="property-main-content">
            
            <!-- Header -->
            <header class="property-header">
                <div class="property-title-section">
                    <h1 class="property-title"><?php the_title(); ?></h1>
                    <?php if ($city || $state) : ?>
                        <p class="property-location">
                            <span class="dashicons dashicons-location"></span>
                            <?php 
                            $location_parts = array_filter(array($city, $state, $country));
                            echo esc_html(implode(', ', $location_parts));
                            ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="property-actions">
                    <button class="action-button favorite-button <?php echo $is_favorited ? 'favorited' : ''; ?>" data-property-id="<?php echo $property_id; ?>" title="Add to Favorites">
                        <span class="dashicons dashicons-heart"></span>
                        <span class="action-text"><?php echo $is_favorited ? 'Favorited' : 'Favorite'; ?></span>
                    </button>
                    <button class="action-button share-button" title="Share">
                        <span class="dashicons dashicons-share"></span>
                        <span class="action-text">Share</span>
                    </button>
                    <button class="action-button report-button" data-property-id="<?php echo $property_id; ?>" title="Report Property">
                        <span class="dashicons dashicons-flag"></span>
                        <span class="action-text">Report</span>
                    </button>
                </div>
            </header>
            
            <!-- Price and Key Details -->
            <div class="property-key-details">
                <div class="property-specs-row">
                    <div class="spec-icon price-icon" title="<?php echo esc_attr($formatted_price); ?>">
                        <span class="dashicons dashicons-tag"></span>
                    </div>
                    <?php if ($bedrooms) : ?>
                        <div class="spec-icon" title="<?php echo esc_attr($bedrooms . ' Bedroom' . ($bedrooms > 1 ? 's' : '')); ?>">
                            <span class="dashicons dashicons-admin-home"></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($bathrooms) : ?>
                        <div class="spec-icon" title="<?php echo esc_attr($bathrooms . ' Bathroom' . ($bathrooms > 1 ? 's' : '')); ?>">
                            <span class="dashicons dashicons-admin-appearance"></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($area) : ?>
                        <div class="spec-icon" title="<?php echo esc_attr(number_format($area) . ' sq ft'); ?>">
                            <span class="dashicons dashicons-grid-view"></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($garage) : ?>
                        <div class="spec-icon" title="<?php echo esc_attr($garage . ' Garage'); ?>">
                            <span class="dashicons dashicons-car"></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Description -->
            <section class="property-section">
                <h2 class="section-title">Description</h2>
                <div class="property-description">
                    <?php the_content(); ?>
                </div>
            </section>
            
            <!-- Property Details -->
            <section class="property-section">
                <h2 class="section-title">Property Details</h2>
                <div class="property-details-grid">
                    <?php if (!empty($property_type)) : ?>
                        <div class="detail-item">
                            <span class="detail-label">Property Type</span>
                            <span class="detail-value"><?php echo esc_html($property_type[0]); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($status) : ?>
                        <div class="detail-item">
                            <span class="detail-label">Status</span>
                            <span class="detail-value"><?php echo esc_html($status); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($year_built) : ?>
                        <div class="detail-item">
                            <span class="detail-label">Year Built</span>
                            <span class="detail-value"><?php echo esc_html($year_built); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($bedrooms) : ?>
                        <div class="detail-item">
                            <span class="detail-label">Bedrooms</span>
                            <span class="detail-value"><?php echo esc_html($bedrooms); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($bathrooms) : ?>
                        <div class="detail-item">
                            <span class="detail-label">Bathrooms</span>
                            <span class="detail-value"><?php echo esc_html($bathrooms); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($area) : ?>
                        <div class="detail-item">
                            <span class="detail-label">Area</span>
                            <span class="detail-value"><?php echo number_format($area); ?> sq ft</span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($garage) : ?>
                        <div class="detail-item">
                            <span class="detail-label">Garage</span>
                            <span class="detail-value"><?php echo esc_html($garage); ?> car<?php echo $garage > 1 ? 's' : ''; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            
            <!-- Features -->
            <?php if (array_filter($features)) : ?>
            <section class="property-section">
                <h2 class="section-title">Features & Amenities</h2>
                <div class="property-features-grid">
                    <?php 
                    $feature_labels = array(
                        'pool' => 'Swimming Pool',
                        'gym' => 'Gym',
                        'garden' => 'Garden',
                        'balcony' => 'Balcony',
                        'parking' => 'Parking',
                        'security' => '24/7 Security',
                        'elevator' => 'Elevator',
                        'furnished' => 'Furnished',
                        'air_conditioning' => 'Air Conditioning'
                    );
                    
                    foreach ($features as $key => $value) :
                        if ($value) :
                    ?>
                        <div class="feature-item">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <span><?php echo esc_html($feature_labels[$key]); ?></span>
                        </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </section>
            <?php endif; ?>
            
        </div>
        
        <!-- Sidebar -->
        <aside class="property-sidebar">
            
            <!-- Agent Contact Card -->
            <div class="agent-contact-card">
                                <?php
                                $can_rate = false;
                                if (is_user_logged_in()) {
                                    $current_user = wp_get_current_user();
                                    $user_roles = (array) $current_user->roles;
                                    // Interdire aux agents, owners, developers de noter
                                    $forbidden_roles = array('agent_basic', 'agent_premium', 'owner', 'developer');
                                    if (!array_intersect($forbidden_roles, $user_roles) && $current_user->ID != $author_id) {
                                        $can_rate = true;
                                    }
                                }
                                ?>
                                <?php if ($can_rate): ?>
                                <button class="rate-agent-button" style="margin-bottom:10px;" onclick="document.getElementById('rate-agent-modal').classList.add('open');">
                                    <span class="dashicons dashicons-star-half"></span> Noter cet agent
                                </button>
                                <?php endif; ?>
                <h3 class="card-title">Contact Agent</h3>
                <?php if ($avg_rating > 0): ?>
                <div class="agent-rating-summary" style="margin-bottom:10px;">
                    <span class="agent-rating-stars" style="color:#f5b301;font-size:1.2em;">
                        <?php
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= floor($avg_rating)) {
                                echo '<span class="dashicons dashicons-star-filled"></span>';
                            } elseif ($i - $avg_rating < 1) {
                                echo '<span class="dashicons dashicons-star-half"></span>';
                            } else {
                                echo '<span class="dashicons dashicons-star-empty"></span>';
                            }
                        }
                        ?>
                    </span>
                    <span class="agent-rating-value" style="margin-left:6px;">
                        <?php echo esc_html($avg_rating); ?> / 5
                        (<?php echo esc_html($total_ratings); ?> avis)
                    </span>
                </div>
                <?php endif; ?>
                
                <div class="agent-info">
                    <div class="agent-avatar">
                        <?php echo get_avatar($author_id, 80); ?>
                    </div>
                    <div class="agent-details">
                        <h4 class="agent-name"><?php echo esc_html($author_name); ?></h4>
                        <p class="agent-role">Property Agent</p>
                    </div>
                </div>
                
                <button class="contact-agent-button" data-property-id="<?php echo $property_id; ?>" data-agent-id="<?php echo $author_id; ?>">
                    <span class="dashicons dashicons-phone"></span>
                    Show Contact Details
                </button>
                
                <div class="agent-contact-details hidden">
                    <?php if ($author_phone) : ?>
                        <div class="contact-item">
                            <span class="dashicons dashicons-phone"></span>
                            <a href="tel:<?php echo esc_attr($author_phone); ?>"><?php echo esc_html($author_phone); ?></a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="contact-item">
                        <span class="dashicons dashicons-email"></span>
                        <a href="mailto:<?php echo esc_attr($author_email); ?>"><?php echo esc_html($author_email); ?></a>
                    </div>
                </div>
                
                <div class="contact-form hidden">
                    <form class="quick-contact-form">
                        <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">
                        <input type="hidden" name="agent_id" value="<?php echo $author_id; ?>">
                        
                        <div class="form-group">
                            <input type="text" name="name" placeholder="Your Name" required>
                        </div>
                        
                        <div class="form-group">
                            <input type="email" name="email" placeholder="Your Email" required>
                        </div>
                        
                        <div class="form-group">
                            <input type="tel" name="phone" placeholder="Your Phone">
                        </div>
                        
                        <div class="form-group">
                            <textarea name="message" rows="4" placeholder="Your Message" required></textarea>
                        </div>
                        
                        <button type="submit" class="submit-button">
                            Send Message
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Property ID -->

            <!-- View Agent Details Modal -->
            <div id="view-agent-modal" class="malisafi-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Détails de l'agent</h3>
                        <button class="modal-close" onclick="document.getElementById('view-agent-modal').classList.remove('open');">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="agent-modal-info">
                            <div class="agent-avatar" style="margin-bottom:10px;">
                                <?php echo get_avatar($author_id, 80); ?>
                            </div>
                            <div class="agent-details">
                                <h4 class="agent-name" style="margin-bottom:4px;"><?php echo esc_html($author_name); ?></h4>
                                <p class="agent-role" style="margin-bottom:8px;">Property Agent</p>
                                <?php if ($avg_rating > 0): ?>
                                <div class="agent-rating-summary" style="margin-bottom:10px;">
                                    <span class="agent-rating-stars" style="color:#f5b301;font-size:1.2em;">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= floor($avg_rating)) {
                                                echo '<span class="dashicons dashicons-star-filled"></span>';
                                            } elseif ($i - $avg_rating < 1) {
                                                echo '<span class="dashicons dashicons-star-half"></span>';
                                            } else {
                                                echo '<span class="dashicons dashicons-star-empty"></span>';
                                            }
                                        }
                                        ?>
                                    </span>
                                    <span class="agent-rating-value" style="margin-left:6px;">
                                        <?php echo esc_html($avg_rating); ?> / 5
                                        (<?php echo esc_html($total_ratings); ?> avis)
                                    </span>
                                </div>
                                <?php endif; ?>
                                <div class="agent-contact-details">
                                    <?php if ($author_phone) : ?>
                                        <div class="contact-item">
                                            <span class="dashicons dashicons-phone"></span>
                                            <a href="tel:<?php echo esc_attr($author_phone); ?>"><?php echo esc_html($author_phone); ?></a>
                                        </div>
                                    <?php endif; ?>
                                    <div class="contact-item">
                                        <span class="dashicons dashicons-email"></span>
                                        <a href="mailto:<?php echo esc_attr($author_email); ?>"><?php echo esc_html($author_email); ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="property-meta-card">
                <div class="meta-item">
                    <span class="meta-label">Property ID</span>
                    <span class="meta-value">#<?php echo str_pad($property_id, 6, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Published</span>
                    <span class="meta-value"><?php echo get_the_date(); ?></span>
                </div>
            </div>
            
        </aside>
        
    </div>
    
</div>

<!-- Report Modal -->
<div id="report-modal" class="malisafi-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Report Property</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="report-form">
                <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">
                
                <div class="form-group">
                    <label>Reason for reporting</label>
                    <select name="report_reason" required>
                        <option value="">Select a reason...</option>
                        <option value="incorrect_info">Incorrect Information</option>
                        <option value="duplicate">Duplicate Listing</option>
                        <option value="scam">Suspected Scam</option>
                        <option value="sold">Already Sold/Rented</option>
                        <option value="inappropriate">Inappropriate Content</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Additional details (optional)</label>
                    <textarea name="report_details" rows="4" placeholder="Provide more information..."></textarea>
                </div>
                
                <?php if (is_user_logged_in()) : ?>
                    <input type="hidden" name="reporter_email" value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>">
                <?php else : ?>
                    <div class="form-group">
                        <label>Your email</label>
                        <input type="email" name="reporter_email" placeholder="your@email.com" required>
                    </div>
                <?php endif; ?>
                
                <div class="form-actions">
                    <button type="button" class="button-secondary modal-close">Cancel</button>
                    <button type="submit" class="button-primary">Submit Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
endwhile;

get_footer();
