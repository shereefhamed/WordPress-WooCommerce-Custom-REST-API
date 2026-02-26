<?php
if (!class_exists('Wordpress_API_Payment_methods')) {
    class Wordpress_API_Payment_methods
    {
        public function __construct()
        {
            add_action('rest_api_init', array($this, 'register_payment_methods_rest_api'));
        }

        public function register_payment_methods_rest_api()
        {
            register_rest_route(
                'custom/v1',
                '/payment-methods',
                [
                    'methods' => 'GET',
                    'callback' => array($this, 'get_payment_methods'),
                    'permission_callback' => '__return_true',
                ]
            );
        }

        public function get_payment_methods()
        {
            if (!class_exists('WooCommerce')) {
                return new WP_REST_Response(
                    [
                        'success' => false,
                        'message' => 'WooCommerce not active'
                    ],
                    400
                );
            }

            $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();

            $methods = [];

            foreach ($available_gateways as $gateway) {

                $methods[] = [
                    'id' => $gateway->id,
                    'title' => $gateway->get_title(),
                    'description' => $gateway->get_description(),
                    'enabled' => $gateway->enabled,
                ];
            }

            return new WP_REST_Response(
                [
                    'success' => true,
                    'payment_methods' => $methods
                ],
                200
            );
        }
    }

    $payment_methods = new Wordpress_API_Payment_methods();
}