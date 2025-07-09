<?php
/**
 * Debug logger for API requests and responses.
 *
 * @package    LayoutBerg
 * @subpackage Core
 * @since      1.3.0
 */

namespace DotCamp\LayoutBerg;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Debug Logger class.
 *
 * Handles logging of API requests, responses, and other debug information
 * for Agency plan users when debug mode is enabled.
 *
 * @since 1.3.0
 */
class Debug_Logger {

	/**
	 * Check if debug logging is enabled.
	 *
	 * @since 1.3.0
	 * @return bool True if debug logging is enabled.
	 */
	public static function is_enabled() {
		// Check if user has agency plan
		if ( ! LayoutBerg_Licensing::is_agency_plan() ) {
			return false;
		}

		// Check if debug mode is enabled in settings
		$options = get_option( 'layoutberg_options', array() );
		return ! empty( $options['debug_mode'] );
	}

	/**
	 * Check if verbose logging is enabled.
	 *
	 * @since 1.3.0
	 * @return bool True if verbose logging is enabled.
	 */
	public static function is_verbose() {
		if ( ! self::is_enabled() ) {
			return false;
		}

		$options = get_option( 'layoutberg_options', array() );
		return ! empty( $options['verbose_logging'] );
	}

	/**
	 * Log an API request and response.
	 *
	 * @since 1.3.0
	 * @param array $args Log arguments.
	 * @return int|false The log ID on success, false on failure.
	 */
	public static function log_api_request( $args = array() ) {
		if ( ! self::is_enabled() ) {
			return false;
		}

		global $wpdb;

		$defaults = array(
			'user_id'         => get_current_user_id(),
			'log_type'        => 'api_request',
			'log_level'       => 'info',
			'provider'        => null,
			'model'           => null,
			'request_data'    => null,
			'response_data'   => null,
			'error_message'   => null,
			'tokens_used'     => 0,
			'processing_time' => 0,
			'metadata'        => null,
		);

		$args = wp_parse_args( $args, $defaults );

		// Serialize complex data
		if ( is_array( $args['request_data'] ) || is_object( $args['request_data'] ) ) {
			$args['request_data'] = wp_json_encode( $args['request_data'] );
		}

		if ( is_array( $args['response_data'] ) || is_object( $args['response_data'] ) ) {
			$args['response_data'] = wp_json_encode( $args['response_data'] );
		}

		if ( is_array( $args['metadata'] ) || is_object( $args['metadata'] ) ) {
			$args['metadata'] = wp_json_encode( $args['metadata'] );
		}

		// Insert log entry
		$result = $wpdb->insert(
			$wpdb->prefix . 'layoutberg_debug_logs',
			$args,
			array(
				'%d', // user_id
				'%s', // log_type
				'%s', // log_level
				'%s', // provider
				'%s', // model
				'%s', // request_data
				'%s', // response_data
				'%s', // error_message
				'%d', // tokens_used
				'%f', // processing_time
				'%s', // metadata
			)
		);

		if ( false === $result ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Log a general debug message.
	 *
	 * @since 1.3.0
	 * @param string $message The message to log.
	 * @param string $level The log level (info, warning, error, debug).
	 * @param array  $metadata Additional metadata.
	 * @return int|false The log ID on success, false on failure.
	 */
	public static function log( $message, $level = 'info', $metadata = null ) {
		if ( ! self::is_enabled() ) {
			return false;
		}

		return self::log_api_request( array(
			'log_type'      => 'debug',
			'log_level'     => $level,
			'error_message' => $message,
			'metadata'      => $metadata,
		) );
	}

	/**
	 * Get debug logs.
	 *
	 * @since 1.3.0
	 * @param array $args Query arguments.
	 * @return array Array of log entries.
	 */
	public static function get_logs( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'user_id'  => null,
			'log_type' => null,
			'log_level' => null,
			'provider' => null,
			'limit'    => 100,
			'offset'   => 0,
			'orderby'  => 'created_at',
			'order'    => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		// Build WHERE clause
		$where = array( '1=1' );
		$values = array();

		if ( ! empty( $args['user_id'] ) ) {
			$where[] = 'user_id = %d';
			$values[] = $args['user_id'];
		}

		if ( ! empty( $args['log_type'] ) ) {
			$where[] = 'log_type = %s';
			$values[] = $args['log_type'];
		}

		if ( ! empty( $args['log_level'] ) ) {
			$where[] = 'log_level = %s';
			$values[] = $args['log_level'];
		}

		if ( ! empty( $args['provider'] ) ) {
			$where[] = 'provider = %s';
			$values[] = $args['provider'];
		}

		// Validate orderby
		$allowed_orderby = array( 'id', 'created_at', 'log_level', 'log_type', 'provider' );
		if ( ! in_array( $args['orderby'], $allowed_orderby, true ) ) {
			$args['orderby'] = 'created_at';
		}

		// Validate order
		$args['order'] = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		// Build query
		$table_name = $wpdb->prefix . 'layoutberg_debug_logs';
		$where_clause = implode( ' AND ', $where );
		
		$sql = "SELECT * FROM $table_name WHERE $where_clause ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d";
		$values[] = $args['limit'];
		$values[] = $args['offset'];

		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $sql, $values );
		}

		$results = $wpdb->get_results( $sql, ARRAY_A );

		// Decode JSON data
		if ( $results ) {
			foreach ( $results as &$log ) {
				if ( ! empty( $log['request_data'] ) ) {
					$log['request_data'] = json_decode( $log['request_data'], true );
				}
				if ( ! empty( $log['response_data'] ) ) {
					$log['response_data'] = json_decode( $log['response_data'], true );
				}
				if ( ! empty( $log['metadata'] ) ) {
					$log['metadata'] = json_decode( $log['metadata'], true );
				}
			}
		}

		return $results ? $results : array();
	}

	/**
	 * Get log count.
	 *
	 * @since 1.3.0
	 * @param array $args Query arguments.
	 * @return int Log count.
	 */
	public static function get_log_count( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'user_id'  => null,
			'log_type' => null,
			'log_level' => null,
			'provider' => null,
		);

		$args = wp_parse_args( $args, $defaults );

		// Build WHERE clause
		$where = array( '1=1' );
		$values = array();

		if ( ! empty( $args['user_id'] ) ) {
			$where[] = 'user_id = %d';
			$values[] = $args['user_id'];
		}

		if ( ! empty( $args['log_type'] ) ) {
			$where[] = 'log_type = %s';
			$values[] = $args['log_type'];
		}

		if ( ! empty( $args['log_level'] ) ) {
			$where[] = 'log_level = %s';
			$values[] = $args['log_level'];
		}

		if ( ! empty( $args['provider'] ) ) {
			$where[] = 'provider = %s';
			$values[] = $args['provider'];
		}

		// Build query
		$table_name = $wpdb->prefix . 'layoutberg_debug_logs';
		$where_clause = implode( ' AND ', $where );
		
		$sql = "SELECT COUNT(*) FROM $table_name WHERE $where_clause";

		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $sql, $values );
		}

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Clear debug logs.
	 *
	 * @since 1.3.0
	 * @param int $days Clear logs older than X days (0 = clear all).
	 * @return int Number of logs cleared.
	 */
	public static function clear_logs( $days = 0 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'layoutberg_debug_logs';

		if ( $days > 0 ) {
			$date = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
			$result = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $table_name WHERE created_at < %s",
					$date
				)
			);
		} else {
			$result = $wpdb->query( "TRUNCATE TABLE $table_name" );
		}

		return $result;
	}

	/**
	 * Get a single log entry.
	 *
	 * @since 1.3.0
	 * @param int $log_id The log ID.
	 * @return array|null Log entry or null if not found.
	 */
	public static function get_log( $log_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'layoutberg_debug_logs';
		$log = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE id = %d",
				$log_id
			),
			ARRAY_A
		);

		if ( ! $log ) {
			return null;
		}

		// Decode JSON data
		if ( ! empty( $log['request_data'] ) ) {
			$log['request_data'] = json_decode( $log['request_data'], true );
		}
		if ( ! empty( $log['response_data'] ) ) {
			$log['response_data'] = json_decode( $log['response_data'], true );
		}
		if ( ! empty( $log['metadata'] ) ) {
			$log['metadata'] = json_decode( $log['metadata'], true );
		}

		return $log;
	}
}