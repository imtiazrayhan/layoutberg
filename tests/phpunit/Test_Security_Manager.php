<?php
/**
 * Test Security Manager class.
 *
 * @package LayoutBerg
 */

namespace DotCamp\LayoutBerg\Tests;

use DotCamp\LayoutBerg\Security_Manager;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;

/**
 * Test Security Manager functionality.
 */
class Test_Security_Manager extends TestCase {

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
		parent::tearDown();
	}

	/**
	 * Test Security_Manager instantiation.
	 */
	public function test_security_manager_instantiation() {
		$manager = new Security_Manager();
		$this->assertInstanceOf( Security_Manager::class, $manager );
	}

	/**
	 * Test encrypt_api_key and decrypt_api_key.
	 */
	public function test_api_key_encryption_decryption() {
		$manager = new Security_Manager();
		
		$original_key = 'sk-test-api-key-12345';
		
		$encrypted = $manager->encrypt_api_key( $original_key );
		$this->assertIsString( $encrypted );
		$this->assertNotEquals( $original_key, $encrypted );
		
		$decrypted = $manager->decrypt_api_key( $encrypted );
		$this->assertEquals( $original_key, $decrypted );
	}

	/**
	 * Test validate_api_key with valid key.
	 */
	public function test_validate_api_key_valid() {
		$manager = new Security_Manager();
		
		$valid_key = 'sk-test1234567890abcdef1234567890abcdef1234567890abcdef';
		
		$result = $manager->validate_api_key( $valid_key );
		
		$this->assertTrue( $result );
	}

	/**
	 * Test validate_api_key with invalid key.
	 */
	public function test_validate_api_key_invalid() {
		$manager = new Security_Manager();
		
		$invalid_key = 'invalid-key';
		
		$result = $manager->validate_api_key( $invalid_key );
		
		$this->assertFalse( $result );
	}

	/**
	 * Test sanitize_prompt.
	 */
	public function test_sanitize_prompt() {
		$manager = new Security_Manager();
		
		// Mock sanitize_textarea_field function
		Monkey\Functions\when( 'sanitize_textarea_field' )->returnArg();
		
		$prompt = "Create a hero section\nwith some content";
		
		$result = $manager->sanitize_prompt( $prompt );
		
		$this->assertIsString( $result );
		$this->assertEquals( $prompt, $result );
	}

	/**
	 * Test check_nonce with valid nonce.
	 */
	public function test_check_nonce_valid() {
		$manager = new Security_Manager();
		
		// Mock wp_verify_nonce function
		Monkey\Functions\when( 'wp_verify_nonce' )->justReturn( true );
		
		$result = $manager->check_nonce( 'test_nonce', 'test_action' );
		
		$this->assertTrue( $result );
	}

	/**
	 * Test check_nonce with invalid nonce.
	 */
	public function test_check_nonce_invalid() {
		$manager = new Security_Manager();
		
		// Mock wp_verify_nonce function
		Monkey\Functions\when( 'wp_verify_nonce' )->justReturn( false );
		
		$result = $manager->check_nonce( 'invalid_nonce', 'test_action' );
		
		$this->assertFalse( $result );
	}

	/**
	 * Test check_capability with valid capability.
	 */
	public function test_check_capability_valid() {
		$manager = new Security_Manager();
		
		// Mock current_user_can function
		Monkey\Functions\when( 'current_user_can' )->justReturn( true );
		
		$result = $manager->check_capability( 'edit_posts' );
		
		$this->assertTrue( $result );
	}

	/**
	 * Test check_capability with invalid capability.
	 */
	public function test_check_capability_invalid() {
		$manager = new Security_Manager();
		
		// Mock current_user_can function
		Monkey\Functions\when( 'current_user_can' )->justReturn( false );
		
		$result = $manager->check_capability( 'invalid_capability' );
		
		$this->assertFalse( $result );
	}

	/**
	 * Test generate_encryption_key.
	 */
	public function test_generate_encryption_key() {
		$manager = new Security_Manager();
		
		$key = $manager->generate_encryption_key();
		
		$this->assertIsString( $key );
		$this->assertEquals( 32, strlen( $key ) ); // Should be 32 bytes for AES-256
	}

	/**
	 * Test hash_data.
	 */
	public function test_hash_data() {
		$manager = new Security_Manager();
		
		$data = 'sensitive_data_to_hash';
		
		$hash = $manager->hash_data( $data );
		
		$this->assertIsString( $hash );
		$this->assertNotEquals( $data, $hash );
		$this->assertEquals( 64, strlen( $hash ) ); // SHA-256 hash length
	}
}