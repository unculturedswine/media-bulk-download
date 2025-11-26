<?php
/**
 * Download handler class
 */
class MBD_Download_Handler {
    
    /**
     * Common image extensions
     */
    private $image_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff', 'tif');
    
    /**
     * WordPress image sizes pattern (to exclude)
     */
    private $size_pattern = '/-(150x150|300x\d+|768x\d+|1024x\d+|\d+x\d+)$/';
    
    /**
     * AJAX handler for downloading images
     */
    public function ajax_download_images() {
        // Verify nonce
        if (!check_ajax_referer('mbd_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'media-bulk-download'));
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'media-bulk-download'));
        }
        
        $year = isset($_POST['year']) ? sanitize_text_field($_POST['year']) : '';
        $month = isset($_POST['month']) ? sanitize_text_field($_POST['month']) : '';
        
        try {
            $zip_path = $this->create_zip($year, $month);
            
            if (!$zip_path) {
                wp_send_json_error(__('No images found for the selected criteria', 'media-bulk-download'));
            }
            
            $this->download_zip($zip_path, $year, $month);
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Create ZIP file with images
     */
    private function create_zip($year = '', $month = '') {
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'];
        
        // Determine source directories
        $source_dirs = array();
        
        if (!empty($year)) {
            $year_dir = $base_dir . '/' . $year;
            if (!empty($month)) {
                // Specific year and month
                $month_dir = $year_dir . '/' . sprintf('%02d', intval($month));
                if (is_dir($month_dir)) {
                    $source_dirs[] = $month_dir;
                }
            } else {
                // All months in the year
                if (is_dir($year_dir)) {
                    $months = scandir($year_dir);
                    foreach ($months as $m) {
                        if (preg_match('/^\d{2}$/', $m)) {
                            $month_dir = $year_dir . '/' . $m;
                            if (is_dir($month_dir)) {
                                $source_dirs[] = $month_dir;
                            }
                        }
                    }
                }
            }
        } else {
            // All years and months
            $years = scandir($base_dir);
            foreach ($years as $y) {
                if (preg_match('/^\d{4}$/', $y)) {
                    $year_dir = $base_dir . '/' . $y;
                    if (is_dir($year_dir)) {
                        $months = scandir($year_dir);
                        foreach ($months as $m) {
                            if (preg_match('/^\d{2}$/', $m)) {
                                $month_dir = $year_dir . '/' . $m;
                                if (is_dir($month_dir)) {
                                    $source_dirs[] = $month_dir;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        if (empty($source_dirs)) {
            return false;
        }
        
        // Get original images from source directories
        $images = $this->get_original_images($source_dirs);
        
        if (empty($images)) {
            return false;
        }
        
        // Create temporary directory
        $temp_dir = $upload_dir['basedir'] . '/mbd-temp';
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        // Create ZIP file
        $zip_filename = 'media-download-' . date('Y-m-d-H-i-s') . '.zip';
        $zip_path = $temp_dir . '/' . $zip_filename;
        
        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE) !== TRUE) {
            throw new Exception(__('Could not create ZIP file', 'media-bulk-download'));
        }
        
        // Add images to ZIP
        foreach ($images as $image_path) {
            if (file_exists($image_path)) {
                // Create relative path for ZIP entry
                $relative_path = str_replace($base_dir . '/', '', $image_path);
                $zip->addFile($image_path, $relative_path);
            }
        }
        
        $zip->close();
        
        return $zip_path;
    }
    
    /**
     * Get original images from directories (excludes WordPress generated sizes)
     */
    private function get_original_images($directories) {
        $images = array();
        
        foreach ($directories as $dir) {
            $files = $this->scan_directory_recursive($dir);
            
            foreach ($files as $file) {
                if ($this->is_original_image($file)) {
                    $images[] = $file;
                }
            }
        }
        
        return $images;
    }
    
    /**
     * Recursively scan directory for files
     */
    private function scan_directory_recursive($dir) {
        $files = array();
        
        if (!is_dir($dir)) {
            return $files;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    /**
     * Check if file is an original image (not a WordPress generated size)
     */
    private function is_original_image($file_path) {
        $file_info = pathinfo($file_path);
        
        // Check if it's an image file
        if (!isset($file_info['extension'])) {
            return false;
        }
        
        $extension = strtolower($file_info['extension']);
        if (!in_array($extension, $this->image_extensions)) {
            return false;
        }
        
        // Check if it's a WordPress generated size
        $filename = $file_info['filename'];
        if (preg_match($this->size_pattern, $filename)) {
            return false;
        }
        
        // Additional check for common WordPress size patterns
        if (preg_match('/-(thumb|thumbnail|medium|large|scaled)$/', $filename)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Download ZIP file
     */
    private function download_zip($zip_path, $year = '', $month = '') {
        if (!file_exists($zip_path)) {
            wp_send_json_error(__('ZIP file not found', 'media-bulk-download'));
        }
        
        // Generate filename
        $filename_parts = array('media-download');
        if ($year) {
            $filename_parts[] = $year;
            if ($month) {
                $filename_parts[] = sprintf('%02d', intval($month));
            }
        }
        $filename = implode('-', $filename_parts) . '.zip';
        
        // Set headers
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($zip_path));
        header('Cache-Control: private, no-transform, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output file
        readfile($zip_path);
        
        // Clean up
        unlink($zip_path);
        
        exit;
    }
}