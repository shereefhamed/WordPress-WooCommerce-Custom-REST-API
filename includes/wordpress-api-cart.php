<?php
require_once(WORDPRESS_API_PATH . 'helper/user-permission.php');

if (!class_exists('Wordpress_Api_Cart')) {
    class Wordpress_Api_Cart
    {
        use UserPermission;
        public function __construct()
        {
            add_action('rest_api_init', array($this, 'register_add_to_cart_rest_api'));
            add_action('rest_api_init', array($this, 'register_cart_rest_api'));
            add_action('rest_api_init', array($this, 'register_update_cart_rest_api'));
            add_action('rest_api_init', array($this, 'register_increase_cart_item_rest_api'));
            add_action('rest_api_init', array($this, 'register_decrease_cart_item_rest_api'));
        }

        public function register_add_to_cart_rest_api()
        {
            register_rest_route(
                'custom/v1',
                '/add-to-cart',
                [
                    'methods' => 'POST',
                    'callback' => array($this, 'add_product_to_cart'),
                    'permission_callback' => array($this, 'is_user_logged_in'),
                ]
            );
        }

        public function register_cart_rest_api()
        {
            register_rest_route(
                'custom/v1',
                '/cart',
                [
                    'methods' => 'GET',
                    'callback' => array($this, 'get_cart'),
                    'permission_callback' => array($this, 'is_user_logged_in'),
                ]
            );
        }

        public function register_update_cart_rest_api()
        {
            register_rest_route(
                'custom/v1',
                '/cart/update',
                [
                    'methods' => 'POST',
                    'callback' => array($this, 'update_cart_item'),
                    'permission_callback' => array($this, 'is_user_logged_in'),
                ]
            );
        }

        public function register_increase_cart_item_rest_api()
        {
            register_rest_route(
                'custom/v1',
                '/cart/increase',
                [
                    'methods' => 'POST',
                    'callback' => array($this, 'increate_cart_item'),
                    'permission_callback' => array($this, 'is_user_logged_in'),
                ]
            );
        }

        public function register_decrease_cart_item_rest_api()
        {
            register_rest_route(
                'custom/v1',
                '/cart/decrease',
                [
                    'methods' => 'POST',
                    'callback' => array($this, 'decreate_cart_item'),
                    'permission_callback' => array($this, 'is_user_logged_in'),
                ]
            );
        }

        public function add_product_to_cart($request)
        {

            $this->init_cart();

            $product_id = absint($request->get_param('product_id'));
            $quantity = absint($request->get_param('quantity'));
            $variation_id = absint($request->get_param('variation_id'));
            $variation = $request->get_param('variation') ?? [];

            $product = wc_get_product($product_id);

            if (!$product) {
                return new WP_REST_Response(
                    [
                        'success' => false,
                        'message' => 'Product not found.'
                    ],
                    404
                );
            }

            if (!$product->is_purchasable()) {
                return new WP_REST_Response(
                    [
                        'success' => false,
                        'message' => 'This product cannot be purchased.'
                    ],
                    404
                );


            }

            if (!$product->is_in_stock()) {
                return new WP_REST_Response(
                    [
                        'success' => false,
                        'message' => 'This product is out of stock.'
                    ],
                    404
                );


            }

            if ($product->is_type('variable')) {
                //$variations = $product->get_available_variations();
                //return $variations;


                $product_variation = wc_get_product($variation_id);
                if (!$product_variation || $product_variation->get_parent_id() !== $product_id) {
                    return new WP_REST_Response(
                        ['success' => false, 'message' => 'Variation is not valid for this product.'],
                        400,
                    );
                }

                if (!$product_variation->is_purchasable() || !$product_variation->is_in_stock()) {
                    return new WP_REST_Response(['success' => false, 'message' => 'This version is not available.'], 400);
                }

            }

            $cart_item_key = WC()->cart->add_to_cart(
                $product_id,
                $quantity,
                $variation_id,
                $variation
            );

            if (!$cart_item_key) {
                return new WP_REST_Response(
                    ['success' => false, 'message' => 'Could not add product to cart.'],
                    500
                );

            }

            WC()->cart->calculate_totals();
            WC()->cart->set_session();

            return new WP_REST_Response(
                [
                    'success' => true,
                    'cart_item_key' => $cart_item_key,
                    'message' => sprintf('%s added to cart.', $product->get_name()),
                    'cart_count' => WC()->cart->get_cart_contents_count(),
                    'cart_total' => WC()->cart->get_cart_total(),
                ],
                200
            );
        }

        public function get_cart(WP_REST_Request $request)
        {

            $this->init_cart();

            $cart_items = [];
            $cart = WC()->cart->get_cart();

            foreach ($cart as $cart_item_key => $cart_item) {

                $product = $cart_item['data'];

                $cart_items[] = [
                    'cart_item_key' => $cart_item_key,
                    'product_id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'quantity' => $cart_item['quantity'],
                    'price' => $product->get_price(),
                    'subtotal' => wc_format_decimal($cart_item['line_subtotal'], 2),
                    'total' => wc_format_decimal($cart_item['line_total'], 2),
                    'image' => wp_get_attachment_url($product->get_image_id()),
                ];
            }

            return new WP_REST_Response([
                'items' => $cart_items,
                'total_items' => WC()->cart->get_cart_contents_count(),
                'subtotal' => WC()->cart->get_subtotal(),
                'discount' => WC()->cart->get_discount_total(),
                'total' => WC()->cart->get_total("edit"),
                'currency' => get_woocommerce_currency(),
            ], 200);
        }


        public function update_cart_item(WP_REST_Request $request)
        {
            $this->init_cart();

            $cart_item_key = $request->get_param('cart_item_key');
            $quantity = intval($request->get_param('quantity'));

            if (!$cart_item_key) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Cart item key is required'
                ], 400);
            }

            if ($quantity <= 0) {
                WC()->cart->remove_cart_item($cart_item_key);
            } else {
                WC()->cart->set_quantity($cart_item_key, $quantity, true);

            }

            WC()->cart->calculate_totals();
            WC()->cart->set_session();

            return new WP_REST_Response([
                'success' => true,
                'cart_count' => WC()->cart->get_cart_contents_count(),
                'cart' => WC()->cart->get_cart()
            ], 200);
        }

        public function increate_cart_item(WP_REST_Request $request)
        {
            $this->init_cart();

            $cart_item_key = $request->get_param('cart_item_key');


            if (!$cart_item_key) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Cart item key is required'
                ], 400);
            }

            $cart = WC()->cart->get_cart();
            $cart_item = $cart[$cart_item_key];
            $cart_item_quantity = $cart_item['quantity'];

            WC()->cart->set_quantity($cart_item_key, $cart_item_quantity + 1, true);

            WC()->cart->calculate_totals();
            WC()->cart->set_session();

            return new WP_REST_Response([
                'success' => true,
                'cart_count' => WC()->cart->get_cart_contents_count(),
                'cart' => WC()->cart->get_cart()
            ], 200);
        }

        public function decreate_cart_item(WP_REST_Request $request)
        {
            $this->init_cart();

            $cart_item_key = $request->get_param('cart_item_key');


            if (!$cart_item_key) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Cart item key is required'
                ], 400);
            }

            $cart = WC()->cart->get_cart();
            $cart_item = $cart[$cart_item_key];
            $cart_item_quantity = $cart_item['quantity'];

            if ($cart_item_quantity === 1) {
                WC()->cart->remove_cart_item($cart_item_key);
            } else {

                WC()->cart->set_quantity($cart_item_key, $cart_item_quantity - 1, true);
            }

            WC()->cart->calculate_totals();
            WC()->cart->set_session();

            return new WP_REST_Response([
                'success' => true,
                'cart_count' => WC()->cart->get_cart_contents_count(),
                'cart' => WC()->cart->get_cart()
            ], 200);
        }

        public function init_cart()
        {

            if (!did_action('woocommerce_init')) {
                do_action('woocommerce_init');
            }

            if (is_null(WC()->session)) {
                WC()->session = new WC_Session_Handler();
                WC()->session->init();
            }

            if (is_null(WC()->customer)) {
                WC()->customer = new WC_Customer(get_current_user_id(), true);
            }

            if (is_null(WC()->cart)) {
                WC()->cart = new WC_Cart();
            }

            WC()->cart->get_cart();
        }

    }

    $wordpress_api_cart = new Wordpress_Api_Cart();
}