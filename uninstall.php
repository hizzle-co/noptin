<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Delete the actions page.
$page = get_option( 'noptin_actions_page' );
if ( is_numeric( $page ) ) {
	wp_delete_post( $page, true );
}

// Delete options.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'noptin\_%';" );

// Delete tables.
$tables = array(
	"{$wpdb->prefix}noptin_subscribers",
	"{$wpdb->prefix}noptin_subscriber_meta",
	"{$wpdb->prefix}noptin_automation_rules",
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

// Delete newsletters.
$wpdb->query(
	"DELETE a,b
    FROM {$wpdb->posts} a
    LEFT JOIN {$wpdb->postmeta} b
        ON (a.ID = b.post_id)
	WHERE a.post_type = 'noptin-campaign'"
);

// Delete subscription forms.
$wpdb->query(
	"DELETE a,b
    FROM {$wpdb->posts} a
    LEFT JOIN {$wpdb->postmeta} b
        ON (a.ID = b.post_id)
	WHERE a.post_type = 'noptin-form'"
);

// Crons.
wp_clear_scheduled_hook( 'noptin_daily_maintenance' );
