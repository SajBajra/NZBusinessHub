<?php
/**
 * Save Search Form
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/bootstrap/save-search-popup/form.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see       https://wpgeodirectory.com/documentation/article/how-tos/customizing-templates/
 * @package   GeoDir_Save_Search
 * @version   1.0
 */

defined( 'ABSPATH' ) || exit;

global $aui_bs5;

?>
<form method="post" class="geodir-save-search-form" autocomplete="off">
	<input type="hidden" name="gd_save_search_nonce" id="gd_save_search_nonce" value="<?php echo esc_attr( wp_create_nonce( 'geodir_save_search_save' ) ); ?>" />
	<?php if ( ! empty( $query_params ) ) { ?>
	<input type="hidden" name="gd_save_search_vars" id="gd_save_search_vars" value="<?php echo esc_attr( json_encode( $query_params ) ); ?>" />
	<?php } ?>
	<?php do_action( 'geodir_save_search_form_hidden_fields' ); ?>

	<div class="geodir_save_search_form-fields">
		<div class="geodri-save-search-status"></div>
		<?php
		do_action( 'geodir_save_search_form_before_fields' );

		echo aui()->input(
			array(
				'type' => 'text',
				'id' => 'gd_save_search_name',
				'name' => 'gd_save_search_name',
				'label' => __( 'Search Name', 'geodir-save-search' ) . ' <span class="text-danger">*</span>',
				'placeholder' => __( 'Search Name', 'geodir-save-search' ),
				'help_text' => __( 'Get emailed when a new listing is added that matches your search.', 'geodir-save-search' ),
				'label_type' => 'vertical',
				'required' => true,
				'extra_attributes' => array(
					'maxlength' => 50
				)
			)
		);

		do_action( 'geodir_save_search_form_after_fields' );
	?>
	</div>
	<div class="geodirectory-form-footer">
		<?php
		do_action( 'geodir_save_search_form_before_button' );

		$button = aui()->button(
			array(
				'type' => 'submit',
				'class' => 'btn btn-primary d-block w-100 geodir-save-search-button',
				'content' => __( 'Save', 'geodir-save-search' ),
				'no_wrap' => true
			)
		);

		echo AUI_Component_Input::wrap(
			array(
				'content' => $button,
				'class' => ( $aui_bs5 ? '' : 'form-group ' ) . ' text-center mb-0 pt-2'
			)
		);

		do_action( 'geodir_save_search_form_after_button' );
		?>
	</div>
</form>
