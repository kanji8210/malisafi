<?php
/**
 * Unified Admin Property Form Template
 * Redirects to the modern Wizard interface
 * 
 * @package MalisafiMLS
 */
if (!defined('ABSPATH')) exit;

// Ensure scripts are enqueued (normally handled by the controller, but safe to call here)
\MalisafiMLS\Property_Submission::enqueue_assets();

echo '<div class="wrap malisafi-unified-wizard-admin">';
echo \MalisafiMLS\Property_Submission::render_submission_form();
echo '</div>';

// Simple fix for admin menu styles to accommodate the wizard
?>
<style>
.malisafi-unified-wizard-admin .malisafi-property-wizard {
    margin-top: 20px;
    background: #fff;
    padding: 2px; /* Small padding to avoid border collisions in admin */
}
#adminmenuwrap {
    z-index: 10001; /* Ensure admin menu stays on top if wizard uses high z-index */
}
</style>    });

    populateSubcounties($('#county').val(), '<?php echo esc_js($property_meta['subcounty'] ?? ''); ?>');

    $('#listing_type').on('change', toggleSaleLeaseDetails);
    toggleSaleLeaseDetails();
});

function malisafiGetLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            var lat = position.coords.latitude.toFixed(6);
            var lng = position.coords.longitude.toFixed(6);
            document.getElementById('property_gps').value = lat + ', ' + lng;
        }, function(error) {
            alert('<?php _e('Unable to retrieve your location.', 'malisafi-mls'); ?>');
        });
    } else {
        alert('<?php _e('Geolocation is not supported by your browser.', 'malisafi-mls'); ?>');
    }
}
</script>
