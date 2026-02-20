<?php

// Check GD_Social_Importer_Facebook class exists or not.
if( ! class_exists( 'GD_Social_Importer_Facebook' ) ) {

	/**
	 * GD_Social_Importer_Facebook Class for Facebook import actions.
	 *
	 * @since 2.0.0
	 *
	 * Class GD_Social_Importer_Facebook
	 */
	class GD_Social_Importer_Facebook {

		/**
		 * Constructor.
		 *
		 * @since 2.0.0
		 *
		 * GD_Social_Importer_Facebook constructor.
		 */
		public function __construct() {

		}

		/**
		 * Get FB page or events meta data. like title, description, address.
		 *
		 * @since 2.0.0
		 *
		 * @param string $url Get page/event url.
		 *
		 * @return string $response.
		 */
		public function gdfi_get_fb_meta( $url ) {
			global $gd_post;
			$response = array();

			$post_id = ! empty( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
			$gd_post = geodir_get_post_info( $post_id );
			$post_type = $post_id > 0 ? get_post_type( $post_id ) : 'gd_place';

			$social_importer_obj = new Social_Importer_General();

			$response_html = $social_importer_obj->get_remote_response( $url );

			if ( strpos($response_html, 'You must log in to continue') !== false ) {
				$response['error'] = true;
				$response['error_msg'] = __( 'Sorry, this content isn\'t available right now', 'gd-social-importer' );

				return json_encode( $response );
			}

			if ( strpos( $url, '/events/' ) !== false ) {
				if ( ! empty( $response_html ) ) {
					// Event title.
					$fb_event_title = '';
					if ( strpos( $response_html, 'id="seo_h1_tag"') !== false ) {
						preg_match_all( '/<h1 id="seo_h1_tag" (.*)>(.*)<\/h1>/U', $response_html, $matches_response );
						$fb_event_title = !empty( $matches_response[2][0] ) ? str_replace( "&amp;", "&", $matches_response[2][0] ) : '';
					}

					// Event Address.
					$address ='';
					if ( strpos( $response_html, 'id="event_summary"' ) !== false ) {
						$get_address_location = $social_importer_obj->get_page_data( $response_html, '<li class="_3xd0 _3slj">', '</li>' );

						if ( strpos( $get_address_location, 'class="_5xhk"' ) !== false ) {
							$get_address_div = $social_importer_obj->get_page_data( $get_address_location, '<div class="_xkh">', '</div>' );
							$address_array = ! empty( $get_address_div ) ? explode( '<div class="_5xhp fsm fwn fcg">', $get_address_div ) : '';
							$address = ! empty( $address_array[1] ) ? $address_array[1] : '';

							if ( empty( $address ) && $address == '' ) {
								if ( ! empty( $address_array[0] ) && strpos( $address_array[0], '<a') !== false ) {
									$temp_address = ! empty( $address_array[0] ) ? str_replace( '</a>', '', $address_array[0] ) : '';
									$temp_address = ! empty( $temp_address ) ? explode( '">', $temp_address ) : '';
									$address = ! empty( $temp_address[1] ) ? $temp_address[1] : '';
								} elseif ( ! empty( $address_array[0] ) && strpos( $address_array[0], '<span' ) !== false ) {
									$temp_address = ! empty( $address_array[0] ) ? str_replace( '</span>', '', $address_array[0] ) : '';
									$temp_address = ! empty( $temp_address ) ? explode( '">', $temp_address ) : '';
									$address = ! empty( $temp_address[1] ) ? $temp_address[1] : '';
								}
							}
						}

						// Event Dates
						if ( ! empty( $post_type ) && GeoDir_Post_types::supports( $post_type, 'events' ) ) {
							$input_date_format = geodir_event_field_date_format();
							$display_date_format = geodir_event_date_format();
							$display_time_format = geodir_event_time_format();

							$get_date_time = $this->get_event_date_time( $response_html );

							$start_date = ! empty( $get_date_time['event_sdate'] ) ? trim( $get_date_time['event_sdate'] ) : '';
							$end_date = ! empty( $get_date_time['event_edate'] ) ? trim( $get_date_time['event_edate'] ) : $start_date;
							$start_time = ! empty( $get_date_time['event_stime'] ) ? trim( $get_date_time['event_stime'] ) : '';
							$end_time = ! empty( $get_date_time['event_etime'] ) ? trim( $get_date_time['event_etime'] ) : '00:00';

							$response['fb_event'] = true;
							$response['fb_event_sdate'] = $start_date != ''? date_i18n( $input_date_format, strtotime( $start_date ) ) : '';
							$response['fb_event_edate'] = $end_date != ''? date_i18n( $input_date_format, strtotime( $end_date ) ) : '';
							$response['fb_event_stime'] = $start_time;
							$response['fb_event_etime'] = $end_time;
							$response['fb_event_sdate_ymd'] = $start_date;
							$response['fb_event_edate_ymd'] = $end_date;
							$response['fb_event_sdate_display'] = $start_date != ''? date_i18n( $display_date_format, strtotime( $start_date ) ) : '';
							$response['fb_event_edate_display'] = $end_date != ''? date_i18n( $display_date_format, strtotime( $end_date ) ) : '';
							$response['fb_event_stime_display'] = $start_time != ''? date_i18n( $display_time_format, strtotime( $start_time ) ) : '';
							$response['fb_event_etime_display'] = $end_time != '' ? date_i18n( $display_time_format, strtotime( $end_time ) ) : '';
						}
					}

					$cf_post_images = geodir_get_field_infoby( 'htmlvar_name', 'post_images', $post_type );

					// Images limit
					$image_limit = apply_filters( "geodir_custom_field_file_limit", 0, $cf_post_images, $gd_post );

					// Event images
					$images = $this->get_images( $response_html, $image_limit, 0, true );

					$post_images = array();
					if (!  empty($images ) ) {
						foreach( $images as $image ) {
							$post_images[] = $image['url'] . "|" . $image['ID'] . "||";
						}
					}

					// Get FB events Postal code.
					$postalCode ='';
					if ( ! empty( $address ) && $address != '' ) {
						$address_arr = $social_importer_obj->get_address_response( $address );
						$postalCode = ! empty( $address_arr['postal_code'] ) ? $address_arr['postal_code'] : '';

						if ( isset( $address_arr['latitude'] ) && isset( $address_arr['longitude'] ) ) {
							$response['fb_address_latitude'] = $address_arr['latitude'];
							$response['fb_address_longitude'] = $address_arr['longitude'];
						}
					}

					// Description
					$description = $fb_event_title;
					if ( ! empty( $address ) ) {
						$description = wp_sprintf( __( '%s at %s', 'gd-social-importer' ), $fb_event_title, $address );

						if ( ! empty( $address_arr['country_name'] ) ) {
							$address = str_replace( ', ' . $address_arr['country_name'], '', $address );
						}

						if ( ! empty( $address_arr['state_name'] ) && ! empty( $address_arr['postal_code'] ) ) {
							$address = str_replace( ', ' . $address_arr['state_name'] . ' ' . $address_arr['postal_code'], '', $address );
							$address = str_replace( ', ' . $address_arr['postal_code'] . ' ' . $address_arr['state_name'], '', $address );
							$address = str_replace( ', ' . $address_arr['postal_code'] . ' ', ', ', $address );
						}

						trim( $address, ', ' );
					}

					$response['is_facebook'] = true;
					$response['fb_title'] =  ! empty( $fb_event_title ) ? stripslashes( $fb_event_title ) : '';
					$response['fb_description'] = stripslashes( $description );
					$response['fb_address'] = stripslashes( $address );
					$response['fb_zipcode'] = ! empty( $postalCode ) ? $postalCode :'';
					$response['fb_post_images'] = ! empty( $post_images ) ? implode( "::", $post_images ) : '';
					$response['fb_images_count'] = ! empty( $post_images ) ? count( $post_images ) : '';
				}
			} else {
				if ( ! empty( $response_html ) && $response_html != '' ) {
					$about_url = trailingslashit( $url ) . 'about';
					$about_html = $social_importer_obj->get_remote_response( $about_url );

					$about = $this->get_about_data( $about_html );

					if ( ! empty( $about ) ) {
						$about = stripslashes_deep( $about );
					}

					$about['business_hours'] = $this->get_business_hours( $about_html );
					$default_city = $social_importer_obj->get_default_city_location();

					$schema_html = $social_importer_obj->get_page_data( $response_html, '<script type="application/ld+json" ', '</script>' );
					if(!empty($schema_html)){
						$schema_parts = explode('">{"',$schema_html);
						if(!empty($schema_parts[1])){
							$schema_html = '{"'.$schema_parts[1];
						}
					}
					$schema = json_decode( $schema_html );
					if ( empty( $schema ) && ! empty( $about['schema'] ) ) {
						$schema = $about['schema'];
					}

					// Title
					$title = '';
					if ( ! empty( $schema->name ) ) {
						$title = stripslashes( $schema->name );
					} elseif ( ! empty( $about['meta_title'] ) ) {
						$title = $about['meta_title'];
					} else {
						$title_og = $social_importer_obj->get_page_data( $response_html, '<meta property="og:title" content="', '" /><' );

						if ( ! empty( $title_og ) && is_scalar( $title_og ) ) {
							$title = trim( strip_tags( $title_og ) );
						}
					}

					// Description
					$description = '';
					if ( ! empty( $about['description'] ) ) {
						$description = $about['description'];
					} elseif ( ! empty( $about['meta_description'] ) ) {
						$description = $about['meta_description'];
					} else{
						$desc_og = $social_importer_obj->get_page_data( $response_html, '<meta property="og:description" content="', '" /><' );
						if(!empty($desc_og)){
							$desc_parts = explode(". ",$desc_og);
							if(!empty($desc_parts[2])){
								$description = $desc_parts[2];
							}
						}
					}


					$phone = ! empty( $about['phone'] ) ? $about['phone'] : ''; // Phone
					$email = ! empty( $about['email'] ) ? $about['email'] : ''; // Email
					$website = ! empty( $about['website'] ) ? $about['website'] : ''; // Website

					$about_content = $social_importer_obj->get_page_data( $response_html, '<div class="_4-u2 _u9q _3xaf _4-u8">', '<div class="_1q7f">' );
					$_about_content = explode( '<div class="_2pi9 _2pi2">', $about_content );

					if ( ! empty( $_about_content ) ) {
						foreach ( $_about_content as $key => $content ) {
							if ( empty( $phone ) && ( strpos( $content, '/oXiCJHPgn3c.png' ) !== false || strpos( $content, '/mYv88EsODOI.png' ) !== false ) ) {
								$phone_html = $social_importer_obj->get_page_data( $content, '<div class="_4bl9">', '</div>' );

								if ( ! empty( $phone_html ) ) {
									$phone = trim( html_entity_decode( str_replace( 'Call', '', strip_tags( $phone_html ) ) ) );
								}
							}

							if ( empty( $website ) && ( strpos( $content, '/ZBnKG6mAW8D.png' ) !== false || strpos( $content, '/xVA3lB-GVep.png' ) !== false ) ) {
								$website_html = $social_importer_obj->get_page_data( $content, '<div class="_4bl9 _v0m">', '</div>' );
						
								$_website_html = ! empty( $website_html ) ? explode( '<a href="', $website_html ) : '';
								$website_link = ! empty( $_website_html[2] ) ? explode( '?u=', html_entity_decode( urldecode( $_website_html[2] ) ) ) : '';
								$website_link = ! empty( $website_link[1] ) ? explode( '&h=',$website_link[1] ) : '';
								$website = trim( html_entity_decode( urldecode( strip_tags( $website_link[0] ) ) ) );
							}
						}
					}

					// Address
					$gps = $this->get_gps( $social_importer_obj, $response_html );

					$streetAddress = !empty( $schema->address->streetAddress ) ? $schema->address->streetAddress :'';
					$addressLocality = !empty( $schema->address->addressLocality ) ? $schema->address->addressLocality :'';
					$postalCode = !empty( $schema->address->postalCode ) ? $schema->address->postalCode :'';
					$address = $streetAddress.', '.$addressLocality.', '.$postalCode;

					// Business Hours
					$business_hours = ! empty( $about['business_hours'] ) ? $about['business_hours'] : array();
					$business_hours_hidden = $social_importer_obj->get_business_hours_hidden_time( $business_hours );

					// Logo
					$has_logo = $social_importer_obj->check_cf_key_available( 'logo', $post_type );
					if ( $has_logo ) {
						$logo_image_arr = array();

						$get_logo = $this->get_company_logo( $response_html , 'logo' );

						if ( ! empty( $get_logo ) ) {
							foreach( $get_logo as $image ) {
								$logo_image_arr[] = $image['url'] . "|" . $image['ID'] . "|" . ( ! empty( $image['title'] ) ? $image['title'] : "" ) . "|";
							}
						}

						$response['fb_logo_images'] = implode( "::", $logo_image_arr );
					}

					$cf = geodir_get_field_infoby( 'htmlvar_name', 'post_images', $post_type );

					// Images limit
					$image_limit = apply_filters( "geodir_custom_field_file_limit", 0, $cf, $gd_post );

					$company_logo = $this->get_company_logo( $response_html, 'post_images' );
					$image_array = array();
					if ( ! empty( $company_logo ) ) {
						foreach( $company_logo as $key => $post_image ) {
							if ( ! empty( $post_image ) && ! empty( $post_image['url'] ) && ! empty( $post_image['ID'] ) ) {
								$image_array[] = $post_image['url'] . "|" . $post_image['ID'] . "|" . ( ! empty( $post_image['title'] ) ? $post_image['title'] : "" ) . "|";
							}
						}
					}

					// Images
					$get_images = $this->get_images( $response_html, $image_limit, count( $image_array ) );
					if ( ! empty( $get_images ) ){
						foreach( $get_images as $image ) {
							$image_array[] = $image['url'] . "|" . $image['ID'] . "|" . ( ! empty( $image['title'] ) ? $image['title'] : "" ) . "|";
						}
					}

					if ( ! empty( $image_array ) ) {
						$response['fb_post_images'] = implode( "::", $image_array );
						$response['fb_images_count'] = count( $image_array );
					} else {
						$response['fb_images_count'] = '';
					}

					// Videos
					$get_fb_video = $this->get_videos( $response_html, $url );
					if ( ! empty( $get_fb_video ) && is_array( $get_fb_video ) ) {
						$response['fb_videos'] = ! empty( $get_fb_video ) ? nl2br( implode( '|', $get_fb_video ) ) : '';
					}

					$response['is_facebook'] = true;
					$response['fb_title'] = $title;
					$response['fb_description'] = $description;
					$response['fb_address'] = stripslashes( $streetAddress );
					$response['fb_zipcode'] = ! empty( $postalCode ) ? $postalCode : '';

					if ( isset( $gps[0] ) && isset( $gps[1] ) ) {
						$response['fb_address_latitude'] = $gps[0];
						$response['fb_address_longitude'] = $gps[1];
					}

					$response['fb_contact'] = $phone;
					$response['fb_email'] = $email;
					$response['fb_website'] = $website;
					$response['fb_business_hour'] = ! empty( $business_hours ) ? $business_hours : '';
					$response['fb_business_hidden_hours'] = ! empty( $business_hours_hidden ) ? $business_hours_hidden :'';
					$response['fb_default_city'] = false;
					$response['fb_facebook'] = str_replace( 'en-gb.facebook.com', 'www.facebook.com', $url );
					// we need another page load to get videos: <iframe src="https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Ffurter.hotdogs%2Fvideos%2F794743987295548%2F&show_text=0&width=560" width="560" height="320" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowTransparency="true" allowFullScreen="true"></iframe>

					if ( ! empty( $addressLocality ) &&  strpos( strtolower( $addressLocality ), strtolower( $default_city ) ) !== false ) {
						$response['fb_default_city'] = true;
					}
				}
			}

			$response = apply_filters( 'geodir_social_facebook_response', $response, $response_html, $url );

			return json_encode( $response );
		}

		/**
		 * Get the GPS from the page.
		 *
		 * @param $social_importer_obj
		 * @param $response_html
		 *
		 * @return array
		 */
		public function get_gps( $social_importer_obj, $response_html ) {
			$data = $social_importer_obj->get_page_data( $response_html, '&amp;markers=', '&amp;language=' );
			$gps = explode( "%2C", $data );
			if ( ! empty( $gps ) ) {
				return $gps;
			}
			return array();
		}

		/**
		 * Retrieves headers for scraping-bot.io
		 *
		 * @return array|WP_Error
		 */
		public function get_srapingbot_headers() {
			$api_key  = geodir_get_option('si_fb_scraping_bot_io_api_key');
			$username = geodir_get_option('si_fb_scraping_bot_io_username');

			if ( empty( $api_key ) || empty( $username ) ) {
				return new WP_Error( 'missing_api_key', __( 'Missing API key or username.', 'gd-social-importer' ) );
			}

			return array(
				'Authorization' => 'Basic ' . base64_encode( $username . ':' . $api_key ),
				'Content-Type'  => 'application/json',
			);

		}

		/**
		 * Retrieves the response ID for scraping-bot.io
		 *
		 * @param $url The Facebook page URL.
		 * @return array|WP_Error
		 */
		public function get_srapingbot_response_id( $url ) {
			//return array(
			//	'responseId' => 'z3186t1650967650106r5k792kbdri8',
			//	'scraper'    => 'facebookOrganization',
			//	'src'        => 'scrapingbot',
			//);

			$headers = $this->get_srapingbot_headers();

			if ( is_wp_error( $headers ) ) {
				return $headers;
			}

			$scraper     = strpos( $url, '/events/' ) !== false ? 'facebookPost' : 'facebookOrganization';
			$response_id = wp_remote_post(
				'http://api.scraping-bot.io/scrape/data-scraper',
				array(
					'headers' => $headers,
					'body'    => wp_json_encode(
						array(
							'url'     => $url,
							'scraper' => $scraper,
						)
					),
					'data_format' => 'body',
					'sslverify' => false,
				)
			);

			if ( is_wp_error( $response_id ) ) {
				return $response_id;
			}

			$response_id = json_decode( wp_remote_retrieve_body( $response_id ) );

			if ( empty( $response_id ) || empty( $response_id->responseId ) ) {
				return new WP_Error( 'missing_response_id', __( 'Missing response ID.', 'gd-social-importer' ) );
			}

			return array(
				'responseId' => $response_id->responseId,
				'scraper'    => $scraper,
				'src'        => 'scrapingbot',
			);

		}

		/**
		 * Retrieves the response for a given response ID scraping-bot.io
		 *
		 * @param array $args
		 * @return array|WP_Error
		 */
		public function get_srapingbot_response( $args ) {

			//return $this->post_process_srapingbot_response( [[
			//	'avatat_image_url' => 'https://scontent.fxds1-1.fna.fbcdn.net/v/t1.6435-1/78554678_2642270752508589_3545342640666968064_n.jpg?stp=dst-jpg_p148x148&_nc_cat=105&ccb=1-5&_nc_sid=1eb0c7&_nc_ohc=BXPBlfrnAXQAX8ECwn-&_nc_ht=scontent.fxds1-1.fna&oh=00_AT97GGVkOw1ORH5SPviK77zrM-rRPXrGgZq1-oTHESPGsA&oe=628DDF34',
            //	'profile_handle' => '@savoy.cafe.newcastle',
            //	'profile_name' => 'Savoy Cafe',
            //	'is_verified' => false,
            //	'profile_type' => 'Café',
            //	'name' => 'Savoy Cafe',
			//	'about' => 'Breakfast | Lunch | Homemade Cakes | Speciality Coffee',
            //	'phone' => '+44 28 4372 5757',
            //	'address' => "22  Main Street\nBT33 0AD Newcastle",
			//	'website' => 'http://www.savoy-cafe.com/',
            //	'category' => 'Café',
            //	'page_created' => '2019-05-01T00:00:00+00:00', 
            //	'email' => 'eat@savoy-cafe.com',
			//]] );
		
			$headers = $this->get_srapingbot_headers();

			if ( is_wp_error( $headers ) ) {
				return $headers;
			}

			$response = wp_remote_get(
				add_query_arg( $args, 'http://api.scraping-bot.io/scrape/data-scraper-response' ),
				array( 'headers' => $headers )
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$response = json_decode( wp_remote_retrieve_body( $response ) );

			if ( empty( $response ) ) {
				return new WP_Error( 'invalid_response', __( 'Invalid response. Please try again.', 'gd-social-importer' ) );
			}

			if ( is_array( $response ) ) {
				return $this->post_process_srapingbot_response( $response );
			}

			return $response;

		}

		/**
		 * Post processes scraping bot response.
		 *
		 * @param array $response
		 * @return array $response
		 */
		public function post_process_srapingbot_response( $response ) {

			$response = current( $response );

			$defaults = array(
				'processed'    => true,
				'profile_name' => '',
				'phone'        => '',
				'email'        => '',
				'address'      => '',
				'website'      => '',
				'about'		   => '',
				'images'       => '',
				'logo'         => '',
				'zip'          => '',
				'latitude'     => '',
				'longitude'    => '',
				'country_name' => '',
				'state_name'   => '',
				'city_name'    => '',
			);

			if ( empty( $response ) ) {
				return $defaults;
			}

			$args = wp_parse_args( (array) $response, $defaults );

			// Address.
			if ( ! empty( $args['address'] ) ) {
				// Replace new lines with commas.
				$args['address'] = preg_replace( "/\r\n|\n|\r/", ', ', $args['address'] );
				$helper      = new Social_Importer_General();
				$address_arr = $helper->get_address_response( $args['address'] );

				if ( ! empty( $address_arr ) && 2 !== count( $address_arr ) ) {
					$args = wp_parse_args( (array) $address_arr, $args );

					$args['zip'] = ! empty( $address_arr['postal_code'] ) ? $address_arr['postal_code'] : '';

					if ( isset( $address_arr['latitude'] ) && isset( $address_arr['longitude'] ) ) {
						$args['latitude']  = $address_arr['latitude'];
						$args['longitude'] = $address_arr['longitude'];
					}

					// Remove country name from returned address.
					if ( ! empty( $address_arr['country_name'] ) ) {
						$args['address']      = str_replace( ', ' . $address_arr['country_name'], '', $args['address'] );
						$args['country_name'] = $address_arr['country_name'];
					}

					if ( ! empty( $address_arr['state_name'] ) && ! empty( $address_arr['postal_code'] ) ) {
						$args['address'] = str_replace( ', ' . $address_arr['state_name'] . ' ' . $address_arr['postal_code'], '', $args['address'] );
						$args['address'] = str_replace( ', ' . $address_arr['postal_code'] . ' ' . $address_arr['state_name'], '', $args['address'] );
						$args['address'] = str_replace( ', ' . $address_arr['postal_code'] . ' ', ', ', $args['address'] );

						$args['state_name'] = $address_arr['state_name'];
					}

					$args['address'] = trim( trim( $args['address'], ', ' ) );

				} else {
					$osm_address = geodir_get_gps_from_address(
						array(
							'street' => trim( strtok( $args['address'], ',' ) ),
							'zip'    => trim( strtok( ',' ) ),
						)
					);

					if ( is_array( $osm_address ) && isset( $osm_address['latitude'] ) && isset( $osm_address['longitude'] ) ) {
						$args['latitude']  = $osm_address['latitude'];
						$args['longitude'] = $osm_address['longitude'];
					}

					$args['address'] = trim( strtok( $args['address'], ',' ) );
					$args['zip']     = trim( strtok( ',' ) );
				}
			}

			// Images
			$images = array();
			if ( ! empty( $args['avatat_image_url'] ) ) {

				$wp_upload_dir = wp_upload_dir();
				$pathinfo      = pathinfo( $args['avatat_image_url'] );
				$filename      = $pathinfo['filename'];
				$gd_media_obj  = new GeoDir_Media();

				if ( $filename && strpos( $filename, '.jpg?' ) !== false ) {
					$filename = current( explode( '.jpg?', $filename, 2 ) );
				}

				$image_info = $gd_media_obj::get_external_media( $args['avatat_image_url'], $filename );

				if ( ! is_wp_error( $image_info ) && isset( $image_info['url'] ) && $image_info['url'] ) {
					$image_title = apply_filters( 'geodir_social_post_image_title', '', array( 'site' => 'facebook', 'image_name' => $filename, 'post_id' => absint( $_GET['post_id'] ), 'order' => 0, 'image_url' => $args['avatat_image_url'] ) );

					$image_temp = $gd_media_obj::insert_attachment( absint( $_GET['post_id'] ), 'post_images', $image_info['url'], $image_title, '', -1, 0, 0 );

					if ( ! is_wp_error( $image_temp ) && ! empty( $image_temp['file'] ) ) {
						$images[]     = $wp_upload_dir['baseurl'] . $image_temp['file'] . "|" . $image_temp['ID'] . "|" . $image_title . "|";
						$args['logo'] = $wp_upload_dir['baseurl'] . $image_temp['file'] . "|" . $image_temp['ID'] . "|" . $image_title . "|";

						// delete original image
						@wp_delete_file( $image_info['file'] );
					} elseif ( is_wp_error( $image_temp ) ) {
						geodir_error_log( $image_temp->get_error_message(), 'insert_attachment', __FILE__, __LINE__ );
					}
				}
			}

			
			if ( ! empty( $images ) ) {
				$args['images']      = implode( '::', $images );
				$args['image_count'] = count( $images );
			} else {
				$args['image_count'] = '';
			}

			return array( $args );

		}

		/**
		 * Get Facebook page meta data using scraping-bot.io API.
		 *
		 * Get Pages,events,groups data using API.
		 *
		 * @since 2.0.0
		 *
		 * @param string $url Get facebook page url.
		 *
		 * @return string
		 */
		public function gdfi_get_fb_api_meta( $url ) {

			$event = false;

			if ( !empty( $url ) && strpos($url, '?') !== false ) {

				$temp_url = explode('?', $url);
				$url = !empty( $temp_url[0] ) ? $temp_url[0] :'';

			}

			if ( !empty( $url ) && strpos($url, 'facebook.com/') !== false ) {

				$temp_url = explode('facebook.com/', $url);
				$url = !empty( $temp_url[1] ) ? $temp_url[1] :'';

			}

			if( !empty( $url ) && strpos($url, 'groups/') !== false ) {

				$temp_url = explode('groups/', $url);
				$url = !empty( $temp_url[1] ) ? $temp_url[1] : '';

			}

			if( !empty( $url ) && strpos($url, 'pages/') !== false ) {

				$temp_url = explode('pages/', $url);
				$url = !empty( $temp_url[1] ) ? $temp_url[1] : '';

			}

			if( !empty( $url ) && strpos($url, 'events/') !== false ) {

				$temp_url = explode('events/', $url);
				$url = !empty( $temp_url[1] ) ? $temp_url[1] : '';

				$event = true;

			}

			if ( !empty( $url ) && strpos( $url, '/' ) !== false ) {

				$temp_url = explode('/', $url);

				if ( is_numeric( $temp_url[1] ) && strlen( $temp_url[1] ) > 5 ) {

					$url = !empty( $temp_url[1] ) ? $temp_url[1] :'';

				} else {

					$url = !empty( $temp_url[0] ) ? $temp_url[0] :'';

				}
			}

			if ( ! empty( $url ) && strpos( $url, '-' ) !== false ) {
				$temp_urls = explode( '-', $url );
				$temp_url = end( $temp_urls );

				if ( is_numeric( $temp_url ) && strlen( $temp_url ) >= 10 ) {
					$url = ! empty( $temp_url ) ? $temp_url : '';
				}
			}

			return $this->get_fb_api_data( $url, $event );

		}

		/**
		 * Get Facebook page data using page id.
		 *
		 * @since 2.0.0
		 *
		 * @param string $page_id Get Facebook page id.
		 * @param bool $event Get current page is event.
		 *
		 * @return string $response
		 */
		public function get_fb_api_data( $page_id, $event = false ) {
			$access_token = geodir_get_option( 'si_fb_access_token', '' );

			$fields = $this->get_page_fields( $event );
			$url = 'https://graph.facebook.com/v2.9/' . $page_id . '?metadata=1&fields=' . $fields . '&access_token=' . $access_token;
			$result = wp_remote_get( $url, array( 'timeout' => 20 ) );

			$response = array();
			if ( ! empty( $result['response']['code'] ) && $result['response']['code'] == 200 ) {
				$result_arr = json_decode( $result['body'] );

				$response['is_facebook'] = true;
				$response['fb_title'] = '';
				$response['fb_description'] = '';
				$response['fb_address'] = '';
				$response['fb_zipcode'] = '';
				$response['fb_contact'] = '';
				$response['fb_email'] = '';
				$response['fb_website'] = '';
			} else {
				$result_arr = json_decode( $result['body'] );

				$error_code = __( 'Something went wrong[111]', 'gd-social-importer' );

				if ( ! empty( $result_arr->error->code ) && $result_arr->error->code != '' ) {
					if ( '100' == $result_arr->error->code ) {
						$error_code = __( 'Something went wrong[100], this page/event may not be public', 'gd-social-importer' );
					} elseif ( '104' == $result_arr->error->code ) {
						$error_code = __( 'Something went wrong[104], the admin must authorize this app in the backend', 'gd-social-importer' );
					} else {
						$error_code = __( 'Something went wrong','gd-social-importer') . "[" . $result_arr->error->code . "]";
					}
				}

				if ( ! empty( $result_arr->error->message ) && current_user_can( 'manage_options' ) ) {
					$error_code .= ' - ' . $result_arr->error->message; // Let allow admin to see original error from FB response.
				}

				$response['error'] = true;
				$response['error_msg'] = $error_code;
			}

			return json_encode( $response );
		}

		/**
		 * Get facebook page fields.
		 *
		 * Check current page is not event then get general fields,
		 * else get event fields.
		 *
		 * @since 2.0.0
		 *
		 * @param bool $event Get current page url is event.
		 *
		 * @return string $fields
		 */
		public function get_page_fields( $event ) {

			$fields = "name,description,category,place,photos{images},owner,start_time,end_time,cover,ticket_uri";

			// Check eve
			if( !$event ) {

				$fields = "about,name,description,category_list,category,location,phone,emails,press_contact,website,link,videos{format},photos{images},cover";

			}

			return $fields;

		}

		/**
		 * Get Facebook page business hour using page url.
		 *
		 * @since 2.0.0
		 *
		 * @param string $url Get facebook page URL.
		 *
		 * @todo about page seems to now be blocked, but the mobile main page seems to contain the hours.
		 *
		 * @return array $hour_array
		 */
		public function get_business_hours( $content ) {
			$social_importer = new Social_Importer_General();
			$opening_time = $social_importer->get_page_data( $content, 'u-Y3MNo413i.png', '</span>' );

			$hours = array();

			if ( strpos( $opening_time, 'Always open' ) !== false ) {
				$day_hours = array();
				$day_hours['open'] = '00:00';
				$day_hours['close'] = '00:00';

				$hours['Mo'] = array( $day_hours );
				$hours['Tu'] = array( $day_hours );
				$hours['We'] = array( $day_hours );
				$hours['Th'] = array( $day_hours );
				$hours['Fr'] = array( $day_hours );
				$hours['Sa'] = array( $day_hours );
				$hours['Su'] = array( $day_hours );
			} else {
				$schema = $social_importer->get_page_data( $content, '[[{"ctor":', '"className":null}],' );

				$week_arr = array(
					'Monday' => 'Mo',
					'Tuesday' => 'Tu',
					'Wednesday' => 'We',
					'Thursday' => 'Th',
					'Friday' => 'Fr',
					'Saturday' => 'Sa',
					'Sunday' => 'Su',
				);

				if ( ! empty( $schema ) && $schema !='' ) {
					$explode_hours = explode( ',"label":"', $schema );
					$time_format = geodir_bh_input_time_format();

					if ( ! empty( $explode_hours ) && is_array( $explode_hours ) ) {
						foreach ( $explode_hours as $tmp_key => $tmp_values ) {
							if ( strpos( $tmp_values, '"title"' ) !== false ) {
								$exp_hour = explode( '"title"', $tmp_values );
								$week_hour = !empty( $exp_hour[0] ) ? str_replace( '",', '', $exp_hour[0]) : '';
								$explode_time = !empty( $week_hour ) ? explode( ': ', $week_hour ) : '';
								$week_name = !empty( $explode_time[0] ) ? $explode_time[0] : '';

								if ( ! empty( $week_arr[ $week_name ] ) && '' != $week_arr[ $week_name ] ) {
									$explode_slots = !empty( $explode_time[1] ) ? explode( ',', $explode_time[1] ) : '';

									if ( ! empty( $explode_slots ) ) {
										$day_hours = array();

										foreach ( $explode_slots as $slot ) {
											$explode_times = !empty( $slot ) ? explode( '-', trim( $slot ) ) : '';

											if ( isset( $explode_times[1] ) ) {
												$open_time = $social_importer->convert_time_in_24h_format( trim( $explode_times[0] ) );
												$close_time = $social_importer->convert_time_in_24h_format( trim( $explode_times[1] ) );

												$day_hours[] = array(
													'open' => !empty( $open_time ) ? trim( $open_time ) : '',
													'close' => !empty( $close_time ) ? trim( $close_time ) : '',
													'open_display' => $open_time ? date_i18n( $time_format, strtotime( $open_time ) ) : $open_time,
													'close_display' => $close_time ? date_i18n( $time_format, strtotime( $close_time ) ) : $close_time
												);
											}
										}

										if ( ! empty( $day_hours ) ) {
											$hours[ $week_arr[ $week_name ] ] = $day_hours;
										}
									}
								}
							}
						}
					}
				}
			}

			return $hours;
		}

		public function get_images( $page_html, $image_limit = 0, $total_images = 0, $event = false ) {
			if ( $image_limit > 0 ) {
				$image_limit = $image_limit - $total_images;

				if ( $image_limit <= 0 ) {
					return false;
				}
			}

			if ( empty( $page_html ) ) {
				return false;
			}

			$listing_id = !empty( $_POST['post_id'] ) ? absint($_POST['post_id']) :'';

			$gd_media_obj = new GeoDir_Media();
			$social_importer_obj = new Social_Importer_General();

			if ( $event ) {
				$image_html = $social_importer_obj->get_page_data( $page_html, 'id="event_header_primary"', '</a>' );
			} else {
				$image_html = $social_importer_obj->get_page_data( $page_html, 'id="page_photos">', 'See all</a>' );
			}

			$images_arr = array();

			if ( ! empty( $image_html ) ) {
				$image_urls = self::parse_image_urls( $image_html );

				if ( ! empty( $image_urls ) ) {
					$wp_upload_dir = wp_upload_dir();
					$counter = 0;
					foreach ( $image_urls as $key => $image_url ) {
						if ( $image_url && strpos( $image_url, '.jpg?' ) !== false ) {
							$pathinfo = pathinfo( $image_url );
							$filename = $pathinfo['filename'];
							if ( $filename && strpos( $filename, '.jpg?' ) !== false ) {
								$_filename = explode( '.jpg?', $filename, 2 );
								$filename = $_filename[0];
							}

							$image_info = $gd_media_obj::get_external_media( $image_url, $filename );

							if ( ! is_wp_error( $image_info ) && isset( $image_info['url'] ) && $image_info['url'] ) {
								$image_title = apply_filters( 'geodir_social_post_image_title', '', array( 'site' => 'facebook', 'image_name' => $filename, 'post_id' => $listing_id, 'order' => $key, 'image_url' => $image_url, 'image_type' => 'post_images' ) );

								$image_temp = $gd_media_obj::insert_attachment( $listing_id, 'post_images', $image_info['url'], $image_title, '', -1, 0, 0 );

								if ( ! is_wp_error( $image_temp ) && ! empty( $image_temp['file'] ) ) {
									$image_temp['url'] = $wp_upload_dir['baseurl'] . $image_temp['file'];
									$image_temp['title'] = $image_title;
									$images_arr[] = $image_temp;
									$counter++;

									// delete original image
									@wp_delete_file( $image_info['file'] );
								} elseif ( is_wp_error( $image_temp ) ) {
									geodir_error_log( $image_temp->get_error_message(), 'insert_attachment', __FILE__, __LINE__ );
								}
							}
						}

						if ( $image_limit > 0 && $counter >= $image_limit ) {
							return $images_arr;
						}
					}
				}
			}

			return $images_arr;
		}

		/**
		 * Get Company logo using Facebook page.
		 *
		 * @since 2.0.0
		 *
		 * @param string $html Get page html.
		 *
		 * @return bool|array LOGO image.
		 */
		public function get_company_logo( $html, $insert_type ='logo' ) {
			if( empty($html)) {
				return false;
			}

			$listing_id = ! empty( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : '';
			$gd_media_obj = new GeoDir_Media();
			$social_importer_obj = new Social_Importer_General();
			$logo_html = $social_importer_obj->get_og_meta_tags_values( $html );
			$image_path = ! empty( $logo_html['image'] ) ? $logo_html['image'] :'';
			$images_arr = array();

			if ( $image_path ) {
				$image_path = str_replace("amp;","&",$image_path);
				$url_filename = pathinfo( $image_path );
				$counter = 0;
				$image_info = $gd_media_obj::get_external_media( $image_path,$url_filename['filename']);

				if ( $image_info['url'] ) {
					$image_title = apply_filters( 'geodir_social_post_image_title', '', array( 'site' => 'facebook', 'image_name' => $url_filename['filename'], 'post_id' => $listing_id, 'order' => 0, 'image_url' => $image_path, 'image_type' => $insert_type ) );

					if ( ! empty( $insert_type ) && 'logo' != $insert_type ) {
						$image_temp = $gd_media_obj::insert_attachment( $listing_id, $insert_type, $image_info['url'], $image_title, '', -1, 0, 0 );
					} else {
						$image_temp = $gd_media_obj::insert_attachment( $listing_id, 'logo', $image_info['url'], $image_title, '', -1, 0, 0 );
					}

					$wp_upload_dir = wp_upload_dir();

					if ( ! is_wp_error( $image_temp ) && ! empty( $image_temp['file'] ) ) {
						$image_temp['url'] = $wp_upload_dir['baseurl'] . $image_temp['file'];
						$images_arr['url'] = $image_temp;
						$image_temp['title'] = $image_title;
						$images_arr['title'] = $image_title;

						// delete original image
						@wp_delete_file( $image_info['file'] );
					} elseif ( is_wp_error( $image_temp ) ) {
						geodir_error_log( $image_temp->get_error_message(), 'insert_attachment', __FILE__, __LINE__ );
					}
				}
			}

			return $images_arr;
		}

        public function get_videos( $page_content, $page_url ) {

		    if( empty( $page_content ) ) {
		        return false;
            }

            $page_url = trim($page_url,'/');
            $fburl = !empty( $page_url ) ? explode('/',$page_url) : '';

            $social_importer_obj = new Social_Importer_General();

            $get_videos = array();

            if( !empty( $fburl ) && is_array( $fburl ) ) {
               $last_val = end($fburl);

               if( strpos($last_val, '-') !== false){
                   $last_temp = explode('-', $last_val );
                   $last_val = end($last_temp);

                   end($fburl);
                   $key = key($fburl);
                   unset($fburl[$key]);
                   $fburl[] = !empty($last_val ) ? $last_val :'';

                   $page_url = !empty($fburl) ? implode('/',$fburl) :'';
               }

               $explode_video = 'href="/'.$last_val.'/videos/';

               $explode_html = explode($explode_video, $page_content);

               if( !empty( $explode_html ) && is_array( $explode_html ) ) {

                   foreach ( $explode_html as $keys => $exp_values ) {

                       if( strpos( $exp_values, 'aria-label="Video' ) !== false ) {

                           $video_html = explode('aria-label="Video', $exp_values);

                           $get_video_id = !empty( $video_html[0] ) ? str_replace('/"','',$video_html[0]) : '';

                           $last_value = substr("$page_url", -1);

                           $videos = 'videos';

                           if( '/' !== $last_value  ) {
                               $videos = '/'.$videos;
                           }

                           $video_url = $page_url.$videos.'/'.$get_video_id;

                           $video_url = str_replace( 'en-gb.facebook.com','www.facebook.com', $video_url);

                           $get_videos[] = $video_url;
                       }

                   }
               }
            }

            return $get_videos;

        }

        public function get_event_date_time( $response_html ) {

		    if( empty( $response_html ) ) {
		        return false;
            }

            $social_importer_obj = new Social_Importer_General();

            $event_date_content = $social_importer_obj->get_page_data( $response_html,'id="event_time_info"','</li>');

            $explode_date_content = !empty( $event_date_content ) ? explode('class="_2ycp _5xhk"',$event_date_content) : '';

            $explode_date = !empty( $explode_date_content[1] ) ? explode('content="',$explode_date_content[1]) :'';
            $explode_date = !empty( $explode_date[1] ) ? explode('">',$explode_date[1] ) : '';
            $explode_date = !empty( $explode_date[0] ) ? explode('to',$explode_date[0] ) : '';

            $event_start_date = !empty( $explode_date[0] ) ? explode( 'T', $explode_date[0] ) : '';
            $event_start_date = !empty( $event_start_date[0] ) ? $event_start_date[0] :'';
            $event_end_date = !empty( $explode_date[1] ) ? explode( 'T', $explode_date[1] ) : '';
            $event_end_date = !empty( $event_end_date[0] ) ? $event_end_date[0] :'';

            $event_time_content = !empty( $explode_date_content[1] ) ? explode('">',$explode_date_content[1]) :'';
            $event_time_content = !empty( $event_time_content[1] ) ? explode('</div>', $event_time_content[1] ) :'';
            $event_time_content = !empty( $event_time_content[0] ) ? explode('from', $event_time_content[0] ) :'';
            $event_time_content = !empty( $event_time_content[1] ) ? explode(' ', trim($event_time_content[1]) ) :'';

            $time_explode = !empty( $event_time_content[0] ) ? explode('-',$event_time_content[0]) :'';
            $start_time = !empty( $time_explode[0] ) ? $time_explode[0] :'';
            $end_time = !empty( $time_explode[1] ) ? $time_explode[1] :'';

            if( empty( $event_time_content ) && $event_time_content =='' ) {
                $event_time_content = !empty( $explode_date_content[1] ) ? explode('">',$explode_date_content[1]) :'';
                $event_time_content = !empty( $event_time_content[1] ) ? explode('</div>', $event_time_content[1] ) :'';
                $time_explode = !empty( $event_time_content[0] ) ? explode('–',$event_time_content[0]) :'';
                $start_times = !empty( $time_explode[0] ) ? explode('at ',$time_explode[0]) :'';
                $start_time = !empty( $start_times[1] ) ? trim( $start_times[1] ) : '';
                $start_time = !empty( $start_time ) ? explode( ' ',$start_time) :'';
                $start_time = !empty( $start_time[0] ) ? $start_time[0] :'';
                $end_times = !empty( $time_explode[1] ) ? explode('at ',$time_explode[1]) :'';
                $end_time = !empty( $end_times[1] ) ? explode(' ',trim($end_times[1])) : '';
                $end_time = !empty( $end_time[0] ) ? $end_time[0] :'00:00';
            }

            $date_time_arr = array();

            $date_time_arr['event_sdate'] = trim( $event_start_date );
            $date_time_arr['event_edate'] = trim( $event_end_date );
            $date_time_arr['event_stime'] = trim( $start_time );
            $date_time_arr['event_etime'] = trim( $end_time );

            return $date_time_arr;
        }

		public static function parse_image_urls( $content ) {
			$images = array();
			$matches = array();

			if ( strpos( $content, ' data-ploi=' ) !== false ) {
				preg_match_all( '/( data-ploi)=["\'](.*)["\']/Ui', $content, $matches );
			}

			if ( ! ( ! empty( $matches ) && array_key_exists( 2, $matches ) ) ) {
				preg_match_all( '/( src)=["\'](.*)["\']/Ui', $content, $matches );
			}

			if ( ! empty( $matches ) && array_key_exists( 2, $matches ) ) {
				foreach( $matches[2] as $key => $src ) {
					$src = trim( $src );

					if ( $src != '' ) {
						$images[] = str_replace( 'amp;', '&', $src );
					}
				}
			}

			return $images;
		}

		public function get_about_data( $html ) {
			$about = array();

			if ( empty( $html ) ) {
				return $about;
			}

			$social_importer = new Social_Importer_General();

			$meta_tags = $social_importer->get_og_meta_tags_values( $html );

			if ( ! empty( $meta_tags['title'] ) ) {
				$about['meta_title'] = $meta_tags['title'];
			}

			if ( ! empty( $meta_tags['description'] ) ) {
				$about['meta_description'] = $meta_tags['description'];
			}

			if ( ! empty( $meta_tags['image'] ) ) {
				$about['meta_image'] = $meta_tags['image'];
			}

			$description_html = $social_importer->get_page_data( $html, '<div class="_50f4">About</div>', '</div>' );
			if ( ! empty( $description_html ) ) {
				$about['description'] = trim( wp_strip_all_tags( str_replace( '<div class="_3-8w">', '', $description_html ) ) );
			}

			preg_match_all( '/<div class="_5aj7 _3-8j"><div class="(.*)"><img class="(.*)" src="(.*)"(.*)><\/div><div class="_4bl9">(.*)<\/div><div class="_4bl7 _3-99"><\/div>/U', $html, $matches );
			if ( ! empty( $matches[3] ) && ! empty( $matches[5] ) ) {
				foreach ( $matches[3] as $key => $icon ) {
					if ( empty( $icon ) || empty( $matches[5][ $key ] ) ) {
						continue;
					}

					$content = $matches[5][ $key ];

					if ( empty( $about['phone'] ) && ( strpos( $icon, '/oXiCJHPgn3c.png' ) !== false || strpos( $icon, '/4VjyF4t9Hqt.png' ) !== false || strpos( $icon, '/mYv88EsODOI.png' ) !== false ) ) { // Phone
						$content = trim( html_entity_decode( str_replace( 'Call', '', strip_tags( $content ) ) ) );

						if ( ! empty( $content ) ) {
							$about['phone'] = $content;
						}
					} elseif ( empty( $about['email'] ) && strpos( $icon, '/vKDzW_MdhyP' ) !== false ) { // Email
						$content = trim( html_entity_decode( urldecode( strip_tags( $content ) ) ) );

						if ( ! empty( $content ) ) {
							$about['email'] = $content;
						}
					} elseif ( empty( $about['website'] ) && ( strpos( $icon, '/EaDvTjOwxIV.png' ) !== false || strpos( $icon, '/ZBnKG6mAW8D.png' ) !== false || strpos( $icon, '/xVA3lB-GVep.png' ) !== false ) ) { // Website
						$content = trim( html_entity_decode( urldecode( strip_tags( $content ) ) ) );

						if ( ! empty( $content ) ) {
							$about['website'] = $content;
						}
					}
				}
			}

			if ( ! empty( $meta_tags['schema'] ) ) {
				$about['schema'] = $meta_tags['schema'];

				if ( empty( $about['phone'] ) && ! empty( $about['schema']->telephone ) ) {
					$about['phone'] = $about['schema']->telephone;
				}
			}

			$about['business_hours'] = $this->get_business_hours( $html );

			return $about;
		}

	}
}