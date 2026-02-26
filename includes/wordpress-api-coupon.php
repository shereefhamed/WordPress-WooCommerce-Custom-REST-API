<?php
if (!class_exists('Wordpress_API_Coupon')) {
    require_once(WORDPRESS_API_PATH . 'helper/cart-helper.php');
    require_once(WORDPRESS_API_PATH . 'helper/user-permission.php');
    class Wordpress_API_Coupon
    {
        use Cart_Helper, UserPermission;
        public function __construct()
        {
            add_action('rest_api_init', array($this, 'register_apply_coupon_rest_api'));
            add_action('rest_api_init', array($this, 'register_remove_coupon_rest_api'));
        }

        public function register_apply_coupon_rest_api()
        {
            register_rest_route(
                'custom/v1',
                '/apply-coupon',
                [
                    'methods' => 'POST',
                    'callback' => array($this, 'apply_coupon_to_cart'),
                    'permission_callback' => array($this, 'is_user_logged_in')
                ]
            );
        }

        public function register_remove_coupon_rest_api()
        {
            register_rest_route(
                'custom/v1',
                '/remove-coupon',
                [
                    'methods' => 'POST',
                    'callback' => array($this, 'remove_coupon_from_cart'),
                    'permission_callback' => array($this, 'is_user_logged_in')
                ]
            );
        }

        public function apply_coupon_to_cart(WP_REST_Request $request)
        {
            $this->init_cart();

            $coupon_code = sanitize_text_field($request->get_param('coupon'));

            if (empty($coupon_code)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Coupon code is required'
                ], 400);
            }


            $coupon = new WC_Coupon($coupon_code);
            if (!$coupon->get_id()) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Coupon does not exist'
                ], 400);

            }

            $discounts = new WC_Discounts(WC()->cart);
            $is_valid = $discounts->is_coupon_valid($coupon);

            if (is_wp_error($is_valid)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => $is_valid->get_error_message(),
                ], 400);
            }



            // Apply coupon
            $applied = WC()->cart->apply_coupon($coupon_code);

            if (is_wp_error($applied)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => $applied->get_error_message()
                ], 400);
            }

            WC()->cart->calculate_totals();

            return new WP_REST_Response([
                'success' => true,
                'message' => 'Coupon applied successfully',
                'cart_total' => WC()->cart->get_total(),
                'discount_total' => WC()->cart->get_discount_total(),
                'currency' => get_woocommerce_currency(),
                'total' => (float) WC()->cart->get_total('edit'),
                'subtotal' => (float) WC()->cart->get_subtotal(),
                'applied_coupons' => WC()->cart->get_applied_coupons(),
            ]);
        }

        public function remove_coupon_from_cart(WP_REST_Request $request)
        {
            $this->init_cart();

            $coupon_code = sanitize_text_field($request->get_param('coupon'));

            if (empty($coupon_code)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Coupon code is required'
                ], 400);
            }

            // Check if coupon is applied
            if (!WC()->cart->has_discount($coupon_code)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Coupon is not applied'
                ], 400);
            }

            // Remove coupon
            WC()->cart->remove_coupon($coupon_code);
            WC()->cart->calculate_totals();

            return new WP_REST_Response([
                'success' => true,
                'message' => 'Coupon removed successfully',
                'applied_coupons' => WC()->cart->get_applied_coupons(),
                'total' => (float) WC()->cart->get_total('edit'),
                'discount_total' => (float) WC()->cart->get_discount_total(),
                'currency' => get_woocommerce_currency(),
                'subtotal' => (float) WC()->cart->get_subtotal(),

            ]);
        }



        public function check_coupon_exists($coupon_code)
        {
            $coupon = new WC_Coupon($coupon_code);

            if ($coupon->get_id()) {
                return true;
            }

            return false;
        }
    }

    $wordpress_api_coupon = new Wordpress_API_Coupon();
}