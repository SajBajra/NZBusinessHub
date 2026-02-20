<?php
/**
 * Pricing Manager.
 *
 * Adds integration between Advertising and Pricing Manager.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Pricing Manager Integration Class
 *
 */
class Adv_Pricing_Manager {

	/**
	 * Class initializer.
	 */
	public static function init() {
		// Register advertising settings.
		add_filter( 'geodir_pricing_package_settings', array( __CLASS__, 'pricing_package_settings' ), 10, 2 );

		// Save advertising settings.
		add_filter( 'geodir_pricing_process_data_for_save', array( __CLASS__, 'save_pricing_package_data' ), 10, 2 );

		// Sync advertisements to listing statuses.
		add_action( 'save_post', array( __CLASS__, 'sync_ads_to_listing' ), 100, 3 );
		add_action( 'geodir_post_saved', array( __CLASS__, 'maybe_sync_ads_to_listing' ), 100, 3 );
		add_action( 'geodir_ajax_post_saved', array( __CLASS__, 'maybe_sync_ads_to_listing_gd' ), 100, 3 );
		add_action( 'geodir_pricing_complete_package_post_updated', array( __CLASS__, 'maybe_sync_ads_to_listing_x' ) );
		add_filter( 'ads_get_ad_invoices', array( __CLASS__, 'filter_ad_invoices' ), 10, 2 );

		// Handle ads on listing trashes.
		add_action( 'wp_trash_post', array( __CLASS__, 'delete_listing_ads' ), 10, 2 );

		// Delete ads on list deletions.
		add_action( 'delete_post', array( __CLASS__, 'delete_listing_ads' ), 10, 2 );

		add_filter( 'adv_display_rules' , array( __CLASS__, 'filter_conditions' ) );
		add_action( 'adv_tab_packages' , array( __CLASS__, 'display_condition' ) );
		add_filter( 'adv_metabox_fields_save_zone', array( __CLASS__, 'metabox_fields' ) );
		add_filter( 'adv_can_display_object' , array( __CLASS__, 'can_display' ), 60, 2 );

		// Link zones to packages.
		add_action( 'adv_zone_details_form_form_after_advertisement_link', array( __CLASS__, 'zone_metabox_settings' ) );
	}

	/**
	 * Adds advertising fields to the edit package form.
	 *
	 * @param $settings
	 * @param $package_data
	 *
	 * @return array
	 */
	public static function pricing_package_settings( $settings, $package_data ) {
		return array_merge(
			$settings,
			array(
				array(
					'type' 	      => 'title',
					'id'   	      => 'advertising_package_settings',
					'title'       => __( 'Advertising', 'advertising' ),
					'desc' 	      => '',
				),
				array(
					'type' 		  => 'multiselect',
					'id'       	  => 'package_advertising_zones',
					'title'       => __( 'Advertisement Zones', 'advertising' ),
					'desc' 		  => __( 'Select the advertisement zones where new listings in this package will be advertised for free.', 'advertising' ),
					'options'     => self::get_dropdown_zones(),
					'placeholder' => __( 'Select Package', 'advertising' ),
					'class'		  => 'geodir-select',
					'desc_tip' 	  => true,
					'advanced' 	  => false,
					'value'	   	  => empty( $package_data['package_advertising_zones'] ) ? array() : wp_parse_id_list( $package_data['package_advertising_zones'] ),
				),
				array(
					'type'        => 'sectionend',
					'id'          => 'package_advertising_settings'
				)
			)
		);
	}

	/**
	 * Save the submitted data.
	 *
	 * @param $package_data
	 * @param $data
	 * @param $package
	 *
	 * @return mixed
	 */
	public static function save_pricing_package_data( $package_data, $data ) {
		if ( ! empty( $data['advertising_zones'] ) ) {
			$package_data['meta']['package_advertising_zones'] = implode( ',', wp_parse_id_list( $data['advertising_zones'] ) );
		} else {
			$package_data['meta']['package_advertising_zones'] = '';
		}

		return $package_data;
	}

	/**
	 * Publishes listing ads.
	 *
	 * @param int $listing_id
	 */
	public static function publish_listing_ads( $listing_id ) {
		$published_ads     = self::get_listing_ads( $listing_id );
		$published_zones   = array();
		$publishable_zones = self::get_available_listing_zones( $listing_id );
		$new_ads           = array();
		$listing           = get_post( $listing_id );

		// Delete wrong ads.
		foreach ( $published_ads as $ad ) {
			// Is the zone allowed?
			$zone = (int) get_post_meta( $ad, '_adv_ad_zone', true );
			if ( ! in_array( $zone, $publishable_zones ) ) {
				wp_delete_post( $ad, true );
				continue;
			}

			// Maybe publish the ad.
			if ( 'publish' != get_post_status( $ad ) ) {
				wp_update_post(
					array(
						'ID'          =>$ad,
						'post_status' => 'publish',
					)
				);
			}

			$published_zones[] = $zone;
			$new_ads[]         = $ad;
		}

		// Publish unpublished ads.
		foreach ( $publishable_zones as $zone ) {
			// Is the zone published?
			if ( in_array( $zone, $published_zones ) ) {
				continue;
			}

			// Create a new ad.
			$ad = wp_insert_post(
				array(
					'post_title'    => sprintf(
						'%s (%s)',
						sanitize_text_field( get_the_title( $listing ) ),
						sanitize_text_field( get_the_title( $zone ) )
					),
					'post_type'     => adv_ad_post_type(),
					'post_author'   => $listing->post_author,
					'post_status'   => 'publish',
				),
				true
			);

			if ( is_wp_error( $ad ) ) {
				continue;
			}

			update_post_meta( $ad, '_adv_ad_type', 'listing' );
			update_post_meta( $ad, '_adv_ad_zone', $zone );
			update_post_meta( $ad, '_adv_ad_listing', $listing->ID );
			$new_ads[] = $ad;
		}

		update_post_meta( $listing_id, '_adv_ads', $new_ads );
	}

	/**
	 * Unpublishes published listing ads.
	 *
	 * @param int $listing_id
	 */
	public static function unpublish_listing_ads( $listing_id ) {
		$ads = self::get_listing_ads( $listing_id );

		if ( empty( $ads ) ) {
			return;
		}

		foreach ( $ads as $ad ) {
			wp_update_post(
				array(
					'ID'          => $ad,
					'post_status' => 'pending',
				)
			);
		}
	}

	/**
	 * Deletes listing ads.
	 *
	 * @param int $listing_id
	 */
	public static function delete_listing_ads( $listing_id, $post_or_status = '' ) {
		if ( geodir_is_gd_post_type( get_post_type( $listing_id ) ) ) {
			$ads = self::get_listing_ads( $listing_id );

			if ( empty( $ads ) ) {
				return;
			}

			foreach ( $ads as $ad ) {
				wp_delete_post( $ad, true );
			}
		}
	}

	/**
	 * Fetches listing ads.
	 *
	 * @param int $listing_id
	 * @return int[]
	 */
	public static function get_listing_ads( $listing_id ) {
		$ads = get_post_meta( $listing_id, '_adv_ads', true );

		return empty( $ads ) ? array() : wp_parse_id_list( $ads );
	}

	/**
	 * Fetches available listing zones.
	 *
	 * @param int $listing_id
	 * @return int[]
	 */
	public static function get_available_listing_zones( $listing_id ) {
		$package_id = geodir_get_post_meta( $listing_id, 'package_id', true );

		if ( ! empty( $_REQUEST['package_id'] ) ) {
			$package_id = (int) $_REQUEST['package_id'];
		}

		$zones      = empty( $package_id ) ? array() : GeoDir_Pricing_Package::get_meta( (int) $package_id, 'package_advertising_zones', true );
		return empty( $zones ) ? array() : wp_parse_id_list( $zones );
	}

	/**
	 * Filters ad invoices.
	 *
	 * @param array $invoices
	 * @param int $ad_id
	 * @return array
	 */
	public static function filter_ad_invoices( $invoices, $ad_id ) {
		$listing    = adv_ad_get_meta( $ad_id, 'listing' );
		$zone       = adv_ad_get_meta( $ad_id, 'zone' );
		$is_gd_zone = (int) adv_zone_get_meta( $zone, 'link_to_packages', true, 0 );

		if ( 1 == $is_gd_zone && ! empty( $listing) ) {
			$listing_invoices = GeoDir_Pricing_Post_Package::get_items(
				array(
					'post_id'  => $listing,
					'order_by' => 'id DESC'
				)
			);

			foreach ( $listing_invoices as $listing_invoice ) {
				$invoice = new WPInv_Invoice( $listing_invoice->invoice_id );

				if ( $invoice->exists() ) {
					$invoices[ $invoice->get_id() ] = $invoice;
				}
			}
		}

		return $invoices;
	}

	/**
	 * Syncs listing ads to the listing status.
	 *
	 * @param string $new_status
	 * @param string $old_status
	 * @param WP_Post $post
	 * @return void
	 */
	public static function sync_ads_to_listing( $post_ID, $post ) {
		if ( empty( $post ) ) {
			$post = get_post( $post_ID );
		}

		if ( empty( $post ) ) {
			return;
		}

		$post_type = get_post_type( $post_ID );
		$post_status = get_post_status( $post_ID );

		// auto-draft or revision
		if ( $post_status == 'auto-draft' || $post_status == 'inherit' ) {
			return;
		}

		// Abort if not our post status.
		if ( ! ( function_exists( 'geodir_is_gd_post_type' ) && geodir_is_gd_post_type( $post_type ) ) ) {
			return;
		}

		// Either publish or create listing ads.
		if ( $post_status == 'publish' || in_array( $post_status, geodir_get_post_stati( 'public', array( 'post_type' => $post_type ) ) ) ) {
			return self::publish_listing_ads( $post_ID );
		}

		// Un-publish ads if the listing is being unpublished.
		return self::unpublish_listing_ads( $post_ID );
	}

	public static function maybe_sync_ads_to_listing( $postarr, $gd_post, $post ) {
		self::sync_ads_to_listing( $post->ID, $post );
	}

	public static function maybe_sync_ads_to_listing_gd( $post_data ) {
		$post_ID = $post_data['ID'];

		if ( wp_is_post_revision( $post_ID ) ) {
			$post_ID = wp_get_post_parent_id( $post_ID );
		}

		self::sync_ads_to_listing( $post_ID, get_post( $post_ID ) );
	}

	public static function maybe_sync_ads_to_listing_x( $post_ID ) {
		if ( wp_is_post_revision( $post_ID ) ) {
			$post_ID = wp_get_post_parent_id( $post_ID );
		}

		self::sync_ads_to_listing( $post_ID, get_post( $post_ID ) );
	}

	/**
	 * Allows site admins to link advertising zones to GeoDirectory.
	 *
	 * @param WP_Post $post
	 * @return void
	 */
	public static function zone_metabox_settings( $post ) {
		global $aui_bs5;
		?>
			<?php do_action( 'adv_zone_details_form_before_link_to_packages', $post ); ?>
			<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> row">
				<label class="col-sm-3 col-form-label" for="adv_link_to_packages"><span><?php _e( 'Link to GeoDirectory', 'advertising' )?></span></label>
				<label class="col-sm-8">
					<input type="checkbox" id="adv_link_to_packages" name="link_to_packages" value="1" <?php checked( (int) adv_zone_get_meta( $post->ID, 'link_to_packages', true, 0 ), 1 ); ?> />
					<span><?php _e( 'If checked, users will only be able to add ads to this zone when they publish a listing on your site.', 'advertising' ); ?></span>&nbsp;
				</label>
			</div>
			<?php do_action( 'adv_zone_details_form_after_link_to_packages', $post ); ?>
		<?php
	}

	/**
	 * Retrieves a list of zones for use with dropdowns.
	 */
	public static function get_dropdown_zones() {
		// Get a list of all zones.
		$all_zones = adv_get_zones(
			array(
				'fields'        => 'all',
				'meta_query'    => array(
					array(
						'key'     => '_adv_zone_link_to_packages',
						'compare' => '=',
						'value'   => '1',
					)
				)
			)
		);

		return wp_list_pluck( $all_zones, 'post_title', 'ID' );
	}

	/**
	 * filter conditions
	 */
	public static function filter_conditions( $conditions ) {
		$conditions['packages'] = array(
			'label' => __( 'Packages', 'advertising' ),
			'icon'  => 'dashicons-money-alt',
		);

		return $conditions;
	}

	/**
	 * display conditions
	 */
	public static function display_condition( $meta_prefix ) {
		global $aui_bs5, $adv_post;

		$post_id      = !empty( $adv_post->ID ) ? $adv_post->ID : 0;

		$packages_to  = get_post_meta( $post_id, "{$meta_prefix}packages_to", true );
		$packages_to  = ! empty( $packages_to ) ? $packages_to : 'all';
		$packages     = get_post_meta( $post_id, "{$meta_prefix}packages", true );
		$packages     = !empty( $packages ) ? $packages : array();
		$all_packages = GeoDir_Pricing_Package::get_packages();

		if ( $packages_to == 'show' || $packages_to == 'hide' ) {
			$style = '';
		} else {
			$style    = 'display:none;';
			$packages = array();
		}

		?>
		<table class="form-table">
			<tbody>
				<tr class="tr-packages">
					<th valign="top" scope="row">
						<label for="packages_to"><?php _e( 'Package Settings:', 'advertising' ); ?></label>
					</th>
					<td>
						<select name="packages_to" id="packages_to" class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?> form-select regular-text">
							<option value="all" <?php selected( $packages_to == 'all', true ); ?>><?php _e( 'Show on all packages', 'advertising' ); ?></option>
							<option value="show" <?php selected( $packages_to == 'show', true ); ?>><?php _e( 'Only show on selected packages', 'advertising' ); ?></option>
							<option value="hide" <?php selected( $packages_to == 'hide', true ); ?>><?php _e( 'Hide on selected packages', 'advertising' ); ?></option>
						</select>
						<div class="adv-check-boxes" style="<?php echo $style; ?>">
							<?php foreach ( $all_packages as $package ) { ?>
								<div class="<?php echo ( $aui_bs5 ? 'mb-3' : 'form-group' ); ?>">
									<label for="adv_packages_<?php echo (int) $package->id; ?>"><input value="<?php echo (int) $package->id; ?>" id="adv_packages_<?php echo (int) $package->id; ?>" name="packages[]" type="checkbox" <?php checked( in_array( $package->id, $packages ), true ); ?> /> <?php echo esc_html( $package->name ); ?> <code>( <?php echo esc_html( $package->post_type ); ?> )</code></label>
								</div>
							<?php } ?>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<div class="alert alert-info " role="alert"><i class="fas fa-info-circle"></i> <?php _e( 'Applies to details page only', 'advertising' ); ?></div>
		<?php
	}

	/**
	 * Add metabox fields
	 */
	public static function metabox_fields ( $fields ) {
		$fields[] = '_adv_zone_packages_to';
		$fields[] = '_adv_zone_packages';
		return $fields;
	}

	/**
	 * Checks if the zone can be displayed
	 */
	public static function can_display( $can_display, $object  ) {
		global $gd_post;

		// Abort if this is not a supported object.
		if ( ! ( $object instanceof Adv_Zone ) && ! ( $object instanceof Adv_Ad ) ) {
			return $can_display;
		}

		// Ensure we're on a listing details page that has a package...
		if ( empty( $can_display ) || empty( $gd_post ) || empty( $gd_post->package_id ) || ! geodir_is_page( 'single' ) ) {
			return $can_display;
		}

		// And that we're filtering by packages.
		$packages    = $object->get( 'packages' );
		$packages_to = $object->get( 'packages_to' );

		if ( empty( $packages ) || empty( $packages_to ) || 'all' == $packages_to ) {
			return $can_display;
		}

		// Is the current package selected?
		$is_selected = in_array( $gd_post->package_id, $packages );

		// Are we only showing on selected packages?
		if ( 'show' === $packages_to ) {
			return $is_selected;
		}

		return ! $is_selected;
	}

}
