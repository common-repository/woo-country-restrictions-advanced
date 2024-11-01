<?php

include WCACR_DIST_DIR . '/views/action-buttons.php';
$steps = array();
if ( !class_exists( 'WooCommerce' ) ) {
    $steps['required_plugin_wc'] = '<p>' . __( 'Please install the free plugin <b>"WooCommerce"</b>', WCACR_TEXTDOMAIN ) . '</p>';
}
if ( function_exists( 'wcacr_get_user_country' ) && !wcacr_get_user_country() ) {
    $steps['local_ip'] = __( '<p>Local server detected. On local servers your visitor IP is 127.0.0.1, which is not found in the geolocation database. You can fix it easily, add define("WCACR_FORCE_IP", "add your public ip here"); to your wp-config.php. This is not needed when using a real/online server.</p>', WCACR_TEXTDOMAIN );
}
$steps['use_guest_user'] = __( '<p><b>Important</b>. We use the country restrictions only for non-admin users. Please visit your store as a real customer to test the country restrictions (without log in or logged in as customer).</p>', WCACR_TEXTDOMAIN );
if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
    $steps['no_cache'] = __( '<p>We detected that you are using Cache. The premium version supports all the cache plugins and systems. You need to deactivate the cache or use the premium version to avoid issues like: the page for country A appearing for country B.</p>', WCACR_TEXTDOMAIN );
}
$steps['individual_settings'] = '<p>' . sprintf( __( 'You can hide individual products from selected countries. You have the option on the product editor when you create/edit the product. <a href="%s" target="_blank" class="button">Open list of products</a>', $this->textname ), esc_url( admin_url( 'edit.php?post_type=product' ) ) ) . '</p>';
$steps[] = sprintf( __( '<p>Features in the free version:<br>You need to edit each product individually to hide them for specific countries. Global settings not available.<br>The user country is auto detected by IP.<br/>You can hide these product types by country: %s</p>', WCACR_TEXTDOMAIN ), implode( ', ', $this->settings['allowed_product_types'] ) );
$steps[] = __( '<p>Go create or edit some products :)</p>', WCACR_TEXTDOMAIN );
$steps = apply_filters( 'vg_admin_to_frontend/welcome_steps', $steps );
if ( !empty( $steps ) ) {
    echo '<ol class="steps">';
    foreach ( $steps as $key => $step_content ) {
        ?>
		<li><?php 
        echo $step_content;
        ?></li>		
		<?php 
    }
    echo '</ol>';
}
?>
	<hr/>
	<h3><?php 
_e( 'Go Premium', WCACR_TEXTDOMAIN );
?></h3>
	<ul class="plain-list">
		<li><p><?php 
_e( 'Create different shop catalogs for different countries', WCACR_TEXTDOMAIN );
?></p></li>
		<li><p><?php 
_e( 'Advanced Geolocation: Auto detect country by IP, show a country selector in the header, OR use the shipping/billing country', WCACR_TEXTDOMAIN );
?></p></li>
		<li><p><?php 
_e( 'Advanced restriction logic: Hide products from the catalog OR show products in the catalog and hide the prices / disable "add to cart"', WCACR_TEXTDOMAIN );
?></p></li>
		<li><p><?php 
_e( 'Restrict coupons by country. You can create coupons for specific countries, continents, or regions. I.e. Coupons for Europe, Canada, etc.', WCACR_TEXTDOMAIN );
?></p></li>
		<li><p><?php 
_e( 'Restrict category pages by country. For example, Hide the category "Music" for Canada and automatically remove it from the menus, categories lists, and widgets', WCACR_TEXTDOMAIN );
?></p></li>
		<li><p><?php 
_e( 'Restrict product attributes by country. For example, Small pants for USA, Large pants for Canada; or print pictures for USA and downloadable pictures for the rest of the world', WCACR_TEXTDOMAIN );
?></p></li>
		<li><p><?php 
_e( 'You can hide "variable products" by countries', WCACR_TEXTDOMAIN );
?></p></li>
		<li><p><?php 
_e( 'You can hide individual "product variations" by countries. For example, Small pants for USA, Large pants for Canada; or print pictures for USA and downloadable pictures for the rest of the world', WCACR_TEXTDOMAIN );
?></p></li>
		<li><p><?php 
_e( 'Bulk Edit Products by Category. For example, Hide all products under the category "Music" for Canada', WCACR_TEXTDOMAIN );
?></p></li>
		<li><p><?php 
_e( 'Global settings page - Apply country restrictions to all products automatically. No need to edit every product manually.', WCACR_TEXTDOMAIN );
?></p></li>
		<li><p><?php 
_e( 'Global settings - Apply country restrictions to all products variations matching specific attributes automatically (no need to edit each product and variation)', WCACR_TEXTDOMAIN );
?></p></li>
	</ul>

	<p style="text-align: center;"><?php 
_e( '<b>Money back guarantee.</b> We´ll give you a refund if the plugin doesn´t work.', WCACR_TEXTDOMAIN );
?></p>
	<?php 
include WCACR_DIST_DIR . '/views/action-buttons.php';
?>
<style>
	.plain-list {
		list-style: inherit;
	}
	.plain-list li {
		font-size: 18px;
	}
</style>
<script>
	jQuery('.install-plugin-trigger').click(function (e) {
		return !window.open(this.href, 'Install plugin', 'width=500,height=500');
	});
</script>
<hr>
<p><?php 
echo sprintf( __( 'For developers. <a href="%s" class="button">Print debugging info</a>', $this->textname ), esc_url( add_query_arg( 'wcacr_debug', 1 ) ) );
?></p>

<?php 
if ( !empty( $_GET['wcacr_debug'] ) ) {
    global $wpdb;
    $all_options = $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'wccr_%' LIMIT 50", ARRAY_A );
    echo '<pre style="text-align: left; font-size: 14px;">';
    var_dump( '$_SERVER', $_SERVER );
    $final_options = array();
    foreach ( $all_options as $option ) {
        $final_options[$option['option_name']] = maybe_unserialize( $option['option_value'] );
    }
    var_dump( '$final_options', $final_options );
    echo '</pre>';
    var_dump( '$sample_products' );
    $sample_products = new WP_Query(array(
        'post_type'      => 'product',
        'meta_key'       => '',
        'meta_value'     => '0',
        'meta_compare'   => '>',
        'post_status'    => 'publish',
        'posts_per_page' => 10,
    ));
    foreach ( $sample_products->posts as $sample_product ) {
        echo '<p><b>' . esc_html( $sample_product->post_title ) . '</b>: <a href="' . esc_url( get_permalink( $sample_product->ID ) ) . '" target="_blank">View</a> - <a href="' . esc_url( admin_url( 'post.php?action=edit&post=' . $sample_product->ID ) ) . '" target="_blank">Edit</a></p>';
    }
    // Test the IP geolocation
    if ( !empty( $_SERVER['REMOTE_ADDR'] ) && strpos( $_SERVER['REMOTE_ADDR'], ',' ) !== false ) {
        $_SERVER['REMOTE_ADDR'] = strtok( $_SERVER['REMOTE_ADDR'], ',' );
    }
    $ip = '';
    if ( !empty( $server_ip_key ) && isset( $_SERVER[$server_ip_key] ) ) {
        $ip = $_SERVER[$server_ip_key];
    }
    $location = WC_Geolocation::geolocate_ip( $ip, true );
    $my_country = $location['country'];
    var_dump( '$before_wc_geolocation', $_SERVER['REMOTE_ADDR'] );
    var_dump( '$current_ip_sent_to_wc', $ip );
    var_dump( '$wc_geolocation_response', $location );
    var_dump( '$my_country', $my_country );
}