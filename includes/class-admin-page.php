<?php
/**
 * Admin page class
 */
class MBD_Admin_Page {
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'upload.php',
            __('Media Bulk Download', 'media-bulk-download'),
            __('Bulk Download', 'media-bulk-download'),
            'manage_options',
            'media-bulk-download',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        // Get available years from uploads directory
        $years = $this->get_available_years();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Media Bulk Download', 'media-bulk-download'); ?></h1>
            
            <div class="mbd-container">
                <div class="mbd-description">
                    <p><?php echo esc_html__('Select a year and month to download original images from your uploads folder. All WordPress-generated image sizes will be excluded.', 'media-bulk-download'); ?></p>
                </div>
                
                <form id="mbd-download-form" method="post">
                    <?php wp_nonce_field('mbd_download_nonce', 'mbd_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="mbd-year"><?php echo esc_html__('Year', 'media-bulk-download'); ?></label>
                            </th>
                            <td>
                                <select id="mbd-year" name="year">
                                    <option value=""><?php echo esc_html__('All Years', 'media-bulk-download'); ?></option>
                                    <?php foreach ($years as $year_data) : ?>
                                        <option value="<?php echo esc_attr($year_data['year']); ?>">
                                            <?php echo esc_html($year_data['year']); ?> 
                                            <?php if ($year_data['count'] > 0) : ?>
                                                (<?php echo esc_html($year_data['count']); ?> <?php echo esc_html(_n('image', 'images', $year_data['count'], 'media-bulk-download')); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="mbd-month"><?php echo esc_html__('Month', 'media-bulk-download'); ?></label>
                            </th>
                            <td>
                                <select id="mbd-month" name="month" disabled>
                                    <option value=""><?php echo esc_html__('All Months', 'media-bulk-download'); ?></option>
                                </select>
                                <p class="description"><?php echo esc_html__('Select a year first to load available months', 'media-bulk-download'); ?></p>
                                <p id="mbd-image-count" class="description" style="display: none;"></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary" id="mbd-download-btn">
                            <?php echo esc_html__('Download Images', 'media-bulk-download'); ?>
                        </button>
                        <span class="spinner"></span>
                    </p>
                </form>
                
                <div id="mbd-message" class="notice" style="display: none;"></div>
                
                <div class="mbd-info">
                    <h3><?php echo esc_html__('Information', 'media-bulk-download'); ?></h3>
                    <ul>
                        <li><?php echo esc_html__('Only original uploaded images will be included in the download', 'media-bulk-download'); ?></li>
                        <li><?php echo esc_html__('WordPress-generated thumbnails and resized images will be excluded', 'media-bulk-download'); ?></li>
                        <li><?php echo esc_html__('The download will be provided as a ZIP file', 'media-bulk-download'); ?></li>
                        <li><?php echo esc_html__('Large selections may take some time to process', 'media-bulk-download'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get available years from uploads directory with image counts
     */
    private function get_available_years() {
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'];
        $years = array();
        
        if (is_dir($base_dir)) {
            $items = scandir($base_dir);
            foreach ($items as $item) {
                if (preg_match('/^\d{4}$/', $item) && is_dir($base_dir . '/' . $item)) {
                    // Get image count for this year
                    $count = $this->get_image_count($item);
                    $years[] = array(
                        'year' => $item,
                        'count' => $count
                    );
                }
            }
        }
        
        // Sort by year descending
        usort($years, function($a, $b) {
            return $b['year'] - $a['year'];
        });
        
        return $years;
    }
    
    /**
     * AJAX handler to get months for a specific year
     */
    public function ajax_get_months() {
        // Verify nonce
        if (!check_ajax_referer('mbd_ajax_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        $year = isset($_POST['year']) ? sanitize_text_field($_POST['year']) : '';
        
        if (empty($year)) {
            wp_send_json_error(__('Invalid year', 'media-bulk-download'));
        }
        
        $months = $this->get_available_months($year);
        wp_send_json_success($months);
    }
    
    /**
     * Get available months for a specific year with image counts
     */
    private function get_available_months($year) {
        $upload_dir = wp_upload_dir();
        $year_dir = $upload_dir['basedir'] . '/' . $year;
        $months = array();
        
        if (is_dir($year_dir)) {
            $items = scandir($year_dir);
            foreach ($items as $item) {
                if (preg_match('/^\d{2}$/', $item) && is_dir($year_dir . '/' . $item)) {
                    $month_name = date('F', mktime(0, 0, 0, intval($item), 1));
                    $count = $this->get_image_count($year, $item);
                    
                    $label = $month_name . ' (' . $item . ')';
                    if ($count > 0) {
                        $label .= ' - ' . $count . ' ' . _n('image', 'images', $count, 'media-bulk-download');
                    }
                    
                    $months[] = array(
                        'value' => $item,
                        'label' => $label,
                        'count' => $count
                    );
                }
            }
        }
        
        return $months;
    }
    
    /**
     * AJAX handler to get image count for a specific year/month
     */
    public function ajax_get_image_count() {
        // Verify nonce
        if (!check_ajax_referer('mbd_ajax_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        $year = isset($_POST['year']) ? sanitize_text_field($_POST['year']) : '';
        $month = isset($_POST['month']) ? sanitize_text_field($_POST['month']) : '';
        
        $count = $this->get_image_count($year, $month);
        wp_send_json_success($count);
    }
    
    /**
     * Get count of original images for a specific year/month using database query
     */
    private function get_image_count($year = '', $month = '') {
        global $wpdb;
        
        // Base query for image attachments
        $query = "
            SELECT COUNT(*) 
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'attachment'
            AND p.post_mime_type LIKE 'image/%'
        ";
        
        $query_params = array();
        
        // Add date filtering
        if (!empty($year)) {
            if (!empty($month)) {
                // Specific year and month
                $query .= " AND YEAR(p.post_date) = %d AND MONTH(p.post_date) = %d";
                $query_params[] = intval($year);
                $query_params[] = intval($month);
            } else {
                // Specific year only
                $query .= " AND YEAR(p.post_date) = %d";
                $query_params[] = intval($year);
            }
        }
        
        // Execute query
        if (!empty($query_params)) {
            $count = $wpdb->get_var($wpdb->prepare($query, $query_params));
        } else {
            $count = $wpdb->get_var($query);
        }
        
        return intval($count);
    }
}