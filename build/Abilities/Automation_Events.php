<?php

namespace Hizzle\Noptin\Abilities;

use Hizzle\Noptin\Automation_Rules\Actions\Main as Actions;
use Hizzle\Noptin\Automation_Rules\Triggers\Main as Triggers;
use Hizzle\Noptin\Misc\Main as Metadata;

defined( 'ABSPATH' ) || exit;

/** Read-only discovery of locally registered Noptin automation events. */
class Automation_Events {
	/** Register the automation metadata tools on REST and MCP. */
	public static function register() {
		$kind = array(
			'type' => 'string',
			'enum' => array( 'trigger', 'action' ),
		);
		$id   = array(
			'type'        => 'string',
			'minLength'   => 1,
			'description' => 'Exact local registration ID.',
		);
		self::register_tool(
			'list-automation-events',
			'List automation triggers and actions',
			'Find Noptin automation rule events registered on this site. Use the returned kind and ID to look up event settings. Include locked events to inspect license requirements.',
			'list_events',
			array(
				'kind'           => $kind,
				'search'         => array(
					'type'        => 'string',
					'description' => 'Search IDs, labels, descriptions, categories and owners.',
				),
				'include_locked' => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
			array()
		);
		self::register_tool(
			'get-automation-event-settings',
			'Get automation event settings',
			'Get the settings, field definitions and configured choices for one available Noptin automation trigger or action.',
			'get_settings',
			array(
				'kind' => $kind,
				'id'   => $id,
			),
			array( 'kind', 'id' )
		);
		self::register_tool(
			'get-automation-merge-tags',
			'Get automation merge tags',
			'Get grouped merge tags for a Noptin automation trigger or a loop collection. For loops, use collection tags inside that loop body.',
			'get_merge_tags',
			array(
				'type' => array(
					'type' => 'string',
					'enum' => array( 'automation_trigger', 'collection' ),
				),
				'id'   => $id,
			),
			array( 'type', 'id' )
		);
	}

	/**
	 * Register a read-only tool gated by automation rule management permission.
	 *
	 * @param string $slug Ability slug.
	 * @param string $label Display label.
	 * @param string $description Discovery text.
	 * @param string $callback Class method.
	 * @param array  $properties Input properties.
	 * @param array  $required Required input names.
	 */
	private static function register_tool( $slug, $label, $description, $callback, $properties, $required ) {
		$input = array(
			'type'                 => 'object',
			'properties'           => $properties,
			'additionalProperties' => false,
		);
		if ( $required ) {
			$input['required'] = $required;
		} else {
			$input['default'] = array();
		}
		wp_register_ability(
			'noptin/' . $slug,
			array(
				'label'               => $label,
				'description'         => $description,
				'category'            => 'noptin',
				'input_schema'        => $input,
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( __CLASS__, $callback ),
				'permission_callback' => static function () {
					return current_user_can( get_noptin_collection_capability( 'automation_rules' ) );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array( 'public' => true ),
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);
	}

	/**
	 * Build metadata for the requested local registrations without loading their settings.
	 *
	 * @param string|null $only_kind Optional registry filter.
	 * @param string|null $only_id   Optional exact registration ID.
	 * @return array
	 */
	private static function events( $only_kind = null, $only_id = null ) {
		$integrations = array();
		foreach ( apply_filters( 'noptin_get_all_known_integrations', array() ) as $addon ) {
			$integrations[ self::owner_key( $addon['slug'] ) ] = $addon;
		}
		$connections = array_map( array( __CLASS__, 'owner_key' ), wp_list_pluck( \Noptin_COM::get_connections(), 'slug' ) );
		$events      = array(
			'trigger' => array(),
			'action'  => array(),
		);
		foreach ( array(
			'trigger' => Triggers::all(),
			'action'  => Actions::all(),
		) as $kind => $registry ) {
			if ( null !== $only_kind && $only_kind !== $kind ) {
				continue;
			}
			foreach ( $registry as $id => $event ) {
				if ( ( null !== $only_id && $only_id !== $id ) || ! empty( $event->depricated ) || ( 'action' === $kind && 'email' === $id ) ) {
					continue;
				}
				$integration = $event->integration ?? '';
				$connection  = $event->connection ?? '';
				$key         = self::owner_key( $integration );
				$addon       = $integrations[ $key ] ?? array();
				if ( ! $connection && ! $addon && in_array( $key, $connections, true ) ) {
					$connection  = $integration;
					$integration = '';
				}
				$events[ $kind ][ $id ] = array(
					'kind'        => $kind,
					'id'          => $id,
					'label'       => wp_strip_all_tags( $event->get_name() ),
					'description' => wp_strip_all_tags( $event->get_description() ),
					'category'    => $event->category,
					'integration' => $integration,
					'connection'  => $connection,
					'premium'     => ! empty( $connection ) || ! empty( $event->forcePremium )
						|| self::addon_premium( $addon, $kind . 's', $id ),
				);
			}
		}
		return $events;
	}

	/** Normalize an integration or connection slug for catalog matching. @param string $slug Owner slug. */
	private static function owner_key( $slug ) {
		return strtolower( str_replace( array( '-', '_' ), '', $slug ) );
	}

	/** Resolve owner and event-specific plan flags from the integration catalog. */
	private static function addon_premium( $addon, $registry, $id ) {
		if ( 'premium' === ( $addon['plan'] ?? '' ) ) {
			return true;
		}
		foreach ( $addon[ $registry ] ?? array() as $group ) {
			foreach ( $group as $entry ) {
				if ( $id === $entry['id'] && ! empty( $entry['premium'] ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/** Check verified site activation and expiration using the license service's cached details. */
	private static function has_license() {
		$license = \Noptin_COM::get_active_license_key( true );
		return ! is_wp_error( $license ) && ! empty( $license->is_active )
			&& ! empty( $license->is_active_on_site ) && empty( $license->has_expired );
	}

	/** List usable local registrations, optionally including Premium events. @param array $input Discovery filters. */
	public static function list_events( $input = array() ) {
		$results = array();
		$license = null;
		foreach ( self::events( $input['kind'] ?? null ) as $events ) {
			foreach ( $events as $event ) {
				if ( isset( $input['search'] ) && false === stripos( implode( ' ', $event ), $input['search'] ) ) {
					continue;
				}
				if ( $event['premium'] && null === $license ) {
					$license = self::has_license();
				}
				$event['available'] = ! $event['premium'] || $license;
				if ( ! $event['available'] ) {
					if ( empty( $input['include_locked'] ) ) {
						continue;
					}
					$event['reason'] = 'Requires an active Noptin Premium license on this site.';
				}
				$results[] = $event;
			}
		}
		return array( 'events' => $results );
	}

	/** Check a concrete, discoverable local registration before returning its metadata. */
	private static function check_event( $kind, $id ) {
		$event = self::events( $kind, $id )[ $kind ][ $id ] ?? null;
		if ( ! $event ) {
			return new \WP_Error( 'noptin_unknown_event', 'This automation event is unavailable on this site.', array( 'status' => 404 ) );
		}
		if ( $event['premium'] && ! self::has_license() ) {
			return new \WP_Error( 'noptin_premium_event', 'This automation event requires an active Noptin Premium license.', array( 'status' => 403 ) );
		}
		return true;
	}

	/** Return the same event settings the automation editor uses. @param array $input Event kind and ID. */
	public static function get_settings( $input ) {
		$kind = $input['kind'];
		$id   = $input['id'];
		$can  = self::check_event( $kind, $id );
		if ( is_wp_error( $can ) ) {
			return $can;
		}
		$request = new \WP_REST_Request();
		$request->set_param( $kind . '_id', $id );
		$response = Metadata::trigger_action_config( $request )->get_data();
		return array(
			'kind' => $kind,
			'id'   => $id,
		) + (array) $response[ $kind . 's' ]->{$id};
	}

	/** Return normalized trigger tags or the collection tags available in a loop body. @param array $input Tag source type and ID. */
	public static function get_merge_tags( $input ) {
		$can = 'collection' === $input['type'] ? self::check_event( 'action', 'loop' ) : self::check_event( 'trigger', $input['id'] );
		if ( is_wp_error( $can ) ) {
			return $can;
		}
		$request = new \WP_REST_Request();
		$request->set_param( 'type', $input['type'] );
		$request->set_param( 'id', $input['id'] );
		$response = Metadata::get_merge_tags( $request );
		return is_wp_error( $response ) ? $response : array( 'merge_tags' => $response->get_data() );
	}
}
