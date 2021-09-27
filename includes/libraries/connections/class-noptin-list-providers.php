<?php
/**
 * Noptin.com List Providers Class.
 *
 * @package Noptin\noptin.com
 * @since   1.5.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * Noptin_List_Providers Class
 *
 * @since 1.5.1
 * @ignore
 */
abstract class Noptin_List_Providers {

	/**
	 * Returns the providers unique id.
	 *
	 * @since 1.5.1
	 */
	abstract public function get_id();

	/**
	 * Returns the providers unique name.
	 *
	 * @since 1.5.1
	 */
	abstract public function get_name();

	/**
	 * Returns the providers remote URL.
	 *
	 * @since 1.5.1
	 */
	abstract public function get_url();

	/**
     * Get the cache key
     *
     * @param string $post_fix The post fix.
     * @return string
     */
    public function get_cache_key( $post_fix ) {
		return sanitize_key( 'noptin_' . $this->get_id() . '_' . $post_fix );
	}

	/**
     * Retrieves an item from the cache.
     *
     * @param string $item The item to retrieve.
     * @return mixed|false
     */
    public function get_cached( $item ) {
		return get_transient( $this->get_cache_key( $item ) );
	}

	/**
     * Adds an item to the cache.
     *
     * @param string $key The cache key.
	 * @param mixed $data The cache data.
     * @return mixed|false
     */
    public function cache( $key, $data ) {
		return set_transient( $this->get_cache_key( $key ), $data, HOUR_IN_SECONDS );
	}

	/**
     * Empties the cache.
     *
     */
    public function empty_cache() {
		global $wpdb;

		delete_transient( $this->get_cache_key( 'list_ids' ) );

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s;",
				$wpdb->esc_like( $this->get_cache_key( 'list' ) ) . '%'
			)
		);

	}

	/**
     * Get list ID's
     *
     * @param bool $force Do not retrieve from the cache.
     * @return string[]
     */
    public function get_list_ids( $force = false ) {
        $list_ids = $this->get_cached( 'list_ids' );

        if ( ! is_array( $list_ids ) || $force ) {
            $list_ids = $this->fetch_list_ids();
			$this->cache( 'list_ids', $list_ids );
        }

        return is_array( $list_ids ) ? $list_ids : array();
    }

	/**
	 * Fetches list ids from the provider.
	 *
	 * @since 1.5.1
	 * @return int[]
	 */
	abstract public function fetch_list_ids();

	/**
	 * Returns all lists from the provider for use in a dropdown.
	 *
	 * @param bool $force Do not retrieve from the cache.
	 * @since 1.5.1
	 * @return string[]
	 */
	public function get_dropdown_lists( $force = false ) {

		$lists = array();

		foreach ( $this->get_lists( $force ) as $list_id => $list ) {
			$lists["$list_id"] = sanitize_text_field( $list->get_name() );
		}

		return $lists;
	}

	/**
	 * Returns a list type for use in a dropdown.
	 *
	 * @since 1.5.1
	 * @return string[]
	 */
	public function get_dropdown( $list_type ) {
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

	/**
	 * Retrieves secondary lists.
	 *
	 * @return array
	 * @since 1.5.1
	 */
	public function get_secondary() {
		return array();
	}

	/**
	 * Returns all lists from the provider.
	 *
	 * @param bool $force Do not retrieve from the cache.
	 * @since 1.5.1
	 * @return Noptin_List_Provider[]
	 */
	public function get_lists( $force = false ) {

		$lists = array();

		foreach ( $this->get_list_ids( $force ) as $list_id ) {
			$lists["$list_id"] = $this->get_list( $list_id );
		}

		return $lists;
	}

	/**
	 * Retrieves a list by list id.
	 *
	 * @param string $list_id The list id.
	 * @since 1.5.1
	 * @return Noptin_List_Provider|false
	 */
	public function get_list( $list_id ) {

		$cache_key = $this->get_cache_key( 'list' ) . '_' . $list_id;
		$cached    = get_option( $cache_key );

		if ( false === $cached ) {
            $cached = $this->fetch_list( $list_id );
			update_option( $cache_key, $cached, false );
        }

		if ( empty( $cached ) ) {
			return false;
		}

		return $this->prepare_list( $cached );
	}

	/**
	 * Fetches a list.
	 *
	 * @since 1.5.1
	 * @return mixed
	 */
	abstract public function fetch_list( $list_id );

	/**
	 * Prepares a list.
	 *
	 * @since 1.5.1
	 * @return Noptin_List_Provider
	 */
	abstract public function prepare_list( $list );

	/**
	 * Returns an array of list columns.
	 *
	 * @since 1.5.1
	 * @return array
	 */
	public function get_list_columns() {
		return array(
			'name' => __( 'Name', 'newsletter-optin-box' ),
			'id'   => __( 'ID', 'newsletter-optin-box' ),
		);
	}

	/**
	 * Displays all lists.
	 *
	 * @since 1.5.1
	 */
	public function output_lists() {
		$table = new Noptin_List_Providers_Table( $this );
		$table->prepare_items();
		$table->display();
	}

	/**
	 * Returns the universal merge fields.
	 *
	 * @since 1.5.1
	 * @return array
	 */
	public function get_fields() {
		return array();
	}

	/**
	 * Tags a subscriber.
	 *
	 * @param Noptin_Subscriber $subscriber
	 * @param array $tags
	 * @since 1.5.1
	 */
	public function tag_subscriber( $subscriber, $tags ) {}

	/**
	 * Untags a subscriber.
	 *
	 * @param Noptin_Subscriber $subscriber
	 * @param array $tags
	 * @since 1.5.1
	 */
	public function untag_subscriber( $subscriber, $tags ) {}

}
