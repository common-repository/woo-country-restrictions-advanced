<?php
if (!function_exists("vcwccr_add_variation_custom_fields")) {

	add_action('woocommerce_product_after_variable_attributes', "vcwccr_add_variation_custom_fields", 10, 3);

	function vcwccr_add_variation_custom_fields($loop, $variation_data, $variation) {

		$countries = vcwccr_get_countries_with_continents();

		$selected_countries = get_post_meta($variation->ID, "vcwccr_selected_countries", true);

		//adding custom fields and buttons
		$idSelect = 'variationSelectedCountries[' . $variation->ID . '][]';
		?>
		<div class = 'options_group country-restrictions-variation-wrapper'>
			<hr>
			<div>
				<strong>
					<?php __("Country restrictions", VCWCCR_TEXT_DOMAIN) ?>
				</strong>
			</div>
			<?php
			do_action('wcacr_before_variation_custom_fields', $selected_countries);

			woocommerce_wp_select(
					array(
						"id" => $idSelect,
						'label' => __("Selected countries", VCWCCR_TEXT_DOMAIN),
						"class" => "wc-enhanced-select",
						'options' => $countries,
						'value' => $selected_countries,
						'custom_attributes' => array("multiple" => "multiple")
					)
			);

			woocommerce_wp_select(
					array(
						'id' => 'variation_country_availability_operator[' . $variation->ID . ']',
						'label' => __('Available in selected countries:', VCWCCR_TEXT_DOMAIN),
						'desc_tip' => 'true',
						'description' => __('Establishes if the variation is available or not in the selected countries', VCWCCR_TEXT_DOMAIN),
						'default' => '1',
						'options' => vcwccr_get_country_availability_operators(),
						'value' => get_post_meta($variation->ID, "vcwccr_availability_operator", true)
					)
			);

			do_action('wcacr_after_variation_custom_fields', $selected_countries);
			?></div><?php
	}

}

if (!function_exists("vcwccr_save_variation_custom_fields")) {

	add_action('woocommerce_save_product_variation', "vcwccr_save_variation_custom_fields", 10, 2);

	function vcwccr_save_variation_custom_fields($post_id) {

		//saving fields 	
		if (!isset($_POST['variation_country_availability_operator'][$post_id])) {
			return;
		}

		if (empty($_POST['variationSelectedCountries']) || empty($_POST['variationSelectedCountries'][$post_id])) {
			$variation_selected_countries = '';
		} else {
			$variation_selected_countries = vcwccr_sanitize_array($_POST['variationSelectedCountries'][$post_id]);
		}
		update_post_meta($post_id, 'vcwccr_selected_countries', $variation_selected_countries);

		if (!empty($_POST['variation_country_availability_operator'][$post_id]) ||
				$_POST['variation_country_availability_operator'][$post_id] === "0") {

			$variation_country_availability_operator = $_POST['variation_country_availability_operator'][$post_id];
			$variation_country_availability_operator = sanitize_text_field($variation_country_availability_operator);

			update_post_meta($post_id, 'vcwccr_availability_operator', $variation_country_availability_operator);
		}
	}

}