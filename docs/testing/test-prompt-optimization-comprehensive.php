<?php
/**
 * Comprehensive test scenarios for prompt engineering optimization.
 *
 * @package    LayoutBerg
 * @subpackage Tests
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __DIR__ ) . '/includes/class-prompt-engineer.php';

use DotCamp\LayoutBerg\Prompt_Engineer;

/**
 * Comprehensive test scenarios for the optimized prompt engineer.
 */
class Test_Prompt_Optimization_Comprehensive {

	/**
	 * Prompt engineer instance.
	 *
	 * @var Prompt_Engineer
	 */
	private $prompt_engineer;

	/**
	 * Test results.
	 *
	 * @var array
	 */
	private $test_results = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->prompt_engineer = new Prompt_Engineer();
	}

	/**
	 * Run all test scenarios.
	 *
	 * @return array Test results.
	 */
	public function run_all_tests() {
		echo "<h1>Comprehensive Prompt Engineering Tests</h1>";
		
		// Edge case tests
		$this->test_edge_cases();
		
		// Quality validation tests
		$this->test_quality_validation();
		
		// Performance tests
		$this->test_performance();
		
		// Template matching tests
		$this->test_template_matching();
		
		// Block detection accuracy tests
		$this->test_block_detection_accuracy();
		
		// Complexity analysis tests
		$this->test_complexity_analysis();
		
		// Token usage validation
		$this->test_token_usage_validation();
		
		// Generate summary
		$this->generate_test_summary();
		
		return $this->test_results;
	}

	/**
	 * Test edge cases and boundary conditions.
	 */
	private function test_edge_cases() {
		echo "<h2>Testing Edge Cases</h2>";
		
		$edge_cases = array(
			'empty_prompt' => '',
			'very_short' => 'Hi',
			'very_long' => str_repeat( 'Create a hero section with lots of content and many features and services and testimonials and pricing and contact forms and galleries and videos and everything else you can think of because I want a very comprehensive website with all possible features included. ', 20 ),
			'special_characters' => 'Create a héro sectión with ñ and émojis 🚀 and spëcial chars!',
			'all_caps' => 'CREATE A HERO SECTION WITH BUTTONS AND FEATURES',
			'mixed_case' => 'CrEaTe A hErO sEcTiOn WiTh BuTtOnS',
			'numbers_only' => '123 456 789',
			'html_injection' => 'Create a <script>alert("test")</script> hero section',
			'sql_injection' => "Create a hero'; DROP TABLE users; --",
			'unicode' => 'Créer une section héroïque avec des boutons 中文 العربية',
		);
		
		foreach ( $edge_cases as $case_name => $prompt ) {
			$this->test_single_scenario( 
				"Edge Case: {$case_name}", 
				$prompt, 
				array( 'expects_error' => in_array( $case_name, array( 'empty_prompt', 'very_short', 'numbers_only' ) ) )
			);
		}
	}

	/**
	 * Test quality validation scenarios.
	 */
	private function test_quality_validation() {
		echo "<h2>Testing Quality Validation</h2>";
		
		$quality_scenarios = array(
			'basic_hero' => array(
				'prompt' => 'Create a hero section with a title and button',
				'expected_blocks' => array( 'heading', 'cover', 'buttons' ),
				'expected_complexity' => 'simple',
				'expected_template' => 'hero',
			),
			'complex_landing' => array(
				'prompt' => 'Create a complete landing page with hero, features, testimonials, pricing, and contact form',
				'expected_blocks' => array( 'heading', 'cover', 'buttons', 'columns', 'quote', 'list' ),
				'expected_complexity' => 'complex',
				'expected_template' => 'combined',
			),
			'pricing_table' => array(
				'prompt' => 'Create a pricing table with 3 plans',
				'expected_blocks' => array( 'columns', 'list', 'buttons', 'pricing' ),
				'expected_complexity' => 'moderate',
				'expected_template' => 'pricing',
			),
			'features_grid' => array(
				'prompt' => 'Create a features section with 4 services',
				'expected_blocks' => array( 'heading', 'columns', 'paragraph' ),
				'expected_complexity' => 'moderate',
				'expected_template' => 'features',
			),
			'testimonials_section' => array(
				'prompt' => 'Create testimonials from our happy customers',
				'expected_blocks' => array( 'quote', 'columns' ),
				'expected_complexity' => 'simple',
				'expected_template' => 'testimonials',
			),
		);
		
		foreach ( $quality_scenarios as $scenario_name => $scenario ) {
			$this->test_quality_scenario( $scenario_name, $scenario );
		}
	}

	/**
	 * Test performance scenarios.
	 */
	private function test_performance() {
		echo "<h2>Testing Performance</h2>";
		
		$performance_scenarios = array(
			'simple_prompt' => 'Create a hero section',
			'moderate_prompt' => 'Create a features section with 3 columns and buttons',
			'complex_prompt' => 'Create a complete landing page with hero, features, testimonials, pricing, FAQ, and contact section',
		);
		
		foreach ( $performance_scenarios as $scenario_name => $prompt ) {
			$start_time = microtime( true );
			$result = $this->prompt_engineer->build_system_prompt( array( 'prompt' => $prompt ) );
			$end_time = microtime( true );
			
			$execution_time = ( $end_time - $start_time ) * 1000; // Convert to milliseconds
			$token_count = $this->prompt_engineer->estimate_token_count( $result );
			
			$this->test_results['performance'][ $scenario_name ] = array(
				'execution_time_ms' => round( $execution_time, 2 ),
				'token_count' => $token_count,
				'prompt_length' => strlen( $result ),
				'status' => $execution_time < 100 ? 'PASS' : 'SLOW', // Should be under 100ms
			);
			
			echo "<p><strong>{$scenario_name}:</strong> {$execution_time}ms, {$token_count} tokens - " . 
				( $execution_time < 100 ? '✅ FAST' : '⚠️ SLOW' ) . "</p>";
		}
	}

	/**
	 * Test template matching accuracy.
	 */
	private function test_template_matching() {
		echo "<h2>Testing Template Matching</h2>";
		
		$template_tests = array(
			// Hero templates
			'hero_basic' => array( 'prompt' => 'Create a hero section', 'expected' => 'hero' ),
			'hero_with_headline' => array( 'prompt' => 'Create a headline with button', 'expected' => 'hero' ),
			'banner_section' => array( 'prompt' => 'Create a banner section', 'expected' => 'hero' ),
			
			// Features templates
			'features_basic' => array( 'prompt' => 'Create a features grid', 'expected' => 'features' ),
			'services_section' => array( 'prompt' => 'Create our services section', 'expected' => 'features' ),
			'benefits_grid' => array( 'prompt' => 'Show our benefits in columns', 'expected' => 'features' ),
			
			// Testimonials templates
			'testimonials_basic' => array( 'prompt' => 'Create testimonials section', 'expected' => 'testimonials' ),
			'customer_reviews' => array( 'prompt' => 'Show customer reviews', 'expected' => 'testimonials' ),
			'feedback_section' => array( 'prompt' => 'Display client feedback', 'expected' => 'testimonials' ),
			
			// Pricing templates
			'pricing_basic' => array( 'prompt' => 'Create pricing table', 'expected' => 'pricing' ),
			'subscription_plans' => array( 'prompt' => 'Show subscription tiers', 'expected' => 'pricing' ),
			'package_options' => array( 'prompt' => 'Display our packages', 'expected' => 'pricing' ),
			
			// CTA templates
			'cta_basic' => array( 'prompt' => 'Create call to action', 'expected' => 'cta' ),
			'get_started' => array( 'prompt' => 'Add get started section', 'expected' => 'cta' ),
			'contact_cta' => array( 'prompt' => 'Create contact us section', 'expected' => 'cta' ),
			
			// Combined templates
			'landing_page' => array( 'prompt' => 'Create a complete landing page', 'expected' => 'combined' ),
			'full_website' => array( 'prompt' => 'Build entire page with all sections', 'expected' => 'combined' ),
			'hero_and_features' => array( 'prompt' => 'Create hero and features sections', 'expected' => 'combined' ),
			
			// No template match
			'simple_paragraph' => array( 'prompt' => 'Create a simple text paragraph', 'expected' => null ),
			'just_image' => array( 'prompt' => 'Add an image', 'expected' => null ),
		);
		
		$correct_matches = 0;
		$total_tests = count( $template_tests );
		
		foreach ( $template_tests as $test_name => $test_data ) {
			$prompt = $test_data['prompt'];
			$expected = $test_data['expected'];
			
			// Use reflection to access private method
			$reflection = new ReflectionClass( $this->prompt_engineer );
			$method = $reflection->getMethod( 'analyze_user_prompt' );
			$method->setAccessible( true );
			
			$analysis = $method->invoke( $this->prompt_engineer, $prompt );
			$detected_template = $analysis['template'];
			
			$is_correct = $detected_template === $expected;
			if ( $is_correct ) {
				$correct_matches++;
			}
			
			$this->test_results['template_matching'][ $test_name ] = array(
				'prompt' => $prompt,
				'expected' => $expected,
				'detected' => $detected_template,
				'status' => $is_correct ? 'PASS' : 'FAIL',
			);
			
			echo "<p><strong>{$test_name}:</strong> Expected '{$expected}', Got '{$detected_template}' - " . 
				( $is_correct ? '✅ PASS' : '❌ FAIL' ) . "</p>";
		}
		
		$accuracy = round( ( $correct_matches / $total_tests ) * 100, 1 );
		echo "<p><strong>Template Matching Accuracy: {$accuracy}% ({$correct_matches}/{$total_tests})</strong></p>";
		
		$this->test_results['template_matching']['accuracy'] = $accuracy;
	}

	/**
	 * Test block detection accuracy.
	 */
	private function test_block_detection_accuracy() {
		echo "<h2>Testing Block Detection Accuracy</h2>";
		
		$block_tests = array(
			'hero_blocks' => array(
				'prompt' => 'Create a hero section with title, description, and button',
				'expected' => array( 'heading', 'cover', 'buttons', 'paragraph' ),
			),
			'features_blocks' => array(
				'prompt' => 'Create features section with images and columns',
				'expected' => array( 'heading', 'columns', 'image' ),
			),
			'pricing_blocks' => array(
				'prompt' => 'Create pricing table with lists and buttons',
				'expected' => array( 'pricing', 'columns', 'list', 'buttons' ),
			),
			'gallery_blocks' => array(
				'prompt' => 'Create image gallery with photos',
				'expected' => array( 'gallery', 'image' ),
			),
			'testimonial_blocks' => array(
				'prompt' => 'Create testimonials with quotes in columns',
				'expected' => array( 'quote', 'columns' ),
			),
			'faq_blocks' => array(
				'prompt' => 'Create FAQ section with expandable questions',
				'expected' => array( 'faq', 'heading', 'details' ),
			),
			'media_text_blocks' => array(
				'prompt' => 'Create side by side image and text',
				'expected' => array( 'media-text' ),
			),
			'video_blocks' => array(
				'prompt' => 'Add video section with YouTube embed',
				'expected' => array( 'video', 'heading' ),
			),
		);
		
		$total_correct = 0;
		$total_expected = 0;
		
		foreach ( $block_tests as $test_name => $test_data ) {
			$prompt = $test_data['prompt'];
			$expected_blocks = $test_data['expected'];
			
			// Use reflection to access private method
			$reflection = new ReflectionClass( $this->prompt_engineer );
			$method = $reflection->getMethod( 'analyze_user_prompt' );
			$method->setAccessible( true );
			
			$analysis = $method->invoke( $this->prompt_engineer, $prompt );
			$detected_blocks = $analysis['blocks'];
			
			$correct_blocks = array_intersect( $expected_blocks, $detected_blocks );
			$missed_blocks = array_diff( $expected_blocks, $detected_blocks );
			$extra_blocks = array_diff( $detected_blocks, $expected_blocks );
			
			$total_correct += count( $correct_blocks );
			$total_expected += count( $expected_blocks );
			
			$this->test_results['block_detection'][ $test_name ] = array(
				'prompt' => $prompt,
				'expected' => $expected_blocks,
				'detected' => $detected_blocks,
				'correct' => $correct_blocks,
				'missed' => $missed_blocks,
				'extra' => $extra_blocks,
				'accuracy' => round( ( count( $correct_blocks ) / count( $expected_blocks ) ) * 100, 1 ),
			);
			
			echo "<p><strong>{$test_name}:</strong><br>";
			echo "Expected: " . implode( ', ', $expected_blocks ) . "<br>";
			echo "Detected: " . implode( ', ', $detected_blocks ) . "<br>";
			if ( ! empty( $missed_blocks ) ) {
				echo "Missed: " . implode( ', ', $missed_blocks ) . "<br>";
			}
			if ( ! empty( $extra_blocks ) ) {
				echo "Extra: " . implode( ', ', $extra_blocks ) . "<br>";
			}
			echo "</p>";
		}
		
		$overall_accuracy = round( ( $total_correct / $total_expected ) * 100, 1 );
		echo "<p><strong>Overall Block Detection Accuracy: {$overall_accuracy}%</strong></p>";
		
		$this->test_results['block_detection']['overall_accuracy'] = $overall_accuracy;
	}

	/**
	 * Test complexity analysis accuracy.
	 */
	private function test_complexity_analysis() {
		echo "<h2>Testing Complexity Analysis</h2>";
		
		$complexity_tests = array(
			// Simple complexity
			'simple_hero' => array( 'prompt' => 'Create a simple hero section', 'expected' => 'simple' ),
			'just_button' => array( 'prompt' => 'Add just a button', 'expected' => 'simple' ),
			'single_heading' => array( 'prompt' => 'Create only a heading', 'expected' => 'simple' ),
			'basic_text' => array( 'prompt' => 'Add basic text content', 'expected' => 'simple' ),
			
			// Moderate complexity
			'hero_with_features' => array( 'prompt' => 'Create hero section and features grid', 'expected' => 'moderate' ),
			'pricing_table' => array( 'prompt' => 'Create pricing table with 3 columns', 'expected' => 'moderate' ),
			'testimonials_grid' => array( 'prompt' => 'Create testimonials in multiple columns', 'expected' => 'moderate' ),
			
			// Complex complexity
			'full_landing' => array( 'prompt' => 'Create complete landing page with all sections', 'expected' => 'complex' ),
			'comprehensive_site' => array( 'prompt' => 'Build entire website with hero, features, testimonials, pricing, FAQ, and contact', 'expected' => 'complex' ),
			'multiple_sections' => array( 'prompt' => 'Create hero, about, services, portfolio, and contact sections', 'expected' => 'complex' ),
		);
		
		$correct_complexity = 0;
		$total_tests = count( $complexity_tests );
		
		foreach ( $complexity_tests as $test_name => $test_data ) {
			$prompt = $test_data['prompt'];
			$expected = $test_data['expected'];
			
			// Use reflection to access private method
			$reflection = new ReflectionClass( $this->prompt_engineer );
			$method = $reflection->getMethod( 'analyze_user_prompt' );
			$method->setAccessible( true );
			
			$analysis = $method->invoke( $this->prompt_engineer, $prompt );
			$detected_complexity = $analysis['complexity'];
			
			$is_correct = $detected_complexity === $expected;
			if ( $is_correct ) {
				$correct_complexity++;
			}
			
			$this->test_results['complexity_analysis'][ $test_name ] = array(
				'prompt' => $prompt,
				'expected' => $expected,
				'detected' => $detected_complexity,
				'status' => $is_correct ? 'PASS' : 'FAIL',
			);
			
			echo "<p><strong>{$test_name}:</strong> Expected '{$expected}', Got '{$detected_complexity}' - " . 
				( $is_correct ? '✅ PASS' : '❌ FAIL' ) . "</p>";
		}
		
		$accuracy = round( ( $correct_complexity / $total_tests ) * 100, 1 );
		echo "<p><strong>Complexity Analysis Accuracy: {$accuracy}% ({$correct_complexity}/{$total_tests})</strong></p>";
		
		$this->test_results['complexity_analysis']['accuracy'] = $accuracy;
	}

	/**
	 * Test token usage validation.
	 */
	private function test_token_usage_validation() {
		echo "<h2>Testing Token Usage Validation</h2>";
		
		$token_tests = array(
			'simple' => 'Create a hero section',
			'moderate' => 'Create features section with 3 columns and testimonials',
			'complex' => 'Create complete landing page with hero, features, testimonials, pricing, FAQ, and contact sections',
		);
		
		$target_limits = array(
			'simple' => 500,    // Should be under 500 tokens
			'moderate' => 350,  // Should be under 350 tokens
			'complex' => 600,   // Should be under 600 tokens
		);
		
		foreach ( $token_tests as $complexity => $prompt ) {
			$result = $this->prompt_engineer->build_system_prompt( array( 'prompt' => $prompt ) );
			$token_count = $this->prompt_engineer->estimate_token_count( $result );
			$target = $target_limits[ $complexity ];
			
			$is_within_limit = $token_count <= $target;
			$efficiency = round( ( 1 - ( $token_count / $target ) ) * 100, 1 );
			
			$this->test_results['token_usage'][ $complexity ] = array(
				'prompt' => $prompt,
				'token_count' => $token_count,
				'target_limit' => $target,
				'within_limit' => $is_within_limit,
				'efficiency' => max( 0, $efficiency ),
				'status' => $is_within_limit ? 'PASS' : 'FAIL',
			);
			
			echo "<p><strong>{$complexity}:</strong> {$token_count}/{$target} tokens ({$efficiency}% under limit) - " . 
				( $is_within_limit ? '✅ PASS' : '❌ FAIL' ) . "</p>";
		}
	}

	/**
	 * Test a single scenario.
	 *
	 * @param string $name Test name.
	 * @param string $prompt Test prompt.
	 * @param array  $options Test options.
	 */
	private function test_single_scenario( $name, $prompt, $options = array() ) {
		$expects_error = $options['expects_error'] ?? false;
		
		try {
			$validation_result = $this->prompt_engineer->validate_prompt( $prompt );
			$is_valid = ! is_wp_error( $validation_result );
			
			if ( $is_valid ) {
				$result = $this->prompt_engineer->build_system_prompt( array( 'prompt' => $prompt ) );
				$token_count = $this->prompt_engineer->estimate_token_count( $result );
				
				$status = $expects_error ? 'UNEXPECTED_PASS' : 'PASS';
				$this->test_results['edge_cases'][ $name ] = array(
					'status' => $status,
					'token_count' => $token_count,
					'prompt_length' => strlen( $prompt ),
					'result_length' => strlen( $result ),
				);
			} else {
				$status = $expects_error ? 'EXPECTED_FAIL' : 'UNEXPECTED_FAIL';
				$this->test_results['edge_cases'][ $name ] = array(
					'status' => $status,
					'error' => $validation_result->get_error_message(),
				);
			}
			
			echo "<p><strong>{$name}:</strong> " . 
				( $status === 'PASS' || $status === 'EXPECTED_FAIL' ? '✅' : '❌' ) . 
				" {$status}</p>";
				
		} catch ( Exception $e ) {
			$this->test_results['edge_cases'][ $name ] = array(
				'status' => 'EXCEPTION',
				'error' => $e->getMessage(),
			);
			echo "<p><strong>{$name}:</strong> ❌ EXCEPTION - {$e->getMessage()}</p>";
		}
	}

	/**
	 * Test a quality scenario.
	 *
	 * @param string $name Scenario name.
	 * @param array  $scenario Scenario data.
	 */
	private function test_quality_scenario( $name, $scenario ) {
		$prompt = $scenario['prompt'];
		
		// Use reflection to access private method
		$reflection = new ReflectionClass( $this->prompt_engineer );
		$method = $reflection->getMethod( 'analyze_user_prompt' );
		$method->setAccessible( true );
		
		$analysis = $method->invoke( $this->prompt_engineer, $prompt );
		
		$tests_passed = 0;
		$total_tests = 0;
		
		// Test block detection
		if ( isset( $scenario['expected_blocks'] ) ) {
			$total_tests++;
			$expected_blocks = $scenario['expected_blocks'];
			$detected_blocks = $analysis['blocks'];
			$blocks_match = count( array_intersect( $expected_blocks, $detected_blocks ) ) >= ( count( $expected_blocks ) * 0.8 ); // 80% match
			if ( $blocks_match ) {
				$tests_passed++;
			}
		}
		
		// Test complexity
		if ( isset( $scenario['expected_complexity'] ) ) {
			$total_tests++;
			if ( $analysis['complexity'] === $scenario['expected_complexity'] ) {
				$tests_passed++;
			}
		}
		
		// Test template
		if ( isset( $scenario['expected_template'] ) ) {
			$total_tests++;
			if ( $analysis['template'] === $scenario['expected_template'] ) {
				$tests_passed++;
			}
		}
		
		$success_rate = $total_tests > 0 ? round( ( $tests_passed / $total_tests ) * 100, 1 ) : 0;
		
		$this->test_results['quality_validation'][ $name ] = array(
			'prompt' => $prompt,
			'analysis' => $analysis,
			'expected' => $scenario,
			'tests_passed' => $tests_passed,
			'total_tests' => $total_tests,
			'success_rate' => $success_rate,
			'status' => $success_rate >= 80 ? 'PASS' : 'FAIL',
		);
		
		echo "<p><strong>{$name}:</strong> {$tests_passed}/{$total_tests} tests passed ({$success_rate}%) - " . 
			( $success_rate >= 80 ? '✅ PASS' : '❌ FAIL' ) . "</p>";
	}

	/**
	 * Generate test summary.
	 */
	private function generate_test_summary() {
		echo "<h2>Test Summary</h2>";
		
		$categories = array(
			'edge_cases' => 'Edge Cases',
			'quality_validation' => 'Quality Validation',
			'performance' => 'Performance',
			'template_matching' => 'Template Matching',
			'block_detection' => 'Block Detection',
			'complexity_analysis' => 'Complexity Analysis',
			'token_usage' => 'Token Usage',
		);
		
		$overall_score = 0;
		$total_categories = 0;
		
		foreach ( $categories as $key => $name ) {
			if ( isset( $this->test_results[ $key ] ) ) {
				$category_results = $this->test_results[ $key ];
				$passed = 0;
				$total = 0;
				
				foreach ( $category_results as $test_key => $result ) {
					if ( $test_key === 'accuracy' || $test_key === 'overall_accuracy' ) {
						continue; // Skip accuracy summaries
					}
					
					$total++;
					if ( isset( $result['status'] ) && 
						( $result['status'] === 'PASS' || $result['status'] === 'EXPECTED_FAIL' ) ) {
						$passed++;
					} elseif ( isset( $result['success_rate'] ) && $result['success_rate'] >= 80 ) {
						$passed++;
					}
				}
				
				$category_score = $total > 0 ? round( ( $passed / $total ) * 100, 1 ) : 0;
				$overall_score += $category_score;
				$total_categories++;
				
				echo "<p><strong>{$name}:</strong> {$passed}/{$total} passed ({$category_score}%)</p>";
			}
		}
		
		$final_score = $total_categories > 0 ? round( $overall_score / $total_categories, 1 ) : 0;
		
		echo "<h3>Overall Test Score: {$final_score}%</h3>";
		
		if ( $final_score >= 90 ) {
			echo "<p style='color: green; font-weight: bold;'>🎉 EXCELLENT - System is highly optimized and reliable!</p>";
		} elseif ( $final_score >= 80 ) {
			echo "<p style='color: orange; font-weight: bold;'>⚠️ GOOD - System is well optimized with minor issues to address.</p>";
		} else {
			echo "<p style='color: red; font-weight: bold;'>❌ NEEDS IMPROVEMENT - System requires optimization work.</p>";
		}
		
		$this->test_results['summary'] = array(
			'overall_score' => $final_score,
			'total_categories' => $total_categories,
			'grade' => $final_score >= 90 ? 'EXCELLENT' : ( $final_score >= 80 ? 'GOOD' : 'NEEDS_IMPROVEMENT' ),
		);
	}
}

// Run tests if this file is accessed directly
if ( ! empty( $_GET['run_tests'] ) ) {
	$tester = new Test_Prompt_Optimization_Comprehensive();
	$results = $tester->run_all_tests();
	
	// Optionally save results to file
	if ( ! empty( $_GET['save_results'] ) ) {
		file_put_contents( 
			dirname( __FILE__ ) . '/test-results-' . date( 'Y-m-d-H-i-s' ) . '.json', 
			json_encode( $results, JSON_PRETTY_PRINT ) 
		);
		echo "<p>Results saved to file.</p>";
	}
}