<?php
/**
 * REST API zones controller
 *
 * Handles requests to the /ad_zones endpoint.
 *
 * @package GetPaid
 * @subpackage REST API
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid REST ad zones controller class.
 *
 * @package Advertising
 */
class GetPaid_REST_Ad_Zones_Controller extends GetPaid_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'ad_zones';

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 2.0.0
	 *
	 * @see register_rest_route()
	 */
	public function register_namespace_routes( $namespace ) {

		register_rest_route(
			$namespace,
			$this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

	}

	/**
	 * Makes sure the current user has access to view ads.
	 *
	 * @since  2.0.0
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {

		if ( ! wpinv_current_user_can_manage_invoicing() ) {
			return new WP_Error( 'rest_cannot_view', __( 'Sorry, you cannot list resources.', 'advertising' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get all ads.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request
	 * @return array|WP_Error
	 */
	public function get_items( $request ) {

		$args = array(
			'post_type'     => 'adv_zone',
			'post_status'   => 'publish',
			'numberposts'   => '-1',
			'orderby'       => 'title',
			'order'         => 'ASC',
		);

		$data = array();
		foreach ( get_posts( $args ) as $post ) {
			$item   = $this->prepare_item_for_response( $post, $request );
			$data[] = $this->prepare_response_for_collection( $item );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Prepare a response object for serialization.
	 *
	 * @since 2.0.0
	 * @param WP_Post $zone Post data.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $zone, $request ) {
		$data = array(
			'ID'                 => $zone->ID,
			'name'               => $zone->post_title,
			'description'        => get_the_excerpt( $zone->ID ),
			'price'              => floatval( adv_zone_get_meta( $zone->ID, 'price', true, 0 ) ),
			'pricing_term'       => (float) adv_zone_get_meta( $zone->ID, 'pricing_term', true, '1000' ),
			'pricing_type'       => adv_zone_get_meta( $zone->ID, 'pricing_type', true, 'impressions' ),
			'show_name'          => 'yes' == adv_zone_get_meta( $zone->ID, 'show_title', true, 'yes' ),
			'link_to_packages'   => (bool) adv_zone_get_meta( $zone->ID, 'link_to_packages', true, false ),
			'display_grid'       => (bool) adv_zone_get_meta( $zone->ID, 'display_grid', true, 0 ),
			'ads_per_grid'       => (float) adv_zone_get_meta( $zone->ID, 'ads_per_grid', true, 2 ),
			'hide_frontend'      => (bool) adv_zone_get_meta( $zone->ID, 'hide_frontend', true, 0 ),
			'advertise_here_url' => adv_zone_get_meta( $zone->ID, 'advertise_here_url', true, '' ),
			'link_position'      => adv_zone_get_meta( $zone->ID, 'link_position', true, 1 ),
			'count'              => adv_zone_get_meta( $zone->ID, 'count', true, 1 ),
			'max_ads'            => adv_zone_get_meta( $zone->ID, 'max_ads', true, '' ),
			'width'              => adv_zone_get_meta( $zone->ID, 'width', true ),
			'height'             => adv_zone_get_meta( $zone->ID, 'height', true ),
			'allowed_ad_types'   => adv_zone_get_meta( $zone->ID, 'allowed_ad_types', true, false )
		);

		if ( empty( $data['allowed_ad_types'] ) ) {
			$data['allowed_ad_types'] = array_keys( advertising_ad_types() );
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );
		$response->add_links( array(
			'self' => array(
				'href' => rest_url( sprintf( '/%s/%s/%s', $this->namespace, $this->rest_base, $zone->ID ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ),
			),
		) );

		return apply_filters( 'getpaid_ads_rest_prepare_zone', $response, $zone, $request );
	}

	/**
	 * Get the zones's schema, conforming to JSON Schema.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'zoneujn',
			'type'       => 'object',
			'properties' => array(
				'ID' => array(
					'description' => __( 'A numeric identifier for the resource.', 'advertising' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'name' => array(
					'description' => __( 'A human-readable name for the resource.', 'advertising' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'description' => array(
					'description' => __( 'A human-readable description of the resource.', 'advertising' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'price' => array(
					'description' => __( 'The cost for advertising in the zone.', 'advertising' ),
					'type'        => 'number',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'pricing_term' => array(
					'description' => __( 'The term per cost.', 'advertising' ),
					'type'        => 'number',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'pricing_type' => array(
					'description' => __( 'The type of pricing term.', 'advertising' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'show_name' => array(
					'description' => __( 'Whether or not to display the name of the zone', 'advertising' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'link_to_packages' => array(
					'description' => __( 'Whether or not the zone is linked to a GeoDirectory package', 'advertising' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'display_grid' => array(
					'description' => __( 'Whether or not to display ads in a grid', 'advertising' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'ads_per_grid' => array(
					'description' => __( 'The number of ads to display per row in the grid.', 'advertising' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'hide_frontend' => array(
					'description' => __( 'Whether or not to hide the zone when creating ads via the frontend.', 'advertising' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'advertise_here_url' => array(
					'description' => __( 'The URL to redirect users who click on the advertise here link.', 'advertising' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'link_position' => array(
					'description' => __( 'The advertise here link position.', 'advertising' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'count' => array(
					'description' => __( 'The number of visible ads at any given time.', 'advertising' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'max_ads' => array(
					'description' => __( 'The maximum number of ads that can be added to the zone.', 'advertising' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'width' => array(
					'description' => __( 'The prefered zone width.', 'advertising' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'height' => array(
					'description' => __( 'The prefered zone height.', 'advertising' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'allowed_ad_types' => array(
					'description' => __( 'The allowed ad types.', 'advertising' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'enum'        => array_keys( advertising_ad_types() ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);
	}
}
