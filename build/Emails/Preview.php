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
		self::$simulation = false;
		self::$mode       = 'browser';
		self::$campaign   = new Email( $request['cid'] );
		self::$user       = $request;

		// Render the preview.
		self::render();
	}

	/**
	 * Admin preview.
	 */
	public static function admin_preview( $template ) {

		if ( is_singular( 'noptin-campaign' ) ) {
			$campaign_id = get_the_ID();
		} elseif ( 'noptin-campaign' === ( $_GET['post_type'] ?? '' ) && ! empty( $_GET['p'] ) ) {
			$campaign_id = absint( $_GET['preview_id'] ?? $_GET['p'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} else {
			return $template;
		}

		if ( empty( $campaign_id ) || 'noptin-campaign' !== get_post_type( $campaign_id ) ) {
			return $template;
		}

		// Ensure the current user can edit it.
		if ( ! current_user_can( 'edit_post', $campaign_id ) ) {
			wp_die( 'You do not have permission to view this campaign.' );
		}

		remove_filter( 'get_post_metadata', '_wp_preview_meta_filter' );

		// Prepare the preview.
		self::$simulation = ! isset( $_GET['noptin_simulate'] ) || '0' !== $_GET['noptin_simulate']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		self::$mode       = 'preview';
		self::$campaign   = new Email( $campaign_id );
		$user             = wp_get_current_user();
		self::$user       = array(
			'email' => $user->user_email,
			'cid'   => $campaign_id,
		);

		// Plain-text admin previews use an HTML shell for the preview mode controls.
		if ( 'plain_text' === self::$campaign->get_email_type() && ! headers_sent() ) {
			header( 'Content-Type: text/html; charset=UTF-8' );
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

		define( 'NOPTIN_EMAIL_PREVIEW_MODE', true );
		$GLOBALS['noptin_email_force_skip'] = null;

		$empty_before     = did_action( 'noptin_collection_list_empty' );
		$not_empty_before = did_action( 'noptin_collection_list_not_empty' );

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

		if ( 'preview' === self::$mode ) {
			$skip_reason = self::$simulation ? self::get_skip_reason( self::$campaign, $empty_before, $not_empty_before ) : '';
			if ( $skip_reason ) {
				$preview = '<!doctype html><html><head><meta charset="utf-8"><title>Email preview</title></head><body><main style="max-width:680px;margin:40px auto;padding:24px;font:16px/1.5 sans-serif"><h1>This email would not be sent now</h1><p>' . esc_html( $skip_reason ) . '</p><p>' . '<a href="' . esc_url( add_query_arg( 'noptin_simulate', 0 ) ) . '">View sample content using older items.</a></p></main></body></html>';
			} elseif ( 'plain_text' === self::$campaign->get_email_type() ) {
				$preview = '<!doctype html><html><head><meta charset="utf-8"><title>Email preview</title></head><body><pre style="margin:24px;white-space:pre-wrap">' . esc_html( $preview ) . '</pre></body></html>';
			}
			$preview = self::add_preview_controls( $preview );
		}

		echo $preview; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Explain whether the generated content would be skipped by the normal send flow.
	 *
	 * @param Email $campaign Campaign being previewed.
	 * @param int   $empty_before Empty collection count before rendering.
	 * @param int   $not_empty_before Nonempty collection count before rendering.
	 * @return string Empty when the campaign is allowed to send.
	 */
	private static function get_skip_reason( $campaign, $empty_before, $not_empty_before ) {
		if ( ! empty( $GLOBALS['noptin_email_force_skip'] ) ) {
			return $GLOBALS['noptin_email_force_skip']['message'];
		}
		$empty     = did_action( 'noptin_collection_list_empty' ) - $empty_before;
		$not_empty = did_action( 'noptin_collection_list_not_empty' ) - $not_empty_before;
		if ( $empty > 0 && 0 === $not_empty && apply_filters( 'noptin_email_skip_if_all_items_empty', (bool) $campaign->get( 'skip_if_all_items_empty' ), $campaign ) ) {
			return 'All dynamic blocks have no matching records, and this campaign is configured to skip sending in that case.';
		}
		return '';
	}

	/**
	 * Add read-only mode controls to the admin preview, never to recipient emails.
	 *
	 * @param string $preview Rendered email HTML.
	 * @return string
	 */
	private static function add_preview_controls( $preview ) {
		$message = self::$simulation
			? 'Uses the saved sending date filters as of now. Content may change before the scheduled send.'
			: 'Sample content ignores the last-send date. Other content filters still apply.';

		$links       = '';
		$simulations = array(
			1 => 'If sent now',
			0 => 'Sample content',
		);

		foreach ( $simulations as $simulate => $label ) {
			$active = (bool) $simulate === self::$simulation;
			$links .= sprintf(
				'<a href="%s"%s style="display:inline-block;padding:8px 12px;margin:0 8px 8px 0;border:1px solid %s;border-radius:3px;text-decoration:none;color:%s;background:%s">%s</a>',
				esc_url( add_query_arg( 'noptin_simulate', $simulate ) ),
				$active ? ' aria-current="page"' : '',
				$active ? '#FB1A62' : '#c3c4c7',
				$active ? '#fff' : '#1e1e1e',
				$active ? '#FB1A62' : '#fff',
				esc_html( $label )
			);
		}
		// Native disclosure keeps hiding/showing accessible without preview scripts.
		$notice  = '<style>.noptin-email-preview-controls[open]{width:min(440px,calc(100vw - 32px))}.noptin-email-preview-controls[open] .noptin-email-preview-show,.noptin-email-preview-controls:not([open]) .noptin-email-preview-hide{display:none}</style>';
		$notice .= '<details open class="noptin-email-preview-controls" aria-label="Email preview mode" style="position:fixed;z-index:2147483647;bottom:16px;right:16px;max-width:calc(100vw - 32px);max-height:calc(100vh - 32px);overflow:auto;box-sizing:border-box;margin:0;padding:16px;border:1px solid #c3c4c7;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.16);background:#fff;color:#1e1e1e;font:14px/1.4 sans-serif;text-align:left">';
		$notice .= '<summary style="cursor:pointer;font-weight:600"><span class="noptin-email-preview-hide">Hide preview controls</span><span class="noptin-email-preview-show">Show preview controls</span></summary>';
		$notice .= '<nav style="margin-top:16px" aria-label="Preview content">' . $links . '</nav><p style="margin:0">' . esc_html( $message ) . '</p></details>';

		if ( preg_match( '/<body\b[^>]*>/i', $preview, $matches, PREG_OFFSET_CAPTURE ) ) {
			$offset = $matches[0][1] + strlen( $matches[0][0] );
			return substr_replace( $preview, $notice, $offset, 0 );
		}

		return $notice . $preview;
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
