<?php
/**
 * Displays the welcome email tab in the form editor.
 *
 * @var Noptin_Form $form
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$url = add_query_arg(
	array(
		'utm_medium'   => 'plugin-dashboard',
		'utm_campaign' => 'form-builder',
		'utm_source'   => urlencode( esc_url( get_home_url() ) ),
	),
	'https://noptin.com/product/ultimate-addons-pack'
);

?>

<h2 class="screen-reader-text"><?php esc_html_e( 'Welcome Email', 'newsletter-optin-box' ); ?></h2>

<p class="description" style="margin-bottom: 16px;"><?php esc_html_e( 'This email is automatically sent to new subscribers who sign-up via this form. If double opt-in is enabled, then the email will be sent after a subscriber confirms their email address.', 'newsletter-optin-box' ); ?></p>

<?php if ( ! defined( 'NOPTIN_WELCOME_EMAILS_VERSION' ) ) : ?>
	<div class="card">
		<h2><?php _e( 'This is a premium feature', 'newsletter-optin-box' );?></h2>
		<p><?php _e( "We're sorry, Welcome Emails are not available on your plan. Please buy the ultimate addons pack to send welcome emails and get access to more awesome features.", 'newsletter-optin-box' ); ?></p>
		<p><a href="<?php echo esc_url( $url );?>" class="button noptin-button-standout" target="_blank"><?php _e( 'Learn More', 'newsletter-optin-box' ); ?>&nbsp;<i class="dashicons dashicons-arrow-right-alt"></i></a></p>
	</div>
<?php endif; ?>
