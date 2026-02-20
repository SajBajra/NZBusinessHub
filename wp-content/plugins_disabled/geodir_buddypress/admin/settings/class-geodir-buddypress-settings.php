<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'GeoDir_BuddyPress_Settings', false ) ) :

	class GeoDir_BuddyPress_Settings extends GeoDir_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id    = 'gd-buddypress';
			$this->label = __( 'Buddypress', 'geodir-buddypress' );

			add_filter( 'geodir_settings_tabs_array', array( $this, 'add_settings_page' ), 21 );
			add_action( 'geodir_settings_' . $this->id, array( $this, 'output' ) );
//			add_action( 'geodir_sections_' . $this->id, array( $this, 'output_toggle_advanced' ) );

			add_action( 'geodir_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'geodir_sections_' . $this->id, array( $this, 'output_sections' ) );

            add_filter( 'geodir_uninstall_options', array($this, 'geodir_buddypress_uninstall_options'), 10, 1);
		}

		/**
		 * Get sections.
		 *
		 * @return array
		 */
		public function get_sections() {
            $sections = array(
                '' => __( 'General', 'geodir-buddypress' ),
            );

			return apply_filters( 'geodir_get_sections_' . $this->id, $sections );
		}

		/**
		 * Output the settings.
		 */
		public function output() {
			global $current_section;

			$settings = $this->get_settings( $current_section );

			GeoDir_Admin_Settings::output_fields( $settings );
		}

		/**
		 * Save settings.
		 */
		public function save() {
			global $current_section;

			$settings = $this->get_settings( $current_section );

			GeoDir_Admin_Settings::save_fields( $settings );
		}

		/**
		 * Get settings array.
		 *
		 * @return array
		 */
		public function get_settings( $current_section = '' ) {

            $settings = array(
                array(
                    'name' => __( 'Review Rating Settings', 'geodir-buddypress' ),
                    'type' => 'title',
                    'desc' => '',
                    'id' => 'buddypress_settings'
                ),
                array(
                    'name' => __( 'Redirect GD dashboard my listing link to BuddyPress profile', 'geodir-buddypress' ),
                    'desc' => __( 'If this option is selected, the my listing link from GD dashboard will redirect to listings tab of BuddyPress profile.', 'geodir-buddypress' ),
                    'id'   => 'geodir_buddypress_link_listing',
                    'type' => 'checkbox',
                    'advanced' => false
                ),
                array(
                    'name' => __( 'Redirect GD dashboard favorite link to BuddyPress profile', 'geodir-buddypress' ),
                    'desc' => __( 'If this option is selected, the favorite link from GD dashboard will redirect to favorites tab of BuddyPress profile.', 'geodir-buddypress' ),
                    'id'   => 'geodir_buddypress_link_favorite',
                    'type' => 'checkbox',
                    'advanced' => false
                ),
                array(
                    'name' => __( 'Link blog author link to the BuddyPress profile link', 'geodir-buddypress' ),
                    'desc' => __( 'If this option is selected, the blog author page links to the BuddyPress profile page.', 'geodir-buddypress' ),
                    'id'   => 'geodir_buddypress_link_author',
                    'type' => 'checkbox',
                    'advanced' => false
                ),
                array(
                    'name' => __( 'Show featured image in activity', 'geodir-buddypress' ),
                    'desc' => __( 'If this option is selected, the featured image is displayed in activity for new listing submitted.', 'geodir-buddypress' ),
                    'id'   => 'geodir_buddypress_show_feature_image',
                    'type' => 'checkbox',
                    'advanced' => false
                ),
                array(
                    'name'       => __( 'Show listings in BuddyPress dashboard', 'geodir-buddypress' ),
                    'desc'       => __( 'Choose the post types to show listing type tab under listings tab in BuddyPress dashboard', 'geodir-buddypress' ),
                    'id'         => 'geodir_buddypress_tab_listing',
                    'default'    => array('gd_place'),
                    'type'       => 'multiselect',
                    'desc_tip'   => true,
                    'class'      => 'geodir-select',
                    'css'        => 'min-width:90%',
                    'options'    => geodir_get_posttypes('options'),
                    'advanced' => true
                ),
                array(
                    'name'       => __( 'Show reviews in BuddyPress dashboard', 'geodir-buddypress' ),
                    'desc'       => __( 'Choose the post types to show listing type tab under reviews tab in BuddyPress dashboard', 'geodir-buddypress' ),
                    'id'         => 'geodir_buddypress_tab_review',
                    'default'    => array('gd_place'),
                    'type'       => 'multiselect',
                    'desc_tip'   => true,
                    'class'      => 'geodir-select',
                    'css'        => 'min-width:90%',
                    'options'    => geodir_get_posttypes('options'),
                    'advanced' => true
                ),
                array(
                    'name'       => __( 'Track new listing activity in BuddyPress', 'geodir-buddypress' ),
                    'desc'       => __( 'Choose the post types to track new listing submission in BuddyPress activity', 'geodir-buddypress' ),
                    'id'         => 'geodir_buddypress_activity_listing',
                    'default'    => array('gd_place'),
                    'type'       => 'multiselect',
                    'desc_tip'   => true,
                    'class'      => 'geodir-select',
                    'css'        => 'min-width:90%',
                    'options'    => geodir_get_posttypes('options'),
                    'advanced' => true
                ),
                array(
                    'name'       => __( 'Track new review activity in BuddyPress', 'geodir-buddypress' ),
                    'desc'       => __( 'Choose the post types to track new review submission in BuddyPress activity', 'geodir-buddypress' ),
                    'id'         => 'geodir_buddypress_activity_review',
                    'default'    => array('gd_place'),
                    'type'       => 'multiselect',
                    'desc_tip'   => true,
                    'class'      => 'geodir-select',
                    'css'        => 'min-width:90%',
                    'options'    => geodir_get_posttypes('options'),
                    'advanced' => true
                ),

                array( 'type' => 'sectionend', 'id' => 'buddypress_settings' )
            );

			// Section starts: Listings
			$settings[] = array(
				'type' => 'title',
				'id' => 'buddypress_listings_settings',
				'title' => __( 'Profile Listings', 'geodir-buddypress' ),
				'desc' => '',
			);

			$settings[] = array(
				'type' => 'number',
				'id' => 'geodir_buddypress_listings_count',
				'name' => __( 'No. Of Listings', 'geodir-buddypress' ),
				'desc' => __( 'Enter number of listings to display in the member dashboard listings tab.', 'geodir-buddypress' ),
				'default' => '5',
				'desc_tip' => true,
				'advanced' => true
			);

			$settings[] = array(
				'type' => 'select',
				'id' => 'bp_listings_layout',
				'name' => __( 'Layout', 'geodir-buddypress' ),
				'desc' => __( 'How the listings should laid out by default.', 'geodir-buddypress' ),
				'class' => 'geodir-select',
				'options' => geodir_get_layout_options(),
				'default' => '2',
				'desc_tip' => true,
				'advanced' => false
			);

			// AUI
			if ( geodir_design_style() ) {
				$settings[] = array(
					'type' => 'select',
					'id' => 'bp_listings_row_gap',
					'name' => __( "Listing Card Row Gap", 'geodir-buddypress' ),
					'desc' => __( 'This adjusts the spacing between the listing cards horizontally.', 'geodir-buddypress' ),
					'class' => 'geodir-select',
					'options' => array(
						'' => __( "Default", 'geodir-buddypress' ),
						'1' => '1',
						'2' => '2',
						'3' => '3',
						'4' => '4',
						'5' => '5',
					),
					'default' => '',
					'desc_tip' => true,
					'advanced' => false
				);

				$settings[] = array(
					'type' => 'select',
					'id' => 'bp_listings_column_gap',
					'name' => __( "Listing Card Column Gap", 'geodir-buddypress' ),
					'desc' => __( 'This adjusts the spacing between the listing cards vertically.', 'geodir-buddypress' ),
					'class' => 'geodir-select',
					'options' => array(
						'' => __( "Default", 'geodir-buddypress' ),
						'1' => '1',
						'2' => '2',
						'3' => '3',
						'4' => '4',
						'5' => '5',
					),
					'default' => '',
					'desc_tip' => true,
					'advanced' => false
				);

				$settings[] = array(
					'type' => 'select',
					'id' => 'bp_listings_card_border',
					'name' => __( "Listing Card Border", 'geodir-buddypress' ),
					'desc' => __( 'Set the border style for the listing card.', 'geodir-buddypress' ),
					'class' => 'geodir-select',
					'options' => array(
						'' => __( "Default", 'geodir-buddypress' ),
						'none' => __( "None", 'geodir-buddypress' )
					) + geodir_aui_colors(),
					'default' => '',
					'desc_tip' => true,
					'advanced' => true
				);

				$settings[] = array(
					'type' => 'select',
					'id' => 'bp_listings_card_shadow',
					'name' => __( "Listing Card Shadow", 'geodir-buddypress' ),
					'desc' => __( 'Set the listing card shadow style.', 'geodir-buddypress' ),
					'class' => 'geodir-select',
					'options' => array(
						'none' => __( "None", 'geodir-buddypress' ),
						'small' => __( "Small",'geodir-buddypress' ),
						'medium' => __( "Medium",'geodir-buddypress' ),
						'large' => __( "Large",'geodir-buddypress' )
					),
					'default' => '',
					'desc_tip' => true,
					'advanced' => true
				);
			}

			// Elementor Pro features below here.
			if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
				$settings[] = array(
					'type' => 'select',
					'id' => 'bp_listings_skin_id',
					'name' => __( "Elementor Skin", 'geodir-buddypress' ),
					'desc' => __( 'Set the elementor skin to apply listings tab design.', 'geodir-buddypress' ),
					'class' => 'geodir-select',
					'options' => GeoDir_Elementor::get_elementor_pro_skins(),
					'default' => '',
					'desc_tip' => true,
					'advanced' => false
				);

				$settings[] = array(
					'type' => 'number',
					'id' => 'bp_listings_skin_row_gap',
					'name' => __( 'Skin Row Gap', 'geodir-buddypress' ),
					'desc' => __( 'The px value for the row gap.', 'geodir-buddypress' ),
					'default' => '35',
					'desc_tip' => true,
					'advanced' => true
				);

				$settings[] = array(
					'type' => 'number',
					'id' => 'bp_listings_skin_column_gap',
					'name' => __( 'Skin Column Gap', 'geodir-buddypress' ),
					'desc' => __( 'The px value for the column gap.', 'geodir-buddypress' ),
					'default' => '30',
					'desc_tip' => true,
					'advanced' => true
				);
			}

			$settings[] = array( 
				'type' => 'sectionend', 
				'id' => 'buddypress_listings_settings' 
			);
			// Section ends: Listings

			$settings = apply_filters( 'geodir_buddypress_general_options', $settings );

			return apply_filters( 'geodir_get_settings_' . $this->id, $settings, $current_section );
		}

        public static function geodir_buddypress_uninstall_options($settings){
            array_pop($settings);
            $settings[] = array(
                'name'     => __( 'Buddypress Integration', 'geodir-buddypress' ),
                'desc'     => __( 'Check this box if you would like to completely remove all of its data when plugin is deleted.', 'geodir-buddypress' ),
                'id'       => 'uninstall_geodir_buddypress_manager',
                'type'     => 'checkbox',
            );
            $settings[] = array( 'type' => 'sectionend', 'id' => 'uninstall_options' );

            return $settings;
        }

	}

endif;

return new GeoDir_BuddyPress_Settings();
