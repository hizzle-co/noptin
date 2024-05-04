<?php

namespace Hizzle\Noptin\Integrations\WooCommerce;

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
		$GLOBALS['noptin_woocommerce_email_template_footer_text'] = $footer;

		ob_start();

		// Heading.
		wc_get_template( 'emails/email-header.php', array( 'email_heading' => $heading ) );

		// Content.
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// Footer.
		add_filter( 'woocommerce_email_footer_text', array( $this, 'email_template_add_extra_footer_text' ), 999 );
		wc_get_template( 'emails/email-footer.php' );
		remove_filter( 'woocommerce_email_footer_text', array( $this, 'email_template_add_extra_footer_text' ), 999 );

		return ob_get_clean();
	}

	/**
	 * Retrieves the email's footer text.
	 *
	 * @param array $args
	 * @return string
	 */
	public function email_template_add_extra_footer_text( $text ) {

		if ( empty( $GLOBALS['noptin_woocommerce_email_template_footer_text'] ) ) {
			return $text;
		}

		return wp_kses_post( $GLOBALS['noptin_woocommerce_email_template_footer_text'] );
	}

	/**
	 * Applies WooCommerce email styles to Noptin templates.
	 *
	 */
	public function render_email_styles() {
		wc_get_template( 'emails/email-styles.php' );
	}
}
