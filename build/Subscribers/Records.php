<?php

namespace Hizzle\Noptin\Subscribers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for a Noptin subscribers.
 *
 * @since 3.0.0
 */
class Records extends \Hizzle\Noptin\Objects\People {

	/**
	 * @var string the record class.
	 */
	public $record_class = '\Hizzle\Noptin\Subscribers\Record';

	/**
	 * @var string integration.
	 */
	public $integration = 'noptin';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function __construct() {
		$this->label                = __( 'Noptin Subscribers', 'newsletter-optin-box' );
		$this->singular_label       = __( 'Subscriber', 'newsletter-optin-box' );
		$this->type                 = 'subscriber';
		$this->email_sender         = 'noptin';
		$this->email_sender_options = 'noptin_subscriber_options';
		$this->is_stand_alone       = false;
		$this->can_list             = true;
		$this->icon                 = array(
			'icon' => 'admin-users',
			'fill' => '#50575e',
		);

		// State transition.
		foreach ( array_keys( $this->subscriber_states() ) as $state ) {
			add_action( $state, array( $this, 'subscriber_state_changed' ), 11, 2 );
		}

		// Custom fields.
		foreach ( $this->subscriber_fields( true ) as $merge_tag => $field ) {
			if ( empty( $field['multiple'] ) ) {
				add_action( "noptin_subscriber_{$merge_tag}_changed", array( $this, 'on_field_change' ), 10, 3 );
			} else {
				add_action( "noptin_subscriber_added_to_{$merge_tag}", array( $this, 'on_field_add' ), 10, 2 );
				add_action( "noptin_subscriber_removed_from_{$merge_tag}", array( $this, 'on_field_add' ), 10, 2 );
			}
		}

		add_action( 'log_noptin_subscriber_campaign_open', array( $this, 'on_open' ), 10, 2 );
		add_action( 'log_noptin_subscriber_campaign_click', array( $this, 'on_click' ), 10, 3 );

		parent::__construct();
	}

	private function subscriber_states() {
		$statuses = array(
			'noptin_subscriber_created'      => __( 'Created', 'newsletter-optin-box' ),
			'noptin_subscriber_saved'        => __( 'Saved', 'newsletter-optin-box' ),
			'noptin_subscribers_import_item' => __( 'Imported', 'newsletter-optin-box' ),
		);

		foreach ( noptin_get_subscriber_statuses() as $status => $label ) {
			if ( 'pending' === $status ) {
				$label = __( 'Pending email confirmation', 'newsletter-optin-box' );
			}

			$statuses[ 'noptin_subscriber_status_set_to_' . $status ] = $label;
		}

		return $statuses;
	}

	public static function get_all_tags_as_options() {
		static $tags = null;

		if ( null === $tags ) {
			$tags = noptin()->db()->get_all_meta_by_key( 'tags' );
		}

		return array_combine( $tags, $tags );
	}

	private function subscriber_fields( $partial = false ) {
		$fields = array(
			'tags' => array(
				'label'    => __( 'Tags', 'newsletter-optin-box' ),
				'multiple' => true,
				'options'  => null,
			),
		);

		// Loop through all props.
		foreach ( get_noptin_subscriber_filters() as $merge_tag => $options ) {

			// Skip if no options.
			if ( empty( $options['options'] ) || in_array( $merge_tag, array( 'confirmed', 'status', 'source' ), true ) ) {
				continue;
			}

			$fields[ $merge_tag ] = array(
				'label'    => $options['label'],
				'multiple' => ! empty( $options['is_multiple'] ),
				'options'  => $partial ? array() : noptin_newslines_to_array( $options['options'] ),
			);
		}

		return $fields;
	}

	/**
	 * Returns a list of available triggers.
	 *
	 * @return array $triggers The triggers.
	 */
	public function get_triggers() {

		$triggers = array();

		// Statuses.
		foreach ( $this->subscriber_states() as $state => $label ) {
			$triggers[ $state ] = array(
				'label'       => sprintf(
					'%s > %s',
					$this->singular_label,
					$label
				),
				'description' => sprintf(
					/* translators: %s: Object type label and new state */
					__( 'When a %1$s is %2$s', 'newsletter-optin-box' ),
					strtolower( $this->singular_label ),
					'noptin_subscriber_saved' === $state ? __( 'created or updated', 'newsletter-optin-box' ) : strtolower( $label )
				),
				'subject'     => 'subscriber',
			);

			if ( 'noptin_subscriber_status_set_to_pending' === $state ) {
				$triggers[ $state ]['mail_config'] = array(
					'object_type' => $this->object_type,
					'label'       => ucwords(
						sprintf(
							/* translators: %s: Object type label. */
							__( 'Confirm new %s', 'newsletter-optin-box' ),
							$this->singular_label
						)
					),
				);
			}

			if ( 'noptin_subscriber_status_set_to_subscribed' === $state ) {
				$triggers[ $state ]['mail_config'] = array(
					'object_type' => $this->object_type,
					'label'       => ucwords(
						sprintf(
							/* translators: %s: Object type label. */
							__( 'Welcome new %s', 'newsletter-optin-box' ),
							$this->singular_label
						)
					),
				);
			}

			if ( 'noptin_subscriber_status_set_to_unsubscribed' === $state ) {
				$triggers[ $state ]['previous_name'] = 'unsubscribe';
			}

			// Check if the hook name contains a status change.
			if ( false !== strpos( $state, 'noptin_subscriber_status_set_to_' ) ) {
				$triggers[ $state ]['description'] = sprintf(
					/* translators: %s: new state */
					__( "When a subscriber's status is set to %1\$s", 'newsletter-optin-box' ),
					strtolower( $label )
				);

				$triggers[ $state ]['extra_args'] = array(
					'previous_status' => array(
						'label'       => __( 'Previous status', 'newsletter-optin-box' ),
						'description' => __( 'The previous subscriber status.', 'newsletter-optin-box' ),
						'type'        => 'string',
						'options'     => array_merge(
							array( 'new' => __( 'New', 'newsletter-optin-box' ) ),
							noptin_get_subscriber_statuses()
						),
					),
				);
			}

			if ( 'noptin_subscribers_import_item' === $state ) {
				$triggers[ $state ]['previous_name'] = 'import_subscriber';
				$triggers[ $state ]['extra_args']    = array(
					'action' => array(
						'label'   => __( 'Action', 'newsletter-optin-box' ),
						'type'    => 'string',
						'options' => array(
							'updated' => __( 'Updated', 'newsletter-optin-box' ),
							'created' => __( 'Created', 'newsletter-optin-box' ),
						),
					),
				);
			}
		}

		// Custom fields.
		foreach ( $this->subscriber_fields() as $merge_tag => $field ) {
			if ( empty( $field['multiple'] ) ) {
				$triggers[ "noptin_subscriber_{$merge_tag}_changed" ] = array(
					'label'         => sprintf(
						// translators: %1$s: Object type label, %2$s: Field label.
						__( '%1$s > %2$s changed', 'newsletter-optin-box' ),
						$this->singular_label,
						$field['label']
					),
					'previous_name' => $merge_tag . '_changed',
					'description'   => sprintf(
						/* translators: %s: field label */
						__( 'When %s changes', 'newsletter-optin-box' ),
						strtolower( $field['label'] )
					),
					'subject'       => 'subscriber',
					'extra_args'    => array(
						'new_value' => array(
							'label'      => __( 'New value', 'newsletter-optin-box' ),
							'type'       => 'string',
							'deprecated' => 'new_value',
							'group'      => $field['label'],
							'options'    => $field['options'],
						),
						'old_value' => array(
							'label'      => __( 'Old value', 'newsletter-optin-box' ),
							'type'       => 'string',
							'deprecated' => 'old_value',
							'group'      => $field['label'],
							'options'    => $field['options'],
						),
					),
					'icon'          => array(
						'icon' => 'editor-table',
						'fill' => '#008000',
					),
				);
			} else {
				$triggers[ "noptin_subscriber_added_to_{$merge_tag}" ] = array(
					'label'         => sprintf(
						// translators: %1$s: Object type label, %2$s: Field label.
						__( '%1$s > Add to %2$s', 'newsletter-optin-box' ),
						$this->singular_label,
						$field['label']
					),
					'previous_name' => 'add_to_' . $merge_tag,
					'description'   => sprintf(
						/* translators: %s: field label */
						__( 'When a %1$s is added to %2$s', 'newsletter-optin-box' ),
						strtolower( $this->singular_label ),
						strtolower( $field['label'] )
					),
					'subject'       => 'subscriber',
					'extra_args'    => array(
						'field_value' => array(
							'label'      => __( 'New value', 'newsletter-optin-box' ),
							'type'       => 'string',
							'deprecated' => 'field_value',
							'group'      => $field['label'],
							'options'    => $field['options'],
						),
					),
					'icon'          => array(
						'icon' => 'category',
						'fill' => '#008000',
					),
				);

				$triggers[ "noptin_subscriber_removed_from_{$merge_tag}" ] = array(
					'label'         => sprintf(
						// translators: %1$s: Object type label, %2$s: Field label.
						__( '%1$s > Removed from %2$s', 'newsletter-optin-box' ),
						$this->singular_label,
						$field['label']
					),
					'previous_name' => 'remove_from_' . $merge_tag,
					'description'   => sprintf(
						/* translators: %s: field label */
						__( 'When a %1$s is removed from %2$s', 'newsletter-optin-box' ),
						strtolower( $this->label ),
						strtolower( $field['label'] )
					),
					'subject'       => 'subscriber',
					'extra_args'    => array(
						'field_value' => array(
							'label'      => __( 'The removed value', 'newsletter-optin-box' ),
							'type'       => 'string',
							'deprecated' => 'field_value',
							'group'      => $field['label'],
							'options'    => $field['options'],
						),
					),
					'icon'          => array(
						'icon' => 'category',
						'fill' => '#008000',
					),
				);
			}
		}

		return array_merge(
			parent::get_triggers(),
			$triggers,
			array(
				'open_email' => array(
					'label'       => sprintf(
						/* translators: %s: Object type label. */
						__( '%s > Open Email', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description' => __( 'When a subscriber opens an email campaign', 'newsletter-optin-box' ),
					'subject'     => 'subscriber',
					'provides'    => array( 'noptin-campaign' ),
					'icon'        => array(
						'icon' => 'email',
						'fill' => '#008000',
					),
				),
				'link_click' => array(
					'label'       => sprintf(
						/* translators: %s: Object type label. */
						__( '%s > Link Click', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'When a %s clicks on a link in an email', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'subject'     => 'subscriber',
					'provides'    => array( 'noptin-campaign' ),
					'extra_args'  => array(
						'url' => array(
							'label'      => __( 'Clicked URL', 'newsletter-optin-box' ),
							'type'       => 'string',
							'deprecated' => 'url',
						),
					),
					'icon'        => array(
						'icon' => 'email',
						'fill' => '#008000',
					),
				),
			)
		);
	}

	/**
	 * Fired when a subscriber state changes.
	 *
	 * @param \Hizzle\Noptin\DB\Subscriber $subscriber The subscriber.
	 * @param string|mixed The previous value.
	 */
	public function subscriber_state_changed( $subscriber, $from = null ) {

		if ( is_numeric( $subscriber ) ) {
			$subscriber = noptin_get_subscriber( $subscriber );
		}

		if ( empty( $subscriber ) || ! is_a( $subscriber, '\Hizzle\Noptin\DB\Subscriber' ) ) {
			return;
		}

		// Check that the current action is a valid trigger.
		$hook = current_filter();

		if ( ! in_array( $hook, array_keys( $this->subscriber_states() ), true ) ) {
			return;
		}

		$args = array(
			'email'      => $subscriber->get_email(),
			'object_id'  => $subscriber->get_id(),
			'subject_id' => $subscriber->get_id(),
		);

		// Check if the hook name contains a status change.
		if ( false !== strpos( $hook, 'noptin_subscriber_status_set_to_' ) ) {
			$args['unserialize'] = array(
				'subscriber.status' => $subscriber->get_status(),
			);

			$args['activity'] = sprintf(
				/* translators: %s: New status. */
				__( 'Status set to %1$s', 'newsletter-optin-box' ),
				$subscriber->get_status()
			);

			$args['extra_args'] = array(
				'subscriber.previous_status' => 'new',
			);

			if ( is_string( $from ) && ! empty( $from ) ) {
				$args['extra_args'] = array(
					'subscriber.previous_status' => $from,
				);
			}
		}

		if ( 'noptin_subscribers_import_item' === $hook ) {
			$args['extra_args'] = array(
				'subscriber.action' => $from,
			);
		}

		$this->trigger(
			$hook,
			$args
		);
	}

	/**
	 * Fired when a subscriber field changes.
	 *
	 * @param \Hizzle\Noptin\DB\Subscriber $subscriber The subscriber.
	 * @param string|mixed $from The previous value.
	 * @param string|mixed $to The new value.
	 */
	public function on_field_change( $subscriber, $from, $to ) {

		if ( ! noptin_has_active_license_key() || empty( $subscriber ) || ! is_a( $subscriber, '\Hizzle\Noptin\DB\Subscriber' ) ) {
			return;
		}

		$this->trigger(
			current_filter(),
			array(
				'email'      => $subscriber->get_email(),
				'object_id'  => $subscriber->get_id(),
				'subject_id' => $subscriber->get_id(),
				'extra_args' => array(
					'subscriber.old_value' => $from,
					'subscriber.new_value' => $to,
				),
			)
		);
	}

	/**
	 * Fired when a subscriber is added to a field.
	 *
	 * @param \Hizzle\Noptin\DB\Subscriber $subscriber The subscriber.
	 * @param string|mixed $value The new value.
	 */
	public function on_field_add( $subscriber, $value ) {

		if ( ! noptin_has_active_license_key() || empty( $subscriber ) || ! is_a( $subscriber, '\Hizzle\Noptin\DB\Subscriber' ) ) {
			return;
		}

		$this->trigger(
			current_filter(),
			array(
				'email'      => $subscriber->get_email(),
				'object_id'  => $subscriber->get_id(),
				'subject_id' => $subscriber->get_id(),
				'extra_args' => array(
					'subscriber.field_value' => $value,
				),
			)
		);
	}

	/**
	* Fired when a subscriber opens an email campaign.
	*
	* @param \Hizzle\Noptin\DB\Subscriber $subscriber The subscriber.
	* @param $campaign_id The campaign that was opened.
	*/
	public function on_open( $subscriber, $campaign_id ) {

		$subscriber = noptin_get_subscriber( $subscriber );
		if ( empty( $subscriber ) || ! is_a( $subscriber, '\Hizzle\Noptin\DB\Subscriber' ) ) {
			return;
		}

		$args = array(
			'email'      => $subscriber->get_email(),
			'object_id'  => $subscriber->get_id(),
			'subject_id' => $subscriber->get_id(),
			'provides'   => array(
				'noptin-campaign' => $campaign_id,
			),
		);

		$this->trigger( 'open_email', $args );
	}

	/**
	* Fired when a subscriber clicks on a link in an email campaign.
	*
	* @param \Hizzle\Noptin\DB\Subscriber $subscriber The subscriber.
	* @param $campaign_id The campaign that was opened.
	* @param $url The url that was clicked.
	*/
	public function on_click( $subscriber, $campaign_id, $url ) {

		$subscriber = noptin_get_subscriber( $subscriber );
		if ( empty( $subscriber ) || ! is_a( $subscriber, '\Hizzle\Noptin\DB\Subscriber' ) ) {
			return;
		}

		$args = array(
			'email'      => $subscriber->get_email(),
			'object_id'  => $subscriber->get_id(),
			'subject_id' => $subscriber->get_id(),
			'provides'   => array(
				'noptin-campaign' => $campaign_id,
			),
			'extra_args' => array(
				'subscriber.url' => $url,
			),
		);

		$this->trigger( 'open_email', $args );
	}

	/**
	 * Retrieves several subscribers.
	 *
	 * @param array $filters The available filters.
	 * @return int[] $subscribers The subscriber IDs.
	 */
	public function get_all( $filters ) {
		return noptin_get_subscribers(
			array_merge(
				$filters,
				array(
					'fields' => 'id',
				)
			)
		);
	}

	/**
	 * Retrieves newsletter recipients.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $email
	 * @return int[] $subscribers The subscriber IDs.
	 */
	public function get_newsletter_recipients( $options, $email ) {
		// Prepare arguments.
		$args = array(
			'status'     => 'subscribed',
			'number'     => -1,
			'fields'     => 'id',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => '_campaign_' . $email->id,
					'compare' => 'NOT EXISTS',
				),
			),
		);

		$manual_recipients = $email->get_manual_recipients_ids();
		if ( ! empty( $manual_recipients ) ) {
			$args['include'] = $manual_recipients;
		} elseif ( noptin_has_active_license_key() ) {

			if ( is_array( $options ) ) {

				// Backward compatibility.
				if ( ! empty( $options['_subscriber_via'] ) ) {
					if ( ! isset( $options['source'] ) ) {
						$args['source'] = $options['_subscriber_via'];
					}
					unset( $options['_subscriber_via'] );
				}

				// Loop through available filters.
				$filters = array_merge(
					array_keys( get_noptin_subscriber_filters() ),
					array( 'tags' )
				);

				foreach ( $filters as $filter ) {

					// Filter by key.
					$filtered = isset( $options[ $filter ] ) ? $options[ $filter ] : '';

					if ( '' !== $filtered && array() !== $filtered ) {
						$args[ $filter ] = $filtered;
					}

					// Exclude by key.
					$filtered = isset( $options[ $filter . '_not' ] ) ? $options[ $filter . '_not' ] : '';

					if ( '' !== $filtered && array() !== $filtered ) {
						$args[ $filter . '_not' ] = $filtered;
					}
				}
			}

			// (Backwards compatibility) Subscription source.
			$source = $email->get( '_subscriber_via' );

			if ( '' !== $source && empty( $options['source'] ) ) {
				$args['source'] = $source;
			}

			// Allow other plugins to filter the query.
			$args = apply_filters( 'noptin_mass_mailer_subscriber_query', $args, $email );
		}

		// Run the query...
		return noptin_get_subscribers( $args );
	}

	/**
	 * Get the sender settings.
	 *
	 * @return array
	 */
	public function get_sender_settings() {
		$fields = array();

		foreach ( get_noptin_subscriber_filters() as $key => $filter ) {

			// Skip status since emails can only be sent to active subscribers.
			if ( 'status' === $key ) {
				continue;
			}

			$multiple = 2 < count( $filter['options'] );
			$options  = $filter['options'];

			$fields[ $key ] = array(
				'label'                => $filter['label'],
				'type'                 => 'select',
				'placeholder'          => __( 'Any', 'newsletter-optin-box' ),
				'canSelectPlaceholder' => true,
				'options'              => $options,
				'description'          => ( empty( $filter['description'] ) || $filter['label'] === $filter['description'] ) ? '' : $filter['description'],
			);

			if ( $multiple || $filter['is_multiple'] ) {

				$fields[ $key ]['placeholder'] = __( 'Optional. Leave blank to send to all', 'newsletter-optin-box' );
				$fields[ $key ]['multiple']    = 'true';

				$fields[ $key . '_not' ] = array_merge(
					$fields[ $key ],
					array(
						'label'       => sprintf(
							// translators: %s is the filter label, e.g, "Tags".
							__( '%s - Exclude', 'newsletter-optin-box' ),
							$filter['label']
						),
						'description' => '',
					)
				);
			}
		}

		$all_tags = array();

		if ( is_callable( array( noptin()->db(), 'get_all_meta_by_key' ) ) ) {
			$all_tags = noptin()->db()->get_all_meta_by_key( 'tags' );
		}

		$fields['tags'] = array(
			'label'       => __( 'Tags', 'newsletter-optin-box' ),
			'type'        => 'token',
			'description' => __( 'Optional. Filter recipients by their tags.', 'newsletter-optin-box' ),
			'suggestions' => $all_tags,
		);

		$fields['tags_not'] = array(
			'label'       => __( 'Tags - Exclude', 'newsletter-optin-box' ),
			'type'        => 'token',
			'description' => __( 'Optional. Exclude recipients by their tags.', 'newsletter-optin-box' ),
			'suggestions' => $all_tags,
		);

		return apply_filters( 'noptin_subscriber_sending_options', $fields );
	}

	/**
	 * Retrieves a single person from a WordPress user.
	 *
	 * @param \WP_User $user The user.
	 * @return Record $person The person.
	 */
	public function get_from_user( $user ) {
		return new Record( $user );
	}

	/**
	 * Retrieves a single person from an email address.
	 *
	 * @param string $email The email address.
	 * @return Record $person The person.
	 */
	public function get_from_email( $email ) {
		return new Record( $email );
	}

	/**
	 * Retrieves the manual recipients.
	 */
	public function get_manual_recipients() {
		return array(
			$this->field_to_merge_tag( 'email' ) => $this->singular_label,
		);
	}

	/**
	 * Retrieves available fields.
	 *
	 */
	public function get_fields() {

		$fields  = array();
		$buttons = array(
			'manage_preferences_url'   => __( 'Manage your preferences', 'newsletter-optin-box' ),
			'confirm_subscription_url' => __( 'Confirm your subscription', 'newsletter-optin-box' ),
			'unsubscribe_url'          => __( 'Unsubscribe', 'newsletter-optin-box' ),
			'resubscribe_url'          => __( 'Resubscribe', 'newsletter-optin-box' ),
		);

		foreach ( get_noptin_subscriber_smart_tags() as $smart_tag => $field ) {
			$prepared = array(
				'label'        => $field['label'],
				'description'  => $field['description'],
				'type'         => $field['conditional_logic'],
				'deprecated'   => $smart_tag,
				'show_in_meta' => true,
				'required'     => 'email' === $smart_tag,
			);

			if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
				$prepared['options'] = $field['options'];
			}

			if ( isset( $buttons[ $smart_tag ] ) ) {
				$prepared['block'] = array(
					'title'       => $field['label'],
					'description' => sprintf(
						/* translators: %s: Object link destination. */
						__( 'Displays a button link to %s', 'newsletter-optin-box' ),
						strtolower( $field['description'] )
					),
					'icon'        => 'admin-links',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'defaults'    => array(
						'text' => $buttons[ $smart_tag ],
						'url'  => $this->field_to_merge_tag( $smart_tag ),
					),
					'element'     => 'button',
				);
			}

			$fields[ $smart_tag ] = $prepared;
		}

		$fields['meta'] = $this->meta_key_tag_config();

		// Add provided fields.
		$fields = $this->add_provided( $fields );

		return $fields;
	}

	/**
	 * Retrieves a test object args.
	 *
	 * @since 3.0.0
	 * @param \Hizzle\Noptin\DB\Automation_Rule $rule
	 * @throws \Exception
	 * @return array
	 */
	public function get_test_args( $rule ) {

		$subscriber_id = $this->get_test_id();

		if ( empty( $subscriber_id ) ) {
			throw new \Exception( 'No subscriber found.' );
		}

		$args = array(
			'object_id'  => $subscriber_id,
			'subject_id' => $subscriber_id,
		);

		if ( 'noptin_subscribers_import_item' === $rule->get_trigger_id() ) {
			$args['extra_args'] = array(
				'subscriber.action' => 'created',
			);
		}

		if ( 'open_email' === $rule->get_trigger_id() || 'link_click' === $rule->get_trigger_id() ) {
			$campaign_id = get_posts(
				array(
					'post_type'   => 'noptin-campaign',
					'numberposts' => 1,
					'fields'      => 'ids',
				)
			);

			if ( ! empty( $campaign_id ) ) {
				$args['provides'] = array(
					'noptin-campaign' => $campaign_id[0],
				);
			} else {
				throw new \Exception( 'No campaign found.' );
			}

			if ( 'link_click' === $rule->get_trigger_id() ) {
				$args['extra_args'] = array(
					'subscriber.url' => 'https://example.com',
				);
			}
		}

		return $args;
	}

	/**
	 * Retrieves a test ID.
	 *
	 */
	public function get_test_id() {
		$subscriber = get_current_noptin_subscriber_id();

		if ( ! empty( $subscriber ) ) {
			return $subscriber;
		}

		return (int) current(
			noptin_get_subscribers(
				array(
					'number' => 1,
					'fields' => 'id',
				)
			)
		);
	}

	/**
	 * Returns a list of available (actions).
	 *
	 * @return array $actions The actions.
	 */
	public function get_actions() {
		$actions = array_merge(
			parent::get_actions(),
			array(
				'subscribe'         => array(
					'id'             => 'subscribe',
					'label'          => sprintf(
						/* translators: %s: Object type label. */
						__( '%s > Create or Update', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description'    => sprintf(
						/* translators: %s: Object type label. */
						__( 'Create or update a %s', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'callback'       => 'add_noptin_subscriber',
					'extra_settings' => array(
						'update_existing' => array(
							'label'   => __( 'Update existing subscribers', 'newsletter-optin-box' ),
							'el'      => 'input',
							'type'    => 'checkbox',
							'default' => true,
						),
					),
					'action_fields'  => array_keys( get_editable_noptin_subscriber_fields() ),
				),
				'delete_subscriber' => array(
					'id'             => 'delete_subscriber',
					'label'          => sprintf(
						/* translators: %s: Object type label. */
						__( '%s > Delete', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description'    => sprintf(
						/* translators: %s: Object type label. */
						__( 'Delete a %s', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'callback'       => 'delete_noptin_subscriber',
					'extra_settings' => array(
						'email' => array(
							'label'    => __( 'Subscriber ID or email address', 'newsletter-optin-box' ),
							'type'     => 'string',
							'default'  => '[[email]]',
							'required' => true,
						),
					),
				),
				'unsubscribe'       => array(
					'id'             => 'unsubscribe',
					'label'          => sprintf(
						/* translators: %s: Object type label. */
						__( '%s > Unsubscribe', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description'    => sprintf(
						/* translators: %s: Object type label. */
						__( 'Unsubscribe a %s', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'callback'       => 'unsubscribe_noptin_subscriber',
					'extra_settings' => array(
						'email' => array(
							'label'    => __( 'Subscriber ID or email address', 'newsletter-optin-box' ),
							'type'     => 'string',
							'default'  => '[[email]]',
							'required' => true,
						),
					),
				),
				'custom-field'      => array(
					'id'             => 'custom-field',
					'label'          => sprintf(
						/* translators: %s: Object type label. */
						__( '%s > Update Custom Field', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description'    => sprintf(
						/* translators: %s: Object type label. */
						__( 'Update a %s field', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'callback'       => __CLASS__ . '::update_subscriber_field',
					'extra_settings' => array(
						'email'       => array(
							'label'    => __( 'Subscriber ID or email address', 'newsletter-optin-box' ),
							'type'     => 'string',
							'default'  => '[[email]]',
							'required' => true,
						),
						'field_name'  => array(
							'el'          => 'select',
							'label'       => __( 'Custom Field', 'newsletter-optin-box' ),
							'description' => __( 'Select the custom field to update', 'newsletter-optin-box' ),
							'placeholder' => __( 'Select Field', 'newsletter-optin-box' ),
							'options'     => wp_list_pluck( get_editable_noptin_subscriber_fields(), 'label' ),
							'required'    => true,
						),
						'field_value' => array(
							'type'         => 'string',
							'label'        => __( 'Field Value', 'newsletter-optin-box' ),
							'description'  => __( 'Enter a value to assign the field', 'newsletter-optin-box' ),
							'show_in_meta' => true,
						),
					),
				),
			)
		);

		// Custom fields.
		if ( ! class_exists( '\Noptin\Addons_Pack\Custom_Fields\Main' ) ) {
			foreach ( $this->subscriber_fields() as $merge_tag => $field ) {

				if ( 'confirmed' === $merge_tag ) {
					$field['label'] = __( 'Email confirmation status', 'newsletter-optin-box' );
				}

				if ( empty( $field['multiple'] ) ) {
					$actions[ "change_{$merge_tag}" ] = array(
						'label'          => sprintf(
							// translators: %1$s: Object type label, %2$s: Field label.
							__( '%1$s > Update %2$s', 'newsletter-optin-box' ),
							$this->singular_label,
							$field['label']
						),
						'description'    => sprintf(
							/* translators: %s: field label */
							__( 'Updates %s', 'newsletter-optin-box' ),
							strtolower( $field['label'] )
						),
						'icon'           => array(
							'icon' => 'editor-table',
							'fill' => '#008000',
						),
						'callback'       => __CLASS__ . '::update_subscriber_field',
						'extra_settings' => array(
							'email'    => array(
								'label'    => __( 'Subscriber ID or email address', 'newsletter-optin-box' ),
								'type'     => 'string',
								'default'  => '[[email]]',
								'required' => true,
							),
							$merge_tag => array(
								'el'       => 'select',
								'label'    => $field['label'],
								'options'  => $field['options'],
								'required' => true,
								'default'  => '',
							),
						),
					);
				} else {
					$actions[ "add_to_{$merge_tag}" ] = array(
						'label'          => sprintf(
							// translators: %1$s: Object type label, %2$s: Field label.
							__( '%1$s > Add to %2$s', 'newsletter-optin-box' ),
							$this->singular_label,
							$field['label']
						),
						'description'    => sprintf(
							/* translators: %s: field label */
							__( 'Adds the subscriber to %s', 'newsletter-optin-box' ),
							strtolower( $field['label'] )
						),
						'icon'           => array(
							'icon' => 'category',
							'fill' => '#008000',
						),
						'callback'       => __CLASS__ . '::add_to_subscriber_field',
						'extra_settings' => array(
							'email'    => array(
								'label'    => __( 'Subscriber ID or email address', 'newsletter-optin-box' ),
								'type'     => 'string',
								'default'  => '[[email]]',
								'required' => true,
							),
							$merge_tag => array(
								'el'       => 'tags' === $merge_tag ? 'input' : 'multi_checkbox_alt',
								'label'    => $field['label'],
								'options'  => $field['options'],
								'required' => true,
								'default'  => array(),
							),
						),
					);

					$actions[ "remove_from_{$merge_tag}" ] = array(
						'label'       => sprintf(
							// translators: %1$s: Object type label, %2$s: Field label.
							__( '%1$s > Remove from %2$s', 'newsletter-optin-box' ),
							$this->singular_label,
							$field['label']
						),
						'description' => sprintf(
							/* translators: %s: field label */
							__( 'Removes the subscriber from %s', 'newsletter-optin-box' ),
							strtolower( $field['label'] )
						),
						'icon'        => array(
							'icon' => 'category',
							'fill' => '#008000',
						),
						'callback'       => __CLASS__ . '::remove_from_subscriber_field',
						'extra_settings' => array(
							'email'    => array(
								'label'    => __( 'Subscriber ID or email address', 'newsletter-optin-box' ),
								'type'     => 'string',
								'default'  => '[[email]]',
								'required' => true,
							),
							$merge_tag => array(
								'el'       => 'tags' === $merge_tag ? 'input' : 'multi_checkbox_alt',
								'label'    => $field['label'],
								'options'  => $field['options'],
								'required' => true,
								'default'  => array(),
							),
						),
					);
				}
			}
		}

		return $actions;
	}

	/**
	 * Processes a subscriber action.
	 *
	 * @param array $args
	 */
	public static function update_subscriber_field( $args, $action_id = 'custom-field' ) {

		if ( empty( $args['email'] ) ) {
			return new \WP_Error( 'noptin_invalid_email', 'Invalid email address or subscriber ID.' );
		}

		$action_args = array();

		if ( 'custom-field' === $action_id ) {
			$action_args = array(
				$args['field_name'] => isset( $args['field_value'] ) ? $args['field_value'] : '',
			);
		}

		if ( 0 === strpos( $action_id, 'change_' ) ) {
			$field_name  = str_replace( 'change_', '', $action_id );
			$action_args = array(
				$field_name => isset( $args[ $field_name ] ) ? $args[ $field_name ] : '',
			);
		}

		return update_noptin_subscriber(
			$args['email'],
			$action_args
		);
	}

	/**
	 * Processes a subscriber action.
	 *
	 * @param array $args
	 */
	public static function add_to_subscriber_field( $args, $action_id ) {

		if ( empty( $args['email'] ) ) {
			return new \WP_Error( 'noptin_invalid_email', 'Invalid email address or subscriber ID.' );
		}

		$subscriber = noptin_get_subscriber( $args['email'] );

		if ( ! $subscriber->exists() ) {
			return new \WP_Error( 'noptin_subscriber_not_found', 'Subscriber not found.' );
		}

		$field_name  = str_replace( 'add_to_', '', $action_id );
		$field_value = noptin_parse_list( isset( $args[ $field_name ] ) ? $args[ $field_name ] : array(), true );
		$existing    = noptin_parse_list( $subscriber->get( $field_name, array() ), true );

		$subscriber->set( $field_name, array_unique( array_merge( $existing, $field_value ) ) );
		$subscriber->save();
	}

	/**
	 * Processes a subscriber action.
	 *
	 * @param array $args
	 */
	public static function remove_from_subscriber_field( $args, $action_id ) {

		if ( empty( $args['email'] ) ) {
			return new \WP_Error( 'noptin_invalid_email', 'Invalid email address or subscriber ID.' );
		}

		$subscriber = noptin_get_subscriber( $args['email'] );

		if ( ! $subscriber->exists() ) {
			return new \WP_Error( 'noptin_subscriber_not_found', 'Subscriber not found.' );
		}

		$field_name  = str_replace( 'remove_from_', '', $action_id );
		$field_value = noptin_parse_list( isset( $args[ $field_name ] ) ? $args[ $field_name ] : array(), true );
		$existing    = noptin_parse_list( $subscriber->get( $field_name, array() ), true );

		$subscriber->set( $field_name, array_unique( array_diff( $existing, $field_value ) ) );
		$subscriber->save();
	}
}
