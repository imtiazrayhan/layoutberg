<?php
/**
 * Test Template Manager class.
 *
 * @package LayoutBerg
 */

namespace DotCamp\LayoutBerg\Tests;

use DotCamp\LayoutBerg\Template_Manager;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Mockery;

/**
 * Test Template Manager functionality.
 */
class Test_Template_Manager extends TestCase {

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		
		// Mock global $wpdb
		global $wpdb;
		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->andReturn( 'prepared_query' );
		$wpdb->shouldReceive( 'get_results' )->andReturn( array() );
		$wpdb->shouldReceive( 'get_var' )->andReturn( 0 );
	}

	/**
	 * Tear down test environment.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test Template_Manager instantiation.
	 */
	public function test_template_manager_instantiation() {
		$manager = new Template_Manager();
		$this->assertInstanceOf( Template_Manager::class, $manager );
	}

	/**
	 * Test get_templates returns array.
	 */
	public function test_get_templates_returns_array() {
		$manager = new Template_Manager();
		
		$result = $manager->get_templates();
		
		$this->assertIsArray( $result );
	}

	/**
	 * Test generate_unique_slug.
	 */
	public function test_generate_unique_slug() {
		$manager = new Template_Manager();
		
		// Mock sanitize_title function
		Monkey\Functions\when( 'sanitize_title' )->alias( function( $title ) {
			return strtolower( str_replace( ' ', '-', $title ) );
		});
		
		$slug = $manager->generate_unique_slug( 'Test Template Name' );
		
		$this->assertIsString( $slug );
		$this->assertEquals( 'test-template-name', $slug );
	}

	/**
	 * Test validate_template_data with valid data.
	 */
	public function test_validate_template_data_valid() {
		$manager = new Template_Manager();
		
		$valid_data = array(
			'name'    => 'Test Template',
			'content' => '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->',
		);
		
		$result = $manager->validate_template_data( $valid_data );
		
		$this->assertTrue( $result );
	}

	/**
	 * Test validate_template_data with missing required fields.
	 */
	public function test_validate_template_data_missing_fields() {
		$manager = new Template_Manager();
		
		$invalid_data = array(
			'name' => 'Test Template',
			// Missing content
		);
		
		$result = $manager->validate_template_data( $invalid_data );
		
		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	/**
	 * Test prepare_template_for_export.
	 */
	public function test_prepare_template_for_export() {
		$manager = new Template_Manager();
		
		// Mock get_userdata function
		Monkey\Functions\when( 'get_userdata' )->justReturn( (object) array( 'display_name' => 'Test User' ) );
		
		$template = array(
			'id'         => 1,
			'name'       => 'Test Template',
			'content'    => '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->',
			'created_by' => 1,
			'created_at' => '2024-01-01 00:00:00',
		);
		
		$result = $manager->prepare_template_for_export( $template );
		
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'author', $result );
		$this->assertArrayNotHasKey( 'created_by', $result );
	}

	/**
	 * Test get_popular_templates.
	 */
	public function test_get_popular_templates() {
		$manager = new Template_Manager();
		
		global $wpdb;
		$wpdb->shouldReceive( 'get_results' )
		     ->with( Mockery::type( 'string' ) )
		     ->andReturn( array() );
		
		$result = $manager->get_popular_templates( 5 );
		
		$this->assertIsArray( $result );
	}

	/**
	 * Test get_templates_by_category.
	 */
	public function test_get_templates_by_category() {
		$manager = new Template_Manager();
		
		global $wpdb;
		$wpdb->shouldReceive( 'get_results' )
		     ->with( Mockery::type( 'string' ) )
		     ->andReturn( array() );
		
		$result = $manager->get_templates_by_category( 'landing' );
		
		$this->assertIsArray( $result );
	}
}