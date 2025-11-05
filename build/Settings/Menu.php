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

		// Abort if not on the settings page.
		if ( self::$hook_suffix !== $hook ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_script( 'hizzlewp-settings' );

		// Localize the script.
		wp_localize_script(
			'hizzlewp-settings',
			'hizzleWPSettings',
			array(
				'data' => array(
					'settings'    => self::prepare_settings(),
					'saved'       => array_merge(
						get_noptin_options(),
						array(
							'custom_fields' => array_values( get_noptin_custom_fields() ),
						)
					),
					'brand'       => noptin()->white_label->get_details(),
					'option_name' => 'noptin_options',
				),
			)
		);

		wp_set_script_translations( 'hizzlewp-settings', 'newsletter-optin-box', noptin()->plugin_path . 'languages' );
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

		$settings = array(

			'general_info'      => array(
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
						'description'      => __( 'Enable this to track revenue collected per email campaign.', 'newsletter-optin-box' ) . ( noptin_has_alk() ? '' : ' ' . sprintf(
							'<a href="%s" target="_blank">%s</a>',
							noptin_get_upsell_url( '/pricing', 'settings', 'ecommerce-tracking' ),
							__( 'Activate your license key to unlock', 'newsletter-optin-box' )
						) ),
						'type'             => 'checkbox_alt',
						'el'               => 'input',
						'default'          => noptin_has_alk(),
						'customAttributes' => array(
							'disabled' => ! noptin_has_alk(),
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

			'brand_info'        => array(
				'el'       => 'settings_group',
				'label'    => __( 'Brand Info', 'newsletter-optin-box' ),
				'section'  => 'general',
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

			'subscription_msg'  => array(
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

			'subscription_info' => array(
				'el'       => 'settings_group',
				'label'    => __( 'Email Subscribers', 'newsletter-optin-box' ),
				'section'  => 'general',
				'settings' => array(
					'hide_from_subscribers'  => array(
						'el'          => 'input',
						'type'        => 'checkbox_alt',
						'label'       => __( 'Hide From Subscribers', 'newsletter-optin-box' ),
						'default'     => false,
						'description' => __( 'Hide opt-in forms and methods from existing subscribers.', 'newsletter-optin-box' ),
					),
					'always_show_to_admin'   => array(
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
					'subscribers_cookie'     => array(
						'el'          => 'input',
						'type'        => 'text',
						'label'       => __( 'Subscription Cookie', 'newsletter-optin-box' ),
						'placeholder' => '',
						'tooltip'     => __( 'If you are migrating from another email plugin, enter the cookie name they used to identify subscribers.', 'newsletter-optin-box' ),
					),
					'manage_preferences_url' => array(
						'el'          => 'input',
						'type'        => 'text',
						'label'       => __( 'Manage Preferences URL', 'newsletter-optin-box' ),
						'placeholder' => get_noptin_action_url( 'manage_preferences' ),
						'default'     => get_noptin_action_url( 'manage_preferences' ),
						'tooltip'     => __( 'Optional. Enter a custom URL to a page where subscribers can manage their subscriptions. Ensure the page has the [noptin_manage_subscription] shortcode.', 'newsletter-optin-box' ),
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
