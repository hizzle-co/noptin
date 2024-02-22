<?php

namespace Hizzle\Noptin\Emails;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Container for a Noptin email.
 *
 * @since 3.0.0
 */
class Record extends \Hizzle\Noptin\Objects\Person {

	/**
	 * @var \Hizzle\Noptin\Emails\Email The external object.
	 */
	public $external;

	/**
	 * Class constructor.
	 *
	 * @param mixed $external The external object.
	 */
	public function __construct( $external ) {
		$this->external = noptin_get_email_campaign_object( $external );
	}

	/**
	 * Checks if the email exists.
	 * @return bool
	 */
	public function exists() {
		return $this->external->exists();
	}

	/**
	 * Retrieves a given field's value.
	 *
	 * @param string $field The field.
	 * @return mixed $value The value.
	 */
	public function get( $field, $args = array() ) {

		if ( ! $this->exists() ) {
			return null;
		}

		return $this->external->get( $field, true );
	}
}
