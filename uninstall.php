<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Delete options.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'noptin\_%'" );

// Delete subscribers table.
$table = $wpdb->prefix . 'noptin_subscribers';
if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
	$wpdb->query( $wpdb->prepare( 'DROP TABLE %s', $table ) );
}

// Delete subscribers meta table.
$table = $wpdb->prefix . 'noptin_subscriber_meta';
if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
	$wpdb->query( $wpdb->prepare( 'DROP TABLE %s', $table ) );
}

// Delete automation rules table.
$table = $wpdb->prefix . 'noptin_automation_rules';
if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
	$wpdb->query( $wpdb->prepare( 'DROP TABLE %s', $table ) );
}
