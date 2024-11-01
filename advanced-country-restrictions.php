<?php

/*
  Plugin Name: WooCommerce Country Restrictions - Advanced
  Description: Restricts products and variations by country, globally or product by product.
  Version: 1.15.2
  WC requires at least: 4.0
  WC tested up to: 9.3
  Author: WP Super Admins
  Author URI: https://wpsuperadmins.com/?utm_source=wp-admin&utm_campaign=plugins-list&utm_medium=web&utm_term=country-catalogs
  Plugin URI: https://wpsuperadmins.com/plugins/woocommerce-country-catalogs-restrictions/?utm_source=wp-admin&utm_campaign=plugins-list&utm_medium=web
*/
if ( !defined( 'WCACR_MAIN_FILE' ) ) {
    define( 'WCACR_MAIN_FILE', __FILE__ );
}
if ( !defined( 'WCACR_DIST_DIR' ) ) {
    define( 'WCACR_DIST_DIR', __DIR__ );
}
if ( !defined( 'WCACR_TEXTDOMAIN' ) ) {
    define( 'WCACR_TEXTDOMAIN', 'wc_advanced_country_restrictions' );
}
require_once WCACR_DIST_DIR . '/vendor/vg-plugin-sdk/index.php';
require_once WCACR_DIST_DIR . '/inc/freemius-init.php';
if ( !class_exists( 'WC_Advanced_Country_Restrictions_Dist' ) ) {
    class WC_Advanced_Country_Restrictions_Dist {
        private static $instance = false;

        static $dir = __DIR__;

        static $version = '1.15.2';

        static $name = 'Advanced Country Restrictions';

        var $args = null;

        var $vg_plugin_sdk = null;

        private function __construct() {
        }

        /**
         * Creates or returns an instance of this class.
         */
        static function get_instance() {
            if ( null == WC_Advanced_Country_Restrictions_Dist::$instance ) {
                WC_Advanced_Country_Restrictions_Dist::$instance = new WC_Advanced_Country_Restrictions_Dist();
                WC_Advanced_Country_Restrictions_Dist::$instance->init();
            }
            return WC_Advanced_Country_Restrictions_Dist::$instance;
        }

        function remove_disallowed_files_from_list( $files ) {
            $files = array_map( 'wp_normalize_path', $files );
            preg_match( '/@fs_premium_only\\s+(.+)/', file_get_contents( __FILE__ ), $matches );
            if ( isset( $matches[1] ) ) {
                // Split the values by comma and trim whitespace
                $premium_only_values = array_filter( array_map( 'trim', explode( ',', $matches[1] ) ) );
                $premium_files_regex = '#(' . implode( '|', array_map( 'preg_quote', $premium_only_values ) ) . ')#';
                foreach ( $files as $index => $file_path ) {
                    if ( preg_match( $premium_files_regex, $file_path ) ) {
                        unset($files[$index]);
                    }
                }
            }
            return $files;
        }

        function init() {
            $this->args = array(
                'main_plugin_file'       => __FILE__,
                'show_welcome_page'      => true,
                'welcome_page_file'      => WC_Advanced_Country_Restrictions_Dist::$dir . '/views/welcome-page-content.php',
                'plugin_name'            => WC_Advanced_Country_Restrictions_Dist::$name,
                'plugin_prefix'          => 'wcacr_',
                'plugin_version'         => WC_Advanced_Country_Restrictions_Dist::$version,
                'plugin_options'         => get_option( 'vc_wc_cr_variations_per_country_tab_product_select_country_setting', false ),
                'allowed_product_types'  => array(
                    'simple'   => 'Simple',
                    'external' => 'External',
                    'grouped'  => 'Grouped',
                ),
                'default_billing_period' => WP_FS__PERIOD_ANNUALLY,
                'buy_url'                => wacr_fs()->checkout_url( WP_FS__PERIOD_ANNUALLY, true ),
                'buy_text'               => __( 'Try Premium Plugin for FREE - 7 Days', WCACR_TEXTDOMAIN ),
                'can_use_premium_code'   => wacr_fs()->can_use_premium_code__premium_only(),
            );
            update_option( 'wccr_geolocation_method', 'ip' );
            update_option( 'wccr_secondary_geolocation_method', '' );
            $this->vg_plugin_sdk = new VG_Freemium_Plugin_SDK($this->args);
            $modules = $this->get_modules_list();
            if ( empty( $modules ) ) {
                return;
            }
            // Add support for pantheon cache, which requires a special cookie name
            if ( !empty( getenv( 'PANTHEON_SITE' ) ) && !defined( 'WCACR_USER_COUNTRY_COOKIE' ) ) {
                define( 'WCACR_USER_COUNTRY_COOKIE', "STYXKEY_wcacr_user_country" );
            }
            add_action( 'plugins_loaded', array($this, 'late_init') );
            // Load all modules
            foreach ( $modules as $module ) {
                $path = ( file_exists( __DIR__ . "/modules/{$module}/{$module}.php" ) ? __DIR__ . "/modules/{$module}/{$module}.php" : __DIR__ . "/modules/{$module}/index.php" );
                if ( file_exists( $path ) ) {
                    require $path;
                }
            }
            add_action( 'before_woocommerce_init', function () {
                if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
                    \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
                }
            } );
            add_action( 'admin_menu', array($this, 'register_menu_page') );
            add_action( 'woocommerce_settings_tabs_variations_per_country_tab', array($this, 'add_action_buttons_to_global_settings'), 9 );
            // Disable WC's marketplace ads
            add_filter( 'woocommerce_allow_marketplace_suggestions', '__return_false' );
        }

        function add_action_buttons_to_global_settings() {
            ?>
			<h2><?php 
            _e( 'Country catalogs', VCWCCR_TEXT_DOMAIN );
            ?></h2>
			<?php 
            include WCACR_DIST_DIR . '/views/action-buttons.php';
        }

        function register_menu_page() {
            if ( function_exists( 'WC' ) ) {
                add_submenu_page(
                    'woocommerce',
                    $this->args['plugin_name'],
                    $this->args['plugin_name'],
                    'manage_woocommerce',
                    $this->args['plugin_prefix'] . 'welcome_page',
                    array($this->vg_plugin_sdk, 'render_welcome_page')
                );
            } else {
                add_menu_page(
                    $this->args['plugin_name'],
                    $this->args['plugin_name'],
                    'manage_options',
                    $this->args['plugin_prefix'] . 'welcome_page',
                    array($this->vg_plugin_sdk, 'render_welcome_page')
                );
            }
        }

        function late_init() {
            $inc_files = array_merge( glob( __DIR__ . '/inc/*' ), glob( __DIR__ . '/integrations/*' ), glob( __DIR__ . '/integrations/premium/*' ) );
            $inc_files = $this->remove_disallowed_files_from_list( $inc_files );
            foreach ( $inc_files as $inc_file ) {
                if ( !is_file( $inc_file ) ) {
                    continue;
                }
                require_once $inc_file;
            }
            load_plugin_textdomain( WCACR_TEXTDOMAIN, false, basename( dirname( __FILE__ ) ) . '/lang' );
        }

        /**
         * Get all modules in the folder
         * @return array
         */
        function get_modules_list() {
            $directories = glob( __DIR__ . '/modules/*', GLOB_ONLYDIR );
            if ( !empty( $directories ) ) {
                $directories = array_map( 'basename', $directories );
            }
            return $directories;
        }

        function __set( $name, $value ) {
            $this->{$name} = $value;
        }

        function __get( $name ) {
            return $this->{$name};
        }

    }

}
if ( !function_exists( 'WCACR' ) ) {
    function WCACR() {
        return WC_Advanced_Country_Restrictions_Dist::get_instance();
    }

}
WCACR();