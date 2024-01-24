<?php

namespace Hizzle\Noptin\Objects;

defined( 'ABSPATH' ) || exit;

/**
 * Allows users to use dynamic tags in shortcodes.
 *
 * @internal
 * @access private
 * @since 3.0.0
 * @ignore
 */
class Tags extends \Noptin_Dynamic_Content_Tags {

	/**
	 * @var string $object_type The object type for the tags.
	 */
	private $object_type;

	/**
	 * Parses a record's tags.
	 *
	 * @param string $object_type The object type.
	 * @param array $fields
	 */
	public function __construct( $object_type ) {
		$this->object_type = $object_type;
		$this->tags        = Store::smart_tags( $object_type, true );
	}

	/**
	 * @param Record $record The record.
	 * @param string $content The content containing dynamic content tags.
	 * @param string $escape_function Escape mode for the replacement value. Leave empty for no escaping.
	 * @return string
	 */
	public function replace_record_fields( $record, $content, $escape_function = 'wp_kses_post' ) {
		global $noptin_current_objects;

		if ( ! is_string( $content ) || empty( $content ) ) {
			return $content;
		}

		// Store the current record.
		if ( ! is_array( $noptin_current_objects ) ) {
			$noptin_current_objects = array();
		}

		$old_record = isset( $noptin_current_objects[ $this->object_type ] ) ? $noptin_current_objects[ $this->object_type ] : null;

		$noptin_current_objects[ $this->object_type ] = $record;

		// Replace merge tags.
		$content = $this->replace_with_brackets( $content, $escape_function );

		// Restore the old record.
		if ( null === $old_record ) {
			unset( $noptin_current_objects[ $this->object_type ] );
		} else {
			$noptin_current_objects[ $this->object_type ] = $old_record;
		}

		return $content;
	}
}
