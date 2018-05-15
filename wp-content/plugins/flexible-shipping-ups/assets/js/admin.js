jQuery(document).ready(function(){
    var ajax_data = {
        action  		: 'flexible_shipping_ups_api_status',
        security        : flexible_shipping_ups_admin.ajax_nonce
    };
    jQuery.ajax({
        url		: flexible_shipping_ups_admin.ajax_url,
        data	: ajax_data,
        method	: 'POST',
        dataType: 'JSON',
        success: function( data ) {
            jQuery( '#woocommerce_flexible_shipping_ups_api_status' ).html( data.status );
            jQuery( '#woocommerce_flexible_shipping_ups_api_status' ).addClass( data.class_name );
        },
        error: function ( xhr, ajaxOptions, thrownError ) {
            jQuery( '#woocommerce_flexible_shipping_ups_api_status' ).html( thrownError );
        },
        complete: function() {
        }
    });

    jQuery('#woocommerce_flexible_shipping_ups_custom_origin').change(function(){
        if ( jQuery(this).is(':checked') ) {
            jQuery('#woocommerce_flexible_shipping_ups_origin_address').closest('tr').show();
            jQuery('#woocommerce_flexible_shipping_ups_origin_address').attr('required',true);
            jQuery('#woocommerce_flexible_shipping_ups_origin_city').closest('tr').show();
            jQuery('#woocommerce_flexible_shipping_ups_origin_city').attr('required',true);
            jQuery('#woocommerce_flexible_shipping_ups_origin_postcode').closest('tr').show();
            jQuery('#woocommerce_flexible_shipping_ups_origin_postcode').attr('required',true);
            jQuery('#woocommerce_flexible_shipping_ups_origin_country').closest('tr').show();
            jQuery('#woocommerce_flexible_shipping_ups_origin_country').attr('required',true);
        }
        else {
            jQuery('#woocommerce_flexible_shipping_ups_origin_address').closest('tr').hide();
            jQuery('#woocommerce_flexible_shipping_ups_origin_address').attr('required',false);
            jQuery('#woocommerce_flexible_shipping_ups_origin_city').closest('tr').hide();
            jQuery('#woocommerce_flexible_shipping_ups_origin_city').attr('required',false);
            jQuery('#woocommerce_flexible_shipping_ups_origin_postcode').closest('tr').hide();
            jQuery('#woocommerce_flexible_shipping_ups_origin_postcode').attr('required',false);
            jQuery('#woocommerce_flexible_shipping_ups_origin_country').closest('tr').hide();
            jQuery('#woocommerce_flexible_shipping_ups_origin_country').attr('required',false);
        }
    });
    if ( jQuery('#woocommerce_flexible_shipping_ups_custom_origin').length ) {
        jQuery('#woocommerce_flexible_shipping_ups_custom_origin').change();
    }

    jQuery('#woocommerce_flexible_shipping_ups_custom_services').change(function(){
        if ( jQuery(this).is(':checked') ) {
            jQuery('#woocommerce_flexible_shipping_ups_services').closest('tr').show();
            jQuery('.woocommerce_flexible_shipping_ups_service_name').prop('required',true);
        }
        else {
            jQuery('#woocommerce_flexible_shipping_ups_services').closest('tr').hide();
            jQuery('.woocommerce_flexible_shipping_ups_service_name').prop('required',false);
        }
    });
    if ( jQuery('#woocommerce_flexible_shipping_ups_custom_services').length ) {
        jQuery('#woocommerce_flexible_shipping_ups_custom_services').change();
    }

    jQuery('.checkbox-select-all-services').change(function(){
        if ( jQuery(this).is(':checked') ) {
            jQuery('.checkbox-select-service').prop('checked',true);
        }
        else {
            jQuery('.checkbox-select-service').prop('checked',false);
        }
    });

    jQuery('#woocommerce_flexible_shipping_ups_fallback').change(function(){
        if ( jQuery(this).is(':checked') ) {
            jQuery('#woocommerce_flexible_shipping_ups_fallback_cost').closest('tr').show();
            jQuery('#woocommerce_flexible_shipping_ups_fallback_cost').attr('required',true);
        }
        else {
            jQuery('#woocommerce_flexible_shipping_ups_fallback_cost').closest('tr').hide();
            jQuery('#woocommerce_flexible_shipping_ups_fallback_cost').attr('required',false);
        }
    });
    if ( jQuery('#woocommerce_flexible_shipping_ups_fallback').length ) {
        jQuery('#woocommerce_flexible_shipping_ups_fallback').change();
    }

    jQuery('#woocommerce_flexible_shipping_ups_units').select2( {
        minimumResultsForSearch: -1
    } );
    jQuery('#woocommerce_flexible_shipping_ups_origin_country').select2();

})
