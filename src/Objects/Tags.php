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
	 * @var null|Record the old record.
	 */
	private $old_record;

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
	 */
	public function prepare_record_tags( $record ) {
		global $noptin_current_objects;

		// Store the current record.
		if ( ! is_array( $noptin_current_objects ) ) {
			$noptin_current_objects = array();
		}

		$this->old_record = isset( $noptin_current_objects[ $this->object_type ] ) ? $noptin_current_objects[ $this->object_type ] : null;

		$noptin_current_objects[ $this->object_type ] = $record;
	}

	public function restore_record_tags() {
		global $noptin_current_objects;

		// Store the current record.
		if ( ! is_array( $noptin_current_objects ) ) {
			$noptin_current_objects = array();
		}

		// Restore the old record.
		if ( ! $this->old_record ) {
			unset( $noptin_current_objects[ $this->object_type ] );
		} else {
			$noptin_current_objects[ $this->object_type ] = $this->old_record;
		}
	}

	/**
	 * @param Record $record The record.
	 * @param string $content The content containing dynamic content tags.
	 * @param string $escape_function Escape mode for the replacement value. Leave empty for no escaping.
	 * @return string
	 */
	public function replace_record_fields( $record, $content, $escape_function = 'wp_kses_post' ) {
		if ( ! is_string( $content ) || empty( $content ) ) {
			return $content;
		}

		// Store the current record.
		$this->prepare_record_tags( $record );

		// Replace merge tags.
		$content = $this->replace( $content, $escape_function );

		// Restore the old record.
		$this->restore_record_tags();

		return $content;
	}

	/**
	 * @param string $content The string containing dynamic content tags.
	 * @param string $escape_function Escape mode for the replacement value. Leave empty for no escaping.
	 * @return string
	 */
	protected function replace( $content, $escape_function = '' ) {
		return $this->replace_with_brackets( $content, $escape_function );
	}
}
