<div class="wccpg-intro">
	<?php 
?>
		<a href="<?php 
echo WCACR()->args['buy_url'];
?>" class="button"><span class="dashicons dashicons-cart"></span> <?php 
echo WCACR()->args['buy_text'];
?></a> - <a href="<?php 
echo wacr_fs()->checkout_url( WCACR()->args['default_billing_period'], false );
?>" class="button button-primary"><span class="dashicons dashicons-cart"></span> <?php 
_e( 'Buy premium plugin' );
?></a>
	<?php 
?>
		<a href="<?php 
echo wacr_fs()->contact_url();
?>" class="button"><span class="dashicons dashicons-editor-help"></span> <?php 
_e( 'Need help? Contact us' );
?></a>
</div>
<style>
	h2 {
		text-align: center;
	}
	.wccpg-conditions-list {
		max-width: 670px;
	}
	.wccpg-intro {
		border-top: 1px solid #d2d2d2;
		border-bottom: 1px solid #d2d2d2;
		text-align: center;
		padding: 8px 0;
	}
	.wccpg-intro .dashicons {
		margin-top: 3px; 
	}
</style>