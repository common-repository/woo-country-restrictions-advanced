<?php
if (!class_exists('WCACR_Sheet_Editor')) {

	class WCACR_Sheet_Editor {

		static private $instance = false;
		var $post_types = array('product', 'shop_coupon');

		private function __construct() {
			
		}

		function init() {
			add_filter('vcwccr_tab_settings_filter', array($this, 'add_settings_options'));
			add_action('vg_sheet_editor/initialized', array($this, 'init_wpse_features'));
		}

		function init_wpse_features() {
			add_action('vg_sheet_editor/editor/register_columns', array($this, 'register_columns'));
			add_action('vg_sheet_editor/editor_page/after_content', array($this, 'render_tooltip_first_time'));
			add_filter('vg_sheet_editor/woocommerce/teasers/allowed_columns', array($this, 'allow_columns'));
			add_filter('vg_sheet_editor/woocommerce/teasers/allowed_variation_columns', array($this, 'allow_columns'));
			add_filter('vg_sheet_editor/custom_columns/teaser/allow_to_lock_column', array($this, 'allow_column'), 10, 2);
			add_filter('vg_sheet_editor/factory/is_column_allowed', array($this, 'allow_column'), 10, 2);
		}

		function allow_column($allowed_to_lock, $column_key) {
			if (in_array($column_key, array('vcwccr_selected_countries', 'vcwccr_availability_operator'), true)) {
				$allowed_to_lock = false;
			}

			return $allowed_to_lock;
		}

		function add_settings_options($options) {
			if (class_exists('WP_Sheet_Editor_WC_Products')) {
				$options['restrict_products']['sheet_products_desc'] = array(
					'type' => 'title',
					'desc' => sprintf(__('<hr><h3>Do you want to edit product restrictions in a Spreadsheet Quickly?</h3><a href="%s" target="_blank" class="button">Open the bulk editor for products</a><hr>', VCWCCR_TEXT_DOMAIN), admin_url('admin.php?page=vgse-bulk-edit-product')),
				);
				$options['restrict_variations']['sheet_variations_desc'] = array(
					'type' => 'title',
					'desc' => sprintf(__('<hr><h3>Do you want to edit variations in a Spreadsheet Quickly?</h3><a href="%s" target="_blank" class="button">Open the bulk editor for variations</a><hr>', VCWCCR_TEXT_DOMAIN), admin_url('admin.php?page=vgse-bulk-edit-product')),
				);
			} else {
				$options['restrict_products']['sheet_products_desc'] = array(
					'type' => 'title',
					'desc' => sprintf(__('<hr><h3>Do you want to edit product restrictions in a Spreadsheet Quickly?</h3>You can install a <b>Spreadsheet Editor for Products</b> and you will see all the products in a spreadsheet table and you can select which countries can see the products and which countries cant quickly.<br><a href="%s" target="_blank" class="button">Install the free plugin</a><hr>', VCWCCR_TEXT_DOMAIN), wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=woo-bulk-edit-products'), 'install-plugin_woo-bulk-edit-products')),
				);
				$options['restrict_variations']['sheet_variations_desc'] = array(
					'type' => 'title',
					'desc' => sprintf(__('<hr><h3>Do you want to edit variation restrictions in a Spreadsheet Quickly?</h3>You can install a <b>Spreadsheet Editor for Products</b> and you will see all the products in a spreadsheet table and you can select which countries can see the products and which countries cant quickly.<br><a href="%s" target="_blank" class="button">Install the free plugin</a><hr>', VCWCCR_TEXT_DOMAIN), wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=woo-bulk-edit-products'), 'install-plugin_woo-bulk-edit-products')),
				);
			}
			// Deactivated because the free taxonomies plugin doesn't allow to edit product categories
			/* if (class_exists('WP_Sheet_Editor_Taxonomy_Terms')) {
			  $options[__('Restrict category pages', VCWCCR_TEXT_DOMAIN)]['sheet_categories_desc'] = array(
			  'type' => 'title',
			  'desc' => sprintf(__('<hr><h3>Do you want to edit category restrictions in a Spreadsheet Quickly?</h3><a href="%s" target="_blank" class="button">Open the bulk editor for categories</a><hr>', VCWCCR_TEXT_DOMAIN), VGSE()->helpers->get_editor_url('product_cat')),
			  );
			  } else {
			  $options[__('Restrict category pages', VCWCCR_TEXT_DOMAIN)]['sheet_categories_desc'] = array(
			  'type' => 'title',
			  'desc' => sprintf(__('<hr><h3>Do you want to edit category restrictions in a Spreadsheet Quickly?</h3>You can install a <b>Spreadsheet Editor for Categories</b> and you will see all the categories in a spreadsheet table and you can select which countries can see the categories and which countries cant quickly.<br><a href="%s" target="_blank" class="button">Install the free plugin</a><hr>', VCWCCR_TEXT_DOMAIN), wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=bulk-edit-categories-tags'), 'install-plugin_bulk-edit-categories-tags')),
			  );
			  } */
			if (class_exists('WP_Sheet_Editor_WC_Coupons')) {
				$options['restrict_coupons']['sheet_coupons_desc'] = array(
					'type' => 'title',
					'desc' => sprintf(__('<hr><h3>Do you want to edit coupon restrictions in a Spreadsheet Quickly?</h3><a href="%s" target="_blank" class="button">Open the bulk editor for coupons</a><hr>', VCWCCR_TEXT_DOMAIN), admin_url('admin.php?page=vgse-bulk-edit-shop_coupon')),
				);
			} else {
				$options['restrict_coupons']['sheet_coupons_desc'] = array(
					'type' => 'title',
					'desc' => sprintf(__('<hr><h3>Do you want to edit coupon restrictions in a Spreadsheet Quickly?</h3>You can install a <b>Spreadsheet Editor for WooCommerce Coupons</b> and you will see all the coupons in a spreadsheet table and you can select which countries can use the coupons and which countries cant quickly.<br><a href="%s" target="_blank" class="button">Install the free plugin</a><hr>', VCWCCR_TEXT_DOMAIN), wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=woo-coupons-bulk-editor'), 'install-plugin_woo-coupons-bulk-editor')),
				);
			}
			return $options;
		}

		function allow_columns($columns) {
			$columns[] = 'vcwccr_selected_countries';
			$columns[] = 'vcwccr_availability_operator';
			return $columns;
		}

		function render_tooltip_first_time($post_type) {
			if (!in_array($post_type, $this->post_types)) {
				return;
			}
			$flag_key = 'wcacr_hide_sheet_tip';
			if (get_option($flag_key)) {
				return;
			}
			update_option($flag_key, 1);
			?>
			<script>
				jQuery(document).ready(function () {
					jQuery('body').on('vgSheetEditor:afterRowsInsert', function () {
						hot.selectColumns('vcwccr_selected_countries', 'vcwccr_availability_operator');
						vgseCustomTooltip(jQuery('#vgse-wrapper .handsontable .ht__active_highlight').first(), <?php echo json_encode(__('You can edit the country restrictions here. You can edit hundreds of products at once, auto complete cells, and copy paste.', VCWCCR_TEXT_DOMAIN)); ?>, 'top', false);
					});
				});
			</script>
			<?php
		}

		/**
		 * Register spreadsheet columns
		 */
		function register_columns($editor) {
			$countries = vcwccr_get_countries_with_continents();
			$final_data = array();
			foreach ($countries as $key => $label) {
				$final_data[] = array(
					'id' => $key,
					'label' => $label,
				);
			}

			foreach ($this->post_types as $post_type) {
				if (!in_array($post_type, $editor->args['enabled_post_types'])) {
					continue;
				}

				$editor->args['columns']->register_item('vcwccr_selected_countries', $post_type, array(
					'title' => __('Countries and continents', VCWCCR_TEXT_DOMAIN),
					'formatted' => array(
						'editor' => 'chosen',
						'width' => 300,
						'source' => $final_data,
						'chosenOptions' => array(
							'multiple' => true,
							'search_contains' => true,
							'data' => $final_data
						),
					),
					'data_type' => 'meta_data',
					'supports_formulas' => true,
					'supports_sql_formulas' => false,
				));
				$editor->args['columns']->register_item('vcwccr_availability_operator', $post_type, array(
					'data_type' => 'meta_data',
					'column_width' => 200,
					'title' => __('Available in those countries', VCWCCR_TEXT_DOMAIN),
					'supports_formulas' => true,
					'supports_sql_formulas' => false,
					'formatted' => array(
						'type' => 'checkbox',
						'checkedTemplate' => '1',
						'uncheckedTemplate' => '0',
					),
					'default_value' => '0',
				));
				$editor->args['columns']->remove_item('wcacr_disallowed_countries', $post_type);
			}
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WCACR_Sheet_Editor::$instance) {
				WCACR_Sheet_Editor::$instance = new WCACR_Sheet_Editor();
				WCACR_Sheet_Editor::$instance->init();
			}
			return WCACR_Sheet_Editor::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

if (!function_exists('WCACR_Sheet_Editor_Obj')) {

	function WCACR_Sheet_Editor_Obj() {
		return WCACR_Sheet_Editor::get_instance();
	}

}
add_action('init', 'WCACR_Sheet_Editor_Obj', 5);
