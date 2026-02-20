<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since 1.0.0
 *
 * The main admin class
 */
class GeoDir_Compare_Admin {

	/**
	 * Main Class Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Compare page options
		add_filter( 'geodir_page_options', array( $this, 'add_page_options' ) );

		// Force db upgrades.
		add_action( 'geodir_clear_version_numbers', array( $this, 'clear_version_number' ) );

		add_filter( 'geodir_uninstall_options', array( $this, 'uninstall_options' ), 13, 1 );
	}

	/**
	 * Deletes the version number from the DB so install functions will run again.
	 */
	public function clear_version_number(){
		delete_option( 'geodir_compare_db_version' );
	}

	/**
	 * Let the user select a custom compare page
	 */
	public function add_page_options( $pages ) {
		$supports_blocks = geodir_is_gutenberg();

		$pages[] = array(
			'title'             => __( 'Comparisons Page Settings', 'geodir-compare' ),
			'type'              => 'title',
			'desc'              => __('Set comparison related pages.','geodir-compare'),
			'id'                => 'page_comparison_options',
			'desc_tip'          => true,
		);

		$pages[] = array(
			'title'             => __( 'Listings Comparison Page', 'geodir-compare' ),
			'type'              => 'single_select_page',
			'desc'              => __('Select the page to use as the listings comparison page.','geodir-compare'),
			'id'                => 'geodir_compare_listings_page',
			'class'             => 'geodir-select',
			'default_content'   => geodir_compare_page_content( false, $supports_blocks ),
			'desc_tip'          => true,
		);

		$pages[] = array( 
			'type'              => 'sectionend', 
			'id'                => 'page_options',
		);

		return $pages;
	}

	public function uninstall_options( $settings ) {
		array_pop( $settings );

		$settings[] = array(
			'id' => 'uninstall_geodir_compare',
			'type' => 'checkbox',
			'name' => __( 'Compare Listings', 'geodir-compare' ),
			'desc' => __( 'Tick to remove all of its data when Compare Listings is deleted.', 'geodir-compare' )
		);

		$settings[] = array( 
			'type' => 'sectionend',
			'id' => 'uninstall_options'
		);

		return $settings;
	}
}