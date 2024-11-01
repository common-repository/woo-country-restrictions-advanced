<?php

/**
 * @wordpress-plugin
 * Plugin Name:         vegacorp woocommerce product country restrictions
 * Plugin URI:          
 * Description:         Restricts product and product variations by country
 * Version:             1.0.0
 * Author:              VegaCorp
 * Author URI:          
 * License:             GPL2
 * License URI:         
 * Text Domain:         text-domain
 * Domain Path:         /languages
 */
if (!defined('WCACR_CORE_DIR')) {
	define('WCACR_CORE_DIR', __DIR__);
}

if (!defined('WCACR_USER_COUNTRY_COOKIE')) {
	define('WCACR_USER_COUNTRY_COOKIE', "wcacr_user_country");
}

if (!defined('VCWCCR_TEXT_DOMAIN')) {
	define('VCWCCR_TEXT_DOMAIN', "vegacorp_woocommerce_country_restrictions");
}

if (!defined('VCWCCR_PATH')) {
	define( 'VCWCCR_PATH', plugin_dir_path(__FILE__) );
}

if (!defined('VCWCCR_URL')) {
	define( 'VCWCCR_URL', plugin_dir_url(__FILE__) );
}

require_once VCWCCR_PATH . "inc/init.php";


