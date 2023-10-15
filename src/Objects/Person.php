<?php

namespace Hizzle\Noptin\Objects;

/**
 * Container for a single person.
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Container for a single person.
 */
abstract class Person extends Record {

	/**
	 * Retrieves the person's email address.
	 *
	 */
	abstract public function get_email();
}
