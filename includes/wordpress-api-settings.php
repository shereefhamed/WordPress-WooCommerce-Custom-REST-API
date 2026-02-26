<?php
if (!class_exists('Wordpress_API_Settings')) {
    class Wordpress_API_Settings
    {
        public function __construct()
        {
            add_action('admin_menu', array($this, 'add_setting_page_menu'));
            add_filter('rest_authentication_errors', array($this, 'check_is_wordpress_api_enbaled'));
            add_filter('plugin_action_links_' . plugin_basename(WORDPRESS_API_FILE), array($this, 'wordpress_api_settings_link'));
            register_activation_hook(WORDPRESS_API_FILE, array('Wordpress_API_Settings', 'activate'));
            register_deactivation_hook(WORDPRESS_API_FILE, array('Wordpress_API_Settings', 'deactivate'));
            register_uninstall_hook(WORDPRESS_API_FILE, array('Wordpress_API_Settings', 'uninstall'));
        }

        public function add_setting_page_menu()
        {
            add_menu_page(
                'WordPress API Settings',
                'WordPress API Settings',
                'manage_options',
                'api-settings',
                array($this, 'create_setting_page')
            );
        }

        public function create_setting_page()
        {
            if (!current_user_can('manage_options')) {
                return;
            }
            include(WORDPRESS_API_PATH . 'templates/setting-page.php');
        }

        public function check_is_wordpress_api_enbaled($errors)
        {

            if (!empty($errors)) {
                return $errors;
            }

            $enabled = get_option('wordpress_api_enabled', false);

            $request = $_SERVER['REQUEST_URI'];

            if (strpos($request, '/wp-json/custom/v1/') !== false) {
                if (!$enabled) {

                    return new WP_Error(
                        'api_disabled',
                        'Custom API is disabled.',
                        ['status' => 403]
                    );
                }
            }

            return $errors;
        }

        public function wordpress_api_settings_link($links)
        {
            $settings_link = '<a href="' . admin_url('admin.php?page=api-settings') . '">Settings</a>';

            array_unshift($links, $settings_link);

            return $links;
        }

        static public function activate()
        {
            update_option('wordpress_api_enabled', true);
        }

        static public function deactivate()
        {
            update_option('wordpress_api_enabled', false);
        }

        static public function uninstall()
        {
            delete_option('wordpress_api_enabled');
        }
    }

    $wordpress_api_settings = new Wordpress_API_Settings();
}