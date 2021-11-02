<?php
/**
 * Upgrades the db
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Noptin_Install Class.
 */
class Noptin_Install {

	public $charset_collate;

	public $table_prefix;

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

		$this->charset_collate = $wpdb->get_charset_collate();
		$this->table_prefix    = $wpdb->prefix;

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
		}

		// Upgrading from version 2.
		if ( 2 === $upgrade_from ) {
			return $this->upgrade_from_2();
		}

		// Upgrading from version 3.
		if ( 3 === $upgrade_from ) {
			return $this->upgrade_from_3();
		}

	}

	/**
	 * Force create the subscribers table
	 */
	public function create_subscribers_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( array( $this->get_subscribers_table_schema() ) );
	}

	/**
	 * Force create the subscribers meta table
	 */
	public function create_subscribers_meta_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( array( $this->get_subscriber_meta_table_schema() ) );
	}

	/**
	 * Force create the subscribers automation rules table
	 */
	public function create_automation_rules_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( array( $this->get_automation_rules_table_schema() ) );
	}

	/**
	 * Returns the subscribers table schema
	 */
	private function get_subscribers_table_schema() {

		$table           = $this->table_prefix . 'noptin_subscribers';
		$charset_collate = $this->charset_collate;

		return "CREATE TABLE $table (
			id bigint(20) unsigned NOT NULL auto_increment,
            first_name varchar(100) NOT NULL default '',
            second_name varchar(100) NOT NULL default '',
            email varchar(100) NOT NULL default '',
            active tinyint(2) NOT NULL default '0',
            confirm_key varchar(255) NOT NULL default '',
            confirmed tinyint(2) NOT NULL default '0',
            date_created date NOT NULL DEFAULT '0000-00-00',
			PRIMARY KEY  (id),
			KEY email (email)
) $charset_collate;";

	}

	/**
	 * Returns the subscriber meta table schema
	 */
	private function get_subscriber_meta_table_schema() {

		$table           = $this->table_prefix . 'noptin_subscriber_meta';
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
	 * Returns the automation rules table schema
	 *
	 * @since 1.2.8
	 */
	private function get_automation_rules_table_schema() {

		$table           = $this->table_prefix . 'noptin_automation_rules';
		$charset_collate = $this->charset_collate;

		return "CREATE TABLE $table (
			id bigint(20) unsigned NOT NULL auto_increment,
			action_id varchar(255) NOT NULL default '',
			action_settings longtext DEFAULT NULL,
			trigger_id varchar(255) NOT NULL default '',
			trigger_settings longtext DEFAULT NULL,
			status tinyint(2) NOT NULL default '1',
			times_run int(11) NOT NULL default '0',
			created_at datetime NOT NULL default '0000-00-00 00:00:00',
			updated_at datetime NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY trigger_id (trigger_id),
			KEY action_id (action_id)
) $charset_collate;";

	}

	/**
	 * Upgrades the db from version 1 to 2
	 */
	private function upgrade_from_1() {
		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$table = $this->table_prefix . 'noptin_subscribers';

		$wpdb->query( "ALTER TABLE $table ADD active tinyint(2)  NOT NULL DEFAULT '0'" );
		$wpdb->query( "ALTER TABLE $table ADD date_created  DATE" );

		// Had not been implemented.
		$wpdb->query( "ALTER TABLE $table DROP COLUMN source" );

		// Not really helpful.
		$wpdb->query( "ALTER TABLE $table DROP COLUMN time" );

		dbDelta( array( $this->get_subscriber_meta_table_schema() ) );

		$this->upgrade_from_2();
	}

	/**
	 * Upgrades the db from version 2 to 3
	 */
	private function upgrade_from_2() {

		// Create initial subscriber.
		add_noptin_subscriber( $this->get_initial_subscriber_args() );

		$this->upgrade_from_3();
	}

	/**
	 * Upgrades the db from version 3 to 4
	 */
	private function upgrade_from_3() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( array( $this->get_automation_rules_table_schema() ) );
	}

	/**
	 * Returns initial subscriber args
	 */
	function get_initial_subscriber_args() {

		$admin_email = sanitize_email( get_bloginfo( 'admin_email' ) );
		$args        = array(
			'email'           => $admin_email,
			'_subscriber_via' =>'default_user'
		);

		if ( $admin = get_user_by( 'email', $admin_email ) ) {
			$args['name'] = $admin->display_name;
		}

		return $args;

	}

	/**
	 * Does a full install of the plugin.
	 */
	private function do_full_install() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// Create database tables.
		dbDelta( array( $this->get_subscribers_table_schema() ) );
		dbDelta( array( $this->get_subscriber_meta_table_schema() ) );
		dbDelta( array( $this->get_automation_rules_table_schema() ) );

		// Add a default subscriber.
		add_noptin_subscriber( $this->get_initial_subscriber_args() );

		// Do not nudge new installs to create custom fields.
		update_option( 'noptin_created_new_custom_fields', '1' );

		// Use the new editor for new installs.
		update_option( 'noptin_use_new_forms', '1' );

		// Create default subscribe form.
		$new_form = new Noptin_Form(
			array(
				'title'      => __( 'Newsletter Subscription Form', 'newsletter-optin-box' ),
				'settings'   => array(
					'fields' => array( 'email' ),
					'submit' => __( 'Subscribe', 'newsletter-optin-box' ),
					'labels' => 'show',
				)
			)
		);

		$new_form->save();
	}


}
