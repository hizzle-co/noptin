<?php
/**
 * Noptin.com List Provider Class.
 *
 * @package Noptin\noptin.com
 * @since   1.5.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * Noptin_List_Provider Class
 *
 * @since 1.5.1
 * @ignore
 */
abstract class Noptin_List_Provider {

	/**
	 * Returns the lists unique id.
	 *
	 * @since 1.5.1
	 * @return string
	 */
	abstract public function get_id();

	/**
	 * Returns the list name.
	 *
	 * @since 1.5.1
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * Returns the lists merge fields.
	 *
	 * @since 1.5.1
	 * @return array
	 */
	public function get_fields() {
		return array();
	}

	/**
	 * Adds a subscriber to the list.
	 *
	 * @param Noptin_Subscriber $subscriber
	 * @param array $options
	 * @since 1.5.1
	 * @return string|WP_Error
	 */
	public function add_subscriber( $subscriber, $options = array() ) {}

	/**
	 * Removes a subscriber from the list.
	 *
	 * @param Noptin_Subscriber $subscriber
	 * @since 1.5.1
	 * @return true|WP_Error
	 */
	public function remove_subscriber( $subscriber ) {}

	/**
	 * Tags a subscriber in the list.
	 *
	 * @param Noptin_Subscriber $subscriber
	 * @param array $tags
	 * @since 1.5.1
	 */
	public function tag_subscriber( $subscriber, $tags ) {}

	/**
	 * Untags a subscriber in the list.
	 *
	 * @param Noptin_Subscriber $subscriber
	 * @param array $tags
	 * @since 1.5.1
	 */
	public function untag_subscriber( $subscriber, $tags ) {}

	/**
	 * Sends a campaign to the list.
	 *
	 * @param array $campaign
	 * @since 1.5.1
	 */
	public function send_campaign( $campaign ) {}

	/**
	 * Retrieves children.
	 *
	 * @param array $campaign
	 * @return array
	 * @since 1.5.1
	 */
	public function get_children() {
		return array();
	}

	/**
	 * Adds a subscriber to the given secondary lists.
	 *
	 * @since 1.5.1
	 * @param Noptin_Subscriber $subscriber
	 * @param string $secondary_list
	 * @param string $list_type
	 */
	public function add_to( $subscriber, $secondary_list, $list_type ) {}

}
