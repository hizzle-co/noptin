<?php
/**
 * Dashboard API.
 *
 * @since   1.0.0
 */

namespace Hizzle\Noptin\Dashboard;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Dashboard API Class.
 *
 * @since 1.0.0
 */
class Main {

	/**
	 * @var string hook suffix
	 */
	public static $hook_suffix;

	/**
	 * Class constructor.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'dashboard_menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Add dashboard menu item.
	 */
	public static function dashboard_menu() {
		self::$hook_suffix = add_submenu_page(
			'noptin',
			__( 'Noptin Dashboard', 'newsletter-optin-box' ),
			__( 'Dashboard', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin',
			array( __CLASS__, 'render_dashboard_page' )
		);
	}

	/**
	 * Displays the dashboard page.
	 */
	public static function render_dashboard_page() {
		if ( ! current_user_can( get_noptin_capability() ) ) {
			return;
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
		// Abort if not on the dashboard page.
		if ( self::$hook_suffix !== $hook ) {
			return;
		}

		// Enqueue the dashboard script.
		$config = include plugin_dir_path( __FILE__ ) . 'assets/js/dashboard.asset.php';
		wp_enqueue_script(
			'noptin-dashboard',
			plugin_dir_url( __FILE__ ) . 'assets/js/dashboard.js',
			$config['dependencies'],
			$config['version'],
			true
		);

		$plugins = array();

		if ( ! class_exists( '\Hizzle\Recaptcha\Main' ) ) {
			$plugins[] = array(
				'label' => 'Hizzle CAPTCHA',
				'info'  => __( 'Protects your subscription, contact, checkout, and registration forms from spammers.', 'newsletter-optin-box' ),
				'icon'  => 'lock',
				'url'   => network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=hizzle-recaptcha&TB_iframe=true&width=772&height=600' ),
			);
		}

		if ( ! function_exists( 'hizzle_downloads' ) ) {
			$plugins[] = array(
				'label' => 'Hizzle Downloads',
				'info'  => __( 'Add downloadable files to your site and restrict access by user role or newsletter subscription status.', 'newsletter-optin-box' ),
				'icon'  => 'download',
				'url'   => network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=hizzle-downloads&TB_iframe=true&width=772&height=600' ),
			);
		}

		$data = array(
			'brand'               => noptin()->white_label->get_details(),
			'forms'               => noptin_count_optin_forms(),
			'subscriber_statuses' => noptin_get_subscriber_statuses(),
			'plugins'             => $plugins,
			'links'               => array(
				array(
					'text' => __( 'Report a bug or request a feature', 'newsletter-optin-box' ),
					'href' => 'https://github.com/hizzle-co/noptin/issues/new/choose',
				),
				array(
					'text' => __( 'Prevent spam sign-ups.', 'newsletter-optin-box' ),
					'href' => noptin_get_guide_url( 'Dashboard', '/subscription-forms/preventing-spam-sign-ups/' ),
				),
				array(
					'text' => __( 'Set-up new post notifications.', 'newsletter-optin-box' ),
					'href' => noptin_get_guide_url( 'Dashboard', '/guide/sending-emails/new-post-notifications/' ),
				),
				array(
					'text' => __( 'Email sending limits.', 'newsletter-optin-box' ),
					'href' => noptin_get_guide_url( 'Dashboard', '/guide/sending-emails/email-sending-limits/' ),
				),
				array(
					'text' => __( 'How to fix emails not sending.', 'newsletter-optin-box' ),
					'href' => noptin_get_guide_url( 'Dashboard', '/guide/sending-emails/how-to-fix-emails-not-sending/' ),
				),
			),
		);

		// If we have a campaign, add it to the data.
		if ( isset( $_GET['noptin_campaign'] ) && current_user_can( 'edit_post', $_GET['noptin_campaign'] ) ) {
			$campaign         = noptin_get_email_campaign_object( (int) $_GET['noptin_campaign'] );
			$data['campaign'] = array(
				'id'          => $campaign->id,
				'edit_url'    => $campaign->get_edit_url(),
				'preview_url' => $campaign->get_preview_url(),
				'name'        => $campaign->name,
			);

			// Query the date range so that we can display the correct date range in the dashboard.
			$query = array(
				'campaign_id' => $campaign->id,
				'orderby'     => 'date_created',
				'fields'      => 'date_created',
				'per_page'    => 1,
			);

			if ( isset( $_GET['noptin_activity'] ) ) {
				$query['activity']            = sanitize_key( urldecode( $_GET['noptin_activity'] ) );
				$data['campaign']['activity'] = $query['activity'];
			}

			$date_last      = \Hizzle\Noptin\Emails\Logs\Main::query( $query );
			$query['order'] = 'ASC';
			$date_first     = \Hizzle\Noptin\Emails\Logs\Main::query( $query );

			if ( ! is_wp_error( $date_first ) && ! empty( $date_first ) ) {
				$date_first = gmdate( 'Y-m-d', strtotime( current( $date_first ) ) );
			}

			if ( empty( $date_first ) ) {
				$date_first = gmdate( 'Y-m-d' );
			}

			if ( ! is_wp_error( $date_last ) && ! empty( $date_last ) ) {
				$date_last = gmdate( 'Y-m-d', strtotime( current( $date_last ) ) );
			}

			if ( empty( $date_last ) ) {
				$date_last = gmdate( 'Y-m-d' );
			}

			// Calaulate the correct group by.
			// Calculate the correct group by based on date difference
			$first_timestamp     = strtotime( $date_first );
			$last_timestamp  = strtotime( $date_last );
			$diff_in_seconds = $last_timestamp - $first_timestamp;
			$diff_in_hours   = $diff_in_seconds / HOUR_IN_SECONDS;
			$diff_in_days    = $diff_in_seconds / DAY_IN_SECONDS;

			if ( $diff_in_hours < 48 ) {
				$group_by = 'hour';
			} elseif ( $diff_in_days < 14 ) {
				$group_by = 'day';
			} elseif ( $diff_in_days < 60 ) {
				$group_by = 'week';
			} elseif ( $diff_in_days < 730 ) { // 2 years (approx)
				$group_by = 'month';
			} else {
				$group_by = 'year';
			}

			if ( ! empty( $date_first ) && ! empty( $date_last ) ) {
				$data['campaign']['date_range'] = array(
					'start'   => $date_first,
					'end'     => $date_last,
					'groupBy' => $group_by,
				);
			}
		}

		// Localize the script.
		wp_add_inline_script(
			'noptin-dashboard',
			'window.noptinDashboard = ' . wp_json_encode( $data ) . ';',
			'before'
		);

		wp_set_script_translations( 'noptin-dashboard', 'newsletter-optin-box', noptin()->plugin_path . 'languages' );

		// Load the css.
		wp_enqueue_style(
			'noptin-dashboard',
			plugin_dir_url( __FILE__ ) . 'assets/css/style-dashboard.css',
			array( 'wp-components' ),
			$config['version']
		);
	}
}
