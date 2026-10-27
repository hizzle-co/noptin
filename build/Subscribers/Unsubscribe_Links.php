<?php

namespace Hizzle\Noptin\Subscribers;

defined( 'ABSPATH' ) || exit;

/**
 * Configures and processes recipient-specific unsubscribe links for selected preferences.
 *
 * Custom links update tags, lists, and option-based subscriber segments while
 * leaving the default global unsubscribe link available.
 */
class Unsubscribe_Links {
	/**
	 * Registers settings, option sanitization, and custom unsubscribe page hooks.
	 *
	 * Runs in both admin and frontend requests so settings are available through
	 * REST and recipient links can use their own confirmation pages and redirects.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'noptin_page_settings', array( __CLASS__, 'settings' ) );
		add_filter( 'sanitize_option_noptin_options', array( __CLASS__, 'sanitize_settings' ) );
		add_action( 'noptin_pre_load_actions_page', array( __CLASS__, 'prepare_page' ), 20, 3 );
		add_filter( 'noptin_action_page_redirect', array( __CLASS__, 'redirect' ), 10, 2 );
	}

	/**
	 * Retrieves the subscriber segments that custom unsubscribe links may change.
	 *
	 * Uses the labels, choices, and multiple-value metadata from subscriber_fields(),
	 * excluding fields unsupported by the shared field manager. Choice keys retain
	 * their stored values, including zero, while empty keys are removed.
	 *
	 * @return array<string, array{label: string, multiple: bool, options: array<int|string, string>}>
	 *         Segment definitions keyed by their subscriber field names. Tags allow
	 *         multiple values and have no predefined options.
	 */
	public static function segments() {
		$segments = array_diff_key(
			Records::subscriber_fields(),
			array_flip( Fields_REST_API::get_skip_fields() )
		);
		foreach ( $segments as $key => $segment ) {
			$segments[ $key ]['options'] = array_filter(
				(array) $segment['options'],
				static function ( $value ) {
					return '' !== (string) $value;
				},
				ARRAY_FILTER_USE_KEY
			);
		}
		return $segments;
	}

	/**
	 * Retrieves the fallback HTML shown after a custom preference change succeeds.
	 *
	 * The heading and paragraph contain translated, HTML-escaped text. This
	 * message is separate from the default global unsubscribe confirmation.
	 *
	 * @return string Confirmation message containing an HTML heading and paragraph.
	 */
	public static function default_message() {
		return '<h1>' . esc_html__( 'Preferences updated', 'newsletter-optin-box' ) . '</h1><p>' . esc_html__( 'Your email preferences have been updated.', 'newsletter-optin-box' ) . '</p>';
	}

	/**
	 * Adds custom unsubscribe link controls to the action-page settings.
	 *
	 * Preserves the permanent global unsubscribe message and redirect controls.
	 * Places a standalone link repeater immediately after their settings group.
	 * Each link has a stable key, repeatable segment changes, a message, and a
	 * redirect. Tag values use existing-tag suggestions; other segments use their
	 * configured choices.
	 *
	 * @param array<string, array> $settings Action-page setting definitions, including
	 *                                      the pages_unsubscribe settings group.
	 * @return array<string, array> Setting definitions with the custom link repeater.
	 */
	public static function settings( $settings ) {
		$segments = self::segments();
		$fields   = array(
			'segment' => array(
				'el'               => 'select',
				'label'            => __( 'Field', 'newsletter-optin-box' ),
				'options'          => wp_list_pluck( $segments, 'label' ),
				'default'          => 'tags',
				'customAttributes' => array(
					'style' => array(
						'flex'     => '1 1 140px',
						'minWidth' => 0,
					),
				),
			),
			'action'  => array(
				'el'               => 'select',
				'label'            => __( 'Action', 'newsletter-optin-box' ),
				'options'          => array(
					'add'    => __( 'Add', 'newsletter-optin-box' ),
					'remove' => __( 'Remove', 'newsletter-optin-box' ),
				),
				'default'          => 'remove',
				'customAttributes' => array(
					'style' => array(
						'flex'     => '1 1 140px',
						'minWidth' => 0,
					),
				),
			),
		);
		foreach ( $segments as $key => $segment ) {
			// Separate value paths preserve input types when switching segments.
			$fields[ 'values.' . $key ] = array(
				'el'               => 'tags' === $key ? 'token' : 'select',
				'multiple'         => $segment['multiple'],
				'label'            => __( 'Value', 'newsletter-optin-box' ),
				'customAttributes' => array(
					'style' => array(
						'flex'     => '2 1 180px',
						'minWidth' => 0,
					),
				),
				'options'          => $segment['multiple'] ? self::select_options( $segment['options'] ) : array_merge(
					array(
						array(
							'value' => '',
							'label' => __( 'Select a value', 'newsletter-optin-box' ),
						),
					),
					self::select_options( $segment['options'] )
				),
				'conditions'       => array(
					array(
						'key'      => 'segment',
						'operator' => '==',
						'value'    => $key,
					),
				),
			);
			if ( 'tags' === $key ) {
				$fields[ 'values.' . $key ]['suggestions'] = get_noptin_subscriber_tags();
			}
		}
		$unsubscribe_settings = array_merge(
			array(
				'default_unsubscribe_link' => array(
					'el'      => 'paragraph',
					'content' => __( 'Unsubscribe from all emails: [[unsubscribe_url]]. This default link is always available.', 'newsletter-optin-box' ),
				),
			),
			$settings['pages_unsubscribe']['settings'],
			array(
				'custom_unsubscribe_links' => array(
					'el'               => 'repeater',
					'label'            => __( 'Additional unsubscribe links', 'newsletter-optin-box' ),
					'default'          => array(),
					'description'      => __( 'Change selected preferences without unsubscribing from all emails. Configure the corresponding recipient filters in your campaigns. Insert the merge tag shown on each link into your email.', 'newsletter-optin-box' ),
					'customAttributes' => array(
						'button'      => __( 'Add unsubscribe link', 'newsletter-optin-box' ),
						'cardProps'   => array( 'size' => 'medium' ),
						'repeaterKey' => array(
							'to'          => 'key',
							'display'     => '[[unsubscribe_url type="%s"]]',
							'collapsible' => false,
							'maxLength'   => 3,
						),
						'fields'      => array(
							'changes'  => array(
								'el'               => 'repeater',
								'label'            => __( 'Preference changes', 'newsletter-optin-box' ),
								'default'          => array(),
								'customAttributes' => array(
									'button'      => __( 'Add preference change', 'newsletter-optin-box' ),
									'defaultItem' => array(
										'segment' => 'tags',
										'action'  => 'remove',
									),
									'fields'      => array(
										'preference_change' => array(
											'el'       => 'horizontal',
											'settings' => $fields,
										),
									),
									'cardProps'   => array(
										'isBorderless' => true,
										'isRounded'    => false,
										'size'         => 'none',
										'borderTop'    => false,
										'borderBottom' => false,
										'borderLeft'   => false,
										'borderRight'  => false,
									),
								),
							),
							'message'  => array(
								'el'      => 'textarea',
								'label'   => __( 'Unsubscription Message', 'newsletter-optin-box' ),
								'default' => self::default_message(),
							),
							'redirect' => array(
								'el'          => 'input',
								'label'       => __( 'Unsubscription Redirect', 'newsletter-optin-box' ),
								'placeholder' => 'https://example.com/preferences-updated',
							),
						),
					),
				),
			)
		);
		// Keep the custom-link cards beside the permanent default card, in order.
		$settings['pages_unsubscribe']['settings'] = array_diff_key( $unsubscribe_settings, array( 'custom_unsubscribe_links' => true ) );
		$links                                     = $unsubscribe_settings['custom_unsubscribe_links'];
		$links['section']                          = 'messages';
		$result                                    = array();
		foreach ( $settings as $name => $setting ) {
			$result[ $name ] = $setting;
			if ( 'pages_unsubscribe' === $name ) {
				$result['custom_unsubscribe_links'] = $links;
			}
		}
		return $result;
	}

	/**
	 * Converts a segment's value-to-label map into select-control options.
	 *
	 * Casts stored option keys to strings so numeric choices retain their exact
	 * values when passed through the settings interface.
	 *
	 * @param array<int|string, string> $options Stored option values and display labels.
	 * @return array<int, array{value: string, label: string}> Select-control options.
	 */
	private static function select_options( $options ) {
		$result = array();
		foreach ( $options as $value => $label ) {
			$result[] = array(
				'value' => (string) $value,
				'label' => $label,
			);
		}
		return $result;
	}

	/**
	 * Sanitizes custom link keys, confirmation messages, and redirect URLs.
	 *
	 * Preserves valid unique keys across edits and reordering, generates new
	 * eight-character keys for missing or duplicate keys, and discards non-array
	 * rows. Configured preference changes are validated when a link is applied.
	 * Unrelated option values and inputs without custom links are left unchanged.
	 *
	 * @param mixed $settings Option value submitted for noptin_options.
	 * @return mixed Sanitized option array, or the original input when inapplicable.
	 */
	public static function sanitize_settings( $settings ) {
		if ( ! is_array( $settings ) || ! isset( $settings['custom_unsubscribe_links'] ) ) {
			return $settings;
		}
		$links = array();
		$keys  = array();
		foreach ( (array) $settings['custom_unsubscribe_links'] as $link ) {
			if ( ! is_array( $link ) ) {
				continue;
			}
			$key = isset( $link['key'] ) && is_string( $link['key'] ) ? sanitize_key( $link['key'] ) : '';
			while ( '' === $key || isset( $keys[ $key ] ) ) {
				$key = strtolower( wp_generate_password( 8, false, false ) );
			}
			$keys[ $key ]     = true;
			$link['key']      = $key;
			$link['message']  = wp_kses_post( is_string( $link['message'] ?? null ) ? $link['message'] : self::default_message() );
			$link['redirect'] = esc_url_raw( is_string( $link['redirect'] ?? null ) ? $link['redirect'] : '' );
			$links[]          = $link;
		}
		$settings['custom_unsubscribe_links'] = $links;
		return $settings;
	}

	/**
	 * Finds a saved custom unsubscribe link by its exact persisted key.
	 *
	 * Empty, non-string, unknown, and deleted keys do not resolve to the default
	 * global unsubscribe link.
	 *
	 * @param mixed $key Custom link key to look up.
	 * @return array|null Saved link configuration, or null when the key is unavailable.
	 */
	public static function get( $key ) {
		if ( ! is_string( $key ) || '' === $key ) {
			return null;
		}
		foreach ( (array) get_noptin_option( 'custom_unsubscribe_links', array() ) as $link ) {
			if ( is_array( $link ) && ( $link['key'] ?? null ) === $key ) {
				return $link;
			}
		}
		return null;
	}

	/**
	 * Generates an unsubscribe URL with an optional custom type bound to its recipient.
	 *
	 * Removes any existing custom type from the recipient before using the supplied
	 * attributes. An omitted or empty type produces the default global unsubscribe
	 * URL. A known custom type is included inside the encrypted recipient payload;
	 * an unavailable type returns an empty URL rather than a global unsubscribe.
	 *
	 * @param mixed $recipient Recipient data to encrypt, normally including an email
	 *                         address, subscriber ID, and optional campaign ID.
	 *                         Non-array inputs are treated as an empty recipient.
	 * @param array $attributes {
	 *     Optional unsubscribe_url merge-tag attributes.
	 *
	 *     @type string $type Saved custom link key. Empty selects global unsubscribe.
	 * }
	 * @return string Generated action URL, or an empty string for an unavailable type.
	 */
	public static function url( $recipient, $attributes = array() ) {
		$recipient = is_array( $recipient ) ? $recipient : array();
		unset( $recipient['unsubscribe_type'] );
		if ( isset( $attributes['type'] ) && '' !== $attributes['type'] ) {
			if ( ! self::get( $attributes['type'] ) ) {
				return '';
			}
			$recipient['unsubscribe_type'] = $attributes['type'];
		}
		return get_noptin_action_url( 'unsubscribe', noptin_encrypt( wp_json_encode( $recipient ) ) );
	}

	/**
	 * Prepares confirmation and result rendering for a custom unsubscribe request.
	 *
	 * Ignores other actions and untyped unsubscribe requests. Custom requests must
	 * reference an existing link and identify either a saved subscriber or a valid
	 * recipient email. Invalid requests terminate with an HTTP 400 response.
	 * This method does not create subscribers or apply preference changes.
	 *
	 * @param array        $recipient Recipient data decoded from the action link.
	 * @param string       $action    Requested action name.
	 * @param \Noptin_Page $page      Action-page router whose confirmation is configured.
	 * @return void
	 */
	public static function prepare_page( $recipient, $action, $page ) {
		if ( 'unsubscribe' !== $action || ! array_key_exists( 'unsubscribe_type', $recipient ) ) {
			return;
		}
		if ( ! self::get( $recipient['unsubscribe_type'] ) || ( ! Actions::get_subscriber() && ( ! is_string( $recipient['email'] ?? null ) || ! is_email( $recipient['email'] ) ) ) ) {
			wp_die( esc_html__( 'This preference link is no longer available. Your subscription has not been changed.', 'newsletter-optin-box' ), '', array( 'response' => 400 ) );
		}
		$page->add_confirmable_action( 'unsubscribe', __( 'Update email preferences', 'newsletter-optin-box' ), __( 'Please confirm the changes to your email preferences. Your subscription status will stay the same.', 'newsletter-optin-box' ) );
		add_action( 'noptin_page_unsubscribe', array( __CLASS__, 'display_message' ) );
	}

	/**
	 * Selects the custom link's redirect instead of the global unsubscribe redirect.
	 *
	 * Reads the custom type from the current email recipient. A custom link with
	 * no redirect returns an empty string so its confirmation message is rendered.
	 * Other actions and untyped unsubscribe requests retain the supplied redirect.
	 *
	 * @param int|string|false $redirect Existing action-page redirect URL or page ID,
	 *                                   or false when no redirect is configured.
	 * @param string           $action   Requested action name.
	 * @return int|string|false Custom redirect URL, an empty string, or the original value.
	 */
	public static function redirect( $redirect, $action ) {
		$recipient = \Hizzle\Noptin\Emails\Main::$current_email_recipient;
		if ( 'unsubscribe' === $action && isset( $recipient['unsubscribe_type'] ) ) {
			$link = self::get( $recipient['unsubscribe_type'] );
			return $link['redirect'] ?? '';
		}
		return $redirect;
	}

	/**
	 * Outputs the current custom link's confirmation message after processing.
	 *
	 * Uses the default preference message when the link has no custom message,
	 * resolves email merge tags through the action-page router, and restricts the
	 * resulting HTML to WordPress's allowed post markup.
	 *
	 * @return void
	 */
	public static function display_message() {
		$link    = self::get( \Hizzle\Noptin\Emails\Main::$current_email_recipient['unsubscribe_type'] ?? '' );
		$message = ! empty( $link['message'] ) ? $link['message'] : self::default_message();
		echo wp_kses_post( noptin()->actions_page->merge( $message ) );
	}

	/**
	 * Validates and applies a custom link's preference changes to its recipient.
	 *
	 * Validates every configured change before saving. Add and Remove rows are
	 * evaluated in order, preserving unrelated values. Existing subscriber statuses
	 * remain unchanged, and requests whose final values are unchanged do not save.
	 *
	 * When no saved subscriber is supplied, the email is used to find an existing
	 * record or prepare a new one. A new record is saved only when the link contains
	 * an Add action and its final updates retain a value. New records are pending
	 * and unconfirmed: initialization avoids the pending-status transition, and a
	 * recipient-scoped filter suppresses the built-in confirmation during creation.
	 * The temporary filter is removed even if saving fails. Remove-only and net-empty
	 * updates for unknown recipients succeed without creating a subscriber.
	 *
	 * Does not increment the global unsubscribe campaign counter. Validation and
	 * persistence failures are returned as errors instead of successful updates.
	 *
	 * @param Subscriber|null $subscriber Existing subscriber to update, or null when
	 *                                     the recipient must be resolved by email.
	 * @param string          $key        Persisted custom unsubscribe link key.
	 * @param string          $email      Email from the decoded recipient token.
	 *                                     Required when the supplied subscriber is
	 *                                     missing or does not exist. Default empty.
	 * @return true|\WP_Error True after saving or a valid no-op; an error for invalid
	 *                        configuration, recipient data, or a failed save.
	 */
	public static function apply( $subscriber, $key, $email = '' ) {
		$link = self::get( $key );

		if ( ! $link || empty( $link['changes'] ) || ! is_array( $link['changes'] ) ) {
			return new \WP_Error( 'invalid_preference_link', 'This preference link is not available. Your subscription has not been changed.' );
		}

		if ( ! $subscriber || ! $subscriber->exists() ) {
			if ( ! is_string( $email ) || ! is_email( $email ) ) {
				return new \WP_Error( 'invalid_preference_recipient', 'This preference link has no valid recipient.' );
			}

			// The encrypted link may identify a recipient who has no subscriber record yet.
			$subscriber = noptin_get_subscriber( $email );
			if ( ! $subscriber->exists() ) {
				$subscriber->set_email( $email );
			}
		}

		$creating        = ! $subscriber->exists();
		$segments        = self::segments();
		$operations      = array();
		$original_values = array();
		$has_add         = false;
		foreach ( $link['changes'] as $change ) {
			if ( ! is_array( $change ) ) {
				return new \WP_Error( 'invalid_preference_change', 'This link contains an invalid preference change.' );
			}

			$segment = $change['segment'] ?? '';
			$action  = $change['action'] ?? '';
			if ( ! is_string( $segment ) || ! isset( $segments[ $segment ] ) || ! in_array( $action, array( 'add', 'remove' ), true ) ) {
				return new \WP_Error( 'invalid_preference_change', 'A preference on this link is no longer available. Your subscription has not been changed.' );
			}

			if ( method_exists( $subscriber, 'has_prop' ) && ! $subscriber->has_prop( $segment ) ) {
				return new \WP_Error( 'invalid_preference_change', 'A field on this preference link is no longer available.' );
			}

			$value   = $change['values'][ $segment ] ?? null;
			$has_add = $has_add || 'add' === $action;
			if ( 'tags' === $segment ) {
				if ( ! is_array( $value ) || empty( $value ) || count( array_filter( $value, 'is_string' ) ) !== count( $value ) ) {
					return new \WP_Error( 'invalid_preference_value', 'This link has no valid tags configured.' );
				}

				$value = array_values( array_filter( array_map( 'sanitize_text_field', $value ), 'strlen' ) );
				if ( empty( $value ) ) {
					return new \WP_Error( 'invalid_preference_value', 'This link has no valid tags configured.' );
				}
			} else {
				// Keep previously saved scalar choices valid for multiple-value fields.
				$values = $segments[ $segment ]['multiple'] && is_array( $value ) ? $value : array( $value );
				if ( empty( $values ) ) {
					return new \WP_Error( 'invalid_preference_value', 'A value on this preference link is no longer available.' );
				}
				foreach ( $values as $choice ) {
					if ( ! is_scalar( $choice ) || ! array_key_exists( (string) $choice, $segments[ $segment ]['options'] ) ) {
						return new \WP_Error( 'invalid_preference_value', 'A value on this preference link is no longer available.' );
					}
				}
				$value = array_map( 'strval', array_values( $values ) );
			}
			$operations[] = array(
				'segment'  => $segment,
				'action'   => $action,
				'value'    => $segments[ $segment ]['multiple'] ? $value : $value[0],
				'multiple' => $segments[ $segment ]['multiple'],
			);
			$original                   = $subscriber->get( $segment );
			$original_values[ $segment ] = is_array( $original ) ? array_values( $original ) : $original;
		}

		try {
			// Let the datastore calculate the result before touching the live record.
			$preview = clone $subscriber;
			foreach ( $operations as $operation ) {
				self::set_preference( $preview, $operation );
			}
			$changed_fields = array();
			$has_values     = false;
			foreach ( $original_values as $field => $original ) {
				$final = $preview->get( $field );
				$final = is_array( $final ) ? array_values( $final ) : $final;
				if ( $final !== $original ) {
					$changed_fields[ $field ] = true;
				}
				$has_values = $has_values || ( is_array( $final ) ? ! empty( $final ) : '' !== (string) $final );
			}
			if ( ( $creating && ( ! $has_add || ! $has_values ) ) || ( ! $creating && empty( $changed_fields ) ) ) {
				return true;
			}
			if ( $creating ) {
				// Initialize pending status without firing a pending-status automation.
				$was_read = $subscriber->get_object_read();
				$subscriber->set_object_read( false );
				$subscriber->set_status( 'pending' );
				$subscriber->set_confirmed( false );
				$subscriber->set_object_read( $was_read );
			}
			foreach ( $operations as $operation ) {
				if ( isset( $changed_fields[ $operation['segment'] ] ) ) {
					self::set_preference( $subscriber, $operation );
				}
			}
			if ( $creating ) {
				$recipient_email       = $subscriber->get_email();
				$suppress_confirmation = static function ( $send, $record ) use ( $recipient_email ) {
					return 0 === strcasecmp( $record->get_email(), $recipient_email ) ? false : $send;
				};
				add_filter( 'noptin_should_send_double_optin_email', $suppress_confirmation, PHP_INT_MAX, 2 );
			}
			try {
				$saved = $subscriber->save();
			} finally {
				if ( $creating ) {
					remove_filter( 'noptin_should_send_double_optin_email', $suppress_confirmation, PHP_INT_MAX );
				}
			}
			if ( is_wp_error( $saved ) ) {
				return $saved;
			}
			if ( ! $saved ) {
				return new \WP_Error( 'preference_save_failed', __( 'Could not save your email preferences. Please try again.', 'newsletter-optin-box' ) );
			}
		} catch ( \Exception $exception ) {
			return new \WP_Error( 'preference_save_failed', $exception->getMessage() );
		}
		return true;
	}

	/**
	 * Applies one validated preference operation through the datastore setters.
	 *
	 * Multi-value fields use native ::add and ::remove operations. Scalar fields
	 * replace their value on Add and clear it only when a Remove value matches;
	 * the datastore suffixes do not implement removal for scalar properties.
	 *
	 * @param Subscriber $subscriber Record to update in memory; it is not saved here.
	 * @param array      $operation {
	 *     Validated preference change.
	 *
	 *     @type string          $segment  Subscriber field name.
	 *     @type string          $action   Either add or remove.
	 *     @type string|string[] $value    Scalar choice or multiple choices.
	 *     @type bool            $multiple Whether the field stores multiple values.
	 * }
	 * @return void
	 */
	private static function set_preference( $subscriber, $operation ) {
		$field = $operation['segment'];
		if ( $operation['multiple'] || 'add' === $operation['action'] ) {
			$subscriber->set( $field . '::' . $operation['action'], $operation['value'] );
		} elseif ( (string) $subscriber->get( $field ) === $operation['value'] ) {
			$subscriber->set( $field, '' );
		}
	}
}
