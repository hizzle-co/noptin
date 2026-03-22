<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Base triggers class.
 *
 * @deprecated Use \Hizzle\Noptin\Automation_Rules\Triggers\Trigger instead.
 * @since 1.2.8
 */
abstract class Noptin_Abstract_Trigger extends \Hizzle\Noptin\Automation_Rules\Triggers\Trigger {
	public function __construct() {
		//_deprecated_class( __CLASS__, '4.2.0', 'Use \Hizzle\Noptin\Automation_Rules\Triggers\Trigger instead.' );
	}
}
