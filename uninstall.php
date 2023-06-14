<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) && ! defined( 'NOPTIN_RESETING_DATA' ) ) {
	exit;
}

/**@var wpdb $wpdb */
global $wpdb;


// Delete options.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'noptin\_%';" );

// Delete tables.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}noptin_subscribers" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}noptin_subscriber_meta" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}noptin_automation_rules" );

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
