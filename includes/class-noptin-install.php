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
	 * Install Noptin
	 *
	 * @param int|string $upgrade_from The name of a table to create or the database version to upgrade from.
	 * @param int        $current_version The current database version (not used).
	 */
	public function __construct( $upgrade_from, $current_version ) {

		// Abort if this is MS and the blog is not installed.
		if ( ! is_blog_installed() ) {
			return;
		}

		update_option( 'noptin_db_version', $current_version );

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
	}

	/**
	 * Does a full install of the plugin.
	 */
	private function do_full_install() {

		// Create default subscribe form.
		$count_forms = wp_count_posts( 'noptin-form' );
		if ( class_exists( 'WooCommerce' ) && empty( $count_forms ) && ! get_option( 'noptin_created_initial_form' ) ) {
			update_option( 'noptin_created_initial_form', '1' );

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
		}

		// Use the new editor for new installs.
		if ( class_exists( 'WooCommerce' ) ) {
			update_option( 'noptin_use_new_forms', '1' );
		}

	}

}
