<?php

if ( !function_exists( 'wcacr_exclude_variable_metabox' ) ) {
    add_action( 'woocommerce_product_options_general_product_data', 'wcacr_exclude_variable_metabox' );
    function wcacr_exclude_variable_metabox() {
        ?>
			<script>
				jQuery(document).ready(function () {
					function maybeBlockFieldsGroup(wrapperSelector, isVariable) {
						var $generalCountryRestrictions = jQuery(wrapperSelector);
						if (isVariable) {
							$generalCountryRestrictions.find('select').each(function () {
								jQuery(this).prop('disabled', true).parent().append($generalCountryRestrictions.find('.wcacr-go-premium-link').first().clone());
							});
						} else {
							$generalCountryRestrictions.find('select').prop('disabled', false).siblings('.wcacr-go-premium-link').remove();
						}
					}

					// product type specific options
					jQuery('body').on('woocommerce-product-type-change', function (event, select_val, select) {
						maybeBlockFieldsGroup('.country-restrictions-general-wrapper', select_val === 'variable');
						maybeBlockFieldsGroup('.country-restrictions-variation-wrapper', select_val === 'variable');
					});

					maybeBlockFieldsGroup('.country-restrictions-general-wrapper', jQuery('#product-type').val() === 'variable');
					maybeBlockFieldsGroup('.country-restrictions-variation-wrapper', jQuery('#product-type').val() === 'variable');

					jQuery("#woocommerce-product-data").on("woocommerce_variations_loaded", function () {
						maybeBlockFieldsGroup('.country-restrictions-variation-wrapper', jQuery('#product-type').val() === 'variable');
					});
				});
			</script>
			<?php 
    }

}