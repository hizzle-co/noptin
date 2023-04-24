<?php

namespace Hizzle\Store;

/**
 * This class handles webhooks.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Webhooks.
 */
class Webhooks {

	/**
	 * Webhook events.
	 *
	 * @var array
	 */
	protected $events = array();

	/**
	 * Hook prefix.
	 *
	 * @var string
	 */
	protected $hook_prefix;

	/**
	 * Loads the class.
	 *
	 * @param Store $store Data store.
	 */
	public function __construct( $store ) {

		$this->hook_prefix = $store->hook_prefix();

		foreach ( $store->get_collections() as $collection ) {

			// Ignore events that are not associated with any CRUD class.
			if ( empty( $collection->object ) ) {
				continue;
			}

			// CRUD Events.
			$this->events[ $collection->hook_prefix( 'created', true ) ] = $collection->get_singular_name() . '.created';
			$this->events[ $collection->hook_prefix( 'updated', true ) ] = $collection->get_singular_name() . '.updated';
			$this->events[ $collection->hook_prefix( 'deleted', true ) ] = $collection->get_singular_name() . '.deleted';

		}

		// For custom events, make sure you pass an object the implements the get_data() method to the hook callback.
		$this->events = apply_filters( $store->hook_prefix( 'webhook_events' ), $this->events );

		// Attach event handlers.
		foreach ( array_keys( $this->events ) as $event ) {
			add_action( $event, array( $this, 'handle_event' ) );
		}

	}

	/**
	 * Handles webhook events.
	 *
	 * @param Record $record The record.
	 */
	public function handle_event( $record ) {
		$hook_name = current_action();

		// Only handle events for this plugin.
		if ( ! isset( $this->events[ $hook_name ] ) || ! is_object( $record ) || ! is_callable( array( $record, 'get_data' ) ) ) {
			return;
		}

		foreach ( $this->get_endpoints() as $endpoint ) {

			// Ensure we have a URL.
			if ( ! isset( $endpoint['url'] ) ) {
				continue;
			}

			$url    = esc_url_raw( $endpoint['url'] );
			$secret = isset( $endpoint['secret'] ) ? $endpoint['secret'] : '';
			$events = isset( $endpoint['events'] ) ? $endpoint['events'] : '';

			// Confirm that this event is supported.
			if ( is_array( $events ) && ! in_array( $this->events[ $hook_name ], $events, true ) ) {
				continue;
			}

			// Deliver the webhook.
			wp_remote_post(
				$url,
				array(
					'body'     => wp_json_encode(
						apply_filters(
							$this->hook_prefix . 'webhook_data',
							array(
								'event'       => $this->events[ $hook_name ],
								'secret'      => $secret,
								'date'        => gmdate( 'c' ),
								'object_type' => strtok( $this->events[ $hook_name ], '.' ),
								'data'        => $record->get_data(),
							),
							$record
						)
					),
					'timeout'  => 10,
					'headers'  => array(
						'Content-Type' => 'application/json',
					),
					'blocking' => false,
				)
			);

		}

	}

	/**
	 * Retrieves valid webhook endpoints.
	 *
	 * @return array
	 */
	public function get_endpoints() {
		return apply_filters( $this->hook_prefix . 'webhook_endpoints', array() );
	}

	/**
	 * Retrieves webhook events.
	 *
	 * @return array
	 */
	public function get_events() {
		return array_values( $this->events );
	}

	/**
	 * Casts to string.
	 *
	 * @return string
	 */
	public function __toString() {
		return implode( ', ', $this->get_events() );
	}

}
