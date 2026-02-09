<?php
/**
 * GeoDirectory.
 *
 * Adds integration between Advertising and GeoDirectory.
 *
 */

defined( 'ABSPATH' ) || exit;
/**
 * GD integration class
 */
class Adv_GeoDirectory {

    /**
	 * Class initializer.
	 */
	public static function init() {

        if ( defined( 'GEODIR_LOCATION_PLUGIN_FILE' ) ) {

            //Add location conditions
            add_filter( 'adv_display_rules' , array( __CLASS__, 'filter_conditions' ) );

            //Display conditions
            add_action( 'adv_tab_locations' , array( __CLASS__, 'display_condition' ) );

            //Check if the add can be displaed
            add_filter( 'adv_can_display_object' , array( __CLASS__, 'can_display' ), 999, 2  );
            add_filter( 'adv_can_display_ad', array( __CLASS__, 'can_display_ad' ), 999, 3 );
        }

        add_action( 'adv_zone_details_form_form_after_allowed_ad_types', array( __CLASS__, 'input_gd_post_types' ), 10, 1 );

        //Save metabox
        add_filter( 'adv_metabox_fields_save_zone', array( __CLASS__, 'metabox_fields' )  );

        // Register extra zone injection positions.
        add_filter( 'adv_zone_injection_positions', array( __CLASS__, 'register_injection_settings' ) );

        // Inject listings to GD templates.
        add_action( 'geodir_before_listing_listview', array( __CLASS__, 'inject_before_listings' ) );
        add_action( 'geodir_after_listing_listview', array( __CLASS__, 'inject_after_listings' ) );

        // Link zones to packages.
		add_action( 'adv_tab_post_types', array( __CLASS__, 'display_rule_setting_own_cpt' ), 6 );
		add_action( 'adv_tab_taxonomies', array( __CLASS__, 'display_rule_setting_own_taxonomy' ), 6 );
		add_action( 'adv_tab_terms', array( __CLASS__, 'display_rule_setting_own_terms' ), 6 );
		add_action( 'adv_tab_locations', array( __CLASS__, 'display_rule_setting_own_location' ), 6 );

	}

	/**
	 * Settings for CPT display rules.
	 */
	public static function display_rule_setting_own_cpt(){
		global $aui_bs5, $post;
		?>
    <div class="row mb-2 mt-3">
			<label class="col-sm-3 col-form-label" for="adv_own_post_types"><span><?php _e( 'GeoDirectory Listings', 'advertising' )?></span></label>
			<label class="col-sm-8 pl-4">
				<input type="checkbox" id="adv_own_post_types" name="own_post_types" value="1" <?php checked( (int) adv_zone_get_meta( $post->ID, 'own_post_types', true, 0 ), 1 ); ?> />
				<span><?php _e( 'Only show listing ads on their own Custom Post Type pages.', 'advertising' ); ?></span>&nbsp;
				<small class="form-text d-block text-muted">
					<?php _e( 'This will prevent listing ads showing on CPTs that are not the same. For example: Event ads would not show on Places CPT pages.', 'advertising' ); ?>
				</small>
			</label>
		</div>
		<?php
	}

	/**
	 * Settings for taxonomy display rules.
	 */
	public static function display_rule_setting_own_taxonomy(){
		global $aui_bs5, $post;
		?>
  <div class="row mb-2 mt-3">
			<label class="col-sm-3 col-form-label" for="adv_own_taxonomy"><span><?php _e( 'GeoDirectory Listings', 'advertising' )?></span></label>
			<label class="col-sm-8 pl-4">
				<input type="checkbox" id="adv_own_taxonomy" name="own_taxonomy" value="1" <?php checked( (int) adv_zone_get_meta( $post->ID, 'own_taxonomy', true, 0 ), 1 ); ?> />
				<span><?php _e( 'Only show listing ads on their own taxonomy pages.', 'advertising' ); ?></span>&nbsp;
				<small class="form-text d-block text-muted">
					<?php _e( 'This will prevent listing ads from other CPTs showing on listing category and tags pages. For example: If viewing any Place category archive, only other Places ads would show.', 'advertising' ); ?>
				</small>
			</label>
		</div>
		<?php
	}

	/**
	 * Settings for terms display rules.
	 */
	public static function display_rule_setting_own_terms(){
		global $aui_bs5, $post;
		?>
    <div class="row mb-2 mt-3">
			<label class="col-sm-3 col-form-label" for="adv_own_terms"><span><?php _e( 'GeoDirectory Listings', 'advertising' )?></span></label>
			<label class="col-sm-8 pl-4">
				<input type="checkbox" id="adv_own_terms" name="own_terms" value="1" <?php checked( (int) adv_zone_get_meta( $post->ID, 'own_terms', true, 0 ), 1 ); ?> />
				<span><?php _e( 'Only show listing ads on their own terms pages.', 'advertising' ); ?></span>&nbsp;
				<small class="form-text d-block text-muted">
					<?php _e( 'This will prevent listing ads showing on terms that the listing ad itself is not part of. For example: If an ad has the category of "Hotels" it would not show in "Restaurants" category archive pages.', 'advertising' ); ?>
				</small>
			</label>
		</div>
		<?php
	}

	/**
	 * Settings for location display rules.
	 */
	public static function display_rule_setting_own_location(){
		global $aui_bs5, $post;
		?>
    <div class="row mb-2 mt-3">
			<label class="col-sm-3 col-form-label" for="adv_own_location"><span><?php _e( 'GeoDirectory Listings', 'advertising' )?></span></label>
			<label class="col-sm-8 pl-4">
				<input type="checkbox" id="adv_own_location" name="own_location" value="1" <?php checked( (int) adv_zone_get_meta( $post->ID, 'own_location', true, 0 ), 1 ); ?> />
				<span><?php _e( 'Only show listing ads from the same location.', 'advertising' ); ?></span>&nbsp;
				<small class="form-text d-block text-muted">
					<?php _e( 'This will prevent listing ads showing from other locations. For example: A listing ad from "New York" will not show if the current page is limited to "London".', 'advertising' ); ?>
				</small>
			</label>
		</div>
		<?php
	}

    /**
     * filter conditions
     */
    public static function filter_conditions( $conditions ) {

        $conditions['locations'] = array(
            'label' => __( 'Locations', 'advertising' ),
            'icon'  => 'dashicons-location-alt',
        );

        return $conditions;

    }

	/**
	 * Add metabox fields
	 */
	public static function metabox_fields ( $fields ) {
		$fields[] = '_adv_zone_locations';
		$fields[] = '_adv_zone_own_post_types';
		$fields[] = '_adv_zone_own_taxonomy';
		$fields[] = '_adv_zone_own_terms';
		$fields[] = '_adv_zone_own_location';
		$fields[] = '_adv_zone_gd_post_types';
		return $fields;
	}

    public static function can_display_ad( $can_display, $ad_id, $ad ) {

        if ( 'listing' === $ad->get( 'type' ) ) {
            return $can_display;
        }

        return self::can_display( $can_display, $ad );
    }

	/**
	 * Checks if the zone can be displayed
	 */
	public static function can_display( $can_display, $object  ) {
		global $geodirectory;

		// Abort if this is not a supported object
		if ( ! ( $object instanceof Adv_Zone ) && !( $object instanceof Adv_Ad ) ) {
			return $can_display;
		}

		$locations   = $object->get( 'locations' );

		if ( empty( $locations ) || empty( $can_display ) ) {
			return $can_display;
		}

		$locations = array_map( 'trim', explode( ',', strtolower( $locations ) ) );

		if ( self::can_display_search( $locations ) ) {
			return true;
		}

		$has_location = ! empty( $geodirectory->location ) && ! empty( $geodirectory->location->type ) ? true : false;

		if ( ! $has_location ) {
			return false;
		}

		if ( is_singular() || geodir_is_page( 'single' ) || geodir_is_page( 'search' ) || geodir_is_page( 'location' ) ) {
			$to_check_against = array(
				$geodirectory->location->city_slug,
				$geodirectory->location->region_slug,
				geodir_strtolower( $geodirectory->location->city ),
				geodir_strtolower( $geodirectory->location->region ),
			);

			$to_check_against = array_values( array_unique( array_filter( $to_check_against ) ) );
		} else if ( geodir_is_page( 'post_type' ) || geodir_is_page( 'archive' ) ) {
			$to_check_against = self::get_location_query_vars();
		} else {
			$to_check_against = array();
		}

		if ( empty( $to_check_against ) ) {
			return false;
		}

		// Check if the two arrays have any common values
		$can_display = 0 < count( array_intersect( $locations, $to_check_against ) );

		return $can_display;
	}

	/**
	 * Get location query vars.
	 */
	public static function get_location_query_vars() {
		global $wp;

		$locations = array();

		foreach ( array( 'country', 'region', 'city', 'neighbourhood' ) as $type ) {
			if ( ! empty( $wp->query_vars[ $type ] ) && is_scalar( $wp->query_vars[ $type ] ) ) {
				$location = geodir_strtolower( $wp->query_vars[ $type ] );

				$locations[] = $location;
				$locations[] = preg_replace( '/[_-]/', ' ', preg_replace( '/-(\d+)$/', '', $location ) );
			}
		}

		if ( ! empty( $locations ) ) {
			$locations = array_values( array_unique( array_filter( $locations ) ) );
		}

		return $locations;
	}

    public static function can_display_search( $locations  ) {

        $near_search_location = adv_search_locations();
        if ( ! empty( $near_search_location ) ) {
            return 0 < count( array_intersect( $locations, $near_search_location ) );
        }

        return false;
    }

    /**
     * display conditions
     */
    public static function display_condition( $meta_prefix ) {
        global $adv_post;
    
        $post_id   = !empty( $adv_post->ID ) ? $adv_post->ID : 0;
    
        $locations = get_post_meta( $post_id, "{$meta_prefix}locations", true );
        $locations = !empty( $locations ) ? $locations : '';
        $default_location = array(
            sanitize_title( geodir_get_option('default_location_city') ),
            sanitize_title( geodir_get_option('default_location_region') ),
        );
        $default_location = implode( ',', array_filter( $default_location ) );
    
        ?>
    <table class="form-table">
        <tbody>
            <tr class="tr-locations">
                <th valign="top" scope="row">
                    <label for="locations"><?php _e( 'Regions / Cities', 'advertising' ); ?></label>
                </th>
                <td>
                    <div class="adv-check-boxes">
                        <input type="text" name="locations" id="locations" class="form-control large-text"  placeholder="<?php echo esc_attr( $default_location ); ?>" value="<?php echo esc_attr( $locations ); ?>" />
                        <p class="description"><?php _e( 'Enter a comma separated list of region or city slugs to limit this zone to. Leave empty to ignore this rule.', 'advertising' ); ?></p>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
        <?php
    }

    /**
     * Register zone injection positions.
     */
    public static function register_injection_settings ( $positions ) {
        $positions['before_gd_search']  = __( 'Before GD Search Results', 'advertising' );
        $positions['before_gd_archive'] = __( 'Before GD Archive content', 'advertising' );
        $positions['after_gd_search']   = __( 'After GD Search Results', 'advertising' );
        $positions['after_gd_archive']  = __( 'After GD Archive content', 'advertising' );
        return $positions;
    }

    /**
     * Retrieves zone injection positions.
     */
    public static function get_injection_positions ( $zone_id ) {
        $positions = adv_zone_get_meta( $zone_id, 'inject' );
        return is_array( $positions ) ? $positions : array();
    }

    /**
     * Injects ads before listings.
     */
    public static function inject_before_listings () {
	    global $geodir_is_widget_listing;

	    // don't inject into listings widget
	    if ( $geodir_is_widget_listing ) {
		    return;
	    }

        // Check if we're inside the main loop.
		if ( ! in_the_loop() || ! is_main_query() ) {
			//return;
		}

        // Loop through all active zones.
		foreach( adv_get_zones( array( 'meta_query' => array() ) ) as $zone_id ) {

            $positions = self::get_injection_positions( $zone_id );
            if ( ( is_search() && ! in_array( 'before_gd_search', $positions ) ) || ( ! is_search() && ! in_array( 'before_gd_archive', $positions ) ) ) {
                continue;
            }

			// Fetch the zone...
			$zone = adv_get_zone( $zone_id );

			// and display it.
			if ( $zone->can_display_zone() ) {
				echo $zone->get_html();
			}

		}

    }

    /**
     * Injects ads after listings.
     */
    public static function inject_after_listings () {

        // Check if we're inside the main loop.
		if ( ! in_the_loop() || ! is_main_query() ) {
			//return;
		}

        // Loop through all active zones.
		foreach( adv_get_zones( array( 'meta_query' => array() ) ) as $zone_id ) {

            $positions = self::get_injection_positions( $zone_id );

            if ( ( is_search() && ! in_array( 'after_gd_search', $positions ) ) || ( ! is_search() && ! in_array( 'after_gd_archive', $positions ) ) ) {
                continue;
            }

			// Fetch the zone...
			$zone = adv_get_zone( $zone_id );

			// and display it.
			if ( $zone->can_display_zone() ) {
				echo $zone->get_html();
			}

		}

    }

	public static function get_post_title( $post, $cpt_title = true, $context = 'dropdown' ) {
		$post_title = get_the_title( $post );

		if ( $cpt_title ) {
			$post_title = wp_sprintf( _x( '%s (%s)', 'geodirctory listing title', 'advertising' ), $post_title, geodir_strtolower( geodir_post_type_singular_name( get_post_type( $post ) ) ) );
		}

		$post_title = wp_strip_all_tags( $post_title );

		return apply_filters( 'adv_geodir_post_title', $post_title, $post, $cpt_title, $context );
	}

	public static function get_post_statuses( $post_types = array() ) {
		if ( empty( $post_types ) ) {
			$post_types = geodir_get_posttypes();
		}

		if ( is_array( $post_types ) ) {
			$_post_statuses = array();

			foreach ( $post_types as $post_type ) {
				$_post_statuses = array_merge( $_post_statuses, geodir_get_post_stati( 'public', array( 'post_type' => $post_type ) ) );
			}

			$post_statuses = array_unique( array_filter( $_post_statuses ) );
		} else {
			$post_statuses = geodir_get_post_stati( 'public', array( 'post_type' => $post_types ) );
		}

		return apply_filters( 'adv_geodir_post_statuses', $post_statuses, $post_types );
	}

	public static function input_gd_post_types( $post ) {
		$gd_post_types = adv_zone_get_meta( $post->ID, 'gd_post_types', true );
		if ( ! is_array( $gd_post_types ) ) {
			$gd_post_types = array();
		}
		?>
		<div class="mb-3 row adv-row-gd_post_types">
			<label class="col-sm-3 col-form-label" for="advertising_gd_post_types"><span><?php _e( 'GD Post Types', 'advertising' )?></span></label>
			<div class="col-sm-8">
				<?php
					echo aui()->select(
						array(
							'id'               => 'advertising_gd_post_types',
							'name'             => 'gd_post_types',
							'label'            => __( 'GD Post Types', 'advertising' ),
							'placeholder'      => __( 'All Post Types', 'advertising' ),
							'value'            => $gd_post_types,
							'select2'          => true,
							'multiple'         => true,
							'data-allow-clear' => false,
							'options'          => geodir_get_posttypes( 'options-plural' ),
							'help_text'        => __( 'Users will be allowed to create listing ads for selected GeoDirectory post types.(For Listing Ad only).', 'advertising' )
						)
					);
				?>
			</div>
		</div>
		<?php
	}
}
