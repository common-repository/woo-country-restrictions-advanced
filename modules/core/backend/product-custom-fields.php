<?php

if ( !function_exists( "vcwccr_add_product_custom_fields" ) ) {
    add_action( 'woocommerce_product_options_general_product_data', 'vcwccr_add_product_custom_fields' );
    function vcwccr_add_product_custom_fields() {
        global $post;
        //getting woocommerce countries
        $countries = vcwccr_get_countries_with_continents();
        $selected_countries = vcwccr_get_selected_countries( $post->ID );
        //adding custom fields
        ?><div class="options_group country-restrictions-general-wrapper">
			<h2>
				<strong> 
					<?php 
        echo __( 'Country restrictions', VCWCCR_TEXT_DOMAIN );
        ?>  
				</strong>
			</h2>
			<?php 
        do_action( 'wcacr_before_product_custom_fields', $selected_countries, $post->ID );
        woocommerce_wp_select( array(
            'id'                => 'product_selected_countries[]',
            'label'             => __( "Selected countries", VCWCCR_TEXT_DOMAIN ),
            "class"             => "wc-enhanced-select product-selected-countries-field",
            'options'           => $countries,
            'value'             => $selected_countries,
            'custom_attributes' => array(
                "multiple" => "multiple",
            ),
        ) );
        woocommerce_wp_select( array(
            'id'          => 'product_country_availability_operator',
            'label'       => __( "Available in selected countries", VCWCCR_TEXT_DOMAIN ),
            "default"     => "1",
            'desc_tip'    => 'true',
            'description' => __( 'Establishes if the item is available or not in the selected countries', VCWCCR_TEXT_DOMAIN ),
            'options'     => vcwccr_get_country_availability_operators(),
            'value'       => get_post_meta( $post->ID, "vcwccr_availability_operator", true ),
        ) );
        // We use this function for the coupons metabox as well
        do_action( 'wcacr_after_' . $post->post_type . '_custom_fields', $selected_countries, $post->ID );
        ?> </div> <?php 
    }

}
if ( !function_exists( "vcwccr_save_product_custom_fields" ) ) {
    add_action(
        'woocommerce_process_product_meta',
        'vcwccr_save_product_custom_fields',
        10,
        1
    );
    function vcwccr_save_product_custom_fields(  $post_id, $data = null  ) {
        //saving availability
        if ( !$data ) {
            $data = $_POST;
        }
        if ( !isset( $data['product_country_availability_operator'] ) ) {
            return;
        }
        if ( !isset( $data['product_selected_countries'] ) ) {
            $data['product_selected_countries'] = '';
        }
        if ( is_numeric( $data['product_country_availability_operator'] ) ) {
            update_post_meta( $post_id, 'vcwccr_availability_operator', (int) $data['product_country_availability_operator'] );
        }
        //saving countries list
        $product_selected_countries = vcwccr_sanitize_array( $data['product_selected_countries'] );
        if ( !empty( $product_selected_countries ) ) {
            update_post_meta( $post_id, 'vcwccr_selected_countries', implode( ',', $product_selected_countries ) );
        } else {
            update_post_meta( $post_id, 'vcwccr_selected_countries', array() );
        }
    }

}
if ( !function_exists( 'vcwccr_product_countries_updated' ) ) {
    add_action(
        'updated_post_meta',
        'vcwccr_product_countries_updated',
        10,
        4
    );
    add_action(
        'added_post_meta',
        'vcwccr_product_countries_updated',
        10,
        4
    );
    function vcwccr_product_countries_updated(
        $meta_id,
        $object_id,
        $meta_key,
        $meta_value
    ) {
        if ( !in_array( get_post_type( $object_id ), array('product', 'shop_coupon') ) || !in_array( $meta_key, array('vcwccr_selected_countries', 'vcwccr_availability_operator') ) ) {
            return;
        }
        $product_selected_countries = vcwccr_get_selected_countries( $object_id );
        $product_country_availability_operator = get_post_meta( $object_id, 'vcwccr_availability_operator', true );
        wcacr_save_disallowed_countries( $object_id, $product_selected_countries, $product_country_availability_operator );
    }

}