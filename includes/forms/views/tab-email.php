<?php
/**
 * Displays the welcome email tab in the form editor.
 *
 * @var Noptin_Form $form
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div class="card">
	<h3><?php esc_html_e( 'Welcome new subscribers', 'newsletter-optin-box' ); ?></h3>
	<p><?php esc_html_e( 'Noptin allows you to send welcome emails to new subscribers. You can also set up a series of welcome emails to act as an email course.', 'newsletter-optin-box' ); ?></p>
	<p><a href="<?php echo esc_url( noptin_get_upsell_url( '/guide/sending-emails/welcome-emails/', 'welcome-emails', 'subscription-forms' ) ); ?>" class="button noptin-button-standout" target="_blank"><?php esc_html_e( 'Learn More', 'newsletter-optin-box' ); ?>&nbsp;<i class="dashicons dashicons-arrow-right-alt"></i></a></p>
</div>
