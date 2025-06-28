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
}