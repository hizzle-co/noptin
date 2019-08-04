<?php
/**
 * Upgrades the db
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Noptin_Install Class.
 */
class Noptin_Install {

	/**
	 * Install Noptin
	 */
	public function __construct( $upgrade_from ) {

        //Abort if this is MS and the blog is not installed
		if ( ! is_blog_installed() ) {
			return;
		}

        //If this is a fresh install
		if( !$upgrade_from ){
			$this->do_full_install();
		}

		//Upgrading from version 1
		if( 1 == $upgrade_from ){
			$this->upgrade_from_1();
		}

	}

	/**
	 * Creates a single db table
	 */
	private function create_table( $table, $schema ) {
		global $wpdb;
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$charset_collate = $wpdb->get_charset_collate();

        //Create the table
        $table = $wpdb->prefix . $table;
        dbDelta( "CREATE TABLE IF NOT EXISTS $table ($schema) $charset_collate;" );

	}

	/**
	 * Returns the subscribers table schema
	 */
	private function get_subscribers_table_schema() {

		return "
			id bigint(9) NOT NULL AUTO_INCREMENT,
            first_name varchar(200),
            second_name varchar(200),
            email varchar(50) NOT NULL UNIQUE,
            active varchar(50) DEFAULT 'unknown',
            confirm_key varchar(50) NOT NULL,
            confirmed INT(2) NOT NULL DEFAULT '0',
            last_modified timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY id (id)";

	}

	/**
	 * Returns the subscriber meta table schema
	 */
	private function get_subscriber_meta_table_schema() {

		return "
			meta_id bigint(20) unsigned NOT NULL auto_increment,
			post_id bigint(20) unsigned NOT NULL default '0',
			meta_key varchar(255) default NULL,
			meta_value longtext,
			PRIMARY KEY  (meta_id),
			KEY post_id (post_id),
			KEY meta_key (meta_key($max_index_length))";

	}

	/**
	 * Upgrades the db from version 1 to 2
	 */
	private function upgrade_from_1() {
		global $wpdb;
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$charset_collate = $wpdb->get_charset_collate();

        //Create the subscribers table
        $table = $wpdb->prefix . 'noptin_subscribers';
        $sql = "CREATE TABLE IF NOT EXISTS $table (id bigint(9) NOT NULL AUTO_INCREMENT,
            first_name varchar(200),
            second_name varchar(200),
            email varchar(50) NOT NULL UNIQUE,
            source varchar(50) DEFAULT 'unknown',
            confirm_key varchar(50) NOT NULL,
            confirmed INT(2) NOT NULL DEFAULT '0',
            time timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY id (id)) $charset_collate;";

        dbDelta($sql);
	}

	/**
	 * Does a full install of the plugin.
	 */
	private function do_full_install() {
		global $wpdb;
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$charset_collate = $wpdb->get_charset_collate();

        //Create the subscribers table
        $table = $wpdb->prefix . 'noptin_subscribers';
        $sql = "CREATE TABLE IF NOT EXISTS $table (id bigint(9) NOT NULL AUTO_INCREMENT,
            first_name varchar(200),
            second_name varchar(200),
            email varchar(50) NOT NULL UNIQUE,
            source varchar(50) DEFAULT 'unknown',
            confirm_key varchar(50) NOT NULL,
            confirmed INT(2) NOT NULL DEFAULT '0',
            time timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY id (id)) $charset_collate;";

        dbDelta($sql);
	}


}
