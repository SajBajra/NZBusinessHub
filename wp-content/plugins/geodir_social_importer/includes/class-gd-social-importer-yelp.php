<?php

// Check GD_Social_Importer_Yelp class exists or not.
if( ! class_exists( 'GD_Social_Importer_Yelp' ) ) {

	/**
	 * GD_Social_Importer_Yelp Class for Yelp import actions.
	 *
	 * @since 2.0.0
	 *
	 * Class GD_Social_Importer_Yelp
	 */
	class GD_Social_Importer_Yelp {

		/**
		 * Constructor.
		 *
		 * @since 2.0.0
		 *
		 * GD_Social_Importer_Yelp constructor.
		 */
		public function __construct() {

		}

		/**
		 * Get listing fields record using Yelp page id.
		 *
		 * @since 2.0.0
		 *
		 * @param string $page_id Yelp page id.
		 *
		 * @return string $response
		 *
		 * @throws Exception
		 */
		public function gdfi_yelp_get_v3( $page_id ) {
			global $gd_post;

			$response = array();

			$yelp_api_key = geodir_get_option('si_yelp_api_key');

			if ( !empty( $yelp_api_key ) && class_exists( 'Geodir_Yelp' ) ) {

				$yelp = new Geodir_Yelp( $yelp_api_key );

			} else {

				return __('Invalid Yelp API Key.', 'gd-social-importer');

			}

			if ( ! empty( $yelp ) && $yelp->get_error() ) {

				return $yelp->get_error();

			}

			$business = $yelp->business($page_id);

//			print_r($business );exit;

			if ( empty( $business ) ) {

				return __('Something went wrong[300], this page/event may not be public or does not exist!', 'gd-social-importer');

			}

			if ( ! empty( $business['error']['description'] ) ) {

				return wp_sprintf(__('[%s] %s', 'gd-social-importer'), $business['error']['code'], __($business['error']['description'], 'gd-social-importer'));

			}

			$response['is_yelp'] = true;

			if( !empty( $response ) && !isset( $response['error'] ) ) {

//				print_r($business);

				$post_id = ! empty( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
				$gd_post = geodir_get_post_info( $post_id );
				
				$social_importer_obj = new Social_Importer_General();

				$page_url = !empty( $business['url'] ) ? explode('?',$business['url']) :'';
				$page_url = !empty( $page_url ) ? $page_url[0] :'';

				$yelp_business_hour = $this->get_yelp_busieness_hour( $business['hours'] );

				$get_bh_hidden_value = $social_importer_obj->get_business_hours_hidden_time($yelp_business_hour);
				
				$cf = geodir_get_field_infoby( 'htmlvar_name', 'post_images', get_post_type( $post_id ) );
				$image_limit = apply_filters( "geodir_custom_field_file_limit", 0, $cf, $gd_post ); // Images limit

				$image_title = !empty($business['alias']) ? esc_attr($business['alias']) : esc_attr($business['name']);

				$get_images = $this->get_yelp_images( $business['photos'], $image_limit, 0, $image_title );

				$get_image_path = $social_importer_obj->get_post_images_path($get_images);

				$country_code = !empty( $business['location']['country'] ) ? $business['location']['country'] :'';
				$country = !empty( $business['location']['display_address'] ) ? end($business['location']['display_address']) :'';
				$address = !empty( $business['location']['display_address'] ) ? implode(', ',$business['location']['display_address']) : '';

				$phone_number = !empty( $business['phone'] ) ? $business['phone'] :'';

				$response['yelp_title'] = ! empty( $business['name'] ) ? $business['name'] : '';
				$response['yelp_description'] ='';
				$response['yelp_address'] =  !empty( $address ) ? $address :'';
				$response['yelp_zipcode'] = !empty( $business['location']['zip_code'] ) ? $business['location']['zip_code'] :'';
				$response['yelp_mobile'] = !empty( $business['display_phone'] ) ? $business['display_phone'] : $phone_number ;
				$response['yelp_website'] = '';
				$response['yelp_email'] = '';
				$response['yelp_country_code'] = !empty( $country_code ) ? $country_code :'';
				$response['yelp_country'] = !empty( $country ) ? $country :'';
				$response['yelp_region'] = '';
				$response['yelp_city'] = !empty( $business['location']['city'] ) ? $business['location']['city'] :'';
				$response['yelp_business_hour'] = !empty( $yelp_business_hour ) ? $yelp_business_hour :'';
				$response['yelp_business_hidden_hour'] = !empty( $get_bh_hidden_value ) ? $get_bh_hidden_value :'';
				$response['yelp_images_path'] = !empty( $get_image_path ) ? $get_image_path :'';
				$response['yelp_images_count'] = !empty( $get_images ) ?  count($get_images):'';
				$response['yelp_latitude'] = !empty( $business['coordinates']['latitude'] ) ? $business['coordinates']['latitude'] :'';
				$response['yelp_longitude'] = !empty( $business['coordinates']['longitude'] ) ? $business['coordinates']['longitude'] :'';

				$_description = array();
				if ( ! empty( $response['yelp_title'] ) ) {
					$_description[] = $response['yelp_title'];
				}
				if ( ! empty( $business['price'] ) ) {
					$_description[] = $business['price'];
				}
				if ( ! empty( $business['categories'] ) ) {
					$categories = array();
					foreach ( $business['categories'] as $category ) {
						$categories[] = $category['title'];
					}
					$_description[] = implode( ", ", $categories );
				}
				if ( ! empty( $response['yelp_address'] ) ) {
					$_description[] = $response['yelp_address'];
				}

				$description = ! empty( $_description ) ? implode( ' | ', $_description ) : $response['yelp_description'];
				$response['yelp_description'] = apply_filters( 'geodir_social_importer_response_yelp_description', $description, $_description, $response, $business, $page_id, $gd_post );

				$response = apply_filters( 'geodir_social_importer_response_yelp', $response, $business, $page_id, $gd_post );
			}

			return json_encode( $response );
		}

		/**
		 * Get Business hour from using Yelp API.
		 *
		 * @since 2.0.0
		 *
		 * @param array $business_hour Get Business hour from Yelp API.
		 *
		 * @return array|bool $business_hours.
		 */
		public function get_yelp_busieness_hour( $business_hour ) {
			$business_hours = array();

			$get_hour_arr = !empty( $business_hour[0]['open'] ) ? $business_hour[0]['open'] :'';

			if ( empty( $get_hour_arr ) && $get_hour_arr == '' ) {
				return false;
			}

			$social_importer_obj = new Social_Importer_General();

			$weeks = array( 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su' );
			$time_format = geodir_bh_input_time_format();

			if ( ! empty( $get_hour_arr ) && is_array( $get_hour_arr ) ) {
				foreach ( $get_hour_arr as $bh_key => $bh_values ) {
					$day_no = isset( $bh_values['day'] ) ? $bh_values['day'] : $bh_key;
					$hour_arr = array();

					$open_time = $social_importer_obj->convert_time_in_24h_format( trim( $bh_values['start'] ) );
					$close_time = $social_importer_obj->convert_time_in_24h_format( trim( $bh_values['end'] ) );

					$temp_arr['open'] = ! empty( $open_time ) ? trim( $open_time ) : '';
					$temp_arr['close'] = ! empty( $close_time ) ? trim( $close_time ) : '';
					$temp_arr['open_display'] = $temp_arr['open'] ? date_i18n( $time_format, strtotime( $temp_arr['open'] ) ) : $temp_arr['open'];
					$temp_arr['close_display'] = $temp_arr['close'] ? date_i18n( $time_format, strtotime( $temp_arr['close'] ) ) : $temp_arr['close'];

					$hour_arr[] = $temp_arr;

					$business_hours[ $weeks[ $day_no ] ] = $hour_arr;
				}
			}

			return $business_hours;
		}

		/**
		 * Get images from yelp using API and insert to listing and get Uploaded images array.
		 *
		 * @since 2.0.0
		 *
		 * @param array $images_arr Get Images array from yelp using API.
		 *
		 * @return array $images
		 */
		public function get_yelp_images( $images_arr, $image_limit = 0, $total_images = 0, $image_title = '' ) {
			global $wpdb;

			$listing_id = !empty( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) :'';

			$images = array();

			if ( empty( $images_arr) ) {
				return $images;
			}

			if ( $image_limit > 0 ) {
				$image_limit = $image_limit - $total_images;

				if ( $image_limit <= 0 ) {
					return false;
				}
			}

			$post_type = get_post_type( $listing_id );
			$detail_table = geodir_db_cpt_table( $post_type );

			$gd_media_obj = new GeoDir_Media();

			if ( ! empty( $images_arr ) && is_array( $images_arr ) ) {
				foreach ( $images_arr as $img_key => $img_values ) {
					$_image_title = preg_replace( '/[\-_|]/', ' ', $image_title );
					$image_title = apply_filters( 'geodir_social_post_image_title', $_image_title, array( 'post_type' => $post_type, 'site' => 'yelp', 'image_name' => $image_title, 'post_id' => $listing_id, 'order' => $img_key, 'image_url' => $img_values ) );

					$uploaded_file_path = $gd_media_obj::insert_attachment( $listing_id, 'post_images', $img_values, $image_title, '', -1, 0, 0 );

					if ( ! is_wp_error( $uploaded_file_path ) && ! empty( $uploaded_file_path['file'] ) ) {
						$temp_array = array(
							'ID' => ! empty( $uploaded_file_path['ID'] ) ? $uploaded_file_path['ID'] :0,
							'post_id' => ! empty( $uploaded_file_path['post_id'] ) ? $uploaded_file_path['post_id'] :0,
							'file' => ! empty( $uploaded_file_path['file'] ) ? $uploaded_file_path['file'] :'',
							'type' => ! empty( $uploaded_file_path['type'] ) ? $uploaded_file_path['type'] :'',
							'user_id' => ! empty( $uploaded_file_path['user_id'] ) ? $uploaded_file_path['user_id'] :0,
							'title' => $image_title,
						);

						$images[] = $temp_array;
					} else {
						geodir_error_log( $uploaded_file_path->get_error_message(), 'insert_attachment', __FILE__, __LINE__ );
					}

					if ( $image_limit > 0 && count( $images ) >= $image_limit ) {
						return $images;
					}
				}
			}

			return $images;
		}
	}
}