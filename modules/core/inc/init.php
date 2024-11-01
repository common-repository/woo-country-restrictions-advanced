<?php

if (!class_exists("VCWCCR_PRODUCT_COUNTRY_RESTRICTIONS")) {

	class VCWCCR_PRODUCT_COUNTRY_RESTRICTIONS {

		private static $instance;

		private function __construct() {
			$this->init();
		}

		public static function get_instance() {
			if (!isset(self::$instance)) {
				$myclass = __CLASS__;
				self::$instance = new $myclass;
			}
			return self::$instance;
		}

		public function __clone() {
			trigger_error("Clonation of this object is forbidden", E_USER_ERROR);
		}

		public function __wakeup() {
			trigger_error("You can't unserealize an instance of " . get_class($this) . " class.");
		}

		function get_files_list($directory_path, $file_format = '.php') {
			$files = glob(trailingslashit($directory_path) . '*' . $file_format);
			return $files;
		}

		public function is_rest_request() {
			$rest_prefix = function_exists('rest_get_url_prefix') ? rest_get_url_prefix() : '';

			return !empty($rest_prefix) && strpos($_SERVER['REQUEST_URI'], '/' . $rest_prefix) !== false || !empty($_GET['rest_route']) || strpos($_SERVER['REQUEST_URI'], '/doofinder/v1/' ) !== false;
		}

		public function late_init() {
			// Exit if woocommerce not found
			if (!function_exists('WC')) {
				return;
			}
			require_once plugin_dir_path(__DIR__) . "inc/globals.php";

			// Exclude common bots from geolocation by user agent.
			$ua = strtolower(wc_get_user_agent());
			if (strstr($ua, 'bot') || strstr($ua, 'spider') || strstr($ua, 'crawl')) {
				return;
			}

			// If they haven't enabled the restrictions for the rest api, bail
			if ($this->is_rest_request() && empty(get_option('wccr_enable_rest_api_restrictions')) && strpos($_SERVER['REQUEST_URI'], '/wc/store/v1/') === false ) {
				return;
			}

			//Some files should load for all users, even admins
			if (!is_admin()) {
				$files_for_all_users = $this->get_files_list(plugin_dir_path(__DIR__) . "frontend/all-users");
				$files_for_all_users = WCACR()->remove_disallowed_files_from_list( $files_for_all_users );
				foreach ($files_for_all_users as $file_path) {
					require_once $file_path;
				}
			}

			$files = $this->get_files_list(plugin_dir_path(__DIR__) . "inc/");

			if (is_admin()) {
				$files = array_merge($files, $this->get_files_list(plugin_dir_path(__DIR__) . "backend/"), $this->get_files_list(plugin_dir_path(__DIR__) . "views/backend/"));
			}

			// Exit if user role is whitelisted to let them view all products regardless of country restriction
			if (!$this->is_user_whitelisted()) {
				if (!is_admin() || wp_doing_ajax()) {
					$cache_support_file = plugin_dir_path(__DIR__) . "frontend/cache-support.php";
					if (file_exists($cache_support_file)) {
						require_once $cache_support_file;
					}
					if (apply_filters('wcacr_can_load_frontend_checks', true)) {
						$files = array_merge($files, $this->get_files_list(plugin_dir_path(__DIR__) . "frontend/"));
					}
				}
			}

			$files = WCACR()->remove_disallowed_files_from_list( $files );
			foreach ($files as $file_path) {
				require_once $file_path;
			}
		}

		function is_user_whitelisted() {
			$is_whitelisted = false;

			$apply_restrictions_during_order_creation = get_option('wccr_apply_restrictions_when_admin_creates_order');

			// Dont whitelist the current user if this is a product search when creating a new order in the backend
			if ($apply_restrictions_during_order_creation === 'yes' &&
					!empty($_GET['wcacr-apply-restrictions']) &&
					!empty($_REQUEST['action']) && $_REQUEST['action'] === 'woocommerce_json_search_products_and_variations' && wp_doing_ajax()) {
				$new_country = sanitize_text_field($_GET['wcacr-apply-restrictions']);
				setcookie(WCACR_USER_COUNTRY_COOKIE, $new_country, time() + (DAY_IN_SECONDS * 3), "/");
				$_COOKIE[WCACR_USER_COUNTRY_COOKIE] = $new_country;
				$_GET['exclude'] = vcwccr_get_disallowed_products();
				return $is_whitelisted;
			}

			// Exit if user role is whitelisted to let them view all products regardless of country restriction
			if (!$this->is_rest_request() && is_user_logged_in()) {
				$whitelisted_user_roles = get_option('wccr_whitelisted_roles');
				if (empty($whitelisted_user_roles) || !is_array($whitelisted_user_roles)) {
					$whitelisted_user_roles = array('manage_woocommerce');
				}
				foreach ($whitelisted_user_roles as $whitelisted_user_role) {

					if (current_user_can($whitelisted_user_role)) {
						$is_whitelisted = true;
						break;
					}
				}
			}
			return $is_whitelisted;
		}

		function set_body_class($classes) {
			$classes[] = 'country-' . wcacr_get_user_country();

			if (is_product()) {
				$disallowed_countries = array_filter(explode(',', get_post_meta(get_the_ID(), 'wcacr_disallowed_countries', true)));
				sort($disallowed_countries);

				$classes[] = 'disallowed-country-' . implode('-', $disallowed_countries);
				if (vcwccr_is_restricted(get_the_ID()) || !vcwccr_shop_is_available()) {
					$classes[] = 'wcacr-country-restricted';
				} else {
					$classes[] = 'wcacr-country-allowed';
				}
			}

			// We add this body class with the current date to detect if page cache is used
			$classes[] = 'wcacr-rendered-' . date('Y-m-d-H-i-s');
			$classes[] = 'wcacr-rendered-time' . current_time('timestamp');
			if (is_object(WC()->customer)) {
				$classes[] = 'wcacr-shipping-country-' . WC()->customer->get_shipping_country();
				$classes[] = 'wcacr-billing-country-' . WC()->customer->get_billing_country();
			}

			return $classes;
		}

		function wc_init() {
			if (!apply_filters('wcacr_can_load_frontend_checks', true)) {
				return;
			}
			if (!function_exists('wcacr_set_user_country')) {
				require_once plugin_dir_path(__DIR__) . "inc/globals.php";
			}

			$country = null;
			if( strpos($_SERVER['REQUEST_URI'], '/woocommerce_gpf/') !== false && ! empty( $_GET['wccr_country']) ) {
				$country_from_url = sanitize_text_field($_GET['wccr_country']);
				$cookie_expires = apply_filters('vcwccr_user_country_cookie_expires', time() + (DAY_IN_SECONDS * 3));
				setcookie(WCACR_USER_COUNTRY_COOKIE, $country_from_url, $cookie_expires, "/");
				$_COOKIE[WCACR_USER_COUNTRY_COOKIE] = $country_from_url;
				$country = $country_from_url;
			}

			wcacr_set_user_country($country);

			// If the primary method fails, use the fallback method
			if (!wcacr_get_user_country()) {
				wcacr_set_user_country($country, 'secondary');
			}
		}

		public function init() {
			if (wp_doing_cron()) {
				return;
			}
			add_action("plugins_loaded", array($this, "late_init"));
			add_action("woocommerce_init", array($this, "wc_init"));
			add_filter('body_class', array($this, "set_body_class"));
		}

	}

}

//Initializing plugin
$vcwccr_plugin = VCWCCR_PRODUCT_COUNTRY_RESTRICTIONS::get_instance();

