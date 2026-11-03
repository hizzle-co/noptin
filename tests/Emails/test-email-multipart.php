<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
require_once ABSPATH . WPINC . '/class-wp-phpmailer.php';

class Noptin_Multipart_Test_Mailer extends WP_PHPMailer {
	public $mime = '';
	public $alternative = '';
	public $fail = false;
	public function send() {
		$this->preSend();
		$this->mime = $this->getSentMIMEMessage();
		$this->alternative = $this->AltBody;
		if ( $this->fail ) {
			throw new \PHPMailer\PHPMailer\Exception( 'Simulated delivery failure.' );
		}
		return true;
	}
}
/** Regression coverage for email multipart. */
class Test_Email_Multipart extends Noptin_Regression_Test_Case {

	public function test_email_multipart() {
		$assert = array( $this, 'assertTrue' );
		add_filter( 'pre_http_request', static function () {
			return new WP_Error( 'test_no_network', 'Network disabled during checks.' );
		} );
		// Exercise the real WordPress/PHPMailer path, without API mailers or SMTP plugins.
		remove_all_filters( 'pre_wp_mail' );
		remove_all_actions( 'phpmailer_init' );
		$GLOBALS['phpmailer'] = new Noptin_Multipart_Test_Mailer( true );
		$mailer = $GLOBALS['phpmailer'];
		$enabled = false;
		add_filter( 'get_noptin_option_email_plain_text_alternative', static function () use ( &$enabled ) {
			return $enabled;
		} );

		$settings = \Hizzle\Noptin\Emails\Admin\Main::email_settings( array() );
		$assert( false === $settings['general_email_info']['settings']['email_plain_text_alternative']['default'], 'Setting must default to off.' );
		$html = '<html><head><style>p { color: red; }</style></head><body><h1>Hello Jane</h1><p>Caf&eacute; news.</p><p><a href="https://example.com/article">Read more</a></p><p><a href="https://example.com/unsubscribe?key=fixture">Unsubscribe</a></p></body></html>';
		$args = array(
			'recipients'      => 'fixture@example.com',
			'from_email'      => 'sender@example.com',
			'from_name'       => 'Multipart test',
			'reply_to'        => 'reply@example.com',
			'unsubscribe_url' => 'https://example.com/unsubscribe?key=fixture',
			'subject'         => 'Multipart fixture',
			'content_type'    => 'html',
			'message'         => $html,
		);
		$assert( noptin_send_email( $args ), 'Disabled HTML send failed.' );
		$assert( '' === $mailer->alternative && false === strpos( $mailer->mime, 'multipart/alternative' ), 'Disabled sends must remain HTML only.' );
		$enabled = true;
		$assert( noptin_send_email( $args ), 'Enabled HTML send failed.' );
		$assert( false !== strpos( $mailer->mime, 'multipart/alternative' ) && false !== strpos( $mailer->mime, 'Content-Type: text/plain' ) && false !== strpos( $mailer->mime, 'Content-Type: text/html' ), 'Both MIME alternatives required.' );
		$assert( false !== stripos( $mailer->alternative, 'Hello Jane' ) && false !== strpos( $mailer->alternative, 'Café news.' ), 'Personalized text and Unicode must survive: ' . $mailer->alternative );
		$assert( false !== strpos( $mailer->alternative, 'https://example.com/article' ) && false !== strpos( $mailer->alternative, 'https://example.com/unsubscribe?key=fixture' ), 'Content and unsubscribe URLs must survive.' );
		$assert( false === strpos( $mailer->alternative, '<p>' ) && false === strpos( $mailer->alternative, 'color: red' ), 'Text alternative must omit markup and CSS.' );
		$assert( $html === $mailer->Body && '' === $mailer->AltBody, 'HTML must remain intact and alternative must be cleared after sending.' );
		$assert( false === has_action( 'phpmailer_init', array( \Hizzle\Noptin\Emails\Sender::class, 'add_plain_text_alternative' ) ), 'Send hook must be removed.' );
		$args['content_type'] = 'text';
		$args['message'] = 'Plain text fixture';
		$assert( noptin_send_email( $args ) && '' === $mailer->alternative && false === strpos( $mailer->mime, 'multipart/alternative' ), 'Plain-text emails must stay plain text.' );
		$args['content_type'] = 'html';
		$args['message'] = $html;
		$mailer->fail = true;
		$assert( false === noptin_send_email( $args ) && '' === $mailer->AltBody, 'Failed sends must also clear the alternative.' );
		$mailer->fail = false;
		$enabled = false;
		$assert( noptin_send_email( $args ) && '' === $mailer->alternative, 'Later disabled sends must not inherit text.' );
		$assert( wp_mail( 'fixture@example.com', 'Unrelated mail', 'Unrelated content' ) && '' === $mailer->alternative, 'Unrelated WordPress mail must not inherit the alternative.' );
		$enabled = true;
		$captured = null;
		add_filter( 'pre_wp_mail', static function ( $result, $data ) use ( &$captured ) {
			$captured = $data;
			return true;
		}, 10, 2 );
		$assert( noptin_send_email( $args ) && $html === $captured['message'] && false !== strpos( $captured['headers'], 'Content-Type: text/html' ), 'API mailers must retain the original HTML and content type.' );
		$assert( '' === $mailer->AltBody && false === has_action( 'phpmailer_init', array( \Hizzle\Noptin\Emails\Sender::class, 'add_plain_text_alternative' ) ), 'Intercepted sends must not leave alternatives or hooks behind.' );
	}
}
