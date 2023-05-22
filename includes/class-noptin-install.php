<?php
/**
 * Upgrades the db
 */

defined( 'ABSPATH' ) || exit;

/**
 * Noptin_Install Class.
 */
class Noptin_Install {

	/**
	 * The current db charset.
	 *
	 * @var string
	 */
	public $charset_collate;

	/**
	 * Install Noptin
	 *
	 * @param int|string $upgrade_from The name of a table to create or the database version to upgrade from.
	 */
	public function __construct( $upgrade_from ) {
		global $wpdb;

		// Abort if this is MS and the blog is not installed.
		if ( ! is_blog_installed() ) {
			return;
		}

		$this->charset_collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$this->charset_collate = $wpdb->get_charset_collate();
		}

		// We're creating a table.
		if ( is_string( $upgrade_from ) ) {

			if ( method_exists( $this, "create_{$upgrade_from}_table" ) ) {
				call_user_func( array( $this, "create_{$upgrade_from}_table" ) );
			}

			return;
		}

		// If this is a fresh install.
		if ( ! $upgrade_from ) {
			return $this->do_full_install();
		}

		// Upgrading from version 1.
		if ( 1 === $upgrade_from ) {
			return $this->upgrade_from_1();
		} else {
			return $this->upgrade_from_4();
		}
	}

	/**
	 * Force create the subscribers meta table
	 */
	public function create_subscribers_meta_table() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( array( $this->get_subscriber_meta_table_schema() ) );
	}

	/**
	 * Returns the subscriber meta table schema
	 */
	private function get_subscriber_meta_table_schema() {
		global $wpdb;

		$table           = $wpdb->prefix . 'noptin_subscriber_meta';
		$charset_collate = $this->charset_collate;

		return "CREATE TABLE $table (
			meta_id bigint(20) unsigned NOT NULL auto_increment,
			noptin_subscriber_id bigint(20) unsigned NOT NULL default '0',
			meta_key varchar(255) default NULL,
			meta_value longtext,
			PRIMARY KEY  (meta_id),
			KEY noptin_subscriber_id (noptin_subscriber_id),
			KEY meta_key (meta_key(191))
		) $charset_collate;";

	}

	/**
	 * Upgrades the db from version 1 to 2
	 */
	private function upgrade_from_1() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$wpdb->query( "ALTER TABLE {$wpdb->prefix}noptin_subscribers ADD active tinyint(2)  NOT NULL DEFAULT '0'" );
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}noptin_subscribers ADD date_created  DATE" );

		// Had not been implemented.
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}noptin_subscribers DROP COLUMN source" );

		// Not really helpful.
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}noptin_subscribers DROP COLUMN time" );

		dbDelta( array( $this->get_subscriber_meta_table_schema() ) );

		$this->upgrade_from_4();
	}

	/**
	 * Upgrades the db from version 2 to 4
	 */
	private function upgrade_from_4() {
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

		// Fetch all subscriber ids then add them to list of subscribers to migrate.
		$subscriber_ids = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}noptin_subscribers" );

		if ( ! empty( $subscriber_ids ) ) {

			// Add migration flag to all subscribers.
			foreach ( $subscriber_ids as $subscriber_id ) {
				update_noptin_subscriber_meta( $subscriber_id, '_migrate_subscriber', 1 );
			}

			// Create recurring CRON job to migrate subscribers.
			wp_schedule_single_event( time() + 60, 'noptin_migrate_subscribers' );

			define( 'NOPTIN_MIGRATE_SUBSCRIBERS', true );
		}
	}

	/**
	 * Does a full install of the plugin.
	 */
	private function do_full_install() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Create database tables.
		dbDelta( array( $this->get_subscriber_meta_table_schema() ) );

		// Create default subscribe form.
		$count_forms = wp_count_posts( 'noptin-form' );
		if ( class_exists( 'WooCommerce' ) && empty( $count_forms ) && ! get_option( 'noptin_created_initial_form' ) ) {

			$new_form = new Noptin_Form(
				array(
					'title'    => __( 'Newsletter Subscription Form', 'newsletter-optin-box' ),
					'settings' => array(
						'fields' => array( 'email' ),
						'submit' => __( 'Subscribe', 'newsletter-optin-box' ),
						'labels' => 'show',
					),
				)
			);

			$new_form->save();
			update_option( 'noptin_created_initial_form', '1' );
		}

		// Do not nudge new installs to create custom fields.
		update_option( 'noptin_created_new_custom_fields', '1' );

		// Use the new editor for new installs.
		if ( class_exists( 'WooCommerce' ) ) {
			update_option( 'noptin_use_new_forms', '1' );
		}

	}

}
