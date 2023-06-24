<?php
/**
 * Contains the settings handler.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings handler class.
 */
class Noptin_Settings {

	/**
	 * Setting sections.
	 *
	 * @var array
	 */
	protected static $sections;

	/**
	 * Settings.
	 *
	 * @var array
	 */
	protected static $settings;

	/**
	 * Current state.
	 *
	 * @var array
	 */
	protected static $state;

	/**
	 * Class constructor.
	 *
	 * It's protected since we do not want anyone to create a new instance of the class.
	 * It's here purely for encapsulation.
	 */
	protected function __construct() {}

	/**
	 * Render settings.
	 */
	public static function output() {
		add_thickbox();
		get_noptin_template( 'settings.php' );
	}

	/**
	 * Returns all setting sections.
	 *
	 * @return array
	 */
	public static function get_sections() {

		if ( ! empty( self::$sections ) ) {
			return self::$sections;
		}

		// Known sections.
		$sections = apply_filters(
			'noptin_get_setting_sections',
			array(
				'general'      => __( 'General', 'newsletter-optin-box' ),
				'emails'       => array(
					'label'    => __( 'Emails', 'newsletter-optin-box' ),
					'children' => array(
						'main'          => __( 'Emails', 'newsletter-optin-box' ),
						'double_opt_in' => __( 'Double Opt-In Email', 'newsletter-optin-box' ),
					),
				),
				'fields'       => __( 'Custom Fields', 'newsletter-optin-box' ),
				'integrations' => __( 'Integrations', 'newsletter-optin-box' ),
				'messages'     => __( 'Messages', 'newsletter-optin-box' ),
			)
		);

		// Add unknown sections.
		foreach ( self::get_settings() as $setting ) {

			// Do we have a section.
			if ( empty( $setting['section'] ) ) {
				continue;
			}

			// If yes, ensure that it is set.
			$section = $setting['section'];
			if ( empty( $sections[ $section ] ) ) {
				$sections[ $section ] = ucwords( str_replace( '-', ' ', $section ) );
			}

			// If we have a sub-section, maybe add it.
			if ( ! empty( $setting['sub_section'] ) ) {

				$sub_section = $setting['sub_section'];

				// Sections that have subsections are usually arrays.
				if ( ! is_array( $sections[ $section ] ) ) {
					$sections[ $section ]   = array(
						'label'    => $sections[ $section ],
						'children' => array(
							'main' => $sections[ $section ],
						),
					);
				}

				if ( empty( $sections[ $section ]['children'][ $sub_section ] ) ) {
					$sections[ $section ]['children'][ $sub_section ] = ucwords( str_replace( '-', ' ', $sub_section ) );
				}
			}
		}

		// Cache it.
		self::$sections = $sections;

		return $sections;

	}

	/**
	 * Returns a section conditional
	 *
	 * @return string
	 */
	public static function get_section_conditional( $args ) {

		// Ensure there is a section.
		if ( empty( $args['section'] ) ) {
			return '';
		}

		$section     = esc_attr( $args['section'] );
		$sub_section = empty( $args['sub_section'] ) ? 'main' : esc_attr( $args['sub_section'] );

		return "v-show=\"currentTab=='$section' && currentSection=='$sub_section' \"";

	}

	/**
	 * Returns a section conditional
	 *
	 * @return string
	 */
	public static function section_conditional( $args ) {

		// Ensure there is a section.
		if ( ! empty( $args['section'] ) ) {
			printf(
				'v-show="currentTab==\'%s\' && currentSection==\'%s\' "',
				esc_attr( $args['section'] ),
				empty( $args['sub_section'] ) ? 'main' : esc_attr( $args['sub_section'] )
			);
		}

	}

	/**
	 * Returns the current state
	 *
	 * @return array
	 */
	public static function get_state() {

		if ( ! empty( self::$state ) ) {
			return self::$state;
		}

		// Prepare options.
		$state = array();

		foreach ( self::get_settings() as $key => $args ) {

			if ( ! empty( $args['el'] ) && 'settings_section' === $args['el'] ) {

				foreach ( $args['children'] as $key => $args ) {
					$default       = isset( $args['default'] ) ? $args['default'] : '';
					$state[ $key ] = get_noptin_option( $key, $default );
				}
			} else {
				$default       = isset( $args['default'] ) ? $args['default'] : '';
				$state[ $key ] = get_noptin_option( $key, $default );
			}
		}

		$state                   = array_merge( get_noptin_options(), $state );
		$state['custom_fields']  = array_values( get_noptin_custom_fields() );
		$state['openSections']   = isset( $_GET['integration'] ) ? array( 'settings_section_' . noptin_clean( $_GET['integration'] ) ) : array();
		$state['currentTab']     = isset( $_GET['tab'] ) ? noptin_clean( $_GET['tab'] ) : 'general';
		$state['currentSection'] = 'main';
		$state['saved']          = __( 'Your settings have been saved', 'newsletter-optin-box' );
		$state['error']          = __( 'Your settings could not be saved.', 'newsletter-optin-box' );
		$state['fieldTypes']     = get_noptin_custom_field_types();

		// Cache this.
		self::$state = apply_filters( 'noptin_settings_state', $state );

		return self::$state;

	}

	/**
	 * Returns all settings fields
	 *
	 * @return array
	 */
	public static function get_settings() {

		if ( ! empty( self::$settings ) ) {
			return self::$settings;
		}

		$double_optin = get_default_noptin_subscriber_double_optin_email();
		$settings     = array(

			'double_optin'                 => array(
				'el'          => 'input',
				'type'        => 'checkbox_alt',
				'section'     => 'general',
				'label'       => __( 'Double Opt-in', 'newsletter-optin-box' ),
				'description' => __( 'Require new subscribers to confirm their email addresses.', 'newsletter-optin-box' ),
				'default'     => false,
			),

			'disable_double_optin_email'   => array(
				'el'          => 'input',
				'type'        => 'checkbox_alt',
				'section'     => 'general',
				'label'       => __( 'Disable default double opt-in email', 'newsletter-optin-box' ),
				'default'     => false,
				'description' => sprintf(
					'%s <a href="%s" target="_blank">%s</a>',
					__( 'You can disable the default double opt-in email if you wish to use a custom email or set-up different emails.', 'newsletter-optin-box' ),
					noptin_get_upsell_url( '/guide/email-subscribers/double-opt-in/#how-to-customize-the-email-or-set-up-multiple-double-opt-in-emails', 'double-opt', 'settings' ),
					__( 'Learn more', 'newsletter-optin-box' )
				),
				'restrict'    => 'double_optin',
			),

			'hide_from_subscribers'        => array(
				'el'          => 'input',
				'type'        => 'checkbox_alt',
				'section'     => 'general',
				'label'       => __( 'Hide From Subscribers', 'newsletter-optin-box' ),
				'default'     => false,
				'description' => __( 'Hide opt-in forms and methods from existing subscribers.', 'newsletter-optin-box' ),
			),

			'track_campaign_stats'         => array(
				'label'       => __( 'Show campaign stats', 'newsletter-optin-box' ),
				'description' => __( 'Enable this to display opens and clicks on campaigns that you send.', 'newsletter-optin-box' ),
				'type'        => 'checkbox_alt',
				'section'     => 'general',
				'el'          => 'input',
				'default'     => true,
			),

			'subscribers_cookie'           => array(
				'el'          => 'input',
				'type'        => 'text',
				'section'     => 'general',
				'label'       => __( 'Subscription Cookie', 'newsletter-optin-box' ),
				'placeholder' => '',
				'description' => __( 'If you are migrating from another email plugin, enter the cookie name they used to identify subscribers.', 'newsletter-optin-box' ),
			),

			'admin_email'                  => array(
				'el'          => 'input',
				'section'     => 'emails',
				'type'        => 'text',
				'label'       => __( 'Notification recipient(s)', 'newsletter-optin-box' ),
				'class'       => 'regular-text',
				'default'     => get_option( 'admin_email' ),
				'description' => __( 'Enter a comma separated list of email address that should receive new subscriber notifications', 'newsletter-optin-box' ),
			),

			'reply_to'                     => array(
				'el'          => 'input',
				'section'     => 'emails',
				'type'        => 'email',
				'label'       => __( '"Reply-to" Email', 'newsletter-optin-box' ),
				'class'       => 'regular-text',
				'default'     => get_option( 'admin_email' ),
				'description' => __( 'Where should subscribers reply to in case they need to get in touch with you?', 'newsletter-optin-box' ),
			),

			'from_email'                   => array(
				'el'          => 'input',
				'section'     => 'emails',
				'type'        => 'email',
				'label'       => __( '"From" Email', 'newsletter-optin-box' ),
				'class'       => 'regular-text',
				'description' => __( 'How the sender email appears in outgoing emails. Leave this field blank if you are not able to send any emails.', 'newsletter-optin-box' ),
			),

			'from_name'                    => array(
				'el'          => 'input',
				'section'     => 'emails',
				'label'       => __( '"From" Name', 'newsletter-optin-box' ),
				'class'       => 'regular-text',
				'placeholder' => get_option( 'blogname' ),
				'default'     => get_option( 'blogname' ),
				'description' => __( 'How the sender name appears in outgoing emails', 'newsletter-optin-box' ),
			),

			'per_hour'                     => array(
				'el'          => 'input',
				'type'        => 'number',
				'section'     => 'emails',
				'label'       => __( 'Emails Per Hour', 'newsletter-optin-box' ),
				'class'       => 'regular-text',
				'placeholder' => __( 'Unlimited', 'newsletter-optin-box' ),
				'description' => __( 'The maximum number of emails to send per hour. Leave empty to send as many as possible.', 'newsletter-optin-box' ),
			),

			'delete_campaigns'             => array(
				'el'          => 'input',
				'type'        => 'number',
				'section'     => 'emails',
				'label'       => __( 'Delete Campaigns', 'newsletter-optin-box' ),
				'class'       => 'regular-text',
				'placeholder' => __( 'Never Delete', 'newsletter-optin-box' ),
				'description' => __( 'The number of days after which to delete a sent campaign. Leave empty to if you do not want to automatically delete campaigns.', 'newsletter-optin-box' ),
			),

			'company'                      => array(
				'el'          => 'input',
				'section'     => 'emails',
				'label'       => __( 'Company', 'newsletter-optin-box' ),
				'class'       => 'regular-text',
				'placeholder' => get_option( 'blogname' ),
				'description' => __( 'What is the name of your company or website?', 'newsletter-optin-box' ),
			),

			'logo_url'                     => array(
				'el'          => 'input',
				'type'        => 'image',
				'section'     => 'emails',
				'label'       => __( 'Logo', 'newsletter-optin-box' ),
				'description' => __( 'Enter a full url to your logo. Works best with rectangular images.', 'newsletter-optin-box' ),
			),

			'brand_color'                  => array(
				'el'          => 'input',
				'type'        => 'text',
				'section'     => 'emails',
				'label'       => __( 'Brand Color', 'newsletter-optin-box' ),
				'placeholder' => '#1a82e2',
				'default'     => '#1a82e2',
				'description' => __( 'Used as the link color and button background.', 'newsletter-optin-box' ),
			),

			'email_template'               => array(
				'el'          => 'select',
				'section'     => 'emails',
				'label'       => __( 'Email Template', 'newsletter-optin-box' ),
				'placeholder' => __( 'Select a template', 'newsletter-optin-box' ),
				'options'     => get_noptin_email_templates(),
				'default'     => 'paste',
				'description' => sprintf(
					'%s %s',
					__( 'Select "No Template" if you are using an email templates plugin.', 'newsletter-optin-box' ),
					sprintf(
						'<br /><a href="%s" class="thickbox open-plugin-details-modal">%s</a>',
						esc_url(
							admin_url( 'plugin-install.php?tab=plugin-information&plugin=email-customizer&TB_iframe=true&width=772&height=560' )
						),
						esc_html__( 'Or install our free email templates plugin to design your own templates.', 'newsletter-optin-box' )
					)
				),
			),

			'footer_text'                  => array(
				'el'          => 'textarea',
				'section'     => 'emails',
				'label'       => __( 'Footer text', 'newsletter-optin-box' ),
				'placeholder' => get_default_noptin_footer_text(),
				'default'     => get_default_noptin_footer_text(),
				'description' => __( 'This text appears below all emails.', 'newsletter-optin-box' ),
			),

			'custom_css'                   => array(
				'el'          => 'textarea',
				'section'     => 'emails',
				'label'       => __( 'Custom CSS', 'newsletter-optin-box' ),
				'description' => __( 'Optional. Add any custom CSS to style your emails.', 'newsletter-optin-box' ),
			),

			'success_message'              => array(
				'el'          => 'input',
				'type'        => 'text',
				'section'     => 'general',
				'label'       => __( 'Default Success Message', 'newsletter-optin-box' ),
				'placeholder' => esc_attr__( 'Thanks for subscribing to our newsletter', 'newsletter-optin-box' ),
				'description' => __( 'This is the message shown to people after they successfully sign up for your newsletter.', 'newsletter-optin-box' ),
			),

			'ipgeolocation_io_api_key'     => array(
				'el'          => 'input',
				'type'        => 'text',
				'section'     => 'general',
				'label'       => __( 'GeoLocation API Key', 'newsletter-optin-box' ),
				'placeholder' => '',
				'description' => sprintf(
					// Translators: %s Link to the IP location provider.
					__( 'Enter your %s API key if you want to GeoLocate your subscribers using their service.', 'newsletter-optin-box' ),
					'<a href="https://ipgeolocation.io/" target="_blank">ipgeolocation.io</a>'
				),
			),

			'double_optin_email_subject'   => array(
				'el'          => 'input',
				'section'     => 'emails',
				'sub_section' => 'double_opt_in',
				'label'       => __( 'Email Subject', 'newsletter-optin-box' ),
				'class'       => 'regular-text',
				'default'     => $double_optin['email_subject'],
				'placeholder' => $double_optin['email_subject'],
				'description' => __( 'The subject of the subscription confirmation email', 'newsletter-optin-box' ),
			),

			'double_optin_hero_text'       => array(
				'el'          => 'input',
				'section'     => 'emails',
				'sub_section' => 'double_opt_in',
				'label'       => __( 'Email Title', 'newsletter-optin-box' ),
				'class'       => 'regular-text',
				'default'     => $double_optin['hero_text'],
				'placeholder' => $double_optin['hero_text'],
				'description' => __( 'The title of the email', 'newsletter-optin-box' ),
			),

			'double_optin_email_body'      => array(
				'el'          => 'textarea',
				'section'     => 'emails',
				'sub_section' => 'double_opt_in',
				'label'       => __( 'Email Body', 'newsletter-optin-box' ),
				'placeholder' => $double_optin['email_body'],
				'default'     => $double_optin['email_body'],
				'description' => __( 'This is the main content of the email', 'newsletter-optin-box' ),
			),

			'double_optin_cta_text'        => array(
				'el'          => 'input',
				'section'     => 'emails',
				'sub_section' => 'double_opt_in',
				'label'       => __( 'Call to Action', 'newsletter-optin-box' ),
				'class'       => 'regular-text',
				'default'     => $double_optin['cta_text'],
				'placeholder' => $double_optin['cta_text'],
				'description' => __( 'The text of the call to action button', 'newsletter-optin-box' ),
			),

			'double_optin_after_cta_text'  => array(
				'el'          => 'textarea',
				'section'     => 'emails',
				'sub_section' => 'double_opt_in',
				'label'       => __( 'Extra Text', 'newsletter-optin-box' ),
				'default'     => $double_optin['after_cta_text'],
				'placeholder' => $double_optin['after_cta_text'],
				'description' => __( 'This text is shown after the call to action button', 'newsletter-optin-box' ),
			),

			'double_optin_permission_text' => array(
				'el'          => 'textarea',
				'section'     => 'emails',
				'sub_section' => 'double_opt_in',
				'label'       => __( 'Permission Text', 'newsletter-optin-box' ),
				'default'     => $double_optin['permission_text'],
				'placeholder' => $double_optin['permission_text'],
				'description' => __( 'Remind the subscriber how they signed up.', 'newsletter-optin-box' ),
			),

			'custom_fields'                => array(
				'el'      => 'custom_fields',
				'section' => 'fields',
				'label'   => __( 'Custom Fields', 'newsletter-optin-box' ),
				'default' => Noptin_Custom_Fields::default_fields(),
			),

		);

		$integration_settings = apply_filters( 'noptin_get_integration_settings', array() );
		ksort( $integration_settings );

		if ( noptin_upsell_integrations() ) {
			foreach ( Noptin_COM::get_connections() as $data ) {

				$slug = sanitize_key( str_replace( '-', '_', $data->slug ) );

				if ( isset( $integration_settings[ "settings_section_$slug" ] ) ) {
					continue;
				}

				$integration_settings[ "settings_section_$slug" ] = array(
					'id'          => "settings_section_$slug",
					'el'          => 'settings_section',
					'class'       => 'not-installed',
					'children'    => array(
						"noptin_{$slug}_install" => array(
							'el'      => 'paragraph',
							'section' => 'integrations',
							'content' => '<span class="dashicons dashicons-info" style="margin-right: 10px; color: #03a9f4; "></span>' . sprintf(
								// translators: %s is the name of the integration.
								esc_html__( 'Install the %s to use it with Noptin.', 'newsletter-optin-box' ),
								sprintf(
									'<a target="_blank" href="%s">%s</a>',
									esc_url( noptin_get_upsell_url( $data->connect_url, $slug, 'settings' ) ),
									sprintf(
										// translators: %s is the name of the integration.
										__( '%s addon', 'newsletter-optin-box' ),
										esc_html( $data->name )
									)
								)
							),
						),
					),
					'section'     => 'integrations',
					'heading'     => esc_html( $data->name ),
					'description' => sprintf(
						// translators: %s is the name of the integration.
						__( 'Connects Noptin to %s', 'newsletter-optin-box' ),
						esc_html( $data->name )
					),
					'badge'       => __( 'Not Installed', 'newsletter-optin-box' ),
				);

			}
		}

		$settings = array_merge(
			$settings,
			$integration_settings
		);

		// Filter the settings.
		$settings = apply_filters( 'noptin_get_settings', $settings );

		// Cache them.
		self::$settings = $settings;

		return $settings;

	}

}
