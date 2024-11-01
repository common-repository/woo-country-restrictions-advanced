<?php

if ( !function_exists( 'wcacr_teaser_global_settings_on_product' ) ) {
    add_action( 'wcacr_after_product_custom_fields', 'wcacr_teaser_global_settings_on_product' );
    add_action( 'wcacr_after_variation_custom_fields', 'wcacr_teaser_global_settings_on_product' );
    function wcacr_teaser_global_settings_on_product() {
        ?>
			<?php 
        printf( __( '<p>Advanced features: You can apply Country Restrictions to all products and variations at once using the Global Settings.<br/>More geolocation options: Display country selector in the header, Use shipping/billing country<br/>More restriction options: Show products and disable "add to cart" and prices <a href="%s" target="_blank" class="wcacr-go-premium-link"> Go Premium</a></p>', WCACR_TEXTDOMAIN ), WCACR()->args['buy_url'] );
        ?>
			<?php 
    }

}
if ( !function_exists( "vcwccr_add_product_category_restrictions_form_fields" ) ) {
    add_action( "product_cat_add_form_fields", "vcwccr_add_product_category_restrictions_form_fields", 50 );
    function vcwccr_add_product_category_restrictions_form_fields(  $term  ) {
        $countries_obj = new WC_Countries();
        $countries = $countries_obj->__get( 'countries' );
        ?>
			<h3><?php 
        _e( "Country restrictions", VCWCCR_TEXT_DOMAIN );
        ?></h3>
			<?php 
        woocommerce_wp_select( array(
            'id'                => 'product_selected_countries[]',
            'label'             => sprintf( __( "Select countries. (<b>Pro</b> <a href='%s' target='_blank'>Start free trial</a>)", VCWCCR_TEXT_DOMAIN ), WCACR()->args['buy_url'] ),
            "class"             => "wc-enhanced-select",
            'options'           => $countries,
            'custom_attributes' => array(
                "multiple" => "multiple",
                'disabled' => 'disabled',
            ),
        ) );
        woocommerce_wp_select( array(
            'id'                => 'product_country_availability_operator',
            'label'             => sprintf( __( "Available in selected countries. (<b>Pro</b> <a href='%s' target='_blank'>Start free trial</a>)", VCWCCR_TEXT_DOMAIN ), WCACR()->args['buy_url'] ),
            "default"           => "1",
            'options'           => vcwccr_get_country_availability_operators(),
            'custom_attributes' => array(
                'disabled' => 'disabled',
            ),
        ) );
        woocommerce_wp_select( array(
            'id'                => 'apply_this_to',
            'label'             => sprintf( __( "Apply this to. (<b>Pro</b> <a href='%s' target='_blank'>Start free trial</a>)", VCWCCR_TEXT_DOMAIN ), WCACR()->args['buy_url'] ),
            "default"           => "category",
            'desc_tip'          => 'false',
            'options'           => vcwccr_category_apply_restrictions_operators(),
            'custom_attributes' => array(
                'disabled' => 'disabled',
            ),
        ) );
    }

}
if ( !function_exists( "vcwccr_edit_product_category_restrictions_fields" ) ) {
    add_action( "product_cat_edit_form_fields", "vcwccr_edit_product_category_restrictions_fields", 50 );
    function vcwccr_edit_product_category_restrictions_fields(  $term  ) {
        $term_id = $term->term_id;
        $countries_obj = new WC_Countries();
        $countries = $countries_obj->__get( 'countries' );
        ?> 
			<tr class = "form-field"> 
				<th scope = "row">
					<h3><?php 
        _e( "Country restrictions", VCWCCR_TEXT_DOMAIN );
        ?></h3>
					<label><?php 
        printf( __( "Select countries. (<b>Pro</b> <a href='%s' target='_blank'>Start free trial</a>)", VCWCCR_TEXT_DOMAIN ), WCACR()->args['buy_url'] );
        ?></label>
				</th>
				<td>
					<?php 
        woocommerce_wp_select( array(
            'id'                => 'product_selected_countries[]',
            "class"             => "wc-enhanced-select",
            'options'           => $countries,
            'custom_attributes' => array(
                "multiple" => "multiple",
                'disabled' => 'disabled',
            ),
        ) );
        ?>
				</td>
			</tr>

			<tr class = "form-field"> 
				<th scope = "row">
					<label><?php 
        printf( __( "Available in selected countries. (<b>Pro</b> <a href='%s' target='_blank'>Start free trial</a>)", VCWCCR_TEXT_DOMAIN ), WCACR()->args['buy_url'] );
        ?></label>
				</th>
				<td>		
					<?php 
        woocommerce_wp_select( array(
            'id'                => 'product_country_availability_operator',
            "default"           => "1",
            'options'           => vcwccr_get_country_availability_operators(),
            'custom_attributes' => array(
                'disabled' => 'disabled',
            ),
        ) );
        ?>
				</td>
			</tr>

			<tr class = "form-field"> 
				<th scope = "row">
					<label><?php 
        printf( __( "Apply this to. (<b>Pro</b> <a href='%s' target='_blank'>Start free trial</a>)", VCWCCR_TEXT_DOMAIN ), WCACR()->args['buy_url'] );
        ?></label>
				</th>
				<td>		
					<?php 
        woocommerce_wp_select( array(
            'id'                => 'apply_this_to',
            "default"           => "category",
            'desc_tip'          => 'false',
            'options'           => vcwccr_category_apply_restrictions_operators(),
            'custom_attributes' => array(
                'disabled' => 'disabled',
            ),
        ) );
        ?>
				</td>
			</tr>
			<?php 
    }

}