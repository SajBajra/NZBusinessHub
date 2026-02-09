<?php
/**
 * REST API ads controller
 *
 * Handles requests to the /ads endpoint.
 *
 * @package GetPaid
 * @subpackage REST API
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid REST ads controller class.
 *
 * @package Advertising
 */
class GetPaid_REST_Ads_Controller extends GetPaid_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'ads';

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
			'post_type'     => 'adv_ad',
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
	 * @param WP_Post $ad Post data.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $ad, $request ) {
		$zone = adv_ad_get_meta( $ad->ID, 'zone', true );
		$data = array(
			'ID'          => $ad->ID,
			'zone'        => (int) $zone,
			'is_gd_zone'  => $zone && adv_zone_get_meta( (int) $zone, 'link_to_packages', true, 0 ),
			'type'        => adv_ad_get_meta( $ad->ID, 'type', true ),
			'target_url'  => adv_ad_get_meta( $ad->ID, 'target_url', true ),
			'locations'   => adv_ad_get_meta( $ad->ID, 'locations', true ),
			'image'       => adv_ad_get_meta( $ad->ID, 'image', true ),
			'code'        => adv_ad_get_meta( $ad->ID, 'code', true ),
			'description' => adv_ad_get_meta( $ad->ID, 'description', true ),
			'listing'     => adv_ad_get_meta( $ad->ID, 'listing', true ),
			'new_tab'     => (bool) adv_ad_get_meta( $ad->ID, 'new_tab', true ),
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );
		$response->add_links( array(
			'self' => array(
				'href' => rest_url( sprintf( '/%s/%s/%s', $this->namespace, $this->rest_base, $ad->ID ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ),
			),
		) );

		return apply_filters( 'getpaid_ads_rest_prepare_ad', $response, $ad, $request );
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
				'zone' => array(
					'description' => __( 'A numeric identifier for the zone.', 'advertising' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'description' => array(
					'description' => __( 'A human-readable description of the resource.', 'advertising' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'is_gd_zone' => array(
					'description' => __( 'Whether or not the ad is in a GD zone.', 'advertising' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'type' => array(
					'description' => __( 'The type of ad.', 'advertising' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'target_url' => array(
					'description' => __( 'The target URL for the ad.', 'advertising' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'locations' => array(
					'description' => __( 'The target location for the ad.', 'advertising' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'image' => array(
					'description' => __( 'The associated image.', 'advertising' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'code' => array(
					'description' => __( 'The raw HTML ad code.', 'advertising' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'listing' => array(
					'description' => __( 'The associated GeoDirectory listing', 'advertising' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'new_tab' => array(
					'description' => __( 'Whether or not to open the target URL in a new tab.', 'advertising' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
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
