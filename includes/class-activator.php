<?php
/**
 * Fired during plugin activation.
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
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since 1.0.0
 */
class Activator {

	/**
	 * Plugin activation handler.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		// Load required files
		require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-upgrade.php';

		// Create/upgrade database tables.
		$upgrade = new Upgrade();
		$upgrade->create_tables();
		$upgrade->run();

		// Set default options.
		self::set_default_options();

		// Add capabilities.
		self::add_capabilities();

		// Schedule cron events.
		self::schedule_events();

		// Create upload directory.
		self::create_upload_directory();

		// Install default templates.
		self::install_default_templates();

		// Clear rewrite rules.
		flush_rewrite_rules();

		// Set activation flag.
		set_transient( 'layoutberg_activated', true, 30 );
	}


	/**
	 * Set default plugin options.
	 *
	 * @since 1.0.0
	 */
	private static function set_default_options() {
		$default_options = array(
			'api_key'          => '',
			'model'            => 'gpt-3.5-turbo',
			'max_tokens'       => 2000,
			'temperature'      => 0.7,
			'cache_enabled'    => true,
			'cache_duration'   => 3600,
			'rate_limit'       => array(
				'free'     => array(
					'hour' => 5,
					'day'  => 10,
				),
				'pro'      => array(
					'hour' => 20,
					'day'  => 100,
				),
				'business' => array(
					'hour' => 50,
					'day'  => 500,
				),
			),
			'style_defaults'   => array(
				'style'       => 'modern',
				'colors'      => 'brand',
				'layout'      => 'single-column',
				'density'     => 'balanced',
			),
			'block_restrictions' => array(),
			'analytics_enabled'  => true,
		);

		// Only set if not already exists.
		if ( false === get_option( 'layoutberg_options' ) ) {
			add_option( 'layoutberg_options', $default_options );
		}

		// Set plugin version.
		update_option( 'layoutberg_version', LAYOUTBERG_VERSION );
	}

	/**
	 * Add plugin capabilities to roles.
	 *
	 * @since 1.0.0
	 */
	private static function add_capabilities() {
		$capabilities = array(
			'administrator' => array(
				'layoutberg_generate',
				'layoutberg_manage_templates',
				'layoutberg_view_analytics',
				'layoutberg_configure',
			),
			'editor'        => array(
				'layoutberg_generate',
				'layoutberg_manage_templates',
			),
			'author'        => array(
				'layoutberg_generate',
			),
		);

		foreach ( $capabilities as $role_name => $caps ) {
			$role = get_role( $role_name );
			if ( $role ) {
				foreach ( $caps as $cap ) {
					$role->add_cap( $cap );
				}
			}
		}
	}

	/**
	 * Schedule cron events.
	 *
	 * @since 1.0.0
	 */
	private static function schedule_events() {
		// Schedule daily cleanup.
		if ( ! wp_next_scheduled( 'layoutberg_daily_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'layoutberg_daily_cleanup' );
		}

		// Schedule usage reset (monthly).
		if ( ! wp_next_scheduled( 'layoutberg_usage_reset' ) ) {
			wp_schedule_event( strtotime( 'first day of next month' ), 'monthly', 'layoutberg_usage_reset' );
		}
	}

	/**
	 * Create plugin upload directory.
	 *
	 * @since 1.0.0
	 */
	private static function create_upload_directory() {
		$upload_dir = wp_upload_dir();
		$plugin_dir = $upload_dir['basedir'] . '/layoutberg';

		if ( ! file_exists( $plugin_dir ) ) {
			wp_mkdir_p( $plugin_dir );

			// Create .htaccess to protect directory.
			$htaccess_content = "Options -Indexes\n";
			$htaccess_content .= "<FilesMatch '\.(php|php3|php4|php5|php7|phps|phtml|pl|py|jsp|asp|sh|cgi)$'>\n";
			$htaccess_content .= "    Order Deny,Allow\n";
			$htaccess_content .= "    Deny from all\n";
			$htaccess_content .= "</FilesMatch>\n";

			file_put_contents( $plugin_dir . '/.htaccess', $htaccess_content );
		}
	}

	/**
	 * Install default templates.
	 *
	 * @since 1.0.0
	 */
	private static function install_default_templates() {
		// Check if templates are already installed.
		$existing = get_option( 'layoutberg_default_templates_installed', false );
		if ( $existing ) {
			return;
		}

		// Load template manager.
		require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-template-manager.php';
		$template_manager = new Template_Manager();

		// Default templates data.
		$templates = self::get_default_templates_data();

		// Install each template.
		foreach ( $templates as $template_data ) {
			$result = $template_manager->save_template( $template_data );
			
			if ( is_wp_error( $result ) ) {
				error_log( 'LayoutBerg: Failed to install template: ' . $template_data['name'] . ' - ' . $result->get_error_message() );
			}
		}

		// Mark templates as installed.
		update_option( 'layoutberg_default_templates_installed', true );
	}

	/**
	 * Get default templates data.
	 *
	 * @since 1.0.0
	 * @return array Template data.
	 */
	private static function get_default_templates_data() {
		return array(
			// Landing Page Template
			array(
				'name'        => __( 'Modern Landing Page', 'layoutberg' ),
				'description' => __( 'A clean, modern landing page layout with hero section, features, and call-to-action.', 'layoutberg' ),
				'category'    => 'landing',
				'tags'        => array( 'hero', 'features', 'cta', 'modern' ),
				'prompt'      => __( 'Create a modern landing page with hero section, feature highlights, and call-to-action', 'layoutberg' ),
				'is_public'   => 1,
				'content'     => '<!-- wp:cover {"url":"https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=1200","hasParallax":true,"dimRatio":40,"overlayColor":"black","contentPosition":"center center","align":"full"} -->
<div class="wp-block-cover alignfull has-parallax has-black-background-color has-background-dim-40 has-background-dim"><div class="wp-block-cover__image-background has-parallax" style="background-image:url(https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=1200)"></div><div class="wp-block-cover__inner-container"><!-- wp:group {"layout":{"type":"constrained","contentSize":"800px"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"3.5rem","fontWeight":"700"},"color":{"text":"#ffffff"}}} -->
<h1 class="has-text-align-center has-text-color" style="color:#ffffff;font-size:3.5rem;font-weight:700">Transform Your Business Today</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"1.25rem"},"color":{"text":"#ffffff"}}} -->
<p class="has-text-align-center has-text-color" style="color:#ffffff;font-size:1.25rem">Discover powerful solutions that drive growth and innovation for your company.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"2rem"}}}} -->
<div class="wp-block-buttons" style="margin-top:2rem"><!-- wp:button {"backgroundColor":"primary","textColor":"white","style":{"typography":{"fontWeight":"600"},"spacing":{"padding":{"top":"1rem","bottom":"1rem","left":"2rem","right":"2rem"}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-white-color has-primary-background-color has-text-color has-background" style="padding-top:1rem;padding-right:2rem;padding-bottom:1rem;padding-left:2rem;font-weight:600">Get Started Now</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"4rem","bottom":"4rem"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:4rem;padding-bottom:4rem"><!-- wp:heading {"textAlign":"center","level":2,"style":{"typography":{"fontSize":"2.5rem","fontWeight":"600"}}} -->
<h2 class="has-text-align-center" style="font-size:2.5rem;font-weight:600">Why Choose Us</h2>
<!-- /wp:heading -->

<!-- wp:columns {"style":{"spacing":{"margin":{"top":"3rem"}}}} -->
<div class="wp-block-columns" style="margin-top:3rem"><!-- wp:column {"style":{"spacing":{"padding":{"all":"2rem"}}}} -->
<div class="wp-block-column" style="padding:2rem"><!-- wp:image {"align":"center","width":80,"height":80,"sizeSlug":"full","style":{"border":{"radius":"50%"}}} -->
<figure class="wp-block-image aligncenter size-full is-resized" style="border-radius:50%"><img src="https://images.unsplash.com/photo-1553835973-dec43bfddbeb?w=100" alt="" width="80" height="80"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"600"}}} -->
<h3 class="has-text-align-center" style="font-size:1.5rem;font-weight:600">Fast & Reliable</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Lightning-fast performance with 99.9% uptime guaranteed for your peace of mind.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"padding":{"all":"2rem"}}}} -->
<div class="wp-block-column" style="padding:2rem"><!-- wp:image {"align":"center","width":80,"height":80,"sizeSlug":"full","style":{"border":{"radius":"50%"}}} -->
<figure class="wp-block-image aligncenter size-full is-resized" style="border-radius:50%"><img src="https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=100" alt="" width="80" height="80"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"600"}}} -->
<h3 class="has-text-align-center" style="font-size:1.5rem;font-weight:600">Expert Support</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">24/7 customer support from our team of experienced professionals.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"padding":{"all":"2rem"}}}} -->
<div class="wp-block-column" style="padding:2rem"><!-- wp:image {"align":"center","width":80,"height":80,"sizeSlug":"full","style":{"border":{"radius":"50%"}}} -->
<figure class="wp-block-image aligncenter size-full is-resized" style="border-radius:50%"><img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=100" alt="" width="80" height="80"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"600"}}} -->
<h3 class="has-text-align-center" style="font-size:1.5rem;font-weight:600">Scalable Solution</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Grows with your business needs, from startup to enterprise level.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
			),

			// Blog Post Template
			array(
				'name'        => __( 'Professional Blog Post', 'layoutberg' ),
				'description' => __( 'A well-structured blog post layout with featured image, content sections, and author bio.', 'layoutberg' ),
				'category'    => 'blog',
				'tags'        => array( 'blog', 'article', 'content', 'professional' ),
				'prompt'      => __( 'Create a professional blog post layout with featured image and structured content', 'layoutberg' ),
				'is_public'   => 1,
				'content'     => '<!-- wp:image {"align":"wide","sizeSlug":"large"} -->
<figure class="wp-block-image alignwide size-large"><img src="https://images.unsplash.com/photo-1486312338219-ce68d2c6f44d?w=1200" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:group {"layout":{"type":"constrained","contentSize":"800px"}} -->
<div class="wp-block-group"><!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"2.75rem","fontWeight":"700"}}} -->
<h1 style="font-size:2.75rem;font-weight:700">The Future of Technology: Trends to Watch</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"1.125rem"},"color":{"text":"#666666"}}} -->
<p class="has-text-color" style="color:#666666;font-size:1.125rem">Exploring the latest innovations that will shape our digital landscape in the coming years.</p>
<!-- /wp:paragraph -->

<!-- wp:separator {"style":{"spacing":{"margin":{"top":"2rem","bottom":"2rem"}}}} -->
<hr class="wp-block-separator has-alpha-channel-opacity" style="margin-top:2rem;margin-bottom:2rem"/>
<!-- /wp:separator -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"1.125rem","lineHeight":"1.8"}}} -->
<p style="font-size:1.125rem;line-height:1.8">Technology continues to evolve at an unprecedented pace, reshaping how we work, communicate, and interact with the world around us. In this comprehensive guide, we explore the key trends that are defining the future of technology.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"2rem","fontWeight":"600"}}} -->
<h2 style="font-size:2rem;font-weight:600">Artificial Intelligence Revolution</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"1.125rem","lineHeight":"1.8"}}} -->
<p style="font-size:1.125rem;line-height:1.8">Artificial Intelligence is no longer a concept from science fiction. Today, AI is transforming industries from healthcare to finance, offering unprecedented opportunities for innovation and efficiency.</p>
<!-- /wp:paragraph -->

<!-- wp:quote {"style":{"spacing":{"padding":{"top":"2rem","bottom":"2rem","left":"2rem","right":"2rem"}},"border":{"left":{"color":"#0073aa","width":"4px"}}}} -->
<blockquote class="wp-block-quote" style="border-left-color:#0073aa;border-left-width:4px;padding-top:2rem;padding-right:2rem;padding-bottom:2rem;padding-left:2rem"><p>"The development of AI will be either the best or worst thing ever to happen to humanity."</p><cite>Stephen Hawking</cite></blockquote>
<!-- /wp:quote -->

<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"2rem","fontWeight":"600"}}} -->
<h2 style="font-size:2rem;font-weight:600">Key Takeaways</h2>
<!-- /wp:heading -->

<!-- wp:list {"style":{"typography":{"fontSize":"1.125rem"}}} -->
<ul style="font-size:1.125rem"><li>AI will continue to automate complex tasks</li><li>Machine learning algorithms will become more sophisticated</li><li>Ethical considerations will shape AI development</li><li>Integration with existing systems will accelerate</li></ul>
<!-- /wp:list --></div>
<!-- /wp:group -->',
			),

			// About Us Template
			array(
				'name'        => __( 'About Us Page', 'layoutberg' ),
				'description' => __( 'A comprehensive about page with team introduction, company values, and mission statement.', 'layoutberg' ),
				'category'    => 'about',
				'tags'        => array( 'about', 'team', 'company', 'mission' ),
				'prompt'      => __( 'Create an about us page with team section, company values, and mission statement', 'layoutberg' ),
				'is_public'   => 1,
				'content'     => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"4rem","bottom":"4rem"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:4rem;padding-bottom:4rem"><!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"3rem","fontWeight":"700"}}} -->
<h1 class="has-text-align-center" style="font-size:3rem;font-weight:700">About Our Company</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"1.25rem"},"spacing":{"margin":{"top":"2rem"}}}} -->
<p class="has-text-align-center" style="font-size:1.25rem;margin-top:2rem">We are a team of passionate professionals dedicated to delivering innovative solutions that make a difference.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"4rem","bottom":"4rem"}}},"backgroundColor":"light-gray","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-light-gray-background-color has-background" style="padding-top:4rem;padding-bottom:4rem"><!-- wp:columns {"verticalAlignment":"center"} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:image {"sizeSlug":"large","style":{"border":{"radius":"12px"}}} -->
<figure class="wp-block-image size-large" style="border-radius:12px"><img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=600" alt=""/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","style":{"spacing":{"padding":{"left":"3rem"}}}} -->
<div class="wp-block-column is-vertically-aligned-center" style="padding-left:3rem"><!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"2.5rem","fontWeight":"600"}}} -->
<h2 style="font-size:2.5rem;font-weight:600">Our Mission</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"1.125rem","lineHeight":"1.8"}}} -->
<p style="font-size:1.125rem;line-height:1.8">To empower businesses worldwide with cutting-edge technology solutions that drive growth, innovation, and success. We believe in creating meaningful connections between technology and human potential.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"1.125rem","lineHeight":"1.8"}}} -->
<p style="font-size:1.125rem;line-height:1.8">Founded in 2020, we have helped over 500 companies transform their digital presence and achieve their goals through our comprehensive suite of services.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"4rem","bottom":"4rem"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:4rem;padding-bottom:4rem"><!-- wp:heading {"textAlign":"center","level":2,"style":{"typography":{"fontSize":"2.5rem","fontWeight":"600"}}} -->
<h2 class="has-text-align-center" style="font-size:2.5rem;font-weight:600">Meet Our Team</h2>
<!-- /wp:heading -->

<!-- wp:columns {"style":{"spacing":{"margin":{"top":"3rem"}}}} -->
<div class="wp-block-columns" style="margin-top:3rem"><!-- wp:column {"style":{"spacing":{"padding":{"all":"2rem"}}}} -->
<div class="wp-block-column" style="padding:2rem"><!-- wp:image {"align":"center","width":120,"height":120,"sizeSlug":"full","style":{"border":{"radius":"50%"}}} -->
<figure class="wp-block-image aligncenter size-full is-resized" style="border-radius:50%"><img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150" alt="" width="120" height="120"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"600"}}} -->
<h3 class="has-text-align-center" style="font-size:1.5rem;font-weight:600">John Smith</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#666666"}}} -->
<p class="has-text-align-center has-text-color" style="color:#666666">CEO & Founder</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Visionary leader with 15+ years of experience in technology and business development.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"padding":{"all":"2rem"}}}} -->
<div class="wp-block-column" style="padding:2rem"><!-- wp:image {"align":"center","width":120,"height":120,"sizeSlug":"full","style":{"border":{"radius":"50%"}}} -->
<figure class="wp-block-image aligncenter size-full is-resized" style="border-radius:50%"><img src="https://images.unsplash.com/photo-1494790108755-2616b612b0e0?w=150" alt="" width="120" height="120"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"600"}}} -->
<h3 class="has-text-align-center" style="font-size:1.5rem;font-weight:600">Sarah Johnson</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#666666"}}} -->
<p class="has-text-align-center has-text-color" style="color:#666666">CTO</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Technical expert specializing in scalable solutions and innovative software architecture.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"padding":{"all":"2rem"}}}} -->
<div class="wp-block-column" style="padding:2rem"><!-- wp:image {"align":"center","width":120,"height":120,"sizeSlug":"full","style":{"border":{"radius":"50%"}}} -->
<figure class="wp-block-image aligncenter size-full is-resized" style="border-radius:50%"><img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150" alt="" width="120" height="120"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"600"}}} -->
<h3 class="has-text-align-center" style="font-size:1.5rem;font-weight:600">Mike Davis</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#666666"}}} -->
<p class="has-text-align-center has-text-color" style="color:#666666">Head of Design</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Creative designer focused on user experience and beautiful, functional interfaces.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
			),

			// Contact Page Template
			array(
				'name'        => __( 'Contact Us Page', 'layoutberg' ),
				'description' => __( 'A professional contact page with contact form, location info, and multiple contact methods.', 'layoutberg' ),
				'category'    => 'contact',
				'tags'        => array( 'contact', 'form', 'location', 'business' ),
				'prompt'      => __( 'Create a contact page with contact form, business information, and location details', 'layoutberg' ),
				'is_public'   => 1,
				'content'     => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"4rem","bottom":"4rem"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:4rem;padding-bottom:4rem"><!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"3rem","fontWeight":"700"}}} -->
<h1 class="has-text-align-center" style="font-size:3rem;font-weight:700">Get In Touch</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"1.25rem"},"spacing":{"margin":{"top":"2rem"}}}} -->
<p class="has-text-align-center" style="font-size:1.25rem;margin-top:2rem">Ready to start your next project? We would love to hear from you.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"4rem","bottom":"4rem"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:4rem;padding-bottom:4rem"><!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"style":{"spacing":{"padding":{"right":"3rem"}}}} -->
<div class="wp-block-column" style="padding-right:3rem"><!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"2rem","fontWeight":"600"}}} -->
<h2 style="font-size:2rem;font-weight:600">Send us a message</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"1.125rem"},"spacing":{"margin":{"bottom":"2rem"}}}} -->
<p style="font-size:1.125rem;margin-bottom:2rem">Fill out the form below and we will get back to you as soon as possible.</p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<form class="contact-form">
<div style="margin-bottom: 1.5rem;">
<label for="name" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Name *</label>
<input type="text" id="name" name="name" required style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 1rem;">
</div>

<div style="margin-bottom: 1.5rem;">
<label for="email" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Email *</label>
<input type="email" id="email" name="email" required style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 1rem;">
</div>

<div style="margin-bottom: 1.5rem;">
<label for="subject" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Subject</label>
<input type="text" id="subject" name="subject" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 1rem;">
</div>

<div style="margin-bottom: 1.5rem;">
<label for="message" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Message *</label>
<textarea id="message" name="message" rows="5" required style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 1rem; resize: vertical;"></textarea>
</div>

<button type="submit" style="background: #0073aa; color: white; padding: 0.75rem 2rem; border: none; border-radius: 0.375rem; font-size: 1rem; font-weight: 600; cursor: pointer;">Send Message</button>
</form>
<!-- /wp:html --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"padding":{"left":"3rem"}}}} -->
<div class="wp-block-column" style="padding-left:3rem"><!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"2rem","fontWeight":"600"}}} -->
<h2 style="font-size:2rem;font-weight:600">Contact Information</h2>
<!-- /wp:heading -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"2rem"},"padding":{"all":"1.5rem"}},"border":{"radius":"8px"}},"backgroundColor":"light-gray"} -->
<div class="wp-block-group has-light-gray-background-color has-background" style="border-radius:8px;margin-top:2rem;padding:1.5rem"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","fontSize":"1.125rem"}}} -->
<p style="font-size:1.125rem;font-weight:600">📍 Office Address</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>123 Business Street<br>Suite 100<br>City, State 12345</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"1.5rem"},"padding":{"all":"1.5rem"}},"border":{"radius":"8px"}},"backgroundColor":"light-gray"} -->
<div class="wp-block-group has-light-gray-background-color has-background" style="border-radius:8px;margin-top:1.5rem;padding:1.5rem"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","fontSize":"1.125rem"}}} -->
<p style="font-size:1.125rem;font-weight:600">📞 Phone</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>(555) 123-4567</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"1.5rem"},"padding":{"all":"1.5rem"}},"border":{"radius":"8px"}},"backgroundColor":"light-gray"} -->
<div class="wp-block-group has-light-gray-background-color has-background" style="border-radius:8px;margin-top:1.5rem;padding:1.5rem"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","fontSize":"1.125rem"}}} -->
<p style="font-size:1.125rem;font-weight:600">✉️ Email</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>hello@company.com</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"1.5rem"},"padding":{"all":"1.5rem"}},"border":{"radius":"8px"}},"backgroundColor":"light-gray"} -->
<div class="wp-block-group has-light-gray-background-color has-background" style="border-radius:8px;margin-top:1.5rem;padding:1.5rem"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","fontSize":"1.125rem"}}} -->
<p style="font-size:1.125rem;font-weight:600">🕒 Business Hours</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Monday - Friday: 9:00 AM - 6:00 PM<br>Saturday: 10:00 AM - 4:00 PM<br>Sunday: Closed</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
			),

			// Portfolio Template
			array(
				'name'        => __( 'Portfolio Showcase', 'layoutberg' ),
				'description' => __( 'A creative portfolio layout to showcase work, projects, and achievements in an elegant grid format.', 'layoutberg' ),
				'category'    => 'portfolio',
				'tags'        => array( 'portfolio', 'showcase', 'projects', 'creative' ),
				'prompt'      => __( 'Create a portfolio page with project gallery, skills, and professional showcase', 'layoutberg' ),
				'is_public'   => 1,
				'content'     => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"4rem","bottom":"4rem"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:4rem;padding-bottom:4rem"><!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"3rem","fontWeight":"700"}}} -->
<h1 class="has-text-align-center" style="font-size:3rem;font-weight:700">My Portfolio</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"1.25rem"},"spacing":{"margin":{"top":"2rem"}}}} -->
<p class="has-text-align-center" style="font-size:1.25rem;margin-top:2rem">Showcasing creative projects and professional achievements that define my journey.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"4rem","bottom":"4rem"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:4rem;padding-bottom:4rem"><!-- wp:heading {"textAlign":"center","level":2,"style":{"typography":{"fontSize":"2.5rem","fontWeight":"600"}}} -->
<h2 class="has-text-align-center" style="font-size:2.5rem;font-weight:600">Featured Projects</h2>
<!-- /wp:heading -->

<!-- wp:columns {"style":{"spacing":{"margin":{"top":"3rem"}}}} -->
<div class="wp-block-columns" style="margin-top:3rem"><!-- wp:column {"style":{"spacing":{"padding":{"all":"1rem"}}}} -->
<div class="wp-block-column" style="padding:1rem"><!-- wp:image {"sizeSlug":"large","style":{"border":{"radius":"12px"}}} -->
<figure class="wp-block-image size-large" style="border-radius:12px"><img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=400" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"600"},"spacing":{"margin":{"top":"1.5rem"}}}} -->
<h3 style="font-size:1.5rem;font-weight:600;margin-top:1.5rem">E-Commerce Platform</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"#666666"}}} -->
<p class="has-text-color" style="color:#666666">A comprehensive online shopping platform with modern design and seamless user experience.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"0.875rem","fontWeight":"600"}}} -->
<p style="font-size:0.875rem;font-weight:600">Technologies: React, Node.js, MongoDB</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"padding":{"all":"1rem"}}}} -->
<div class="wp-block-column" style="padding:1rem"><!-- wp:image {"sizeSlug":"large","style":{"border":{"radius":"12px"}}} -->
<figure class="wp-block-image size-large" style="border-radius:12px"><img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=400" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"600"},"spacing":{"margin":{"top":"1.5rem"}}}} -->
<h3 style="font-size:1.5rem;font-weight:600;margin-top:1.5rem">Mobile Banking App</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"#666666"}}} -->
<p class="has-text-color" style="color:#666666">Secure and intuitive mobile banking application with advanced financial management features.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"0.875rem","fontWeight":"600"}}} -->
<p style="font-size:0.875rem;font-weight:600">Technologies: Flutter, Firebase, REST API</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"padding":{"all":"1rem"}}}} -->
<div class="wp-block-column" style="padding:1rem"><!-- wp:image {"sizeSlug":"large","style":{"border":{"radius":"12px"}}} -->
<figure class="wp-block-image size-large" style="border-radius:12px"><img src="https://images.unsplash.com/photo-1551434678-e076c223a692?w=400" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"600"},"spacing":{"margin":{"top":"1.5rem"}}}} -->
<h3 style="font-size:1.5rem;font-weight:600;margin-top:1.5rem">Analytics Dashboard</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"#666666"}}} -->
<p class="has-text-color" style="color:#666666">Real-time data visualization dashboard for business intelligence and decision making.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"0.875rem","fontWeight":"600"}}} -->
<p style="font-size:0.875rem;font-weight:600">Technologies: D3.js, Python, PostgreSQL</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"4rem","bottom":"4rem"}}},"backgroundColor":"light-gray","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-light-gray-background-color has-background" style="padding-top:4rem;padding-bottom:4rem"><!-- wp:heading {"textAlign":"center","level":2,"style":{"typography":{"fontSize":"2.5rem","fontWeight":"600"}}} -->
<h2 class="has-text-align-center" style="font-size:2.5rem;font-weight:600">Skills & Expertise</h2>
<!-- /wp:heading -->

<!-- wp:columns {"style":{"spacing":{"margin":{"top":"3rem"}}}} -->
<div class="wp-block-columns" style="margin-top:3rem"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"600"}}} -->
<h3 style="font-size:1.5rem;font-weight:600">Frontend Development</h3>
<!-- /wp:heading -->

<!-- wp:list {"style":{"typography":{"fontSize":"1.125rem"}}} -->
<ul style="font-size:1.125rem"><li>React & Next.js</li><li>Vue.js & Nuxt.js</li><li>TypeScript & JavaScript</li><li>HTML5 & CSS3</li><li>Responsive Design</li></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"600"}}} -->
<h3 style="font-size:1.5rem;font-weight:600">Backend Development</h3>
<!-- /wp:heading -->

<!-- wp:list {"style":{"typography":{"fontSize":"1.125rem"}}} -->
<ul style="font-size:1.125rem"><li>Node.js & Express</li><li>Python & Django</li><li>REST API Design</li><li>Database Management</li><li>Cloud Services</li></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"600"}}} -->
<h3 style="font-size:1.5rem;font-weight:600">Design & Tools</h3>
<!-- /wp:heading -->

<!-- wp:list {"style":{"typography":{"fontSize":"1.125rem"}}} -->
<ul style="font-size:1.125rem"><li>UI/UX Design</li><li>Figma & Adobe XD</li><li>Git & Version Control</li><li>Agile Methodology</li><li>Project Management</li></ul>
<!-- /wp:list --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
			),

			// Service Page Template
			array(
				'name'        => __( 'Services Overview', 'layoutberg' ),
				'description' => __( 'A comprehensive services page highlighting different offerings with pricing and features.', 'layoutberg' ),
				'category'    => 'services',
				'tags'        => array( 'services', 'pricing', 'features', 'business' ),
				'prompt'      => __( 'Create a services page with service descriptions, features, and pricing information', 'layoutberg' ),
				'is_public'   => 1,
				'content'     => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"4rem","bottom":"4rem"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:4rem;padding-bottom:4rem"><!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"3rem","fontWeight":"700"}}} -->
<h1 class="has-text-align-center" style="font-size:3rem;font-weight:700">Our Services</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"1.25rem"},"spacing":{"margin":{"top":"2rem"}}}} -->
<p class="has-text-align-center" style="font-size:1.25rem;margin-top:2rem">Comprehensive solutions tailored to meet your business needs and drive success.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"4rem","bottom":"4rem"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:4rem;padding-bottom:4rem"><!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"style":{"spacing":{"padding":{"all":"2rem"}},"border":{"radius":"12px"}},"backgroundColor":"white","className":"has-shadow"} -->
<div class="wp-block-column has-shadow has-white-background-color has-background" style="border-radius:12px;padding:2rem"><!-- wp:image {"align":"center","width":80,"height":80,"sizeSlug":"full"} -->
<figure class="wp-block-image aligncenter size-full is-resized"><img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=100" alt="" width="80" height="80"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"600"}}} -->
<h3 class="has-text-align-center" style="font-size:1.5rem;font-weight:600">Web Development</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"bottom":"2rem"}}}} -->
<p class="has-text-align-center" style="margin-bottom:2rem">Custom websites and web applications built with modern technologies and best practices.</p>
<!-- /wp:paragraph -->

<!-- wp:list {"style":{"typography":{"fontSize":"0.875rem"}}} -->
<ul style="font-size:0.875rem"><li>Responsive Design</li><li>E-commerce Solutions</li><li>CMS Development</li><li>API Integration</li><li>Performance Optimization</li></ul>
<!-- /wp:list -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"2rem","fontWeight":"700"},"spacing":{"margin":{"top":"2rem"}}}} -->
<p class="has-text-align-center" style="font-size:2rem;font-weight:700;margin-top:2rem">Starting at $2,500</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"padding":{"all":"2rem"}},"border":{"radius":"12px"}},"backgroundColor":"white","className":"has-shadow"} -->
<div class="wp-block-column has-shadow has-white-background-color has-background" style="border-radius:12px;padding:2rem"><!-- wp:image {"align":"center","width":80,"height":80,"sizeSlug":"full"} -->
<figure class="wp-block-image aligncenter size-full is-resized"><img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=100" alt="" width="80" height="80"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"600"}}} -->
<h3 class="has-text-align-center" style="font-size:1.5rem;font-weight:600">Mobile Apps</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"bottom":"2rem"}}}} -->
<p class="has-text-align-center" style="margin-bottom:2rem">Native and cross-platform mobile applications for iOS and Android devices.</p>
<!-- /wp:paragraph -->

<!-- wp:list {"style":{"typography":{"fontSize":"0.875rem"}}} -->
<ul style="font-size:0.875rem"><li>iOS & Android</li><li>Cross-platform Development</li><li>UI/UX Design</li><li>App Store Optimization</li><li>Maintenance & Updates</li></ul>
<!-- /wp:list -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"2rem","fontWeight":"700"},"spacing":{"margin":{"top":"2rem"}}}} -->
<p class="has-text-align-center" style="font-size:2rem;font-weight:700;margin-top:2rem">Starting at $5,000</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"padding":{"all":"2rem"}},"border":{"radius":"12px"}},"backgroundColor":"white","className":"has-shadow"} -->
<div class="wp-block-column has-shadow has-white-background-color has-background" style="border-radius:12px;padding:2rem"><!-- wp:image {"align":"center","width":80,"height":80,"sizeSlug":"full"} -->
<figure class="wp-block-image aligncenter size-full is-resized"><img src="https://images.unsplash.com/photo-1551434678-e076c223a692?w=100" alt="" width="80" height="80"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"1.5rem","fontWeight":"600"}}} -->
<h3 class="has-text-align-center" style="font-size:1.5rem;font-weight:600">Consulting</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"bottom":"2rem"}}}} -->
<p class="has-text-align-center" style="margin-bottom:2rem">Strategic technology consulting to help your business make informed decisions.</p>
<!-- /wp:paragraph -->

<!-- wp:list {"style":{"typography":{"fontSize":"0.875rem"}}} -->
<ul style="font-size:0.875rem"><li>Technology Strategy</li><li>Digital Transformation</li><li>Architecture Review</li><li>Security Assessment</li><li>Performance Audit</li></ul>
<!-- /wp:list -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"2rem","fontWeight":"700"},"spacing":{"margin":{"top":"2rem"}}}} -->
<p class="has-text-align-center" style="font-size:2rem;font-weight:700;margin-top:2rem">$150/hour</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"4rem","bottom":"4rem"}}},"backgroundColor":"primary","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-primary-background-color has-background" style="padding-top:4rem;padding-bottom:4rem"><!-- wp:heading {"textAlign":"center","level":2,"style":{"typography":{"fontSize":"2.5rem","fontWeight":"600"},"color":{"text":"#ffffff"}}} -->
<h2 class="has-text-align-center has-text-color" style="color:#ffffff;font-size:2.5rem;font-weight:600">Ready to Get Started?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"1.125rem"},"color":{"text":"#ffffff"},"spacing":{"margin":{"top":"1rem","bottom":"2rem"}}}} -->
<p class="has-text-align-center has-text-color" style="color:#ffffff;font-size:1.125rem;margin-top:1rem;margin-bottom:2rem">Contact us today to discuss your project and get a personalized quote.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"white","textColor":"primary","style":{"typography":{"fontWeight":"600"},"spacing":{"padding":{"top":"1rem","bottom":"1rem","left":"2rem","right":"2rem"}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-primary-color has-white-background-color has-text-color has-background" style="padding-top:1rem;padding-right:2rem;padding-bottom:1rem;padding-left:2rem;font-weight:600">Contact Us Today</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->',
			),
		);
	}
}