<?php

/**
 * Main preview class.
 *
 * @since   2.2.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Emails;

defined( 'ABSPATH' ) || exit;

/**
 * Main preview class.
 */
class Preview {

	/**
	 * The current preview mode.
	 */
	public static $mode = 'preview';

	/**
	 * Whether or not this is a simulation.
	 */
	public static $simulation = false;

	/**
	 * The current campaign.
	 *
	 * @var Email
	 */
	public static $campaign;

	/**
	 * The current user info.
	 *
	 * @var array
	 */
	public static $user;

	/**
	 * Inits the main preview class.
	 *
	 */
	public static function init() {

		// Preview email.
		add_action( 'noptin_page_view_in_browser', array( __CLASS__, 'view_in_browser' ), 10, 2 );
		add_filter( 'template_redirect', array( __CLASS__, 'admin_preview' ), -100 );
	}

	/**
	 * View email in browser.
	 */
	public static function view_in_browser( $value, $request ) {

		// Ensure an email campaign is specified.
		if ( empty( $request ) || empty( $request['cid'] ) ) {
			wp_die( 'Invalid or missing campaign id.' );
		}

		// If the campaign is not published, ensure the current user can edit it.
		if ( 'publish' !== get_post_status( $request['cid'] ) && ! current_user_can( 'edit_post', $request['cid'] ) ) {
			wp_die( 'You do not have permission to view this campaign.' );
		}

		// Prepare the preview.
		self::$mode     = 'browser';
		self::$campaign = new Email( $request['cid'] );
		self::$user     = $request;

		// Render the preview.
		self::render();
	}

	/**
	 * Admin preview.
	 */
	public static function admin_preview( $template ) {

		// Check if we are previewing the post type noptin-campaign.
		if ( ! is_singular( 'noptin-campaign' ) ) {
			return $template;
		}

		// Ensure the current user can edit it.
		if ( ! current_user_can( 'edit_post', get_the_ID() ) ) {
			wp_die( 'You do not have permission to view this campaign.' );
		}

		// Prepare the preview.
		self::$simulation = ! empty( $_GET['noptin_simulate'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		self::$mode       = 'preview';
		self::$campaign   = new Email( get_post() );
		$user             = wp_get_current_user();
		self::$user       = array(
			'email' => $user->user_email,
			'cid'   => get_the_ID(),
		);

		// Maybe set plain text mode.
		if ( 'plain_text' === self::$campaign->get_email_type() && ! headers_sent() ) {
			header( 'Content-Type: text/plain' );
		}

		// Render the preview.
		self::render();
	}

	/**
	 * Render preview.
	 */
	private static function render() {

		// Abort if the campaign does not exist.
		if ( empty( self::$campaign ) || ! self::$campaign->exists() ) {
			wp_die( 'Invalid campaign.' );
		}

		// Prepare test content if needed.
		$prepare_preview = self::$campaign->prepare_preview( self::$mode, self::$user );

		if ( is_wp_error( $prepare_preview ) ) {
			wp_die( esc_html( $prepare_preview->get_error_message() ) );
		}

		// Generate the preview.
		$preview = noptin_generate_email_content( self::$campaign, Main::$current_email_recipient, false );

		// Email templates.
		if ( 'normal' === self::$campaign->get_email_type() && 'default' === self::$campaign->get_template() ) {
			$preview = self::process_templates( $preview );
		}

		if ( is_wp_error( $preview ) ) {
			wp_die( esc_html( $preview->get_error_message() ) );
		}

		if ( self::$simulation && ! empty( $GLOBALS['noptin_email_force_skip'] ) ) {
			wp_die( esc_html( $GLOBALS['noptin_email_force_skip']['message'] ) );
		}

		echo $preview; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	private static function process_templates( $preview ) {

		// Email templates.
		if ( class_exists( '\Mailtpl' ) && is_callable( '\Mailtpl::instance' ) ) {
			$templates = \Mailtpl::instance();

			if ( ! empty( $templates->mailer ) && is_callable( array( $templates->mailer, 'send_email' ) ) ) {
				$result = $templates->mailer->send_email( array( 'message' => $preview ) );

				if ( is_array( $result ) && ! empty( $result['message'] ) ) {
					return $result['message'];
				}
			}
		}

		return $preview;
	}
}
