<?php

namespace Hizzle\Noptin\Integrations\EDD;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Custom email template.
 *
 * @since 3.0.0
 */
class Template extends \Hizzle\Noptin\Integrations\Template_Integration {

	/**
	 * @var string The template slug.
	 * @since 2.0.0
	 */
	public $slug = 'woocommerce';

	/**
	 * @var string The template name.
	 * @since 2.0.0
	 */
	public $name = 'WooCommerce';

	/**
	 * Processes the template.
	 *
	 * @param string $heading
	 * @param string $content
	 * @param string $footer
	 * @return string
	 */
	protected function process_template( $heading, $content, $footer ) {
		$GLOBALS['noptin_edd_email_template_footer_text'] = $footer;

		// Footer.
		add_filter( 'edd_email_footer_text', array( $this, 'email_template_add_extra_footer_text' ), 999 );

		$emails = EDD()->emails;
		$html   = $emails->__get( 'html' );

		$emails->__set( 'heading', $heading );
		$emails->__set( 'html', true );
		$email = EDD()->emails->build_email( $content );

		$emails->__set( 'html', $html );

		remove_filter( 'edd_email_footer_text', array( $this, 'email_template_add_extra_footer_text' ), 999 );
		return $email;
	}

	/**
	 * Retrieves the email's footer text.
	 *
	 * @param array $args
	 * @return string
	 */
	public function email_template_add_extra_footer_text( $text ) {

		if ( empty( $GLOBALS['noptin_edd_email_template_footer_text'] ) ) {
			return $text;
		}

		return wp_kses_post( $GLOBALS['noptin_edd_email_template_footer_text'] );
	}

	/**
	 * Applies WooCommerce email styles to Noptin templates.
	 *
	 */
	public function render_email_styles() {
		wc_get_template( 'emails/email-styles.php' );
	}
}
