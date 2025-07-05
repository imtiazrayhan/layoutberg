<?php
/**
 * Model configuration class for LayoutBerg.
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
 * Model configuration class.
 *
 * @since 1.0.0
 */
class Model_Config {

	/**
	 * Model configurations with accurate limits
	 */
	const MODELS = array(
		// OpenAI Models
		'gpt-3.5-turbo' => array(
			'provider' => 'openai',
			'name' => 'GPT-3.5 Turbo',
			'description' => 'Fast and affordable',
			'context_window' => 16385,
			'max_output' => 4096,
			'cost_per_1k_input' => 0.0005,
			'cost_per_1k_output' => 0.0015,
			'supports_json_mode' => true,
			'supports_functions' => true,
		),
		'gpt-4' => array(
			'provider' => 'openai',
			'name' => 'GPT-4',
			'description' => 'Most capable for complex tasks',
			'context_window' => 8192,
			'max_output' => 4096,
			'cost_per_1k_input' => 0.03,
			'cost_per_1k_output' => 0.06,
			'supports_json_mode' => true,
			'supports_functions' => true,
		),
		'gpt-4-turbo' => array(
			'provider' => 'openai',
			'name' => 'GPT-4 Turbo',
			'description' => 'Latest model with 128k context',
			'context_window' => 128000,
			'max_output' => 4096,
			'cost_per_1k_input' => 0.01,
			'cost_per_1k_output' => 0.03,
			'supports_json_mode' => true,
			'supports_functions' => true,
		),
		'gpt-4o' => array(
			'provider' => 'openai',
			'name' => 'GPT-4 Optimized',
			'description' => 'Faster GPT-4 variant',
			'context_window' => 128000,
			'max_output' => 4096,
			'cost_per_1k_input' => 0.0025,
			'cost_per_1k_output' => 0.01,
			'supports_json_mode' => true,
			'supports_functions' => true,
		),
		
		// Claude Models
		'claude-3-opus-20240229' => array(
			'provider' => 'claude',
			'name' => 'Claude 3 Opus',
			'description' => 'Most powerful Claude model',
			'context_window' => 200000,
			'max_output' => 4096,
			'cost_per_1k_input' => 0.015,
			'cost_per_1k_output' => 0.075,
			'supports_json_mode' => false,
			'supports_functions' => false,
		),
		'claude-3-5-sonnet-20241022' => array(
			'provider' => 'claude',
			'name' => 'Claude 3.5 Sonnet',
			'description' => 'Latest balanced Claude model',
			'context_window' => 200000,
			'max_output' => 8192,
			'cost_per_1k_input' => 0.003,
			'cost_per_1k_output' => 0.015,
			'supports_json_mode' => false,
			'supports_functions' => false,
		),
		'claude-3-haiku-20240307' => array(
			'provider' => 'claude',
			'name' => 'Claude 3 Haiku',
			'description' => 'Fast and affordable Claude model',
			'context_window' => 200000,
			'max_output' => 4096,
			'cost_per_1k_input' => 0.00025,
			'cost_per_1k_output' => 0.00125,
			'supports_json_mode' => false,
			'supports_functions' => false,
		),
	);

	/**
	 * Get model configuration
	 *
	 * @since 1.0.0
	 * @param string $model_id Model identifier.
	 * @return array|null Model configuration or null if not found.
	 */
	public static function get_model( $model_id ) {
		return self::MODELS[ $model_id ] ?? null;
	}

	/**
	 * Get all models for a provider
	 *
	 * @since 1.0.0
	 * @param string $provider Provider name (openai or claude).
	 * @return array Array of models for the provider.
	 */
	public static function get_provider_models( $provider ) {
		return array_filter( self::MODELS, function( $model ) use ( $provider ) {
			return $model['provider'] === $provider;
		} );
	}

	/**
	 * Get all available models
	 *
	 * @since 1.0.0
	 * @return array All available models.
	 */
	public static function get_all_models() {
		return self::MODELS;
	}

	/**
	 * Calculate safe max tokens for generation
	 *
	 * @since 1.0.0
	 * @param string $model_id Model identifier.
	 * @param int    $prompt_tokens Number of tokens in prompt.
	 * @param int    $buffer Buffer tokens to reserve.
	 * @return int Safe maximum tokens for generation.
	 */
	public static function calculate_max_tokens( $model_id, $prompt_tokens, $buffer = 500 ) {
		$config = self::get_model( $model_id );
		if ( ! $config ) {
			return 2000; // Safe default
		}

		$available = $config['context_window'] - $prompt_tokens - $buffer;
		return min( $available, $config['max_output'] );
	}

	/**
	 * Estimate cost for a generation
	 *
	 * @since 1.0.0
	 * @param string $model_id Model identifier.
	 * @param int    $input_tokens Number of input tokens.
	 * @param int    $output_tokens Number of output tokens.
	 * @return float Estimated cost in USD.
	 */
	public static function estimate_cost( $model_id, $input_tokens, $output_tokens ) {
		$config = self::get_model( $model_id );
		if ( ! $config ) {
			return 0;
		}

		$input_cost = ( $input_tokens / 1000 ) * $config['cost_per_1k_input'];
		$output_cost = ( $output_tokens / 1000 ) * $config['cost_per_1k_output'];

		return $input_cost + $output_cost;
	}

	/**
	 * Get model display name
	 *
	 * @since 1.0.0
	 * @param string $model_id Model identifier.
	 * @return string Model display name.
	 */
	public static function get_model_name( $model_id ) {
		$config = self::get_model( $model_id );
		return $config ? $config['name'] : $model_id;
	}

	/**
	 * Get model description
	 *
	 * @since 1.0.0
	 * @param string $model_id Model identifier.
	 * @return string Model description.
	 */
	public static function get_model_description( $model_id ) {
		$config = self::get_model( $model_id );
		return $config ? $config['description'] : '';
	}

	/**
	 * Check if model supports JSON mode
	 *
	 * @since 1.0.0
	 * @param string $model_id Model identifier.
	 * @return bool True if model supports JSON mode.
	 */
	public static function supports_json_mode( $model_id ) {
		$config = self::get_model( $model_id );
		return $config ? $config['supports_json_mode'] : false;
	}

	/**
	 * Check if model supports functions
	 *
	 * @since 1.0.0
	 * @param string $model_id Model identifier.
	 * @return bool True if model supports functions.
	 */
	public static function supports_functions( $model_id ) {
		$config = self::get_model( $model_id );
		return $config ? $config['supports_functions'] : false;
	}

	/**
	 * Get models for settings page
	 *
	 * @since 1.0.0
	 * @return array Models formatted for settings dropdown.
	 */
	public static function get_models_for_settings() {
		$models = array();
		
		foreach ( self::MODELS as $model_id => $config ) {
			$models[ $model_id ] = sprintf(
				'%s - %s',
				$config['name'],
				$config['description']
			);
		}
		
		return $models;
	}

	/**
	 * Validate model ID
	 *
	 * @since 1.0.0
	 * @param string $model_id Model identifier.
	 * @return bool True if model is valid.
	 */
	public static function is_valid_model( $model_id ) {
		return isset( self::MODELS[ $model_id ] );
	}
} 