<?php

/*
Plugin Name: USPS Simple Shipping for Woocommerce
Plugin URI: http://wordpress.org/plugins/woo-usps-simple-shipping
Description: The USPS Simple plugin calculates rates for domestic shipping dynamically using USPS API during cart/checkout.
Version: 1.2.6
Author: SkyWorks85

*/

/**
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    class USPS_Simple_Shipping {

        public function __construct() {
            add_action( 'woocommerce_shipping_init', array( $this, 'shippingInit' ) );
            add_filter( 'woocommerce_shipping_methods', array( $this, 'addShippingMethod' ) );
        }


        /**
         * Load gateway class
         */
        public function shippingInit() {
            include_once( 'class-shipping-usps.php' );
        }

        /**
         * Add method to WC
         */
        public function addShippingMethod( $methods ) {
            $methods[] = 'USPS_Simple_Shipping_Method';
            return $methods;
        }


    }

    new USPS_Simple_Shipping();
}

?>