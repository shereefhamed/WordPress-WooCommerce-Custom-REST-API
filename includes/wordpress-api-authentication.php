<?php
if (!class_exists('WordPress_API_Authentication')) {
    class WordPress_API_Authentication
    {
        public function __construct()
        {
            add_action('rest_api_init', array($this, 'register_signup_rest_api'));
        }

        public function register_signup_rest_api()
        {
            register_rest_route(
                'custom/v1',
                '/register',
                [
                    'methods' => 'POST',
                    'callback' => array($this, 'custom_register_user'),
                    'permission_callback' => '__return_true',
                ]
            );
        }

        public function custom_register_user($request)
        {
            $username = sanitize_text_field($request['username']);
            $email = sanitize_email($request['email']);
            $password = $request['password'];

            if (username_exists($username) || email_exists($email)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'User already exists'
                ], 400);
            }

            $user_id = wp_create_user($username, $password, $email);

            if (is_wp_error($user_id)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => $user_id->get_error_message()
                ], 400);
            }

            $user = new WP_User($user_id);
            $user->set_role('customer');

            return [
                'success' => true,
                'user_id' => $user_id,
            ];
        }
    }

    $wordpress_api_authentication = new WordPress_API_Authentication();
}
