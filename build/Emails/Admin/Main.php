<?php
/**
 * Emails API: Emails Admin.
 *
 * Contains the main admin class for Noptin emails
 *
 * @since   2.3.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Emails\Admin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main admin class for Noptin emails.
 *
 * @since 2.3.0
 * @internal
 * @ignore
 */
class Main {

	/**
	 * @var string hook suffix
	 */
	public static $hook_suffix;

	/**
	 * @var \Hizzle\Noptin\Emails\Email[] Edited campaigns.
	 */
	private static $edited_campaigns = array();

	/**
	 * Inits the main emails class.
	 *
	 */
	public static function init() {

		add_action( 'noptin_repair_stuck_campaign', array( __CLASS__, 'repair_stuck_campaign' ) );
		add_action( 'noptin_force_send_campaign', array( __CLASS__, 'force_send_campaign' ) );
		add_action( 'noptin_duplicate_email_campaign', array( __CLASS__, 'duplicate_email_campaign' ) );
		add_action( 'noptin_delete_email_campaign', array( __CLASS__, 'delete_email_campaign' ) );
		add_filter( 'pre_get_users', array( __CLASS__, 'filter_users_by_campaign' ) );
		add_action( 'admin_menu', array( __CLASS__, 'email_campaigns_menu' ), 35 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Repairs a stuck campaign.
	 *
	 * @since 1.13.0
	 */
	public static function repair_stuck_campaign() {

		// Only admins should be able to force send campaigns.
		if ( ! current_user_can( get_noptin_capability() ) || empty( $_GET['noptin_nonce'] ) ) {
			return;
		}

		// Verify nonces to prevent CSRF attacks.
		if ( ! wp_verify_nonce( $_GET['noptin_nonce'], 'noptin_repair_stuck_campaign' ) ) {
			return;
		}

		// TODO: Implement this.
	}

	/**
	 * Manually sends a campaign.
	 *
	 * @since 1.11.2
	 */
	public static function force_send_campaign() {

		// Only admins should be able to force send campaigns.
		if ( ! current_user_can( get_noptin_capability() ) || empty( $_GET['noptin_nonce'] ) ) {
			return;
		}

		// Verify nonces to prevent CSRF attacks.
		if ( ! wp_verify_nonce( $_GET['noptin_nonce'], 'noptin_force_send_campaign' ) ) {
			return;
		}

		// Retrieve campaign object.
		$campaign = new \Hizzle\Noptin\Emails\Email( intval( $_GET['noptin-campaign'] ) );

		// Abort if not mass email.
		if ( ! $campaign->exists() || ! $campaign->is_mass_mail() ) {
			return;
		}

		define( 'NOPTIN_RESENDING_CAMPAIGN', true );

		// Set status to publish to allow sending.
		$campaign->status = 'publish';
		$campaign->save();

		if ( 'publish' === $campaign->status ) {
			do_action( 'noptin_send_' . $campaign->type, $campaign->id );

			// Fire another hook for the automation type.
			if ( 'automation' === $campaign->type && isset( $campaign->options['automation_type'] ) ) {
				do_action( 'noptin_send_' . $campaign->options['automation_type'], $campaign->id );
			}

			// Check if the campaign exists.
			noptin()->admin->show_info( __( 'Your email has been added to the sending queue and will be sent soon.', 'newsletter-optin-box' ) );
		}

		// Redirect.
		wp_safe_redirect( remove_query_arg( array( 'noptin_admin_action', 'noptin_nonce', 'campaign', 'sub_section' ) ) );
		exit;
	}

	/**
	 * Duplicates an email campaign.
	 *
	 * @since 1.7.0
	 */
	public static function duplicate_email_campaign() {

		// Only admins should be able to duplicate campaigns.
		if ( ! current_user_can( get_noptin_capability() ) || empty( $_GET['noptin_nonce'] ) ) {
			return;
		}

		// Verify nonces to prevent CSRF attacks.
		if ( ! wp_verify_nonce( $_GET['noptin_nonce'], 'noptin_duplicate_campaign' ) ) {
			return;
		}

		// Retrieve campaign object.
		$campaign = new \Hizzle\Noptin\Emails\Email( intval( $_GET['campaign'] ) );

		// Check if the campaign exists.
		if ( $campaign->exists() ) {
			$duplicate = $campaign->duplicate();

			if ( $duplicate && ! is_wp_error( $duplicate ) ) {
				noptin()->admin->show_info( __( 'The campaign has been duplicated.', 'newsletter-optin-box' ) );
				wp_safe_redirect( $campaign->get_edit_url() );
				exit;
			}

			if ( is_wp_error( $duplicate ) ) {
				noptin()->admin->show_error( $duplicate->get_error_message() );
			} else {
				noptin()->admin->show_error( __( 'Unable to duplicate the campaign.', 'newsletter-optin-box' ) );
			}
		} else {
			noptin()->admin->show_error( __( 'Campaign not found.', 'newsletter-optin-box' ) );
		}

		// Redirect.
		wp_safe_redirect( remove_query_arg( array( 'noptin_admin_action', 'noptin_nonce', 'campaign' ) ) );
		exit;
	}

	/**
	 * Deletes an email campaign.
	 *
	 * @since 1.7.0
	 */
	public static function delete_email_campaign() {

		// Only admins should be able to delete campaigns.
		if ( ! current_user_can( get_noptin_capability() ) || empty( $_GET['noptin_nonce'] ) ) {
			return;
		}

		// Verify nonces to prevent CSRF attacks.
		if ( ! wp_verify_nonce( $_GET['noptin_nonce'], 'noptin_delete_campaign' ) ) {
			return;
		}

		// Retrieve campaign object.
		$campaign = new \Hizzle\Noptin\Emails\Email( intval( $_GET['campaign'] ) );

		if ( ! $campaign->exists() ) {
			return;
		}

		// Delete the campaign.
		$campaign->delete();

		// Show success info.
		noptin()->admin->show_info( __( 'The campaign has been deleted.', 'newsletter-optin-box' ) );

		// Redirect to success page.
		wp_safe_redirect( remove_query_arg( array( 'noptin_admin_action', 'noptin_nonce', 'campaign' ) ) );
		exit;
	}

	/**
	 * Filters the users query.
	 *
	 * @param \WP_User_Query $query
	 */
	public static function filter_users_by_campaign( $query ) {
		global $pagenow;

		if ( is_admin() && 'users.php' === $pagenow && isset( $_GET['noptin_meta_key'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$meta_query   = $query->get( 'meta_query' );
			$meta_query   = empty( $meta_query ) ? array() : $meta_query;
			$meta_query[] = array(
				'key'   => sanitize_text_field( $_GET['noptin_meta_key'] ),  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'value' => (int) $_GET['noptin_meta_value'],  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			);
			$query->set( 'meta_query', $meta_query );

		}
	} // Recipients, Email Attachments.

	/**
	 * Email campaigns menu.
	 */
	public static function email_campaigns_menu() {

		self::$hook_suffix = add_submenu_page(
			'noptin',
			esc_html__( 'Email Campaigns', 'newsletter-optin-box' ),
			esc_html__( 'Email Campaigns', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-email-campaigns',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Displays the admin page.
	 */
	public static function render_admin_page() {

		$query_args = self::get_query_args();

		// Abort if unknown email type.
		if ( empty( $query_args['noptin_email_type'] ) || ! in_array( $query_args['noptin_email_type'], array_keys( \Hizzle\Noptin\Emails\Main::get_email_types() ), true ) ) {
			printf(
				'<div class="wrap"><div class="notice notice-error"><p>%s</p></div></div>',
				esc_html__( 'Unknown email type.', 'newsletter-optin-box' )
			);
			return;
		}

		$edited_campaign = self::prepare_edited_campaign( $query_args );

		// Check if we are editing a campaign.
		if ( ! empty( $edited_campaign ) ) {

			if ( 'not-found' === $edited_campaign->admin_screen ) {
				include plugin_dir_path( __FILE__ ) . 'views/404.php';
				return;
			}

			include plugin_dir_path( __FILE__ ) . 'views/campaign.php';
		} else {

			// Include the campaigns view.
			include plugin_dir_path( __FILE__ ) . 'views/campaigns.php';
		}
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

		$query_args      = self::get_query_args();
		$edited_campaign = self::prepare_edited_campaign( $query_args );
		$script          = empty( $edited_campaign ) ? 'view-campaigns' : $edited_campaign->admin_screen;
		$type            = \Hizzle\Noptin\Emails\Main::get_email_type( $query_args['noptin_email_type'] );
		$base_path       = plugin_dir_path( __DIR__ );

		// Load the js.
		if ( file_exists( plugin_dir_path( __DIR__ ) . 'assets/js/' . $script . '.js' ) ) {
			$config = include plugin_dir_path( __DIR__ ) . 'assets/js/' . $script . '.asset.php';

			wp_enqueue_script(
				'noptin-' . $script,
				plugins_url( 'assets/js/' . $script . '.js', __DIR__ ),
				$config['dependencies'],
				$config['version'],
				true
			);

			// Prepare the block editor.
			if ( 'email-editor' === $script ) {
				Editor::load( $edited_campaign );
			}

			// Localize the script.
			wp_localize_script(
				'noptin-' . $script,
				'noptinEmailSettingsMisc',
				apply_filters(
					'noptin_email_settings_misc',
					array(
						'data'    => (object) ( empty( $type ) ? array() : $type->to_array() ),
						'senders' => get_noptin_email_senders( true ),
					)
				)
			);
		}

		// Load the css.
		wp_enqueue_style( 'wp-components' );

		if ( file_exists( plugin_dir_path( __DIR__ ) . 'assets/css/style-' . $script . '.css' ) ) {
			$version = empty( $config ) ? filemtime( plugin_dir_path( __DIR__ ) . 'assets/css/style-' . $script . '.css' ) : $config['version'];
			wp_enqueue_style(
				'noptin-' . $script,
				plugins_url( 'assets/css/style-' . $script . '.css', __DIR__ ),
				'email-editor' === $script ? array( 'wp-block-editor', 'wp-edit-post', 'wp-format-library' ) : array(),
				$version
			);
		}
	}

	/**
	 * Retrieves the current query args.
	 *
	 * @return array
	 */
	public static function get_query_args() {

		$query_args = urldecode_deep( wp_unslash( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Abort if unknown email type.
		if ( empty( $query_args['noptin_email_type'] ) ) {
			$query_args['noptin_email_type'] = \Hizzle\Noptin\Emails\Main::get_default_email_type();
		}

		return $query_args;
	}

	/**
	 * Checks the screen to load.
	 *
	 * @param array $query_args The current query args.
	 * @return \Hizzle\Noptin\Emails\Email|null
	 */
	public static function prepare_edited_campaign( $query_args ) {

		// Abort if no campaign is being edited.
		if ( ! isset( $query_args['noptin_campaign'] ) ) {
			return null;
		}

		// Check if we already have the campaign.
		$cache_key = md5( wp_json_encode( $query_args ) );

		if ( isset( self::$edited_campaigns[ $cache_key ] ) ) {
			return self::$edited_campaigns[ $cache_key ];
		}

		// Retrieve campaign object.
		self::$edited_campaigns[ $cache_key ] = new \Hizzle\Noptin\Emails\Email( intval( $query_args['noptin_campaign'] ) );

		$campaign = &self::$edited_campaigns[ $cache_key ];

		if ( $campaign->exists() ) {
			return $campaign;
		}

		if ( ! empty( $query_args['noptin_campaign'] ) ) {
			$campaign->admin_screen = 'not-found';
			return $campaign;
		}

		// If this is a new campaign...
		if ( ! $campaign->exists() ) {

			// Set the type.
			$campaign->type = sanitize_text_field( $query_args['noptin_email_type'] );

			// Set the sub type.
			if ( ! empty( $query_args['noptin_email_sub_type'] ) ) {
				$campaign->options[ $campaign->type . '_type' ] = sanitize_text_field( $query_args['noptin_email_sub_type'] );
			}

			// Set the sender.
			if ( ! empty( $query_args['noptin_email_sender'] ) ) {
				$campaign->options['email_sender'] = sanitize_text_field( $query_args['noptin_email_sender'] );
			}

			// Set the author.
			$campaign->author = get_current_user_id();

			// Check if we have manual recipients.
			if ( ! empty( $query_args['noptin_recipients'] ) ) {
				$campaign->options['manual_recipients_ids'] = noptin_parse_int_list( $query_args['noptin_recipients'] );
			}
		}

		return $campaign;
	}
}
