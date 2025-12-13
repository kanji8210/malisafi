<?php
/**
 * Import/Export page template
 *
 * @package MalisafiMLS
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="malisafi-import-export-wrapper">
        <div class="malisafi-section">
            <h2><?php _e('Import Properties', 'malisafi-mls'); ?></h2>
            <p><?php _e('Import properties from CSV file. The CSV should include columns for property details like title, description, price, bedrooms, bathrooms, etc.', 'malisafi-mls'); ?></p>
            
            <form method="post" enctype="multipart/form-data" action="">
                <?php wp_nonce_field('malisafi_import_properties', 'malisafi_import_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="import_file"><?php _e('CSV File', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <input type="file" id="import_file" name="import_file" accept=".csv" required>
                            <p class="description"><?php _e('Select a CSV file to import properties.', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="import_properties" class="button button-primary" value="<?php _e('Import Properties', 'malisafi-mls'); ?>">
                </p>
            </form>
            
            <p>
                <a href="<?php echo MALISAFI_MLS_URL . 'assets/sample-import.csv'; ?>" class="button" download>
                    <?php _e('Download Sample CSV', 'malisafi-mls'); ?>
                </a>
            </p>
        </div>
        
        <hr>
        
        <div class="malisafi-section">
            <h2><?php _e('Export Properties', 'malisafi-mls'); ?></h2>
            <p><?php _e('Export all properties to a CSV file for backup or migration purposes.', 'malisafi-mls'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('malisafi_export_properties', 'malisafi_export_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Export Options', 'malisafi-mls'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="export_images" value="1" checked>
                                <?php _e('Include image URLs', 'malisafi-mls'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" name="export_meta" value="1" checked>
                                <?php _e('Include all metadata', 'malisafi-mls'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="export_properties" class="button button-primary" value="<?php _e('Export Properties', 'malisafi-mls'); ?>">
                </p>
            </form>
        </div>
    </div>
</div>

<style>
.malisafi-import-export-wrapper {
    max-width: 800px;
}

.malisafi-section {
    margin: 20px 0;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.malisafi-section h2 {
    margin-top: 0;
}
</style>
