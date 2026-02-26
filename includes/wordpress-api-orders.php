<?php
require_once(WORDPRESS_API_PATH . 'helper/user-permission.php');
require_once(WORDPRESS_API_PATH . 'helper/cart-helper.php');

if (!class_exists('Wordpress_API_Orders')) {

    class Wordpress_API_Orders
    {
        use UserPermission, Cart_Helper;
        public function __construct()
        {
            add_action('rest_api_init', array($this, 'register_create_order_rest_api'));
            add_action('rest_api_init', array($this, 'register_orders_rest_api'));
            add_action('rest_api_init', array($this, 'register_order_by_id_rest_api'));
        }

        public function register_create_order_rest_api()
        {
            register_rest_route(
                'custom/v1',
                '/create-order',
                [
                    'methods' => 'POST',
                    'callback' => array($this, 'create_order'),
                    'permission_callback' => array($this, 'is_user_logged_in'),
                ]
            );
        }

        public function register_orders_rest_api()
        {
            register_rest_route(
                'custom/v1',
                '/my-orders',
                [
                    'methods' => 'GET',
                    'callback' => array($this, 'get_current_user_orders'),
                    'permission_callback' => array($this, 'is_user_logged_in'),
                ]
            );
        }

        public function register_order_by_id_rest_api()
        {
            register_rest_route(
                'custom/v1',
                '/order/(?P<id>\d+)',
                [
                    'methods' => 'GET',
                    'callback' => array($this, 'get_order_by_id'),
                    'permission_callback' => array($this, 'is_user_logged_in'),
                ]
            );
        }

        public function create_order(WP_REST_Request $request)
        {
            $this->init_cart();

            if (WC()->cart->is_empty()) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Cart is empty'
                ], 400);
            }

            $user_id = get_current_user_id();

            // Create order
            $order = wc_create_order([
                'customer_id' => $user_id
            ]);
            //$cart = WC()->cart->get_cart();


            // Loop cart items
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {

                $product = $cart_item['data'];
                $quantity = $cart_item['quantity'];
                $variation_id = $cart_item['variation_id'];
                $variation = $cart_item['variation'];

                $order->add_product(
                    $product,
                    $quantity,
                    [
                        'variation_id' => $variation_id,
                        'variation' => $variation,
                    ]
                );
            }

            // Set addresses from user
            $order->set_address([
                'first_name' => get_user_meta($user_id, 'billing_first_name', true),
                'last_name' => get_user_meta($user_id, 'billing_last_name', true),
                'address_1' => get_user_meta($user_id, 'billing_address_1', true),
                'city' => get_user_meta($user_id, 'billing_city', true),
                'phone' => get_user_meta($user_id, 'billing_phone', true),
                'email' => get_userdata($user_id)->user_email,
            ], 'billing');



            $order->set_payment_method('cod');
            $order->set_payment_method_title('Cash on Delivery');

            $order->calculate_totals();
            $order->update_status('processing', 'API request');

            // Reduce stock automatically
            wc_reduce_stock_levels($order->get_id());

            // Empty cart
            WC()->cart->empty_cart();

            return new WP_REST_Response([
                'success' => true,
                'order_id' => $order->get_id(),
                'total' => $order->get_total(),
            ], 200);
        }

        public function get_current_user_orders(WP_REST_Request $request)
        {
            $user_id = get_current_user_id();

            $orders = wc_get_orders([
                'customer_id' => $user_id,
                'limit' => -1,
                'orderby' => 'date',
                'order' => 'DESC',
            ]);

            $data = [];

            foreach ($orders as $order) {

                $items = [] ;

                foreach ($order->get_items() as $item) {

                    $product = $item->get_product();

                    $items[] = [
                        'product_id' => $product ? $product->get_id() : null,
                        'name' => $item->get_name(),
                        'quantity' => $item->get_quantity(),
                        'total' => $item->get_total(),
                    ];
                }

                $data[] = [
                    'order_id' => $order->get_id(),
                    'status' => $order->get_status(),
                    'total' => $order->get_total(),
                    'date' => $order->get_date_created()->date('Y-m-d H:i:s'),
                    'items' => $items,
                    'payment_method' => $order->get_payment_method(),
                ];
            }

            return new WP_REST_Response([
                'success' => true,
                'orders' => $data
            ], 200);
        }

        public function get_order_by_id(WP_REST_Request $request)
        {
            $order_id = intval($request['id']);
            $user_id = get_current_user_id();

            $order = wc_get_order($order_id);

            if (!$order) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // Security check: order must belong to current user
            if ($order->get_customer_id() != $user_id) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $items = [];

            foreach ($order->get_items() as $item) {

                $product = $item->get_product();

                $items[] = [
                    'product_id' => $product ? $product->get_id() : null,
                    'name' => $item->get_name(),
                    'quantity' => $item->get_quantity(),
                    'subtotal' => $item->get_subtotal(),
                    'total' => $item->get_total(),
                    'image' => $product ? wp_get_attachment_url($product->get_image_id()) : null,
                ];
            }

            $data = [
                'order_id' => $order->get_id(),
                'status' => $order->get_status(),
                'currency' => $order->get_currency(),
                'payment_method' => $order->get_payment_method_title(),
                'total' => $order->get_total(),
                'subtotal' => $order->get_subtotal(),
                'discount' => $order->get_total_discount(),
                'shipping' => $order->get_shipping_total(),
                'date_created' => $order->get_date_created()->date('Y-m-d H:i:s'),

                'billing' => $order->get_address('billing'),
                'shipping_address' => $order->get_address('shipping'),

                'items' => $items,
            ];

            return new WP_REST_Response([
                'success' => true,
                'order' => $data
            ], 200);
        }
    }

    $wordpress_api_orders = new Wordpress_API_Orders();
}