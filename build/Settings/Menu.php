<?php
/**
 * Settings API: Settings Admin.
 *
 * Contains the main admin class for Noptin emails
 *
 * @since   3.2.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Settings;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main admin class for Noptin emails.
 *
 * @since 3.2.0
 * @internal
 * @ignore
 */
class Menu {

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
	 * @var string hook suffix
	 */
	public static $hook_suffix;

	/**
	 * Inits the main emails class.
	 *
	 */
	public static function init() {

		add_action( 'admin_menu', array( __CLASS__, 'settings_menu' ), 50 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Settings menu.
	 */
	public static function settings_menu() {

		self::$hook_suffix = add_submenu_page(
			'noptin',
			esc_html__( 'Settings', 'newsletter-optin-box' ),
			esc_html__( 'Settings', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-settings',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Displays the admin page.
	 */
	public static function render_admin_page() {

		// Check permission.
		if ( ! current_user_can( get_noptin_capability() ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'newsletter-optin-box' ) );
		}

		// Include the settings view.
		require_once plugin_dir_path( __FILE__ ) . 'view.php';
	}

	/**
	 * Enqueues scripts and styles.
	 *
	 * @param string $hook The current admin page.
	 */
	public static function enqueue_scripts( $hook ) {

		// Abort if not on the email campaigns page.
		if ( self::$hook_suffix !== $hook ) {
			return;
		}

		$config = include plugin_dir_path( __FILE__ ) . 'assets/js/settings.asset.php';

		wp_enqueue_media();

		wp_enqueue_script(
			'noptin-settings',
			plugins_url( 'assets/js/settings.js', __FILE__ ),
			$config['dependencies'],
			$config['version'],
			true
		);

		// Localize the script.
		wp_localize_script(
			'noptin-settings',
			'noptinSettings',
			array(
				'data' => array(
					'settings' => self::prepare_settings(),
					'saved'    => array_merge(
						get_noptin_options(),
						array(
							'custom_fields' => array_values( get_noptin_custom_fields() ),
						)
					),
					'brand'    => noptin()->white_label->get_details(),
				),
			)
		);

		wp_set_script_translations( 'noptin-settings', 'newsletter-optin-box', noptin()->plugin_path . 'languages' );

		// Load the css.
		wp_enqueue_style(
			'noptin-settings',
			plugins_url( 'assets/css/style-settings.css', __FILE__ ),
			array( 'wp-components' ),
			$config['version']
		);

		\Hizzle\Noptin\Misc\Main::load_interface_styles();
	}

	/**
	 * Prepares settings.
	 *
	 */
	public static function prepare_settings() {

		// Known sections.
		$sections = apply_filters(
			'noptin_get_setting_sections',
			array(
				'general'      => __( 'General', 'newsletter-optin-box' ),
				'emails'       => array(
					'label'    => __( 'Emails', 'newsletter-optin-box' ),
					'children' => array(
						'main'          => __( 'Emails', 'newsletter-optin-box' ),
						'double_opt_in' => __( 'Double Opt-In', 'newsletter-optin-box' ),
					),
				),
				'fields'       => __( 'Custom Fields', 'newsletter-optin-box' ),
				'integrations' => __( 'Integrations', 'newsletter-optin-box' ),
				'messages'     => __( 'Messages', 'newsletter-optin-box' ),
			)
		);

		// Add settings.
		$prepared = array();
		foreach ( self::get_settings() as $setting_key => $setting ) {

			// Do we have a section.
			if ( empty( $setting['section'] ) ) {
				$setting['section'] = 'general';
			}

			// Does section exist?
			if ( empty( $sections[ $setting['section'] ] ) ) {
				$sections[ $setting['section'] ] = ucwords( str_replace( '-', ' ', $setting['section'] ) );
			}

			// Ensure section is an array.
			if ( ! is_array( $sections[ $setting['section'] ] ) ) {
				$sections[ $setting['section'] ] = array(
					'label'    => $sections[ $setting['section'] ],
					'children' => array(
						'main' => $sections[ $setting['section'] ],
					),
				);
			}

			// Do we have a sub-section?
			if ( empty( $setting['sub_section'] ) ) {
				$setting['sub_section'] = current( array_keys( $sections[ $setting['section'] ]['children'] ) );
			}

			// If sub-section does not exist, add it.
			if ( empty( $sections[ $setting['section'] ]['children'][ $setting['sub_section'] ] ) ) {
				$sections[ $setting['section'] ]['children'][ $setting['sub_section'] ] = ucwords( str_replace( '-', ' ', $setting['sub_section'] ) );
			}

			// Add setting to section.
			if ( ! isset( $prepared[ $setting['section'] ] ) ) {
				$prepared[ $setting['section'] ] = array(
					'label'        => $sections[ $setting['section'] ]['label'],
					'sub_sections' => array(),
				);
			}

			if ( ! isset( $prepared[ $setting['section'] ]['sub_sections'][ $setting['sub_section'] ] ) ) {
				$prepared[ $setting['section'] ]['sub_sections'][ $setting['sub_section'] ] = array(
					'label'    => $sections[ $setting['section'] ]['children'][ $setting['sub_section'] ],
					'settings' => array(),
				);
			}

			$prepared[ $setting['section'] ]['sub_sections'][ $setting['sub_section'] ]['settings'][ $setting_key ] = $setting;
		}

		return $prepared;
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

		$double_optin       = get_default_noptin_subscriber_double_optin_email();
		$field_map_settings = apply_filters( 'noptin_get_custom_fields_map_settings', array() );
		$settings           = array(

			'general_info'        => array(
				'el'       => 'settings_group',
				'label'    => __( 'General', 'newsletter-optin-box' ),
				'section'  => 'general',
				'settings' => array(
					'track_campaign_stats'      => array(
						'label'       => __( 'Show campaign stats', 'newsletter-optin-box' ),
						'description' => __( 'Enable this to display opens and clicks on campaigns that you send.', 'newsletter-optin-box' ),
						'type'        => 'checkbox_alt',
						'el'          => 'input',
						'default'     => true,
					),
					'enable_ecommerce_tracking' => array(
						'label'            => __( 'Enable E-commerce Tracking', 'newsletter-optin-box' ),
						'description'      => __( 'Enable this to track revenue collected per email campaign.', 'newsletter-optin-box' ) . ( noptin_has_active_license_key() ? '' : ' ' . sprintf(
							'<a href="%s" target="_blank">%s</a>',
							noptin_get_upsell_url( '/pricing', 'settings', 'ecommerce-tracking' ),
							__( 'Activate your license key to unlock', 'newsletter-optin-box' )
						)),
						'type'             => 'checkbox_alt',
						'el'               => 'input',
						'default'          => noptin_has_active_license_key(),
						'customAttributes' => array(
							'disabled' => ! noptin_has_active_license_key(),
						),
						'conditions'       => array(
							array(
								'key'      => 'track_campaign_stats',
								'operator' => '==',
								'value'    => true,
							),
						),
					),
					'allow_editors'             => array(
						'label'       => __( 'Allow editors', 'newsletter-optin-box' ),
						'description' => __( 'Allow editors to access and manage Noptin.', 'newsletter-optin-box' ),
						'type'        => 'checkbox_alt',
						'el'          => 'input',
						'default'     => false,
					),
					'keep_data_on_uninstall'    => array(
						'label'       => __( 'Keep data on uninstall', 'newsletter-optin-box' ),
						'description' => __( 'Keep all data when the plugin is uninstalled.', 'newsletter-optin-box' ),
						'type'        => 'checkbox_alt',
						'el'          => 'input',
						'default'     => false,
					),
				),
			),

			'subscription_msg'    => array(
				'el'       => 'settings_group',
				'label'    => __( 'Subscription', 'newsletter-optin-box' ),
				'section'  => 'messages',
				'settings' => array(
					'success_message'            => array(
						'el'          => 'input',
						'type'        => 'text',
						'label'       => __( 'Default Success Message', 'newsletter-optin-box' ),
						'placeholder' => __( 'Thanks for subscribing to our newsletter', 'newsletter-optin-box' ),
						'default'     => __( 'Thanks for subscribing to our newsletter', 'newsletter-optin-box' ),
						'tooltip'     => __( 'This is the message shown to people after they successfully sign up for your newsletter.', 'newsletter-optin-box' ),
					),
					'already_subscribed_message' => array(
						'el'          => 'input',
						'type'        => 'text',
						'label'       => __( 'Already subscribed message', 'newsletter-optin-box' ),
						'placeholder' => __( 'You are already subscribed to the newsletter, thank you!', 'newsletter-optin-box' ),
						'default'     => __( 'You are already subscribed to the newsletter, thank you!', 'newsletter-optin-box' ),
						'tooltip'     => __( 'Shown when an existing subscriber tries to sign-up again.', 'newsletter-optin-box' ),
					),
				),
			),

			'subscription_info'   => array(
				'el'       => 'settings_group',
				'label'    => __( 'Email Subscribers', 'newsletter-optin-box' ),
				'section'  => 'general',
				'settings' => array(
					'hide_from_subscribers' => array(
						'el'          => 'input',
						'type'        => 'checkbox_alt',
						'label'       => __( 'Hide From Subscribers', 'newsletter-optin-box' ),
						'default'     => false,
						'description' => __( 'Hide opt-in forms and methods from existing subscribers.', 'newsletter-optin-box' ),
					),
					'always_show_to_admin'  => array(
						'el'          => 'input',
						'type'        => 'checkbox_alt',
						'label'       => __( 'Always Show to Admin', 'newsletter-optin-box' ),
						'default'     => true,
						'description' => __( 'Always show opt-in forms and methods to administrators even if they are already subscribed.', 'newsletter-optin-box' ),
						'conditions'  => array(
							array(
								'key'      => 'hide_from_subscribers',
								'operator' => '==',
								'value'    => true,
							),
						),
					),
					'subscribers_cookie'    => array(
						'el'          => 'input',
						'type'        => 'text',
						'label'       => __( 'Subscription Cookie', 'newsletter-optin-box' ),
						'placeholder' => '',
						'tooltip'     => __( 'If you are migrating from another email plugin, enter the cookie name they used to identify subscribers.', 'newsletter-optin-box' ),
					),
				),
			),
			'general_email_info'  => array(
				'el'       => 'settings_group',
				'label'    => __( 'General', 'newsletter-optin-box' ),
				'section'  => 'emails',
				'settings' => array(
					'reply_to'           => array(
						'el'      => 'input',
						'section' => 'emails',
						'type'    => 'email',
						'label'   => __( '"Reply-to" Email', 'newsletter-optin-box' ),
						'default' => get_option( 'admin_email' ),
						'tooltip' => __( 'Where should subscribers reply to in case they need to get in touch with you?', 'newsletter-optin-box' ),
					),

					'from_email'         => array(
						'el'      => 'input',
						'section' => 'emails',
						'type'    => 'email',
						'label'   => __( '"From" Email', 'newsletter-optin-box' ),
						'tooltip' => __( 'How the sender email appears in outgoing emails. Leave this field blank if you are not able to send any emails.', 'newsletter-optin-box' ),
					),

					'from_name'          => array(
						'el'          => 'input',
						'section'     => 'emails',
						'label'       => __( '"From" Name', 'newsletter-optin-box' ),
						'placeholder' => get_option( 'blogname' ),
						'default'     => get_option( 'blogname' ),
						'tooltip'     => __( 'How the sender name appears in outgoing emails', 'newsletter-optin-box' ),
					),

					'per_hour'           => array(
						'el'          => 'input',
						'type'        => 'number',
						'section'     => 'emails',
						'label'       => __( 'Emails Per Hour', 'newsletter-optin-box' ),
						'placeholder' => __( 'Unlimited', 'newsletter-optin-box' ),
						'tooltip'     => __( 'The maximum number of emails to send per hour. Leave empty to send as many as possible.', 'newsletter-optin-box' ),
					),

					'bounce_webhook_url' => array(
						'el'          => 'input',
						'type'        => 'text',
						'section'     => 'emails',
						'readonly'    => true,
						'label'       => __( 'Bounce Handler', 'newsletter-optin-box' ),
						'default'     => noptin()->api()->bounce_handler->service_url( '{{YOUR_SERVICE}}' ),
						'placeholder' => noptin()->api()->bounce_handler->service_url( '{{YOUR_SERVICE}}' ),
						'description' => sprintf(
							// translators: %s is the list of supported services.
							__( 'Supported services:- %s', 'newsletter-optin-box' ),
							implode(
								', ',
								array_map(
									function ( $args, $service ) {
										return sprintf(
											'<a href="%s" target="_blank">%s</a>',
											esc_url( $args['url'] ),
											esc_html( $service )
										);
									},
									noptin()->api()->bounce_handler->get_supported_services(),
									array_keys( noptin()->api()->bounce_handler->get_supported_services() )
								)
							)
						),
						'disabled'    => true,
					),

					'delete_campaigns'   => array(
						'el'               => 'input',
						'type'             => 'number',
						'section'          => 'emails',
						'label'            => __( 'Auto-Delete Campaigns', 'newsletter-optin-box' ),
						'placeholder'      => __( 'Never Delete', 'newsletter-optin-box' ),
						'tooltip'          => __( 'The number of days after which to delete a sent campaign. Leave empty if you do not want to automatically delete campaigns.', 'newsletter-optin-box' ),
						'customAttributes' => array(
							'min'    => 0,
							'prefix' => __( 'After', 'newsletter-optin-box' ),
							'suffix' => array( __( 'day', 'newsletter-optin-box' ), __( 'days', 'newsletter-optin-box' ) ),
						),
					),
				),
			),

			'brand_info'          => array(
				'el'       => 'settings_group',
				'label'    => __( 'Brand Info', 'newsletter-optin-box' ),
				'section'  => 'emails',
				'settings' => array(
					'company'     => array(
						'el'          => 'input',
						'label'       => __( 'Company', 'newsletter-optin-box' ),
						'placeholder' => get_option( 'blogname' ),
						'tooltip'     => __( 'What is the name of your company or website?', 'newsletter-optin-box' ),
					),
					'logo_url'    => array(
						'el'      => 'input',
						'type'    => 'image',
						'label'   => __( 'Logo', 'newsletter-optin-box' ),
						'tooltip' => __( 'Enter a full url to your logo. Works best with rectangular images.', 'newsletter-optin-box' ),
					),
					'brand_color' => array(
						'el'          => 'color',
						'label'       => __( 'Brand Color', 'newsletter-optin-box' ),
						'placeholder' => '#1a82e2',
						'default'     => '#1a82e2',
						'description' => __( 'Used as the link color and button background.', 'newsletter-optin-box' ),
					),
				),
			),

			'template_info'       => array(
				'el'       => 'settings_group',
				'label'    => __( 'Email Template', 'newsletter-optin-box' ),
				'section'  => 'emails',
				'settings' => array(
					'email_template' => array(
						'el'          => 'select',
						'label'       => __( 'Email Template', 'newsletter-optin-box' ),
						'placeholder' => __( 'Select a template', 'newsletter-optin-box' ),
						'options'     => get_noptin_email_templates(),
						'default'     => 'paste',
						'tooltip'     => __( 'Select "No Template" if you are using an email templates plugin.', 'newsletter-optin-box' ),
					),
					'footer_text'    => array(
						'el'          => 'textarea',
						'label'       => __( 'Footer text', 'newsletter-optin-box' ),
						'placeholder' => get_default_noptin_footer_text(),
						'default'     => get_default_noptin_footer_text(),
						'tooltip'     => __( 'This text appears below all emails.', 'newsletter-optin-box' ),
					),
					'custom_css'     => array(
						'el'      => 'textarea',
						'label'   => __( 'Custom CSS', 'newsletter-optin-box' ),
						'tooltip' => __( 'Optional. Add any custom CSS to style your emails.', 'newsletter-optin-box' ),
					),
				),
			),

			'enable_double_optin' => array(
				'el'          => 'settings_group',
				'label'       => __( 'Enable Double Opt-in', 'newsletter-optin-box' ),
				'section'     => 'emails',
				'sub_section' => 'double_opt_in',
				'settings'    => array(
					'double_optin'               => array(
						'el'          => 'input',
						'type'        => 'checkbox_alt',
						'label'       => __( 'Double Opt-in', 'newsletter-optin-box' ),
						'description' => __( 'Require new subscribers to confirm their email addresses.', 'newsletter-optin-box' ),
						'default'     => false,
					),

					'disable_double_optin_email' => array(
						'el'          => 'input',
						'type'        => 'checkbox_alt',
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
				),
			),

			'double_optin_email'  => array(
				'el'          => 'settings_group',
				'label'       => __( 'Double Opt-in Email', 'newsletter-optin-box' ),
				'section'     => 'emails',
				'sub_section' => 'double_opt_in',
				'conditions'  => array(
					array(
						'key'      => 'double_optin',
						'operator' => '==',
						'value'    => true,
					),
					array(
						'key'      => 'disable_double_optin_email',
						'operator' => '!=',
						'value'    => true,
					),
				),
				'settings'    => array(
					'double_optin_email_subject'   => array(
						'el'          => 'input',
						'label'       => __( 'Email Subject', 'newsletter-optin-box' ),
						'default'     => $double_optin['email_subject'],
						'placeholder' => $double_optin['email_subject'],
						'tooltip'     => __( 'The subject of the subscription confirmation email', 'newsletter-optin-box' ),
					),

					'double_optin_hero_text'       => array(
						'el'          => 'input',
						'label'       => __( 'Email Title', 'newsletter-optin-box' ),
						'default'     => $double_optin['hero_text'],
						'placeholder' => $double_optin['hero_text'],
						'tooltip'     => __( 'The title of the email', 'newsletter-optin-box' ),
					),

					'double_optin_email_body'      => array(
						'el'          => 'textarea',
						'label'       => __( 'Email Body', 'newsletter-optin-box' ),
						'placeholder' => $double_optin['email_body'],
						'default'     => $double_optin['email_body'],
						'tooltip'     => __( 'This is the main content of the email', 'newsletter-optin-box' ),
					),

					'double_optin_cta_text'        => array(
						'el'          => 'input',
						'label'       => __( 'Call to Action', 'newsletter-optin-box' ),
						'default'     => $double_optin['cta_text'],
						'placeholder' => $double_optin['cta_text'],
						'tooltip'     => __( 'The text of the call to action button', 'newsletter-optin-box' ),
					),

					'double_optin_after_cta_text'  => array(
						'el'          => 'textarea',
						'label'       => __( 'Extra Text', 'newsletter-optin-box' ),
						'default'     => $double_optin['after_cta_text'],
						'placeholder' => $double_optin['after_cta_text'],
						'tooltip'     => __( 'This text is shown after the call to action button', 'newsletter-optin-box' ),
					),

					'double_optin_permission_text' => array(
						'el'          => 'textarea',
						'label'       => __( 'Permission Text', 'newsletter-optin-box' ),
						'default'     => $double_optin['permission_text'],
						'placeholder' => $double_optin['permission_text'],
						'tooltip'     => __( 'Remind the subscriber how they signed up.', 'newsletter-optin-box' ),
					),
				),
			),

			'custom_fields'       => array(
				'el'               => 'repeater',
				'section'          => 'fields',
				'label'            => __( 'Custom Fields', 'newsletter-optin-box' ),
				'default'          => \Noptin_Custom_Fields::default_fields(),
				'description'      => sprintf(
					'%s <a href="https://noptin.com/guide/email-subscribers/custom-fields/" target="_blank">%s</a>',
					__( 'Collect more information from your subscribers by adding custom fields. ', 'newsletter-optin-box' ),
					__( 'Learn More', 'newsletter-optin-box' )
				),
				'customAttributes' => array(
					'repeaterKey'         => array(
						'from'      => 'label',
						'to'        => 'merge_tag',
						'newOnly'   => true,
						'maxLength' => 20,
						'display'   => '[[%s]]',
					),
					'defaultItem'         => array(
						'predefined' => false,
					),
					'hideLabelFromVision' => true,
					'fields'              => array_merge(
						array(
							'type'          => array(
								'el'          => 'select',
								'label'       => __( 'Field Type', 'newsletter-optin-box' ),
								'options'     => wp_list_pluck(
									wp_list_filter(
										get_noptin_custom_field_types(),
										array( 'predefined' => false )
									),
									'label'
								),
								'description' => __( 'Select the field type', 'newsletter-optin-box' ),
								'default'     => 'text',
								'conditions'  => array(
									array(
										'key'      => 'type',
										'operator' => '!includes',
										'value'    => \Noptin_Custom_Fields::predefined_fields(),
									),
								),
							),
							'label'         => array(
								'el'          => 'input',
								'label'       => __( 'Field Name', 'newsletter-optin-box' ),
								'description' => __( 'Enter a descriptive name for the field, for example, Phone Number', 'newsletter-optin-box' ),
							),
							'placeholder'   => array(
								'el'          => 'input',
								'label'       => __( 'Placeholder', 'newsletter-optin-box' ),
								'description' => __( 'Optional. Enter the default placeholder for this field', 'newsletter-optin-box' ),
								'conditions'  => array(
									array(
										'key'      => 'type',
										'operator' => 'includes',
										'value'    => array( 'text', 'textarea', 'number', 'email', 'first_name', 'last_name' ),
									),
								),
							),
							'options'       => array(
								'el'          => 'textarea',
								'label'       => __( 'Available Options', 'newsletter-optin-box' ),
								'description' => __( 'Enter one option per line. You can use pipes to separate values and labels.', 'newsletter-optin-box' ),
								'conditions'  => array(
									array(
										'key'      => 'type',
										'operator' => 'includes',
										'value'    => \Noptin_Custom_Fields::option_fields(),
									),
								),
								'placeholder' => implode( PHP_EOL, array( 'Option 1', 'Option 2', 'Option 3' ) ),
							),
							'default_value' => array(
								'el'          => 'input',
								'label'       => __( 'Default value', 'newsletter-optin-box' ),
								'description' => __( 'Optional. Enter the default value for this field', 'newsletter-optin-box' ),
							),
						),
						$field_map_settings,
						array(
							'visible'  => array(
								'el'          => 'input',
								'type'        => 'checkbox_alt',
								'label'       => __( 'Editable', 'newsletter-optin-box' ),
								'description' => __( "Can subscriber's view and edit this field?", 'newsletter-optin-box' ),
								'default'     => true,
								'conditions'  => array(
									array(
										'key'      => 'merge_tag',
										'operator' => '!=',
										'value'    => 'email',
									),
								),
							),
							'required' => array(
								'el'          => 'input',
								'type'        => 'checkbox_alt',
								'label'       => __( 'Required', 'newsletter-optin-box' ),
								'description' => __( 'Subscribers MUST fill this field whenever it is added to a subscription form.', 'newsletter-optin-box' ),
								'conditions'  => array(
									array(
										'key'      => 'merge_tag',
										'operator' => '!=',
										'value'    => 'email',
									),
								),
							),
						)
					),
				),
			),

		);

		if ( ! noptin_supports_ecommerce_tracking() ) {
			unset( $settings['general']['settings']['enable_ecommerce_tracking'] );
		}

		$integration_settings = apply_filters( 'noptin_get_integration_settings', array() );
		ksort( $integration_settings );

		if ( noptin_upsell_integrations() ) {
			foreach ( \Noptin_COM::get_connections() as $data ) {

				$slug = sanitize_key( str_replace( '-', '_', $data->slug ) );

				if ( isset( $integration_settings[ "settings_section_$slug" ] ) ) {
					continue;
				}

				$integration_settings[ "settings_section_$slug" ] = array(
					'id'          => "settings_section_$slug",
					'heading'     => esc_html( $data->name ),
					'section'     => 'integrations',
					'description' => sprintf(
						// translators: %s is the name of the integration.
						__( 'Connects Noptin to %s', 'newsletter-optin-box' ),
						esc_html( $data->name )
                    ),
					'el'          => 'integration_panel',
					'badges'      => array(
						array(
							'text'  => __( 'Not Installed', 'newsletter-optin-box' ),
							'props' => array(
								'variant' => 'muted',
							),
						),
					),
					'settings'    => array(
						"noptin_{$slug}_install" => array(
							'el'      => 'paragraph',
							'raw'     => true,
							'content' => sprintf(
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
