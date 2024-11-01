<?php
get_header();
do_action("vcwccr_before_product_not_available_html");
?>
<style>
	.wcacr-product-not-available-wrapper {
		width: 100%;
		max-width: 1100px;
		margin: 0 auto;
		float: none;
	}
	.wcacr-default-message {
		text-align:center;
		position:relative;
	}
</style>
<div class = "wcacr-product-not-available-wrapper">
	<?php echo apply_filters('the_content', $message); ?>
</div>

<?php
do_action("vcwccr_after_product_not_available_html");
get_footer();
