<?php

require_once( 'wpdesk/class-plugin.php' );

/**
 * Class Flexible_Shipping_UPS_Plugin
 */
class Flexible_Shipping_UPS_Plugin extends WPDesk_Plugin_1_7 {

	/**
	 * @var string
	 */
	private $scripts_version = '8';

	/**
	 * Flexible_Shipping_UPS_Plugin constructor.
	 *
	 * @param string $base_file
	 * @param array $plugin_data
	 *
	 */
	public function __construct( $base_file, $plugin_data ) {

		$this->plugin_namespace = 'flexible-shipping-ups';
		$this->plugin_text_domain = 'flexible-shipping-ups';

		$this->plugin_has_settings = false;

		if ( is_array( $plugin_data ) && count( $plugin_data ) ) {
			if ( ! class_exists( 'WPDesk_Helper_Plugin' ) ) {
				require_once( 'classes/wpdesk/class-helper.php' );
				add_filter( 'plugins_api', array( $this, 'wpdesk_helper_install' ), 10, 3 );
				add_action( 'admin_notices', array( $this, 'wpdesk_helper_notice' ) );
			}
			$helper = new WPDesk_Helper_Plugin( $plugin_data );
			if ( !$helper->is_active() ) {
				$this->plugin_is_active = false;
			}
		}

		parent::__construct( $base_file, $plugin_data );

		if ( $this->plugin_is_active() ) {
			$this->init();
			$this->hooks();
		}
	}

	/**
	 *
	 */
	public function init() {
		include_once( 'tracker.php' );
		include_once( 'ups/constans.php' );
		include_once( 'ups/rating-rest-api.php' );
		include_once( 'ups/rest-api-exception.php' );
		include_once( 'ups/rest-api-login-fault-exception.php' );
		include_once( 'ups/rest-api-connection-exception.php' );
	}

	/**
	 *
	 */
	public function hooks() {
		parent::hooks();

		add_filter( 'woocommerce_shipping_methods', array( $this, 'woocommerce_shipping_methods' ), 20, 1 );

		add_action( 'wp_ajax_flexible_shipping_ups_api_status', array( $this, 'wp_ajax_flexible_shipping_ups_api_status' ) );

		add_filter( 'woocommerce_order_shipping_method', array( $this, 'woocommerce_order_shipping_method' ), 10, 2 );

		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 9 );

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

	}

	/**
	 * @param string $hooq
	 */
	public function admin_enqueue_scripts( $hooq ) {
		parent::admin_enqueue_scripts( $hooq );
		$current_screen = get_current_screen();

		if ( $hooq == 'woocommerce_page_wc-settings' ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_register_style( 'ups_admin_css', $this->get_plugin_assets_url() . 'css/admin' . $suffix . '.css', array(), $this->scripts_version );
			wp_enqueue_style( 'ups_admin_css' );

			wp_enqueue_script( 'flexible_shipping_ups_admin', trailingslashit( $this->get_plugin_assets_url() ) . 'js/admin' . $suffix . '.js', array(), $this->scripts_version );
			wp_localize_script( 'flexible_shipping_ups_admin', 'flexible_shipping_ups_admin', array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( 'flexible_shipping_ups_api_status' ),
			) );
		}
	}

	/**
	 * @param mixed $links
	 *
	 * @return array|void
	 */
	public function links_filter( $links ) {
		$locale = get_locale();
		$docs_link = $locale === 'pl_PL' ? 'https://www.wpdesk.pl/docs/flexible-shipping-ups/' : 'https://www.wpdesk.net/docs/flexible-shipping-ups/';
		$support_link = $locale === 'pl_PL' ? 'https://www.wpdesk.pl/support/' : 'https://www.wpdesk.net/support';

		$docs_link .= '?utm_source=ups&utm_medium=quick-link&utm_campaign=docs-quick-link';

		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=flexible_shipping_ups') . '">' . __( 'Settings', 'flexible-shipping-ups' ) . '</a>',
			'<a href="' . $docs_link . '">' . __( 'Docs', 'flexible-shipping-ups' ) . '</a>',
			'<a href="' . $support_link . '">' . __( 'Support', 'flexible-shipping-ups' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}


	/**
	 * @param bool $return
	 *
	 * @return mixed|string|void
	 */
	public function wp_ajax_flexible_shipping_ups_api_status( $return = false ) {
		check_ajax_referer( 'flexible_shipping_ups_api_status', 'security' );
		$shipping_methods = WC()->shipping()->get_shipping_methods();
		/** @var Flexible_Shipping_UPS_Shipping_Method $flexible_shipping_ups */
		$flexible_shipping_ups = $shipping_methods['flexible_shipping_ups'];
		$json_response = array( 'connected' => true, 'status' => 'OK', 'class_name' => 'flexible_shipping_ups_api_status_ok' );
		$connection_errors = $flexible_shipping_ups->check_connection_error();
		if ( $connection_errors ) {
			$json_response = array( 'connected' => false, 'status' => $connection_errors, 'class_name' => 'flexible_shipping_ups_api_status_error' );
		}
		if ( !$return ) {
			echo json_encode( $json_response );
			die();
		}
		else {
			return json_encode( $json_response );
		}
	}

	/**
	 * @param $methods
	 *
	 * @return mixed
	 */
	public function woocommerce_shipping_methods( $methods ) {
		include_once( 'shipping-method.php' );
		$methods['flexible_shipping_ups'] = 'Flexible_Shipping_UPS_Shipping_Method';
		return $methods;
	}

	/**
	 * @param string $shipping_method_name
	 * @param WC_Order $order
	 * @return string
	 */
	public function woocommerce_order_shipping_method( $shipping_method_name, $order ) {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return $shipping_method_name;
		}
		$current_screen = get_current_screen();
		if ( !is_object( $current_screen ) || $current_screen->id != 'edit-shop_order' || isset( $_GET['action'] ) ) {
			return $shipping_method_name;
		}
		$shipping_methods = $order->get_shipping_methods();
		/** @var WC_Order_Item_Shipping $shipping_method */
		$add_to_name = '';
		foreach ( $shipping_methods as $shipping_method ) {
			if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
				if ( isset( $shipping_method['method_id'] ) ) {
					$method_id = $shipping_method['method_id'];
					$method_id_elements = explode( ':', $method_id );
					if ( isset( $method_id_elements[0] ) && isset( $method_id_elements[2] ) ) {
						if ( $method_id_elements[0] == 'flexible_shipping_ups' && $method_id_elements[2] == 'fallback' ) {
							$add_to_name = __( ' (Fallback)', 'flexible-shipping-ups' );
						}
					}
				}
			}
			else {
				$method_id = $shipping_method->get_method_id();
				$method_id_elements = explode( ':', $method_id );
				if ( isset( $method_id_elements[0] ) && isset( $method_id_elements[2] ) ) {
					if ( $method_id_elements[0] == 'flexible_shipping_ups' && $method_id_elements[2] == 'fallback' ) {
						$add_to_name = __( ' (Fallback)', 'flexible-shipping-ups' );
					}
				}
			}
		}
		$add_to_name = trim( $add_to_name, ',' );
		return $shipping_method_name . $add_to_name;
	}

	public function plugins_loaded() {
		if ( !class_exists( 'WPDesk_Tracker' ) ) {
			include( trailingslashit( $this->plugin_path ) . 'inc/wpdesk-tracker/class-wpdesk-tracker.php' );
			WPDesk_Tracker::init( basename( dirname( __FILE__ ) ) );
		}
	}

	public function admin_notices() {
		$wc_message = false;
		if ( !function_exists( 'WC' ) ) {
			$wc_message = true;
		}
		else {
			if ( version_compare( WC_VERSION, '2.6', '<' ) ) {
				$wc_message = true;
			}
		}
		if ( $wc_message ) {
			$class = 'notice notice-error';
			$message = __( 'Flexible Shipping UPS requires at least version 2.6 of WooCommerce plugin.', 'flexible-shipping-ups' );
			printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
		}
	}

}
