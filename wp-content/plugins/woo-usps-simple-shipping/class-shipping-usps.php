<?php


class USPS_Simple_Shipping_Method extends WC_Shipping_Method {

    private $endpoint        = 'http://production.shippingapis.com/shippingapi.dll';
    private $defaultUserId = '904SKYWO3126';
    private $domestic = array( "US", "PR", "VI", "MH", "FM" );
    private $foundRates = null;


    public function __construct() {
        $this->id                 = 'usps_simple';
        $this->title              = 'USPS Simple';
        $this->method_title       = 'USPS Simple';
        $this->method_description = 'The <strong>USPS Simple</strong> plugin calculates rates for domestic shipping dynamically using USPS API during cart/checkout.' ;
        $this->services           = include( 'data-domestic-services-usps.php' );
        $this->serviceDescription = 'This controls the title which the user sees during checkout.';

        // Load the settings API
        $this->initFormFields();
        $this->init_settings();

        $this->enabled                  = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : $this->enabled;
        $this->sender                   = isset( $this->settings['sender'] ) ? $this->settings['sender'] : '';
        $this->userId                   = !empty( $this->settings['user_id']) ? $this->settings['user_id'] : $this->defaultUserId;
        $this->commercialRate           = isset( $this->settings['commercial_rate'] ) ? $this->settings['commercial_rate'] : 'yes';
        $this->groupByWeight            = isset( $this->settings['group_by_weight'] ) ? $this->settings['group_by_weight'] : 'no';
        $this->t_express_mail           = !empty( $this->settings['t_express_mail']) ? $this->settings['t_express_mail'] : $this->services['EXPRESS_MAIL']['name'].' ('.$this->method_title.')';
        $this->t_priority_mail          = !empty( $this->settings['t_priority_mail']) ? $this->settings['t_priority_mail'] : $this->services['PRIORITY_MAIL']['name'].' ('.$this->method_title.')';
        $this->t_first_class            = !empty( $this->settings['t_first_class']) ? $this->settings['t_first_class'] : $this->services['FIRST_CLASS']['name'].' ('.$this->method_title.')';
        $this->t_standard_post          = !empty( $this->settings['t_standard_post']) ? $this->settings['t_standard_post'] : $this->services['STANDARD_POST']['name'].' ('.$this->method_title.')';
        $this->t_media_mail             = !empty( $this->settings['t_media_mail']) ? $this->settings['t_media_mail'] : $this->services['MEDIA_MAIL']['name'].' ('.$this->method_title.')';
        $this->t_library_mail           = !empty( $this->settings['t_library_mail']) ? $this->settings['t_library_mail'] : $this->services['LIBRARY_MAIL']['name'].' ('.$this->method_title.')';

        //subservices
        $this->subservicesEnabled = array();

        foreach(array_change_key_case($this->services) as $key => $service ){
            foreach($service['services'] as $index => $val){
                $serviceId = $key.'_'.$index;
                $this->subservicesEnabled[$index] = isset($this->settings[$serviceId]) ? $this->settings[$serviceId] : 'no';
            }
        }

        // Save settings in admin if you have any defined
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'clear_transients' ) );
    }

    public function admin_options() {
        $admin_url = admin_url( 'admin.php?page=wc-settings&tab=general' );

        if ( get_woocommerce_currency() != "USD" ) {
            echo '<div class="error">
				<p>' . sprintf( __( '<a href="%s">Currency</a> must be set in US Dollars.', 'usps-simple-woocommerce-shipping' ), $admin_url ) . '</p>
			</div>';
        }

        if ( ! in_array( WC()->countries->get_base_country(), $this->domestic ) ) {
            echo '<div class="error">
				<p>' . sprintf( __( '<a href="%s">Base country/region</a> must be the United States.', 'usps-simple-woocommerce-shipping' ), $admin_url ) . '</p>
			</div>';
        }

        if ( ! $this->sender && $this->enabled == 'yes' ) {
            echo '<div class="error">
				<p>' . __( 'The origin postcode has not been set.', 'usps-simple-woocommerce-shipping' ) . '</p>
			</div>';
        }

        // Show settings
        parent::admin_options();
    }

    public function clear_transients() {
        global $wpdb;

        $wpdb->query( "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_usps_simple_quote_%') OR `option_name` LIKE ('_transient_timeout_usps_simple_quote_%')" );
    }

    public function initFormFields() {

        $this->form_fields  = array(
            'enabled'          => array(
                'title'           => 'Enable/Disable',
                'type'            => 'checkbox',
                'label'           => 'Enable this shipping method',
                'default'         => 'no'
            ),
            'sender'           => array(
                'title'           => __( 'Postcode', 'usps-simple-woocommerce-shipping' ),
                'type'            => 'text',
                'description'     => __( 'Enter the origin postcode.', 'usps-simple-woocommerce-shipping' ),
                'default'         => '',
                'desc_tip'        => true
            ),
            'user_id'          => array(
                'title'           => __( 'User ID', 'usps-simple-woocommerce-shipping' ),
                'type'            => 'text',
                'description'     => __( 'Enter user id, which was provided after registering at USPS website, or use our id.', 'usps-simple-woocommerce-shipping' ),
                'default'         => '',
                'placeholder'     => $this->defaultUserId,
                'desc_tip'        => true
            ),
            'commercial_rate'  => array(
                'title'           => __( 'Rates', 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'label'           => 'Priority use Commercial Base Pricing',
                'default'         => 'yes',
                'desc_tip'        => true,
                'description'     => __( 'Some services offer several rates. If this is the case, Commercial Base Pricing should be used as priority.', 'usps-simple-woocommerce-shipping' ),
            ),
            'group_by_weight'  => array(
                'title'           => __( 'Packing', 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'label'           => 'Quote regular items by weight',
                'default'         => 'no',
                'desc_tip'        => true,
                'description'     => __( 'If enabled regular-size items (less then 12 inches) are quoted by their total weight. Large items are quoted individually still. Otherwise, all items are quoted individually.', 'usps-simple-woocommerce-shipping' ),
            ),
		    'express_mail'     => array(
            'title'               => __( $this->services['EXPRESS_MAIL']['name'], 'usps-simple-woocommerce-shipping' ),
            'type'                => 'title',
            ),
            't_express_mail'   => array(
                'title'           => 'Title',
                'type'            => 'text',
                'description'     => $this->serviceDescription,
                'placeholder'     => $this->services['EXPRESS_MAIL']['name'].' ('.$this->method_title.')',
                'desc_tip'        => true
            ),
            'express_mail_3'  => array(
                'title'           => __( '', 'usps-simple-woocommerce-shipping' ),
                'label'           => __( '[ 3 ] ' . $this->services['EXPRESS_MAIL']['services'][3], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'default'         => 'yes',
                'description'     => __( '', 'usps-simple-woocommerce-shipping' )
            ),
            'express_mail_2'   => array(
                'title'           => __( '', 'usps-simple-woocommerce-shipping' ),
                'label'           => __( '[ 2 ] ' . $this->services['EXPRESS_MAIL']['services'][2], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'default'         => 'yes',
                'description'     => __( '', 'usps-simple-woocommerce-shipping' )
            ),
            'express_mail_23'  => array(
                'title'           => __( '', 'usps-simple-woocommerce-shipping' ),
                'label'           => __( '[23] ' . $this->services['EXPRESS_MAIL']['services'][23], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'default'         => 'yes',
                'description'     => __( '', 'usps-simple-woocommerce-shipping' )
            ),
            'priority_mail'     => array(
                'title'           => __( $this->services['PRIORITY_MAIL']['name'], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'title',
            ),
            't_priority_mail'  => array(
                'title'           => 'Title',
                'type'            => 'text',
                'description'     => $this->serviceDescription,
                'placeholder'     => $this->services['PRIORITY_MAIL']['name'].' ('.$this->method_title.')',
                'desc_tip'        => true
            ),
            'priority_mail_1'  => array(
                'title'           => __( '', 'usps-simple-woocommerce-shipping' ),
                'label'           => __( '[ 1 ] ' . $this->services['PRIORITY_MAIL']['services'][1], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'default'         => 'yes',
                'description'     => __( '', 'usps-simple-woocommerce-shipping' )
            ),
            'priority_mail_33'  => array(
                'title'           => __( '', 'usps-simple-woocommerce-shipping' ),
                'label'           => __( '[33] ' . $this->services['PRIORITY_MAIL']['services'][33], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'default'         => 'yes',
                'description'     => __( '', 'usps-simple-woocommerce-shipping' )
            ),
            'priority_mail_18' => array(
                'title'           => __( '', 'usps-simple-woocommerce-shipping' ),
                'label'           => __( '[18] ' . $this->services['PRIORITY_MAIL']['services'][18], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'default'         => 'yes',
                'description'     => __( '', 'usps-simple-woocommerce-shipping' )
            ),
            'priority_mail_47' => array(
                'title'           => __( '', 'usps-simple-woocommerce-shipping' ),
                'label'           => __( '[47] ' . $this->services['PRIORITY_MAIL']['services'][47], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'default'         => 'yes',
                'description'     => __( '', 'usps-simple-woocommerce-shipping' )
            ),
            'priority_mail_48' => array(
                'title'           => __( '', 'usps-simple-woocommerce-shipping' ),
                'label'           => __( '[48] ' . $this->services['PRIORITY_MAIL']['services'][48], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'default'         => 'yes',
                'description'     => __( '', 'usps-simple-woocommerce-shipping' )
            ),
            'priority_mail_49' => array(
                'title'           => __( '', 'usps-simple-woocommerce-shipping' ),
                'label'           => __( '[49] ' . $this->services['PRIORITY_MAIL']['services'][49], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'default'         => 'yes',
                'description'     => __( '', 'usps-simple-woocommerce-shipping' )
            ),
            'priority_mail_50' => array(
                'title'           => __( '', 'usps-simple-woocommerce-shipping' ),
                'label'           => __( '[50] ' . $this->services['PRIORITY_MAIL']['services'][50], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'default'         => 'yes',
                'description'     => __( '', 'usps-simple-woocommerce-shipping' )
            ),
            'first_class_mail' => array(
                'title'           => __( $this->services['FIRST_CLASS']['name'], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'title',
            ),
            't_first_class'=> array(
                'title'           => 'Title',
                'type'            => 'text',
                'description'     => $this->serviceDescription,
                'placeholder'     => $this->services['FIRST_CLASS']['name'].' ('.$this->method_title.')',
                'desc_tip'        => true
            ),
            'first_class_0A'=> array(
                'title'           => __( '', 'usps-simple-woocommerce-shipping' ),
                'label'           => __( '[0A] ' . $this->services['FIRST_CLASS']['services']['0A'], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'default'         => 'yes',
                'description'     => __( '', 'usps-simple-woocommerce-shipping' )
            ),
            'first_class_0B'=> array(
                'title'           => __( '', 'usps-simple-woocommerce-shipping' ),
                'label'           => __( '[0B] ' . $this->services['FIRST_CLASS']['services']['0B'], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'default'         => 'yes',
                'description'     => __( '', 'usps-simple-woocommerce-shipping' )
            ),
            'first_class_0C'=> array(
                'title'           => __( '', 'usps-simple-woocommerce-shipping' ),
                'label'           => __( '[0C] ' . $this->services['FIRST_CLASS']['services']['0C'], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'default'         => 'yes',
                'description'     => __( '', 'usps-simple-woocommerce-shipping' )
            ),
            'first_class_0D'=> array(
                'title'           => __( '', 'usps-simple-woocommerce-shipping' ),
                'label'           => __( '[0D] ' . $this->services['FIRST_CLASS']['services']['0D'], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'default'         => 'yes',
                'description'     => __( '', 'usps-simple-woocommerce-shipping' )
            ),
            'first_class_12'=> array(
                'title'           => __( '', 'usps-simple-woocommerce-shipping' ),
                'label'           => __( '[12] ' . $this->services['FIRST_CLASS']['services'][12], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'default'         => 'yes',
                'description'     => __( '', 'usps-simple-woocommerce-shipping' )
            ),
            'first_class_15'=> array(
                'title'           => __( '', 'usps-simple-woocommerce-shipping' ),
                'label'           => __( '[15] ' . $this->services['FIRST_CLASS']['services'][15], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'default'         => 'yes',
                'description'     => __( '', 'usps-simple-woocommerce-shipping' )
            ),
            'first_class_19'=> array(
                'title'           => __( '', 'usps-simple-woocommerce-shipping' ),
                'label'           => __( '[19] ' . $this->services['FIRST_CLASS']['services'][19], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'default'         => 'yes',
                'description'     => __( '', 'usps-simple-woocommerce-shipping' )
            ),
            'first_class_61'=> array(
                'title'           => __( '', 'usps-simple-woocommerce-shipping' ),
                'label'           => __( '[61] ' . $this->services['FIRST_CLASS']['services'][61], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'default'         => 'yes',
                'description'     => __( '', 'usps-simple-woocommerce-shipping' )
            ),
            'first_class_53'=> array(
                'title'           => __( '', 'usps-simple-woocommerce-shipping' ),
                'label'           => __( '[53] ' . $this->services['FIRST_CLASS']['services'][53], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'default'         => 'yes',
                'description'     => __( '', 'usps-simple-woocommerce-shipping' )
            ),
            'first_class_78'=> array(
                'title'           => __( '', 'usps-simple-woocommerce-shipping' ),
                'label'           => __( '[78] ' . $this->services['FIRST_CLASS']['services'][78], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'default'         => 'yes',
                'description'     => __( '', 'usps-simple-woocommerce-shipping' )
            ),
            'standard_post'    => array(
                'title'           => __( $this->services['STANDARD_POST']['name'], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'title',
            ),
            't_standard_post'  => array(
                'title'           => 'Title',
                'type'            => 'text',
                'description'     => $this->serviceDescription,
                'placeholder'     => $this->services['STANDARD_POST']['name'].' ('.$this->method_title.')',
                'desc_tip'        => true
            ),
            'standard_post_4'  => array(
                'title'           => __( '', 'usps-simple-woocommerce-shipping' ),
                'label'           => __( '[ 4 ] ' . $this->services['STANDARD_POST']['services'][4], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'default'         => 'yes',
                'description'     => __( '', 'usps-simple-woocommerce-shipping' )
            ),
            'media_mail'       => array(
                'title'           => __( $this->services['MEDIA_MAIL']['name'], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'title',
            ),
            't_media_mail'     => array(
                'title'           => 'Title',
                'type'            => 'text',
                'description'     => $this->serviceDescription,
                'placeholder'     => $this->services['MEDIA_MAIL']['name'].' ('.$this->method_title.')',
                'desc_tip'        => true
            ),
            'media_mail_6'     => array(
                'title'           => __( '', 'usps-simple-woocommerce-shipping' ),
                'label'           => __( '[ 6 ] ' . $this->services['MEDIA_MAIL']['services'][6], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'default'         => 'yes',
                'description'     => __( '', 'usps-simple-woocommerce-shipping' )
            ),
            'library_mail'     => array(
                'title'           => __( $this->services['LIBRARY_MAIL']['name'], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'title',
            ),
            't_library_mail'   => array(
                'title'           => 'Title',
                'type'            => 'text',
                'description'     => $this->serviceDescription,
                'placeholder'     => $this->services['LIBRARY_MAIL']['name'].' ('.$this->method_title.')',
                'desc_tip'        => true
            ),
            'library_mail_7'   => array(
                'title'           => __( '', 'usps-simple-woocommerce-shipping' ),
                'label'           => __( '[ 7 ] ' . $this->services['LIBRARY_MAIL']['services'][7], 'usps-simple-woocommerce-shipping' ),
                'type'            => 'checkbox',
                'default'         => 'yes',
                'description'     => __( '', 'usps-simple-woocommerce-shipping' )
            ),
        );
    }

    private function generateApiRequest( $packageId, $destination, $dimensions, $weight, $service = 'ONLINE', $size = 'REGULAR' ) {

        $request = '<Package ID="' . $packageId . '">' . "\n";
        $request .= '	<Service>'.$service.'</Service>' . "\n";
        $request .= '	<ZipOrigination>' . str_replace(' ', '', strtoupper($this->sender)) . '</ZipOrigination>' . "\n";
        $request .= '	<ZipDestination>' . $destination . '</ZipDestination>' . "\n";
        $request .= '	<Pounds>' . floor($weight) . '</Pounds>' . "\n";
        $request .= '	<Ounces>' . number_format(($weight - floor($weight)) * 16, 2) . '</Ounces>' . "\n";

        if ('LARGE' === $size) {
            $request .= '	<Container>RECTANGULAR</Container>' . "\n";
        } else {
            $request .= '	<Container />' . "\n";
        }

        $request .= '	<Size>' . $size . '</Size>' . "\n";
        $request .= '	<Width>' . $dimensions[1] . '</Width>' . "\n";
        $request .= '	<Length>' . $dimensions[2] . '</Length>' . "\n";
        $request .= '	<Height>' . $dimensions[0] . '</Height>' . "\n";
        $request .= '	<Girth>' . round($dimensions[0]*2 + $dimensions[1]*2) . '</Girth>' . "\n";
        $request .=  $service == 'STANDARD POST' ? '<GroundOnly>true</GroundOnly>' . "\n" : '';
        $request .= '	<Machinable>true</Machinable> ' . "\n";
        $request .= '	<ShipDate>' . date("d-M-Y", (current_time('timestamp') + (60 * 60 * 24))) . '</ShipDate>' . "\n";
        $request .= '</Package>' . "\n";

        return $request;
    }

    private function getRequestsArr( $package ) {
        global $woocommerce;

        $onlineRequests = array();
        $standardPostRequests = array();
        $groupItemWeight = 0;

        if (in_array( $package['destination']['country'], $this->domestic )) {

            $destination = strtoupper(substr($package['destination']['postcode'], 0, 5));

            // Get weight of order
            foreach ($package['contents'] as $itemId => $values) {

                if (!$values['data']->needs_shipping()) {
                    //Product # is virtual. Skipping.
                    continue;
                }

                if (!$values['data']->get_weight()) {
                    //Product # is missing weight. Using 1lb.

                    $weight = 1;
                } else {
                    $weight = wc_get_weight($values['data']->get_weight(), 'lbs');
                }

                $size = 'REGULAR';

                if ($values['data']->length && $values['data']->height && $values['data']->width) {

                    $dimensions = array(wc_get_dimension($values['data']->length, 'in'), wc_get_dimension($values['data']->height, 'in'), wc_get_dimension($values['data']->width, 'in'));

                    sort($dimensions);

                    if (max($dimensions) > 12) {
                        $size = 'LARGE';
                    }
                } else {
                    $dimensions = array(0, 0, 0);
                }

                if ( $this->groupByWeight == 'yes' && $size == 'REGULAR' ) {
                    $groupItemWeight += ( $weight * $values['quantity'] );
                    continue;
                }

                $packageId = $this->generatePackageId( $itemId, $values['quantity'], $dimensions[2], $dimensions[1], $dimensions[0], $weight );
                $onlineRequests[] = $this->generateApiRequest( $packageId, $destination, $dimensions, $weight, 'ONLINE', $size );
                $standardPostRequests[] = $this->generateApiRequest( $packageId, $destination, $dimensions, $weight, 'STANDARD POST', $size );
            }

            if ( $groupItemWeight > 0 ) {
                $maxPackageWeight = 70;
                $packageWeights    = array();

                $fullPackages      = floor( $groupItemWeight / $maxPackageWeight );
                for ( $i = 0; $i < $fullPackages; $i ++ )
                    $packageWeights[] = $maxPackageWeight;

                if ( $remainder = fmod( $groupItemWeight, $maxPackageWeight ) )
                    $packageWeights[] = $remainder;

                foreach ( $packageWeights as $key => $weight ) {
                    $packageId = $this->generatePackageId( 'group_by_weight_' . $key, 1, 0, 0, 0, 0 );
                    $onlineRequests[] = $this->generateApiRequest( $packageId, $destination, array(0, 0, 0), $weight, 'ONLINE' );
                    $standardPostRequests[] = $this->generateApiRequest( $packageId, $destination, array(0, 0, 0), $weight, 'STANDARD POST' );
                }
            }
        }


        return array(
            'ONLINE'        =>  $onlineRequests,
            'STANDARD POST' =>  $standardPostRequests
        );
    }

    public function generatePackageId( $id, $qty, $l, $w, $h, $weight ) {
        return implode( ':', array( $id, $qty, $l, $w, $h, $weight ) );
    }

    public function prepareRequest($partRequestArray){

        $request = '<RateV4Request USERID="' . $this->userId . '">' . "\n";
        $request .= '<Revision>2</Revision>' . "\n";

        foreach ( $partRequestArray as $key => $partRequest ) {
            $request .= $partRequest;
        }
        $request .= '</RateV4Request>' . "\n";
        $request = 'API=RateV4&XML=' . str_replace( array( "\n", "\r" ), '', $request );

        return $request;
    }

    public function getUnitedResponse( $requestArray ) {

        $responseArray = array();

        foreach ( $requestArray as $service => $request ){
            $responseArray[$service] = wp_remote_post($this->endpoint,
                array(
                    'timeout'   => 70,
                    'sslverify' => true,
                    'body'      => $request
                )
            );
            if ( is_wp_error( $responseArray[$service] ) ){
                return false;
            }
        }

        $onlineResponse   = simplexml_load_string( $responseArray['ONLINE']['body'] );
        $standardResponse = simplexml_load_string( $responseArray['STANDARD POST']['body'] );

        if ( ! empty( $onlineResponse ) && ! empty ($standardResponse)) {

            foreach ( $onlineResponse as $onlinePackage ) {
                if ( property_exists($onlinePackage, 'Postage')) {
                    foreach ($onlinePackage->Postage as $postage){
                        if ( (string)$postage->attributes()->CLASSID == '4'){
                            continue 2;
                        }
                    }
                }
                foreach ( $standardResponse as $standardPackage ) {

                    if((string)$onlinePackage->attributes()->ID == (string)$standardPackage->attributes()->ID && property_exists($standardPackage, 'Postage') && (string)$standardPackage->Postage->attributes()->CLASSID == '4'){
                        $new_postage = $onlinePackage->addChild('Postage');
                        $new_postage->addAttribute( 'CLASSID', 4 );
                        $new_postage->addChild('MailService',$standardPackage->Postage->{'MailService'});
                        $new_postage->addChild('Rate', $standardPackage->Postage->{'Rate'});
                    }
                }
            }
        }

        return $onlineResponse->asXML();
    }

    public function calculate_shipping( $package = array() ) {

        $partRequestArray =  $this->getRequestsArr( $package );
        libxml_use_internal_errors( true );

        if ( $partRequestArray['ONLINE'] && $partRequestArray['STANDARD POST'] ) {

            $requestArray['ONLINE']        = $this->prepareRequest( $partRequestArray['ONLINE'] );
            $requestArray['STANDARD POST'] = $this->prepareRequest( $partRequestArray['STANDARD POST'] );

            $transient       = 'usps_simple_quote_' . md5( implode( "\n", $requestArray ));
            $cachedResponse = get_transient( $transient );

            //wc_add_notice(('USPS REQUEST: <pre>' . print_r(htmlspecialchars($requestArray['ONLINE']), true) . '</pre>'));
            //wc_add_notice(('USPS STANDARD POST REQUEST: <pre>' . print_r(htmlspecialchars($requestArray['STANDARD POST']), true) . '</pre>'));

            if ( $cachedResponse !== false ) {
                $response = $cachedResponse;

                //wc_add_notice(('CACHED RESPONSE!'));

            } else {

                $response = $this->getUnitedResponse( $requestArray );

                if ($response != false) {

                    set_transient( $transient, $response, DAY_IN_SECONDS * 5 );

                    //wc_add_notice('USPS RESPONSE: <pre style="height: 1000px; overflow:auto;">' . print_r(htmlspecialchars($response), true) . '</pre>');

                }
            }
            $xml = simplexml_load_string( '<root>' . preg_replace('/<\?xml.*\?>/', '', $response ) . '</root>' );
            if (( ! empty( $xml->RateV4Response ) )&&($uspsPackages = $xml->RateV4Response->children()))
            {
                foreach ( $uspsPackages as $uspsPackage ) {

                    // Get package data
                    list( $packageItemId, $cartItemQty, $packageLength, $packageWidth, $packageHeight, $packageWeight ) = explode( ':', $uspsPackage->attributes()->ID );
                    $quotes = $uspsPackage->children();

                    // Loop our known services
                    foreach ( $this->services as $service => $values ) {

                        $note = '';

                        $rateCode = (string) $service;
                        $rateId   = $this->id . ':' . $rateCode;
                        $rateName = (string)$this->{'t_'.strtolower($rateCode)};
                        $rateCost = null;
                        $rateCommercialCost = null;
                        $displayedRateCost = null;

                        foreach ( $quotes as $quote ) {

                            $code = strval( $quote->attributes()->CLASSID );
                            $serviceName = strip_tags( htmlspecialchars_decode( (string) $quote->{'MailService'} ) );

                            if ( $code == "0" ) {

                                if (       strstr( $serviceName, 'Postcards' ) ) {
                                    $code .= "A";

                                } elseif ( strstr( $serviceName, 'Letter' ) ) {
                                    $code .= "B";

                                } elseif ( strstr( $serviceName, 'Large Envelope' ) ) {
                                    $code .= "C";

                                } elseif ( strstr( $serviceName, 'Parcel' ) ) {
                                    $code .= "D";
                                }

                            }

                            if ( $code !== "" && in_array( $code, array_keys( $values['services'] ) ) ) {

                                $cost            = !empty( $quote->{'Rate'}) && (float)$quote->{'Rate'}!=0 ? (float)$quote->{'Rate'} * $cartItemQty : false;
                                $commercialCost  = !empty( $quote->{'CommercialRate'}) && (float)$quote->{'CommercialRate'}!=0 ? (float)$quote->{'CommercialRate'} * $cartItemQty : false;

                                $note .= 'SERVICE: ' . print_r(htmlspecialchars($values['services'][$code]), true) .
                                              '<br> COST: ' . (!empty($cost) ? print_r(htmlspecialchars($cost), true) : 'EMPTY') .
                                              '<br> COMMECIAL COST: ' . (!empty($commercialCost) ? print_r(htmlspecialchars($commercialCost), true) : 'EMPTY') .
                                              '<br><br>';

                                // Enabled check
                                if ( empty($this->subservicesEnabled[$code])||($this->subservicesEnabled[$code]=='no') )
                                    continue;

                                if ($packageLength && $packageWidth && $packageHeight ) {
                                    switch ( $code ) {
                                        // Regional rate boxes need additonal checks to deal with USPS's complex API
                                        //REALLY???

                                        /*case "47" :
                                            if ( ( $packageLength > 10 || $packageWidth > 7 || $packageHeight > 4.75 ) && ( $packageLength > 12.875 || $packageWidth > 10.9375 || $packageHeight > 2.365 ) ) {
                                                continue 2;
                                            } else {
                                                // Valid
                                                break;
                                            }
                                            break;
                                        case "49" :
                                            if ( ( $packageLength > 12 || $packageWidth > 10.25 || $packageHeight > 5 ) && ( $packageLength > 15.875 || $packageWidth > 14.375 || $packageHeight > 2.875 ) ) {
                                                continue 2;
                                            } else {
                                                // Valid
                                                break;
                                            }
                                            break;
                                        case "58" :
                                            if ( $packageLength > 14.75 || $packageWidth > 11.75 || $packageHeight > 11.5 ) {
                                                continue 2;
                                            } else {
                                                // Valid
                                                break;
                                            }
                                            break;*/

                                        // Handle first class - there are multiple d0 rates and we need to handle size retrictions because the API doesn't do this for us!
                                        case "0" :

                                            if ( strstr( $serviceName, 'Postcards' ) ) {

                                                if ( $packageLength > 6 || $packageLength < 5 ) {
                                                    continue 2;
                                                }
                                                if ( $packageWidth > 4.25 || $packageWidth < 3.5 ) {
                                                    continue 2;
                                                }
                                                if ( $packageHeight > 0.016 || $packageHeight < 0.007 ) {
                                                    continue 2;
                                                }

                                            } elseif ( strstr( $serviceName, 'Large Envelope' ) ) {

                                                if ( $packageLength > 15 || $packageLength < 11.5 ) {
                                                    continue 2;
                                                }
                                                if ( $packageWidth > 12 || $packageWidth < 6 ) {
                                                    continue 2;
                                                }
                                                if ( $packageHeight > 0.75 || $packageHeight < 0.25 ) {
                                                    continue 2;
                                                }

                                            } elseif ( strstr( $serviceName, 'Letter' ) ) {

                                                if ( $packageLength > 11.5 || $packageLength < 5 ) {
                                                    continue 2;
                                                }
                                                if ( $packageWidth > 6.125 || $packageWidth < 3.5 ) {
                                                    continue 2;
                                                }
                                                if ( $packageHeight > 0.25 || $packageHeight < 0.007 ) {
                                                    continue 2;
                                                }

                                            } elseif ( strstr( $serviceName, 'Parcel' ) ) {

                                                $girth = ( $packageWidth + $packageHeight ) * 2;

                                                if ( $girth + $packageLength > 108 ) {
                                                    continue 2;
                                                }

                                            } else {
                                                continue 2;
                                            }
                                            break;
                                    }
                                }
                                if ($cost) {
                                    if (is_null($rateCost)) {
                                        $rateCost = $cost;
                                    } elseif ($cost < $rateCost) {
                                        $rateCost = $cost;
                                    }
                                }
                                if($commercialCost) {
                                    if (is_null($rateCommercialCost)) {
                                        $rateCommercialCost = $commercialCost;
                                    } elseif ($commercialCost < $rateCommercialCost) {
                                        $rateCommercialCost = $commercialCost;
                                    }
                                }
                            }
                        }

                        if($this->commercialRate=='yes') {
                            $displayedRateCost = $rateCommercialCost ? $rateCommercialCost : $rateCost;
                        } else {
                            $displayedRateCost = $rateCost ? $rateCost : $rateCommercialCost;
                        }

                        if ( $displayedRateCost ) {
                            $this->prepareRate( $rateCode, $rateId, $rateName, $displayedRateCost );
                        }

                        if ($note) {
                            //wc_add_notice($note);
                        }

                    }

                }

            }

        }

        // Ensure rates were found for all packages
        if ( $this->foundRates ) {
            foreach ( $this->foundRates as $key => $value ) {
                if ( $value['packages'] < sizeof( $partRequestArray['ONLINE'] ) ) {
                    unset( $this->foundRates[ $key ] );
                }
            }
        }


        if ( $this->foundRates ) {

            foreach ( $this->foundRates as $key => $rate ) {
                $this->add_rate( $rate );

            }
        }
    }

    private function prepareRate( $rateCode, $rateId, $rateName, $rateCost ) {

        // Merging
        if ( isset( $this->foundRates[ $rateId ] ) ) {
            $rateCost = $rateCost + $this->foundRates[ $rateId ]['cost'];
            $packages  = 1 + $this->foundRates[ $rateId ]['packages'];
        } else {
            $packages = 1;
        }

        $this->foundRates[ $rateId ] = array(
            'id'       => $rateId,
            'label'    => $rateName,
            'cost'     => $rateCost,
            'sort'     => 999,
            'packages' => $packages
        );
    }

}

?>