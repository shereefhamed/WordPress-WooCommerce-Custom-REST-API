<?php
if (!class_exists('')) {
    class Wordpress_Api_Poroducts
    {
        public function __construct()
        {
            add_action('rest_api_init', array($this, 'register_products_rest_api'));
            add_action('rest_api_init', array($this, 'register_product_by_id_rest_api'));
            add_action('rest_api_init', array($this, 'register_product_categories_rest_api'));
            add_action('rest_api_init', array($this, 'register_products_by_categories_rest_api'));
            add_action('rest_api_init', array($this, 'register_best_seller_rest_api'));
        }

        public function register_products_rest_api()
        {
            register_rest_route(
                'custom/v1',
                '/products',
                [
                    'methods' => 'GET',
                    'callback' => array($this, 'get_products'),
                    'permission_callback' => '__return_true',
                ]
            );
        }

        public function get_products($request)
        {
            $args = [
                'post_type' => 'product',
                'posts_per_page' => -1,
                'post_status' => 'publish',
            ];

            $query = new WP_Query($args);
            $products = [];

            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());

                $products[] = [
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'regular_price' => $product->get_regular_price(),
                    'sale_price' => $product->get_sale_price(),
                    'price' => $product->get_price(),
                    'on_sale' => $product->is_on_sale(),
                    'image' => wp_get_attachment_url($product->get_image_id()),
                ];
            }
            wp_reset_postdata();

            return new WP_REST_Response([
                'success' => true,
                'products' => $products
            ], 200);
        }

        public function register_product_by_id_rest_api()
        {
            register_rest_route(
                'custom/v1',
                '/product/(?P<id>\d+)',
                [
                    'methods' => 'GET',
                    'callback' => array($this, 'get_product_by_id'),
                    'permission_callback' => '__return_true',
                ]
            )
            ;
        }

        public function get_product_by_id(WP_REST_Request $request)
        {

            $product_id = $request['id'];
            $product = wc_get_product($product_id);

            if (!$product) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            $result = [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'type' => $product->get_type(),
                'regular_price' => $product->get_regular_price(),
                'sale_price' => $product->get_sale_price(),
                'price' => $product->get_price(),
                'description' => $product->get_description(),
                'stock_status' => $product->get_stock_status(),
                'images' => [],
                'variations' => [],
            ];

            // Images
            foreach ($product->get_gallery_image_ids() as $image_id) {
                $result['images'][] = wp_get_attachment_url($image_id);
            }

            // If variable → include variations
            if ($product->is_type('variable')) {
                foreach ($product->get_children() as $variation_id) {
                    $variation = wc_get_product($variation_id);

                    $result['variations'][] = [
                        'id' => $variation->get_id(),
                        'regular_price' => $variation->get_regular_price(),
                        'sale_price' => $variation->get_sale_price(),
                        'price' => $variation->get_price(),
                        'stock_status' => $variation->get_stock_status(),
                        'attributes' => $variation->get_attributes(),
                    ];
                }
            }

            return new WP_REST_Response([
                'success' => true,
                'product' => $result,
            ], 200);

        }

        public function register_product_categories_rest_api()
        {
            register_rest_route(
                'custom/v1',
                '/categories',
                [
                    'methods' => 'GET',
                    'callback' => array($this, 'get_product_categories'),
                    'permission_callback' => '__return_true',
                ]
            );
        }

        public function get_product_categories(WP_REST_Request $request)
        {
            $terms = get_terms([
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
            ]);

            $categories = [];

            foreach ($terms as $term) {

                $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
                $image = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : null;

                $categories[] = [
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'parent' => $term->parent,
                    'count' => $term->count,
                    'description' => $term->description,
                    'image' => $image,
                ];
            }

            return new WP_REST_Response([
                'success' => true,
                'categories' => $categories,
            ], 200);
        }

        public function register_products_by_categories_rest_api()
        {
            register_rest_route(
                'custom/v1',
                '/products/category/(?P<id>\d+)',
                [
                    'methods' => 'GET',
                    'callback' => array($this, 'get_products_by_category'),
                    'permission_callback' => '__return_true',
                ]
            );
        }

        public function get_products_by_category(WP_REST_Request $request)
        {
            $category_id = $request['id'];

            $args = [
                'post_type' => 'product',
                'posts_per_page' => -1,
                'tax_query' => [
                    [
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $category_id,
                    ]
                ]
            ];

            $query = new WP_Query($args);
            $products = [];

            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());

                $products[] = [
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'type' => $product->get_type(),
                    'price' => $product->get_price(),
                    'regular_price' => $product->get_regular_price(),
                    'sale_price' => $product->get_sale_price(),
                    'image' => wp_get_attachment_url($product->get_image_id()),
                    'stock_status' => $product->get_stock_status(),
                ];
            }

            wp_reset_postdata();

            return new WP_REST_Response([
                'success' => true,
                'products' => $products,
            ], 200);
        }

        public function register_best_seller_rest_api()
        {
            register_rest_route(
                'custom/v1',
                '/best-sellers',
                [
                    'methods' => 'GET',
                    'callback' => array($this, 'get_best_sellers'),
                    'permission_callback' => '__return_true',
                ]
            );
        }

        public function get_best_sellers()
        {
            $args = [
                'post_type' => 'product',
                'posts_per_page' => 10,
                'meta_key' => 'total_sales',
                'orderby' => 'meta_value_num',
                'order' => 'DESC',
            ];

            $query = new WP_Query($args);
            $products = [];

            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());

                $products[] = [
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'price' => $product->get_price(),
                    'sales' => get_post_meta($product->get_id(), 'total_sales', true),
                    'image' => wp_get_attachment_url($product->get_image_id()),
                ];
            }

            wp_reset_postdata();

            return new WP_REST_Response([
                'success' => true,
                'products' => $products,
            ], 200);
        }

        private function get_products_by_arg_filter($args)
        {
            $query = new WP_Query($args);
            $products = [];

            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());

                $products[] = [
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'price' => $product->get_price(),
                    'sales' => get_post_meta($product->get_id(), 'total_sales', true),
                    'image' => wp_get_attachment_url($product->get_image_id()),
                ];
            }

            wp_reset_postdata();
            return $products;
        }
    }

    $wordpress_api_proucts = new Wordpress_Api_Poroducts();
}