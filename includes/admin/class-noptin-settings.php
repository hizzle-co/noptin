<?php

defined( 'ABSPATH' ) || exit;

class Noptin_Settings {

	// Class constructor.
	private function __construct() {}

	// Renders the settings page.
	public static function output() {

		// Maybe save the settings.
		Noptin_Settings::maybe_save_settings();

		// Render settings.
		get_noptin_template( 'settings.php' );

	}

	// Saves the settings page.
	public static function maybe_save_settings() {
		global $noptin_options;

		// Maybe abort early.
		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'] ) ) {
			return;
		}

		// Prepare the settings.
		$registered_settings = self::get_settings();
		$posted_settings     = $_POST;
		unset( $posted_settings['_wpnonce'] );
		unset( $posted_settings['_wp_http_referer'] );

		// Sanitize the settings.
		$options = self::sanitize_settings( $registered_settings, $posted_settings );

		// Then save them.
		$noptin_options = $options;
		update_option( 'noptin_options', $options );
	}

	/**
	 * Sanitizes settings fields
	 */
	public static function sanitize_settings( $registered_settings, $posted_settings ) {

		foreach ( $registered_settings as $id => $args ) {

			// Deal with checkboxes(unchecked ones are never posted).
			if ( 'checkbox' == $args['el'] ) {
				$posted_settings[ $id ] = isset( $posted_settings[ $id ] ) ? '1' : '0';
			}
		}

		/**
		 * Filters Noptin settings before they are saved in the database.
		 * 
		 * @param array $posted_settings An array of posted settings.
		 */
		return apply_filters( 'noptin_sanitize_settings', $posted_settings );
	}

	/**
	 * Returns all settings sections
	 */
	public static function get_sections() {
		$sections = wp_list_pluck( self::get_settings(), 'section' );
		$modified = array();

		foreach ( $sections as $section ) {
			$modified[ $section ] = ucwords( str_replace( '-', ' ', $section ) );
		}
		return $modified;

	}

	/**
	 * Returns a section conditional
	 */
	public static function get_section_conditional( $args ) {

		if ( empty( $args['section'] ) ) {
			return '';
		}

		return "v-show=\"currentTab=='{$args['section']}'\"";

	}

	/**
	 * Returns the default state
	 */
	public static function get_state() {

		$settings = self::get_settings();
		$state    = array();

		foreach ( $settings as $key => $args ) {
			$default = isset( $args['default'] ) ? $args['default'] : '';
			$state[ $key ] = get_noptin_option( $key, $default );
		}

		$state = array_replace( get_noptin_options(), $state );

		$state['currentTab'] = 'general';
		$state['saved']      = __( 'Your settings have been saved', 'newsletter-optin-box' );
		$state['error']      = __( 'Your settings could not be saved.', 'newsletter-optin-box' );
		return $state;

	}

	/**
	 * Returns all settings fields
	 */
	public static function get_settings() {

		$settings = array(

			'notify_admin'          => array(
				'el'          => 'input',
				'type'        => 'checkbox_alt',
				'section'     => 'general',
				'label'       => __( 'Admin Notifications', 'newsletter-optin-box' ),
				'description' => __( 'Notify the site admin every time a new subscriber signs up for the newsletter.', 'newsletter-optin-box' ),
			),

			
			'double_optin'    => array(
				'el'          => 'input',
				'type'        => 'checkbox_alt',
				'section'     => 'general',
				'label'       => __( 'Double Opt-in', 'newsletter-optin-box' ),
				'description' => __( 'Require new subscribers to confirm their email addresses.', 'newsletter-optin-box' ),
			),

			'hide_from_subscribers' => array(
				'el'          => 'input',
				'type'        => 'checkbox_alt',
				'section'     => 'general',
				'label'       => __( 'Hide From Subscribers', 'newsletter-optin-box' ),
				'description' => __( 'Hide opt-in forms and methods from existing subscribers.', 'newsletter-optin-box' ),
			),

			'subscribers_cookie' => array(
				'el'          => 'input',
				'type'        => 'text',
				'section'     => 'general',
				'label'       => __( 'Subscribers Cookie', 'newsletter-optin-box' ),
				'placeholder' => '',
				'description' => __( 'If you are migrating from another email plugin, enter the cookie name they used to identify subscribers.', 'newsletter-optin-box' ),
			),

			'reply_to'        => array(
				'el'          => 'input',
				'section'     => 'emails',
				'type'        => 'email',
				'label'       => __( '"Reply-to" Email', 'newsletter-optin-box' ),
				'class'       => 'regular-text',
				'placeholder' => get_option( 'admin_email' ),
				'default'     => get_option( 'admin_email' ),
				'description' => __( 'Where should subscribers reply to in case they need to get in touch with you?', 'newsletter-optin-box' ),
			),

			'from_email'            => array(
				'el'          => 'input',
				'section'     => 'emails',
				'type'        => 'email',
				'label'       => __( '"From" Email', 'newsletter-optin-box' ),
				'class'       => 'regular-text',
				'placeholder' => noptin()->mailer->default_from_address(),
				'description' => __( 'How the sender email appears in outgoing emails. Leave this field blank if you are not able to send any emails.', 'newsletter-optin-box' ),
			),

			'from_name'             => array(
				'el'          => 'input',
				'section'     => 'emails',
				'label'       => __( '"From" Name', 'newsletter-optin-box' ),
				'class'       => 'regular-text',
				'placeholder' => get_option( 'blogname' ),
				'description' => __( 'How the sender name appears in outgoing emails', 'newsletter-optin-box' ),
			),

			'company'         => array(
				'el'          => 'input',
				'section'     => 'emails',
				'label'       => __( 'Company', 'newsletter-optin-box' ),
				'class'       => 'regular-text',
				'placeholder' => get_option( 'blogname' ),
				'description' => __( 'What is the name of your company or website?', 'newsletter-optin-box' ),
			),

			'logo_url'        => array(
				'el'          => 'input',
				'type'        => 'image',
				'section'     => 'emails',
				'label'       => __( 'Logo', 'newsletter-optin-box' ),
				'description' => __( 'Enter a full url to your logo. Works best with rectangular images.', 'newsletter-optin-box' ),
			),

			'email_template'  => array(
				'el'          => 'select',
				'section'     => 'emails',
				'label'       => __( 'Email Template', 'newsletter-optin-box' ),
				'placeholder' => __( 'Select a template', 'newsletter-optin-box' ),
				'options'     => array(
					'paste'        => __( 'Default', 'newsletter-optin-box' ),
					'merriweather' => __( 'Merriweather', 'newsletter-optin-box' ),
				),
				'default'     => 'paste',
				'description' => __( 'Select your preferred email template.', 'newsletter-optin-box' ),
			),

			'permission_text' => array(
				'el'          => 'textarea',
				'section'     => 'emails',
				'label'       => __( 'Permission reminder', 'newsletter-optin-box' ),
				'description' => sprintf(
					/* Translators: %s . [[unsubscribe_url]] */
					__( '%s will be replaced by a url to the unsubscription page.', 'newsletter-optin-box' ),
					'[[unsubscribe_url]]'
				),
				'placeholder' => noptin()->mailer->default_permission_text(),
				'default'     => noptin()->mailer->default_permission_text(),
			),

			'footer_text'     => array(
				'el'          => 'textarea',
				'section'     => 'emails',
				'label'       => __( 'Footer text', 'newsletter-optin-box' ),
				'placeholder' => noptin()->mailer->default_footer_text(),
				'default'     => noptin()->mailer->default_footer_text(),
				'description' => sprintf(
					/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
					__( 'This text appears below all emails. If you are a %1$sNoptin affiliate%2$s, include your affiliate link here and earn commissions for new referrals.', 'newsletter-optin-box' ),
					'<a href="https://noptin.com/become-an-affiliate/">',
					'</a>'
				),
			),

			'success_message'       => array(
				'el'          => 'input',
				'type'        => 'text',
				'section'     => 'general',
				'label'       => __( 'Default Success Message', 'newsletter-optin-box' ),
				'placeholder' => esc_attr__( 'Thanks for subscribing to our newsletter', 'newsletter-optin-box' ),
				'description' => __( 'This is the message shown to people after they successfully sign up for your newsletter.', 'newsletter-optin-box' ),
			),

			'ipgeolocation_io_api_key'       => array(
				'el'          => 'input',
				'type'        => 'text',
				'section'     => 'general',
				'label'       => __( 'GeoLocation API Key', 'newsletter-optin-box' ),
				'placeholder' => '****************************',
				'description' => sprintf( 
					__( 'Enter your %s API key if you want to GeoLocate your subscribers using their service.', 'newsletter-optin-box' ),
					'<a href="https://ipgeolocation.io/" target="_blank">ipgeolocation.io</a>'
				)
			),

		);

		/**
		 * Filters Noptin settings.
		 * 
		 * @param array $settings An array of Noptin settings.
		 */
		return apply_filters( 'noptin_get_settings', $settings );
	}


}
