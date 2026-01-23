<?php

namespace Hizzle\Noptin\Emails\Logs;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles email related activity logs.
 *
 * @since 3.0.0
 */
class Main {

	/**
	 * @var array Activity checks cache.
	 */
	protected static $activity_checks_cache = array();

	/**
	 * Initializes the email logs.
	 *
	 * @since 3.0.0
	 */
	public static function init() {
		add_filter( 'noptin_db_schema', array( __CLASS__, 'add_table' ) );
		add_action( 'noptin_log_activity', array( __CLASS__, 'create' ), 10, 5 );
	}

	/**
	 * Retrieves an email log by ID.
	 *
	 * @param int $id The email log ID.
	 * @return Log
	 */
	public static function get( $id ) {
		return noptin()->db()->get( $id, 'email_logs' );
	}

	/**
	 * Queries records from the database.
	 *
	 * @param array $args Query arguments.
	 * @param string $to_return 'results' returns the found records, 'count' returns the total count, 'aggregate' runs an aggregate query, while 'query' returns query object.
	 *
	 * @return int|Log[]|\Hizzle\Store\Query|\WP_Error
	 */
	public static function query( $args = array(), $to_return = 'results' ) {
		return noptin()->db()->query( 'email_logs', $args, $to_return );
	}

	/**
	 * Creates a new email log.
	 *
	 * @param string $activity The activity type.
	 * @param int $campaign_id The campaign ID.
	 * @param string $email The email address.
	 * @param string $extra Additional information.
	 * @param array $meta Additional metadata.
	 * @return int|\WP_Error
	 */
	public static function create( $activity, $campaign_id, $email, $extra = null, $meta = array() ) {

		if ( ! is_string( $email ) || ! is_email( $email ) ) {
			return;
		}

		$log = self::get( 0 );
		$log->set( 'activity', $activity );
		$log->set( 'email', $email );
		$log->set( 'campaign_id', (int) $campaign_id );
		$log->set_metadata( $meta );

		if ( $extra ) {
			$log->set( 'activity_info', $extra );
		}

		if ( ! empty( $campaign_id ) ) {

			// Maybe log for the parent campaign.
			$parent = get_post_parent( $campaign_id );

			if ( $parent ) {
				$log->set( 'parent_id', $parent->ID );
			}
		}

		// Clear cache.
		self::$activity_checks_cache = array();
		return $log->save();
	}

	/**
	 * Checks if the given email address did an action on a given email campaign.
	 *
	 * @param string $activity The activity type. E.g, open, click, send, bounce, unsubscribe, complain.
	 * @param int $campaign_id
	 * @param string $email_address
	 * @param int|string $since
	 */
	public static function did_activity( $activity, $campaign_id = 'any', $email_address = 'any', $since = 'any' ) {

		if ( 'any' === $since ) {
			$since = null;
		}

		// Check cache.
		$cache_key = $activity . '-' . ( $campaign_id ? $campaign_id : 'any' ) . '-' . ( $email_address ? $email_address : 'any' ) . '-' . ( $since ? $since : 'any' );
		if ( isset( self::$activity_checks_cache[ $cache_key ] ) ) {
			return self::$activity_checks_cache[ $cache_key ];
		}

		$was_send = self::query(
			array_filter(
				array(
					'email'              => $email_address,
					'campaign_id'        => $campaign_id,
					'activity'           => $activity,
					'number'             => 1,
					'date_created_after' => $since,
				)
			),
			'count'
		);

		self::$activity_checks_cache[ $cache_key ] = ! empty( $was_send );
		return self::$activity_checks_cache[ $cache_key ];
	}

	/**
	 * Adds the email activity logs table to the schema.
	 *
	 * @param array $schema The database schema.
	 * @return array
	 */
	public static function add_table( $schema ) {

		return array_merge(
			$schema,
			array(

				// Email activity.
				'email_logs' => array(
					'object'        => __NAMESPACE__ . '\Log',
					'singular_name' => 'email_log',
					'props'         => array(

						'id'             => array(
							'type'        => 'BIGINT',
							'length'      => 20,
							'nullable'    => false,
							'extra'       => 'AUTO_INCREMENT',
							'description' => __( 'Unique identifier for this resource.', 'newsletter-optin-box' ),
						),

						'email'          => array(
							'type'        => 'VARCHAR',
							'length'      => 255,
							'nullable'    => false,
							'description' => 'The email address associated with this log.',
						),

						'campaign_id'    => array(
							'type'        => 'BIGINT',
							'length'      => 20,
							'description' => 'The campaign ID.',
						),

						'parent_id'      => array(
							'type'        => 'BIGINT',
							'length'      => 20,
							'nullable'    => true,
							'description' => 'The parent campaign ID.',
						),

						'activity'       => array(
							'type'        => 'VARCHAR',
							'length'      => 30,
							'nullable'    => false,
							'description' => 'The type of activity',
						),

						'activity_info'  => array(
							'type'        => 'TEXT',
							'description' => 'Additional details',
						),

						'date_created'   => array(
							'type'        => 'DATETIME',
							'nullable'    => false,
							'readonly'    => true,
							'description' => 'The date this log was created.',
						),

						'campaign_title' => array(
							'type'        => 'TEXT',
							'description' => 'The campaign title',
							'is_dynamic'  => true,
							'readonly'    => true,
						),

						'campaign_url'   => array(
							'type'        => 'TEXT',
							'description' => 'The campaign URL',
							'is_dynamic'  => true,
							'readonly'    => true,
						),

						'metadata'       => array(
							'type'        => 'TEXT',
							'description' => 'A key value array of additional metadata about this log',
						),
					),

					'keys'          => array(
						'primary'     => array( 'id' ),
						'activity'    => array( 'activity' ),
						'campaign_id' => array( 'campaign_id' ),
						'email'       => array( 'email' ),
					),
				),
			)
		);
	}
}
