<?php
/**
 * Dynamic User Emails Log Query class
 *
 * @author    AyeCode Ltd
 * @package   GeoDir_Dynamic_Emails
 * @version   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Dynamic_Emails_Log_Query class.
 */
class GeoDir_Dynamic_Emails_Log_Query {
	public $query_vars = array();

	private $results;

	private $total_items = 0;

	/**
	 * The SQL query used to fetch matching results.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	public $request;

	private $compat_fields = array( 'results', 'total_items' );

	// SQL clauses.
	public $query_fields;
	public $query_from;
	public $query_join;
	public $query_where;
	public $query_orderby;
	public $query_limit;

	// SQL table.
	private $table;
	private $list_table;
	private $user_table;

	/**
	 * PHP5 constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param null|string|array $query Optional. The query variables.
	 */
	public function __construct( $query = null ) {
		$this->table = GEODIR_DYNAMIC_EMAILS_LOG_TABLE;
		$this->list_table = GEODIR_DYNAMIC_EMAILS_LISTS_TABLE;
		$this->user_table = GEODIR_DYNAMIC_EMAILS_USERS_TABLE;

		if ( ! empty( $query ) ) {
			$this->prepare_query( $query );
			$this->query();
		}
	}

	/**
	 * Fills in missing query variables with default values.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args Query vars.
	 * @return array Complete query variables with undefined ones filled in with defaults.
	 */
	public static function fill_query_vars( $args ) {
		$defaults = array(
			'include'             => array(),
			'exclude'             => array(),
			'search'              => '',
			'search_columns'      => array(),
			'orderby'             => 'email_log_id',
			'order'               => 'ASC',
			'offset'              => '',
			'number'              => '',
			'paged'               => 1,
			'count_total'         => true,
			'fields'              => 'all',
			'who'                 => '',
			'action'              => '',
			'status'              => '',
			'post_type'           => '',
			'post_type__in'       => array(),
			'post_type__not_in'   => array(),
			'category'            => '',
			'category__in'        => array(),
			'category__not_in'    => array(),
			'role'                => '',
			'role__in'            => array(),
			'role__not_in'        => array()
		);

		return wp_parse_args( $args, $defaults );
	}

	/**
	 * Prepares the query variables.
	 *
	 * @since 2.0.0
	 * 
	 * @global wpdb     $wpdb     WordPress database abstraction object.
	 *
	 * @param string|array $query Optional. Array or string of Query parameters.
	 */
	public function prepare_query( $query = array() ) {
		global $wpdb;

		if ( empty( $this->query_vars ) || ! empty( $query ) ) {
			$this->query_limit = null;
			$this->query_vars  = $this->fill_query_vars( $query );
		}

		do_action_ref_array( 'geodir_dynamic_emails_pre_get_email_log', array( &$this ) );

		$qv =& $this->query_vars;
		$qv = $this->fill_query_vars( $qv );

		$allowed_fields = array(
			'email_log_id',
			'date_sent', 
			'email_list_id',
			'action',
			'name',
			'post_type',
			'category',
			'user_roles',
			'subject',
			'template',
			'meta',
			'date_added',
			'status'
		);

		if ( is_array( $qv['fields'] ) ) {
			$qv['fields'] = array_map( 'strtolower', $qv['fields'] );
			$qv['fields'] = array_intersect( array_unique( $qv['fields'] ), $allowed_fields );

			if ( empty( $qv['fields'] ) ) {
				$qv['fields'] = array( 'email_log_id' );
			}

			$this->query_fields = array();
			foreach ( $qv['fields'] as $field ) {
				$field = 'email_log_id' === $field ? 'email_log_id' : sanitize_key( $field );
				$this->query_fields[] = "$this->table.$field";
			}
			$this->query_fields = implode( ',', $this->query_fields );
		} elseif ( 'all' === $qv['fields'] || ! in_array( $qv['fields'], $allowed_fields, true ) ) {
		$this->query_fields = "{$this->table}.*, {$this->list_table}.*";
		} else {
			$field = 'email_log_id' === strtolower( $qv['fields'] ) ? 'email_log_id' : sanitize_key( $qv['fields'] );
			$this->query_fields = "$this->table.$field";
		}

		if ( isset( $qv['count_total'] ) && $qv['count_total'] ) {
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
		}

		$this->query_from  = "FROM $this->table";
		$this->query_join  = "LEFT JOIN {$this->list_table} ON {$this->list_table}.email_list_id = {$this->table}.email_list_id";
		$this->query_where = 'WHERE 1=1';

		// Parse and sanitize 'include', for use by 'orderby' as well as 'include' below.
		if ( ! empty( $qv['include'] ) ) {
			$include = wp_parse_id_list( $qv['include'] );
		} else {
			$include = false;
		}

		// action
		if ( '' !== $qv['action'] ) {
			$this->query_where .= $wpdb->prepare( ' AND action = %s', $qv['action'] );
		}

		// post_type
		if ( '' !== $qv['post_type'] ) {
			$this->query_where .= $wpdb->prepare( ' AND post_type = %s', $qv['post_type'] );
		}

		// status
		if ( '' !== $qv['status'] ) {
			$this->query_where .= $wpdb->prepare( ' AND status = %s', $qv['status'] );
		}

		if ( ! empty( $qv['post_type__in'] ) ) {
			$post_type__in = array( "`post_type` IS NULL OR `post_type` = ''" );
			foreach ( $qv['post_type__in'] as $value ) {
				$post_type__in[] = $wpdb->prepare( "FIND_IN_SET( %s, `post_type` )", array( $value ) );
			}
			$this->query_where .= " AND ( " . implode( " OR ", $post_type__in ) . " )";
		}

		if ( ! empty( $qv['post_type__not_in'] ) ) {
			$post_type__not_in = array();
			foreach ( $qv['post_type__not_in'] as $value ) {
				$post_type__not_in[] = $wpdb->prepare( "NOT FIND_IN_SET( %s, `post_type` )", array( $value ) );
			}
			$this->query_where .= " AND " . implode( " AND ", $post_type__not_in );
		}

		// category
		if ( '' !== $qv['category'] ) {
			$this->query_where .= $wpdb->prepare( ' AND category = %s', $qv['category'] );
		}

		if ( ! empty( $qv['category__in'] ) ) {
			$category__in = array( "`category` IS NULL OR `category` = ''" );
			foreach ( $qv['category__in'] as $value ) {
				$category__in[] = $wpdb->prepare( "FIND_IN_SET( %s, `category` )", array( $value ) );
			}
			$this->query_where .= " AND ( " . implode( " OR ", $category__in ) . " )";
		}

		if ( ! empty( $qv['category__not_in'] ) ) {
			$category__not_in = array();
			foreach ( $qv['category__not_in'] as $value ) {
				$category__not_in[] = $wpdb->prepare( "NOT FIND_IN_SET( %s, `category` )", array( $value ) );
			}
			$this->query_where .= " AND " . implode( " AND ", $category__not_in );
		}

		// Roles.
		if ( $qv['role'] != '' ) {
			$this->query_where .= $wpdb->prepare( ' AND user_roles = %s', $qv['role'] );
		}

		if ( ! empty( $qv['role__in'] ) ) {
			$role__in = array( "`user_roles` IS NULL OR `user_roles` = ''" );
			foreach ( $qv['role__in'] as $value ) {
				$role__in[] = $wpdb->prepare( "FIND_IN_SET( %s, `user_roles` )", array( $value ) );
			}
			$this->query_where .= " AND ( " . implode( " OR ", $role__in ) . " )";
		}

		if ( ! empty( $qv['role__not_in'] ) ) {
			$role__not_in = array();
			foreach ( $qv['role__not_in'] as $value ) {
				$role__not_in[] = $wpdb->prepare( "NOT FIND_IN_SET( %s, `user_roles` )", array( $value ) );
			}
			$this->query_where .= " AND " . implode( " AND ", $role__not_in );
		}

		// Sorting.
		$qv['order'] = isset( $qv['order'] ) ? strtoupper( $qv['order'] ) : '';
		$order       = $this->parse_order( $qv['order'] );

		if ( empty( $qv['orderby'] ) ) {
			// Default order is by 'email_log_id'.
			$ordersby = array( 'email_log_id' => $order );
		} elseif ( is_array( $qv['orderby'] ) ) {
			$ordersby = $qv['orderby'];
		} else {
			// 'orderby' values may be a comma- or space-separated list.
			$ordersby = preg_split( '/[,\s]+/', $qv['orderby'] );
		}

		$orderby_array = array();
		foreach ( $ordersby as $_key => $_value ) {
			if ( ! $_value ) {
				continue;
			}

			if ( is_int( $_key ) ) {
				// Integer key means this is a flat array of 'orderby' fields.
				$_orderby = $_value;
				$_order   = $order;
			} else {
				// Non-integer key means this the key is the field and the value is ASC/DESC.
				$_orderby = $_key;
				$_order   = $_value;
			}

			$parsed = $this->parse_orderby( $_orderby );

			if ( ! $parsed ) {
				continue;
			}

			if ( 'post_type__in' === $_orderby || 'category__in' === $_orderby ) {
				$orderby_array[] = $parsed;
			} else {
				$orderby_array[] = $parsed . ' ' . $this->parse_order( $_order );
			}
		}

		// If no valid clauses were found, order by id.
		if ( empty( $orderby_array ) ) {
			$orderby_array[] = "email_log_id $order";
		}

		$orderby = implode( ', ', $orderby_array );

		$this->query_orderby = 'ORDER BY ' . $orderby;

		// Limit.
		if ( isset( $qv['number'] ) && $qv['number'] > 0 ) {
			if ( $qv['offset'] ) {
				$this->query_limit = $wpdb->prepare( 'LIMIT %d, %d', $qv['offset'], $qv['number'] );
			} else {
				$this->query_limit = $wpdb->prepare( 'LIMIT %d, %d', $qv['number'] * ( $qv['paged'] - 1 ), $qv['number'] );
			}
		}

		$search = '';
		if ( isset( $qv['search'] ) ) {
			$search = trim( $qv['search'] );
		}

		if ( $search ) {
			$leading_wild  = ( ltrim( $search, '*' ) != $search );
			$trailing_wild = ( rtrim( $search, '*' ) != $search );
			if ( $leading_wild && $trailing_wild ) {
				$wild = 'both';
			} elseif ( $leading_wild ) {
				$wild = 'leading';
			} elseif ( $trailing_wild ) {
				$wild = 'trailing';
			} else {
				$wild = false;
			}
			if ( $wild ) {
				$search = trim( $search, '*' );
			}

			$search_columns = array();
			if ( $qv['search_columns'] ) {
				$search_columns = array_intersect( $qv['search_columns'], array( 'email_log_id', 'date_sent', 'action', 'name', 'post_type', 'user_roles' ) );
			}
			if ( ! $search_columns ) {
				$search_columns = array( 'email_log_id', 'date_sent', 'action', 'name', 'post_type', 'user_roles' );
			}

			$search_columns = apply_filters( 'geodir_dynamic_emails_email_list_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}

		if ( ! empty( $include ) ) {
			// Sanitized earlier.
			$ids                = implode( ',', $include );
			$this->query_where .= " AND $this->table.email_log_id IN ($ids)";
		} elseif ( ! empty( $qv['exclude'] ) ) {
			$ids                = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND $this->table.email_log_id NOT IN ($ids)";
		}

		// Date queries are allowed for the date_added field.
		if ( ! empty( $qv['date_query'] ) && is_array( $qv['date_query'] ) ) {
			$date_query         = new WP_Date_Query( $qv['date_query'], 'date_added' );
			$this->query_where .= $date_query->get_sql();
		}

		do_action_ref_array( 'geodir_dynamic_emails_pre_email_lists_query', array( &$this ) );
	}

	/**
	 * Executes the query, with the current variables.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 */
	public function query() {
		global $wpdb;

		$qv =& $this->query_vars;

		$this->results = apply_filters_ref_array( 'geodir_dynamic_emails_email_lists_pre_query', array( null, &$this ) );

		if ( null === $this->results ) {
			$this->request = "SELECT {$this->query_fields} {$this->query_from} {$this->query_join} {$this->query_where} {$this->query_orderby} {$this->query_limit}";

			if ( is_array( $qv['fields'] ) || $qv['fields'] == 'all' ) {
				$this->results = $wpdb->get_results( $this->request );
			} else {
				$this->results = $wpdb->get_col( $this->request );
			}

			if ( isset( $qv['count_total'] ) && $qv['count_total'] ) {
				$found_items_query = apply_filters( 'geodir_dynamic_emails_found_email_lists_query', 'SELECT FOUND_ROWS()', $this );

				$this->total_items = (int) $wpdb->get_var( $found_items_query );
			}
		}

		if ( ! $this->results ) {
			return;
		}
	}

	/**
	 * Retrieves query variable.
	 *
	 * @since 2.0.0
	 *
	 * @param string $query_var Query variable key.
	 * @return mixed
	 */
	public function get( $query_var ) {
		if ( isset( $this->query_vars[ $query_var ] ) ) {
			return $this->query_vars[ $query_var ];
		}

		return null;
	}

	/**
	 * Sets query variable.
	 *
	 * @since 2.0.0
	 *
	 * @param string $query_var Query variable key.
	 * @param mixed  $value     Query variable value.
	 */
	public function set( $query_var, $value ) {
		$this->query_vars[ $query_var ] = $value;
	}

	/**
	 * Used internally to generate an SQL string for searching across multiple columns.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string   $search  Search string.
	 * @param string[] $columns Array of columns to search.
	 * @param bool     $wild    Whether to allow wildcard searches.
	 * @return string
	 */
	protected function get_search_sql( $search, $columns, $wild = false ) {
		global $wpdb;

		$searches      = array();
		$leading_wild  = ( 'leading' === $wild || 'both' === $wild ) ? '%' : '';
		$trailing_wild = ( 'trailing' === $wild || 'both' === $wild ) ? '%' : '';
		$like          = $leading_wild . $wpdb->esc_like( $search ) . $trailing_wild;

		foreach ( $columns as $column ) {
			if ( 'email_log_id' === $column ) {
				$searches[] = $wpdb->prepare( "$column = %s", $search );
			} else {
				$searches[] = $wpdb->prepare( "$column LIKE %s", $like );
			}
		}

		return ' AND (' . implode( ' OR ', $searches ) . ')';
	}

	/**
	 * Returns the list of items.
	 *
	 * @since 2.0.0
	 *
	 * @return array Array of results.
	 */
	public function get_results() {
		return $this->results;
	}

	/**
	 * Returns the total number of items for the current query.
	 *
	 * @since 2.0.0
	 *
	 * @return int Number of total items.
	 */
	public function get_total() {
		return $this->total_items;
	}

	/**
	 * Parses and sanitizes 'orderby' keys passed to the query.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $orderby Alias for the field to order by.
	 * @return string Value to used in the ORDER clause, if `$orderby` is valid.
	 */
	protected function parse_orderby( $orderby ) {
		global $wpdb;

		$_orderby = '';
		if ( in_array( $orderby, array( 'email_log_id', 'date_sent', 'action', 'name', 'date_added', 'status' ), true ) ) {
			$_orderby = $orderby;
		} else if ( in_array( $orderby, array( 'email_list_id' ), true ) ) {
			$_orderby = "{$this->list_table}." . $orderby;
		}

		return $_orderby;
	}

	/**
	 * Parses an 'order' query variable and casts it to ASC or DESC as necessary.
	 *
	 * @since 2.0.0
	 *
	 * @param string $order The 'order' query variable.
	 * @return string The sanitized 'order' query variable.
	 */
	protected function parse_order( $order ) {
		if ( ! is_string( $order ) || empty( $order ) ) {
			return 'DESC';
		}

		if ( 'ASC' === strtoupper( $order ) ) {
			return 'ASC';
		} else {
			return 'DESC';
		}
	}

	/**
	 * Makes private properties readable for backward compatibility.
	 *
	 * @since 4.0.0
	 *
	 * @param string $name Property to get.
	 * @return mixed Property.
	 */
	public function __get( $name ) {
		if ( in_array( $name, $this->compat_fields, true ) ) {
			return $this->$name;
		}
	}

	/**
	 * Makes private properties settable for backward compatibility.
	 *
	 * @since 2.0.0
	 *
	 * @param string $name  Property to check if set.
	 * @param mixed  $value Property value.
	 * @return mixed Newly-set property.
	 */
	public function __set( $name, $value ) {
		if ( in_array( $name, $this->compat_fields, true ) ) {
			return $this->$name = $value;
		}
	}

	/**
	 * Makes private properties checkable for backward compatibility.
	 *
	 * @since 2.0.0
	 *
	 * @param string $name Property to check if set.
	 * @return bool Whether the property is set.
	 */
	public function __isset( $name ) {
		if ( in_array( $name, $this->compat_fields, true ) ) {
			return isset( $this->$name );
		}
	}

	/**
	 * Makes private properties un-settable for backward compatibility.
	 *
	 * @since 2.0.0
	 *
	 * @param string $name Property to unset.
	 */
	public function __unset( $name ) {
		if ( in_array( $name, $this->compat_fields, true ) ) {
			unset( $this->$name );
		}
	}

	/**
	 * Makes private/protected methods readable for backward compatibility.
	 *
	 * @since 2.0.0
	 *
	 * @param string $name      Method to call.
	 * @param array  $arguments Arguments to pass when calling.
	 * @return mixed Return value of the callback, false otherwise.
	 */
	public function __call( $name, $arguments ) {
		if ( 'get_search_sql' === $name ) {
			return $this->get_search_sql( ...$arguments );
		}
		return false;
	}
}