<?php

namespace Hizzle\Noptin\Objects;

/**
 * Container for all collections.
 *
 * @since   1.0.0
 */

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

		$callback = array( __CLASS__, 'handle_field_smart_tag' );
		$prefix   = $prefix ? self::get_collection_config( $type, 'smart_tags_prefix' ) : false;

		return self::convert_fields_to_smart_tags( $fields, $type, $group, $prefix, $callback );
	}

	/**
	 * Converts custom fields to a smart tags.
	 *
	 * @since 2.2.0
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
	 * @since 2.2.0
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

		if ( ! empty( $group ) ) {
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

		// Remove prefix.
		$field = explode( '.', $field );
		array_shift( $field );
		$field = implode( '.', $field );

		// Bail if the collection doesn't exist.
		if ( ! isset( $noptin_current_objects[ $config['object_type'] ] ) || empty( $field ) ) {
			return '';
		}

		// Fetch the raw value.
		$raw_value = $noptin_current_objects[ $config['object_type'] ]->get( $field );

		// Are we formatting the value?
		if ( empty( $args['format'] ) || '' === $raw_value || null === $raw_value ) {
			return $raw_value;
		}

		return $noptin_current_objects[ $config['object_type'] ]->format( $raw_value, $args );
	}

}
