<?php

namespace Hizzle\Noptin\Integrations;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Base custom fields integration
 *
 * @since 2.0.0
 */
abstract class Custom_Fields_Integration {

	/**
	 * @var int The priority for hooks.
	 * @since 2.0.0
	 */
	public $priority = 10;

	/**
	 * Constructor
	 */
	public function __construct() {

		// User fields.
		add_filter( 'noptin_users_known_custom_fields', array( $this, 'filter_user_fields' ), $this->priority );

		// Post type fields.
		add_filter( 'noptin_post_type_known_custom_fields', array( $this, 'filter_post_type_fields' ), $this->priority, 2 );
	}

	/**
	 * Filters user fields.
	 *
	 * @param array $custom_fields The known user custom fields.
	 */
	public function filter_user_fields( $custom_fields ) {
		static $fields = null;

		if ( is_null( $fields ) ) {
			$fields = $this->get_user_fields();
		}

		return array_merge(
			$custom_fields,
			$fields
		);
	}

	/**
	 * Returns an array of user fields.
	 *
	 * @return array $custom_fields The known user custom fields.
	 */
	protected function get_user_fields() {
		return array();
	}

	/**
	 * Filters post type fields.
	 *
	 * @param array $custom_fields The known user custom fields.
	 * @param string $post_type The post type.
	 */
	public function filter_post_type_fields( $custom_fields, $post_type ) {
		static $fields = array();

		if ( ! isset( $fields[ $post_type ] ) ) {
			$fields[ $post_type ] = $this->get_post_type_fields( $post_type );
		}

		return array_merge(
			$custom_fields,
			$fields[ $post_type ]
		);
	}

	/**
	 * Returns an array of post type fields.
	 *
	 * @param string $post_type The post type.
	 * @return array $custom_fields The known user custom fields.
	 */
	protected function get_post_type_fields( $post_type ) {
		return array();
	}
}
