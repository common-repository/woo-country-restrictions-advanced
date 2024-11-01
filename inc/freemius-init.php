<?php

// Create a helper function for easy SDK access.
if ( !function_exists( 'wacr_fs' ) ) {
    function wacr_fs() {
        global $wacr_fs;
        if ( !isset( $wacr_fs ) ) {
            // Activate multisite network integration.
            if ( !defined( 'WP_FS__PRODUCT_2220_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_2220_MULTISITE', true );
            }
            // Include Freemius SDK.
            require_once WCACR_DIST_DIR . '/vendor/freemius/start.php';
            $wacr_fs = fs_dynamic_init( array(
                'id'             => '2220',
                'slug'           => 'woo-country-restrictions-advanced',
                'type'           => 'plugin',
                'public_key'     => 'pk_27f5d13a51b75fcfe2d033c780f4f',
                'is_premium'     => false,
                'premium_suffix' => 'Pro',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'trial'          => array(
                    'days'               => 7,
                    'is_require_payment' => true,
                ),
                'menu'           => array(
                    'slug'       => 'wcacr_welcome_page',
                    'first-path' => 'admin.php?page=wcacr_welcome_page',
                    'support'    => false,
                    'parent'     => array(
                        'slug' => 'woocommerce',
                    ),
                ),
                'is_live'        => true,
            ) );
        }
        return $wacr_fs;
    }

    // Init Freemius.
    wacr_fs();
    // Signal that SDK was initiated.
    do_action( 'wacr_fs_loaded' );
    wacr_fs()->add_filter( 'show_trial', '__return_false' );
}