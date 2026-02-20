<?php

// Check GeoDir_Lists_Data class exists or not.
if( ! class_exists( 'GeoDir_Lists_Data' ) ) {

    /**
     * GeoDir_Lists_Datas Class for the CPT actions.
     *
     * @since 2.0.0
     *
     * Class GeoDir_Lists_Data
     */
    class GeoDir_Lists_Data{

        /**
         * Constructor.
         *
         * @since 2.0.0
         *
         * GeoDir_Lists_Data constructor.
         */
        public function __construct() {

           // add_filter( 'geodir_loop_active', array( $this, 'maybe_loop' ) );

        }
        
        public function get_posts( $post_id = '' ) {
            global $post,$wpdb;

            if ( $post_id == '' && $post->ID ) {
                $post_id = $post->ID;
            }

            $posts = array();
            $post_ids = array();

            if ( $post_id ) {
                $post_ids_obj = $wpdb->get_results( $wpdb->prepare( "SELECT p2p_from FROM $wpdb->p2p WHERE p2p_to = %d", absint( $post_id ) ) );

                if ( ! empty( $post_ids_obj ) ) {
                    foreach( $post_ids_obj as $obj ){
                        $post_ids[] = $obj->p2p_from;
                    }
                }

                $real_post_status = geodir_lists_get_real_post_status( $post_id );
                $post_statuses = geodir_get_publish_statuses();
                if ( $real_post_status == 'private' ) {
                    $post_statuses[] = 'private';
                }
                $post_types = geodir_get_posttypes();

                foreach ( $post_types as $post_type ) {
                    $post_statuses = array_merge( $post_statuses, geodir_get_publish_statuses( array( 'post_type' => $post_type ) ) );

                    if ( $real_post_status == 'private' && ( $non_public_statuses = geodir_get_post_stati( 'non-public', array( 'post_type' => $post_type ) ) ) ) {
                        $post_statuses = array_merge( $post_statuses, $non_public_statuses );
                    }
                }

                if ( ! empty( $post_ids ) ) {
                    $query_args = array(
                        'posts_per_page' => -1,
                        'is_geodir_loop' => true,
                        'gd_location' => false,
                        'post_type' => $post_types,
                        'post__not_in' => array( $post_id ),
                        'post__in' => $post_ids,
                        'post_status' => array_unique( $post_statuses )
                    );

                    $posts = get_posts($query_args);
                }
            }

            return $posts;
        }

        /**
         * Get the users lists.
         * 
         * @param string $user_id
         *
         * @return array|null|object
         */
        public static function get_user_lists($user_id = ''){
            global $wpdb;
            $lists = array();

            $get_private_lists  = '';
            $current_user_id = get_current_user_id();
            if(!$user_id){
                $user_id = $current_user_id;
            }elseif($current_user_id == $user_id){
                $get_private_lists  = ",'private'";
            }

            if($user_id){
                $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_type = 'gd_list' AND post_author = %d AND post_status IN ('publish'$get_private_lists) ORDER BY post_date DESC", absint($user_id)));
                if(!empty($results)){
                    $lists = $results;
                }
            }

            return $lists;
        }

        /**
         * Get the users lists as an array of ids.
         *
         * @param string $user_id
         *
         * @return array|null|object
         */
        public static function get_user_lists_ids($user_id = ''){
            $lists = array();

            if(!$user_id){
                $user_id = get_current_user_id();
            }

            if($user_id){
                $lists_arr = self::get_user_lists($user_id);
                if(!empty($lists_arr)){
                    foreach($lists_arr as $list){
                        $lists[] = absint($list->ID);
                    }
                }
            }

            return $lists;
        }

        /**
         * Get the list image.
         *
         * @todo add the CPT image as fallback and a blank image as fall back to that.
         * @param $post_id
         * @param string $size
         *
         * @return string
         */
        public static function get_list_image($post_id,$size = 'thumbnail'){
            $image = get_the_post_thumbnail($post_id, $size );
            if(!$image){
                $image = self::get_list_cpt_image( $size );
            }

            // if no image show a list icon
            if(!$image){
                $image = '<span class=""><i class="fas fa-list" aria-hidden="true"></i></span>';
            }

            return $image;
        }

        /**
         * Get the list CPT default image;
         *
         * @param string $size
         *
         * @return string
         */
        public static function get_list_cpt_image($size = 'thumbnail'){
            $cpt = geodir_get_option('list_post_type');
            $image_id = !empty($cpt['default_image']) ? absint($cpt['default_image']) : '';
            $image = '';
            if($image_id){
                $image = wp_get_attachment_image($image_id, $size);
            }
            return $image;
        }


        /**
         * Check if a post is in a list.
         *
         * @param $list_id
         * @param $post_id
         *
         * @return array|null|object
         */
        public static function has_post($list_id,$post_id){
            global $wpdb;
            $result = false;


            if($list_id && $post_id){
                $results = $wpdb->get_results($wpdb->prepare("SELECT p2p_from FROM $wpdb->p2p WHERE   p2p_to = %d AND p2p_from = %d", $list_id, $post_id));
                if(!empty($results)){
                    $result = true;
                }

            }
            return $result;
        }


        public static function in_user_lists($post_id){
            global $wpdb;
            $result = false;

            $user_id = get_current_user_id();

            if($user_id && $post_id){
                $user_lists = self::get_user_lists_ids( $user_id ); // this is already escaped with absint()
                if(!empty($user_lists)){
                    $user_lists = implode( ",", $user_lists );
                    $results = $wpdb->get_row($wpdb->prepare("SELECT p2p_id FROM $wpdb->p2p WHERE p2p_from = %d AND   p2p_to IN ($user_lists) ", $post_id));
                    if(!empty($results)){
                        $result = true;
                    }
                }
            }
            return $result;
        }

        public static function save_to_list($list_id,$post_id){

            if(!function_exists('p2p_type')){return false;}
            $post_type = get_post_type($post_id);
            // Create connection
            $result = p2p_type( $post_type .'_to_gd_list' )->connect( $post_id, $list_id, array(
                'date' => current_time('mysql')
            ) );

            return $result;
        }

        public static function remove_from_list($list_id,$post_id){

            if(!function_exists('p2p_type')){return false;}
            $post_type = get_post_type($post_id);
            // Create connection
            $result = p2p_type( $post_type .'_to_gd_list' )->disconnect( $post_id, $list_id);

            return $result;
        }

		public static function save_list( $args = array() ) {
			$result = false;

			if ( ! function_exists( 'p2p_type' ) ) {
				return $result;
			}

			$user_id = (int) get_current_user_id();

			// Create new list
			$post_args = array(
				'post_title'   => ! empty( $args['post_title'] ) ? wp_strip_all_tags( $args['post_title'] ) : '',
				'post_content' => ! empty( $args['post_content'] ) ? wp_strip_all_tags( $args['post_content'] ) : '',
				'post_status'  => ! empty( $args['post_status'] ) ? wp_strip_all_tags( $args['post_status'] ) : 'draft',
				'post_author'  => $user_id,
				'post_type'    => 'gd_list',
			);

			// If we don't have a title then bail.
			if ( empty( $post_args['post_title'] ) ) {
				return $result;
			}

			if ( ! empty( $args['ID'] ) ) {
				$post_id = absint( $args['ID'] );
				$post = get_post( $post_id );

				if ( ! empty( $post->post_author ) ) {
					$post_args['post_author'] = (int) $post->post_author;
				}

				if ( ! empty( $post->ID ) && $post->post_type == 'gd_list' && geodir_listing_belong_to_current_user( $post->ID ) ) {
					$post_args['ID'] =  $post_id;

					// Update the post into the database
					$result = wp_update_post( $post_args );
				}
			} else {
				// Generate a random slug so users don't abuse it
				$post_args['post_name'] = sanitize_title( wp_generate_password( 20 ) );

				// Insert the post into the database
				$result = wp_insert_post( $post_args );
			}

			return $result;
		}

		public static function delete_list( $list_id ) {
			if ( ! function_exists( 'p2p_type' ) ) {
				return false;
			}

			$result = false;
			$list_id = absint( $list_id );

			if ( ! empty( $list_id ) && geodir_listing_belong_to_current_user( $list_id ) ) {
				// Trash the list
				$result = wp_trash_post( $list_id );
			}

			return $result;
		}

        public static function get_slug(){

            if(!function_exists('p2p_type')){return false;}
            $result = false;
            $list_id = absint($list_id); // to be sure to be sure
            $user_id = get_current_user_id();
            $post_author_id = get_post_field( 'post_author', $list_id );

            if($list_id && $user_id && $post_author_id && $user_id==$post_author_id){
                // trash the list
                $result = wp_trash_post( $list_id );
            }


            return $result;
        }
        



    }

}