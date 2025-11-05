<?php

/**
 * Privacy data exporter for Noptin subscribers.
 *
 * @since   3.4.6
 * @package Noptin
 */

namespace Hizzle\Noptin\Subscribers;

defined( 'ABSPATH' ) || exit;

/**
 * Privacy class.
 */
class Privacy {

	/**
	 * Constructor.
	 */
	public static function init() {
		// Register the privacy exporter.
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ) );

		// Register the privacy eraser.
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ) );
	}

	/**
	 * Registers the privacy exporter.
	 *
	 * @param array $exporters The registered exporters.
	 * @return array
	 */
	public static function register_exporter( $exporters ) {
		$exporters['noptin'] = array(
			'exporter_friendly_name' => 'Noptin',
			'callback'               => array( __CLASS__, 'export_subscriber_data' ),
		);

		return $exporters;
	}

	/**
	 * Exports subscriber data.
	 *
	 * @param string $email_address The email address to export data for.
	 * @return array
	 */
	public static function export_subscriber_data( $email_address ) {
		$subscriber = noptin_get_subscriber( $email_address );

		if ( ! $subscriber->exists() ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$data = array();

		foreach ( get_noptin_subscriber_smart_tags() as $smart_tag => $field ) {
			$value = $subscriber->get( $smart_tag );

			if ( is_bool( $value ) ) {
				$value = $value ? __( 'Yes', 'newsletter-optin-box' ) : __( 'No', 'newsletter-optin-box' );
			}

			if ( empty( $value ) ) {
				continue;
			}

			if ( is_string( $value ) && ! empty( $field['options'] ) ) {
				$value = $field['options'][ $value ] ?? $value;
			}

			if ( is_array( $value ) ) {
				if ( ! empty( $field['options'] ) ) {
					$value = array_map(
						function ( $option ) use ( $field ) {
							return $field['options'][ $option ] ?? $option;
						},
						$value
					);
				}

				$value = implode( ', ', $value );
			}

			// Handle objects that have a __toString method
			if ( is_object( $value ) && method_exists( $value, '__toString' ) ) {
				$value = (string) $value;
			}

			if ( ! is_scalar( $value ) ) {
				continue;
			}

			$data[] = array(
				'name'  => empty( $field['label'] ) ? $smart_tag : $field['label'],
				'value' => (string) $value,
			);
		}

		return array(
			'data' => array(
				array(
					'group_id'    => 'noptin',
					'group_label' => 'Noptin',
					'item_id'     => 'subscriber-' . $subscriber->get_id(),
					'data'        => $data,
				),
			),
			'done' => true,
		);
	}

	/**
	 * Registers the privacy eraser.
	 *
	 * @param array $erasers The registered erasers.
	 * @return array
	 */
	public static function register_eraser( $erasers ) {
		$erasers['noptin'] = array(
			'eraser_friendly_name' => 'Noptin',
			'callback'             => array( __CLASS__, 'erase_subscriber_data' ),
		);

		return $erasers;
	}

	/**
	 * Erases subscriber data.
	 *
	 * @param string $email_address The email address to erase data for.
	 * @return array
	 */
	public static function erase_subscriber_data( $email_address ) {
		$subscriber = noptin_get_subscriber( $email_address );

		// Delete email logs.
		noptin()->db()->delete_where( array( 'email' => $email_address ), 'email_logs' );

		if ( ! $subscriber->exists() ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		// Delete the subscriber.
		$subscriber->delete();

		return array(
			'items_removed'  => true,
			'items_retained' => false,
			'messages'       => array( 'Newsletter subscription deleted.' ),
			'done'           => true,
		);
	}
}
