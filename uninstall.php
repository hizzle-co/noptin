<?php
//Goodbye Mate

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

//Delete options
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'noptin\_%'" );

//Delete subscribers table
$table = $wpdb->prefix . 'gp_optin_subscribers';	
if($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
	$sql = "DROP TABLE $table";
	$wpdb->query($sql);
}
