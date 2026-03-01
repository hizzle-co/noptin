<?php

/**
 * Contains the main DB installer class.
 *
 * @since   1.0.0
 */

namespace Hizzle\Noptin\Onboarding;

defined( 'ABSPATH' ) || exit;

/**
 * The main DB installer class.
 */
class Installer {

	/**
	 * Loads the class.
	 *
	 */
	public static function init() {
		add_action( 'noptin_db_before_init', array( __CLASS__, 'maybe_upgrade_db' ) );
		add_action( 'init', array( __CLASS__, 'check_version' ), 1 );
		add_action( 'init', array( __CLASS__, 'create_missing_tables' ), 1 );
		add_action( 'wpmu_drop_tables', array( __CLASS__, 'wpmu_drop_tables' ) );
	}

	/**
	 * Check the plugin version and run the updater if required.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 */
	public static function check_version() {
		if ( self::needs_db_update() ) {
			self::install();
			do_action( 'noptin_updated' );
		}
	}

	/**
	 * Creates missing DB tables.
	 */
	public static function create_missing_tables() {
		if ( self::has_missing_tables() ) {
			self::verify_base_tables( true );
		}
	}

	/**
	 * Is a DB update needed?
	 *
	 * @since  1.0.0
	 * @return boolean
	 */
	public static function needs_db_update() {
		return get_option( 'noptin_db_schema', null ) !== self::get_schema_hash();
	}

	/**
	 * Retrieves the database schema hash.
	 *
	 * @return string
	 */
	public static function get_schema_hash() {
		return md5( implode( ';', self::get_schema() ) );
	}

	/**
	 * Install.
	 */
	public static function install() {
		if ( ! is_blog_installed() ) {
			return;
		}

		// Check if we are not already running this routine.
		if ( 'yes' === get_transient( 'noptin_installing' ) ) {
			return;
		}

		// Prevent other instances from running simultaneously.
		set_transient( 'noptin_installing', 'yes', MINUTE_IN_SECONDS * 10 );

		// Update DB tables.
		self::create_db_tables();

		// Verify DB tables.
		self::verify_base_tables();

		// If this is the first install, add default subscriber.
		if ( ! get_option( 'noptin_db_schema' ) ) {
			add_noptin_subscriber( self::get_initial_subscriber_args() );
		}

		// Update the schema hash.
		update_option( 'noptin_db_schema', self::get_schema_hash() );

		// Allow other instances to run.
		delete_transient( 'noptin_installing' );

		// Fired after install or upgrade.
		do_action( 'noptin_installed' );
	}

	/**
	 * Get Table schema.
	 *
	 * @return array
	 */
	private static function get_schema() {
		return noptin()->db()->store->get_schema();
	}

	/**
	 * Set up the database tables which the plugin needs to function.
	 */
	private static function create_db_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( self::get_schema() );
	}

	/**
	 * Check if all the base tables are present.
	 *
	 * @param bool $execute Whether to execute get_schema queries as well.
	 *
	 * @return array List of queries.
	 */
	public static function verify_base_tables( $execute = false ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		if ( $execute ) {
			self::create_db_tables();
		}

		$queries        = dbDelta( self::get_schema(), false );
		$missing_tables = array();
		foreach ( $queries as $table_name => $result ) {
			if ( "Created table $table_name" === $result ) {
				$missing_tables[] = $table_name;
			}
		}

		if ( 0 < count( $missing_tables ) ) {
			update_option( 'noptin_schema_missing_tables', $missing_tables );
		} else {
			delete_option( 'noptin_schema_missing_tables' );
		}

		return $missing_tables;
	}

	/**
	 * Checks if there are any missing tables.
	 *
	 * @return bool
	 */
	public static function has_missing_tables() {
		return (bool) get_option( 'noptin_schema_missing_tables', false );
	}

	/**
	 * Returns initial subscriber args
	 */
	protected static function get_initial_subscriber_args() {

		if ( get_current_user_id() > 0 ) {
			$user = get_user_by( 'id', get_current_user_id() );
			return array(
				'email'      => $user->user_email,
				'name'       => $user->display_name,
				'source'     => 'manual',
				'tags'       => array(),
				'ip_address' => noptin_get_user_ip(),
			);
		}

		$admin_email = sanitize_email( get_bloginfo( 'admin_email' ) );
		$admin       = get_user_by( 'email', $admin_email );
		$args        = array(
			'email'  => $admin_email,
			'source' => 'manual',
			'tags'   => array(),
		);

		if ( $admin ) {
			$args['name'] = $admin->display_name;
		}

		return $args;
	}

	/**
	 * Drop tables on blog deletion.
	 *
	 * @param array $tables
	 */
	public static function wpmu_drop_tables( $tables ) {

		foreach ( noptin()->db()->store->get_collections() as $collection ) {
			$tables[] = $collection->get_db_table_name();

			if ( $collection->create_meta_table() ) {
				$tables[] = $collection->get_meta_table_name();
			}
		}

		return $tables;
	}

	/**
	 * Upgrades the database if the installed version is lower than the current version.
	 */
	public static function maybe_upgrade_db() {

		$installed_version = absint( get_option( 'noptin_db_version', 0 ) );

		// Upgrade db if installed version of noptin is lower than current version
		if ( $installed_version < noptin()->db_version ) {
			self::upgrade( $installed_version, noptin()->db_version );

			do_action( 'noptin_upgrade_db', $installed_version, noptin()->db_version );
		}
	}

	/**
	 * Upgrades the database.
	 *
	 * @param int|string $from The name of a table to create or the database version to upgrade from.
	 * @param int        $current_version The current database version.
	 */
	private static function upgrade( $from, $current_version ) {
		global $wpdb;

		// Abort if this is MS and the blog is not installed.
		if ( ! is_blog_installed() ) {
			return;
		}

		// Update the version option to prevent multiple runs of this routine.
		update_option( 'noptin_db_version', $current_version );

		// Flush permalinks.
		flush_rewrite_rules();

		// If this is a fresh install.
		if ( ! $from ) {

			// Fire action for fresh install.
			do_action( 'noptin_full_install' );

			// Save installation date.
			update_option( 'noptin_install_date', time() );

			// Review nag.
			if ( ! get_option( 'noptin_review_nag' ) ) {
				update_option( 'noptin_review_nag', time() + WEEK_IN_SECONDS );
			}

			return;
		}

		// Nothing to migrate if the subscribers table does not exist.
		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}noptin_subscribers'" ) ) {
			return;
		}

		// Upgrading from version 1.
		if ( 1 === $from ) {
			return self::upgrade_from_1();
		} elseif ( $from < 5 ) {
			return self::upgrade_from_4();
		} elseif ( 6 === $from ) {
			return self::upgrade_from_6();
		}
	}

	/**
	 * Upgrades the db from version 1 to 2
	 */
	private static function upgrade_from_1() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$wpdb->query( "ALTER TABLE {$wpdb->prefix}noptin_subscribers ADD active tinyint(2)  NOT NULL DEFAULT '0'" );
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}noptin_subscribers ADD date_created  DATE" );

		// Had not been implemented.
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}noptin_subscribers DROP COLUMN source" );

		// Not really helpful.
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}noptin_subscribers DROP COLUMN time" );

		self::upgrade_from_4();
	}

	/**
	 * Upgrades the db from version 2 to 4
	 */
	private static function upgrade_from_4() {
		global $wpdb;

		// Rename second_name to last_name.
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}noptin_subscribers CHANGE second_name last_name VARCHAR(100) NOT NULL default ''" );

		// Rename rename active to status.
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}noptin_subscribers CHANGE active status VARCHAR(12) NOT NULL default 'subscribed'" );

		// Change status values. If value is 0, set to subscribed, if 1 set to pending.
		$wpdb->query( "UPDATE {$wpdb->prefix}noptin_subscribers SET status = 'subscribed' WHERE status = '0'" );
		$wpdb->query( "UPDATE {$wpdb->prefix}noptin_subscribers SET status = 'pending' WHERE status = '1'" );

		// Remove key email.
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}noptin_subscribers DROP KEY email" );

		// Create a _migrate_subscriber meta field for all subscribers.
		// This will be used to migrate subscribers in the background.
		$wpdb->query(
			"INSERT INTO {$wpdb->prefix}noptin_subscriber_meta (noptin_subscriber_id, meta_key, meta_value)
			SELECT id, '_migrate_subscriber', '1' FROM {$wpdb->prefix}noptin_subscribers"
		);

		// Create recurring CRON job to migrate subscribers.
		wp_schedule_single_event( time(), 'noptin_migrate_subscribers' );

		// Flush cache.
		wp_cache_flush();

		// Upgrade from version 6 to 7.
		self::upgrade_from_6();
	}

	/**
	 * Upgrades the db from version 6 to 7.
	 */
	private static function upgrade_from_6() {
		global $wpdb;

		// Loop through all subscribers.
		$subscribers = $wpdb->get_results( "SELECT id, email FROM {$wpdb->prefix}noptin_subscribers" );

		if ( is_array( $subscribers ) ) {
			foreach ( $subscribers as $subscriber ) {
				create_noptin_background_task(
					array(
						'hook'           => 'noptin_recalculate_subscriber_engagement_rate',
						'args'           => array( $subscriber->id ),
						'date_scheduled' => time() + MINUTE_IN_SECONDS,
						'lookup_key'     => $subscriber->email,
					)
				);
			}
		}
	}
}
