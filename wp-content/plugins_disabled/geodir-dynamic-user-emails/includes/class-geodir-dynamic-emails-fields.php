<?php
/**
 * Dynamic User Emails Fields class
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Dynamic_Emails
 * @version   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Dynamic_Emails_Fields class.
 */
class GeoDir_Dynamic_Emails_Fields {
	/**
	 * Init.
	 *
	 * @since 2.0.0
	 */
	public static function init() {
		add_filter( 'geodir_get_field_key_options', array( __CLASS__, 'filter_field_key_options' ), 51, 2 );
		add_filter( 'geodir_dynamic_emails_query_field_where', array( __CLASS__, 'filter_query_field_where' ), 10, 4 );
		add_filter( 'geodir_dynamic_emails_field_rule_search', array( __CLASS__, 'field_rule_search' ), 10, 3 );
	}

	public static function filter_field_key_options( $options, $args ) {
		if ( ! empty( $args['context'] ) && $args['context'] == 'dynamic-email-filter' ) {
			foreach ( array( 'address', 'post_link', 'map_directions', 'post_images' ) as $unset ) {
				if ( isset( $options[ $unset ] ) ) {
					unset( $options[ $unset ] );
				}
			}

			foreach ( $options as $key => $title ) {
				if ( strpos( $key , 'business_hours_' ) === 0 ) {
					unset( $options[ $key ] );
				}
			}
		}

		return $options;
	}

	public static function parse_meta( $value ) {
		$meta = array();

		if ( empty( $value ) ) {
			return $meta;
		}

		if ( is_scalar( $value ) ) {
			$meta = json_decode( $value, true );
		}

		return $meta;
	}

	public static function parse_query_args( $query_args ) {
		$args = array();

		foreach ( $query_args as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = array_filter( $value );

				if ( ! empty( $value ) ) {
					if ( isset( $value['from'] ) || isset( $value['to'] ) ) {
						$value_from = isset( $value['from'] ) ? ( is_array( $value['from'] ) ? ',' . implode( ",", $value['from'] ) . ',' : trim( $value['from'] ) ) : '';
						$value_to = isset( $value['to'] ) ? ( is_array( $value['to'] ) ? ',' . implode( ",", $value['to'] ) . ',' : trim( $value['to'] ) ) : '';

						$args[ $key ] = $value_from . ' to ' . $value_to;
					} else {
						$args[ $key ] = ',' . implode( ",", $value ) . ',';
					}
				}
			} else if ( is_scalar( $value ) ) {
				$value = trim( $value );

				if ( $value !== '' && $value !== false && $value !== null && $value !== 0 ) {
					$args[ $key ] = $value;
				}
			}
		}

		$parse_args = $args;

		$skip_args = array( 'geodir_search', 'stype', 'paged' );

		foreach ( $skip_args as $skip_arg ) {
			if ( isset( $args[ $skip_arg ] ) ) {
				unset( $args[ $skip_arg ] );
			}
		}

		return apply_filters( 'geodir_dynamic_emails_parse_query_args', $args, $parse_args, $query_args );
	}

	public static function get_fields_where( $post_type, $fields ) {
		global $wpdb;

		$where_parts = array();

		$columns = self::get_table_columns( $post_type );
		$wp_columns = ! empty( $columns['wp'] ) ? $columns['wp'] : array();
		$gd_columns = ! empty( $columns[ $post_type ] ) ? $columns[ $post_type ] : array();

		foreach ( $fields as $key => $rule ) {
			if ( empty( $rule['field'] ) || empty( $rule['condition'] ) ) {
				continue;
			}

			if ( ! isset( $rule['search'] ) ) {
				$rule['search'] = '';
			}

			if ( in_array( $rule['field'], $gd_columns ) ) {
				$rule['alias'] = 'pd';
			} else if ( in_array( $rule['field'], $wp_columns ) ) {
				$rule['alias'] = $wpdb->posts;
			} else {
				$rule['alias'] = '';
			}

			if ( ! empty( $rule['alias'] ) ) {
				$field_where = self::get_field_where( $post_type, $rule );
			} else {
				$field_where = self::get_custom_field_where( $post_type, $rule );
			}

			if ( ! empty( $field_where ) ) {
				$where_parts[] = $field_where;
			}
		}

		$fields_where = ! empty( $where_parts ) ? implode( " AND ", $where_parts ) : '';

		return apply_filters( 'geodir_dynamic_emails_query_fields_where', $fields_where, $where_parts, $post_type, $fields );
	}

	/**
	 * Check if the string is a timestamp or not.
	 *
	 * @param $string
	 *
	 * @return bool
	 */
	public static function is_timestamp( $string ) {
		if (is_numeric($string) && $string >= 0 && floor($string) == $string && strlen($string) >= 10 && strlen($string) <= 11) {
			return true;
		}

		return false;
	}

	public static function get_field_where( $post_type, $rule ) {
		global $wpdb;

		$field = $rule['field'];
		$condition = $rule['condition'];
		$search = $rule['search'];

		$search = apply_filters( 'geodir_dynamic_emails_field_rule_search', $search, $post_type, $rule );

		// maybe check if a date compare
		if ( in_array( $condition, array(
			'is_greater_than',
			'is_less_than',
		) ) ) {
			$maybe_timestamp = @strtotime( $search );
			if ( self::is_timestamp( $maybe_timestamp ) ) {
				$dateTime = new DateTime(); // Current date and time
				$dateTime->setTimestamp( absint( $maybe_timestamp ) );
				$search = $dateTime->format('Y-m-d'); // Format as YYYY-MM-DD
			}
		}

		if ( ! empty( $rule['column'] ) ) {
			$column = $rule['column'];
		} else {
			$column = $rule['alias'] ? "`" . $rule['alias'] . "`.`{$field}`" : "`{$field}`";
		}

		$field_where = '';

		switch ( $condition ) {
			case 'is_equal':
				if ( is_int( $search ) ) {
					$field_where = $wpdb->prepare( "{$column} = %d", array( $search ) );
				} else if ( is_float( $search ) ) {
					$field_where = $wpdb->prepare( "{$column} = %f", array( $search ) );
				} else {
					$field_where = $wpdb->prepare( "{$column} LIKE %s", array( $search ) );
				}
				break;
			case 'is_not_equal':
				if ( is_int( $search ) ) {
					$field_where = $wpdb->prepare( "{$column} != %d", array( $search ) );
				} else if ( is_float( $search ) ) {
					$field_where = $wpdb->prepare( "{$column} != %f", array( $search ) );
				} else {
					$field_where = $wpdb->prepare( "{$column} NOT LIKE %s", array( $search ) );
				}
				break;
			case 'is_greater_than':
				$field_where = $wpdb->prepare( "{$column} > %s", array( $search ) );
				break;
			case 'is_less_than':
				$field_where = $wpdb->prepare( "{$column} < %s", array( $search ) );
				break;
			case 'is_empty':
				$field_where = "( {$column} IS NULL OR {$column} = '' OR {$column} = '0' )";
				break;
			case 'is_not_empty':
				$field_where = "( {$column} IS NOT NULL AND {$column} != '' AND {$column} != '0' )";
				break;
			case 'is_contains':
				$field_where = "{$column} LIKE '%" . esc_sql( $wpdb->esc_like( geodir_clean( $search ) ) ) . "%'";
				break;
			case 'is_not_contains':
				$field_where = "{$column} NOT LIKE '%" . esc_sql( $wpdb->esc_like( geodir_clean( $search ) ) ) . "%'";
				break;
		}

		return apply_filters( 'geodir_dynamic_emails_query_field_where', $field_where, $post_type, $rule );
	}

	public static function get_custom_field_where( $post_type, $rule ) {
		$field_where = '';

		if ( GeoDir_Post_types::supports( $post_type, 'events' ) && ! empty( $rule['field'] ) && ( strpos( $rule['field'], 'event_start_' ) == 0 || strpos( $rule['field'], 'event_end_' ) == 0 ) ) {
			$column = '';

			if ( $rule['field'] == 'event_start_date' ) {
				$column = "`gdes`.`start_date`";
			} else if ( $rule['field'] == 'event_start_time' ) {
				$column = "`gdes`.`start_time`";
			} else if ( $rule['field'] == 'event_start_date_time' ) {
				$column = "CONCAT( `gdes`.`start_date`, ' ', `gdes`.`start_time` )";
			} else if ( $rule['field'] == 'event_end_date' ) {
				$column = "`gdes`.`end_date`";
			} else if ( $rule['field'] == 'event_end_time' ) {
				$column = "`gdes`.`end_time`";
			} else if ( $rule['field'] == 'event_end_date_time' ) {
				$column = "CONCAT( `gdes`.`end_date`, ' ', `gdes`.`end_time` )";
			}

			if ( $column ) {
				$rule['alias'] = '';
				$rule['column'] = $column;

				$field_where = self::get_field_where( $post_type, $rule );
			}
		}

		return apply_filters( 'geodir_dynamic_emails_query_custom_field_where', $field_where, $post_type, $rule );
	}

	public static function get_table_columns( $post_type = '' ) {
		global $wpdb, $geodir_table_columns;

		if ( empty( $geodir_table_columns ) ) {
			$geodir_table_columns = array();
		}

		if ( empty( $geodir_table_columns[ 'wp' ] ) ) {
			$row = $wpdb->get_row( "SELECT * FROM `" . $wpdb->posts . "` LIMIT 1" );

			if ( ! empty( $row ) ) {
				$geodir_table_columns['wp'] = array_keys( (array) $row );
			}
		}

		if ( ! empty( $post_type ) ) {
			if ( empty( $geodir_table_columns[ $post_type ] ) ) {
				$row = $wpdb->get_row( "SELECT * FROM `" . geodir_db_cpt_table( $post_type ) . "` LIMIT 1" );

				if ( ! empty( $row ) ) {
					$geodir_table_columns[ $post_type ] = array_keys( (array) $row );
				}
			}
		}

		return $geodir_table_columns;
	}

	public static function filter_query_field_where( $field_where, $post_type, $rule ) {
		if ( $field_where != '' && $rule['field'] == 'post_title' ) {
			$field_where = "( {$field_where} OR " . str_replace( "`post_title`", "`_search_title`", $field_where ) . " )";
		}

		return $field_where;
	}

	public static function has_event_filter( $post_type, $fields ) {
		$check = false;

		if ( GeoDir_Post_types::supports( $post_type, 'events' ) ) {
			foreach ( $fields as $key => $rule ) {
				if ( ! empty( $rule['field'] ) && ( strpos( $rule['field'], 'event_start_' ) == 0 || strpos( $rule['field'], 'event_end_' ) == 0 ) ) {
					$check = true;
					break;
				}
			}
		}

		return apply_filters( 'geodir_dynamic_emails_fields_has_event_filter', $check, $post_type, $fields );
	}

	public static function field_rule_search( $search, $post_type, $rule ) {
		if ( ! $search ) {
			return $search;
		}

		$_search = strtolower( $search );

		if ( $_search == 'date_today' ) {
			$search = date( 'Y-m-d' );
		} else if ( $_search == 'date_tomorrow' ) {
			$search = date( 'Y-m-d', strtotime( "+1 day" ) );
		} else if ( $_search == 'date_yesterday' ) {
			$search = date( 'Y-m-d', strtotime( "-1 day" ) );
		} else if ( $_search == 'time_his' ) {
			$search = date( 'H:i:s' );
		} else if ( $_search == 'time_hi' ) {
			$search = date( 'H:i' );
		} else if ( $_search == 'datetime_now' ) {
			$search = date( 'Y-m-d H:i:s' );
		} else if ( strpos( $_search, 'datetime_after_' ) === 0 ) {
			$_searches = explode( 'datetime_after_', $_search, 2 );

			if ( ! empty( $_searches[1] ) ) {
				$search = date( 'Y-m-d H:i:s', strtotime( "+ " . str_replace( "_", " ", $_searches[1] ) ) );
			} else {
				$search = date( 'Y-m-d H:i:s' );
			}
		} else if ( strpos( $_search, 'datetime_before_' ) === 0 ) {
			$_searches = explode( 'datetime_before_', $_search, 2 );

			if ( ! empty( $_searches[1] ) ) {
				$search = date( 'Y-m-d H:i:s', strtotime( "- " . str_replace( "_", " ", $_searches[1] ) ) );
			} else {
				$search = date( 'Y-m-d H:i:s' );
			}
		} else if ( strpos( $_search, 'date_after_' ) === 0 ) {
			$_searches = explode( 'date_after_', $_search, 2 );

			if ( ! empty( $_searches[1] ) ) {
				$search = date( 'Y-m-d', strtotime( "+ " . str_replace( "_", " ", $_searches[1] ) ) );
			} else {
				$search = date( 'Y-m-d' );
			}
		} else if ( strpos( $_search, 'date_before_' ) === 0 ) {
			$_searches = explode( 'date_before_', $_search, 2 );

			if ( ! empty( $_searches[1] ) ) {
				$search = date( 'Y-m-d', strtotime( "- " . str_replace( "_", " ", $_searches[1] ) ) );
			} else {
				$search = date( 'Y-m-d' );
			}
		}

		return $search;
	}
}