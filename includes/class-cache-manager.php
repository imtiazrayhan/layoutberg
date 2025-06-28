<?php
/**
 * Cache manager class.
 *
 * @package    LayoutBerg
 * @subpackage Core
 * @since      1.0.0
 */

namespace DotCamp\LayoutBerg;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cache manager class.
 *
 * @since 1.0.0
 */
class Cache_Manager {

	/**
	 * Cache group.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string
	 */
	private $cache_group = 'layoutberg';

	/**
	 * Default cache expiration time.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    int
	 */
	private $default_expiration = 3600; // 1 hour.

	/**
	 * Memory cache.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array
	 */
	private $memory_cache = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Load cache settings.
		$options = get_option( 'layoutberg_options', array() );
		
		if ( isset( $options['cache_duration'] ) ) {
			$this->default_expiration = intval( $options['cache_duration'] );
		}
	}

	/**
	 * Get cached value.
	 *
	 * @since 1.0.0
	 * @param string $key Cache key.
	 * @return mixed|false Cached value or false if not found.
	 */
	public function get( $key ) {
		// Check if caching is disabled.
		if ( ! $this->is_cache_enabled() ) {
			return false;
		}

		// Check memory cache first.
		if ( isset( $this->memory_cache[ $key ] ) ) {
			return $this->memory_cache[ $key ];
		}

		// Check object cache if available.
		if ( wp_using_ext_object_cache() ) {
			$value = wp_cache_get( $key, $this->cache_group );
			if ( false !== $value ) {
				$this->memory_cache[ $key ] = $value;
				return $value;
			}
		}

		// Check transient cache.
		$value = get_transient( $key );
		if ( false !== $value ) {
			$this->memory_cache[ $key ] = $value;
			return $value;
		}

		// Check file cache as fallback.
		$value = $this->get_file_cache( $key );
		if ( false !== $value ) {
			$this->memory_cache[ $key ] = $value;
			return $value;
		}

		return false;
	}

	/**
	 * Set cache value.
	 *
	 * @since 1.0.0
	 * @param string $key        Cache key.
	 * @param mixed  $value      Value to cache.
	 * @param int    $expiration Optional. Expiration time in seconds.
	 * @return bool True on success, false on failure.
	 */
	public function set( $key, $value, $expiration = null ) {
		// Check if caching is disabled.
		if ( ! $this->is_cache_enabled() ) {
			return false;
		}

		if ( null === $expiration ) {
			$expiration = $this->default_expiration;
		}

		// Set in memory cache.
		$this->memory_cache[ $key ] = $value;

		// Set in object cache if available.
		if ( wp_using_ext_object_cache() ) {
			wp_cache_set( $key, $value, $this->cache_group, $expiration );
		}

		// Set in transient cache.
		set_transient( $key, $value, $expiration );

		// Set in file cache as fallback.
		$this->set_file_cache( $key, $value, $expiration );

		return true;
	}

	/**
	 * Delete cached value.
	 *
	 * @since 1.0.0
	 * @param string $key Cache key.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $key ) {
		// Remove from memory cache.
		unset( $this->memory_cache[ $key ] );

		// Remove from object cache if available.
		if ( wp_using_ext_object_cache() ) {
			wp_cache_delete( $key, $this->cache_group );
		}

		// Remove transient.
		delete_transient( $key );

		// Remove file cache.
		$this->delete_file_cache( $key );

		return true;
	}

	/**
	 * Flush all cache.
	 *
	 * @since 1.0.0
	 * @return bool True on success, false on failure.
	 */
	public function flush() {
		// Clear memory cache.
		$this->memory_cache = array();

		// Clear object cache if available.
		if ( wp_using_ext_object_cache() ) {
			wp_cache_flush();
		}

		// Clear transients.
		$this->clear_transients();

		// Clear file cache.
		$this->clear_file_cache();

		// Fire action hook.
		do_action( 'layoutberg_cache_flushed' );

		return true;
	}

	/**
	 * Remember value using callback.
	 *
	 * @since 1.0.0
	 * @param string   $key        Cache key.
	 * @param callable $callback   Callback to generate value.
	 * @param int      $expiration Optional. Expiration time in seconds.
	 * @return mixed Cached or generated value.
	 */
	public function remember( $key, $callback, $expiration = null ) {
		$value = $this->get( $key );

		if ( false !== $value ) {
			return $value;
		}

		// Generate value using callback.
		$value = call_user_func( $callback );

		// Cache the value.
		$this->set( $key, $value, $expiration );

		return $value;
	}

	/**
	 * Check if caching is enabled.
	 *
	 * @since 1.0.0
	 * @return bool True if enabled, false otherwise.
	 */
	private function is_cache_enabled() {
		// Check if debug mode is enabled.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return false;
		}

		// Check plugin settings.
		$options = get_option( 'layoutberg_options', array() );
		
		return ! isset( $options['cache_enabled'] ) || $options['cache_enabled'];
	}

	/**
	 * Get file cache directory.
	 *
	 * @since 1.0.0
	 * @return string Cache directory path.
	 */
	private function get_cache_dir() {
		$upload_dir = wp_upload_dir();
		$cache_dir  = $upload_dir['basedir'] . '/layoutberg/cache';

		// Create directory if it doesn't exist.
		if ( ! file_exists( $cache_dir ) ) {
			wp_mkdir_p( $cache_dir );
			
			// Add index.php to prevent directory listing.
			file_put_contents( $cache_dir . '/index.php', '<?php // Silence is golden.' );
		}

		return $cache_dir;
	}

	/**
	 * Get file cache.
	 *
	 * @since 1.0.0
	 * @param string $key Cache key.
	 * @return mixed|false Cached value or false if not found.
	 */
	private function get_file_cache( $key ) {
		$cache_file = $this->get_cache_dir() . '/' . md5( $key ) . '.cache';

		if ( ! file_exists( $cache_file ) ) {
			return false;
		}

		$data = file_get_contents( $cache_file );
		if ( false === $data ) {
			return false;
		}

		$cache_data = unserialize( $data );
		if ( false === $cache_data ) {
			return false;
		}

		// Check expiration.
		if ( isset( $cache_data['expiration'] ) && time() > $cache_data['expiration'] ) {
			unlink( $cache_file );
			return false;
		}

		return isset( $cache_data['value'] ) ? $cache_data['value'] : false;
	}

	/**
	 * Set file cache.
	 *
	 * @since 1.0.0
	 * @param string $key        Cache key.
	 * @param mixed  $value      Value to cache.
	 * @param int    $expiration Expiration time in seconds.
	 * @return bool True on success, false on failure.
	 */
	private function set_file_cache( $key, $value, $expiration ) {
		$cache_file = $this->get_cache_dir() . '/' . md5( $key ) . '.cache';

		$cache_data = array(
			'value'      => $value,
			'expiration' => time() + $expiration,
		);

		return false !== file_put_contents( $cache_file, serialize( $cache_data ) );
	}

	/**
	 * Delete file cache.
	 *
	 * @since 1.0.0
	 * @param string $key Cache key.
	 * @return bool True on success, false on failure.
	 */
	private function delete_file_cache( $key ) {
		$cache_file = $this->get_cache_dir() . '/' . md5( $key ) . '.cache';

		if ( file_exists( $cache_file ) ) {
			return unlink( $cache_file );
		}

		return true;
	}

	/**
	 * Clear all file cache.
	 *
	 * @since 1.0.0
	 */
	private function clear_file_cache() {
		$cache_dir = $this->get_cache_dir();
		$files     = glob( $cache_dir . '/*.cache' );

		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				unlink( $file );
			}
		}
	}

	/**
	 * Clear all transients.
	 *
	 * @since 1.0.0
	 */
	private function clear_transients() {
		global $wpdb;

		// Delete all transients with our prefix.
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_layoutberg_%' 
			OR option_name LIKE '_transient_timeout_layoutberg_%'"
		);
	}

	/**
	 * Get cache statistics.
	 *
	 * @since 1.0.0
	 * @return array Cache statistics.
	 */
	public function get_stats() {
		global $wpdb;

		// Count transients.
		$transient_count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_layoutberg_%'"
		);

		// Count file cache.
		$cache_dir  = $this->get_cache_dir();
		$file_count = count( glob( $cache_dir . '/*.cache' ) );

		// Calculate file cache size.
		$file_size = 0;
		$files     = glob( $cache_dir . '/*.cache' );
		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				$file_size += filesize( $file );
			}
		}

		return array(
			'memory_cache_count' => count( $this->memory_cache ),
			'transient_count'    => intval( $transient_count ),
			'file_cache_count'   => $file_count,
			'file_cache_size'    => size_format( $file_size ),
			'object_cache'       => wp_using_ext_object_cache() ? 'enabled' : 'disabled',
		);
	}

	/**
	 * Preload cache with commonly used data.
	 *
	 * @since 1.0.0
	 */
	public function preload() {
		// Preload templates.
		$template_manager = new Template_Manager();
		$templates        = $template_manager->get_templates( array( 'per_page' => 10 ) );
		
		$this->set( 'layoutberg_popular_templates', $templates, DAY_IN_SECONDS );

		// Preload predefined templates.
		$block_generator = new Block_Generator();
		$predefined      = $block_generator->get_predefined_templates();
		
		$this->set( 'layoutberg_predefined_templates', $predefined, WEEK_IN_SECONDS );

		// Fire action hook.
		do_action( 'layoutberg_cache_preloaded' );
	}

	/**
	 * Clean expired cache entries.
	 *
	 * @since 1.0.0
	 * @return int Number of expired entries cleaned.
	 */
	public function clean_expired() {
		$cleaned = 0;
		
		// Clean file cache.
		$cache_dir = $this->get_cache_dir();
		$files     = glob( $cache_dir . '/*.cache' );
		
		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				$data = file_get_contents( $file );
				if ( false !== $data ) {
					$cache_data = unserialize( $data );
					if ( isset( $cache_data['expiration'] ) && time() > $cache_data['expiration'] ) {
						unlink( $file );
						$cleaned++;
					}
				}
			}
		}
		
		// Clean expired transients.
		global $wpdb;
		
		$expired_transients = $wpdb->get_col(
			"SELECT option_name FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_timeout_layoutberg_%' 
			AND option_value < UNIX_TIMESTAMP()"
		);
		
		foreach ( $expired_transients as $timeout_key ) {
			$transient_key = str_replace( '_transient_timeout_', '_transient_', $timeout_key );
			delete_option( $timeout_key );
			delete_option( $transient_key );
			$cleaned++;
		}
		
		return $cleaned;
	}

	/**
	 * Delete cache entries by pattern.
	 *
	 * @since 1.0.0
	 * @param string $pattern Cache key pattern (supports wildcards).
	 * @return int Number of entries deleted.
	 */
	public function delete_by_pattern( $pattern ) {
		$deleted = 0;
		
		// Handle file cache.
		$cache_dir = $this->get_cache_dir();
		$files     = glob( $cache_dir . '/*.cache' );
		
		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				$filename = basename( $file, '.cache' );
				
				// Check if this file matches any of our cached keys.
				foreach ( $this->memory_cache as $key => $value ) {
					if ( fnmatch( $pattern, $key ) && md5( $key ) === $filename ) {
						unlink( $file );
						unset( $this->memory_cache[ $key ] );
						$deleted++;
						break;
					}
				}
			}
		}
		
		// Handle transients.
		global $wpdb;
		
		$like_pattern = str_replace( array( '*', '?' ), array( '%', '_' ), $pattern );
		$transients = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} 
				WHERE option_name LIKE %s",
				'_transient_' . $like_pattern
			)
		);
		
		foreach ( $transients as $transient_key ) {
			$key = str_replace( '_transient_', '', $transient_key );
			delete_transient( $key );
			$deleted++;
		}
		
		return $deleted;
	}

	/**
	 * Get cache usage statistics.
	 *
	 * @since 1.0.0
	 * @return array Detailed cache usage statistics.
	 */
	public function get_usage_stats() {
		$stats = $this->get_stats();
		
		// Add hit/miss ratios if available.
		$hit_count = get_option( 'layoutberg_cache_hits', 0 );
		$miss_count = get_option( 'layoutberg_cache_misses', 0 );
		$total_requests = $hit_count + $miss_count;
		
		$stats['hit_count'] = $hit_count;
		$stats['miss_count'] = $miss_count;
		$stats['hit_ratio'] = $total_requests > 0 ? round( ( $hit_count / $total_requests ) * 100, 2 ) : 0;
		
		// Add cache effectiveness metrics.
		$stats['cache_effectiveness'] = $this->calculate_cache_effectiveness();
		
		return $stats;
	}

	/**
	 * Track cache hit.
	 *
	 * @since 1.0.0
	 */
	public function track_hit() {
		$hits = get_option( 'layoutberg_cache_hits', 0 );
		update_option( 'layoutberg_cache_hits', $hits + 1 );
	}

	/**
	 * Track cache miss.
	 *
	 * @since 1.0.0
	 */
	public function track_miss() {
		$misses = get_option( 'layoutberg_cache_misses', 0 );
		update_option( 'layoutberg_cache_misses', $misses + 1 );
	}

	/**
	 * Calculate cache effectiveness.
	 *
	 * @since 1.0.0
	 * @return array Cache effectiveness metrics.
	 */
	private function calculate_cache_effectiveness() {
		$cache_dir = $this->get_cache_dir();
		$files = glob( $cache_dir . '/*.cache' );
		
		$total_size = 0;
		$total_age = 0;
		$count = 0;
		
		if ( is_array( $files ) ) {
			$current_time = time();
			foreach ( $files as $file ) {
				$total_size += filesize( $file );
				$total_age += $current_time - filemtime( $file );
				$count++;
			}
		}
		
		return array(
			'average_entry_size' => $count > 0 ? round( $total_size / $count ) : 0,
			'average_entry_age' => $count > 0 ? round( $total_age / $count ) : 0,
			'total_entries' => $count,
			'total_size_bytes' => $total_size,
		);
	}

	/**
	 * Optimize cache performance.
	 *
	 * @since 1.0.0
	 * @return array Optimization results.
	 */
	public function optimize() {
		$results = array(
			'expired_cleaned' => 0,
			'fragmentation_reduced' => false,
			'preload_refreshed' => false,
		);
		
		// Clean expired entries.
		$results['expired_cleaned'] = $this->clean_expired();
		
		// Defragment file cache if needed.
		if ( $results['expired_cleaned'] > 10 ) {
			$this->defragment_file_cache();
			$results['fragmentation_reduced'] = true;
		}
		
		// Refresh preloaded data.
		$this->preload();
		$results['preload_refreshed'] = true;
		
		return $results;
	}

	/**
	 * Defragment file cache by reorganizing cache files.
	 *
	 * @since 1.0.0
	 */
	private function defragment_file_cache() {
		$cache_dir = $this->get_cache_dir();
		$files = glob( $cache_dir . '/*.cache' );
		
		if ( ! is_array( $files ) || count( $files ) < 100 ) {
			return; // Not worth defragmenting.
		}
		
		// Create temporary directory.
		$temp_dir = $cache_dir . '/temp_' . time();
		wp_mkdir_p( $temp_dir );
		
		// Move valid cache files to temp directory.
		$current_time = time();
		foreach ( $files as $file ) {
			$data = file_get_contents( $file );
			if ( false !== $data ) {
				$cache_data = unserialize( $data );
				if ( isset( $cache_data['expiration'] ) && $current_time <= $cache_data['expiration'] ) {
					$new_file = $temp_dir . '/' . basename( $file );
					rename( $file, $new_file );
				} else {
					unlink( $file );
				}
			}
		}
		
		// Remove old cache files and move temp files back.
		$remaining_files = glob( $cache_dir . '/*.cache' );
		if ( is_array( $remaining_files ) ) {
			foreach ( $remaining_files as $file ) {
				unlink( $file );
			}
		}
		
		$temp_files = glob( $temp_dir . '/*.cache' );
		if ( is_array( $temp_files ) ) {
			foreach ( $temp_files as $file ) {
				$new_file = $cache_dir . '/' . basename( $file );
				rename( $file, $new_file );
			}
		}
		
		// Remove temp directory.
		rmdir( $temp_dir );
	}
}