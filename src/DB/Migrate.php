<?php

namespace Hizzle\Noptin\DB;

/**
 * Contains the main DB migrator class.
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The main DB migrator class.
 */
class Migrate {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'noptin_migrate_subscribers', array( __CLASS__, 'migrate_subscribers' ) );
	}

	/**
	 * Migrates subscribers from the old table to the new one.
	 */
	public static function migrate_subscribers() {
		global $wpdb;

		// Abort if we're already migrating.
		if ( get_transient( 'noptin_migrating_subscribers' ) ) {
			return;
		}

		set_transient( 'noptin_migrating_subscribers', true, 20 );

		$subscriber_ids = $wpdb->get_col( "SELECT `noptin_subscriber_id` FROM `{$wpdb->prefix}noptin_subscriber_meta` WHERE `meta_key`='_migrate_subscriber'" );
		$start_time     = time();

		// Abort if no subscribers to migrate.
		if ( empty( $subscriber_ids ) ) {
			return;
		}

		// Run for 20 seconds or less.
		do {

			$subscriber_id = array_shift( $subscriber_ids );

			if ( null === $subscriber_id ) {
				break;
			}

			$subscriber = noptin_get_subscriber( $subscriber_id );

			if ( $subscriber->exists() ) {
				self::migrate_subscriber( $subscriber );
			}
		} while ( time() - $start_time < 20 );

		// If we have more subscribers, schedule another migration.
		if ( ! empty( $subscriber_ids ) ) {
			wp_schedule_single_event( time() + 60, 'noptin_migrate_subscribers' );
		}
	}

	/**
	 * Migrates a single subscriber.
	 *
	 * @param Subscriber $subscriber The subscriber to migrate.
	 */
	public static function migrate_subscriber( $subscriber ) {

		// Default meta.
		$meta = array(
			'_subscriber_via'      => 'source',
			'ip_address'           => 'ip_address',
			'conversion_page'      => 'conversion_page',
			'_subscriber_activity' => 'activity',
		);

		// Custom fields.
		foreach ( get_noptin_custom_fields() as $custom_field ) {
			$meta[ $custom_field['merge_tag'] ] = $custom_field['merge_tag'];
		}

		foreach ( $meta as $meta_key => $prop ) {
			$meta_value = get_noptin_subscriber_meta( $subscriber->get_id(), $meta_key, true );

			if ( '' !== $meta_value ) {
				$subscriber->set( $prop, $meta_value );
			}
		}

		// Add timezone offset from date created.
		$date_created = $subscriber->get_date_created();

		if ( ! empty( $date_created ) ) {
			$date_created = $date_created->getTimestamp() - ( get_option( 'gmt_offset' ) * 3600 );
			$subscriber->set_date_created( $date_created );
		} else {
			$subscriber->set_date_created( time() );
		}

		// Fires before saving a migrated subscriber.
		do_action( 'noptin_before_migrate_subscriber', $subscriber );

		// Save the subscriber.
		$subscriber->save();

		// Delete the meta.
		delete_noptin_subscriber_meta( $subscriber->get_id(), '_migrate_subscriber' );
	}
}
