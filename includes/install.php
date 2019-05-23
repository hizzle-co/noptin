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

        //Abort if this is MU and the blog is not installed
		if ( ! is_blog_installed() ) {
			return;
		}
        
        //If this is a fresh install
		if( !$upgrade_from ){
			$this->do_full_install();
		}

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
