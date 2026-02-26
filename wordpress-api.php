<?php
/**
 * Plugin Name: WordPress API
 * Author: Shereef Hamed
 * Author URI: https://shereefhamed.github.io/portfolio/
 * Description: WordPress API
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

define('WORDPRESS_API_PATH', plugin_dir_path(__FILE__));
define('WORDPRESS_API_URL', plugin_dir_url(__FILE__));
define('WORDPRESS_API_VERSION', '1.0.0');
define('WORDPRESS_API_FILE', __FILE__);

require_once(WORDPRESS_API_PATH. 'includes/wordpress-api-settings.php');
require_once(WORDPRESS_API_PATH. 'includes/wordpress-api-authentication.php');
require_once(WORDPRESS_API_PATH. 'includes/wordpress-api-products.php');
require_once(WORDPRESS_API_PATH. 'includes/wordpress-api-cart.php');
require_once(WORDPRESS_API_PATH. 'includes/wordpress-api-payment-methods.php');
require_once(WORDPRESS_API_PATH. 'includes/wordpress-api-orders.php');
require_once(WORDPRESS_API_PATH. 'includes/wordpress-api-coupon.php');

// if(class_exists('Wordpress_API_Settings')){
// 	register_activation_hook(__FILE__, array('Wordpress_API_Settings', 'activate'));
// 	register_deactivation_hook(__);
// 	register_uninstall_hook();
// }