<?php
/**
 * Licensing and plan management helper class.
 *
 * @package LayoutBerg
 * @since 1.0.0
 */

namespace DotCamp\LayoutBerg;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LayoutBerg Licensing class.
 *
 * Provides helper methods for checking user's plan and feature access.
 *
 * @since 1.0.0
 */
class LayoutBerg_Licensing {

	/**
	 * Check if user can access premium features.
	 *
	 * This respects Freemius configuration where expired yearly plans
	 * keep their features, while expired monthly plans lose access.
	 *
	 * @since 1.0.0
	 * @return bool True if user can use premium features.
	 */
	public static function can_use_premium_code() {
		return \layoutberg_fs()->can_use_premium_code();
	}

	/**
	 * Check if user has an expired monthly subscription.
	 *
	 * @since 1.0.0
	 * @return bool True if user has expired monthly subscription.
	 */
	public static function is_expired_monthly() {
		return \layoutberg_fs()->is_registered() &&
				! \layoutberg_fs()->can_use_premium_code() &&
				! \layoutberg_fs()->is_paying();
	}

	/**
	 * Check if user is on the Starter plan.
	 *
	 * @since 1.0.0
	 * @return bool True if user is on Starter plan.
	 */
	public static function is_starter_plan() {
		if ( ! self::can_use_premium_code() ) {
			return false;
		}

		$plan = \layoutberg_fs()->get_plan();
		if ( ! $plan ) {
			return false;
		}

		// Check by plan name (case insensitive)
		if ( empty( $plan->name ) ) {
			return false;
		}
		$plan_name = strtolower( $plan->name );
		return in_array( $plan_name, array( 'starter', 'start', 'basic' ), true );
	}

	/**
	 * Check if user is on the Professional plan.
	 *
	 * @since 1.0.0
	 * @return bool True if user is on Professional plan.
	 */
	public static function is_professional_plan() {
		if ( ! self::can_use_premium_code() ) {
			return false;
		}

		$plan = \layoutberg_fs()->get_plan();
		if ( ! $plan ) {
			return false;
		}

		// Check by plan name (case insensitive)
		if ( empty( $plan->name ) ) {
			return false;
		}
		$plan_name = strtolower( $plan->name );
		return in_array( $plan_name, array( 'professional', 'pro', 'premium' ), true );
	}

	/**
	 * Check if user is on the Agency plan.
	 *
	 * @since 1.0.0
	 * @return bool True if user is on Agency plan.
	 */
	public static function is_agency_plan() {
		if ( ! self::can_use_premium_code() ) {
			return false;
		}

		$plan = \layoutberg_fs()->get_plan();
		if ( ! $plan ) {
			return false;
		}

		// Check by plan name (case insensitive)
		if ( empty( $plan->name ) ) {
			return false;
		}
		$plan_name = strtolower( $plan->name );
		return in_array( $plan_name, array( 'agency', 'business', 'enterprise', 'team' ), true );
	}

	/**
	 * Check if user can use all AI models.
	 *
	 * @since 1.0.0
	 * @return bool True if user can access all AI models.
	 */
	public static function can_use_all_models() {
		return self::can_use_premium_code() &&
				( self::is_professional_plan() || self::is_agency_plan() );
	}

	/**
	 * Check if user can export templates.
	 *
	 * @since 1.0.0
	 * @return bool True if user can export templates.
	 */
	public static function can_export_templates() {
		return self::can_use_premium_code() &&
				( self::is_professional_plan() || self::is_agency_plan() );
	}

	/**
	 * Check if user can export CSV.
	 *
	 * @since 1.0.0
	 * @return bool True if user can export CSV.
	 */
	public static function can_export_csv() {
		return self::can_use_premium_code() && self::is_agency_plan();
	}

	/**
	 * Check if user can use advanced generation options.
	 *
	 * @since 1.0.0
	 * @return bool True if user can use advanced options.
	 */
	public static function can_use_advanced_options() {
		return self::can_use_premium_code() &&
				( self::is_professional_plan() || self::is_agency_plan() );
	}

	/**
	 * Check if user can use all template categories.
	 *
	 * @since 1.0.0
	 * @return bool True if user can use all categories.
	 */
	public static function can_use_all_categories() {
		return self::can_use_premium_code() &&
				( self::is_professional_plan() || self::is_agency_plan() );
	}

	/**
	 * Check if user can use prompt engineering templates.
	 *
	 * @since 1.0.0
	 * @return bool True if user can use prompt templates.
	 */
	public static function can_use_prompt_templates() {
		return self::can_use_premium_code() && self::is_agency_plan();
	}

	/**
	 * Check if user can use debug mode.
	 *
	 * @since 1.0.0
	 * @return bool True if user can use debug mode.
	 */
	public static function can_use_debug_mode() {
		return self::can_use_premium_code() && self::is_agency_plan();
	}

	/**
	 * Check if user can use pattern and block variations.
	 *
	 * @since 1.0.0
	 * @return bool True if user can use variations.
	 */
	public static function can_use_variations() {
		return self::can_use_premium_code() &&
				( self::is_professional_plan() || self::is_agency_plan() );
	}

	/**
	 * Get template limit for current plan.
	 *
	 * @since 1.0.0
	 * @return int Template limit. 0 if expired monthly, 10 for Starter, PHP_INT_MAX for others.
	 */
	public static function get_template_limit() {
		if ( ! self::can_use_premium_code() ) {
			return 0; // Expired monthly cannot save
		}

		if ( self::is_starter_plan() ) {
			return 10;
		}

		return PHP_INT_MAX; // Unlimited for Professional and Agency
	}

	/**
	 * Get generation history days limit.
	 *
	 * @since 1.0.0
	 * @return int Number of days. 30 for Starter/expired, PHP_INT_MAX for others.
	 */
	public static function get_history_days() {
		if ( ! self::can_use_premium_code() || self::is_starter_plan() ) {
			return 30;
		}
		return PHP_INT_MAX; // Unlimited for Professional and Agency
	}

	/**
	 * Get appropriate URL for user action.
	 *
	 * @since 1.0.0
	 * @return string Account URL for renewal or upgrade URL.
	 */
	public static function get_action_url() {
		// Always return the dashboard pricing page URL
		return admin_url( 'admin.php?page=layoutberg' ) . '#pricing';
	}

	/**
	 * Get plan display name.
	 *
	 * @since 1.0.0
	 * @param bool $translate Whether to translate the plan name.
	 * @return string Current plan name or 'Free' if no active plan.
	 */
	public static function get_plan_name( $translate = true ) {
		$plan_name = '';
		
		if ( ! self::can_use_premium_code() ) {
			$plan_name = 'Free (Expired)';
		} elseif ( self::is_agency_plan() ) {
			$plan_name = 'Agency';
		} elseif ( self::is_professional_plan() ) {
			$plan_name = 'Professional';
		} elseif ( self::is_starter_plan() ) {
			$plan_name = 'Starter';
		} else {
			// If none match, return the actual plan name from Freemius
			$plan = \layoutberg_fs()->get_plan();
			if ( $plan && ! empty( $plan->name ) ) {
				$plan_name = ucfirst( $plan->name );
			} else {
				$plan_name = 'Unknown';
			}
		}
		
		// Only translate if requested and after init hook
		if ( $translate && did_action( 'init' ) ) {
			return __( $plan_name, 'layoutberg' );
		}
		
		return $plan_name;
	}

	/**
	 * Get upgrade message for a specific feature.
	 *
	 * @since 1.0.0
	 * @param string $feature_name Feature name.
	 * @param string $required_plan Required plan (starter, professional, agency).
	 * @return string Upgrade message.
	 */
	public static function get_upgrade_message( $feature_name, $required_plan = 'professional' ) {
		// Only translate after init hook
		if ( ! did_action( 'init' ) ) {
			return $feature_name . ' requires an upgrade.';
		}
		
		if ( self::is_expired_monthly() ) {
			return sprintf(
				/* translators: %s: feature name */
				__( 'Your subscription has expired. Please renew to access %s.', 'layoutberg' ),
				$feature_name
			);
		}

		$plan_names = array(
			'starter'      => __( 'Starter', 'layoutberg' ),
			'professional' => __( 'Professional', 'layoutberg' ),
			'agency'       => __( 'Agency', 'layoutberg' ),
		);

		$plan_display = isset( $plan_names[ $required_plan ] ) ? $plan_names[ $required_plan ] : ucfirst( $required_plan );
		
		// Get feature-specific value propositions
		$feature_benefits = array(
			__( 'All AI Models', 'layoutberg' ) => __( 'Access GPT-4, Claude, and future models for superior content generation', 'layoutberg' ),
			__( 'Unlimited Templates', 'layoutberg' ) => __( 'Save unlimited templates and build your design library', 'layoutberg' ),
			__( 'Export Templates', 'layoutberg' ) => __( 'Export and share templates across sites and teams', 'layoutberg' ),
			__( 'Advanced Settings', 'layoutberg' ) => __( 'Fine-tune AI parameters for perfect results', 'layoutberg' ),
			__( 'Token Control', 'layoutberg' ) => __( 'Optimize costs with custom token limits', 'layoutberg' ),
			__( 'Temperature Settings', 'layoutberg' ) => __( 'Control creativity levels for consistent outputs', 'layoutberg' ),
			__( 'Debug Mode', 'layoutberg' ) => __( 'Access advanced debugging tools and logs', 'layoutberg' ),
			__( 'CSV Export', 'layoutberg' ) => __( 'Export analytics data for reporting', 'layoutberg' ),
			__( 'All Categories', 'layoutberg' ) => __( 'Access premium template categories', 'layoutberg' ),
			__( 'Style Defaults', 'layoutberg' ) => __( 'Set brand-consistent default styles', 'layoutberg' ),
			__( 'Prompt Templates', 'layoutberg' ) => __( 'Create reusable prompts for your team', 'layoutberg' ),
		);
		
		$benefit = isset( $feature_benefits[ $feature_name ] ) ? ' ' . $feature_benefits[ $feature_name ] : '';

		return sprintf(
			/* translators: 1: feature name, 2: plan name, 3: benefit description */
			__( '%1$s is available in the %2$s plan and above.%3$s', 'layoutberg' ),
			$feature_name,
			$plan_display,
			$benefit
		);
	}

	/**
	 * Render upgrade notice HTML.
	 *
	 * @since 1.0.0
	 * @param string $feature_name Feature name.
	 * @param string $required_plan Required plan.
	 * @param array  $args Additional arguments.
	 */
	public static function render_upgrade_notice( $feature_name, $required_plan = 'professional', $args = array() ) {
		$defaults = array(
			'show_button' => true,
			'button_text' => '',
			'classes'     => 'layoutberg-upgrade-notice',
		);

		$args = wp_parse_args( $args, $defaults );

		$is_expired = self::is_expired_monthly();
		$action_url = self::get_action_url();
		$message    = self::get_upgrade_message( $feature_name, $required_plan );

		if ( empty( $args['button_text'] ) ) {
			// Only translate after init hook
			if ( did_action( 'init' ) ) {
				$args['button_text'] = $is_expired
					? __( 'Renew Subscription', 'layoutberg' )
					: __( 'Upgrade Now', 'layoutberg' );
			} else {
				$args['button_text'] = $is_expired ? 'Renew Subscription' : 'Upgrade Now';
			}
		}

		?>
		<div class="<?php echo esc_attr( $args['classes'] ); ?>">
			<p><?php echo esc_html( $message ); ?></p>
			<?php if ( $args['show_button'] ) : ?>
				<a href="<?php echo esc_url( $action_url ); ?>" class="button button-primary layoutberg-pricing-trigger" data-required-plan="<?php echo esc_attr( $required_plan ); ?>">
					<?php echo esc_html( $args['button_text'] ); ?>
				</a>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get locked feature button HTML.
	 *
	 * @since 1.0.0
	 * @param string $button_text Button text.
	 * @param string $feature_name Feature name.
	 * @param string $required_plan Required plan.
	 * @return string Button HTML.
	 */
	public static function get_locked_button( $button_text, $feature_name, $required_plan = 'professional' ) {
		$message = self::get_upgrade_message( $feature_name, $required_plan );
		$is_expired = self::is_expired_monthly();
		$action_url = self::get_action_url();
		
		// Add inline styles for better visual feedback
		$button_style = 'opacity: 0.7; cursor: pointer; position: relative; overflow: hidden;';
		
		// Determine button action text based on status
		if ( did_action( 'init' ) ) {
			$action_text = $is_expired ? __( 'Renew', 'layoutberg' ) : __( 'Upgrade', 'layoutberg' );
		} else {
			$action_text = $is_expired ? 'Renew' : 'Upgrade';
		}

		return sprintf(
			'<a href="%s" class="button layoutberg-locked-feature layoutberg-pricing-trigger" data-feature="%s" data-required-plan="%s" style="%s">
				%s 
				<span class="dashicons dashicons-lock" style="font-size: 14px; margin-left: 4px; vertical-align: middle;"></span>
			</a>',
			esc_url( $action_url ),
			esc_attr( $feature_name ),
			esc_attr( $required_plan ),
			esc_attr( $button_style ),
			esc_html( $button_text )
		);
	}

	/**
	 * Get pricing plans data.
	 *
	 * @since 1.0.0
	 * @return array Pricing plans with features.
	 */
	public static function get_pricing_data() {
		// Only translate after init hook
		if ( ! did_action( 'init' ) ) {
			return array();
		}
		
		$plans = array(
			'starter'      => array(
				'name'        => __( 'Starter', 'layoutberg' ),
				'price'       => __( '$9', 'layoutberg' ),
				'period'      => __( '/month', 'layoutberg' ),
				'features'    => array(
					__( 'Unlimited AI generations', 'layoutberg' ),
					__( 'Save up to 10 templates', 'layoutberg' ),
					__( 'GPT-3.5 Turbo model', 'layoutberg' ),
					__( '30-day generation history', 'layoutberg' ),
					__( 'Basic template categories', 'layoutberg' ),
					__( 'Priority email support', 'layoutberg' ),
				),
				'limitations' => array(
					__( 'Limited to GPT-3.5 Turbo', 'layoutberg' ),
					__( 'Cannot export templates', 'layoutberg' ),
					__( '10 template limit', 'layoutberg' ),
				),
			),
			'professional' => array(
				'name'        => __( 'Professional', 'layoutberg' ),
				'price'       => __( '$19', 'layoutberg' ),
				'period'      => __( '/month', 'layoutberg' ),
				'popular'     => true,
				'features'    => array(
					__( 'Unlimited AI generations', 'layoutberg' ),
					__( 'Unlimited template storage', 'layoutberg' ),
					__( 'All AI models (GPT-3.5, GPT-4, GPT-4 Turbo)', 'layoutberg' ),
					__( 'Unlimited generation history', 'layoutberg' ),
					__( 'All template categories', 'layoutberg' ),
					__( 'Export templates as JSON', 'layoutberg' ),
					__( 'Advanced generation options', 'layoutberg' ),
					__( 'Priority email support', 'layoutberg' ),
				),
				'limitations' => array(),
			),
			'agency'       => array(
				'name'        => __( 'Agency', 'layoutberg' ),
				'price'       => __( '$49', 'layoutberg' ),
				'period'      => __( '/month', 'layoutberg' ),
				'features'    => array(
					__( 'Everything in Professional', 'layoutberg' ),
					__( 'Export generation history as CSV', 'layoutberg' ),
					__( 'Custom prompt templates', 'layoutberg' ),
					__( 'Debug mode access', 'layoutberg' ),
					__( 'White-label options', 'layoutberg' ),
					__( 'Priority phone & email support', 'layoutberg' ),
					__( 'Custom integrations', 'layoutberg' ),
					__( 'Dedicated account manager', 'layoutberg' ),
				),
				'limitations' => array(),
			),
		);

		return apply_filters( 'layoutberg_pricing_plans', $plans );
	}
}