<?php
/**
 * TableDataStore class file.
 */

/**************************************************************************
* Data Stores is a topic bigger than one session.
*
*	W:\fhs\wordpress\wp-content\plugins\woocommerce\includes\data-stores
*		class-wc-data-store-wp.php
*		abstract class Abstract_stdClass_Data_Store_CPT extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface, WC_Abstract_Order_Data_Store_Interface {
*
************/

//namespace Automattic\WooCommerce\Internal\DataStores\;

defined( 'ABSPATH' ) || exit;

/**
 * This class is the standard data store to be used when the custom table is in use.
 *
 *	Merged 2 levels of extends into this one.
 *	Need to review to make more generic.  Still based upon wc/order
 *	Has some functions (i.e. timestamp conversions) that should probably be in helper classes.
 */
class TableDataStore  implements \WC_Object_Data_Store_Interface {

	protected $slug;	//For orders, it is wc_order
	protected $default_status_attrib;	//i.e.  'woocommerce_default_order_status'
	protected $valid_statuses;		//!<array eg 'auto-draft', 'draft', 'trash' 
	protected $status_prefix;		//!<string in case we want to prefix statuses to be plugin specific.  Woo uses wc-
						//	Could set to "" which will mean the validation just checks 2x

	/**
	 * Internal meta type used to store order data.
	 *
	 * @var string
	 */
	protected $meta_type = 'post';

	/**
	 * This only needs set if you are using a custom metadata type (for example payment tokens.
	 * This should be the name of the field your table uses for associating meta with objects.
	 * For example, in payment_tokenmeta, this would be payment_token_id.
	 *
	 * @var string
	 */
	protected $object_id_field_for_meta = '';


	/**
	 * Data stored in meta keys, but not considered "meta" for an order.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
	//	'_order_currency',
	);

	/**
	 * Meta data which should exist in the DB, even if empty.
	 *
	 * @since 3.6.0
	 *
	 * @var array
	 */
	protected $must_exist_meta_keys = array();

	/**
	 * Get and store terms from a taxonomy.
	 *
	 * @since  3.0.0
	 * @param  WC_Data|integer $object WC_Data object or object ID.
	 * @param  string          $taxonomy Taxonomy name e.g. product_cat.
	 * @return array of terms
	 */
	protected function get_term_ids( $object, $taxonomy ) {
		if ( is_numeric( $object ) ) {
			$object_id = $object;
		} else {
			$object_id = $object->get_id();
		}
		$terms = get_the_terms( $object_id, $taxonomy );
		if ( false === $terms || is_wp_error( $terms ) ) {
			return array();
		}
		return wp_list_pluck( $terms, 'term_id' );
	}

	/**
	 * Returns an array of meta for an object.
	 *
	 * @since  3.0.0
	 * @param  WC_Data $object WC_Data object.
	 * @return array
	 */
	public function read_meta( &$object ) {
		global $wpdb;
		$db_info       = $this->get_db_info();
		$raw_meta_data = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT {$db_info['meta_id_field']} as meta_id, meta_key, meta_value
				FROM {$db_info['table']}
				WHERE {$db_info['object_id_field']} = %d
				ORDER BY {$db_info['meta_id_field']}",
				// phpcs:enable
				$object->get_id()
			)
		);
		return $this->filter_raw_meta_data( $object, $raw_meta_data );
	}
	/**
	 * Helper method to filter internal meta keys from all meta data rows for the object.
	 *
	 * @since 4.7.0
	 *
	 * @param WC_Data $object        WC_Data object.
	 * @param array   $raw_meta_data Array of std object of meta data to be filtered.
	 *
	 * @return mixed|void
	 */
	public function filter_raw_meta_data( &$object, $raw_meta_data ) {
		$this->internal_meta_keys = array_merge( array_map( array( $this, 'prefix_key' ), $object->get_data_keys() ), $this->internal_meta_keys );
		$meta_data                = array_filter( $raw_meta_data, array( $this, 'exclude_internal_meta_keys' ) );
		return apply_filters( "woocommerce_data_store_wp_{$this->meta_type}_read_meta", $meta_data, $object, $this );
	}

	/**
	 * Deletes meta based on meta ID.
	 *
	 * @since  3.0.0
	 * @param  WC_Data  $object WC_Data object.
	 * @param  stdClass $meta (containing at least ->id).
	 */
	public function delete_meta( &$object, $meta ) {
		delete_metadata_by_mid( $this->meta_type, $meta->id );
	}

	/**
	 * Add new piece of meta.
	 *
	 * @since  3.0.0
	 * @param  WC_Data  $object WC_Data object.
	 * @param  stdClass $meta (containing ->key and ->value).
	 * @return int meta ID
	 */
	public function add_meta( &$object, $meta ) {
		return add_metadata( $this->meta_type, $object->get_id(), wp_slash( $meta->key ), is_string( $meta->value ) ? wp_slash( $meta->value ) : $meta->value, false );
	}

	/**
	 * Update meta.
	 *
	 * @since  3.0.0
	 * @param  WC_Data  $object WC_Data object.
	 * @param  stdClass $meta (containing ->id, ->key and ->value).
	 */
	public function update_meta( &$object, $meta ) {
		update_metadata_by_mid( $this->meta_type, $meta->id, $meta->value, $meta->key );
	}
	/**
	 * Table structure is slightly different between meta types, this function will return what we need to know.
	 *
	 * @since  3.0.0
	 * @return array Array elements: table, object_id_field, meta_id_field
	 */
	protected function get_db_info() {
		global $wpdb;

		$meta_id_field = 'meta_id'; // for some reason users calls this umeta_id so we need to track this as well.
		$table         = $wpdb->prefix;

		// If we are dealing with a type of metadata that is not a core type, the table should be prefixed.
		if ( ! in_array( $this->meta_type, array( 'post', 'user', 'comment', 'term' ), true ) ) {
			$table .= 'woocommerce_';
		}

		$table          .= $this->meta_type . 'meta';
		$object_id_field = $this->meta_type . '_id';

		// Figure out our field names.
		if ( 'user' === $this->meta_type ) {
			$meta_id_field = 'umeta_id';
			$table         = $wpdb->usermeta;
		}

		if ( ! empty( $this->object_id_field_for_meta ) ) {
			$object_id_field = $this->object_id_field_for_meta;
		}

		return array(
			'table'           => $table,
			'object_id_field' => $object_id_field,
			'meta_id_field'   => $meta_id_field,
		);
	}

	/**
	 * Internal meta keys we don't want exposed as part of meta_data. This is in
	 * addition to all data props with _ prefix.
	 *
	 * @since 2.6.0
	 *
	 * @param string $key Prefix to be added to meta keys.
	 * @return string
	 */
	protected function prefix_key( $key ) {
		return '_' === substr( $key, 0, 1 ) ? $key : '_' . $key;
	}

	/**
	 * Callback to remove unwanted meta data.
	 *
	 * @param object $meta Meta object to check if it should be excluded or not.
	 * @return bool
	 */
	protected function exclude_internal_meta_keys( $meta ) {
		return ! in_array( $meta->meta_key, $this->internal_meta_keys, true ) && 0 !== stripos( $meta->meta_key, 'wp_' );
	}
	/**
	 * Update meta data in, or delete it from, the database.
	 *
	 * Avoids storing meta when it's either an empty string or empty array.
	 * Other empty values such as numeric 0 and null should still be stored.
	 * Data-stores can force meta to exist using `must_exist_meta_keys`.
	 *
	 * Note: WordPress `get_metadata` function returns an empty string when meta data does not exist.
	 *
	 * @param WC_Data $object The WP_Data object (WC_Coupon for coupons, etc).
	 * @param string  $meta_key Meta key to update.
	 * @param mixed   $meta_value Value to save.
	 *
	 * @since 3.6.0 Added to prevent empty meta being stored unless required.
	 *
	 * @return bool True if updated/deleted.
	 */
	protected function update_or_delete_post_meta( $object, $meta_key, $meta_value ) {
		if ( in_array( $meta_value, array( array(), '' ), true ) && ! in_array( $meta_key, $this->must_exist_meta_keys, true ) ) {
			$updated = delete_post_meta( $object->get_id(), $meta_key );
		} else {
			$updated = update_post_meta( $object->get_id(), $meta_key, $meta_value );
		}

		return (bool) $updated;
	}

	/**
	 * Get valid WP_Query args from a WC_Object_Query's query variables.
	 *
	 * @since 3.1.0
	 * @param array $query_vars query vars from a WC_Object_Query.
	 * @return array
	 */
	protected function get_wp_query_args( $query_vars ) {

		$skipped_values = array( '', array(), null );
		$wp_query_args  = array(
			'errors'     => array(),
			'meta_query' => array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		);

		foreach ( $query_vars as $key => $value ) {
			if ( in_array( $value, $skipped_values, true ) || 'meta_query' === $key ) {
				continue;
			}

			// Build meta queries out of vars that are stored in internal meta keys.
			if ( in_array( '_' . $key, $this->internal_meta_keys, true ) ) {
				// Check for existing values if wildcard is used.
				if ( '*' === $value ) {
					$wp_query_args['meta_query'][] = array(
						array(
							'key'     => '_' . $key,
							'compare' => 'EXISTS',
						),
						array(
							'key'     => '_' . $key,
							'value'   => '',
							'compare' => '!=',
						),
					);
				} else {
					$wp_query_args['meta_query'][] = array(
						'key'     => '_' . $key,
						'value'   => $value,
						'compare' => is_array( $value ) ? 'IN' : '=',
					);
				}
			} else { // Other vars get mapped to wp_query args or just left alone.
				$key_mapping = array(
					'parent'         => 'post_parent',
					'parent_exclude' => 'post_parent__not_in',
					'exclude'        => 'post__not_in',
					'limit'          => 'posts_per_page',
					'type'           => 'post_type',
					'return'         => 'fields',
				);

				if ( isset( $key_mapping[ $key ] ) ) {
					$wp_query_args[ $key_mapping[ $key ] ] = $value;
				} else {
					$wp_query_args[ $key ] = $value;
				}
			}
		}

		return apply_filters( 'woocommerce_get_wp_query_args', $wp_query_args, $query_vars );
	}

	/**
	 * Map a valid date query var to WP_Query arguments.
	 * Valid date formats: YYYY-MM-DD or timestamp, possibly combined with an operator from $valid_operators.
	 * Also accepts a WC_DateTime object.
	 *
	 * @since 3.2.0
	 * @param mixed  $query_var A valid date format.
	 * @param string $key meta or db column key.
	 * @param array  $wp_query_args WP_Query args.
	 * @return array Modified $wp_query_args
	 */
	public function parse_date_for_wp_query( $query_var, $key, $wp_query_args = array() ) {
		$query_parse_regex = '/([^.<>]*)(>=|<=|>|<|\.\.\.)([^.<>]+)/';
		$valid_operators   = array( '>', '>=', '=', '<=', '<', '...' );

		// YYYY-MM-DD queries have 'day' precision. Timestamp/WC_DateTime queries have 'second' precision.
		$precision = 'second';

		$dates    = array();
		$operator = '=';

		try {
			// Specific time query with a WC_DateTime.
			if ( is_a( $query_var, 'WC_DateTime' ) ) {
				$dates[] = $query_var;
			} elseif ( is_numeric( $query_var ) ) { // Specific time query with a timestamp.
				$dates[] = new WC_DateTime( "@{$query_var}", new DateTimeZone( 'UTC' ) );
			} elseif ( preg_match( $query_parse_regex, $query_var, $sections ) ) { // Query with operators and possible range of dates.
				if ( ! empty( $sections[1] ) ) {
					$dates[] = is_numeric( $sections[1] ) ? new WC_DateTime( "@{$sections[1]}", new DateTimeZone( 'UTC' ) ) : wc_string_to_datetime( $sections[1] );
				}

				$operator = in_array( $sections[2], $valid_operators, true ) ? $sections[2] : '';
				$dates[]  = is_numeric( $sections[3] ) ? new WC_DateTime( "@{$sections[3]}", new DateTimeZone( 'UTC' ) ) : wc_string_to_datetime( $sections[3] );

				if ( ! is_numeric( $sections[1] ) && ! is_numeric( $sections[3] ) ) {
					$precision = 'day';
				}
			} else { // Specific time query with a string.
				$dates[]   = wc_string_to_datetime( $query_var );
				$precision = 'day';
			}
		} catch ( Exception $e ) {
			return $wp_query_args;
		}

		// Check for valid inputs.
		if ( ! $operator || empty( $dates ) || ( '...' === $operator && count( $dates ) < 2 ) ) {
			return $wp_query_args;
		}

		// Build date query for 'post_date' or 'post_modified' keys.
		if ( 'post_date' === $key || 'post_modified' === $key ) {
			if ( ! isset( $wp_query_args['date_query'] ) ) {
				$wp_query_args['date_query'] = array();
			}

			$query_arg = array(
				'column'    => 'day' === $precision ? $key : $key . '_gmt',
				'inclusive' => '>' !== $operator && '<' !== $operator,
			);

			// Add 'before'/'after' query args.
			$comparisons = array();
			if ( '>' === $operator || '>=' === $operator || '...' === $operator ) {
				$comparisons[] = 'after';
			}
			if ( '<' === $operator || '<=' === $operator || '...' === $operator ) {
				$comparisons[] = 'before';
			}

			foreach ( $comparisons as $index => $comparison ) {
				if ( 'day' === $precision ) {
					/**
					 * WordPress doesn't generate the correct SQL for inclusive day queries with both a 'before' and
					 * 'after' string query, so we have to use the array format in 'day' precision.
					 *
					 * @see https://core.trac.wordpress.org/ticket/29908
					 */
					$query_arg[ $comparison ]['year']  = $dates[ $index ]->date( 'Y' );
					$query_arg[ $comparison ]['month'] = $dates[ $index ]->date( 'n' );
					$query_arg[ $comparison ]['day']   = $dates[ $index ]->date( 'j' );
				} else {
					/**
					 * WordPress doesn't support 'hour'/'second'/'minute' in array format 'before'/'after' queries,
					 * so we have to use a string query.
					 */
					$query_arg[ $comparison ] = gmdate( 'm/d/Y H:i:s', $dates[ $index ]->getTimestamp() );
				}
			}

			if ( empty( $comparisons ) ) {
				$query_arg['year']  = $dates[0]->date( 'Y' );
				$query_arg['month'] = $dates[0]->date( 'n' );
				$query_arg['day']   = $dates[0]->date( 'j' );
				if ( 'second' === $precision ) {
					$query_arg['hour']   = $dates[0]->date( 'H' );
					$query_arg['minute'] = $dates[0]->date( 'i' );
					$query_arg['second'] = $dates[0]->date( 's' );
				}
			}
			$wp_query_args['date_query'][] = $query_arg;
			return $wp_query_args;
		}

		// Build meta query for unrecognized keys.
		if ( ! isset( $wp_query_args['meta_query'] ) ) {
			$wp_query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		// Meta dates are stored as timestamps in the db.
		// Check against beginning/end-of-day timestamps when using 'day' precision.
		if ( 'day' === $precision ) {
			$start_timestamp = strtotime( gmdate( 'm/d/Y 00:00:00', $dates[0]->getTimestamp() ) );
			$end_timestamp   = '...' !== $operator ? ( $start_timestamp + DAY_IN_SECONDS ) : strtotime( gmdate( 'm/d/Y 00:00:00', $dates[1]->getTimestamp() ) );
			switch ( $operator ) {
				case '>':
				case '<=':
					$wp_query_args['meta_query'][] = array(
						'key'     => $key,
						'value'   => $end_timestamp,
						'compare' => $operator,
					);
					break;
				case '<':
				case '>=':
					$wp_query_args['meta_query'][] = array(
						'key'     => $key,
						'value'   => $start_timestamp,
						'compare' => $operator,
					);
					break;
				default:
					$wp_query_args['meta_query'][] = array(
						'key'     => $key,
						'value'   => $start_timestamp,
						'compare' => '>=',
					);
					$wp_query_args['meta_query'][] = array(
						'key'     => $key,
						'value'   => $end_timestamp,
						'compare' => '<=',
					);
			}
		} else {
			if ( '...' !== $operator ) {
				$wp_query_args['meta_query'][] = array(
					'key'     => $key,
					'value'   => $dates[0]->getTimestamp(),
					'compare' => $operator,
				);
			} else {
				$wp_query_args['meta_query'][] = array(
					'key'     => $key,
					'value'   => $dates[0]->getTimestamp(),
					'compare' => '>=',
				);
				$wp_query_args['meta_query'][] = array(
					'key'     => $key,
					'value'   => $dates[1]->getTimestamp(),
					'compare' => '<=',
				);
			}
		}

		return $wp_query_args;
	}

	/**
	 * Return list of internal meta keys.
	 *
	 * @since 3.2.0
	 * @return array
	 */
	public function get_internal_meta_keys() {
		return $this->internal_meta_keys;
	}

	/**
	 * Check if the terms are suitable for searching.
	 *
	 * Uses an array of stopwords (terms) that are excluded from the separate
	 * term matching when searching for posts. The list of English stopwords is
	 * the approximate search engines list, and is translatable.
	 *
	 * @since 3.4.0
	 * @param array $terms Terms to check.
	 * @return array Terms that are not stopwords.
	 */
	protected function get_valid_search_terms( $terms ) {
		$valid_terms = array();
		$stopwords   = $this->get_search_stopwords();

		foreach ( $terms as $term ) {
			// keep before/after spaces when term is for exact match, otherwise trim quotes and spaces.
			if ( preg_match( '/^".+"$/', $term ) ) {
				$term = trim( $term, "\"'" );
			} else {
				$term = trim( $term, "\"' " );
			}

			// Avoid single A-Z and single dashes.
			if ( empty( $term ) || ( 1 === strlen( $term ) && preg_match( '/^[a-z\-]$/i', $term ) ) ) {
				continue;
			}

			if ( in_array( wc_strtolower( $term ), $stopwords, true ) ) {
				continue;
			}

			$valid_terms[] = $term;
		}

		return $valid_terms;
	}

	/**
	 * Retrieve stopwords used when parsing search terms.
	 *
	 * @since 3.4.0
	 * @return array Stopwords.
	 */
	protected function get_search_stopwords() {
		// Translators: This is a comma-separated list of very common words that should be excluded from a search, like a, an, and the. These are usually called "stopwords". You should not simply translate these individual words into your language. Instead, look for and provide commonly accepted stopwords in your language.
		$stopwords = array_map(
			'wc_strtolower',
			array_map(
				'trim',
				explode(
					',',
					_x(
						'about,an,are,as,at,be,by,com,for,from,how,in,is,it,of,on,or,that,the,this,to,was,what,when,where,who,will,with,www',
						'Comma-separated list of search stopwords in your language',
						'woocommerce'
					)
				)
			)
		);

		return apply_filters( 'wp_search_stopwords', $stopwords );
	}

	/**
	 * Get data to save to a lookup table.
	 *
	 * @since 3.6.0
	 * @param int    $id ID of object to update.
	 * @param string $table Lookup table name.
	 * @return array
	 */
	protected function get_data_for_lookup_table( $id, $table ) {
		return array();
	}

	/**
	 * Get primary key name for lookup table.
	 *
	 * @since 3.6.0
	 * @param string $table Lookup table name.
	 * @return string
	 */
	protected function get_primary_key_for_lookup_table( $table ) {
		return '';
	}

	/**
	 * Update a lookup table for an object.
	 *
	 * @since 3.6.0
	 * @param int    $id ID of object to update.
	 * @param string $table Lookup table name.
	 *
	 * @return NULL
	 */
	protected function update_lookup_table( $id, $table ) {
		global $wpdb;

		$id    = absint( $id );
		$table = sanitize_key( $table );

		if ( empty( $id ) || empty( $table ) ) {
			return false;
		}

		$existing_data = wp_cache_get( 'lookup_table', 'object_' . $id );
		$update_data   = $this->get_data_for_lookup_table( $id, $table );

		if ( ! empty( $update_data ) && $update_data !== $existing_data ) {
			$wpdb->replace(
				$wpdb->$table,
				$update_data
			);
			wp_cache_set( 'lookup_table', $update_data, 'object_' . $id );
		}
	}

	/**
	 * Delete lookup table data for an ID.
	 *
	 * @since 3.6.0
	 * @param int    $id ID of object to update.
	 * @param string $table Lookup table name.
	 */
	public function delete_from_lookup_table( $id, $table ) {
		global $wpdb;

		$id    = absint( $id );
		$table = sanitize_key( $table );

		if ( empty( $id ) || empty( $table ) ) {
			return false;
		}

		$pk = $this->get_primary_key_for_lookup_table( $table );

		$wpdb->delete(
			$wpdb->$table,
			array(
				$pk => $id,
			)
		);
		wp_cache_delete( 'lookup_table', 'object_' . $id );
	}

	/**
	 * Converts a WP post date string into a timestamp.
	 *
	 * @since 4.8.0
	 *
	 * @param  string $time_string The WP post date string.
	 * @return int|null The date string converted to a timestamp or null.
	 */
	protected function string_to_timestamp( $time_string ) {
		return '0000-00-00 00:00:00' !== $time_string ? wc_string_to_timestamp( $time_string ) : null;
	}

	/**
	 * Get the custom  table name.
	 *
	 * @return string The custom  table name.
	 */
	public function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . $this->slug;
	}
	/**
	 * Get the names of all the tables involved in the custom orders table feature.
	 *
	 * @return string[]
	 */
	public function get_all_table_names() {
	// TODO: Add methods for other table names as appropriate.
		return array(
			$this->get_table_name(),
			//$this->get_addresses_table_name(),
			//$this->get_operational_data_table_name(),
		);
	}


	public function search_XXX( $term ) {
		// TODO: Implement search_orders() method.
		return array();
	}

	public function get_download_permissions_granted( $order ) {
		// TODO: Implement get_download_permissions_granted() method.
		false;
	}

	public function set_download_permissions_granted( $order, $set ) {
		// TODO: Implement set_download_permissions_granted() method.
	}


	/**
	 * @param \stdClass $order
	 */
	public function create( &$order ) {
		throw new \Exception( 'Unimplemented' );
	}

	public function update( &$order ) {
		throw new \Exception( 'Unimplemented' );
	}

	public function query( $query_vars ) {
		return array();
	}

	/**
	 * Get the SQL needed to create all the tables needed for the custom orders table feature.
	 *
	 * @return string
	 */
	public function get_database_schema() {
		$table_name           = $this->get_table_name();
		//$addresses_table_name        = $this->get_addresses_table_name();
		//$operational_data_table_name = $this->get_operational_data_table_name();

		$sql = "
CREATE TABLE $table_name (
	id bigint(20) unsigned auto_increment,
	post_id bigint(20) unsigned null,
	status varchar(20) null,
	date_created_gmt datetime null,
	date_updated_gmt datetime null,
	PRIMARY KEY (id),
	KEY post_id (post_id),
	KEY status (status),
	KEY date_created (date_created_gmt),
);
//CREATE TABLE $addresses_table_name (
//);
//CREATE TABLE $operational_data_table_name (
//);
";
		return $sql;
	}

/*
|--------------------------------------------------------------------------
| CRUD Methods
|--------------------------------------------------------------------------
*/
	/**
	 * Method to create a new order in the database.
	 *
	 * @param stdClass $record Order object.
	 */
	public function create( &$record ) {
		if ( ! $record->get_date_created( 'edit' ) ) {
			$record->set_date_created( time() );
		}

		$id = wp_insert_post(
			apply_filters(
				'woocommerce_new_order_data',
				array(
					'post_date'     => gmdate( 'Y-m-d H:i:s', $record->get_date_created( 'edit' )->getOffsetTimestamp() ),
					'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $record->get_date_created( 'edit' )->getTimestamp() ),
					'post_type'     => $record->get_type( 'edit' ),
					'post_status'   => $this->get_post_status( $record ),
					'ping_status'   => 'closed',
					'post_author'   => 1,
					'post_title'    => $this->get_post_title(),
					'post_password' => $this->get_order_key( $record ),
					'post_parent'   => $record->get_parent_id( 'edit' ),
					'post_excerpt'  => $this->get_post_excerpt( $record ),
				)
			),
			true
		);

		if ( $id && ! is_wp_error( $id ) ) {
			$record->set_id( $id );
			$this->update_post_meta( $record );
			$record->save_meta_data();
			$record->apply_changes();
			$this->clear_caches( $record );
		}
	}

	/**
	 * Method to read an order from the database.
	 *
	 * @param stdClass $record Order object.
	 *
	 * @throws Exception If passed order is invalid.
	 */
	public function read( &$record ) {
		$record->set_defaults();
		$post_object = get_post( $record->get_id() );
		if ( ! $record->get_id() || ! $post_object || ! in_array( $post_object->post_type, wc_get_order_types(), true ) ) {
			throw new Exception( __( 'Invalid order.', 'woocommerce' ) );
		}

		$record->set_props(
			array(
				'parent_id'     => $post_object->post_parent,
				'date_created'  => $this->string_to_timestamp( $post_object->post_date_gmt ),
				'date_modified' => $this->string_to_timestamp( $post_object->post_modified_gmt ),
				'status'        => $post_object->post_status,
			)
		);

		$this->read_order_data( $record, $post_object );
		$record->read_meta_data();
		$record->set_object_read( true );

		/**
		 * In older versions, discounts may have been stored differently.
		 * Update them now so if the object is saved, the correct values are
		 * stored. @todo When meta is flattened, handle this during migration.
		 */
		if ( version_compare( $record->get_version( 'edit' ), '2.3.7', '<' ) && $record->get_prices_include_tax( 'edit' ) ) {
			$record->set_discount_total( (float) get_post_meta( $record->get_id(), '_cart_discount', true ) - (float) get_post_meta( $record->get_id(), '_cart_discount_tax', true ) );
		}
	}

	/**
	 * Method to update an order in the database.
	 *
	 * @param stdClass $record Order object.
	 */
	public function update( &$record ) {
		$record->save_meta_data();
		$record->set_version( Constants::get_constant( 'WC_VERSION' ) );

		if ( null === $record->get_date_created( 'edit' ) ) {
			$record->set_date_created( time() );
		}

		$changes = $record->get_changes();

		// Only update the post when the post data changes.
		if ( array_intersect( array( 'date_created', 'date_modified', 'status', 'parent_id', 'post_excerpt' ), array_keys( $changes ) ) ) {
			$post_data = array(
				'post_date'         => gmdate( 'Y-m-d H:i:s', $record->get_date_created( 'edit' )->getOffsetTimestamp() ),
				'post_date_gmt'     => gmdate( 'Y-m-d H:i:s', $record->get_date_created( 'edit' )->getTimestamp() ),
				'post_status'       => $this->get_post_status( $record ),
				'post_parent'       => $record->get_parent_id(),
				'post_excerpt'      => $this->get_post_excerpt( $record ),
				'post_modified'     => isset( $changes['date_modified'] ) ? gmdate( 'Y-m-d H:i:s', $record->get_date_modified( 'edit' )->getOffsetTimestamp() ) : current_time( 'mysql' ),
				'post_modified_gmt' => isset( $changes['date_modified'] ) ? gmdate( 'Y-m-d H:i:s', $record->get_date_modified( 'edit' )->getTimestamp() ) : current_time( 'mysql', 1 ),
			);

			/**
			 * When updating this object, to prevent infinite loops, use $wpdb
			 * to update data, since wp_update_post spawns more calls to the
			 * save_post action.
			 *
			 * This ensures hooks are fired by either WP itself (admin screen save),
			 * or an update purely from CRUD.
			 */
			if ( doing_action( 'save_post' ) ) {
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $record->get_id() ) );
				clean_post_cache( $record->get_id() );
			} else {
				wp_update_post( array_merge( array( 'ID' => $record->get_id() ), $post_data ) );
			}
			$record->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		}
		$this->update_post_meta( $record );
		$record->apply_changes();
		$this->clear_caches( $record );
	}

	/**
	 * Method to delete an order from the database.
	 *
	 * @param stdClass $record Order object.
	 * @param array    $args Array of args to pass to the delete method.
	 *
	 * @return void
	 */
	public function delete( &$record, $args = array() ) {
		$id   = $record->get_id();
		$args = wp_parse_args(
			$args,
			array(
				'force_delete' => false,
			)
		);

		if ( ! $id ) {
			return;
		}

		if ( $args['force_delete'] ) {
			wp_delete_post( $id );
			$record->set_id( 0 );
			do_action( 'woocommerce_delete_order', $id );
		} else {
			wp_trash_post( $id );
			$record->set_status( 'trash' );
			do_action( 'woocommerce_trash_order', $id );
		}
	}
/*
|--------------------------------------------------------------------------
| Additional Methods
|--------------------------------------------------------------------------
*/

	/**
	 * Get the status to save to the post object.
	 *
	 * Plugins extending the order classes can override this to change the stored status/add prefixes etc.
	 *
	 * @since 3.6.0
	 * @param  WC_order $record Order object.
	 * @return string
	 */
	protected function get_post_status( $record ) {
		$record_status = $record->get_status( 'edit' );

		if ( ! $record_status ) {
			$record_status = apply_filters( $this->default_status_attrib, 'pending' );
		}

		$post_status    = $record_status;
		$valid_statuses = get_post_stati();

		if( ! isset( $this->status_prefix ) )
		{
			throw new Exception( "Status prefixes not set in class so can't check for validity!" );
		}
		if( ! isset( $this->valid_statuses ) )
		{
			throw new Exception( "Valid statuses not set in class so can't check for validity!" );
		}
		if ( ! in_array( $post_status, $this->valid_statuses, true ) && in_array( $this->status_prefix . $post_status, $valid_statuses, true ) ) {
			$post_status = $this->status_prefix . $post_status;
		}

		return $post_status;
	}

	/**
	 * Excerpt for post.
	 *
	 * @param  stdClass $record object.
	 * @return string
	 */
	protected function get_post_excerpt( $record ) {
		return '';
	}

	/**
	 * Get a title for the new post type.
	 *
	 * @return string
	 */
	protected function get_post_title() {
		// @codingStandardsIgnoreStart
		/* translators: %s: Order date */
		return sprintf( __( 'Order &ndash; %s', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'woocommerce' ) ) );
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Get order key.
	 *
	 * @since 4.3.0
	 * @param WC_order $record Order object.
	 * @return string
	 */
	protected function get_order_key( $record ) {
		return wc_generate_order_key();
	}

	/**
	 * Read record data. Can be overridden by child classes to load other props.
	 *
	 * @param stdClass $record object.
	 * @param object   $post_object Post object.
	 * @since 22.12.02
	 */
	protected function read_record_data( &$record, $post_object ) 
	{
		$id = $record->get( "id" );
		//TODO: check for other way to do this
		$props = $record->get( "props" );
		foreach( $props as $field => $meta_key )
		{
			if( metadata_exists( 'post', $id, $meta_key ) )
			{
				$record->set( $field, get_post_meta( $id, $meta_key, true ) );
			}
		}
	}

	/**
	 * Helper method that updates all the post meta for an order based on it's settings in the stdClass class.
	 *
	 * @param stdClass $record Order object.
	 * @since 3.0.0
	 */
	protected function update_post_meta( &$record ) {
		$updated_props     = array();
		$meta_key_to_props = array();
		$props = $record->get( "props" );
		foreach( $props as $field => $meta_key )
		{
			$meta_key_to_props[$meta_key] = $field;
		}
			

		$props_to_update = $this->get_props_to_update( $record, $meta_key_to_props );

		foreach ( $props_to_update as $meta_key => $prop ) {
			$value = $record->{"get_$prop"}( 'edit' );
			$value = is_string( $value ) ? wp_slash( $value ) : $value;

			if ( 'prices_include_tax' === $prop ) {
				$value = $value ? 'yes' : 'no';
			}

			$updated = $this->update_or_delete_post_meta( $record, $meta_key, $value );

			if ( $updated ) {
				$updated_props[] = $prop;
			}
		}

		do_action( 'woocommerce_order_object_updated_props', $record, $updated_props );
	}

	/**
	 * Clear any caches.
	 *
	 * @param stdClass $record Order object.
	 * @since 3.0.0
	 */
	protected function clear_caches( &$record ) {
		clean_post_cache( $record->get_id() );
		wc_delete_shop_order_transients( $record );
		wp_cache_delete( 'order-items-' . $record->get_id(), 'orders' );
	}

	/**
	 * Read order items of a specific type from the database for this order.
	 *
	 * @param  stdClass $record Order object.
	 * @param  string   $type Order item type.
	 * @return array
	 */
	public function read_items( $record, $type ) {
		global $wpdb;

		// Get from cache if available.
		$items = 0 < $record->get_id() ? wp_cache_get( 'order-items-' . $record->get_id(), 'orders' ) : false;

		if ( false === $items ) {
			$items = $wpdb->get_results(
				$wpdb->prepare( "SELECT order_item_type, order_item_id, order_id, order_item_name FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d ORDER BY order_item_id;", $record->get_id() )
			);
			foreach ( $items as $item ) {
				wp_cache_set( 'item-' . $item->order_item_id, $item, 'order-items' );
			}
			if ( 0 < $record->get_id() ) {
				wp_cache_set( 'order-items-' . $record->get_id(), $items, 'orders' );
			}
		}

		$items = wp_list_filter( $items, array( 'order_item_type' => $type ) );

		if ( ! empty( $items ) ) {
			$items = array_map( array( 'stdClass_Factory', 'get_order_item' ), array_combine( wp_list_pluck( $items, 'order_item_id' ), $items ) );
		} else {
			$items = array();
		}

		return $items;
	}

	/**
	 * Remove all line items (products, coupons, shipping, taxes) from the order.
	 *
	 * @param stdClass $record Order object.
	 * @param string   $type Order item type. Default null.
	 */
	public function delete_items( $record, $type = null ) {
		global $wpdb;
		if ( ! empty( $type ) ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM itemmeta USING {$wpdb->prefix}woocommerce_order_itemmeta itemmeta INNER JOIN {$wpdb->prefix}woocommerce_order_items items WHERE itemmeta.order_item_id = items.order_item_id AND items.order_id = %d AND items.order_item_type = %s", $record->get_id(), $type ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d AND order_item_type = %s", $record->get_id(), $type ) );
		} else {
			$wpdb->query( $wpdb->prepare( "DELETE FROM itemmeta USING {$wpdb->prefix}woocommerce_order_itemmeta itemmeta INNER JOIN {$wpdb->prefix}woocommerce_order_items items WHERE itemmeta.order_item_id = items.order_item_id and items.order_id = %d", $record->get_id() ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d", $record->get_id() ) );
		}
		$this->clear_caches( $record );
	}

	/**
	 * Gets a list of props and meta keys that need updated based on change state
	 * or if they are present in the database or not.
	 *
	 * @param  WC_Data $object              The WP_Data object (WC_Coupon for coupons, etc).
	 * @param  array   $meta_key_to_props   A mapping of meta keys => prop names.
	 * @param  string  $meta_type           The internal WP meta type (post, user, etc).
	 * @return array                        A mapping of meta keys => prop names, filtered by ones that should be updated.
	 */
	protected function get_props_to_update( $object, $meta_key_to_props, $meta_type = 'post' ) {
		$props_to_update = array();
		$changed_props   = $object->get_changes();

		// Props should be updated if they are a part of the $changed array or don't exist yet.
		foreach ( $meta_key_to_props as $meta_key => $prop ) {
			if ( array_key_exists( $prop, $changed_props ) || ! metadata_exists( $meta_type, $object->get_id(), $meta_key ) ) {
				$props_to_update[ $meta_key ] = $prop;
			}
		}

		return $props_to_update;
	}
}

