<?php
/**
 * Displays the integrations tab in the form editor.
 *
 * @var Noptin_Form $form
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$all_settings = $form->settings;

?>

<h2 class="screen-reader-text"><?php esc_html_e( 'Form Integrations', 'newsletter-optin-box' ); ?></h2>

<p class="description" style="margin-bottom: 16px;"><?php esc_html_e( 'Noptin also allows you to add new subscribers to an external email service provider.', 'newsletter-optin-box' ); ?></p>

<?php if ( noptin_upsell_integrations() ) : ?>
	<div class="card">
		<h3><?php esc_html_e( 'No integration installed', 'newsletter-optin-box' ); ?></h3>
		<p><?php esc_html_e( 'Please install the appropriate integration to automatically add new subscribers to an external email provider such as ConvertKit or Mailchimp.', 'newsletter-optin-box' ); ?></p>
		<p><a href="<?php echo esc_url( noptin_get_upsell_url( '/integrations/#connections', 'connections', 'subscription-forms' ) ); ?>" class="button noptin-button-standout" target="_blank"><?php esc_html_e( 'View Integrations', 'newsletter-optin-box' ); ?>&nbsp;<i class="dashicons dashicons-arrow-right-alt"></i></a></p>
	</div>
<?php else : ?>
	<?php do_action( 'noptin_form_available_integration_settings', $form ); ?>
<?php endif; ?>
