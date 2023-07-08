<?php

defined( 'ABSPATH' ) || exit;

do_action( 'noptin_before_email_attachments_metabox', $campaign );

?>

<?php if ( ! defined( 'NOPTIN_ADDONS_PACK_VERSION' ) ) : ?>
	<p><?php esc_html_e( 'The add-ons pack allows you to attach images, videos, PDFs or other file types to this email.', 'newsletter-optin-box' ); ?></p>
	<p><a href="<?php echo esc_url( noptin_get_upsell_url( '/pricing/', 'attachments', 'email-campaigns' ) ); ?>" class="button noptin-button-standout" target="_blank"><?php esc_html_e( 'View Pricing', 'newsletter-optin-box' ); ?>&nbsp;<i class="dashicons dashicons-arrow-right-alt"></i></a></p>
<?php endif; ?>
