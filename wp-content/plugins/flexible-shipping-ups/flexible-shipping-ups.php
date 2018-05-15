<?php
/*
	Plugin Name: Flexible Shipping UPS
	Plugin URI: https://wordpress.org/plugins/flexible-shipping-ups/
	Description: WooCommerce UPS Shipping Method and live rates.
	Version: 1.0.2
	Author: WP Desk
	Author URI: https://www.wpdesk.net/
	Text Domain: flexible-shipping-ups
	Domain Path: /lang/
	Requires at least: 4.5
	Tested up to: 4.9.4
	WC requires at least: 2.6.14
	WC tested up to: 3.3.3

	Copyright 2017 WP Desk Ltd.

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

	require_once( 'classes/flexible-shipping-ups-plugin.php' );

	$flexible_shipping_ups_plugin_data = array();

	/**
    * @return Flexible_Shipping_UPS_Plugin
    */
	function flexible_shipping_ups_plugin() {
		global $flexible_shipping_ups_plugin;
		if ( !isset( $flexible_shipping_ups_plugin ) ) {
			global $flexible_shipping_ups_plugin_data;
			$flexible_shipping_ups_plugin = new Flexible_Shipping_UPS_Plugin( __FILE__, $flexible_shipping_ups_plugin_data );
		}
		return $flexible_shipping_ups_plugin;
	}

	flexible_shipping_ups_plugin();

