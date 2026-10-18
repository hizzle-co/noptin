<?php
/** Shared add-on discovery and current-site registration data. */
namespace Hizzle\Noptin\Extensions;

defined( 'ABSPATH' ) || exit;

class Catalog {
	/** Register read-only routes; installation and activation keep their own permission checks. */
	public static function init() {
		add_action(
			'rest_api_init',
			static function () {
				register_rest_route(
					'noptin/v1',
					'/addons',
					array(
						'methods'             => 'GET',
						'callback'            => array( __CLASS__, 'get_state' ),
						'permission_callback' => array( __CLASS__, 'can_read' ),
					)
				);
			}
		);
	}

	/** Allow discovery to people who can use the relevant Noptin screens. */
	public static function can_read() {
		if ( current_user_can_manage_noptin() || current_user_can( 'manage_noptin_automation_rules' ) || current_user_can( 'manage_noptin_campaigns' ) ) {
			return true;
		}

		foreach ( \Hizzle\Noptin\Emails\Main::get_email_types() as $type ) {
			if ( current_user_can_manage_noptin_campaign_type( $type->type ) ) {
				return true;
			}
		}

		return false;
	}

	/** Current recipient sources, shared by campaign pickers and AI. */
	public static function senders() {
		return array_merge(
			array(
				'manual_recipients' => array(
					'label'        => __( 'Specific People', 'newsletter-optin-box' ),
					'description'  => __( 'Enter one or more email addresses manually, separated by commas.', 'newsletter-optin-box' ),
					'image'        => array(
						'icon' => 'businessperson',
						'fill' => '#212121',
					),
					'is_active'    => true,
					'is_installed' => true,
					'is_local'     => true,
					'settings'     => array(
						'disableMergeTags' => false,
						'fields'           => array(
							'recipients' => array(
								'label'       => __( 'Recipient(s)', 'newsletter-optin-box' ),
								'description' => sprintf(
									'%s<br /> <br />%s',
									__( 'Enter recipients (comma-separated) for this email.', 'newsletter-optin-box' ),
									sprintf(
										/* translators: %s: code */
										__( 'Add %s after an email to disable send, open and click tracking for that recipient.', 'newsletter-optin-box' ),
										'<code>--notracking</code>'
									)
								),
								'type'        => 'text',
								'placeholder' => sprintf(
									/* translators: %s: The Example */
									__( 'For example, %s', 'newsletter-optin-box' ),
									'[[email]], ' . get_option( 'admin_email' ) . ' --notracking'
								),
							),
						),
					),
				),
			),
			get_noptin_email_senders( true )
		);
	}

	/**
	 * Retrieves a list of installed plugins.
	 *
	 * @return array
	 */
	public static function get_installed_plugins() {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = array();

		foreach ( get_plugins() as $filename => $data ) {
			// Skip if does not have a directory.
			if ( false === strpos( $filename, '/' ) ) {
				continue;
			}

			$plugins[ basename( dirname( $filename ) ) ] = array(
				'file_name' => $filename,
				'name'      => $data['Name'],
				'isActive'  => is_plugin_active( $filename ),
			);
		}

		return $plugins;
	}

	/**
	 * Get CRM connections.
	 *
	 * @return array
	 */
	public static function get_crm_connections() {
		return array_map(
			function ( $connection ) {
				$installed = noptin()->integrations->integrations[ $connection->slug ]
					?? noptin()->integrations->integrations[ str_replace( '-', '_', $connection->slug ) ]
					?? null;
				$connected = null;

				if ( $installed && is_callable( array( $installed, 'is_connected' ) ) ) {
					try {
						$connected = (bool) $installed->is_connected();
					} catch ( \Throwable $error ) {
						$connected = false;
					}
				}

				return array(
					'isConnected' => $connected,
					'kind'        => 'connection',
					'slug'        => $connection->slug,
					'name'        => $connection->name,
					'description' => sprintf( /* translators: %s Integration such as Mailchimp */ __( 'Connect Noptin to %s', 'newsletter-optin-box' ), $connection->name ),
					'icon'        => $connection->image_url ?? '',
					'plugin_url'  => $connection->connect_url ?? '',
				);
			},
			\Noptin_COM::get_connections()
		);
	}

	/**
	 * Get detected integrations.
	 *
	 * @return array
	 */
	public static function get_detected_integrations() {
		return array_map(
			function ( $integration ) {
				$requirements = array();
				foreach ( array( 'triggers', 'actions' ) as $kind ) {
					foreach ( $integration[ $kind ] ?? array() as $events ) {
						foreach ( $events as $event ) {
							foreach ( $event['requires'] ?? array() as $type => $name ) {
								$requirements[ $type . ':' . $name ] = 'class' === $type ? class_exists( $name ) : ( 'function' === $type ? function_exists( $name ) : ( 'constant' === $type && defined( $name ) ) );
							}
						}
					}
				}
				return array(
					'requirements' => $requirements,
					'kind'        => 'integration',
					'slug'        => $integration['slug'],
					'name'        => $integration['label'],
					'description' => $integration['description'] ?? '',
					'icon'        => $integration['icon_url'] ?? '',
					'plugin_url'  => $integration['url'] ?? '',
					'plan'        => $integration['plan'] ?? '',
				);
			},
			apply_filters( 'noptin_get_all_known_integrations', array() )
		);
	}

	/**
	 * Build the shared boot/refresh contract for every admin application.
	 *
	 * Screens localize this array under config and keep navigation/editor data separate.
	 *
	 * @return array{home_url: string, ajaxUrl: string, updatesNonce: string, license_key: string|false,
	 *     plugins: array, addons: array, integrations: array, connections: array, triggers: array, actions: array,
	 *     emailTypes: array, senders: array, canInstall: bool, canActivate: bool}
	 */
	public static function config() {
		$fields = \Hizzle\Noptin\Settings\Menu::prepare_settings();

		$with_settings = static function ( $addons ) use ( $fields ) {
			foreach ( $addons as &$addon ) {
				$integration = noptin()->integrations_new->integrations[ $addon['slug'] ]
					?? noptin()->integrations->integrations[ $addon['slug'] ]
					?? noptin()->integrations->integrations[ str_replace( '-', '_', $addon['slug'] ) ]
					?? null;
				$slug = str_replace( '-', '_', $integration->slug ?? $addon['slug'] );
				$section = 'addons-pack' === $addon['slug'] ? 'coupon_codes' : 'integrations';
				$settings = $fields[ $section ]['sub_sections']['main']['settings'] ?? array();
				if ( 'integrations' === $section ) {
					$settings = $settings[ 'settings_section_' . $slug ]['settings'] ?? array();
				}
				// Catalog placeholders for uninstalled add-ons are not settings forms.
				// Webhooks only exposes debug logging and needs no setup dialog.
				$addon['hasSettings'] = 'webhooks' !== $slug && ! empty( $settings ) && ! isset( $settings[ 'noptin_' . $slug . '_install' ] );
				$addon['settingsPath'] = $addon['hasSettings']
					? $section . '.main' . ( 'integrations' === $section ? '.settings_section_' . $slug : '' )
					: null;
			}
			return array_values( $addons );
		};

		$email_types = array();
		foreach ( \Hizzle\Noptin\Emails\Main::get_email_types() as $type ) {
			if ( current_user_can_manage_noptin_campaign_type( $type->type ) ) {
				$email_types[ $type->type ] = $type->to_array();
			}
		}

		return array(
			'emailTypes'   => $email_types,
			'senders'      => self::senders(),
			'home_url'     => home_url(),
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'updatesNonce' => wp_create_nonce( 'updates' ),
			'license_key'  => \Noptin_COM::get_active_license_key(),
			'plugins'      => self::get_installed_plugins(),
			'addons'       => $with_settings( array( array(
				'slug' => 'addons-pack',
				'plan' => 'premium',
				'name' => __( 'Addons Pack', 'newsletter-optin-box' ),
				'description' => __( 'Enable email sequences/courses, coupon codes, and more premium features.', 'newsletter-optin-box' ),
				'icon' => array( 'icon' => 'email', 'fill' => '#e91e63' ),
				'plugin_url' => 'https://noptin.com/ultimate-addons-pack/',
			) ) ),
			'integrations' => $with_settings( self::get_detected_integrations() ),
			'connections'  => $with_settings( self::get_crm_connections() ),
			'triggers'     => self::events( \Hizzle\Noptin\Automation_Rules\Triggers\Main::all() ),
			'actions'      => self::events( \Hizzle\Noptin\Automation_Rules\Actions\Main::all() ),
			'canInstall'   => current_user_can( 'install_plugins' ),
			'canActivate'  => current_user_can( 'activate_plugins' ),
		);
	}

	/** Return newly registered events after activation, rather than relying on boot-time snapshots. */
	public static function get_state() {
		$state = self::config();

		// Never send a saved key again during refresh: the client's license provider owns its current state.
		unset( $state['license_key'] );

		return rest_ensure_response( $state );
	}

	/**
	 * Slims down a settings schema for AI consumption.
	 * Removes noise keys and omits defaults (type=text, el=input).
	 *
	 * @param array $settings Raw settings schema.
	 * @return array
	 */
	private static function slim_settings_for_ai( $settings ) {
		$result = array();

		foreach ( $settings as $key => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			// Recurse into grouped fields.
			if ( isset( $field['fields'] ) && is_array( $field['fields'] ) ) {
				$children = self::slim_settings_for_ai( $field['fields'] );
				if ( ! empty( $children ) ) {
					$result[ $key ] = $children;
				}
				continue;
			}

			$slim = array();

			$label = $field['label'] ?? $key;

			if ( ! empty( $field['description'] ) ) {
				$label .= ' (' . $field['description'] . ')';
			}

			if ( ! empty( $label ) ) {
				$slim['label'] = $label;
			}

			// type: only include if not the default 'text'.
			if ( ! empty( $field['type'] ) && 'text' !== $field['type'] ) {
				$slim['type'] = $field['type'];
			}

			// el: only include if not the default 'input'.
			if ( ! empty( $field['el'] ) && 'input' !== $field['el'] ) {
				$slim['el'] = $field['el'];
			}

			// options.
			if ( ! empty( $field['options'] ) && is_array( $field['options'] ) ) {
				$slim['options'] = $field['options'];
			}

			// default.
			if ( isset( $field['default'] ) && '' !== $field['default'] ) {
				$slim['default'] = $field['default'];
			}

			if ( ! empty( $slim ) ) {
				$result[ $key ] = $slim;
			}
		}

		return $result;
	}

	/**
	 * Serialize local registrations with explicit ownership, including boot-time editor data.
	 *
	 * @param \Hizzle\Noptin\Automation_Rules\Trigger_Action[] $events
	 * @return array Event definitions keyed by runtime ID; owner slugs use dashes, or null for core events.
	 */
	public static function events( $events ) {
		$result = array();
		foreach ( $events as $event ) {
			if ( ! empty( $event->depricated ) ) {
				continue;
			}

			$result[ $event->get_id() ] = array(
				'name'                => $event->get_id(),
				'label'               => $event->get_name(),
				'description'         => $event->get_description(),
				'image'               => $event->get_image(),
				'category'            => $event->category,
				'integration'         => empty( $event->integration ) ? null : str_replace( '_', '-', $event->integration ),
				'connection'          => empty( $event->connection ) ? null : str_replace( '_', '-', $event->connection ),
				'settings'            => self::slim_settings_for_ai( $event->get_settings() ),
				'has_wizard_settings' => $event->has_wizard_settings(),
			);
		}
		return $result;
	}
}
