<?php
/**
 * Dependency Injection Container
 *
 * @package    LayoutBerg
 * @subpackage LayoutBerg/includes
 */

namespace DotCamp\LayoutBerg;

use Exception;

/**
 * Simple dependency injection container for managing plugin dependencies
 *
 * @since      1.0.0
 * @package    LayoutBerg
 * @subpackage LayoutBerg/includes
 * @author     DotCamp <support@dotcamp.com>
 */
class Container {

	/**
	 * The single instance of the container.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Container    $instance    The single instance of the container.
	 */
	private static $instance = null;

	/**
	 * Container bindings.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $bindings    Container bindings.
	 */
	private $bindings = array();

	/**
	 * Singleton instances.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $instances    Singleton instances.
	 */
	private $instances = array();

	/**
	 * Get the container instance.
	 *
	 * @since    1.0.0
	 * @return   Container    The container instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation.
	 *
	 * @since    1.0.0
	 */
	private function __construct() {
		// Register core services
		$this->register_core_services();
	}

	/**
	 * Bind a concrete implementation to an abstract.
	 *
	 * @since    1.0.0
	 * @param    string $abstract The abstract name.
	 * @param    mixed  $concrete The concrete implementation (class name or closure).
	 * @param    bool   $singleton Whether to treat as singleton.
	 */
	public function bind( $abstract, $concrete = null, $singleton = false ) {
		if ( null === $concrete ) {
			$concrete = $abstract;
		}

		$this->bindings[ $abstract ] = array(
			'concrete'  => $concrete,
			'singleton' => $singleton,
		);
	}

	/**
	 * Bind a singleton.
	 *
	 * @since    1.0.0
	 * @param    string $abstract The abstract name.
	 * @param    mixed  $concrete The concrete implementation.
	 */
	public function singleton( $abstract, $concrete = null ) {
		$this->bind( $abstract, $concrete, true );
	}

	/**
	 * Resolve a dependency from the container.
	 *
	 * @since    1.0.0
	 * @param    string $abstract The abstract name.
	 * @param    array  $parameters Parameters to pass to the constructor.
	 * @return   mixed The resolved instance.
	 * @throws   Exception If the dependency cannot be resolved.
	 */
	public function make( $abstract, array $parameters = array() ) {
		// If it's a singleton and already instantiated, return it
		if ( isset( $this->instances[ $abstract ] ) ) {
			return $this->instances[ $abstract ];
		}

		// Get the concrete implementation
		$concrete = $this->get_concrete( $abstract );

		// If it's a closure, execute it
		if ( $concrete instanceof \Closure ) {
			$instance = $concrete( $this, $parameters );
		} else {
			// Build the instance
			$instance = $this->build( $concrete, $parameters );
		}

		// If it's a singleton, store the instance
		if ( $this->is_singleton( $abstract ) ) {
			$this->instances[ $abstract ] = $instance;
		}

		return $instance;
	}

	/**
	 * Check if a binding exists.
	 *
	 * @since    1.0.0
	 * @param    string $abstract The abstract name.
	 * @return   bool
	 */
	public function has( $abstract ) {
		return isset( $this->bindings[ $abstract ] ) || isset( $this->instances[ $abstract ] );
	}

	/**
	 * Get the concrete implementation for an abstract.
	 *
	 * @since    1.0.0
	 * @param    string $abstract The abstract name.
	 * @return   mixed The concrete implementation.
	 */
	private function get_concrete( $abstract ) {
		if ( ! isset( $this->bindings[ $abstract ] ) ) {
			return $abstract;
		}

		return $this->bindings[ $abstract ]['concrete'];
	}

	/**
	 * Check if an abstract is registered as singleton.
	 *
	 * @since    1.0.0
	 * @param    string $abstract The abstract name.
	 * @return   bool
	 */
	private function is_singleton( $abstract ) {
		return isset( $this->bindings[ $abstract ] ) && $this->bindings[ $abstract ]['singleton'];
	}

	/**
	 * Build a concrete instance.
	 *
	 * @since    1.0.0
	 * @param    string $concrete The concrete class name.
	 * @param    array  $parameters Parameters to pass to constructor.
	 * @return   object The built instance.
	 * @throws   Exception If the class cannot be instantiated.
	 */
	private function build( $concrete, array $parameters = array() ) {
		// If the concrete class doesn't exist, throw an exception
		if ( ! class_exists( $concrete ) ) {
			throw new Exception( "Class {$concrete} does not exist" );
		}

		$reflection = new \ReflectionClass( $concrete );

		// If the class is not instantiable, throw an exception
		if ( ! $reflection->isInstantiable() ) {
			throw new Exception( "Class {$concrete} is not instantiable" );
		}

		$constructor = $reflection->getConstructor();

		// If there is no constructor, just instantiate the class
		if ( null === $constructor ) {
			return new $concrete();
		}

		$dependencies = $constructor->getParameters();
		$instances    = $this->resolve_dependencies( $dependencies, $parameters );

		return $reflection->newInstanceArgs( $instances );
	}

	/**
	 * Resolve constructor dependencies.
	 *
	 * @since    1.0.0
	 * @param    array $dependencies The constructor parameters.
	 * @param    array $parameters Manual parameters.
	 * @return   array The resolved dependencies.
	 * @throws   Exception If a dependency cannot be resolved.
	 */
	private function resolve_dependencies( array $dependencies, array $parameters ) {
		$results = array();

		foreach ( $dependencies as $dependency ) {
			// If an explicit parameter was passed, use it
			if ( array_key_exists( $dependency->getName(), $parameters ) ) {
				$results[] = $parameters[ $dependency->getName() ];
				continue;
			}

			// Try to resolve the type-hinted class
			$type = $dependency->getType();
			if ( $type && ! $type->isBuiltin() ) {
				try {
					$results[] = $this->make( $type->getName() );
				} catch ( Exception $e ) {
					// If we can't resolve it and it has a default value, use that
					if ( $dependency->isDefaultValueAvailable() ) {
						$results[] = $dependency->getDefaultValue();
					} else {
						throw new Exception( "Cannot resolve dependency {$dependency->getName()}" );
					}
				}
			} elseif ( $dependency->isDefaultValueAvailable() ) {
				// Use default value if available
				$results[] = $dependency->getDefaultValue();
			} else {
				throw new Exception( "Cannot resolve dependency {$dependency->getName()}" );
			}
		}

		return $results;
	}

	/**
	 * Register core services in the container.
	 *
	 * @since    1.0.0
	 */
	private function register_core_services() {
		// Register the loader as a singleton
		$this->singleton(
			'DotCamp\LayoutBerg\Loader',
			function () {
				return new Loader();
			}
		);

		// Register API client as singleton
		$this->singleton(
			'DotCamp\LayoutBerg\API_Client',
			function () {
				return new API_Client();
			}
		);

		// Register cache manager as singleton
		$this->singleton(
			'DotCamp\LayoutBerg\Cache_Manager',
			function () {
				return new Cache_Manager();
			}
		);

		// Register template manager as singleton
		$this->singleton(
			'DotCamp\LayoutBerg\Template_Manager',
			function () {
				return new Template_Manager();
			}
		);

		// Register block generator
		$this->bind(
			'DotCamp\LayoutBerg\Block_Generator',
			function ( $container ) {
				return new Block_Generator(
					$container->make( 'DotCamp\LayoutBerg\API_Client' ),
					$container->make( 'DotCamp\LayoutBerg\Cache_Manager' ),
					$container->make( 'DotCamp\LayoutBerg\Pattern_Variations' ),
					$container->make( 'DotCamp\LayoutBerg\Block_Variations' ),
					$container->make( 'DotCamp\LayoutBerg\Content_Randomizer' )
				);
			}
		);

		// Register security manager as singleton
		$this->singleton(
			'DotCamp\LayoutBerg\Security_Manager',
			function () {
				return new Security_Manager();
			}
		);

		// Register admin class
		$this->bind(
			'DotCamp\LayoutBerg\Admin',
			function ( $container ) {
				$plugin_name = defined( 'LAYOUTBERG_PLUGIN_NAME' ) ? LAYOUTBERG_PLUGIN_NAME : 'layoutberg';
				$version     = defined( 'LAYOUTBERG_VERSION' ) ? LAYOUTBERG_VERSION : '1.0.0';

				return new Admin( $plugin_name, $version );
			}
		);

		// Register public class
		$this->bind(
			'DotCamp\LayoutBerg\PublicFacing',
			function ( $container ) {
				$plugin_name = defined( 'LAYOUTBERG_PLUGIN_NAME' ) ? LAYOUTBERG_PLUGIN_NAME : 'layoutberg';
				$version     = defined( 'LAYOUTBERG_VERSION' ) ? LAYOUTBERG_VERSION : '1.0.0';

				return new PublicFacing( $plugin_name, $version );
			}
		);

		// Register API handler
		$this->bind(
			'DotCamp\LayoutBerg\API_Handler',
			function ( $container ) {
				return new API_Handler(
					$container->make( 'DotCamp\LayoutBerg\Block_Generator' ),
					$container->make( 'DotCamp\LayoutBerg\Template_Manager' ),
					$container->make( 'DotCamp\LayoutBerg\Security_Manager' )
				);
			}
		);

		// Register variation classes as singletons
		$this->singleton(
			'DotCamp\LayoutBerg\Pattern_Variations',
			function () {
				return new Pattern_Variations();
			}
		);

		$this->singleton(
			'DotCamp\LayoutBerg\Block_Variations',
			function () {
				return new Block_Variations();
			}
		);

		$this->singleton(
			'DotCamp\LayoutBerg\Content_Randomizer',
			function () {
				return new Content_Randomizer();
			}
		);
	}

	/**
	 * Magic method to get services using property access.
	 *
	 * @since    1.0.0
	 * @param    string $name The service name.
	 * @return   mixed The service instance.
	 */
	public function __get( $name ) {
		return $this->make( $name );
	}

	/**
	 * Reset the container (mainly for testing).
	 *
	 * @since    1.0.0
	 */
	public function reset() {
		$this->bindings  = array();
		$this->instances = array();
		$this->register_core_services();
	}
}
