<?php
/** Operational data and extension points for the React campaign browser. */
namespace Hizzle\Noptin\Emails\Admin;

defined( 'ABSPATH' ) || exit;

class Browser {
	/**
	 * Register custom commands using label, callback($email, $request), and optional
	 * permission_callback($email, $request). Row commands may also use url instead
	 * of callback. Bulk callbacks run once per selected email with per-email permissions.
	 *
	 * @param \Hizzle\Noptin\Emails\Email|null $email Current email, or null for bulk labels.
	 * @param bool $bulk Whether to read the bulk command registry.
	 * @return array<string,array> Definitions keyed by sanitized action ID.
	 */
	public static function actions( $email = null, $bulk = false ) {
		return (array) apply_filters( $bulk ? 'noptin_campaign_bulk_actions' : 'noptin_campaign_row_actions', array(), $email );
	}

	/** Return safe public command descriptions; never send PHP callbacks to the browser. */
	public static function public_actions( $email = null, $bulk = false ) {
		$items = array();
		foreach ( self::actions( $email, $bulk ) as $id => $action ) {
			if ( ! is_array( $action ) || empty( $action['label'] ) || sanitize_key( $id ) !== (string) $id ) {
				continue;
			}
			$items[] = array(
				'id'          => $id,
				'label'       => $action['label'],
				'url'         => empty( $action['url'] ) ? '' : esc_url_raw( $action['url'] ),
				'destructive' => ! empty( $action['destructive'] ),
			);
		}
		return $items;
	}

	/** Live sending conditions, notices, and available extension bulk commands. */
	public static function state() {
		$notices = array();

		// Warn users when email delivery is disabled on a staging site.
		if ( \Hizzle\Noptin\Emails\Main::is_staging_email_disabled() ) {
			$notices[] = array(
				'id'        => 'staging',
				'status'    => 'warning',
				'message'   => 'Staging email delivery is disabled. Emails are recorded as sent, but no messages are delivered.',
				'url'       => current_user_can_manage_noptin() ? admin_url( 'admin.php?page=noptin-settings&hizzlewp_path=%2Femails%2Fmain' ) : '',
				'linkLabel' => 'Review email settings',
			);
		}

		// Check if sending has been paused due to limits.
		if ( noptin_email_sending_limit_reached() ) {
			$message = sprintf(
				/* translators: %1$s: number of emails allowed to be sent per period, %2$s: time period */
				__( 'Email sending is paused. You have sent %1$s emails in the past %2$s, reaching the sending limit.', 'newsletter-optin-box' ),
				noptin_max_emails_per_period(),
				human_time_diff( time() + noptin_get_email_sending_rolling_period() )
			);

			$next    = noptin_get_next_email_send_time();
			if ( $next ) {
				$message .= ' ' . sprintf(
					/* translators: %s: time until next email can be sent */
					__( 'Sending will resume in %s.', 'newsletter-optin-box' ),
					human_time_diff( $next )
				);
			}

			$notices[]             = array(
				'id'      => 'sending-limit',
				'status'  => 'error',
				'message' => $message,
			);
		}

		// Check if external CRON is not being used.
		$can_display_cron_notice = ! apply_filters( 'noptin_display_cron_notice', get_user_meta( get_current_user_id(), 'noptin_dismiss_cron_notice', true ) );
		if ( $can_display_cron_notice && ( ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON ) ) {
			$notices[] = array(
				'id'          => 'wp-cron',
				'status'      => 'warning',
				'message'     => 'WP-Cron is being used for email sending. Cached sites and sites with little traffic may experience delivery delays. Consider setting up an external cron job.',
				'dismissible' => true,
				'url'         => noptin_get_guide_url( 'External CRON', 'sending-emails/how-to-set-up-an-external-cron-job-in-wordpress-and-speed-up-email-sending/' ),
				'linkLabel'   => __( 'Learn more', 'newsletter-optin-box' ),
			);
		}

		// Other messages.
		if ( current_user_can( get_noptin_capability() ) ) {
			foreach ( \Hizzle\Noptin\Admin\Main::get_notices() as $status => $messages ) {
				foreach ( (array) $messages as $message ) {
					$notices[] = array(
						'id'          => 'saved-' . md5( $status . $message ),
						'status'      => $status,
						'html'        => wp_kses_post( $message ),
						'dismissible' => true,
					);
				}
			}
		}

		return array(
			'notices'       => array_values( apply_filters( 'noptin_campaign_browser_notices', $notices ) ),
			'premiumAddons' => array_values( apply_filters( 'noptin_com_helper_premium_addons', defined( 'NOPTIN_ADDONS_PACK_VERSION' ) ? array( 'Noptin Addons Pack' ) : array() ) ),
			'recovery'      => current_user_can_manage_noptin_campaign_type( 'newsletter' ) ? \Hizzle\Noptin\Emails\Bulk\Main::get_foreground_recovery_data() : false,
			'bulkActions'   => self::public_actions( null, true ),
		);
	}

	/** Persist dismissal through a REST nonce-protected request. */
	public static function dismiss( $request ) {
		$id = $request['notice'];
		if ( 'wp-cron' === $id ) {
			update_user_meta( get_current_user_id(), 'noptin_dismiss_cron_notice', true );
		} elseif ( current_user_can( get_noptin_capability() ) ) {
			$saved = \Hizzle\Noptin\Admin\Main::get_notices();
			foreach ( $saved as $status => $messages ) {
				$saved[ $status ] = array_values(
					array_filter(
						(array) $messages,
						static function ( $message ) use ( $id, $status ) {
							return 'saved-' . md5( $status . $message ) !== $id;
						}
					)
				);
			}

			update_option( 'noptin_notices', $saved );
		}

		return rest_ensure_response( self::state() );
	}
}
