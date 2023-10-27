<?php

namespace Hizzle\Noptin\Objects;

/**
 * Container for a collection of people.
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Container for a collection of people.
 */
abstract class People extends Collection {

	/**
	 * @var string object type.
	 */
	public $object_type = 'person';

	/**
	 * @var bool Whether or not we can send a bulk email to this collection.
	 */
	public $can_email = true;

	/**
	 * Retrieves a single person from a WordPress user.
	 *
	 * @param \WP_User $user The user.
	 * @return Person $person The person.
	 */
	public function get_from_user( $user ) {
		return $this->get_from_email( $user->user_email );
	}

	/**
	 * Retrieves a single person from an email address.
	 *
	 * @param string $email The email address.
	 * @return Person $person The person.
	 */
	abstract public function get_from_email( $email );

	/**
	 * Retrieves fields that can be calculated from an email address.
	 */
	public function provides() {
		return array();
	}
}
