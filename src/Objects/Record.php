<?php

/**
 * Container for a single record.
 *
 * @since   1.0.0
 */

namespace Hizzle\Noptin\Objects;

defined( 'ABSPATH' ) || exit;

/**
 * Container for a single record.
 */
abstract class Record {

	/**
	 * @var mixed The external object.
	 */
	public $external;

	/**
	 * Class constructor.
	 *
	 * @param mixed $external The external object.
	 */
	public function __construct( $external ) {
		$this->external = $external;
	}

	/**
	 * Checks if the person exists.
	 * @return bool
	 */
	abstract public function exists();

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

		return $this->external->{$field};
	}

	/**
	 * Formats a given field's value.
	 *
	 * @param mixed $raw_value The raw value.
	 * @param array $args The args.
	 * @return mixed $value The formatted value.
	 */
	public function format( $raw_value, $args ) {

		// Format images.
		if ( 'image' === $args['format'] ) {
			return sprintf( '<img src="%s" alt="%s" />', esc_url( $raw_value ), esc_attr( $args['alt'] ) );
		}

		// Format sizes.
		if ( 'size' === $args['format'] ) {
			$decimals = isset( $args['decimals'] ) ? $args['decimals'] : 2;
			return size_format( $raw_value, $decimals );
		}

		// Format dates.
		if ( 'date' === $args['format'] ) {
			$as = isset( $args['as'] ) ? $args['as'] : get_option( 'date_format' );
			return date_i18n( $as, strtotime( $raw_value ) );
		}

		// Format times.
		if ( 'time' === $args['format'] ) {
			$as = isset( $args['as'] ) ? $args['as'] : get_option( 'time_format' );
			return date_i18n( $as, strtotime( $raw_value ) );
		}

		// Format datetimes.
		if ( 'datetime' === $args['format'] ) {
			$as = isset( $args['as'] ) ? $args['as'] : get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
			return date_i18n( $as, strtotime( $raw_value ) );
		}

		// Format as a list.
		if ( 'list' === $args['format'] ) {
			$items = noptin_parse_list( noptin_clean( $raw_value ), true );
			return '<ul><li>' . implode( '</li><li>', $items ) . '</li></ul>';
		}

		// Format as a link.
		if ( 'link' === $args['format'] ) {
			$text = empty( $args['text'] ) ? $raw_value : $args['text'];
			return sprintf( '<a href="%s">%s</a>', esc_url( $raw_value ), esc_html( $text ) );
		}

		return apply_filters( 'noptin_objects_record_format_value', $raw_value, $args, $this );
	}

	/**
	 * Get record html to display.
	 *
	 * @param array $args
	 * @param int[] $records
	 * @param string|array $html_callback
	 * @return string
	 */
	public function get_record_ids_or_html( $records, $args, $html_callback ) {
		$limit = isset( $args['number'] ) ? intval( $args['number'] ) : 6;

		// Backward compatibility.
		if ( ! empty( $args['limit'] ) ) {
			$limit = absint( $args['limit'] );
		}

		// Get record ids.
		$records = array_unique( wp_parse_id_list( $records ) );
		if ( $limit > 0 && count( $records ) > $limit ) {
			$records = array_slice( $records, 0, $limit );
		}

		// Abort if no records.
		if ( empty( $records ) ) {
			return '';
		}

		// Check if we're returning ids.
		if ( ! empty( $args['return'] ) && 'ids' === $args['return'] ) {
			return $records;
		}

		return call_user_func( $html_callback, $records, $args );
	}

	/**
	 * Provides a related id.
	 *
	 * @param string $collection The collect.
	 */
	public function provide( $collection ) {
		return 0;
	}

	/**
	 * Prepares custom tab content.
	 *
	 */
	public function prepare_custom_tab() {
		return array();
	}
}
