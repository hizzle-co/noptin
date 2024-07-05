<?php
/**
 * Extensions API: Extensions Admin.
 *
 * Contains the main admin class for Noptin Extensions
 *
 * @since   3.2.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Extensions;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main admin class for Noptin Extensions.
 *
 * @since 3.2.0
 * @internal
 * @ignore
 */
class Menu {

	/**
	 * @var string hook suffix
	 */
	public static $hook_suffix;

	/**
	 * Inits the main emails class.
	 *
	 */
	public static function init() {

		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 70 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Settings menu.
	 */
	public static function register_menu() {

		if ( ! apply_filters( 'noptin_show_addons_page', true ) ) {
			return;
		}

		$count_html = \Noptin_COM_Updater::get_updates_count_html();

		/* translators: %s: extensions count */
		$menu_title = sprintf( __( 'Extensions %s', 'newsletter-optin-box' ), $count_html );

		self::$hook_suffix = add_submenu_page(
			'noptin',
			esc_html__( 'Extensions', 'newsletter-optin-box' ),
			$menu_title,
			get_noptin_capability(),
			'noptin-addons',
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

		$license = \Noptin_COM::get_active_license_key( true );
		$path    = noptin()->plugin_path . 'build/Misc/assets/';
		$url     = noptin()->plugin_url . 'build/Misc/assets/';
		$config  = include $path . 'js/list.asset.php';

		wp_enqueue_media();

		wp_enqueue_script(
			'noptin-list',
			$url . 'js/list.js',
			$config['dependencies'],
			$config['version'],
			true
		);

		// Localize the script.
		$account_url = ( $license && ! is_wp_error( $license ) && ! empty( $license->account_url ) ) ? $license->account_url : 'my-account';
		wp_localize_script(
			'noptin-list',
			'noptinList',
			array(
				'data' => array(
					'isExtensions' => true,
					'cardGroups'   => self::group_extensions( $license ),
					'actions'      => array(
						array(
							'href'      => noptin_get_upsell_url( $account_url, 'view-account', 'extensionsscreen' ),
							'variant'   => 'primary',
							'text'      => esc_html__( 'Manage your account', 'newsletter-optin-box' ),
							'className' => 'noptin-components-button__pink',
						),
					),
					'brand'        => array_merge(
						noptin()->white_label->get_details(),
						array(
							'name'    => 'Noptin Extensions',
							'version' => noptin()->version,
						)
					),
				),
			)
		);

		wp_set_script_translations( 'noptin-list', 'newsletter-optin-box', noptin()->plugin_path . 'languages' );

		// Load the css.
		wp_enqueue_style(
			'noptin-list',
			$url . 'css/style-list.css',
			array( 'wp-components' ),
			$config['version']
		);

		\Hizzle\Noptin\Misc\Main::load_interface_styles();
	}

	/**
	 * Groups integrations.
	 *
	 * @return array
	 */
	public static function group_extensions( $license ) {
		$installed_addons = wp_list_pluck( \Noptin_COM::get_installed_addons(), '_filename', 'slug' );
		$groups           = array(
			__( 'License Key', 'newsletter-optin-box' )  => array(
				'description' => esc_html__( 'An active license key gives you access to priority support, fixes, and all premium features and extensions. Use the tabs on the left to browse and install premium extensions.', 'newsletter-optin-box' ),
				'license'     => array(
					'licenseKey' => \Noptin_COM::get_active_license_key(),
					'info'       => $license,
					'error'      => is_wp_error( $license ) ? $license->get_error_message() : '',
					'nonce'      => wp_create_nonce( 'noptin_save_license_key' ),
					'purchase'   => noptin_get_upsell_url( 'pricing', 'license', 'extensionsscreen' ),
					'deactivate' => str_replace( '&amp;', '&', wp_nonce_url( admin_url( 'admin.php?page=noptin-addons' ), 'noptin-deactivate-license', 'noptin-deactivate-license-nonce' ) ),
				),
			),
			__( 'Addons Pack', 'newsletter-optin-box' )  => array(
				'description'        => esc_html__( 'The addons pack gives you access to the following features.', 'newsletter-optin-box' ),
				'email-course'       => array(
					'name'        => 'email-course',
					'label'       => __( 'Sequences / Courses', 'newsletter-optin-box' ),
					'description' => __( 'Set-up a series of free or paid emails to be sent at specific intervals one after another. Usefull for courses, welcome series, etc.', 'newsletter-optin-box' ),
					'image_url'   => array(
						'icon' => 'email',
						'fill' => '#e91e63',
					),
					'button2'     => self::main_action_button( $license, 'noptin-addons-pack', $installed_addons ),
					'button1'     => array(
						'href'    => noptin_get_upsell_url(
							'https://noptin.com/blog/create-an-email-course-wordpress/',
							'email-courses',
							'extensionsscreen'
						),
						'text'    => esc_html__( 'Learn More', 'newsletter-optin-box' ),
						'variant' => 'secondary',
					),
				),
				'coupon-codes'       => array(
					'name'        => 'coupon-codes',
					'label'       => __( 'Coupon Codes', 'newsletter-optin-box' ),
					'description' => __( 'Make more money by automatically sending new subscribers, users, or customers unique coupon codes.', 'newsletter-optin-box' ),
					'image_url'   => array(
						'icon' => 'money',
						'fill' => '#e91e63',
					),
					'button2'     => self::main_action_button( $license, 'noptin-addons-pack', $installed_addons ),
					'button1'     => array(
						'href'    => noptin_get_upsell_url(
							'https://noptin.com/blog/how-to-send-a-unique-woocommerce-coupon-code-to-new-email-subscribers/',
							'coupon-codes',
							'extensionsscreen'
						),
						'text'    => esc_html__( 'Learn More', 'newsletter-optin-box' ),
						'variant' => 'secondary',
					),
				),
				'tag-subscribers'    => array(
					'name'        => 'tag-subscribers',
					'label'       => __( 'Tag Subscribers', 'newsletter-optin-box' ),
					'description' => __( 'Tag subscribers based on their actions and send emails to subscribers with specific tags, or automatically send emails to subscribers whenever they are tagged or untagged.', 'newsletter-optin-box' ),
					'image_url'   => array(
						'icon' => 'tag',
						'fill' => '#e91e63',
					),
					'button2'     => self::main_action_button( $license, 'noptin-addons-pack', $installed_addons ),
					'button1'     => array(
						'href'    => noptin_get_upsell_url(
							'https://noptin.com/guide/email-subscribers/tagging-subscribers/',
							'tag-subscribers',
							'extensionsscreen'
						),
						'text'    => esc_html__( 'Learn More', 'newsletter-optin-box' ),
						'variant' => 'secondary',
					),
				),
				'lists'              => array(
					'name'        => 'lists',
					'label'       => __( 'Lists', 'newsletter-optin-box' ),
					'description' => __( 'Create multiple lists and send emails to subscribers in specific lists, or automatically send emails to subscribers whenever they are added to or removed from specific lists.', 'newsletter-optin-box' ),
					'image_url'   => array(
						'icon' => 'category',
						'fill' => '#e91e63',
					),
					'button2'     => self::main_action_button( $license, 'noptin-addons-pack', $installed_addons ),
					'button1'     => array(
						'href'    => noptin_get_upsell_url(
							'https://noptin.com/guide/email-subscribers/subscriber-lists/',
							'lists',
							'extensionsscreen'
						),
						'text'    => esc_html__( 'Learn More', 'newsletter-optin-box' ),
						'variant' => 'secondary',
					),
				),
				'manage-preferences' => array(
					'name'        => 'manage-preferences',
					'label'       => __( 'Manage Preferences', 'newsletter-optin-box' ),
					'description' => __( 'Allow subscribers to manage their preferences and unsubscribe from specific lists.', 'newsletter-optin-box' ),
					'image_url'   => array(
						'icon' => 'admin-settings',
						'fill' => '#e91e63',
					),
					'button2'     => self::main_action_button( $license, 'noptin-addons-pack', $installed_addons ),
					'button1'     => array(
						'href'    => noptin_get_upsell_url(
							'https://noptin.com/guide/email-subscribers/manage-preferences/',
							'manage-preferences',
							'extensionsscreen'
						),
						'text'    => esc_html__( 'Learn More', 'newsletter-optin-box' ),
						'variant' => 'secondary',
					),
				),
				'sync'               => array(
					'name'        => 'sync',
					'label'       => __( 'Sync Subscribers', 'newsletter-optin-box' ),
					'description' => __( 'Sync subscribers between sites, and add newsletter subscription forms on external sites.', 'newsletter-optin-box' ),
					'image_url'   => array(
						'icon' => 'marker',
						'fill' => '#e91e63',
					),
					'button2'     => self::main_action_button( $license, 'noptin-addons-pack', $installed_addons ),
					'button1'     => array(
						'href'    => noptin_get_upsell_url(
							'https://noptin.com/guide/email-subscribers/sync-subscribers/',
							'sync',
							'extensionsscreen'
						),
						'text'    => esc_html__( 'Learn More', 'newsletter-optin-box' ),
						'variant' => 'secondary',
					),
				),
			),
			__( 'Integrations', 'newsletter-optin-box' ) => array(
				'description' => esc_html__( 'These extensions allow you to connect Noptin to your favorite plugins.', 'newsletter-optin-box' ),
			),
			__( 'Connections', 'newsletter-optin-box' )  => array(
				'description' => esc_html__( 'These extensions allow you to connect Noptin to your favorite CRM or email software.', 'newsletter-optin-box' ),
			),
		);

		if ( ! empty( \Noptin_COM_Helper::$activation_error ) ) {
			$groups['License Key']['license']['licenseKey'] = \Noptin_COM_Helper::$temporary_key;
			$groups['License Key']['license']['error']      = \Noptin_COM_Helper::$activation_error;
		}

		foreach ( noptin()->integrations_new->get_all_known_integrations() as $config ) {
			$groups['Integrations'][ $config['slug'] ] = array(
				'name'        => $config['slug'],
				'label'       => $config['label'],
				'description' => $config['description'] ?? '',
				'image_url'   => $config['icon_url'] ?? '',
				'button2'     => self::main_action_button( $license, "noptin-{$config['slug']}", $installed_addons ),
				'button1'     => ( isset( $config['url'] ) && false === strpos( $config['url'], 'marketing-automation' ) ) ? array(
					'href'    => noptin_get_upsell_url( $config['url'], $config['slug'], 'extensionsscreen' ),
					'text'    => esc_html__( 'Learn More', 'newsletter-optin-box' ),
					'variant' => 'secondary',
				) : false,
			);

			if ( ! isset( $config['plan'] ) || 'free' === $config['plan'] ) {
				$groups['Integrations'][ $config['slug'] ]['button2'] = array(
					'text'      => esc_html__( 'Active', 'newsletter-optin-box' ),
					'variant'   => 'primary',
					'className' => 'noptin-components-button__green',
					'disabled'  => true,
				);
			}
		}

		foreach ( \Noptin_COM::get_connections() as $connection ) {
			$groups['Connections'][ $connection->slug ] = array(
				'name'        => $connection->slug,
				'label'       => $connection->name,
				'description' => sprintf( /* translators: %s Integration such as Mailchimp */ esc_html__( 'Connect Noptin to %s', 'newsletter-optin-box' ), esc_html( $connection->name ) ),
				'image_url'   => $connection->image_url,
				'button2'     => self::main_action_button( $license, "noptin-{$connection->slug}", $installed_addons, true ),
				'button1'     => array(
					'href'    => noptin_get_upsell_url( $connection->connect_url, $connection->slug, 'extensionsscreen' ),
					'text'    => esc_html__( 'Learn More', 'newsletter-optin-box' ),
					'variant' => 'secondary',
				),
			);
		}

		return $groups;
	}

	/**
	 * Returns the main action button.
	 *
	 * @param object|\WP_Error|false $license The active license
	 * @param string $slug The extension slug.
	 * @param array $installed_addons The installed addons.
	 * @param bool  $is_connection Whether this is a connection.
	 */
	public static function main_action_button( $license, $slug, $installed_addons, $is_connection = false ) {
		$has_license = $license && ! is_wp_error( $license ) && $license->is_active && ! $license->has_expired;

		// No license? Show the pricing button.
		if ( ! $has_license ) {
			return array(
				'href'    => noptin_get_upsell_url( 'pricing', str_replace( 'noptin-', '', $slug ), 'extensionsscreen' ),
				'text'    => esc_html__( 'View Pricing', 'newsletter-optin-box' ),
				'variant' => 'primary',
			);
		}

		// If this is a connection, show the upgrade button.
		if ( ! $is_connection && false !== strpos( $license->product_sku, 'connect' ) ) {
			return array(
				'href'    => noptin_get_upsell_url( 'pricing', str_replace( 'noptin-', '', $slug ), 'extensionsscreen' ),
				'text'    => esc_html__( 'Upgrade', 'newsletter-optin-box' ),
				'variant' => 'primary',
			);
		}

		// If installed...
		if ( isset( $installed_addons[ $slug ] ) ) {
			$installed_plugin = $installed_addons[ $slug ];

			if ( \Noptin_COM_Updater::has_extension_update( $slug ) ) {
				return array(
					'href'      => str_replace( '&amp;', '&', wp_nonce_url( admin_url( 'update.php?action=upgrade-plugin&plugin=' . $installed_plugin ), 'upgrade-plugin_' . $installed_plugin ) ),
					'text'      => esc_html__( 'Install Update', 'newsletter-optin-box' ),
					'variant'   => 'primary',
					'className' => 'noptin-components-button__update',
				);
			}

			// Check if active.
			if ( is_plugin_active( $installed_plugin ) ) {

				// If connection, link to settings.
				if ( $is_connection ) {
					return array(
						'href'      => admin_url( 'admin.php?page=noptin-settings&tab=integrations#noptin-settings-section-settings_section_' . $slug ),
						'text'      => esc_html__( 'Settings', 'newsletter-optin-box' ),
						'variant'   => 'primary',
						'className' => 'noptin-components-button__green',
					);
				}

				// Else, show active text.
				return array(
					'text'      => esc_html__( 'Active', 'newsletter-optin-box' ),
					'variant'   => 'primary',
					'className' => 'noptin-components-button__green',
					'disabled'  => true,
				);
			}

			// Activate.
			return array(
				'href'      => str_replace( '&amp;', '&', wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $installed_plugin ), 'activate-plugin_' . $installed_plugin ) ),
				'text'      => esc_html__( 'Activate', 'newsletter-optin-box' ),
				'variant'   => 'primary',
				'className' => 'noptin-components-button__green',
			);
		}

		// Install.
		return array(
			'href'    => str_replace( '&amp;', '&', wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=noptin-plugin-with-slug-' . $slug ), 'install-plugin_noptin-plugin-with-slug-' . $slug ) ),
			'text'    => esc_html__( 'Install Now', 'newsletter-optin-box' ),
			'variant' => 'primary',
		);
	}
}
