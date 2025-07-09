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
		return layoutberg_fs()->can_use_premium_code();
	}

	/**
	 * Check if user has an expired monthly subscription.
	 * 
	 * @since 1.0.0
	 * @return bool True if user has expired monthly subscription.
	 */
	public static function is_expired_monthly() {
		return layoutberg_fs()->is_registered() && 
		       ! layoutberg_fs()->can_use_premium_code() && 
		       ! layoutberg_fs()->is_paying();
	}

	/**
	 * Check if user is on the Starter plan.
	 * 
	 * @since 1.0.0
	 * @return bool True if user is on Starter plan.
	 */
	public static function is_starter_plan() {
		return self::can_use_premium_code() && layoutberg_fs()->is_plan( 'starter' );
	}

	/**
	 * Check if user is on the Professional plan.
	 * 
	 * @since 1.0.0
	 * @return bool True if user is on Professional plan.
	 */
	public static function is_professional_plan() {
		return self::can_use_premium_code() && layoutberg_fs()->is_plan( 'professional' );
	}

	/**
	 * Check if user is on the Agency plan.
	 * 
	 * @since 1.0.0
	 * @return bool True if user is on Agency plan.
	 */
	public static function is_agency_plan() {
		return self::can_use_premium_code() && layoutberg_fs()->is_plan( 'agency' );
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
		if ( self::is_expired_monthly() ) {
			return layoutberg_fs()->get_account_url(); // For renewal
		}
		return layoutberg_fs()->get_upgrade_url(); // For upgrade
	}

	/**
	 * Get plan display name.
	 * 
	 * @since 1.0.0
	 * @return string Current plan name or 'Free' if no active plan.
	 */
	public static function get_plan_name() {
		if ( ! self::can_use_premium_code() ) {
			return __( 'Free (Expired)', 'layoutberg' );
		}

		if ( self::is_starter_plan() ) {
			return __( 'Starter', 'layoutberg' );
		}

		if ( self::is_professional_plan() ) {
			return __( 'Professional', 'layoutberg' );
		}

		if ( self::is_agency_plan() ) {
			return __( 'Agency', 'layoutberg' );
		}

		return __( 'Unknown', 'layoutberg' );
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

		return sprintf(
			/* translators: 1: feature name, 2: plan name */
			__( '%1$s is available in the %2$s plan and above.', 'layoutberg' ),
			$feature_name,
			$plan_display
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
			$args['button_text'] = $is_expired 
				? __( 'Renew Subscription', 'layoutberg' ) 
				: __( 'Upgrade Now', 'layoutberg' );
		}

		?>
		<div class="<?php echo esc_attr( $args['classes'] ); ?>">
			<p><?php echo esc_html( $message ); ?></p>
			<?php if ( $args['show_button'] ) : ?>
				<a href="<?php echo esc_url( $action_url ); ?>" class="button button-primary">
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
		
		return sprintf(
			'<button class="button disabled" title="%s" disabled>%s <span class="dashicons dashicons-lock"></span></button>',
			esc_attr( $message ),
			esc_html( $button_text )
		);
	}
}