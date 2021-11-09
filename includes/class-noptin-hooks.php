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

		// (Maybe) subscribe users from custom forms.
		add_action( 'init', array( $this, 'maybe_subscribe' ), 1000 );

		// (Maybe) delete sent campaigns.
		add_action( 'noptin_daily_maintenance', array( $this, 'maybe_delete_campaigns' ) );

	}

	/**
	 * Add our noptin page rewrite tag and rule.
	 *
	 * This is only here for backwards compatibility.
	 *
	 * @deprecated
	 * @since 1.2.9
	 */
	public function add_rewrite_rule() {

		if ( is_using_new_noptin_forms() ) {
			return;
		}

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
			setcookie( 'noptin_hide', 'true', time() + HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
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

	/**
	 * Subscribes users from custom integration.
	 *
	 */
	public function maybe_subscribe() {

		$submitted = wp_unslash( array_merge( (array) $_GET, (array) $_POST ) );
		$checked   = isset( $submitted['noptin-custom-subscribe'] ) ? $submitted['noptin-custom-subscribe'] : '';

		// Abort if no subscription was attempted.
		if ( ! in_array( $checked, array( 1, '1', 'yes', true, 'true', 'y' ), true ) || apply_filters( 'noptin_skip_custom_subscribe', false ) ) {
			return;
		}

		// Guess core subscriber fields.
		$data = self::guess_fields( $submitted );

		// Add connection data.
		$data = self::add_connections( $data, $submitted );

		// Save the subscriber.
		add_noptin_subscriber( map_deep( $data, 'esc_html' ) );
	}

	/**
	 * Adds connection details.
	 *
	 * @param array $data
	 * @param array $submitted
	 */
	public static function add_connections( $data, $submitted ) {

		if ( ! is_array( $submitted ) ) {
			return;
		}

		foreach ( get_noptin_connection_providers() as $key => $connection ) {

			if ( empty( $connection->list_providers ) ) {
				continue;
			}

			$key          = $connection->slug;
			$data[ $key ] = array();

			if ( isset( $submitted["{$key}_list"] ) ) {
				$data[ $key ]['lists'] = noptin_parse_list( $submitted["{$key}_list"], true );
			}

			if ( $connection->supports( 'tags' ) && isset( $submitted["{$key}_tags"] ) ) {
				$data[ $key ]['tags'] = noptin_parse_list( $submitted["{$key}_tags"], true );
			}

			// Secondary fields.
			foreach ( array_keys( $connection->list_providers->get_secondary() ) as $secondary ) {
				if ( isset( $submitted["{$key}_$secondary"] ) ) {
					$data[ $key ][ $secondary ] = noptin_parse_list( $submitted["{$key}_$secondary"], true );
				}
			}

		}

		return $data;
	}

	/**
	 * Guesses subscriber fields.
	 *
	 * @param array $fields
	 */
	public static function guess_fields( $fields ) {

		if ( ! is_array( $fields ) ) {
			return array();
		}

		$guessed   = array();
		$guessable = array(
			'firstname'        => 'first_name',
			'fname'            => 'first_name',
			'secondname'       => 'second_name',
			'lastname'         => 'second_name',
			'lname'            => 'second_name',
			'name'             => 'name',
			'fullname'         => 'name',
			'familyname'       => 'second_name',
			'displayname'      => 'name',
			'emailaddress'     => 'email',
			'email'            => 'email',
			'subscribedvia'    => '_subscriber_via',
			'source'           => '_subscriber_via',
		);

		foreach (  get_noptin_custom_fields() as $custom_field ) {
			$label_key = strtolower( preg_replace( "/[^A-Za-z0-9]/", '', $custom_field['label'] ) );
			$merge_key = strtolower( preg_replace( "/[^A-Za-z0-9]/", '', $custom_field['merge_tag'] ) );

			$guessable[ $merge_key ] = $custom_field['merge_tag'];
			$guessable[ $label_key ] = $custom_field['merge_tag'];
		}

		foreach( array_keys( $guessable ) as $key ) {
			$guessable["subscriber$key"] = $guessable[ $key ];
			$guessable["noptin$key"] = $guessable[ $key ];
		}

		// Prepare subscriber fields.
		foreach ( $fields as $key => $value ) {
			$sanitized = strtolower( preg_replace( "/[^A-Za-z0-9]/", '', $key ) );

			if ( isset( $guessable[ $sanitized ] ) && ! isset( $guessed[ $guessable[ $sanitized ] ] ) ) {
				$guessed[ $guessable[ $sanitized ] ] = $value;
			}

		}

		return $guessed;

	}

}
