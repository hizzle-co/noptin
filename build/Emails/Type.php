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
	 * @var string Checks if this email supports recipients.
	 */
	public $supports_recipients = true;

	/**
	 * @var string Checks if this email supports timing.
	 */
	public $supports_timing = false;

	/**
	 * @var string Checks if this email supports menu order.
	 */
	public $supports_menu_order = false;

	/**
	 * @var string Timing help text.
	 */
	public $timing_help_text = null;

	/**
	 * @var string Checks if this email supports sub_types.
	 */
	public $supports_sub_types = false;

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
	 * @var string Click to add first campaign label.
	 */
	public $click_to_add_first;

	/**
	 * @var string Icon
	 */
	public $icon = 'email';

	/**
	 * @var string[] Contexts
	 */
	public $contexts = array();

	/**
	 * @var array The email sub types.
	 */
	private $sub_types = null;

	/**
	 * @var string Checks if this is an upsell.
	 */
	public $upsell = false;

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

		if ( ! $this->supports_sub_types ) {
			return array();
		}

		if ( null === $this->sub_types ) {
			$this->sub_types = get_noptin_campaign_sub_types( $this->type );
		}

		return $this->sub_types;
	}

	/**
	 * Returns an email sub type.
	 *
	 * @return array
	 */
	public function get_sub_type( $sub_type ) {

		if ( ! $this->supports_sub_types ) {
			return false;
		}

		if ( empty( $sub_type ) ) {
			return null;
		}

		$sub_types = $this->get_sub_types();
		if ( empty( $sub_types ) ) {
			return null;
		}

		if ( isset( $sub_types[ $sub_type ] ) ) {
			return $sub_types[ $sub_type ];
		}

		foreach ( $sub_types as $custom_type ) {
			if ( isset( $custom_type['alias'] ) && $sub_type === $custom_type['alias'] ) {
				return $custom_type;
			}
		}

		return null;
	}

	/**
	 * Returns the email add URL.
	 *
	 * @return array
	 */
	public function get_add_url() {

		$url = add_query_arg(
			array(
				'page'              => 'noptin-email-campaigns',
				'noptin_email_type' => rawurlencode( $this->type ),
				'noptin_campaign'   => '0',
			),
			admin_url( '/admin.php' )
		);

		// If the type has a parent, add it to the URL.
		if ( ! empty( $this->parent_type ) && ! empty( $_GET['noptin_parent_id'] ) ) {
			$url = add_query_arg( 'noptin_parent_id', (int) $_GET['noptin_parent_id'], $url );
		}

		return $url;
	}

	/**
	 * Returns the data for the email type.
	 *
	 * @return array
	 */
	public function to_array() {
		return array_merge(
			get_object_vars( $this ),
			array(
				'sub_types' => $this->get_sub_types(),
				'add_new'   => $this->get_add_url(),
			)
		);
	}
}
