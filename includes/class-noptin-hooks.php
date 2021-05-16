<?php

/**
 * This class defines various actions and hooks registered by Noptin.
 *
 * @since 1.2.9
 */
class Noptin_Hooks {

	/**
	 * Task constructor.
	 *
	 * @since 1.2.9
	 */
	public function __construct() {

		// Register our action page's endpoint.
		add_action( 'init', array( $this, 'add_rewrite_rule' ), 10, 0 );

		// Temporarily hide opt-in forms.
		add_action( 'init', array( $this, 'maybe_hide_optin_forms' ) );

		// (Maybe) schedule a cron that runs daily.
		add_action( 'init', array( $this, 'maybe_create_scheduled_event' ) );

		// (Maybe) delete sent campaigns.
		add_action( 'noptin_daily_maintenance', array( $this, 'maybe_delete_campaigns' ) );
	}

	/**
	 * Add our noptin page rewrite tag and rule.
	 *
	 * @since 1.2.9
	 */
	public function add_rewrite_rule() {

		add_rewrite_endpoint( 'noptin_newsletter', EP_ALL );

		if ( ! get_option( 'noptin_flushed_rules' ) ) {
			flush_rewrite_rules();
			add_option( 'noptin_flushed_rules', 1 );
		}

	}

	/**
	 * Hide opt-in forms from existing users.
	 *
	 * @since 1.3.2
	 */
	public function maybe_hide_optin_forms() {

		if ( ! empty( $_GET['noptin_hide'] ) ) {
			setcookie( 'noptin_hide', 'true', 0, COOKIEPATH, COOKIE_DOMAIN );
		}

	}

	/**
	 * Schedules a cron to run every day at 7 a.m
	 *
	 */
	public function maybe_create_scheduled_event() {

		if ( ! wp_next_scheduled( 'noptin_daily_maintenance' ) ) {
			$timestamp = strtotime( 'tomorrow 07:00:00', time() );
			wp_schedule_event( $timestamp, 'daily', 'noptin_daily_maintenance' );
		}

	}

	/**
	 * Deletes sent campaigns.
	 *
	 */
	public function maybe_delete_campaigns() {

		$save_days = (int) get_noptin_option( 'delete_campaigns', 0 );
		if ( empty( $save_days ) ) {
			return;
		}

		$args = array(
			'posts_per_page' => -1,
			'post_type'      => 'noptin-campaign',
			'fields'         => 'ids',
			'date_query'     => array(
				'before' => "-$save_days days", 
			),
			'meta_query'  => array(
				array(
					'key'     => 'completed',
					'value'   => '1',
				),
				array(
					'key'   => 'campaign_type',
					'value' => 'newsletter',
				)
			),
		);

		foreach( get_posts( $args ) as $post_id ) {
			wp_delete_post( $post_id, true );
		}

	}

}
