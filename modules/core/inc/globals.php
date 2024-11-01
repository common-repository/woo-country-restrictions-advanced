<?php

if (!function_exists('vcwccr_get_product_not_allowed_message')) {

	function vcwccr_get_product_not_allowed_message($product_title) {
		$text = get_option('wccr_restricted_product_checkout_message');
		if (empty($text)) {
			$text = __('The product: {title} is not allowed for your country', VCWCCR_TEXT_DOMAIN);
		}
		$out = '<span class="wcacr-wrong-country">' . str_replace('{title}', $product_title, $text) . '</span>';
		return $out;
	}

}
if (!function_exists('vcwccr_get_csv_string_as_array')) {

	function vcwccr_get_csv_string_as_array($csv_file, $separator = ',') {
		$out = array();
		if (empty($csv_file) || !file_exists($csv_file)) {
			return $out;
		}

		$handle = fopen($csv_file, 'r');

		$headers = fgetcsv($handle, 0, $separator);
		while ($line = fgetcsv($handle, 0, $separator)) {

			if (count($headers) > count($line)) {
				$line = array_merge($line, array_fill(0, count($headers) - count($line), ''));
			}

			if (count($headers) === count($line)) {
				$out[] = array_combine($headers, $line);
			}
		}

		fclose($handle);
		return $out;
	}

}

if (!function_exists('vcwccr_get_countries')) {

	function vcwccr_get_countries() {
		$out = WC()->countries->get_countries();
		return apply_filters('vcwccr_get_countries', $out);
	}

}
if (!function_exists('vcwccr_get_taxonomies_for_restrictions')) {

	function vcwccr_get_taxonomies_for_restrictions() {
		$out = array_merge(wc_get_attribute_taxonomy_names(), array('product_cat', 'product_tag'));
		return apply_filters('vcwccr_get_taxonomies_for_restrictions', $out);
	}

}

if (!function_exists('vcwccr_get_countries_with_continents')) {

	function vcwccr_get_countries_with_continents() {
		$cache_key = 'vcwccr_countries_continents';
		$countries_with_continents = wp_cache_get($cache_key);
		if (empty($countries_with_continents)) {

			$un_countries = vcwccr_get_csv_string_as_array(WCACR_CORE_DIR . '/assets/un-countries.csv');
			$countries_codes = vcwccr_get_csv_string_as_array(WCACR_CORE_DIR . '/assets/countries_codes_and_coordinates.csv');
			$countries_with_continents = array_unique(array_filter(wp_list_pluck($countries_codes, 'Country', 'Alpha-2 code')));

			$group = array_unique(array_filter(wp_list_pluck($un_countries, 'Region Name')));
			foreach ($group as $group_label) {
				$key = sanitize_text_field($group_label);
				$countries_with_continents['Region Name_' . $key] = sprintf(__('Continent: %s', VCWCCR_TEXT_DOMAIN), $group_label);
			}
			$group = array_unique(array_filter(wp_list_pluck($un_countries, 'Sub-region Name')));
			foreach ($group as $group_label) {
				$key = sanitize_text_field($group_label);
				$countries_with_continents['Sub-region Name_' . $key] = sprintf(__('Region name: %s', VCWCCR_TEXT_DOMAIN), $group_label);
			}
			$group = array_unique(array_filter(wp_list_pluck($un_countries, 'Intermediate Region Name')));
			foreach ($group as $group_label) {
				$key = sanitize_text_field($group_label);
				$countries_with_continents['Intermediate Region Name_' . $key] = sprintf(__('Sub Region name: %s', VCWCCR_TEXT_DOMAIN), $group_label);
			}
			wp_cache_set($cache_key, $countries_with_continents);
		}
		return apply_filters('vcwccr_get_countries_continents', $countries_with_continents);
	}

}
if (!function_exists("vcwccr_country_is_valid")) {

	//check if a country is valid or restricted depending of the operator
	function vcwccr_country_is_valid($countries, $my_country, $available_in_selected_countries) {

		$countries = array_filter((array) $countries);
		$valid = true;

		if (empty($countries) || empty($my_country)) {
			$valid = true;
		} else {

			$countries = vcwccr_replace_continent($countries);
			if (in_array($my_country, $countries, true)) {
				$valid = ( (bool) $available_in_selected_countries ) ? true : false;
			} else {
				$valid = ( (bool) $available_in_selected_countries ) ? false : true;
			}
		}

		return $valid;
	}

}


if (!function_exists("vcwccr_get_country_availability_operators")) {

	//get the country availability operators to display them in a select
	function vcwccr_get_country_availability_operators() {

		return array(
			"1" => __("Yes", VCWCCR_TEXT_DOMAIN),
			"0" => __("No", VCWCCR_TEXT_DOMAIN)
		);
	}

}

if (!function_exists("vcwccr_category_apply_restrictions_operators")) {

	//get the country availability operators to display them in a select
	function vcwccr_category_apply_restrictions_operators() {

		return array(
			"category" => __("Category only", VCWCCR_TEXT_DOMAIN),
			"products_only" => __("Products only", VCWCCR_TEXT_DOMAIN),
			"both" => __("Category and products", VCWCCR_TEXT_DOMAIN)
		);
	}

}

if (!function_exists("wcacr_save_disallowed_countries")) {

	function wcacr_save_disallowed_countries($post_id, $selected_countries, $operator = '1') {
		global $wpdb;
		$countries = new WC_Countries();
		$countries_list = $countries->get_countries();

		// WC caches the products list from the shortcode in a transient. We clear the cache when a post's country restrictions are updated
		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_wc_product_loop%' OR option_name LIKE '%_transient_timeout_wc_product_loop%' ");

		$selected_countries = vcwccr_replace_continent($selected_countries);

		if (empty($selected_countries)) {
			delete_post_meta($post_id, "wcacr_disallowed_countries");
			return;
		}

		if ($operator == "1") {

			foreach ($selected_countries as $selected_country) {

				unset($countries_list[$selected_country]);
			}
			update_post_meta($post_id, "wcacr_disallowed_countries", implode(',', vcwccr_sanitize_array(array_keys($countries_list))));
		} else {

			update_post_meta($post_id, "wcacr_disallowed_countries", implode(',', vcwccr_sanitize_array($selected_countries)));
		}
	}

}

if (!function_exists("wcacr_get_user_country")) {

	function wcacr_get_user_country() {

		$my_country = isset($_COOKIE[WCACR_USER_COUNTRY_COOKIE]) ? $_COOKIE[WCACR_USER_COUNTRY_COOKIE] : '';
		return sanitize_text_field($my_country);
	}

}

if (!function_exists("wcacr_set_user_country")) {

	function wcacr_set_user_country($my_country = null, $geolocation_method = 'primary') {
		$suggested_country = $my_country;
		$option_key = ( $geolocation_method === 'primary') ? 'wccr_geolocation_method' : 'wccr_secondary_geolocation_method';
		if (get_option($option_key, 'ip') === 'ip' && class_exists('WC_Geolocation')) {
			// We don't use HTTP_X_FORWARDED_FOR because it causes issues,
			// sites hosted on hostgator have the wrong ip here.
			if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$_SERVER['HTTP_X_FORWARDED_FOR'] = $_SERVER['REMOTE_ADDR'];
			}
			// Support for Cloudflare when the country header is disabled
			if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
				$_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
			}
			// Support for Sucuri 
			if (isset($_SERVER['HTTP_X_SUCURI_COUNTRY'])) {
				$_SERVER['HTTP_CF_IPCOUNTRY'] = $_SERVER['HTTP_X_SUCURI_COUNTRY'];
			}
			if (isset($_SERVER['HTTP_X_SUCURI_CLIENTIP'])) {
				$_SERVER["REMOTE_ADDR"] = $_SERVER['HTTP_X_SUCURI_CLIENTIP'];
			}
			if (isset($_SERVER['HTTP_X_REAL_IP'])) {
				unset($_SERVER['HTTP_X_REAL_IP']);
			}

			// FIX. In some rare cases, the server might have 2 IPs separated with commas as the user IP, so we must fix it before WC geolocates the IP
			if( ! empty($_SERVER['REMOTE_ADDR']) && strpos($_SERVER['REMOTE_ADDR'], ',') !== false ){
				$_SERVER['REMOTE_ADDR'] = strtok($_SERVER['REMOTE_ADDR'], ',');
			}

			$server_ip_key = get_option('wccr_ip_server_key');
			// empty string to use the ip detection built-in WC
			$ip = '';
			if (!empty($server_ip_key) && isset($_SERVER[$server_ip_key])) {
				$ip = $_SERVER[$server_ip_key];
			}
			// Allow to reset cookie by using ?wcacr_reset=1
			if (empty($_COOKIE[WCACR_USER_COUNTRY_COOKIE]) || !empty($_GET['wcacr_reset'])) {
				$location = WC_Geolocation::geolocate_ip($ip, true);
				$my_country = $location['country'];
			}
		}

		$my_country = apply_filters('vcwccr_set_user_country', $my_country, $geolocation_method, $suggested_country);

		if ($my_country) {
			$cookie_expires = apply_filters('vcwccr_user_country_cookie_expires', time() + (DAY_IN_SECONDS * 3));

			// Only set cookie if the value changed
			if (empty($_COOKIE[WCACR_USER_COUNTRY_COOKIE]) || $_COOKIE[WCACR_USER_COUNTRY_COOKIE] !== $my_country) {
				setcookie(WCACR_USER_COUNTRY_COOKIE, $my_country, $cookie_expires, "/");
				$_COOKIE[WCACR_USER_COUNTRY_COOKIE] = $my_country;
			}
		}
		do_action('vcwccr_after_set_user_country', $my_country, $geolocation_method, $suggested_country);
	}

}

if (!function_exists("vcwccr_sanitize_array")) {

	function vcwccr_sanitize_array($array) {

		if (!empty($array)) {
			$array = array_filter(array_map("sanitize_text_field", $array));
		}

		return $array;
	}

}

if (!function_exists("vcwccr_is_restricted")) {

	function vcwccr_is_restricted($id, $type = "product_or_variation") {

		//getting current country	
		$my_country = wcacr_get_user_country();
		if (empty($my_country)) {
			return false;
		}
		if ($type === 'product_or_variation' && is_user_logged_in() && (int) get_post_field('post_author', $id) === get_current_user_id()) {
			return false;
		}
		$cache_key = implode('-', array('wcacr', $id, $type, $my_country));
		$cached_value = wp_cache_get($cache_key);
		if (is_string($cached_value)) {
			return $cached_value === 'yes';
		}

		//getting restrictions if there are
		if ($type == "category") {
			$selected_countries = vcwccr_get_selected_countries($id, "category");
			$available = get_term_meta($id, "vcwccr_availability_operator", true);
		} else {
			$selected_countries = vcwccr_get_selected_countries($id);
			$available = intval(get_post_meta($id, "vcwccr_availability_operator", true));
		}

		$out = !vcwccr_country_is_valid($selected_countries, $my_country, $available);
		wp_cache_set($cache_key, $out ? 'yes' : 'no');
		return $out;
	}

}

if (!function_exists("vcwccr_shop_is_available")) {

	function vcwccr_shop_is_available() {

		$my_country = wcacr_get_user_country();
		$allow_on_unknown_country = empty(get_option('wccr_geolocation_failed_action'));

		if (empty($my_country)) {
			return $allow_on_unknown_country;
		}

		$cache_key = implode('-', array('wcacr', 'shop', 'available', $my_country));
		$cached_value = wp_cache_get($cache_key);
		if (is_string($cached_value)) {
			return $cached_value === 'yes';
		}

		$products_selected_countries_setting = get_option("vcwccr_selected_countries");
		$products_selected_operator_setting = get_option("vcwccr_availability_operator");

		$products_selected_countries = apply_filters("vcwccr_selected_countries_before_shop_availability_check", $products_selected_countries_setting);

		$products_selected_operator = apply_filters("vcwccr_availability_operator_before_shop_availability_check", $products_selected_operator_setting);

		$out = vcwccr_country_is_valid($products_selected_countries, $my_country, intval($products_selected_operator));
		wp_cache_set($cache_key, $out ? 'yes' : 'no');
		return $out;
	}

}

if (!function_exists("vcwccr_get_disallowed_products")) {

	function vcwccr_get_disallowed_products() {

		global $wpdb;

		$my_country = esc_sql(wcacr_get_user_country());

		if (empty($my_country)) {
			return;
		}


		$cache_key = 'wcr_disallowed_posts_' . $my_country;
		$disallowed_products = wp_cache_get($cache_key);

		if (!is_array($disallowed_products)) {

			$un_countries = vcwccr_get_csv_string_as_array(WCACR_CORE_DIR . '/assets/un-countries.csv');
			$countries_codes = vcwccr_get_csv_string_as_array(WCACR_CORE_DIR . '/assets/countries_codes_and_coordinates.csv');

			$country_raw = wp_list_filter($countries_codes, array('Alpha-2 code' => $my_country));
			$un_country = false;

			if (!empty($country_raw)) {
				$country = current($country_raw);
				$un_country_raw = wp_list_filter($un_countries, array('ISO-alpha3 Code' => $country['Alpha-3 code']));
			}
			if (!empty($un_country_raw)) {
				$un_country = current($un_country_raw);
				$region_name = esc_sql($un_country['Region Name']);
				$subregion_name = esc_sql($un_country['Sub-region Name']);
				$intermediate_region_name = esc_sql($un_country['Intermediate Region Name']);
			} else {
				$region_name = '';
				$subregion_name = '';
				$intermediate_region_name = '';
			}

			$checks = array(" meta_value LIKE '%$my_country%' ");
			if (!empty($region_name)) {
				$checks[] = " meta_value LIKE '%Region Name_$region_name%' ";
			}
			if (!empty($subregion_name)) {
				$checks[] = " meta_value LIKE '%Sub-region Name_$subregion_name%' ";
			}
			if (!empty($intermediate_region_name)) {
				$checks[] = " meta_value LIKE '%Intermediate Region Name_$intermediate_region_name%' ";
			}

			$sql = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'wcacr_disallowed_countries' AND (" . implode(' OR ', $checks) . ") ";
			$disallowed_products = array_map('intval', $wpdb->get_col($sql));

			//Removing current user products ids from disallowed products ids, because makes no sense that users can't see their own products
			$disallowed_products = vcwccr_remove_current_user_products_from_disallowed_products($disallowed_products);

			wp_cache_set($cache_key, $disallowed_products);
		}

		return $disallowed_products;
	}

}

if (!function_exists('vcwccr_remove_current_user_products_from_disallowed_products')) {

	function vcwccr_remove_current_user_products_from_disallowed_products($product_ids) {

		global $wpdb;

		$current_user_id = get_current_user_id();

		if (empty($current_user_id) || empty($product_ids)) {
			return $product_ids;
		}

		$in_format = implode(',', array_fill(0, count($product_ids), '%d'));
		$sql_products_ids = "SELECT ID FROM $wpdb->posts WHERE ID IN($in_format) AND post_author != %d";

		$sql_values = $product_ids;
		$sql_values[] = $current_user_id;

		$sql_products_ids_prepared = $wpdb->prepare($sql_products_ids, $sql_values);
		$products_ids_raw = $wpdb->get_col($sql_products_ids_prepared);
		$product_ids_without_current_user_products_ids = array_map('intval', $products_ids_raw);

		return $product_ids_without_current_user_products_ids;
	}

}



if (!function_exists("vcwccr_get_attributes")) {

	//gets the list of attributes
	function vcwccr_get_attributes() {

		global $wpdb;

		$table = $wpdb->postmeta;

		$sql = $wpdb->prepare("SELECT meta_value FROM $table WHERE meta_key = %s", array("_product_attributes"));

		$custom_attributes_raw = $wpdb->get_results($sql);

		$custom_attributes = vcwccr_parse_attributes($custom_attributes_raw);

		$global_attributes_raw = $wpdb->get_col("SELECT CONCAT(tax.taxonomy, ':', term.slug, ':', term.name) FROM $wpdb->term_taxonomy as tax LEFT JOIN $wpdb->terms as term ON term.term_id = tax.term_id WHERE tax.taxonomy LIKE 'pa_%'");

		$global_attributes = array();

		foreach ($global_attributes_raw as $global_attribute) {

			$global_attribute_exploded = explode(":", $global_attribute);

			$global_attribute_key = $global_attribute_exploded[0] . ':' . $global_attribute_exploded[1];  //1 term slug

			$global_attribute_text = $global_attribute_exploded[0] . ':' . $global_attribute_exploded[2];  //2 term name

			$global_attributes[$global_attribute_key] = $global_attribute_text;
		}

		$results = array_filter(array_unique(array_merge($custom_attributes, $global_attributes)));

		return apply_filters('wcacr_attribute_options', $results);
	}

}

if (!function_exists("vcwccr_parse_attributes")) {

	//auxiliar function that return the options to show them in the select multiple
	function vcwccr_parse_attributes($results) {

		$options = array();

		foreach ($results as $result) {
			if (empty($result) || empty($result->meta_value)) {
				continue;
			}

			$current_attributes = unserialize($result->meta_value);
			if (!is_array($current_attributes)) {
				continue;
			}

			$attributesKeys = array_keys($current_attributes);

			foreach ($attributesKeys as $attributeKey) {

				if ($current_attributes[$attributeKey]["is_taxonomy"]) {
					continue;
				}
				$current_parsed_attribute_values = vcwccr_parse_attribute_values($attributeKey, explode("|", $current_attributes[$attributeKey]["value"]));
				$options = array_merge($options, $current_parsed_attribute_values);
			}
		}

		$options = array_unique($options);

		asort($options);

		return $options;
	}

}

if (!function_exists("vcwccr_parse_attribute_values")) {

	//second auxiliar function that prepares and get the attributes 
	function vcwccr_parse_attribute_values($attribute_key, $attribute_values) {

		$attribute_values_parsed = array();

		foreach ($attribute_values as $attribute_value) {
			$option = $attribute_key . ":" . trim($attribute_value);
			$attribute_values_parsed[$option] = $option;
		}

		return $attribute_values_parsed;
	}

}

function vcwccr_replace_continent($selected_countries) {

	if (!apply_filters('vcwccr_allow_to_replace_continent', true, $selected_countries)) {
		return $selected_countries;
	}

	$un_countries = vcwccr_get_csv_string_as_array(WCACR_CORE_DIR . '/assets/un-countries.csv');
	$countries_codes = wp_list_pluck(vcwccr_get_csv_string_as_array(WCACR_CORE_DIR . '/assets/countries_codes_and_coordinates.csv'), 'Alpha-2 code', 'Alpha-3 code');

	$out = array();
	foreach ($selected_countries as $index => $selected_country) {
		if (strpos($selected_country, 'Region Name_') === 0) {
			unset($selected_countries[$index]);
			$region = str_replace('Region Name_', '', $selected_country);
			$countries_iso3 = wp_list_pluck(wp_list_filter($un_countries, array('Region Name' => $region)), 'ISO-alpha3 Code');
		} elseif (strpos($selected_country, 'Sub-region Name') === 0) {
			unset($selected_countries[$index]);
			$region = str_replace('Sub-region Name_', '', $selected_country);
			$countries_iso3 = wp_list_pluck(wp_list_filter($un_countries, array('Sub-region Name' => $region)), 'ISO-alpha3 Code');
		} elseif (strpos($selected_country, 'Intermediate Region Name') === 0) {
			unset($selected_countries[$index]);
			$region = str_replace('Intermediate Region Name_', '', $selected_country);
			$countries_iso3 = wp_list_pluck(wp_list_filter($un_countries, array('Intermediate Region Name' => $region)), 'ISO-alpha3 Code');
		} else {
			$out[] = $selected_country;
		}

		if (!empty($countries_iso3)) {
			$out = array_merge($out, array_values(array_intersect_key($countries_codes, array_flip($countries_iso3))));
		}
	}

	sort($out);
	return array_filter(array_unique($out));
}

if (!function_exists('vcwccr_get_selected_countries')) {

	function vcwccr_get_selected_countries($product_id, $type = "product_or_variation") {

		if ($type == "category") {
			$selected_countries = get_term_meta($product_id, 'vcwccr_selected_countries', true);
		} else {
			$selected_countries = get_post_meta($product_id, 'vcwccr_selected_countries', true);
		}

		if (is_string($selected_countries)) {
			$selected_countries = array_map('trim', explode(',', $selected_countries));
		}
		if (empty($selected_countries)) {
			$selected_countries = array();
		}

		return array_filter($selected_countries);
	}

}


