<?php
// Check GD_Social_Importer_Rest class exists or not.
if( ! class_exists( 'GD_Social_Importer_Rest' ) ) {
    /**
     * The rest-specific functionality of the plugin.
     *
     * @since 2.0.0
     *
     * @package    GD_Social_Importer
     * @subpackage GD_Social_Importer/rest
     *
     * Class GD_Social_Importer_Rest
     */

    class GD_Social_Importer_Rest extends WP_REST_Controller {

        /**
         * Constructor.
         *
         * @since 2.0.0
         *
         * GD_Social_Importer_Rest constructor.
         */
        public function __construct() {
            $this->namespace = 'geodir/v2';
            $this->rest_base = 'social_importer';

            // Register rest routes.
            add_action( 'rest_api_init', array( $this, 'register_routes' ) );
        }

        /**
         * Registers REST routes.
         *
         * @since 1.0.0
         */
        public function register_routes() {

            // Validates the extension token.
            register_rest_route(
                $this->namespace,
                '/' . $this->rest_base . '/validate_token',
                array(
                    array(
                        'methods'             => WP_REST_Server::CREATABLE,
                        'callback'            => array( $this, 'validate_token' ),
                        'permission_callback' => '__return_true',
                    ),
                    'schema' => '__return_empty_array',
                )
            );

            // Imports a listing posted from the extension.
            register_rest_route(
                $this->namespace,
                '/' . $this->rest_base . '/import',
                array(
                    array(
                        'methods'             => WP_REST_Server::CREATABLE,
                        'callback'            => array( $this, 'import' ),
                        'permission_callback' => '__return_true',
                    ),
                    'schema' => '__return_empty_array',
                )
            );

        }

        /**
         * Validates a user token.
         *
         * @param WP_REST_Request $request
         */
        public function validate_token( $request ) {
            // Ensure we have a token.
            if ( !isset($request['token']) || empty( $request['token'] ) ) {
                return new WP_Error( 'no_token', esc_html__( 'Please provide a valid token.', 'geodir-booking' ), array( 'status' => 'error' ) );
            }

		    $token = wp_unslash( $request['token'] );

            $user_query = new WP_User_Query(array(
                'meta_key' => 'gmb_extension_token_id',
                'meta_value' => $token,
            ));
        
            // Get the results
            $users = $user_query->get_results();
            if ( empty($users) ) {
                return new WP_Error( 'no_user', esc_html__( 'User not found.', 'geodir-booking' ), array( 'status' => 'error' ) );
            }

            $user_info = $users[0];

            $website_info = array(
                'status'    => 'success',
                'data'      => array(
                    'name'      => get_bloginfo('name'),
                    'url'       => home_url(),
                    'user_id'   => (int) $user_info->ID,
                )
            );

		    return rest_ensure_response( $website_info );
        }

        /**
         * Import listing.
         *
         * @param WP_REST_Request $request
         */
        public function import( $request ) {

        }
    }
    
}