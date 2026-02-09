<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since 2.0.0
 *
 * @package    GeoDir_Lists
 * @subpackage GeoDir_Lists/admin
 *
 * Class GeoDir_Lists_Admin
 */
class GeoDir_Lists_Admin {

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * GeoDir_Lists_Admin constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_and_scripts' ) );
		add_action( 'p2p_init', array( $this,'list_p2p_connection' ) );
		add_action( 'geodir_get_widgets', array( $this, 'register_list_widgets' ), 11, 1 );

		add_filter( 'geodirectory_screen_ids', array( $this,'screen_ids'));
		add_action( 'geodir_clear_version_numbers' ,array( $this, 'clear_version_number'));
		//add_filter( 'geodir_get_settings_cpt', array( $this, 'post_type_settings' ), 20, 3 );
		add_filter( 'geodir_cpt_page_options', array( $this, 'post_type_settings' ), 30, 3 );
		add_filter( 'geodir_save_post_type', array( $this, 'sanitize_post_type' ), 10, 3 );
	}

	/**
	 * Deletes the version number from the DB so install functions will run again.
	 */
	public function clear_version_number(){
		delete_option( 'geodir_lists_version' );
	}

	/**
	 * Set the GD list pages as a geodirectory page so the correct files are loaded.
	 *
	 * @param $screen_ids
	 *
	 * @return array
	 */
	public function screen_ids( $screen_ids ) {
		$post_type = 'gd_list';

		$screen_ids[] = $post_type . '_page_' . $post_type . '-settings'; // CPT settings page

		return $screen_ids;
	}

	/**
	 * Register and enqueue list manager styles and scripts.
	 *
	 * @since 2.0.0
	 */
	public function enqueue_styles_and_scripts(){
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'list-manager-admin-script', GD_LISTS_PLUGIN_URL . 'assets/js/geodir_list_manager_admin' . $suffix . '.js', array( 'jquery' ), '2.0.0', true );
		wp_enqueue_script( 'list-manager-admin-script' );

		wp_register_style('list-manager-admin-style', GD_LISTS_PLUGIN_URL . 'assets/css/geodir_list_manager_admin.css', array(), '2.0.0');
		wp_enqueue_style('list-manager-admin-style' );
	}

	/**
	 * Connection to gd_list to custom gd post types.
	 *
	 * @since 2.0.0
	 */
	public function list_p2p_connection() {
		$all_postypes = geodir_get_posttypes();

		if ( ! $all_postypes ) {
			$all_postypes = array( 'gd_place' );
		}

		foreach ( $all_postypes as $pt ) {
			p2p_register_connection_type(
				array(
					'name'  => $pt.'_to_gd_list',
					'from'  => $pt,
					'to'    => 'gd_list',
					'sortable' => 'to',
					'admin_box' => array(
						'show' => 'to',
						'context' => 'side'
					)
				)
			);
		}
	}

	/**
	 * Register widgets.
	 *
	 * @since 2.0.0.0
	 *
	 * @param array $widgets The list of available widgets.
	 * @return array Available GD widgets.
	 */
	public function register_list_widgets( $widgets ) {
		if ( get_option( 'geodir_lists_version' ) ) {
			$widgets[] = 'GeoDir_Widget_List_Single_Description';
			$widgets[] = 'GeoDir_Widget_List_Save';
			$widgets[] = 'GeoDir_Widget_List_Loop';
			$widgets[] = 'GeoDir_Widget_List_Loop_Actions';
		}

		return $widgets;
	}

	public function add_page_option( $pages ) {
		$pages[] = array(
			'title' => __( 'Lists Page Settings', 'gd-lists' ),
			'type'  => 'title',
			'desc'  => __('List page settings for set add list page.','gd-lists'),
			'id'    => 'page_lists_options',
			'desc_tip' => true,
		);

		$pages[] = array(
			'name'     => __( 'Add List Page', 'gd-lists' ),
			'desc'     => __( 'Select the page to use for add list', 'gd-lists' ),
			'id'       => 'geodir_add_list_page',
			'type'     => 'single_select_page',
			'class'      => 'geodir-select',
			'desc_tip' => true,
		);

		$pages[] = array( 'type' => 'sectionend', 'id' => 'page_options' );

		return $pages;

	}

	public function post_type_settings( $settings, $post_type_values, $post_type ) {
		if ( $post_type != 'gd_list' ) {
			return $settings;
		}

		$gutenberg = geodir_is_gutenberg();

		$new_settings = array();

		$new_settings[] = array(
			'title' => __( 'Template Page Settings', 'gd-lists' ),
			'type' => 'title',
			'id' => 'cpt_settings_templates',
			'desc_tip' => true,
		);

		$page_single_id = (int) geodir_get_option( 'list_page_single' );
		$page_single = $page_single_id ? get_the_title( $page_single_id ) : '';

		$new_settings[] = array(
			'name' => __( 'Single Page', 'gd-lists' ),
			'desc' => __( 'Select the template to show GD Lists single page content.', 'gd-lists' ),
			'id' => 'page_single',
			'type' => 'single_select_page',
			'is_template_page' => true,
			'desc_tip' => true,
			'value' => ! empty( $post_type_values['page_details'] ) ? $post_type_values['page_details'] : '',
			'args' => array(
				'show_option_none' => $page_single ? wp_sprintf( __( 'Default (%s)', 'gd-lists' ), $page_single ) : __( 'Select Page...', 'gd-lists' ),
				'option_none_value' => '0',
				'sort_column' => 'post_title',
			),
			'default_content' => geodir_list_page_single_content( false, $gutenberg )
		);

		$new_settings[] = array(
			'name' => __( 'Archive Item Page', 'gd-lists' ),
			'desc' => __( 'Select the template to show GD Lists archive item page content.', 'gd-lists' ),
			'id' => 'page_archive_item',
			'type' => 'single_select_page',
			'is_template_page' => true,
			'desc_tip' => true,
			'value' => ! empty( $post_type_values['page_archive_item'] ) ? $post_type_values['page_archive_item'] : '',
			'args' => array(
				'show_option_none' => __( 'Default', 'gd-lists' ),
				'option_none_value' => '0',
				'sort_column' => 'post_title',
			),
			'default_content' => GeoDir_Defaults::page_archive_item_content( false, geodir_is_gutenberg() ),
		);

		$new_settings[] = array( 
			'type' => 'sectionend', 
			'id' => 'cpt_settings_templates' 
		);

		if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			$new_settings[] = array(
				'title' => __( 'Archive Layout Settings', 'gd-lists' ),
				'type' => 'title',
				'id' => 'cpt_settings_archive_item',
				'desc_tip' => true,
			);

			$new_settings[] = array(
				'type' => 'select',
				'id' => 'elementor_skin',
				'name' => __( 'Elementor Skin', 'gd-lists' ),
				'desc' => __( 'Select Elementor Skin template to show posts on list page.', 'gd-lists' ),
				'placeholder' => __( 'Select Skin&hellip;', 'gd-lists' ),
				'options' => GeoDir_Elementor::get_elementor_pro_skins(),
				'select2' => true,
				'advanced' => false,
				'desc_tip' => true,
				'data-allow-clear' => false,
				'value' => isset( $post_type_values['elementor_skin'] ) ? $post_type_values['elementor_skin'] : ''
			);

			$new_settings[] = array(
				'type' => 'select',
				'id' => 'elementor_skin_columns',
				'name' => __( 'Elementor Layout Columns', 'gd-lists' ),
				'desc' => __( 'Select columns to show posts on list page.', 'gd-lists' ),
				'placeholder' => __( 'Select&hellip;', 'gd-lists' ),
				'options' => array(
					1 => 1,
					2 => 2,
					3 => 3,
					4 => 4,
					5 => 5,
					6 => 6,
				),
				'select2' => true,
				'advanced' => false,
				'desc_tip' => true,
				'data-allow-clear' => false,
				'default' => 2,
				'value' => isset( $post_type_values['elementor_skin_columns'] ) ? absint( $post_type_values['elementor_skin_columns'] ) : ''
			);

			$new_settings[] = array(
				'type' => 'number',
				'id' => 'elementor_skin_column_gap',
				'name' => __( 'Elementor Layout Column Gap', 'gd-lists' ),
				'desc' => __( 'The px value for the column gap.', 'geodirectory' ),
				'placeholder' => 30,
				'advanced' => false,
				'desc_tip' => true,
				'default' => 30,
				'value' => isset( $post_type_values['elementor_skin_column_gap'] ) ? $post_type_values['elementor_skin_column_gap'] : ''
			);

			$new_settings[] = array(
				'type' => 'number',
				'id' => 'elementor_skin_row_gap',
				'name' => __( 'Elementor Layout Row Gap', 'gd-lists' ),
				'desc' => __( 'The px value for the row gap.', 'geodirectory' ),
				'placeholder' => 30,
				'advanced' => false,
				'desc_tip' => true,
				'default' => 30,
				'value' => isset( $post_type_values['elementor_skin_row_gap'] ) ? $post_type_values['elementor_skin_row_gap'] : ''
			);

			$new_settings[] = array( 
				'type' => 'sectionend', 
				'id' => 'cpt_settings_archive_item' 
			);
		}

		return $new_settings;
	}

	public function sanitize_post_type( $data, $post_type, $request ) {
		// Physical location setting
		if ( $post_type == 'gd_list' ) {
			$data[ $post_type ]['page_details'] = ! empty( $request['page_single'] ) ? absint( $request['page_single'] ) : '';
			$data[ $post_type ]['page_archive_item'] = ! empty( $request['page_archive_item'] ) ? absint( $request['page_archive_item'] ) : '';
			$data[ $post_type ]['elementor_skin'] = ! empty( $request['elementor_skin'] ) ? absint( $request['elementor_skin'] ) : '';
			$data[ $post_type ]['elementor_skin_columns'] = ! empty( $request['elementor_skin_columns'] ) ? absint( $request['elementor_skin_columns'] ) : '';
			$data[ $post_type ]['elementor_skin_column_gap'] = ! empty( $request['elementor_skin_column_gap'] ) ? absint( $request['elementor_skin_column_gap'] ) : '';
			$data[ $post_type ]['elementor_skin_row_gap'] = ! empty( $request['elementor_skin_row_gap'] ) ? absint( $request['elementor_skin_row_gap'] ) : '';
		}

		return $data;
	}
}