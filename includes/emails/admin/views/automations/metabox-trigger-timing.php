<?php

defined( 'ABSPATH' ) || exit;

do_action( 'noptin_before_email_trigger_timing_metabox', $campaign );

?>

<?php if ( ! defined( 'NOPTIN_ADDONS_PACK_FILE' ) ) : ?>
	<p><?php esc_html_e( 'The add-ons pack allows you to delay (schedule) this email for a given number of minutes, hours, or days.', 'newsletter-optin-box' ); ?></p>
	<p><a href="<?php echo esc_url( noptin_get_upsell_url( '/pricing/', 'timing', 'email-campaigns' ) ); ?>" class="button noptin-button-standout" target="_blank"><?php esc_html_e( 'View Pricing', 'newsletter-optin-box' ); ?>&nbsp;<i class="dashicons dashicons-arrow-right-alt"></i></a></p>
<?php endif; ?>
