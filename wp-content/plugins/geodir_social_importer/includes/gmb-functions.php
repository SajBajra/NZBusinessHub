<?php
/**
 * Google my business related functions.
 *
 * @since 2.1.0.3
 * @package Geodir_Social_Importer
 */
 
function geodir_social_gmb_auth_url() {
	return add_query_arg( 
		array(
			'client_id'       => GEODIR_GMB_CLIENT_ID,
			'scope'           => GEODIR_GMB_SCOPE,
			'redirect_uri'    => urlencode( GEODIR_GMB_REDIRECT ),
			'access_type'     => 'offline',
			'approval_prompt' => 'force',
			'response_type'   => 'code'
		), 
		GEODIR_GMB_AUTH_URL
	);
}

function geodir_social_gmb_token_url( $auth_code ) {
	return add_query_arg( 
		array(
			'code'          => $auth_code,
			'client_id'     => GEODIR_GMB_CLIENT_ID,
			'client_secret' => GEODIR_GMB_CLIENT_SECRET,
			'redirect_uri'  => urlencode( GEODIR_GMB_REDIRECT ),
			'grant_type'    => 'authorization_code'
		), 
		GEODIR_GMB_TOKEN_URL 
	);
}

function geodir_social_gmb_refresh_token_url( $token = null ) {
	if ( $token === null ) {
		$token = geodir_get_option( 'si_gmb_refresh_token' );
	}

	return add_query_arg( 
		array(
			'client_id'     => GEODIR_GMB_CLIENT_ID,
			'client_secret' => GEODIR_GMB_CLIENT_SECRET,
			'grant_type'    => 'refresh_token',
			'refresh_token' => $token
		), 
		GEODIR_GMB_TOKEN_URL 
	);
}

function geodir_social_gmb_revoke_url( $token ) {
	return add_query_arg( 
		array(
			'token' => $token
		), 
		GEODIR_GMB_REVOKE_URL 
	);
}

function geodir_social_gmb_access_token() {
	$transient_key = 'geodir_social_gmb_access_token';
	$transient = get_transient( $transient_key );

	if ( $transient !== false ) {
		return $transient;
	} else {
		$response = wp_remote_post( geodir_social_gmb_refresh_token_url(), array( 'timeout' => 15 ) );

		$results =  __( 'Something went wrong.','gd-social-importer' );

		if ( ! is_wp_error( $response ) ) {
			if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
				$_response = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( ! empty( $_response['access_token'] ) ) {
					$results = $_response['access_token'];
					geodir_update_option( 'si_gmb_access_token', sanitize_text_field( $_response['access_token'] ) );
					geodir_update_option( 'si_gmb_access_token_date', date( 'Y-m-d H:i:s' ) );
					geodir_update_option( 'si_gmb_expires_in', time() + absint( $_response['expires_in'] ) );

					set_transient( $transient_key, sanitize_text_field( $_response['access_token'] ), absint( $_response['expires_in'] ) - ( MINUTE_IN_SECONDS * 5 ) );
				} else {
					$results =  __( 'Access token not found.','gd-social-importer' );
				}
			} elseif ( ! empty( $response['response']['code'] ) ) {
				$_response = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( isset( $_response['error'] ) ) {
					$results =  $_response['error'] . ": " . $_response['error_description'] . '(' . $response['response']['code'] . ')';
				}
			}
		} else {
			$results =  $response->get_error_message();
		}
	}

	return $results;
}

function geodir_social_gmb_get_accounts() {
	$transient_key = 'geodir_social_gmb_get_accounts';
	$transient = get_transient( $transient_key );

	if ( $transient !== false ) {
		return $transient;
	} else {
		$response = wp_remote_get( 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts', array(
			'headers' => array(
				'Authorization' => 'Bearer '. geodir_social_gmb_access_token(),
			),
			'timeout' => 15
		) );

		$results =  __( 'Something went wrong.','gd-social-importer' );

		if ( ! is_wp_error( $response ) ) {
			if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
				$_response = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( ! empty( $_response['accounts'] ) ) {
					$results = $_response['accounts'];

					set_transient( $transient_key, $results, DAY_IN_SECONDS * 7 );
				} else {
					$results =  __( 'No accounts found.','gd-social-importer' );
				}
			} elseif ( ! empty( $response['response']['code'] ) ) {
				$_response = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( ! empty( $_response['error']['code'] ) ) {
					$results = "[" . $_response['error']['code'] . "] " . $_response['error']['message'];
				}
			}
		} else {
			$results =  $response->get_error_message();
		}
	}

	return $results;
}

function geodir_social_gmb_get_locations() {
	$transient_key = 'geodir_social_gmb_get_locations';
	$transient = get_transient( $transient_key );

	if ( $transient !== false ) {
		return $transient;
	} else {
		$account = geodir_get_option( 'si_gmb_account' );
		if ( empty( $account ) ) {
			$results =  __( 'No accounts selected.','gd-social-importer' );
		}

		$response = wp_remote_get( 'https://mybusinessbusinessinformation.googleapis.com/v1/' . $account . '/locations/?readMask=name,title,storefrontAddress', array(
			'headers' => array(
				'Authorization' => 'Bearer '. geodir_social_gmb_access_token(),
			),
			'timeout' => 15
		) );

		$results =  __( 'Something went wrong.','gd-social-importer' );

		if ( ! is_wp_error( $response ) ) {
			if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
				$_response = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( ! empty( $_response['locations'] ) ) {
					$results = $_response['locations'];

					set_transient( $transient_key, $results, DAY_IN_SECONDS * 7 );
				} else {
					$results =  __( 'No locations found.','gd-social-importer' );
				}
			} elseif ( ! empty( $response['response']['code'] ) ) {
				$_response = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( ! empty( $_response['error']['code'] ) ) {
					$results = "[" . $_response['error']['code'] . "] " . $_response['error']['message'];
				}
			}
		} else {
			$results =  $response->get_error_message();
		}
	}

	return $results;
}

function geodir_social_gmb_get_location( $location = null, $token = null ) {
	if ( $location === null ) {
		$location = geodir_get_option( 'si_gmb_location' );
	}

	if ( $token === null ) {
		$token = geodir_social_gmb_access_token();
	}

	/**
	 * name,languageCode,storeCode,title,phoneNumbers,categories,storefrontAddress,websiteUri,regularHours,
	 * specialHours,serviceArea,labels,latlng,openInfo,metadata,profile,relationshipData,moreHours,serviceItems
	 */
	$response = wp_remote_get( 'https://mybusinessbusinessinformation.googleapis.com/v1/' . $location . '/?readMask=name,title,phoneNumbers,storefrontAddress,websiteUri,regularHours,labels,latlng,profile', array(
		'headers' => array(
			'Authorization' => 'Bearer '. $token,
		),
		'timeout' => 15
	) );

	$results =  __( 'Something went wrong while retrieving the location.','gd-social-importer' );

	if ( ! is_wp_error( $response ) ) {
		if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
			$_response = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! empty( $_response['title'] ) ) {
				$results = $_response;
			} else {
				$results =  __( 'Invalid location.','gd-social-importer' );
			}
		} elseif ( ! empty( $response['response']['code'] ) ) {
			$_response = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! empty( $_response['error']['code'] ) ) {
				$results = "[" . $_response['error']['code'] . "]" . $_response['error']['message'];
			}
		}
	} else {
		$results = $response;
	}

	if ( is_array( $results ) ) {
	} else {
		$results = new WP_Error( 'invalid_location', __( 'Invalid location.','gd-social-importer' ) );
	}

	return $results;
}

function geodir_social_gmb_parse_locations( $locations = null ) {
	if ( $locations === null ) {
		$locations = geodir_social_gmb_get_locations();
	}

	$_locations = array();

	if ( ! empty( $locations ) && is_array( $locations ) ) {
		foreach ( $locations as $location ) {
			$locations_key = $location['name'];
			$locations_label = $location['title'];
			$_address = ! empty( $location['storefrontAddress'] ) ? $location['storefrontAddress'] : array();

			if ( ! empty( $_address ) && ! empty( $location['storefrontAddress']['addressLines'] ) ) {
				$address = implode( ", ", $_address['addressLines'] );

				if ( ! empty( $_address['locality'] ) ) {
					$address .= ", " . $_address['locality'];
				}

				if ( ! empty( $_address['administrativeArea'] ) ) {
					$address .= ", " . $_address['administrativeArea'];
				}

				if ( ! empty( $_address['postalCode'] ) ) {
					$address .= ", " . $_address['postalCode'];
				}

				if ( ! empty( $_address['regionCode'] ) ) {
					$address .= ", " . $_address['regionCode'];
				}
				
				$locations_label .= " (" . $address . ")";
			}

			$_locations[ $locations_key ] = $locations_label;
		}
	}

	return $_locations;
}

function geodir_social_gmb_get_media( $account, $location = null, $token = null ) {
	if ( $location === null ) {
		$location = geodir_get_option( 'si_gmb_location' );
	}

	if ( $token === null ) {
		$token = geodir_social_gmb_access_token();
	}

	$response = wp_remote_get( 'https://mybusiness.googleapis.com/v4/' . $account . '/' . $location . '/media?pageSize=10', array(
		'headers' => array(
			'Authorization' => 'Bearer '. $token,
		),
		'timeout' => 15
	) );

	$results =  __( 'Something went wrong while retrieving the media.','gd-social-importer' );

	if ( ! is_wp_error( $response ) ) {
		if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
			$_response = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! empty( $_response['mediaItems'] ) ) {
				$results = $_response;
			} else {
				$results =  __( 'Invalid location.','gd-social-importer' );
			}
		} elseif ( ! empty( $response['response']['code'] ) ) {
			$_response = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! empty( $_response['error']['code'] ) ) {
				$results = "[" . $_response['error']['code'] . "]" . $_response['error']['message'];
			}
		}
	} else {
		$results = $response;
	}

	if ( is_array( $results ) ) {
	} else {
		$results = new WP_Error( 'invalid_location', __( 'Invalid location.','gd-social-importer' ) );
	}

	return $results;
}

function geodir_social_gmb_create_post( $postID ) {
	$account = geodir_get_option( 'si_gmb_account' );
	$location = geodir_get_option( 'si_gmb_location' );

	if ( empty( $account ) ) {
		return new WP_Error( 'invalid_account', __( 'No business account selected.','gd-social-importer' ) );
	}

	if ( empty( $location ) ) {
		return new WP_Error( 'invalid_location', __( 'No business location selected.','gd-social-importer' ) );
	}

	$post = get_post( $postID );
	$post_type_object = get_post_type_object( $post->post_type );

	if ( geodir_is_gd_post_type( $post->post_type ) ) {
		$pt_plural_name = geodir_post_type_name( $post->post_type, true );
		$pt_singular_name = geodir_post_type_singular_name( $post->post_type, true );
	} else {
		$pt_plural_name = ! empty( $object->labels->name ) ? __( $object->labels->name ) : __( 'Posts' );
		$pt_singular_name = ! empty( $object->labels->singular_name ) ? __( $object->labels->singular_name ) : __( 'Post' );
	}

	$variables = array(
		"post_title" => html_entity_decode( $post->post_title ),
		"post_excerpt" => wp_strip_all_tags( $post->post_excerpt ),
		"post_content" => wp_strip_all_tags( $post->post_content ),
		"post_author" => geodir_get_client_name( $post->post_author ),
		"pt_plural_name" => $pt_plural_name,
		"pt_singular_name" => $pt_singular_name,
		"sitename" => html_entity_decode( geodir_get_blogname() )
	);

	$summary = geodir_social_gmb_post_template( $postID );

	foreach ( $variables as $key => $value ) {
		$summary = str_replace( '{' . $key . '}', $value, $summary ); 
	}

	/**
	 * The topic type of the local post.
	 * One of LOCAL_POST_TOPIC_TYPE_UNSPECIFIED, STANDARD, EVENT, OFFER, ALERT
	 * See https://developers.google.com/my-business/reference/rest/v4/accounts.locations.localPosts#LocalPostTopicType
	 */
	$topicType = 'STANDARD';

	/**
	 * The type of event for which the alert post was created.
	 * One of ALERT_TYPE_UNSPECIFIED, COVID_19
	 * See https://developers.google.com/my-business/reference/rest/v4/accounts.locations.localPosts#alerttype
	 */
	$alertType = 'ALERT_TYPE_UNSPECIFIED';

	$params = array(
		'name' => $variables['post_title'],
		'languageCode' => get_locale(),
		'summary' => substr( $summary, 0, 1490 ),
		'callToAction' => array(
			'actionType' => 'LEARN_MORE',
			'url' => get_permalink( $postID )
		),
		'topicType' => $topicType,
		'alertType' => $alertType
	);

	$image_url = '';
	$event = array();
	if ( geodir_is_gd_post_type( $post->post_type ) ) {
		$post_images = geodir_get_images( $postID, '1' );

		if ( ! empty( $post_images ) ) {
			$upload_dir = wp_upload_dir();

			$image_url = $upload_dir['baseurl'] . $post_images[0]->file;
		}

		if ( GeoDir_Post_types::supports( $post->post_type, 'events' ) && class_exists( 'GeoDir_Event_Schedules' ) ) {
			$schedules = GeoDir_Event_Schedules::get_schedules( $post->ID, 'upcoming', 1 );
			if ( empty( $schedules ) ) {
				$schedules = GeoDir_Event_Schedules::get_schedules( $post->ID, 'all', 1 );
			}

			if ( ! empty( $schedules ) ) {
				$startDateTime = strtotime( $schedules[0]->start_date );
				$endDateTime = strtotime( $schedules[0]->end_date );

				if ( empty( $schedules[0]->all_day ) && $startDateTime == $endDateTime && $schedules[0]->start_time == $schedules[0]->end_time ) {
					$endDateTime = $endDateTime + DAY_IN_SECONDS;
				}

				$startDateYear = date( 'Y', $startDateTime );
				$endDateYear = date( 'Y', $endDateTime );

				$startDateMonth = date( 'n', $startDateTime );
				$endDateMonth = date( 'n', $endDateTime );

				$startDateDay = date( 'j', $startDateTime );
				$endDateDay = date( 'j', $endDateTime );

				if ( ! empty( $schedules[0]->all_day ) ) {
					$startDateHours = 0;
					$startDateMinutes = 0;

					if ( $startDateTime == $endDateTime ) {
						$endDateHours = 23;
						$endDateMinutes = 59;
					} else {
						$endDateHours = 0;
						$endDateMinutes = 0;
					}
				} else {
					$startTime = strtotime( $schedules[0]->start_time );
					$endTime = strtotime( $schedules[0]->end_time );

					$startDateHours = date( 'G', $startTime );
					$endDateHours = date( 'G', $endTime );

					$startDateMinutes = intval( date( 'i', $startTime ) );
					$endDateMinutes = intval( date( 'i', $endTime ) );
				}

				$event = array( 
					'title' => $variables['post_title'],
					'schedule' => array(
						'startDate' => array(
							'year' => $startDateYear,
							'month' => $startDateMonth,
							'day' => $startDateDay
						),
						'startTime' => array(
							'hours' => $startDateHours,
							'minutes' => $startDateMinutes
						),
						'endDate' => array(
							'year' => $endDateYear,
							'month' => $endDateMonth,
							'day' => $endDateDay
						),
						'endTime' => array(
							'hours' => $endDateHours,
							'minutes' => $endDateMinutes
						)
					)
				);
			}
		}
	} else {
		if ( has_post_thumbnail( $postID ) ) {
			$thumbnail_url = get_the_post_thumbnail_url( $postID, 'full' );

			if ( empty( $thumbnail_url ) ) {
				$thumbnail_url = get_the_post_thumbnail_url( $postID );
			}

			if ( ! empty( $thumbnail_url ) ) {
				$image_url = $thumbnail_url;
			}
		}
	}

	if ( ! empty( $image_url ) ) {
		$params['media'] = array(
			"sourceUrl" => $image_url,
			"mediaFormat" => "PHOTO",
		);
	}

	if ( ! empty( $event ) ) {
		$params['event'] = $event;
		$params['topicType'] = 'EVENT';
	}

	$request_data = json_encode( $params );

	$response = wp_remote_post( 'https://mybusiness.googleapis.com/v4/' . $account . '/' . $location . '/localPosts', array(
		'headers' => array(
			'Authorization' => 'Bearer '. geodir_social_gmb_access_token(),
			'Content-Type' => 'application/json; charset=utf-8',
		),
		'body' => $request_data,
		'timeout' => 15
	) );

	if ( ! is_wp_error( $response ) ) {
		if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
			$_response = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! empty( $_response['searchUrl'] ) ) {
				$results = $_response;
			} else {
				$results =  new WP_Error( 'post_to_gmb_error', __( 'Error in create post to Google My Business.','gd-social-importer' ) );
			}
		} elseif ( ! empty( $response['response']['code'] ) ) {
			$_response = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! empty( $_response['error']['code'] ) ) {
				$results = "[" . $_response['error']['code'] . "] " . $_response['error']['message'];
			} else if ( ! empty( $response['response']['code'] ) && ! empty( $response['response']['message'] ) ) {
				$results = "[" . $response['response']['code'] . "] " . $response['response']['message'];
			}

			if ( ! empty( $_response['error']['details'][0]['errorDetails'] ) ) {
				$errors = array();
				foreach ( $_response['error']['details'][0]['errorDetails'] as $i => $error ) {
					$_error = ( $i + 1 ) . ') ' . $error['message'] . ' ' . __( 'for', 'gd-social-importer' ) . ' ' . $error['field'];
					if ( ! empty( $error['value'] ) ) {
						$_error .= '(' . $error['value'] . ')';
					}
					$errors[] = $_error . '.';
				}

				$results =  new WP_Error( 'post_to_gmb_error', $results . ' ' . implode( " ", $errors ) );
			} else {
				if ( ! empty( $results ) ) {
					$results =  new WP_Error( 'post_to_gmb_error', $results );
				} else {
					$results =  new WP_Error( 'post_to_gmb_error', __( 'Something went wrong while posting to GMB.','gd-social-importer' ) );
				}
			}
		} else {
			$results =  new WP_Error( 'post_to_gmb_error', __( 'Something went wrong while posting to GMB.','gd-social-importer' ) );
		}
	} else {
		$results = $response;
	}

	return $results;
}

function geodir_social_gmb_post_template( $postID = 0 ) {
	$template = trim( wp_unslash( geodir_get_option( 'si_gmb_post_text' ) ) );

	if ( ! $template ) {
		$social_importer = new Social_Importer_General();
		$template = $social_importer->post_to_gmb_text();
	}

	return $template;
}