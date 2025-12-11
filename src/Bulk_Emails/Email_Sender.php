<?php

/**
 * Bulk Emails API: Email Sender.
 *
 * Contains the main email sender class.
 *
 * @since   1.12.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Bulk_Emails;

defined( 'ABSPATH' ) || exit;

/**
 * The main email sender class.
 * @deprecated
 */
abstract class Email_Sender extends \Hizzle\Noptin\Emails\Bulk\Sender {
	/**
	 * Initiates new non-blocking asynchronous request.
	 *
	 * @ignore
	 */
	public function __construct() {
		_deprecated_class( __CLASS__, '4.1.0', '\Hizzle\Noptin\Emails\Bulk\Sender' );
		parent::__construct();
	}
}
