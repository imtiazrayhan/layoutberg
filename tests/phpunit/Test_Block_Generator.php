<?php
/**
 * Test Block Generator class.
 *
 * @package LayoutBerg
 */

namespace DotCamp\LayoutBerg\Tests;

use DotCamp\LayoutBerg\Block_Generator;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Mockery;

/**
 * Test Block Generator functionality.
 */
class Test_Block_Generator extends TestCase {

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
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
	 * Test Block_Generator instantiation.
	 */
	public function test_block_generator_instantiation() {
		$generator = new Block_Generator();
		$this->assertInstanceOf( Block_Generator::class, $generator );
	}

	/**
	 * Test parse_ai_response with valid JSON.
	 */
	public function test_parse_ai_response_valid_json() {
		$generator = new Block_Generator();
		
		$valid_json = '{"blocks": "<!-- wp:paragraph --><p>Test content</p><!-- /wp:paragraph -->"}';
		
		$result = $generator->parse_ai_response( $valid_json );
		
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'blocks', $result );
		$this->assertStringContainsString( 'wp:paragraph', $result['blocks'] );
	}

	/**
	 * Test parse_ai_response with invalid JSON.
	 */
	public function test_parse_ai_response_invalid_json() {
		$generator = new Block_Generator();
		
		$invalid_json = 'This is not JSON';
		
		$result = $generator->parse_ai_response( $invalid_json );
		
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'invalid_json', $result->get_error_code() );
	}

	/**
	 * Test validate_block_structure with valid block.
	 */
	public function test_validate_block_structure_valid() {
		$generator = new Block_Generator();
		
		$valid_block = '<!-- wp:paragraph --><p>Test content</p><!-- /wp:paragraph -->';
		
		$result = $generator->validate_block_structure( $valid_block );
		
		$this->assertTrue( $result );
	}

	/**
	 * Test validate_block_structure with invalid block.
	 */
	public function test_validate_block_structure_invalid() {
		$generator = new Block_Generator();
		
		$invalid_block = '<p>Just plain HTML without block markers</p>';
		
		$result = $generator->validate_block_structure( $invalid_block );
		
		$this->assertFalse( $result );
	}

	/**
	 * Test sanitize_blocks with various content.
	 */
	public function test_sanitize_blocks() {
		$generator = new Block_Generator();
		
		// Mock wp_kses_post function
		Monkey\Functions\when( 'wp_kses_post' )->returnArg();
		
		$blocks = '<!-- wp:paragraph --><p>Safe content</p><!-- /wp:paragraph -->';
		
		$result = $generator->sanitize_blocks( $blocks );
		
		$this->assertIsString( $result );
		$this->assertStringContainsString( 'wp:paragraph', $result );
	}

	/**
	 * Test generate_fallback_content.
	 */
	public function test_generate_fallback_content() {
		$generator = new Block_Generator();
		
		// Mock translation function
		Monkey\Functions\when( '__' )->returnArg();
		
		$prompt = 'Create a hero section';
		
		$result = $generator->generate_fallback_content( $prompt );
		
		$this->assertIsString( $result );
		$this->assertStringContainsString( 'wp:paragraph', $result );
		$this->assertStringContainsString( $prompt, $result );
	}

	/**
	 * Test count_blocks.
	 */
	public function test_count_blocks() {
		$generator = new Block_Generator();
		
		$blocks = '<!-- wp:paragraph --><p>First</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Second</h2><!-- /wp:heading -->';
		
		$count = $generator->count_blocks( $blocks );
		
		$this->assertEquals( 2, $count );
	}

	/**
	 * Test extract_block_types.
	 */
	public function test_extract_block_types() {
		$generator = new Block_Generator();
		
		$blocks = '<!-- wp:paragraph --><p>Text</p><!-- /wp:paragraph --><!-- wp:core/heading --><h2>Title</h2><!-- /wp:heading -->';
		
		$types = $generator->extract_block_types( $blocks );
		
		$this->assertIsArray( $types );
		$this->assertContains( 'paragraph', $types );
		$this->assertContains( 'core/heading', $types );
	}
}