<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Flexible_Shipping_UPS_Shipping_Method' ) ) {
	class Flexible_Shipping_UPS_Shipping_Method extends WC_Shipping_Method {

		/**
		 * @var Flexible_Shipping_UPS_UPS_RATING_REST_API
		 */
		public $rating_rest_api = false;

		/**
		 * @var string
		 */
		public $units_default = 'imperial';

		/**
		 * Flexible_Shipping_UPS_Shipping_Method constructor.
		 *
		 * @param int $instance_id
		 */
		public function __construct( $instance_id = 0 ) {

		    $docs_link = 'https://www.wpdesk.net/docs/woocommerce-ups-docs/';
		    if ( get_locale() == 'pl_PL' ) {
			    $docs_link = 'https://www.wpdesk.pl/docs/ups-woocommerce-docs/';
            }

			$docs_link .= '?utm_source=ups-settings&utm_medium=link&utm_campaign=settings-docs-link';

			$this->instance_id 			     	= absint( $instance_id );
			$this->id                 			= 'flexible_shipping_ups';

			$this->method_title       			= __( 'UPS', 'flexible-shipping-ups' );

			$add_link_to_method_description = true;
			if ( isset( $_GET['zone_id'] ) ) {
				$add_link_to_method_description = false;
			}
			if ( $add_link_to_method_description ) {
				$this->method_description = sprintf( __( 'The UPS extension obtains rates dynamically from the UPS API during cart/checkout. %sRefer to the instruction manual →%s', 'flexible-shipping-ups' ), '<a target="_blank" href="' . $docs_link . '">', '</a>' );
			}
			else {
				$this->method_description = __( 'The UPS extension obtains rates dynamically from the UPS API during cart/checkout.', 'flexible-shipping-ups' );
			}

			$this->enabled		                = 'yes';
			$this->title                        = __( 'UPS', 'flexible-shipping-ups' );

			$this->init();

			if ( $this->instance_id ) {
				$this->title = $this->get_instance_option( 'title', __( 'UPS', 'flexible-shipping-ups' ) );
			}

			$this->settings['enabled']          = 'yes';

			$this->supports = array(
			        'settings',
                    'shipping-zones',
				    'instance-settings',
            );

			$this->units_default = 'imperial';

			$woocommerce_weight_unit = get_option( 'woocommerce_weight_unit', '' );
			if ( in_array( $woocommerce_weight_unit, array( 'g', 'kg' ) ) ) {
				$this->units_default = 'metric';
			}


			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

		}

		/**
		 * Custom origin - can be enabled/disabled in WooCommerce 3.2+
		 * In previous versions always disabled - origin address must be entered
		 * @return bool
		 */
		public function custom_origin() {
			if ( version_compare( WC_VERSION, '3.2', '<' ) ) {
			    return true;
			}
			else {
			    return $this->get_option( 'custom_origin', 'no' ) == 'yes';
            }
        }

		/**
		 * Init settings and forms fields
		 *
		 * @return void
		 */
		function init() {
			// Load the settings API
			$this->init_form_fields();
			$this->init_instance_form_fields();
            $this->init_instance_settings();
			$this->init_settings();
		}

		/**
		 * @return bool|Flexible_Shipping_UPS_UPS_RATING_REST_API
		 */
		public function get_rating_rest_api() {
			if ( !$this->rating_rest_api ) {
				$this->rating_rest_api = new Flexible_Shipping_UPS_UPS_RATING_REST_API( $this->settings );
			}
			return $this->rating_rest_api;
		}

		/**
		 * Initialise Settings Form Fields
		 */
		public function init_instance_form_fields() {

			$this->instance_form_fields = array(
				array(
					'title'       => __( 'Method Settings', 'flexible-shipping-ups' ),
					'type'        => 'title',
				),
				'title' => array(
					'title' 		=> __( 'Method Title', 'flexible-shipping-ups' ),
					'type' 			=> 'text',
					'description' 	=> __( 'This controls the title which the user sees during checkout when fallback is used.', 'flexible-shipping-ups' ),
					'default'		=> __( 'UPS', 'flexible-shipping-ups' ),
					'desc_tip'		=> true
				),
				'custom_services' => array(
					'title'             => __( 'Services', 'flexible-shipping-ups' ),
					'label'             => __( 'Enable services custom settings', 'flexible-shipping-ups' ),
					'type'              => 'checkbox',
					'description' 	    => __( 'Enable if you want to select available services. By enabling a service, it does not guarantee that it will be offered, as the plugin will only offer the available rates based on the package weight, the origin and the destination.', 'flexible-shipping-ups' ),
					'desc_tip'		    => true
				),
				'services'  => array(
					'title' 		=> __( 'Services', 'flexible-shipping-ups' ),
					'type' 			=> 'services',
				),
				'negotiated_rates' => array(
					'title'             => __( 'Negotiated Rates', 'flexible-shipping-ups' ),
					'label'             => __( 'Enable negotiated rates', 'flexible-shipping-ups' ),
					'type'              => 'checkbox',
					'description' 	    => __( 'Enable this option only if your shipping account has negotiated rates available.', 'flexible-shipping-ups' ),
					'desc_tip'		    => true
				),
				'insurance' => array(
					'title'             => __( 'Insurance', 'flexible-shipping-ups' ),
					'label'             => __( 'Request insurance to be included in UPS rates', 'flexible-shipping-ups' ),
					'type'              => 'checkbox',
					'description' 	    => __( 'Enable if you want to include insurance in UPS rates when it is available.', 'flexible-shipping-ups' ),
					'desc_tip'		    => true
				),
				'fallback' => array(
					'title' 		=> __( 'Fallback', 'flexible-shipping-ups' ),
					'label'         => __( 'Enable fallback', 'flexible-shipping-ups' ),
					'type' 			=> 'checkbox',
					'description' 	=> __( 'Enable to offer flat rate cost for shipping so that the user can still checkout, if UPS returns no matching rates.', 'flexible-shipping-ups' ),
					'desc_tip'		=> true
				),
				'fallback_cost' => array(
					'title' 		=> __( 'Fallback Cost', 'flexible-shipping-ups' ),
					'type' 			=> 'price',
					'required'      => true,
					'description' 	=> __( 'Enter a numeric value with no currency symbols.', 'flexible-shipping-ups' ),
					'desc_tip'		=> true
				),
			);

		}

		/**
		 * Initialise Settings Form Fields
		 */
		public function init_form_fields() {


			$this->form_fields = array(
				array(
					'title'       => __( 'API Settings', 'flexible-shipping-ups' ),
					'type'        => 'title',
					'description' => sprintf( __( 'You need to obtain UPS account credentials by registering on their %swebsite →%s', 'flexible-shipping-ups' ), '<a href="https://www.ups.com/upsdeveloperkit" target="_blank">', '</a>' ),
				),
				'user_id'       => array(
					'title'             => __( 'UPS User ID', 'flexible-shipping-ups' ),
					'type'              => 'text',
					'custom_attributes' => array(
						'required' => 'required'
					),
					'description' 	    => __( 'Provide your UPS account details.', 'flexible-shipping-ups' ),
					'desc_tip'		    => true
				),
				'password'       => array(
					'title'             => __( 'UPS Password', 'flexible-shipping-ups' ),
					'type'              => 'password',
					'custom_attributes' => array(
						'required'     => 'required',
						'autocomplete' => 'new-password'
					),
					'description' 	    => __( 'Provide your UPS account details.', 'flexible-shipping-ups' ),
					'desc_tip'		    => true
				),
				'access_key' => array(
					'title'             => __( 'UPS Access Key', 'flexible-shipping-ups' ),
					'type'              => 'text',
					'custom_attributes' => array(
						'required' => 'required'
					),
					'description' 	    => __( 'Provide your UPS account details.', 'flexible-shipping-ups' ),
					'desc_tip'		    => true
				),
				'account_number' => array(
					'title'             => __( 'UPS Account Number', 'flexible-shipping-ups' ),
					'type'              => 'text',
					'custom_attributes' => array(
						'required' => 'required'
					),
					'description' 	    => __( 'Provide your UPS account details.', 'flexible-shipping-ups' ),
					'desc_tip'		    => true
				),
				array(
					'title'       => __( 'Origin Settings', 'flexible-shipping-ups' ),
					'type'        => 'title',
				),
								'custom_origin' => array(
					'title'             => __( 'Custom Origin', 'flexible-shipping-ups' ),
					'label'             => __( 'Enable custom origin', 'flexible-shipping-ups' ),
					'type'              => 'checkbox',
					'description' 	    => __( 'By default store address data from the WooCommerce settings are used as the origin.', 'flexible-shipping-ups' ),
					'desc_tip'		    => true
				),
				'origin_address' => array(
					'title' 		=> __( 'Origin Address', 'flexible-shipping-ups' ),
					'type' 			=> 'text',
					'custom_attributes' => array(
						'required' => 'required'
					)
				),
				'origin_city' => array(
					'title' 		=> __( 'Origin City', 'flexible-shipping-ups' ),
					'type' 			=> 'text',
					'custom_attributes' => array(
						'required' => 'required'
					)
				),
				'origin_postcode' => array(
					'title' 		=> __( 'Origin Postcode', 'flexible-shipping-ups' ),
					'type' 			=> 'text',
					'custom_attributes' => array(
						'required' => 'required'
					)
				),
				'origin_country' => array(
					'title' 		=> __( 'Origin Country', 'flexible-shipping-ups' ),
					'type' 			=> 'select',
					'options'       => WC()->countries->get_countries(),
					'custom_attributes' => array(
						'required' => 'required'
					)
				),
				array(
					'title'       => __( 'Advanced Options', 'flexible-shipping-ups' ),
					'type'        => 'title',
				),
				'units' => array(
					'title' 		=> __( 'Weight Unit', 'flexible-shipping-ups' ),
					'type' 			=> 'select',
					'options'       => array(
						'imperial'  => __( 'LB', 'flexible-shipping-ups' ),
						'metric'    => __( 'KG', 'flexible-shipping-ups' ),
					),
					'description' 	=> __( 'By default store settings are used. If you see "This measurement system is not valid for the selected country" errors, switch units. Units in the store settings will be converted to units required by UPS.', 'flexible-shipping-ups' ),
					'desc_tip'		=> true,
					'default'       => $this->units_default,
				),
				'debug_mode' => array(
					'title'             => __( 'Debug Mode', 'flexible-shipping-ups' ),
					'label'             => __( 'Enable debug mode', 'flexible-shipping-ups' ),
					'type'              => 'checkbox',
					'description' 	    => __( 'Enable debug mode to display messages in the cart/checkout. Admins and shop managers will see all messages and data sent to UPS. The customer will only see messages from the UPS API.', 'flexible-shipping-ups' ),
					'desc_tip'		    => true
				),
				'api_status' => array(
					'title'             => __( 'API Status', 'flexible-shipping-ups' ),
					'type'              => 'api_status',
					'class'             => 'flexible_shipping_ups_api_status',
					'default'           => __( 'Checking...', 'flexible-shipping-ups' ),
					'description' 	    => __( 'If there are connection problems, you should see the error message.', 'flexible-shipping-ups' ),
					'desc_tip'		    => true
				),
			);
			if ( version_compare( WC_VERSION, '3.2', '<' ) ) {
			    unset( $this->form_fields['custom_origin'] );
            }
		}

		/**
		 * Generate HTML for custom field type api_status
		 * @param $key
		 * @param $data
		 *
		 * @return string
		 */
		function generate_api_status_html( $key, $data ) {
			$field_key = $this->get_field_key( $key );
			$defaults  = array(
				'title'             => '',
				'disabled'          => false,
				'class'             => '',
				'css'               => '',
				'placeholder'       => '',
				'type'              => 'text',
				'desc_tip'          => false,
				'description'       => '',
				'custom_attributes' => array(),
			);

			$data = wp_parse_args( $data, $defaults );

			ob_start();
			include ( 'views/html-api-status.php' );
			return ob_get_clean();
		}

		/**
		 * @param bool $get_current_services
		 *
		 * @return array
		 */
		function get_available_services( $get_current_services = true ) {
			$country_code = '';
			if ( $this->custom_origin() ) {
				$country_code = $this->get_option( 'origin_country', '' );
			}
			else {
				$woocommerce_default_country = explode( ':', get_option( 'woocommerce_default_country', '' ) );
				if ( !empty( $woocommerce_default_country[0] ) ) {
					$country_code = $woocommerce_default_country[0];
				}
			}

			$services_available = Flexible_Shipping_UPS_Constans::get_services_for_country( $country_code );

			$services = array();

			if ( $get_current_services ) {
				$current_services = $this->get_instance_option( 'services', array() );
				foreach ( $current_services as $service_code => $service ) {
					$services[ $service_code ] = $service;
				}
			}

			foreach ( $services_available as $service_code => $service_name ) {
				if ( empty( $services[ $service_code ] ) ) {
					$services[ $service_code ] = array( 'name' => $service_name, 'enabled' => false );
				}
			}

			return $services;
        }

		/**
		 * @return array
		 */
		public function get_enabled_services() {
		    $enabled_services = $this->get_available_services();
            foreach ( $enabled_services as $service_code => $enabled_service ) {
                if ( !$enabled_service['enabled'] ) {
                    unset( $enabled_services[$service_code] );
                }
            }
            return $enabled_services;
        }

		/**
		 * * Generate HTML for custom field type services
		 * @param $key
		 * @param $data
		 *
		 * @return string
		 */
		function generate_services_html( $key, $data ) {
			$field_key = $this->get_field_key( $key );
			$defaults  = array(
				'title'             => '',
				'disabled'          => false,
				'class'             => '',
				'css'               => '',
				'placeholder'       => '',
				'type'              => 'text',
				'desc_tip'          => false,
				'description'       => '',
				'custom_attributes' => array(),
			);

			$data = wp_parse_args( $data, $defaults );

			$services = $this->get_available_services();

			ob_start();
			include ( 'views/html-services.php' );
			return ob_get_clean();
		}

		/**
		 * Validate custom field type services
		 * @param $key
		 * @param $value
		 *
		 * @return mixed
		 */
		public function validate_services_field( $key, $value ) {
		    foreach ( $value as $key => $values ) {
		        $value[$key]['name'] = sanitize_text_field( $value[$key]['name'] );
		        if ( empty( $value[$key]['enabled'] ) ) {
			        $value[$key]['enabled'] = false;
                }
                else {
	                $value[$key]['enabled'] = true;
                }
            }
            return $value;
        }

		/**
		 * Check for API connection status
		 * @return bool|string|void
		 */
		public function check_connection_error() {
			if ( $this->get_option( 'user_id', '' ) == '' || $this->get_option( 'password', '' ) == '' ) {
				return __( 'No User ID or password entered.', 'flexible-shipping-ups' );
			}
            $rating_rest_api = $this->get_rating_rest_api();
            try {
                $rating_rest_api->ping();
            }
            catch ( Flexible_Shipping_UPS_UPS_REST_API_Login_Fault_Exception $e ) {
                return $e->getMessage();
            }
            catch ( Flexible_Shipping_UPS_UPS_REST_API_Connection_Exception $e ) {
	            return $e->getMessage();
            }
            catch ( Exception $e ) {
	            return false;
            }
			return false;
		}

		/**
		 * @return bool|void
		 */
		public function process_admin_options() {
		    parent::process_admin_options();
		    $rating_rest_api = $this->get_rating_rest_api();
		    $rating_rest_api->clear_cache();
        }

		/**
		 * @param $message
		 * @param bool $error
		 */
		public function debug( $message, $error = false ) {
	        $notice_type = 'notice';
			if ( $error ) {
				$notice_type = 'error';
			}
	        if ( $this->get_option( 'debug_mode', 'no' ) == 'yes' ) {
		        wc_add_notice( $message, $notice_type );
	        }
        }

		/**
		 * @param array $package
		 */
		public function calculate_shipping( $package = array() ) {
		    if ( empty( $package['destination'] ) ) {
		        return;
            }
			$rating_rest_api = $this->get_rating_rest_api();
			$ups_shipper = $this->get_ups_shipper();
			$ups_ship_to = $this->get_ups_ship_to( $package );
			$ups_ship_from = $this->get_ups_ship_from();
			$ups_service = $this->get_ups_service();
			$ups_package = $this->get_ups_package( $package );
			$ups_shipment_rating_options = $this->get_ups_shipment_rating_options();
			try {
				$rate_request_data = $rating_rest_api->prepare_rate_request_data(
					'Shop',
					$ups_shipper,
					$ups_ship_to,
					$ups_ship_from,
					$ups_service,
					$ups_package,
					$ups_shipment_rating_options
				);
				if ( current_user_can( 'manage_woocommerce' ) ) {
					$this->debug( sprintf( __( 'UPS API Request data: %s', 'flexible-shipping-ups' ), '<pre>' . print_r( $rate_request_data, true ) . '</pre>' ) );
				}
				$ups_rates_response = $rating_rest_api->rate_request($rate_request_data);
				if ( current_user_can( 'manage_woocommerce' ) ) {
					$this->debug( sprintf( __( 'UPS API Response: %s', 'flexible-shipping-ups' ), '<pre>' . print_r( $ups_rates_response, true ) . '</pre>' ) );
				}
				$added_rates = 0;
				if ( isset( $ups_rates_response->RateResponse ) && isset( $ups_rates_response->RateResponse->RatedShipment ) ) {
					if ( $this->get_instance_option( 'custom_services', 'no' ) == 'no' ) {
						foreach ( $ups_rates_response->RateResponse->RatedShipment as $ups_rate ) {
							if ( $this->is_service_enabled( $ups_rate->Service->Code ) ) {
								if ( $this->add_rate_from_ups_rate( $ups_rate ) ) {
									$added_rates++;
								};
							}
						}
					}
					else {
					    $enabled_services = $this->get_enabled_services();
					    foreach ( $enabled_services as $enabled_service_code => $enabled_service  ) {
						    foreach ( $ups_rates_response->RateResponse->RatedShipment as $ups_rate ) {
							    if ( $enabled_service_code == $ups_rate->Service->Code ) {
								    if ( $this->add_rate_from_ups_rate( $ups_rate ) ) {
									    $added_rates++;
								    };
							    }
						    }
                        }
                    }
                }
                if ( $added_rates == 0 ) {
					throw new Exception( 'no rates added.' );
                }
			}
			catch ( Exception $e ) {
				$message = $e->getMessage();
				if ( !empty( $this->get_instance_option( 'fallback' , 'no' ) == 'yes' ) ) {
					$this->debug( sprintf(__( 'UPS Fallback!', 'flexible-shipping-ups' ) ), true );
					$this->add_rate( array(
						'id'            => $this->id . ':' . $this->instance_id . ':fallback',
						'label'         => $this->get_instance_option( 'title' ),
						'cost'          => $this->get_instance_option( 'fallback_cost' ),
						'sort'          => 0,
						'meta_data'     => array( 'fallback_reason'  => $message )
					) );
				}
				else {
					$this->debug( sprintf(__( 'UPS Fallback disabled!', 'flexible-shipping-ups' ) ), true );
				}
				$this->debug( sprintf(__( 'UPS Exception: %s', 'flexible-shipping-ups' ), $message ), true );
            }
		}

		/**
		 * @param $ups_rate
		 *
		 * @return bool
		 */
		public function add_rate_from_ups_rate( $ups_rate ) {
			$cost = $ups_rate->TotalCharges->MonetaryValue;
			$currency_code = $ups_rate->TotalCharges->CurrencyCode;
			if ( isset( $ups_rate->NegotiatedRateCharges ) && isset( $ups_rate->NegotiatedRateCharges->TotalCharge )
			     && isset( $ups_rate->NegotiatedRateCharges->TotalCharge->MonetaryValue )
			) {
				$cost = $ups_rate->NegotiatedRateCharges->TotalCharge->MonetaryValue;
				$currency_code = $ups_rate->NegotiatedRateCharges->TotalCharge->CurrencyCode;
			}
			if ( $currency_code == get_woocommerce_currency() ) {
				$this->add_rate( array(
					'id'    => $this->id . ':' . $this->instance_id . ':' . $ups_rate->Service->Code,
					'label' => $this->get_service_name( $ups_rate->Service->Code ),
					'cost'  => $cost,
					'sort'  => 0
				) );
				return true;
			}
			else {
				$this->debug( sprintf(__( 'Invalid UPS currency %s for service %s', 'flexible-shipping-ups' ), $currency_code, $ups_rate->Service->Code ), true );
			}
			return false;
		}

		/**
		 * @param $service_code
		 *
		 * @return bool
		 */
		public function is_service_enabled( $service_code ) {
		    if ( $this->get_instance_option( 'custom_services', 'no' ) == 'yes' ) {
		        $enabled_services = $this->get_enabled_services();
		        if ( empty( $enabled_services[$service_code] ) ) {
		            return false;
                }
            }
            return true;
        }

		/**
		 * @param $service_code
		 *
		 * @return string
		 */
		public function get_service_name( $service_code ) {
		    $available_services = $this->get_available_services();
		    if ( isset( $available_services[$service_code] ) ) {
		        return $available_services[$service_code]['name'];
            }
		    return sprintf( __( 'Unknown UPS service code %s.', 'flexible-shipping-ups' ), $service_code );
        }

		/**
		 * @return array
		 */
		public function get_ups_shipper() {
		    $ups_shipper = array(
		        'ShipperNumber' => $this->get_option( 'account_number' ),
                'Address' => array(
                    'AddressLine'   => array(),
                )
            );
		    if ( $this->custom_origin() ) {
		        $ups_shipper['Address']['AddressLine'][] = $this->get_option( 'origin_address', '' );
			    $ups_shipper['Address']['City'] = $this->get_option( 'origin_city', '' );
			    $ups_shipper['Address']['PostalCode'] = $this->get_option( 'origin_postcode', '' );
			    $ups_shipper['Address']['CountryCode'] = $this->get_option( 'origin_country', '' );
            }
            else {
	            $ups_shipper['Address']['AddressLine'][] = get_option( 'woocommerce_store_address', '' );
	            $ups_shipper['Address']['AddressLine'][] = get_option( 'woocommerce_store_address_2', '' );
	            $ups_shipper['Address']['City'] = get_option( 'woocommerce_store_city', '' );
	            $ups_shipper['Address']['PostalCode'] = get_option( 'woocommerce_store_postcode', '' );
	            $woocommerce_default_country = explode( ':', get_option( 'woocommerce_default_country', '' ) );
	            if ( !empty( $woocommerce_default_country[0] ) ) {
		            $ups_shipper['Address']['CountryCode'] = $woocommerce_default_country[0];
	            }
            }
		    return $ups_shipper;
        }

		/**
		 * @param $package
		 *
		 * @return array
		 */
		public function get_ups_ship_to( $package ) {
		    $ups_ship_to = array();
		    if ( !empty( $package['destination'] ) ) {
			    $ups_ship_to = array( 'Address' => array( 'AddressLine' => array() ) );
			    if ( !empty( $package['destination']['address'] ) ) {
				    $ups_ship_to['Address']['AddressLine'][] = $package['destination']['address'];
			    }
			    if ( !empty( $package['destination']['address_2'] ) ) {
				    $ups_ship_to['Address']['AddressLine'][] = $package['destination']['address_2'];
			    }
			    if ( !empty( $package['destination']['city'] ) ) {
				    $ups_ship_to['Address']['City'] = $package['destination']['city'];
			    }
			    if ( !empty( $package['destination']['postcode'] ) ) {
				    $ups_ship_to['Address']['PostalCode'] = $package['destination']['postcode'];
			    }
			    if ( !empty( $package['destination']['country'] ) ) {
				    $ups_ship_to['Address']['CountryCode'] = $package['destination']['country'];
			    }
		    }
		    return $ups_ship_to;
        }

		/**
		 * @return array
		 */
		public function get_ups_ship_from() {
			$ups_ship_from = array(
               'Address' => array(
                   'AddressLine'   => array(),
               )
			);
			if ( $this->custom_origin() ) {
				$ups_ship_from['Address']['AddressLine'][] = $this->get_option( 'origin_address', '' );
				$ups_ship_from['Address']['City'] = $this->get_option( 'origin_city', '' );
				$ups_ship_from['Address']['PostalCode'] = $this->get_option( 'origin_postcode', '' );
				$ups_ship_from['Address']['CountryCode'] = $this->get_option( 'origin_country', '' );
			}
			else {
				$ups_ship_from['Address']['AddressLine'][] = get_option( 'woocommerce_store_address', '' );
				$ups_ship_from['Address']['AddressLine'][] = get_option( 'woocommerce_store_address_2', '' );
				$ups_ship_from['Address']['City'] = get_option( 'woocommerce_store_city', '' );
				$ups_ship_from['Address']['PostalCode'] = get_option( 'woocommerce_store_postcode', '' );
				$woocommerce_default_country = explode( ':', get_option( 'woocommerce_default_country', '' ) );
				if ( !empty( $woocommerce_default_country[0] ) ) {
					$ups_ship_from['Address']['CountryCode'] = $woocommerce_default_country[0];
				}
			}
			return $ups_ship_from;
		}

		/**
		 * @return array
		 */
		public function get_ups_service() {
			//$ups_service = array( array( 'Code' => '11' ) );
			$ups_service = array();
			return $ups_service;
        }

		/**
		 * @param array $package
		 *
		 * @return array
		 */
		public function get_ups_package( $package = array() ) {
		    $ups_package = array(
		        'PackagingType' => array(
		            'Code' => '02',
                    'Description'   => 'Package/customer supplied',
                ),
            );
		    $ups_package['PackageWeight'] = array();
		    $package_weight = $this->calculate_package_weight( $package );
		    if ( $this->get_option( 'units', $this->units_default ) == 'imperial' ) {
			    $ups_package['PackageWeight']['UnitOfMeasurement'] = array( 'Code' => 'LBS' );
			    $ups_package['PackageWeight']['Weight'] = strval( wc_get_weight( $package_weight, 'lbs' ) );
            }
		    else {
			    $ups_package['PackageWeight']['UnitOfMeasurement'] = array( 'Code' => 'KGS' );
			    $ups_package['PackageWeight']['Weight'] = strval( wc_get_weight( $package_weight, 'kg' ) );
            }
		    $ups_package['PackageServiceOptions'] = $this->get_package_service_options( $package );
            /*
		    $ups_package['Dimensions'] = array(
		        'Length'    => 10,
                'Width'     => 5,
                'Height'    => 7,
            );
            */
		    return $ups_package;
        }

		/**
		 * @return array
		 */
		public function get_ups_shipment_rating_options() {
		    $ups_shipment_rating_options = array();
	        if ( $this->get_instance_option( 'negotiated_rates', 'no' ) == 'yes' ) {
		        $ups_shipment_rating_options = array( 'NegotiatedRatesIndicator' => '' );
	        }
		    return $ups_shipment_rating_options;
        }

		/**
		 * @param $package
		 *
		 * @return array
		 */
		public function get_package_service_options( $package ) {
			$ups_package_service_options = array();
			if ( $this->get_instance_option( 'insurance', 'no' ) == 'yes' ) {
				// From UPS support:
				// I apologize for any inconveniences caused by this issue.
				// As JSON is a web based application, you will need to update the element/tag from InsuredValue to DeclaredValue.
				$ups_package_service_options['DeclaredValue'] = array(
					'CurrencyCode'      => get_woocommerce_currency(),
					'MonetaryValue'     => strval( $this->calculate_package_total( $package ) ),
				);
			}
			return $ups_package_service_options;
        }

		/**
		 * @param $package
		 *
		 * @return int
		 */
		public function calculate_package_total( $package ) {
			$subtotal = 0;
			foreach( $package['contents'] as $item )
				$subtotal += $item['line_subtotal'];
			return $subtotal;
		}

		/**
		 * @param $package
		 *
		 * @return float|int
		 */
		public function calculate_package_weight( $package ) {
			$weight = 0;
			foreach( $package['contents'] as $item ) {
				$weight += floatval( $item['data']->get_weight() ) * floatval( $item['quantity'] );
			}
			return $weight;
		}



	}
}
