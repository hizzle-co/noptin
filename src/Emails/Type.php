<?php

/**
 * Container for an email type.
 *
 * @since   2.2.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Emails;

defined( 'ABSPATH' ) || exit;

/**
 * Container for an email type.
 */
class Type {

	/**
	 * @var string The email type.
	 */
	public $type;

	/**
	 * @var string The email type (plural).
	 */
	public $plural;

	/**
	 * @var string The parent type.
	 */
	public $parent_type = null;

	/**
	 * @var string The child type.
	 */
	public $child_type = null;

	/**
	 * @var string The email type label.
	 */
	public $label;

	/**
	 * @var string The email type label (plural).
	 */
	public $plural_label;

	/**
	 * @var string The new campaign button label.
	 */
	public $new_campaign_label;

	/**
	 * @var array The email sub types.
	 */
	private $sub_types = null;

	/**
	 * Class constructor.
	 *
	 * @param array $args The email type args.
	 */
	public function __construct( $args ) {
		foreach ( $args as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->{$key} = $value;
			}
		}
	}

	/**
	 * Returns the email sub types.
	 *
	 * @return array
	 */
	public function get_sub_types() {

		if ( null === $this->sub_types ) {
			$this->sub_types = get_noptin_campaign_sub_types( $this->type );
		}

		return $this->sub_types;
	}
}
