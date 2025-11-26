<?php
/**
 * Main plugin class
 */
class Media_Bulk_Download {
    
    /**
     * Admin page instance
     */
    private $admin_page;
    
    /**
     * Download handler instance
     */
    private $download_handler;
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain for translations
        add_action('init', array($this, 'load_textdomain'));
        
        // Initialize components
        $this->admin_page = new MBD_Admin_Page();
        $this->download_handler = new MBD_Download_Handler();
        
        // Setup admin menu
        add_action('admin_menu', array($this->admin_page, 'add_admin_menu'));
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Setup AJAX handlers
        add_action('wp_ajax_mbd_get_months', array($this->admin_page, 'ajax_get_months'));
        add_action('wp_ajax_mbd_get_image_count', array($this->admin_page, 'ajax_get_image_count'));
        add_action('wp_ajax_mbd_download_images', array($this->download_handler, 'ajax_download_images'));
        
        // Add media library hover actions
        add_filter('media_row_actions', array($this, 'add_media_row_actions'), 10, 2);
        
        // Enqueue media library scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_media_library_assets'));
        
        // Add plugin action links
        add_filter('plugin_action_links_' . MBD_PLUGIN_BASENAME, array($this, 'add_plugin_action_links'));
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('media-bulk-download', false, dirname(MBD_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin page
        if ($hook !== 'media_page_media-bulk-download') {
            return;
        }
        
        // Enqueue scripts
        wp_enqueue_script(
            'mbd-admin-script',
            MBD_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            MBD_PLUGIN_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('mbd-admin-script', 'mbd_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mbd_ajax_nonce'),
            'downloading_text' => __('Creating zip file...', 'media-bulk-download'),
            'download_complete' => __('Download complete!', 'media-bulk-download'),
            'download_error' => __('An error occurred. Please try again.', 'media-bulk-download'),
            'no_images_found' => __('No images found for the selected criteria.', 'media-bulk-download')
        ));
        
        // Enqueue styles
        wp_enqueue_style(
            'mbd-admin-style',
            MBD_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MBD_PLUGIN_VERSION
        );
    }
    
    /**
     * Enqueue media library assets
     */
    public function enqueue_media_library_assets($hook) {
        // Only load on upload.php (Media Library)
        if ($hook !== 'upload.php') {
            return;
        }
        
        // Enqueue media library script
        wp_enqueue_script(
            'mbd-media-library',
            MBD_PLUGIN_URL . 'assets/js/media-library.js',
            array('jquery'),
            MBD_PLUGIN_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('mbd-media-library', 'mbd_media', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mbd_ajax_nonce'),
            'download_text' => __('Download Month', 'media-bulk-download')
        ));
    }
    
    /**
     * Add download action to media row actions
     */
    public function add_media_row_actions($actions, $post) {
        // Only add for images
        if (strpos($post->post_mime_type, 'image/') !== 0) {
            return $actions;
        }
        
        // Get upload date
        $upload_date = get_the_date('Y/m', $post->ID);
        if ($upload_date) {
            list($year, $month) = explode('/', $upload_date);
            
            $download_url = wp_nonce_url(
                admin_url('admin-ajax.php?action=mbd_download_images&year=' . $year . '&month=' . $month),
                'mbd_ajax_nonce',
                'nonce'
            );
            
            $actions['mbd_download'] = sprintf(
                '<a href="%s" class="mbd-download-month" data-year="%s" data-month="%s">%s</a>',
                esc_url($download_url),
                esc_attr($year),
                esc_attr($month),
                esc_html__('Download Month', 'media-bulk-download')
            );
        }
        
        return $actions;
    }
    
    /**
     * Add plugin action links on plugins page
     */
    public function add_plugin_action_links($links) {
        $download_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('upload.php?page=media-bulk-download')),
            esc_html__('Download Media', 'media-bulk-download')
        );
        
        array_unshift($links, $download_link);
        return $links;
    }
}