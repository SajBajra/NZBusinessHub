<?php
/**
 * Franchise Manager Post class.
 *
 * @since 2.0.0
 * @package Geodir_Franchise
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Franchise_Post class.
 */
class GeoDir_Franchise_Post {

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		// Clean cache
		add_action( 'clean_post_cache', array( __CLASS__, 'clean_post_cache' ), 1, 2 );
		add_action( 'geodir_after_custom_fields_updated', array( __CLASS__, 'on_save_custom_field' ), 1, 2 );
		add_action( 'geodir_post_type_saved', array( __CLASS__, 'on_post_type_saved' ), 1, 3 );
		add_action( 'geodir_pricing_clean_package_cache', array( __CLASS__, 'on_clean_package_cache' ), 1, 2 );

		// Save franchise data
		add_filter( 'geodir_save_post_data', array( __CLASS__, 'save_post_data' ), 101, 4 );
		add_filter( 'geodir_save_post_data', array( __CLASS__, 'save_franchise_data' ), 9999, 4 );
		add_filter( 'geodir_franchise_save_franchise_field', array( __CLASS__, 'save_franchise_field' ), 10, 5 );
		add_filter( 'geodir_franchise_save_franchise_field_post_category', array( __CLASS__, 'save_franchise_post_categories' ), 10, 4 );
		add_filter( 'geodir_franchise_save_franchise_field_post_tags', array( __CLASS__, 'save_franchise_post_tags' ), 10, 4 );
		add_filter( 'geodir_franchise_save_franchise_field_featured_image', array( __CLASS__, 'save_franchise_featured_image' ), 10, 4 );
		add_filter( 'geodir_franchise_save_franchise_fields', array( __CLASS__, 'save_franchise_fields' ), 10, 5 );
		add_filter( 'geodir_pre_delete_attachment_file', array( __CLASS__, 'pre_delete_attachment_file' ), 10, 4 );
		add_filter( 'geodir_save_post_temp_data', array( __CLASS__, 'save_post_temp_data' ), 9, 3 );
		add_filter( 'geodir_author_actions', array( __CLASS__, 'author_actions' ), 20, 2 );
		add_filter( 'geodir_widget_gd_listings_view_all_url', array( __CLASS__, 'gd_listings_view_all_url' ), 20, 5 );

		// Claim Listings
		add_action( 'geodir_claim_approved', array( __CLASS__, 'on_claim_approved' ), 11, 1 );
		add_action( 'geodir_claim_undone', array( __CLASS__, 'on_claim_undone' ), 11, 1 );

		// Lock Comments
		// Reviews
		add_action( 'geodir_single_tab_content_before', array( __CLASS__, 'before_single_tab_content' ), 1, 2 );
		add_action( 'geodir_single_tab_content_after', array( __CLASS__, 'after_single_tab_content' ), 11, 2 );
		add_action( 'comment_form', array( __CLASS__, 'comment_form' ) );
		add_filter( 'comment_post_redirect', array( __CLASS__, 'comment_post_redirect' ), 99, 2 );

		// Post rating widget
		add_action( 'geodir_post_rating_widget_content_before', array( __CLASS__, 'before_post_rating_widget_content' ), 1 );
		add_action( 'geodir_post_rating_widget_content_after', array( __CLASS__, 'after_post_rating_widget_content' ), 11 );
		add_filter( 'get_comments_link', array( __CLASS__, 'get_comments_link' ), 11, 2 );

		// Single reviews widget
		add_action( 'geodir_single_reviews_widget_content_before', array( __CLASS__, 'before_single_reviews_widget_content' ), 1 );
		add_action( 'geodir_single_reviews_widget_content_after', array( __CLASS__, 'after_single_reviews_widget_content' ), 11 );

		// Post meta widget
		add_action( 'geodir_widget_post_meta_set_id', array( __CLASS__, 'set_post_meta_id' ), 1, 2 );
		add_action( 'geodir_widget_post_meta_reset_id', array( __CLASS__, 'reset_post_meta_id' ), 11, 2 );
	}

	public static function save_post_data( $postarr, $gd_post, $post, $update ) {
		if ( ! ( ! empty( $gd_post['post_type'] ) && GeoDir_Post_types::supports( $gd_post['post_type'], 'franchise' ) ) ) {
			return $postarr;
		}

		if ( ! ( isset( $gd_post['franchise'] ) || isset( $gd_post['franchise_of'] ) ) ) {
			return $postarr;
		}

		if ( isset( $gd_post['franchise'] ) && empty( $gd_post['franchise_of'] ) ) {
			// Main post
			if ( empty( $gd_post['franchise'] ) ) {
				$gd_post['franchise_fields'] = '';
			}
		} else if ( ! empty( $gd_post['franchise_of'] ) ) {
			// Franchise post
			$gd_post['franchise'] = 0;
			$gd_post['franchise_fields'] = '';
		}

		return $postarr;
	}

	public static function save_franchise_data( $postarr, $gd_post, $post, $update ) {
		global $franchise_merge_main_id;

		if ( ! ( ! empty( $gd_post['post_type'] ) && GeoDir_Post_types::supports( $gd_post['post_type'], 'franchise' ) ) ) {
			return $postarr;
		}

		// Set locked fields to franchise post
		if ( empty( $gd_post['franchise'] ) && ! geodir_franchise_is_main( (int) $post->ID ) ) {
			$main_post_id = 0;
			if ( ! empty( $gd_post['franchise_of'] ) && ! (int) geodir_get_post_meta( $post->ID, 'franchise_of', true ) ) {
				$main_post_id = (int) $gd_post['franchise_of'];
			} else {
				if ( geodir_franchise_is_franchise( (int) $post->ID ) && $franchise_merge_main_id && ( $franchise_merge_main_id == geodir_franchise_main_post_id( (int) $post->ID ) ) ) {
					$main_post_id = $franchise_merge_main_id;
				}
			}

			if ( $main_post_id ) {
				$locked_fields = geodir_franchise_post_locked_fields( $main_post_id );

				if ( empty( $locked_fields ) ) {
					$locked_fields = array();
				}

				$franchise_postarr = array();
				if ( ! empty( $locked_fields ) ) {
					$main_post = geodir_get_post_info( $main_post_id );
					$main_fields = array_keys( (array) $main_post );
					$main_current_fields = array_keys( $postarr );

					foreach ( $locked_fields as $field ) {
						if ( in_array( $field, $main_fields ) ) {
							$value = in_array( $field, $main_current_fields ) && $field != 'post_category' && $field != 'post_tags' && ! ( isset( $postarr[ 'post_status' ] ) && $postarr[ 'post_status' ] == 'inherit' ) ? $postarr[ $field ] : $main_post->$field;
							$value = apply_filters( 'geodir_franchise_save_franchise_field', $value, $field, (int) $post->ID, $main_post_id, $postarr );
							$value = apply_filters( 'geodir_franchise_save_franchise_field_' . $field, $value, (int) $post->ID, $main_post_id, $postarr );
							$franchise_postarr[ $field ] = $value;
						}
					}
				}

				$franchise_postarr = apply_filters( 'geodir_franchise_save_franchise_fields', $franchise_postarr, (int) $post->ID, $main_post_id, $locked_fields, $postarr );

				if ( ! empty( $franchise_postarr ) ) {
					foreach ( $franchise_postarr as $key => $value ) {
						$postarr[ $key ] = $value;
					}
				}
			}
		} else if ( isset( $gd_post['franchise'] ) ) {
			if ( geodir_get_post_meta( $post->ID, 'franchise', true ) ) {
				self::merge_post_franchises( $post->ID, NULL, NULL, $gd_post ); // Merge fields
			} else {
				self::unlink_franchise( $post->ID ); // Unlink  franchises
			}
		}

		return $postarr;
	}
	
	public static function save_franchise_fields( $postarr, $post_id, $main_post_id, $locked_fields = array(), $current_postarr = array() ) {
		$post_type = get_post_type( $main_post_id );

		if ( ! empty( $locked_fields ) && is_array( $locked_fields ) && in_array( 'post_content', $locked_fields ) && isset( $postarr['post_content'] ) ) {
			unset( $postarr[ 'post_content' ] );
		}

		// Event data
		if ( defined( 'GEODIR_EVENT_VERSION' ) && isset( $postarr['event_dates'] ) ) {
			if ( GeoDir_Post_types::supports( $post_type, 'events' ) ) {
				GeoDir_Event_Schedules::save_schedules( $postarr['event_dates'], $post_id );
			}
		}

		// Link post data
		if ( defined( 'GEODIR_CP_VERSION' ) ) {
			$link_post_types = GeoDir_CP_Link_Posts::linked_to_post_types( $post_type );

			if ( ! empty( $locked_fields ) && ! empty( $link_post_types ) ) {
				foreach ( $link_post_types as $link_post_type ) {
					if ( ! ( is_array( $locked_fields ) && in_array( $link_post_type, $locked_fields ) ) ) {
						continue;
					}

					$posts = GeoDir_CP_Link_Posts::get_meta_value( '', $main_post_id, $link_post_type, true );

					GeoDir_CP_Link_Posts::save_link_posts( $posts, $post_id, $link_post_type );

					if ( isset( $postarr[ $link_post_type ] ) ) {
						unset( $postarr[ $link_post_type ] );
					}
				}
			}
		}

		return $postarr;
	}
	
	public static function save_franchise_field( $value, $field, $post_id, $main_post_id, $postarr = array() ) {
		global $wpdb;

		$file_fields = self::get_file_fields( get_post_type( $main_post_id ) );

		if ( ! empty( $file_fields ) && in_array( $field, $file_fields ) ) {
			$_post_id = (int) wp_is_post_revision( $post_id );

			if ( $_post_id ) {
				$post_id = $_post_id;
			}

			$delete = $wpdb->delete( GEODIR_ATTACHMENT_TABLE, array( 'post_id' => $post_id, 'type' => $field ), array( '%d', '%s' ) );

			if ( $delete === 0 && empty( $value ) ) {
				return $value;
			}

			$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . GEODIR_ATTACHMENT_TABLE . " WHERE type = %s AND post_id = %d ORDER BY menu_order ASC", array( $field, $main_post_id ) ) );

			$attachments = array();
			if ( ! empty( $results ) ) {
				$wp_upload_dir = wp_upload_dir();

				foreach ( $results as $key => $row ) {
					$data = (array)$row;
					unset( $data['ID'] );
					$data['post_id'] = $post_id;

					if ( $wpdb->insert( GEODIR_ATTACHMENT_TABLE, $data ) ) {
						$attachment = array();
						$attachment[] = $wp_upload_dir['baseurl'] . $data['file'];
						$attachment[] = $wpdb->insert_id;
						$attachment[] = $data['title'];
						$attachment[] = $data['caption'];
						if ( empty( $data['is_approved'] ) ) {
							$attachment[] = '0';
						}
						if ( $data['menu_order'] == '-1' ) {
							$attachment[] = '-1';
						}

						$attachments[] = implode( '|', $attachment );
					}
				}
			}
			$value = ! empty( $attachments ) ? implode( '::', $attachments ) : '';
		}
		return $value;
	}

	public static function save_franchise_post_categories( $value, $post_id, $main_post_id, $postarr = array() ) {
		$terms = self::merge_post_terms( $post_id, $main_post_id, 'post_category' );

		if ( ! empty( $terms ) ) {
			$value = is_array( $terms ) ? "," . implode( ",", $terms ) . "," : "," . $terms . ",";
		} else {
			$value = '';
		}

		return $value;
	}

	public static function save_franchise_post_tags( $value, $post_id, $main_post_id, $postarr = array() ) {
		$terms = self::merge_post_terms( $post_id, $main_post_id, 'post_tags' );

		if ( ! empty( $terms ) ) {
			$_terms = wp_get_object_terms( $post_id, get_post_type( $main_post_id ) . '_tags', array( 'fields' => 'names' ) );

			if ( ! empty( $_terms ) && ! is_wp_error( $_terms ) ) {
				$_terms = array_map( 'trim', $_terms );
				$_terms = array_filter( array_unique( $_terms ) );
				$value = implode( ",", $_terms );
			} else {
				$value = '';
			}
		} else {
			$value = '';
		}

		return $value;
	}

	public static function save_franchise_featured_image( $value, $post_id, $main_post_id, $postarr = array() ) {
		global $wpdb;

		$field = 'post_images';
		$wpdb->delete( GEODIR_ATTACHMENT_TABLE, array( 'post_id' => $post_id, 'type' => $field ), array( '%d', '%s' ) );

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . GEODIR_ATTACHMENT_TABLE . " WHERE post_id = %d AND type = %s ORDER BY featured DESC, menu_order ASC, ID ASC", array( $main_post_id, $field ) ) );

		$value = '';
		if ( ! empty( $results ) ) {
			foreach ( $results as $key => $row ) {
				$data = (array) $row;
				$data['post_id'] = $post_id;
				$data['menu_order'] = $key;
				unset( $data['ID'] );

				if ( $key == 0 ) {
					$file_url = get_the_post_thumbnail_url( $main_post_id, 'full' );

					// Prevent franchise post duplicate thumbnail.
					$featured_attachment = get_post_meta( $post_id, '_geodir_franchise_attachment', true );

					if ( ! empty( $featured_attachment ) && is_array( $featured_attachment ) && isset( $featured_attachment['parent_file'] ) && ( $featured_attachment['parent_file'] == geodir_file_relative_url( $file_url ) ) ) {
						$data['file'] = $featured_attachment['file'];
						$data['metadata'] = $featured_attachment['metadata'];
						$value = $featured_attachment['file'];
					} else {
						$attachment = GeoDir_Media::insert_attachment( $post_id, $field, $file_url, $data['title'], $data['caption'], $data['menu_order'], 1 );

						if ( ! is_wp_error( $attachment ) && ! empty( $attachment['file'] ) ) {
							$featured_attachment = array(
								'file' => $attachment['file'],
								'metadata' => $attachment['metadata'],
								'parent_file' => geodir_file_relative_url( $file_url )
							);
							update_post_meta( $post_id, '_geodir_franchise_attachment', $featured_attachment );

							$value = $attachment['file'];

							// Delete existing post thumbnail;
							$attachments =  $wpdb->get_results( $wpdb->prepare( "SELECT ID, guid FROM {$wpdb->posts} WHERE post_type = %s AND post_parent = %d ORDER BY ID ASC", 'attachment', $post_id ) );
							if ( ! empty( $attachments ) ) {
								$post_thumbnail_id = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id = %d ORDER BY meta_id ASC", '_thumbnail_id', $post_id ) );
								foreach ( $attachments as $_attachment ) {
									if ( $post_thumbnail_id && $_attachment->ID == $post_thumbnail_id ) {
										continue;
									}

									if ( $_attachment->guid == $file_url || empty( $post_thumbnail_id ) ) {
										$wpdb->delete( $wpdb->posts, array( 'ID' => $_attachment->ID ) );
									} else {
										wp_delete_attachment( $_attachment->ID, true );
									}
								}
							}

							continue;
						}
					}
				}

				$wpdb->insert( GEODIR_ATTACHMENT_TABLE, $data );
			}
		}

		return $value;
	}

	public static function merge_post_terms( $post_id, $main_post_id, $field ) {
		if ( $field == 'post_category' ) {
			$taxonomy = get_post_type( $main_post_id ) . 'category';
		} else if ( $field == 'post_tags' ) {
			$taxonomy = get_post_type( $main_post_id ) . '_tags';
		} else {
			$taxonomy = $field;
		}

		$terms = wp_get_object_terms( $main_post_id, $taxonomy, array( 'fields' => 'ids' ) );

		if ( ! is_wp_error( $terms ) ) {
			wp_set_object_terms( $post_id, (array) $terms, $taxonomy );
		} else {
			$terms = array();
		}

		return $terms;
	}

	public static function is_main( $post_id ) {
		if ( ! GeoDir_Post_types::supports( get_post_type( $post_id ), 'franchise' ) ) {
			return false;
		}

		$cache_key = 'franchise_is_main:' . $post_id;
		$is_main = geodir_cache_get( $cache_key, 'geodir_franchise' );
		if ( false !== $is_main ) {
			return apply_filters( 'geodir_franchise_is_main_post', $is_main, $post_id );
		}

		$is_main = (int) geodir_get_post_meta( $post_id, 'franchise', true ) > 0 ? 1 : 0;

		geodir_cache_set( $cache_key, $is_main, 'geodir_franchise' );

		return apply_filters( 'geodir_franchise_is_main_post', $is_main, $post_id );
	}

	public static function is_franchise( $post_id ) {
		if ( ! GeoDir_Post_types::supports( get_post_type( $post_id ), 'franchise' ) ) {
			return false;
		}

		if ( geodir_franchise_is_main( $post_id ) ) {
			return false;
		}

		$cache_key = 'franchise_is_franchise:' . $post_id;
		$is_franchise = geodir_cache_get( $cache_key, 'geodir_franchise' );
		if ( false !== $is_franchise ) {
			return apply_filters( 'geodir_franchise_is_franchise_post', $is_franchise, $post_id );
		}

		$main_post_id = (int) geodir_get_post_meta( $post_id, 'franchise_of', true );
	
		$is_franchise = geodir_franchise_is_main( $main_post_id );

		geodir_cache_set( $cache_key, $is_franchise, 'geodir_franchise' );

		return apply_filters( 'geodir_franchise_is_franchise_post', $is_franchise, $post_id );
	}

	public static function get_main_post_id( $post_id ) {
		if ( ! GeoDir_Post_types::supports( get_post_type( $post_id ), 'franchise' ) ) {
			return NULL;
		}

		$cache_key = 'franchise_main_post_id:' . $post_id;
		$main_post_id = geodir_cache_get( $cache_key, 'geodir_franchise' );
		if ( false !== $main_post_id ) {
			return apply_filters( 'geodir_franchise_main_post_id', $main_post_id, $post_id );
		}

		if ( geodir_franchise_is_main( $post_id ) ) {
			$main_post_id = $post_id;
		} else if ( geodir_franchise_is_franchise( $post_id ) ) {
			$main_post_id = (int) geodir_get_post_meta( $post_id, 'franchise_of', true );
		} else {
			geodir_cache_set( $cache_key, NULL, 'geodir_franchise' );

			return NULL;
		}

		geodir_cache_set( $cache_key, $main_post_id, 'geodir_franchise' );

		return apply_filters( 'geodir_franchise_main_post_id', $main_post_id, $post_id );
	}

	public static function can_add_franchise( $post_id ) {
		if ( ! GeoDir_Post_types::supports( get_post_type( $post_id ), 'franchise' ) ) {
			return false;
		}

		$cache_key = 'franchise_can_add_franchise:' . $post_id;
		$allow = geodir_cache_get( $cache_key, 'geodir_franchise' );
		if ( false !== $allow ) {
			return apply_filters( 'geodir_franchise_can_add_franchise', $allow, $post_id );
		}

		if ( geodir_franchise_is_main( $post_id ) || geodir_franchise_is_franchise( $post_id ) ) {
			$allow = true;
		} else {
			$allow = NULL;
		}

		geodir_cache_set( $cache_key, $allow, 'geodir_franchise' );

		return apply_filters( 'geodir_franchise_can_add_franchise', $allow, $post_id );
	}

	public static function get_locked_fields( $post_id, $context = 'db' ) {
		if ( ! geodir_franchise_is_main( $post_id ) ) {
			return $context == 'array' ? array() : '';
		}

		$cache_key = 'franchise_locked_fields:' . $post_id . ':' . $context;
		$fields = geodir_cache_get( $cache_key, 'geodir_franchise' );
		if ( false !== $fields ) {
			return apply_filters( 'geodir_franchise_post_locked_fields', $fields, $post_id, $context );
		}

		// Use fields from request data when main listing is being saved.
		if ( ! empty( $_POST['post_ID'] ) && $_POST['post_ID'] == $post_id && ! empty( $_POST['franchise'] ) && isset( $_POST['franchise_fields'] ) ) {
			$franchise_fields = is_array( $_POST['franchise_fields'] ) ? implode( ",", $_POST['franchise_fields'] ) : $_POST['franchise_fields'];
			$franchise_fields = sanitize_text_field( $franchise_fields );
		} else {
			$franchise_fields = geodir_get_post_meta( $post_id, 'franchise_fields', true );
		}

		if ( ! empty( $franchise_fields ) ) {
			$fields = explode( ',', $franchise_fields );

			if ( $context == 'db' ) {
				if ( in_array( 'post_category', $fields ) ) {
					$fields = array_merge( $fields, array( 'default_category' ) );
				}
				if ( in_array( 'post_images', $fields ) ) {
					$fields = array_merge( $fields, array( 'featured_image' ) );
				}
				if ( in_array( 'event_dates', $fields ) ) {
					$fields = array_merge( $fields, array( 'recurring' ) );
				}

				if ( in_array( 'address', $fields ) ) {
					$fields = array_merge( $fields, array( 'street', 'city', 'region', 'country', 'neighbourhood', 'zip', 'latitude', 'longitude', 'mapview', 'mapzoom' ) );
				}
			}
		} else {
			$fields = array();
		}

		geodir_cache_set( $cache_key, $fields, 'geodir_franchise' );

		return apply_filters( 'geodir_franchise_post_locked_fields', $fields, $post_id, $context );
	}
	
	/*
	 * Unlink main listing franchise
	 */
	public static function unlink_franchise( $post_id ) {
		if ( ! geodir_franchise_is_main( $post_id ) ) {
			return false;
		}

		if ( apply_filters( 'geodir_franchise_post_unlink_franchise', true, $post_id ) !== true ) {
			return false;
		}

		do_action( 'geodir_franchise_post_pre_unlink_franchise', $post_id );

		do_action( 'geodir_franchise_post_franchise_unlinked', $post_id );

		return true;
	}

	public static function merge_post_franchises( $post_id, $fields = NULL, $franchises = NULL, $gd_main_post = array() ) {
		if ( ! geodir_franchise_is_main( $post_id ) ) {
			return false;
		}

		if ( $fields === NULL ) {
			$fields = geodir_franchise_post_locked_fields( $post_id );
		}
		if ( ! is_array( $fields ) || empty( $fields ) ) {
			return false;
		}

		if ( $franchises === NULL ) {
			$franchises = geodir_franchise_post_franchises( $post_id );
		}
		if ( ! is_array( $franchises ) || empty( $franchises ) ) {
			return false;
		}

		if ( apply_filters( 'geodir_franchise_check_merge_post_franchises', true, $post_id, $fields, $franchises, $gd_main_post ) !== true ) {
			return false;
		}

		do_action( 'geodir_franchise_pre_merge_post_franchises', $post_id, $fields, $franchises, $gd_main_post );

		$merged = 0;
		foreach ( $franchises as $key => $franchise_id ) {
			if ( ! empty( $franchise_id ) ) {
				if ( self::merge_post_franchise( $franchise_id, $post_id, $fields, $gd_main_post ) ) {
					$merged++;
				}
			}
		}
		
		do_action( 'geodir_franchise_post_franchises_merged', $post_id, $fields, $franchises, $gd_main_post );

		return $merged;
	}

	public static function merge_post_franchise( $franchise_id, $post_id = 0, $fields = NULL, $gd_main_post = array() ) {
		global $franchise_merge_main_id;

		if ( ! geodir_franchise_is_franchise( $franchise_id ) ) {
			return false;
		}

		if ( empty( $post_id ) ) {
			$post_id = geodir_franchise_main_post_id( $franchise_id );
		}
		if ( empty( $post_id ) ) {
			return false;
		}

		if ( $fields === NULL ) {
			$fields = geodir_franchise_post_locked_fields( $post_id );
		}
		if ( ! is_array( $fields ) || empty( $fields ) ) {
			return false;
		}

		if ( apply_filters( 'geodir_franchise_check_merge_post_franchise', true, $franchise_id, $post_id, $fields ) !== true ) {
			return false;
		}

		do_action( 'geodir_franchise_pre_merge_post_franchise', $franchise_id, $post_id, $fields );

		$main_post = (array) geodir_get_post_info( $post_id );
		$main_current_fields = ! empty( $gd_main_post['ID'] ) && $gd_main_post['ID'] == $post_id ? array_keys( (array) $gd_main_post ) : array();
		$main_fields = array_keys( $main_post );

		$data = array();
		$data['ID'] = $franchise_id;
		foreach ( $fields as $field ) {
			if ( in_array( $field, $main_current_fields ) && $field != 'post_category' && $field != 'post_tags' && $field != 'post_images' ) {
				$data[ $field ] = $gd_main_post[ $field ];
			} else if ( in_array( $field, $main_fields ) ) {
				$data[ $field ] = $main_post[ $field ];
			}
		}

		// package_id & expire_date
		if ( in_array( 'package_id', $main_current_fields ) && in_array( 'expire_date', $main_current_fields ) ) {
			$data[ 'package_id' ] = $main_post[ 'package_id' ];
			$data[ 'expire_date' ] = $main_post[ 'expire_date' ];
		}

		$data = apply_filters( 'geodir_franchise_merge_post_franchise_data', $data, $franchise_id, $post_id, $fields, $gd_main_post );

		$franchise_merge_main_id = $post_id;

		add_action( 'save_post', array( 'GeoDir_Post_Data', 'save_post' ), 10, 3 );

		$return = wp_update_post( $data, true );

		remove_action( 'save_post', array( 'GeoDir_Post_Data', 'save_post' ), 10, 3 );

		$franchise_merge_main_id = NULL;

		if ( $return && ! is_wp_error( $return ) ) {
			do_action( 'geodir_franchise_post_franchise_merged', $franchise_id, $post_id, $fields, $gd_main_post );
			return true;
		}

		return false;
	}

	public static function get_post_franchises( $post_id, $args = array() ) {
		global $wpdb;

		$franchises = array();
		if ( geodir_franchise_is_main( $post_id ) ) {
			$post_type = get_post_type( $post_id );
			$table = geodir_db_cpt_table( $post_type );

			$args = wp_parse_args( 
				(array)$args,
				array(
					'post_status' => '',
					'post_number' => 0,
					'owner'       => true,
					'order_by' 	  => 'p.ID ASC',
				)
			);

			$where = array();
			$where[] = $wpdb->prepare( "p.post_type = %s", array( $post_type ) );
			$where[] = $wpdb->prepare( "( pd.franchise IS NULL OR pd.franchise = 0 ) AND pd.franchise_of = %d", array( $post_id ) );

			if ( empty( $args['post_status'] ) ) {
				$post_statuses = geodir_get_post_statuses();
				if ( isset( $post_statuses['trash'] ) ) {
					unset( $post_statuses['trash'] );
				}
				$args['post_status'] = array_keys( $post_statuses );
			}

			if ( is_array( $args['post_status'] ) ) {
				$post_status = implode( "','", $args['post_status'] );
			} else {
				$post_status = $args['post_status'];
			}

			$where[] = "p.post_status IN('{$post_status}')";

			$where = ! empty( $where ) ? "WHERE " . implode( " AND ", $where ) : '';
			$where = apply_filters( 'geodir_franchise_post_franchises_query_where_clause', $where, $post_id, $args );

			$limit = absint( $args['post_number'] ) > 0 ? " LIMIT " . absint( $args['post_number'] ) : '';
			$limit = apply_filters( 'geodir_franchise_post_franchises_query_limit_clause', $limit, $post_id, $args );

			$order_by = ! empty( $args['order_by'] ) ? "ORDER BY " . $args['order_by'] : '';
			$order_by = apply_filters( 'geodir_franchise_post_franchises_query_order_by_clause', $order_by, $post_id, $args );

			$sql = "SELECT p.ID FROM {$wpdb->posts} AS p LEFT JOIN {$table} AS pd ON pd.post_id = p.ID {$where} {$order_by} {$limit}";

			$results = $wpdb->get_results( $sql );
			if ( ! empty( $results ) ) {
				foreach ( $results as $row ) {
					if ( ! empty( $args['owner'] ) && ! geodir_listing_belong_to_current_user( $row->ID ) ) { // Not allowed to manage franchise
						continue;
					}
					$franchises[] = $row->ID;
				}
			}

			$franchises = apply_filters( 'geodir_franchise_post_franchises', $franchises, $post_id, $args );
		}
		return $franchises;
	}

	public static function get_file_fields( $post_type ) {
		$cache_key = 'geodir_franchise_file_fields:' . $post_type;
		$fields = geodir_cache_get( $cache_key, 'geodir_franchise' );

		if ( false !== $fields ) {
			return $fields;
		}

		$_fields = GeoDir_Media::get_file_fields( $post_type );
		$fields = ! empty( $_fields ) ? array_keys( $_fields ) : array();

		geodir_cache_set( $cache_key, $fields, 'geodir_franchise' );

		return $fields;
	}

	public static function pre_delete_attachment_file( $delete, $id, $post_id, $attachment ) {
		if ( $delete === null && self::is_franchise( $post_id ) && ! empty( $attachment->type ) && ( $main_post_id = self::get_main_post_id( $post_id ) ) ) {
			$fields = self::get_locked_fields( $main_post_id, 'array');

			if ( ! empty( $fields ) && in_array( $attachment->type, $fields ) ) {
				$delete = false; // Don't delete franchise attachments.
			}
		}

		return $delete;
	}

	public static function save_post_temp_data( $gd_post, $post, $update ) {
		if ( ! ( isset( $gd_post['post_type'] ) && geodir_is_gd_post_type( $gd_post['post_type'] ) ) ) {
			return $gd_post;
		}

		if ( self::is_franchise( $post->ID ) && ( $main_post_id = self::get_main_post_id( $post->ID ) ) ) {
			$locked_fields = geodir_franchise_post_locked_fields( $main_post_id );

			if ( ! empty( $locked_fields ) && in_array( 'post_category', $locked_fields ) ) {
				$terms = wp_get_object_terms( $main_post_id, $gd_post['post_type'] . 'category', array( 'fields' => 'ids' ) );

				if ( ! empty( $terms ) ) {
					$gd_post['post_category'] = $terms;
				}
			}

			if ( empty( $gd_post['tax_input'] ) && empty( $gd_post['post_category'] ) && empty( $gd_post['tags_input'] ) ) {
				if ( isset( $gd_post['post_category'] ) ) {
					unset( $gd_post['post_category'] );
				}
				if ( isset( $gd_post['tags_input'] ) ) {
					unset( $gd_post['tags_input'] );
				}
			}
		}

		return $gd_post;
	}

	public static function author_actions( $author_actions, $post_id ) {
		if ( ! empty( $post_id ) && is_user_logged_in() && ( $url = geodir_franchise_add_franchise_link( $post_id ) ) ) {
			$author_actions['add_franchise'] = array(
				'title' => esc_attr( geodir_franchise_label( 'add_new_item', get_post_type( $post_id ) ) ),
				'icon' => 'fas fa-plus-circle',
				'url' => $url
			);
		}

		return $author_actions;
	}

	public static function gd_listings_view_all_url( $viewall_url, $query_args, $instance, $args, $widget ) {
		global $gd_post;

		if ( ! empty( $query_args['franchise_of'] ) && ! empty( $query_args['post_type'] ) && GeoDir_Post_types::supports( $query_args['post_type'], 'franchise' ) ) {
			$main_listing_id = 0;

			if ( $query_args['franchise_of'] == 'auto' ) {
				if ( ! empty( $gd_post->ID ) ) {
					if ( geodir_franchise_is_main( (int) $gd_post->ID ) ) {
						$main_listing_id = (int) $gd_post->ID;
					} else if ( geodir_franchise_is_franchise( (int) $gd_post->ID ) ) {
						$franchise_id = (int) $gd_post->ID;
						$main_listing_id = geodir_franchise_main_post_id( (int) $franchise_id );
					}
				}
			} else {
				if ( geodir_franchise_is_main( (int) $query_args['franchise_of'] ) ) {
					$main_listing_id = (int) $query_args['franchise_of'];
				}
			}

			if ( $main_listing_id > 0 ) {
				$viewall_url = add_query_arg( array( 'franchise_of' => $main_listing_id ), $viewall_url );
			}
		}
		return $viewall_url;
	}

	public static function on_claim_approved( $claim ) {
		global $wpdb;

		if ( ! empty( $claim ) && ! empty( $claim->post_id ) && ! empty( $claim->user_id ) ) {
			$franchises = self::get_post_franchises( (int) $claim->post_id, array( 'owner'=> false ) );

			if ( ! empty( $franchises ) ) {
				$table = geodir_db_cpt_table( $claim->post_type );

				$wpdb->query( $wpdb->prepare( "UPDATE `{$wpdb->posts}` SET `post_author` = %d WHERE ID IN( " . implode( ', ', $franchises ) . " )", array( $claim->user_id ) ) );
				$wpdb->query( $wpdb->prepare( "UPDATE `{$table}` SET `claimed` = %d WHERE post_id IN( " . implode( ', ', $franchises ) . " )", array( 1 ) ) );
			}
		}
	}

	public static function on_claim_undone( $claim ) {
		global $wpdb;

		if ( ! empty( $claim ) && ! empty( $claim->post_id ) && ! empty( $claim->user_id ) ) {
			$franchises = self::get_post_franchises( (int) $claim->post_id, array( 'owner'=> false ) );

			if ( ! empty( $franchises ) ) {
				$table = geodir_db_cpt_table( $claim->post_type );

				$wpdb->query( $wpdb->prepare( "UPDATE `{$wpdb->posts}` SET `post_author` = %d WHERE ID IN( " . implode( ', ', $franchises ) . " )", array( $claim->author_id ) ) );
				$wpdb->query( $wpdb->prepare( "UPDATE `{$table}` SET `claimed` = %d WHERE post_id IN(" . implode( ', ', $franchises ) . " )", array( 0 ) ) );
			}
		}
	}

	public static function before_single_tab_content( $tab, $parent_tab = array() ) {
		if ( ! empty( $tab ) && $tab->tab_key == 'reviews' ) {
			self::switch_to_franchise();
		}
	}

	public static function after_single_tab_content( $tab, $parent_tab = array() ) {
		if ( ! empty( $tab ) && $tab->tab_key == 'reviews' ) {
			self::switch_to_main();
		}
	}

	public static function before_post_rating_widget_content() {
		self::switch_to_franchise();
	}

	public static function after_post_rating_widget_content() {
		self::switch_to_main();
	}

	public static function before_single_reviews_widget_content() {
		self::switch_to_franchise();
	}

	public static function after_single_reviews_widget_content() {
		self::switch_to_main();
	}

	public static function set_post_meta_id( $post_id, $args ) {
		global $geodir_switch_post_id;

		if ( ! empty( $post_id ) && ! empty( $args['key'] ) && ( $args['key'] == 'overall_rating' || $args['key'] == 'rating_count' ) ) {
			if ( $main_post_id = self::has_locked_comments( $post_id ) ) {
				$geodir_switch_post_id = $post_id;
				$post_id = $main_post_id;
			}
		}

		return $post_id;
	}

	public static function reset_post_meta_id( $post_id, $args ) {
		global $geodir_switch_post_id;

		if ( ! empty( $geodir_switch_post_id ) && ! empty( $args['key'] ) && ( $args['key'] == 'overall_rating' || $args['key'] == 'rating_count' ) ) {
			$post_id = $geodir_switch_post_id;
			$geodir_switch_post_id = 0;
		}

		return $post_id;
	}

	public static function switch_to_franchise() {
		global $post, $gd_post, $geodir_switch_post, $geodir_switch_gd_post;

		if ( ! empty( $gd_post ) && ( $main_post_id = self::has_locked_comments( $gd_post->ID ) ) ) {
			$geodir_switch_post = $post;
			$geodir_switch_gd_post = $gd_post;

			$post = get_post( $main_post_id );
			$gd_post = geodir_get_post_info( $main_post_id );
		}
	}

	public static function switch_to_main() {
		global $post, $gd_post, $geodir_switch_post, $geodir_switch_gd_post;

		if ( ! empty( $geodir_switch_gd_post ) ) {
			$post = $geodir_switch_post;
			$gd_post = $geodir_switch_gd_post;

			$geodir_switch_post = NULL;
			$geodir_switch_gd_post = NULL;
		}
	}

	public static function has_locked_comments( $post_id ) {
		$main_post_id = self::get_main_post_id( (int) $post_id );

		if ( $main_post_id && $main_post_id != $post_id && ( $locked_fields = self::get_locked_fields( (int) $main_post_id ) ) ) {
			if ( in_array( 'comments', $locked_fields ) ) {
				return $main_post_id;
			}
		}

		return false;
	}

	public static function comment_form() {
		global $geodir_switch_gd_post;

		if ( geodir_is_page( 'single' ) && ! empty( $geodir_switch_gd_post ) ) {
		?><input type="hidden" name="_geodir_switch_to_post" value="<?php echo $geodir_switch_gd_post->ID; ?>" /><?php
		}
	}

	public static function comment_post_redirect( $location, $comment ) {
		if ( ! empty( $_POST['_geodir_switch_to_post'] ) ) {
			$location = str_replace( get_permalink( $comment->comment_post_ID ), get_permalink( (int) $_POST['_geodir_switch_to_post'] ), $location );
		}
		return $location;
	}

	public static function get_comments_link( $comments_link, $post_id ) {
		global $geodir_switch_gd_post;

		if ( ! empty( $geodir_switch_gd_post ) ) {
			$comments_link = str_replace( get_permalink( $post_id ), get_permalink( (int) $geodir_switch_gd_post->ID), $comments_link );
		}
		return $comments_link;
	}

	/**
	 * Fires on post cache is cleaned.
	 *
	 * @since 2.3.2
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function clean_post_cache( $post_ID, $post ) {
		global $_wp_suspend_cache_invalidation;

		if ( ! empty( $_wp_suspend_cache_invalidation ) ) {
			return;
		}

		geodir_cache_delete( 'franchise_is_franchise:' . $post_ID, 'geodir_franchise' );
		geodir_cache_delete( 'franchise_is_main:' . $post_ID, 'geodir_franchise' );
		geodir_cache_delete( 'franchise_can_add_franchise:' . $post_ID, 'geodir_franchise' );
		geodir_cache_delete( 'franchise_locked_fields:' . $post_ID . ':', 'geodir_franchise' );
		geodir_cache_delete( 'franchise_locked_fields:' . $post_ID . ':array', 'geodir_franchise' );
		geodir_cache_delete( 'franchise_locked_fields:' . $post_ID . ':db', 'geodir_franchise' );
		geodir_cache_delete( 'franchise_main_post_id:' . $post_ID, 'geodir_franchise' );
	}

	public static function clean_post_type_cache( $post_type ) {
		global $_wp_suspend_cache_invalidation;

		if ( ! empty( $_wp_suspend_cache_invalidation ) ) {
			return;
		}

		geodir_cache_flush_group( 'geodir_franchise' );
	}

	public static function on_clean_package_cache( $package_id, $package = array() ) {
		if ( ! empty( $package ) && ! empty( $package->post_type ) ) {
			self::clean_post_type_cache( $package->post_type );
		}
	}

	public static function on_post_type_saved( $post_type, $args = array(), $new = false ) {
		self::clean_post_type_cache( $post_type );
	}

	public static function on_save_custom_field( $field_id, $field = array() ) {
		if ( ! empty( $field ) && ! empty( $field->post_type ) ) {
			self::clean_post_type_cache( $field->post_type );
		}
	}
}