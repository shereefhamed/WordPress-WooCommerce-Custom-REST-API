<?php
trait Cart_Helper
{
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