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
		$this->label          = __( 'Noptin Subscribers', 'newsletter-optin-box' );
		$this->singular_label = __( 'Subscriber', 'newsletter-optin-box' );
		$this->type           = 'subscriber';
		$this->email_sender   = 'noptin';
		$this->can_list       = false;
		$this->icon           = array(
			'icon' => 'email',
			'fill' => '#008000',
		);

		// State transition.
		foreach ( array_keys( $this->subscriber_states() ) as $state ) {
			add_action( $state, array( $this, 'subscriber_state_changed' ), 11, 2 );
		}

		add_action( 'log_noptin_subscriber_campaign_open', array( $this, 'on_open' ), 10, 2 );
		add_action( 'log_noptin_subscriber_campaign_click', array( $this, 'on_click' ), 10, 3 );

		parent::__construct();
	}

	private function subscriber_states() {
		$statuses = array(
			'noptin_subscriber_created'      => __( 'Created', 'newsletter-optin-box' ),
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

	/**
	 * Returns a list of available triggers.
	 *
	 * @return array $triggers The triggers.
	 */
	public function get_triggers() {

		$triggers = array();

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
					strtolower( $label )
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

			// Check if the hook name contains a status change.
			if ( false !== strpos( $state, 'noptin_subscriber_status_set_to_' ) ) {
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
				$triggers[ $state ]['extra_args'] = array(
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
							'label' => __( 'Clicked URL', 'newsletter-optin-box' ),
							'type'  => 'string',
						),
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
	 * Retrieves available fields.
	 *
	 */
	public function get_fields() {

		$fields = array();
		foreach ( get_noptin_subscriber_smart_tags() as $smart_tag => $field ) {

			$prepared = array(
				'label'       => $field['label'],
				'description' => $field['description'],
				'type'        => $field['conditional_logic'],
				'deprecated'  => $smart_tag,
			);

			if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
				$prepared['options'] = $field['options'];
			}

			$fields[ $smart_tag ] = $prepared;
		}

		$fields['meta'] = $this->meta_key_tag_config();

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
}
