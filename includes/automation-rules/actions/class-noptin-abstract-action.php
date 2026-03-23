<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Base actions class.
 * @deprecated Use \Hizzle\Noptin\Automation_Rules\Actions\Action instead.
 * @since       1.2.8
 */
abstract class Noptin_Abstract_Action extends \Hizzle\Noptin\Automation_Rules\Actions\Action {
	public function __construct() {
		//_deprecated_class( __CLASS__, '4.2.0', 'Use \Hizzle\Noptin\Automation_Rules\Actions\Action instead.' );
	}
}
