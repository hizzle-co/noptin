<?php

/**
 * Container for all collections.
 *
 * @since   1.0.0
 */

namespace Hizzle\Noptin\Objects;

defined( 'ABSPATH' ) || exit;

/**
 * Container for a single person.
 */
class Store {

	/**
	 * @var Collection[] Known collections.
	 */
	protected static $collections = array();

	/**
	 * Adds a new collection.
	 *
	 * @param Collection $collection The collection to add.
	 */
	public static function add( $collection ) {
		self::$collections[ $collection->type ] = $collection;
	}

	/**
	 * Checks if a collection exists.
	 *
	 * @param string $type The collection type.
	 * @return bool True if the collection exists, false otherwise.
	 */
	public static function exists( $type ) {
		return isset( self::$collections[ $type ] );
	}

	/**
	 * Retrieves a collection.
	 *
	 * @param string $type The collection type.
	 * @return Collection $collection The collection.
	 */
	public static function get( $type ) {
		return isset( self::$collections[ $type ] ) ? self::$collections[ $type ] : null;
	}

	/**
	 * Retrieves all collections.
	 *
	 * @return Collection[] $collections All collections.
	 */
	public static function all() {
		return self::$collections;
	}

	/**
	 * Retrieves filtered collections.
	 *
	 * @param array $args The args.
	 * @return Collection[] $collections All collections.
	 */
	public static function filtered( $args, $operator = 'AND' ) {
		return wp_list_filter( self::$collections, $args, $operator );
	}

	/**
	 * Fetches a collection's fields.
	 *
	 * @param string $type The collection type.
	 * @return array $fields The collection's fields.
	 */
	public static function fields( $type ) {
		$collection = self::get( $type );

		if ( ! $collection ) {
			return array();
		}

		return $collection->get_all_fields();
	}

	/**
	 * Fetches a collection's merge tags.
	 *
	 * @param string $type The collection type.
	 * @param bool   $prefix Whether to prefix the merge tags.
	 * @return array $fields The collection's fields.
	 */
	public static function smart_tags( $type, $group = '', $prefix = true ) {

		$fields = self::fields( $type );

		if ( empty( $fields ) ) {
			return array();
		}

		// Maybe use collection name as group.
		if ( true === $group ) {
			$group = self::get_collection_config( $type );
		}

		$callback    = array( __CLASS__, 'handle_field_smart_tag' );
		$object_type = is_string( $prefix ) && false !== strpos( $prefix, '.' ) ? $prefix : $type;
		$prefix      = true === $prefix ? self::get_collection_config( $type, 'smart_tags_prefix' ) : $prefix;

		return self::convert_fields_to_smart_tags( $fields, $object_type, $group, $prefix, $callback );
	}

	/**
	 * Converts custom fields to a smart tags.
	 *
	 * @since 3.0.0
	 * @return array
	 */
	public static function convert_fields_to_smart_tags( $fields, $object_type = '', $group = '', $prefix = false, $callback = false ) {

		$prepared = array();

		foreach ( $fields as $key => $field ) {
			$key       = $prefix ? $prefix . '.' . $key : $key;
			$smart_tag = self::convert_field_to_smart_tag( $field, $group, $callback );

			// Standardize examples.
			if ( ! empty( $smart_tag['example'] ) ) {
				$smart_tag['example'] = $key . ' ' . $smart_tag['example'];
			}

			// Add collection.
			if ( ! empty( $object_type ) ) {
				$smart_tag['object_type'] = $object_type;
			}

			$prepared[ $key ] = $smart_tag;
		}

		return $prepared;
	}

	/**
	 * Converts a custom field to a smart tag.
	 *
	 * @since 3.0.0
	 * @return array
	 */
	public static function convert_field_to_smart_tag( $field, $group = '', $callback = '' ) {

		$smart_tag = $field;

		if ( empty( $field['skip_smart_tag'] ) ) {
			$smart_tag['conditional_logic'] = 'boolean' === $field['type'] ? 'string' : $field['type'];
		}

		if ( 'boolean' === $field['type'] ) {
			$smart_tag['options'] = array(
				'yes' => __( 'Yes', 'newsletter-optin-box' ),
				'no'  => __( 'No', 'newsletter-optin-box' ),
			);

			$smart_tag['is_boolean'] = '1';
		}

		if ( ! empty( $group ) && empty( $smart_tag['group'] ) ) {
			$smart_tag['group'] = $group;
		}

		if ( ! empty( $callback ) ) {
			$smart_tag['callback'] = $callback;
		}

		return $smart_tag;
	}

	/**
	 * Fetches a collection's field.
	 *
	 * @param string $type The collection type.
	 * @param string $field The field.
	 */
	public static function get_collection_config( $type, $field = 'singular_label' ) {

		$collection = self::get( $type );
		return empty( $collection ) ? '' : $collection->{$field};
	}

	/**
	 * Callback to handle the provided field's smart tag.
	 *
	 * @param array $args The args.
	 * @param string $field The field.
	 * @param array $config The config.
	 * @return string The smart tag.
	 */
	public static function handle_field_smart_tag( $args, $field, $config = array() ) {
		/** @var Record[] $noptin_current_objects */
		global $noptin_current_objects;

		if ( ! is_array( $noptin_current_objects ) || empty( $config['object_type'] ) ) {
			return '';
		}

		// If object type has a dot, it's a prefix, remove it.
		if ( false !== strpos( $config['object_type'], '.' ) ) {
			$field = substr( $field, strlen( $config['object_type'] ) + 1 );
		} else {
			$field = explode( '.', $field );
			array_shift( $field );
			$field = implode( '.', $field );
		}

		// Bail if the collection doesn't exist.
		if ( ! isset( $noptin_current_objects[ $config['object_type'] ] ) || empty( $field ) ) {
			return '';
		}

		// Fetch the raw value.
		$object = $noptin_current_objects[ $config['object_type'] ];
		if ( 'newsletter' === $field && is_callable( array( $object, 'get_email' ) ) ) {
			$email     = call_user_func( array( $object, 'get_email' ) );
			$raw_value = false;

			if ( is_string( $email ) && is_email( $email ) ) {
				$subscriber = noptin_get_subscriber( $email );
				$raw_value  = ( $subscriber->is_active() && ! noptin_is_email_unsubscribed( $email ) );
			}
		} elseif ( 'avatar_url' === $field && is_callable( array( $object, 'get_email' ) ) ) {
			$email     = call_user_func( array( $object, 'get_email' ) );
			$raw_value = esc_url( get_avatar_url( $email, $args ) );
		} else {
			$raw_value = $noptin_current_objects[ $config['object_type'] ]->get( $field, $args );
		}

		// Convert bools to yes/no.
		if ( is_bool( $raw_value ) ) {
			$raw_value = $raw_value ? 'yes' : 'no';
		}

		// Convert \DateTime to string.
		if ( $raw_value instanceof \DateTime ) {
			$raw_value = $raw_value->format( 'Y-m-d H:i:s' );
		}

		// Are we formatting the value?
		if ( empty( $args['format'] ) || '' === $raw_value || null === $raw_value ) {
			return $raw_value;
		}

		return $noptin_current_objects[ $config['object_type'] ]->format( $raw_value, $args );
	}
}
