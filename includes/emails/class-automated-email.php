<?php
/**
 * Email API: Automated Email.
 *
 * Contains the main automated email class
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Represents a single automated email.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
class Noptin_Automated_Email extends \Hizzle\Noptin\Emails\Email {

	/**
	 * Class constructor.
	 *
	 * @param int|string|array $args
	 */
	public function __construct( $args ) {

		_deprecated_function( __CLASS__, '2.3.0', '\Hizzle\Noptin\Emails\Email' );

		parent::__construct( $args );
	}
}
