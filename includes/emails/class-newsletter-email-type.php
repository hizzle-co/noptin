<?php
/**
 * Emails API: Newsletter Email Type.
 *
 * Container for the newsletter email type.
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Container for the newsletter email type.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
class Noptin_Newsletter_Email_Type extends Noptin_Email_Type {

	/**
	 * @var string
	 */
	public $type = 'newsletter';

	/**
	 * Returns the default campaign name.
	 */
	public function default_name() {

		$name = sprintf(
			// Translators: %s is the current date.
			__( 'Newsletter - %s', 'newsletter-optin-box' ),
			date_i18n( get_option( 'date_format' ) )
		);

		/**
		 * Filters the default newsletter name
		 *
		 * @param string $name The default newsletter name
		 */
		return apply_filters( 'noptin_default_newsletter_name', $name );
	}
}
