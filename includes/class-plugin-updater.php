<?php
/**
 * Plugin Updater Class
 *
 * Enables automatic updates from the Kinda Useful Plugins repository.
 */

if (!class_exists('Kinda_Useful_Plugin_Updater')) {

    class Kinda_Useful_Plugin_Updater {

        private $api_url;
        private $plugin_file;
        private $plugin_slug;
        private $plugin_data;
        private $cache_key;
        private $cache_duration = 43200; // 12 hours

        public function __construct($api_url, $plugin_file, $plugin_slug) {
            $this->api_url = rtrim($api_url, '/');
            $this->plugin_file = $plugin_file;
            $this->plugin_slug = $plugin_slug;
            $this->cache_key = 'kup_update_' . md5($plugin_slug);

            add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
            add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
            add_action('upgrader_process_complete', [$this, 'clear_cache'], 10, 2);
        }

        private function get_plugin_data() {
            if (empty($this->plugin_data)) {
                if (!function_exists('get_plugin_data')) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                }
                $this->plugin_data = get_plugin_data($this->plugin_file);
            }
            return $this->plugin_data;
        }

        public function check_update($transient) {
            if (empty($transient->checked)) {
                return $transient;
            }

            $remote = $this->get_remote_version();

            if (!$remote) {
                return $transient;
            }

            $plugin_data = $this->get_plugin_data();
            $plugin_basename = plugin_basename($this->plugin_file);

            if (version_compare($plugin_data['Version'], $remote->version, '<')) {
                $transient->response[$plugin_basename] = (object) [
                    'slug' => $this->plugin_slug,
                    'plugin' => $plugin_basename,
                    'new_version' => $remote->version,
                    'url' => $remote->url ?? $this->api_url,
                    'package' => $remote->package,
                    'requires' => $remote->requires ?? '',
                    'requires_php' => $remote->requires_php ?? '',
                    'tested' => $remote->tested ?? '',
                    'icons' => [],
                    'banners' => [],
                ];
            } else {
                $transient->no_update[$plugin_basename] = (object) [
                    'slug' => $this->plugin_slug,
                    'plugin' => $plugin_basename,
                    'new_version' => $remote->version,
                    'url' => $remote->url ?? $this->api_url,
                    'package' => $remote->package,
                ];
            }

            return $transient;
        }

        private function get_remote_version() {
            $cached = get_transient($this->cache_key);

            if ($cached !== false) {
                return $cached;
            }

            $response = wp_remote_get(
                $this->api_url . '/update.php?action=check_update&slug=' . urlencode($this->plugin_slug),
                [
                    'timeout' => 10,
                    'headers' => ['Accept' => 'application/json'],
                ]
            );

            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                return false;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);

            if (empty($data) || isset($data->error)) {
                return false;
            }

            set_transient($this->cache_key, $data, $this->cache_duration);

            return $data;
        }

        public function plugin_info($result, $action, $args) {
            if ($action !== 'plugin_information') {
                return $result;
            }

            if (!isset($args->slug) || $args->slug !== $this->plugin_slug) {
                return $result;
            }

            $response = wp_remote_get(
                $this->api_url . '/update.php?action=plugin_information&slug=' . urlencode($this->plugin_slug),
                [
                    'timeout' => 10,
                    'headers' => ['Accept' => 'application/json'],
                ]
            );

            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                return $result;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);

            if (empty($data) || isset($data->error)) {
                return $result;
            }

            return (object) [
                'name' => $data->name,
                'slug' => $data->slug,
                'version' => $data->version,
                'author' => $data->author,
                'author_profile' => $data->author_profile ?? '',
                'requires' => $data->requires ?? '',
                'tested' => $data->tested ?? '',
                'requires_php' => $data->requires_php ?? '',
                'download_link' => $data->download_link,
                'trunk' => $data->trunk ?? $data->download_link,
                'last_updated' => $data->last_updated ?? '',
                'sections' => (array) ($data->sections ?? []),
                'banners' => (array) ($data->banners ?? []),
                'icons' => (array) ($data->icons ?? []),
                'homepage' => $data->homepage ?? $this->api_url,
            ];
        }

        public function clear_cache($upgrader, $options) {
            if ($options['action'] === 'update' && $options['type'] === 'plugin') {
                delete_transient($this->cache_key);
            }
        }
    }
}
