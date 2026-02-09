<?php

// Check GD_Social_Importer_Tripadvisor class exists or not.
if( ! class_exists( 'GD_Social_Importer_Tripadvisor' ) ) {

	/**
	 * GD_Social_Importer_Tripadvisor Class for Tripadvisor import actions.
	 *
	 * @since 2.0.0
	 *
	 * Class GD_Social_Importer_Tripadvisor
	 */
	class GD_Social_Importer_Tripadvisor {

		/**
		 * Constructor.
		 *
		 * @since 2.0.0
		 *
		 * GD_Social_Importer_Tripadvisor constructor.
		 */
		public function __construct() {

		}

		/**
		 * Get listing fields record using Tripadvisor page URL.
		 *
		 * @param string $url Tripadvisor page URL.
		 *
		 * @since 2.0.0
		 *
		 * @return string $field_response.
		 */
		public function get_tripadvisor_reponse( $url ) {
			global $gdfi_cookies, $gd_post;

			$post_id = ! empty( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
			$gd_post = geodir_get_post_info( $post_id );

			$social_importer_obj = new Social_Importer_General();

			$default_city = $social_importer_obj->get_default_city_location();

			// Get page html content using page url.
			$page_html = $social_importer_obj->get_remote_response( $url );

			if ( ! empty( $gdfi_cookies ) && ( empty( $page_html ) || ( ! empty( $page_html ) && strpos( $page_html, '"tel:' ) === false ) ) ) {
				$page_html = $social_importer_obj->get_remote_response( $url, $gdfi_cookies );
			}

			// Grab API data from page content.
			$api_data = $this->get_api_data( $page_html );

			// Get tripadvisor page script data in page content.
			$response_data = $social_importer_obj->get_page_data( $page_html,'<script type="application/ld+json">', '</script>' );
			$response_data = json_decode( $response_data );

			$trip_name = ! empty( $api_data['name'] ) ? $api_data['name'] : '';
			$trip_latitude = ! empty( $api_data['latitude'] ) ? $api_data['latitude'] : '';
			$trip_longitude = ! empty( $api_data['longitude'] ) ? $api_data['longitude'] : '';
			$trip_description = ! empty( $api_data['description'] ) ? $api_data['description'] : '';
			$trip_phone = ! empty( $api_data['phone'] ) ? $api_data['phone'] : '';
			$trip_website = ! empty( $api_data['website'] ) ? $api_data['website'] : '';
			$trip_email = ! empty( $api_data['email'] ) ? $api_data['email'] : '';
			$trip_street = '';
			$trip_city = '';
			$trip_state = '';
			$trip_country = '';
			$trip_postalcode = '';
			if ( ! empty( $api_data['address_obj'] ) ) {
				if ( ! empty( $api_data['address_obj']['street1'] ) ) {
					$trip_street = $api_data['address_obj']['street1'];

					if ( ! empty( $api_data['address_obj']['street2'] ) ) {
						$trip_street .= ', ' . $api_data['address_obj']['street2'];
					}
				}
				if ( ! empty( $api_data['address_obj']['city'] ) ) {
					$trip_city = $api_data['address_obj']['city'];
				}
				if ( ! empty( $api_data['address_obj']['state'] ) ) {
					$trip_state = $api_data['address_obj']['state'];
				}
				if ( ! empty( $api_data['address_obj']['country'] ) ) {
					$trip_country = $api_data['address_obj']['country'];
				}
				if ( ! empty( $api_data['address_obj']['postalcode'] ) ) {
					$trip_postalcode = $api_data['address_obj']['postalcode'];
				}
			}

			if ( empty( $trip_latitude ) ) {
				$get_address_html = $social_importer_obj->get_page_data($page_html,'<span class="ui_icon map-pin">','</div>');
				if ( empty( $get_address_html ) && "" == $get_address_html ) {
					$get_address_html = $social_importer_obj->get_page_data($page_html,'<span class= "ui_icon map-pin-fill">','</div>');
				}

				$get_address = $this->get_address( $get_address_html );
				$lat_long = $social_importer_obj->get_address_response( $get_address );

				if ( ! empty( $lat_long['latitude'] ) ) {
					$trip_latitude = $lat_long['latitude'];
				}

				if ( ! empty( $lat_long['longitude'] ) ) {
					$trip_longitude = $lat_long['longitude'];
				}
			}

			if ( empty( $trip_name ) && ! empty( $response_data->name ) ) {
				$trip_name = $response_data->name;
			}

			$og_image = '';
			if ( empty( $trip_description ) ) {
				// Get og meta tag values using page content.
				$og_meta_values = $social_importer_obj->get_og_meta_tags_values( $page_html, true );

				if ( ! empty( $og_meta_values ) ) {
					if ( empty( $trip_name ) && ! empty( $og_meta_values['schema']['name'] ) ) {
						$trip_name = $og_meta_values['schema']['name'];
					}

					if ( ! empty( $og_meta_values['schema']['address'] ) ) {
						$address_node = (array) $og_meta_values['schema']['address'];

						if ( empty( $trip_street ) && ! empty( $address_node['streetAddress'] ) ) {
							$trip_street = $address_node['streetAddress'];
						}

						if ( empty( $trip_city ) && ! empty( $address_node['addressLocality'] ) ) {
							$trip_city = $address_node['addressLocality'];
						}

						if ( empty( $trip_state ) && ! empty( $address_node['addressRegion'] ) ) {
							$trip_state = $address_node['addressRegion'];
						}

						if ( empty( $trip_postalcode ) && ! empty( $address_node['postalCode'] ) ) {
							$trip_postalcode = $address_node['postalCode'];
						}

						if ( empty( $trip_longitude ) && ! empty( $trip_street ) ) {
							$_trip_street = trim( $trip_street . ' ' . $trip_city . ' ' . $trip_postalcode );
							$lat_long = $social_importer_obj->get_address_response( $_trip_street );

							if ( ! empty( $lat_long['latitude'] ) ) {
								$trip_latitude = $lat_long['latitude'];
							}

							if ( ! empty( $lat_long['longitude'] ) ) {
								$trip_longitude = $lat_long['longitude'];
							}

							if ( ! empty( $lat_long['country_name'] ) ) {
								$trip_country = $lat_long['country_name'];
							}
						}
					}

					if ( ! empty( $og_meta_values['description'] ) ) {
						$trip_description = $og_meta_values['description'];
					}

					if ( ! empty( $og_meta_values['image'] ) ) {
						$og_image = $og_meta_values['image'];
					}
				}
			}

			if ( empty( $trip_phone ) ) {
				// Get tripadvisor page mobile number using page content.
				$trip_phone = $social_importer_obj->get_page_data($page_html,'<span class="is-hidden-mobile detail">','</span>');

				if ( empty( $trip_phone ) ) {
					$trip_phone = $social_importer_obj->get_page_data( $page_html, '<span class="detail  is-hidden-mobile">', '</span>' );
				}

				if ( ! empty( $trip_phone ) ) {
					$get_contact_string = $social_importer_obj->get_page_data($page_html,'<div class="blEntry phone">','</div>');

					preg_match_all( '!\d+!', $get_contact_string, $matches );

					if ( ! empty( $matches ) && count( $matches ) > 0 ) {
						$trip_phone = ! empty( $matches[0] ) ? '+'.implode( " ", $matches[0] ) : '';
					}
				}

				if ( empty( $trip_phone ) ) {
					$phone_html = explode( '"tel:', $page_html, 2 );

					if ( ! empty( $phone_html[1] ) ) {
						$phone_html = explode( '" ', $phone_html[1], 2 );

						if ( ! empty( $phone_html[0] ) ) {
							$trip_phone = strip_tags( $phone_html[0] );
						}
					}
				}
			}

			if ( empty( $trip_email ) ) {
				// Get tripadvisor page email address using page content.
				$email_content = $social_importer_obj->get_page_data($page_html,'<ul class="detailsContent">','</ul>');

				if ( strpos( $email_content, 'mailto' ) !== false) {
					preg_match_all('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}/', $email_content, $email_match );
					$trip_email = !empty( $email_match[0][0] ) ? $email_match[0][0] :'';
				}
	
				if ( empty( $trip_email ) ) {
					$email_html = explode( '"mailto:', $page_html, 2 );

					if ( ! empty( $email_html[1] ) ) {
						$email_html = explode( '" ', $email_html[1], 2 );

						if ( ! empty( $email_html[0] ) ) {
							$trip_email = strip_tags( $email_html[0] );
						}
					}
				}
			}

			// Business hours
			$business_hours = array();
			if ( ! empty( $api_data['hours'] ) ) {
				$business_hours = $this->parse_business_hours( $api_data['hours'] );
			}

			if ( empty( $business_hours ) ) {
				$business_hours = $this->get_business_hours( $page_html );
			}

			$_business_hours = $social_importer_obj->get_business_hours_hidden_time( $business_hours );

			// Images
			$cf = geodir_get_field_infoby( 'htmlvar_name', 'post_images', get_post_type( $post_id ) );
			$image_limit = apply_filters( "geodir_custom_field_file_limit", 0, $cf, $gd_post ); // Images limit

			$images = array();
			$_images = $this->get_images( $page_html, $og_image, $image_limit );
			if ( ! empty( $_images ) ){
				foreach ( $_images as $image ) {
					$images[] = $image['url'] . "|" . $image['ID'] . "|" . ( ! empty( $image['title'] ) ? $image['title'] : "" ) . "|";
				}
			}

			$data = array();
			if ( ! empty( $response_data ) ) {
				$address_arr = ! empty( $response_data->address ) ? (array)$response_data->address : '';

				if ( empty( $trip_street ) && ! empty( $address_arr['streetAddress'] ) ) {
					$trip_street = $address_arr['streetAddress'];
				}
				if ( empty( $trip_city ) && ! empty( $address_arr['addressLocality'] ) ) {
					$trip_city = $address_arr['addressLocality'];
				}
				if ( empty( $trip_state ) && ! empty( $address_arr['addressRegion'] ) ) {
					$trip_state = $address_arr['addressRegion'];
				}
				if ( empty( $trip_country ) && ! empty( $address_arr['addressCountry'] ) && ( $country_obj = (array)$address_arr['addressCountry'] ) ) {
					$trip_country = $country_obj['name'];
				}
				if ( empty( $trip_postalcode ) && ! empty( $address_arr['postalCode'] ) ) {
					$trip_postalcode = $address_arr['postalCode'];
				}

				// Get tripadvisor fields response array.
				$data['is_tripadvisor'] = true;
				$data['trip_title'] = $trip_name;
				$data['trip_description'] = $trip_description;
				$data['trip_address'] = $trip_street;
				$data['trip_city'] = $trip_city;
				$data['trip_region'] = $trip_state;
				$data['trip_country'] = $trip_country;
				$data['trip_zipcode'] = $trip_postalcode;
				$data['trip_latitude'] = $trip_latitude;
				$data['trip_longitude'] = $trip_longitude;
				$data['trip_mobile'] = $trip_phone;
				$data['trip_website'] = $trip_website;
				$data['trip_email'] = $trip_email;
				$data['business_hours'] = $business_hours;
				$data['business_hidden_hours'] = $_business_hours;
				$data['trip_images_path'] = ! empty( $images ) ? implode( "::", $images ) : '';
				$data['trip_images_count'] = count( $images );

				$data['trip_default_city'] = false;
				if ( strtolower( $trip_city ) === strtolower( $default_city ) ) {
					$data['trip_default_city'] = true;
				}
			}

			// Return tripadvisor fields response array.
			return json_encode( $data );
		}

		/**
		 * Get the GPS from the page.
		 *
		 * @param $social_importer_obj
		 * @param $response_html
		 *
		 * @return array
		 */
		public function get_gps($social_importer_obj,$response_html){
			$data = $social_importer_obj->get_page_data( $response_html,',"coords":"','"},{"lookbackServlet');
            $data = !empty( $data ) ? explode('"}]);', $data ) :'';
			$gps = explode(",",$data[0]);
			if(!empty($gps)){
				return $gps;
			}
			return array();
		}

		/**
		 * Get business hours using tripadvisor page content.
		 *
		 * @since 2.0.0
		 *
		 * @param string $page_html Get Page html content.
		 *
		 * @return array $business_hours
		 */
		public function get_business_hours( $page_html ) {

			$social_importer_obj = new Social_Importer_General();

			$hours_html = $social_importer_obj->get_page_data($page_html,'<div class="hours content">','<div class="additional_info">');

			$hours_arr = array();

			$business_hours = array();

			if( !empty( $hours_html ) && $hours_html !='' ) {

				$hours_html = trim(preg_replace('/\s+/', ' ', $hours_html));

				$string_replace = str_replace('<div class="detail"> ','',$hours_html);
				$string_replace = str_replace('</div>','',$string_replace);
				$string_replace = str_replace("<div class='hoursRange'>",'/',$string_replace);
				$string_replace = str_replace("</span>",'',$string_replace);

				$explode_hours = explode('<span class="day">',$string_replace);

				$explode_hours_arr = array_filter($explode_hours);

				if( !empty( $explode_hours_arr ) && $explode_hours_arr !='' ) {

					$multi_hour = false;
					$temp_arr = array();

					foreach ( $explode_hours_arr as $hours_value ) {

						$hour_arr = array();

						$explode_hours = explode('<span class="hours">',$hours_value);

						$trim_b_hours = !empty( $explode_hours[1] ) ? trim($explode_hours[1],"/") :'';

						$businesshour = $trim_b_hours;

						if( strpos( $trim_b_hours, '/' ) !== false) {

							$businesshour = explode('/',$trim_b_hours);

							$multi_hour = true;

						}

						$sort_key = $social_importer_obj->get_week_sort_name( trim( $explode_hours[0] ) );
						$time_format = geodir_bh_input_time_format();

						if( true === $multi_hour ) {

							if( !empty( $businesshour ) && is_array( $businesshour ) ) {

								foreach ( $businesshour as $bh_keys => $bh_value ) {

									$explode_time = !empty( $bh_value ) ? explode('-', $bh_value ) :'';

									$open_time = $social_importer_obj->convert_time_in_24h_format( trim( $explode_time[0] ) );
									$close_time = $social_importer_obj->convert_time_in_24h_format( trim( $explode_time[1] ) );

									$temp_arr['open'] = !empty( $open_time ) ? trim( $open_time ) : '';
									$temp_arr['close'] = !empty( $close_time ) ? trim( $close_time ) : '';
									$temp_arr['open_display'] = $temp_arr['open'] ? date_i18n( $time_format, strtotime( $temp_arr['open'] ) ) : $temp_arr['open'];
									$temp_arr['close_display'] = $temp_arr['close'] ? date_i18n( $time_format, strtotime( $temp_arr['close'] ) ) : $temp_arr['close'];

									$hour_arr[] = $temp_arr;

								}

								$business_hours[$sort_key] = $hour_arr;

							}

						} else {

							$explode_time = !empty( $businesshour ) ? explode('-', $businesshour ) :'';

							$open_time = $social_importer_obj->convert_time_in_24h_format( trim( $explode_time[0] ) );
							$close_time = $social_importer_obj->convert_time_in_24h_format( trim( $explode_time[1] ) );

							$temp_arr['open'] = !empty( $open_time ) ? trim( $open_time ) : '';
							$temp_arr['close'] = !empty( $close_time ) ? trim( $close_time ) : '';
							$temp_arr['open_display'] = $temp_arr['open'] ? date_i18n( $time_format, strtotime( $temp_arr['open'] ) ) : $temp_arr['open'];
							$temp_arr['close_display'] = $temp_arr['close'] ? date_i18n( $time_format, strtotime( $temp_arr['close'] ) ) : $temp_arr['close'];

							$hour_arr[] = $temp_arr;

							$business_hours[$sort_key] = $hour_arr;

						}

					}

				}

			}

			return $business_hours;
		}

		/**
		 * Get Photos using tripadvisor page html.
		 *
		 * @since 2.0.0
		 *
		 * @param string $page_html Get tripadvisor page html.
		 *
		 * @return array|bool $images
		 */
		public function get_images( $page_html, $og_image = '', $image_limit = 0, $total_images = 0, $image_title = '' ) {
			global $wpdb, $tripadvisor_data;

			if ( $image_limit > 0 ) {
				$image_limit = $image_limit - $total_images;

				if ( $image_limit <= 0 ) {
					return false;
				}
			}

			$listing_id = !empty( $_POST['post_id'] ) ? $_POST['post_id'] :'';

			if( empty( $listing_id ) ) {
				return false;
			}

			$post_type = get_post_type( $listing_id );
			$detail_table = geodir_db_cpt_table( $post_type );

			$gd_media_obj = new GeoDir_Media();
			$social_importer_obj = new Social_Importer_General();

			if ( strpos( $page_html, 'data-tab="TABS_PHOTOS"' ) !== false && strpos( $page_html, 'class="add_photos_container desktop"' ) !== false ) {
				$photos_html = $social_importer_obj->get_page_data( $page_html, 'data-tab="TABS_PHOTOS"', 'class="add_photos_container desktop"' );
			} else {
				$end_position = strpos( $page_html, 'id="taplc_sticky_header_ar_responsive_0"' ) !== false ? 'id="taplc_sticky_header_ar_responsive_0"' : 'id="MAIN"';

				$photos_html = $social_importer_obj->get_page_data( $page_html, 'data-tab="TABS_PHOTOS"', $end_position );

				$reviews_html = $social_importer_obj->get_page_data( $page_html, 'id="REVIEWS"', 'data-prwidget-name="common_responsive_pagination"' );
				if ( ! empty( $reviews_html ) ) {
					preg_match_all( '#' . preg_quote( 'data-prwidget-name="reviews_inline_photos_hsx"', '#' ) . '(.*?)' . preg_quote( 'data-prwidget-name="reviews_stay_date_hsx"', '#' ) . '#s', $reviews_html, $reviews_photos_html );

					if (  ! empty( $reviews_photos_html ) && ! empty( $reviews_photos_html[1] ) ) {
						foreach ( $reviews_photos_html[1] as $photo_html ) {
							$photos_html .= $photo_html;
						}
					}
				}
			}

			$image_urls = array();
			if ( ! empty( $photos_html ) && $photos_html != '' ) {
				$image_urls = $this->parse_image_urls( $photos_html );
			}

			if ( empty( $image_urls ) ) {
				if ( ! empty( $tripadvisor_data['album_images'] ) ) {
					$image_urls = $tripadvisor_data['album_images'];
				} elseif ( ! empty( $og_image ) ) {
					$image_urls[] = $og_image;
				}
			}

			$images = array();
			if ( ! empty( $image_urls ) ) {
				$wp_upload_dir = wp_upload_dir();
				$counter = 0;
				foreach ( $image_urls as $key => $image_url ) {
					$url_filename = pathinfo( $image_url );

					$image_info = $gd_media_obj::get_external_media( $image_url, $url_filename['filename'] . '_' . substr( geodir_rand_hash(), 0, 5 ) );

					if ( ! is_wp_error( $image_info ) && isset( $image_info['url'] ) && $image_info['url'] ) {
						if ( empty( $image_title ) ) {
							$image_title = $url_filename['filename'];
						}
						$_image_title = preg_replace( '/[\-_|]/', ' ', $image_title );
						$image_title = apply_filters( 'geodir_social_post_image_title', $_image_title, array( 'post_type' => $post_type, 'site' => 'tripadvisor', 'image_name' => $image_title, 'post_id' => $listing_id, 'order' => $key, 'image_url' => $image_url ) );

						$image_temp = $gd_media_obj::insert_attachment( $listing_id, 'post_images', $image_info['url'], $image_title, '', -1, 0, 0 );
						if ( ! is_wp_error( $image_temp ) && ! empty( $image_temp['file'] ) ) {
							$image_temp['url'] = $wp_upload_dir['baseurl'] . $image_temp['file'];
							$image_temp['title'] = $image_title;
							$images[] = $image_temp;
							$counter++;

							// delete original image
							@wp_delete_file( $image_info['file'] );
						} elseif ( is_wp_error( $image_temp ) ) {
							geodir_error_log( $image_temp->get_error_message(), 'insert_attachment', __FILE__, __LINE__ );
						}
					}

					if ( $image_limit > 0 && $counter >= $image_limit ) {
						return $images;
					}
				}
			}

			return $images;
		}

		/**
		 * Get Address from page address html.
		 *
		 * @since 2.0.0
		 *
		 * @param string $address Get tripadvisor address html.
		 *
		 * @return bool|string $remove_html
		 */
		public function get_address( $address ) {

			if( empty( $address ) ) {
				return false;
			}

			$temp_address = $address;

			if( strpos( $temp_address, '<div class="content hidden">' ) !== false) {

				$explode_address = explode( '<div class="content hidden">', $temp_address );

				$temp_address = !empty( $explode_address[0] ) ? $explode_address[0] :'';
			}

			$remove_html = $replace = str_replace( '</span>', '', $temp_address);
			$remove_html = $replace = str_replace( '<span class="detail">', '', $remove_html);
			$remove_html = $replace = str_replace( '<span class="street-address">', '', $remove_html);
			$remove_html = $replace = str_replace( '<span class="extended-address">', '', $remove_html);
			$remove_html = $replace = str_replace( '<span class="locality">', '', $remove_html);

			return !empty( $remove_html ) ? $remove_html :'';

		}

		/**
		 * Parse the images from html.
		 *
		 * @since 2.0.1.2
		 *
		 * @param string $content Page html.
		 *
		 * @return array|null $images Images array.
		 */
		public function parse_image_urls( $content ) {
			$images = array();
			
			if ( strpos( $content, ' data-lazyurl=' ) !== false ) {
				$matches = array();

				preg_match_all( '/( data-lazyurl)=["\'](.*)["\']/Ui', $content, $matches );

				if ( ! ( ! empty( $matches ) && array_key_exists( 2, $matches ) ) ) {
					preg_match_all( '/( src)=["\'](.*)["\']/Ui', $content, $matches );
				}

				if ( ! empty( $matches ) && array_key_exists( 2, $matches ) ) {
					foreach( $matches[2] as $key => $src ) {
						$src = trim( $src );

						if ( $src != '' ) {
							// /photo-w/ contains large image url.
							$src = str_replace( array( '/media/daodao/photo-s/', '/media/daodao/photo-f/', '/media/daodao/photo-l/' ), '/media/daodao/photo-w/', $src );
							$src = str_replace( array( '/media/photo-s/', '/media/photo-f/', '/media/photo-l/' ), '/media/photo-w/', $src );
							$images[] = str_replace( 'amp;', '&', $src );
						}
					}
				}
			} else {
				$explode_html = ! empty( $content ) ? explode( '<img src="', $content) : '';

				if ( ! empty( $explode_html ) ) {
					$wp_upload_dir = wp_upload_dir();

					foreach ( $explode_html as $key => $image_val ) {
						if ( strpos( $image_val, 'class="basicImg"' ) !== false) {
							$explode_val = ! empty( $image_val ) ? explode( 'class="basicImg"', $image_val ) : '';
							$image_path = ! empty( $explode_val[0] ) ? trim( str_replace( '"', '', $explode_val[0] ) ) : '';
							
							if ( $image_path ) {
								$images[] = $image_path;
							}
						}
					}
				}
			}

			return $images;
		}

		public function get_api_data( $content ) {
			global $tripadvisor_data;

			if ( strpos( $content, 'window.__WEB_CONTEXT__=' ) === false ) {
				return NULL;
			}

			$_pagemanifest = explode( 'window.__WEB_CONTEXT__=', $content );
			if ( ! empty( $_pagemanifest ) && count( $_pagemanifest ) > 1 && ! empty( $_pagemanifest[1] ) ) {
				$_pagemanifest = explode( '</script>', $_pagemanifest[1] );

				if ( ! empty( $_pagemanifest ) && count( $_pagemanifest ) > 1 && ! empty( $_pagemanifest[0] ) ) {
					if ( strpos( $_pagemanifest[0], '(window.$WP=' ) !== false ) {
						$_pagemanifest = explode( '(window.$WP=', $_pagemanifest[0] );
					} elseif ( strpos( $_pagemanifest[0], '(this.$WP=' ) !== false ) {
						$_pagemanifest = explode( '(this.$WP=', $_pagemanifest[0] );
					}
					$_pagemanifest = str_replace( '{pageManifest:', '{"pageManifest":', trim( $_pagemanifest[0], ";" ) );
					$_pagemanifest = json_decode( $_pagemanifest, true );

					if ( isset( $_pagemanifest['pageManifest']['assets'] ) ) {
						unset( $_pagemanifest['pageManifest']['assets'] );
					}

					if ( ! empty( $_pagemanifest ) && is_array( $_pagemanifest ) ) {
						$this->parse_api_data( $_pagemanifest );

						if ( empty( $tripadvisor_data ) ) {
							if ( isset( $_pagemanifest['pageManifest']['urqlCache'] ) ) {
								$_pagemanifest = $_pagemanifest['pageManifest']['urqlCache'];
							}

							$this->parse_api_data_2020( $_pagemanifest );
						}
					}
				}
			}

			return $tripadvisor_data;
		}

		public function parse_api_data( $_data, $key = '', $value = array() ) {
			global $tripadvisor_data;

			if ( ! empty( $tripadvisor_data ) ) {
				return;
			}

			if ( is_array( $_data ) ) {
				foreach ( $_data as $_key => $_value ) {
					if ( $_key != '' && strpos( $_key, '/location/' ) !== false && is_array( $_value ) && ! empty( $_value['data'] ) && ! empty( $_value['data']['location_id'] ) && ! empty( $_value['data']['name'] ) && ( ! empty( $_value['data']['web_url'] ) || ! empty( $_value['data']['description'] ) || ! empty( $_value['data']['latitude'] ) || ! empty( $_value['data']['category'] ) ) ) {
						$tripadvisor_data = $_value['data'];
						break;
					} else {
						$this->parse_api_data( $_value, $_key );
					}
				}
			}
		}

		public function parse_api_data_2020( $_data, $key = '', $value = array() ) {
			global $tripadvisor_data;

			if ( is_array( $_data ) ) {
				foreach ( $_data as $_key => $_value ) {
					if ( empty( $_value ) ) {
						continue;
					}

					$data = array();

					if ( is_array( $_value ) && ! empty( $_value ) ) {
						if ( isset( $_value['data']['currentLocation'] ) ) {
							if ( isset( $_value['data']['currentLocation']['name'] ) ) {
								$_value = $_value['data']['currentLocation'];
							} elseif ( isset( $_value['data']['currentLocation'][0]['name'] ) ) {
								$_value = $_value['data']['currentLocation'][0];
							}

							$keys = array( 'name', 'locationId', 'placeType', 'latitude', 'longitude', 'localLanguage' );
							foreach ( $keys as $key ) {
								if ( isset( $_value[ $key ] ) ) {
									$data[ $key ] = $_value[ $key ];
								}
							}
							if ( isset( $_value['streetAddress'] ) && isset( $_value['streetAddress']['fullAddress'] ) ) {
								$data['fullAddress'] = $_value['streetAddress']['fullAddress'];
							}
							if ( ! empty( $_value['businessAdvantageData']['contactLinks'] ) ) {
								foreach ( $_value['businessAdvantageData']['contactLinks'] as $_item ) {
									if ( isset( $_item['contactLinkType'] ) ) {
										if ( $_item['contactLinkType'] == 'PHONE' && ! empty( $_item['displayPhone'] ) ) {
											$data['phone'] = $_item['displayPhone'];
										} elseif ( $_item['contactLinkType'] == 'EMAIL' && ! empty( $_item['emailParts'] ) ) {
											$data['email'] = implode( "", $_item['emailParts'] );
										} elseif ( $_item['contactLinkType'] == 'URL_HOTEL' && ! empty( $_item['linkUrl'] ) ) {
											$data['website'] = base64_decode( $_item['linkUrl'] );
										}
									}
								}
							}

							$tripadvisor_data = $data;
							break;
						}
					}
				}

				if ( empty( $data ) ) {
					return;
				}

				foreach ( $_data as $_key => $_value ) {
					if ( empty( $_value ) ) {
						continue;
					}

					if ( ! empty( $data['locationId'] ) && ! empty( $_value['data']['locations'] ) && ! empty( $_value['data']['mediaWindow'] ) && ! empty( $_value['data']['locations'][0]['locationId'] ) && $_value['data']['locations'][0]['locationId'] == $data['locationId'] ) {
						if ( empty( $tripadvisor_data['description'] ) && ! empty( $_value['data']['locations'][0]['locationDescription'] ) ) {
							$tripadvisor_data['description'] = $_value['data']['locations'][0]['locationDescription'];
						}

						if ( empty( $tripadvisor_data['album_images'] ) && ! empty( $_value['data']['mediaWindow']['windowPanes'][0]['albums'] ) ) {
							foreach ( $_value['data']['mediaWindow']['windowPanes'][0]['albums'] as $_album ) {
								if ( $_album['name'] == 'View all photos' && ! empty( $_album['mediaList'] ) ) {
									$album_images = array();

									foreach ( $_album['mediaList'] as $mediaList ) {
										if ( ! empty( $mediaList['photoSizes'] ) ) {
											foreach ( $mediaList['photoSizes'] as $photoSizes ) {
												if ( strpos( $photoSizes['url'], '/media/photo-w/' ) !== false ) {
													$album_images[] = $photoSizes['url'];
												}
											}
										}
									}
									$tripadvisor_data['album_images'] = $album_images;
								}
							}
						}
					}
				}
			}
		}

		public function parse_business_hours( $hours ) {
			$business_hours = array();

			if ( ! empty( $hours ) && ! empty( $hours['week_ranges'] ) ) {
				$time_format = geodir_bh_input_time_format();
				$weeks = array( 'Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa' );

				foreach ( $hours['week_ranges'] as $key => $value ) {
					$_hours = array();

					if ( ! empty( $value ) ) {
						foreach ( $value as $_key => $hour ) {
							$_hour = array();

							if ( ! empty( $hour['open_time'] ) ) {
								$_hour['open'] = date( 'H:i', absint( $hour['open_time'] ) * 60 );
								$_hour['close'] = ! empty( $hour['close_time'] ) ? date( 'H:i', absint( $hour['close_time'] ) * 60 ) : '23:59';
								$_hour['open_display'] = $_hour['open'] ? date_i18n( $time_format, strtotime( $_hour['open'] ) ) : $_hour['open'];
								$_hour['close_display'] = $_hour['close'] ? date_i18n( $time_format, strtotime( $_hour['close'] ) ) : $_hour['close'];
							}

							if ( ! empty( $_hour ) ) {
								$_hours[] = $_hour;
							}
						}
					}

					if ( ! empty( $_hours ) ) {
						$business_hours[ $weeks[ $key ] ] = $_hours;
					}
				}
			}

			return $business_hours;
		}
	}
}