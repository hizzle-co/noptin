<?php

namespace Hizzle\Store;

/**
 * Wrapper for PHP DateTime which adds support for gmt/utc offset when a
 * timezone is absent
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Datetime class.
 */
class Date_Time extends \DateTime {

	/**
	 * Output an ISO 8601 date string in local (WordPress) timezone.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function __toString() {
		return $this->format( DATE_ATOM );
	}

	/**
	 * Get the timestamp with the WordPress timezone offset added or subtracted.
	 *
	 * @since  1.0.0
	 * @return int
	 */
	public function getOffsetTimestamp() {
		return $this->getTimestamp() + $this->getOffset();
	}

	/**
	 * Format a date based on the UTC timestamp.
	 *
	 * @since  1.0.0
	 * @param  string $format Date format.
	 * @return string
	 */
	public function utc( $format = 'Y-m-d H:i:s' ) {
		return gmdate( $format, $this->getTimestamp() );
	}

	/**
	 * Format a date based on the offset timestamp.
	 *
	 * @since  1.0.0
	 * @param  string $format Date format.
	 * @return string
	 */
	public function date( $format = 'Y-m-d H:i:s' ) {
		return gmdate( $format, $this->getOffsetTimestamp() );
	}

	/**
	 * Return a localised date based on offset timestamp. Wrapper for date_i18n function.
	 *
	 * @since  1.0.0
	 * @param  string $format Date format.
	 * @param  bool   $gmt    Whether to use GMT timezone.
	 * @return string
	 */
	public function date_i18n( $format = null, $gmt = false ) {

		if ( empty( $format ) ) {
			$format = get_option( 'date_format', 'F j, Y' );
		}

		$timestamp = $gmt ? $this->getTimestamp() : $this->getOffsetTimestamp();
		return date_i18n( $format, $timestamp );
	}

	/**
	 * Formats a date for display or storage.
	 *
	 * @since  1.0.0
	 * @param  string $context Either view, db or raw.
	 * @return string
	 */
	public function context( $context = 'view' ) {

		if ( 'view' === $context ) {

			// If the time is midnight, return just the date.
			if ( '00:00:00' === $this->format( 'H:i:s' ) ) {
				return $this->date_i18n( 'F j, Y' );
			}

			return $this->date_i18n( 'F j, Y @ g:i a' );
		}

		if ( 'view_day' === $context ) {
			return $this->date_i18n( 'F j, Y' );
		}

		if ( 'db' === $context ) {
			return $this->date();
		}

		return $this->__toString();
	}

}
