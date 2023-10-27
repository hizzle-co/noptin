<?php

namespace Hizzle\Noptin\Objects;

/**
 * Collection of records.
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Base object type.
 */
abstract class Collection {

	/**
	 * @var string the record class.
	 */
	public $record_class = '\Hizzle\Noptin\Objects\Record';

	/**
	 * @var string object type.
	 */
	public $object_type;

	/**
	 * @var string type.
	 */
	public $type;

	/**
	 * @var string prefix.
	 */
	public $smart_tags_prefix = null;

	/**
	 * @var string label.
	 */
	public $label;

	/**
	 * @var string label.
	 */
	public $singular_label;

	/**
	 * @var string integration.
	 */
	public $integration;

	/**
	 * Class constructor.
	 */
	public function __construct() {

		// Load automation rule.
		if ( did_action( 'noptin_automation_rules_load' ) ) {
			$this->load_automation_rules( noptin()->automation_rules );
		} else {
			add_action( 'noptin_automation_rules_load', array( $this, 'load_automation_rules' ) );
		}

		// Set automation rule smart tags prefix.
		if ( is_null( $this->smart_tags_prefix ) ) {
			$this->smart_tags_prefix = $this->type;
		}
	}

	/**
	 * Loads the automation rule triggers and actions.
	 *
	 * @param \Noptin_Automation_Rules $rules The automation rules instance.
	 */
	public function load_automation_rules( $rules ) {

		// Register triggers.
		foreach ( $this->get_all_triggers() as $key => $args ) {

			$args['provides'] = empty( $args['provides'] ) ? array() : noptin_parse_list( $args['provides'] );
			$args['provides'] = array_merge( $args['provides'], array( 'current_user' ) );

			if ( empty( $args['subject'] ) ) {
				$args['subject'] = 'current_user';
			}

			$rules->add_trigger(
				new Trigger( $key, $args, $this )
			);
		}

		// Register actions.
		foreach ( $this->get_all_actions() as $key => $args ) {
			$rules->add_action(
				new Action( $this->type, $key, $args )
			);
		}

	}

	/**
	 * Returns a list of available triggers.
	 *
	 * @return array $triggers The triggers.
	 */
	public function get_triggers() {
		return array();
	}

	/**
	 * Retrieves all filtered triggers.
	 *
	 */
	public function get_all_triggers() {
		return $this->filter( $this->get_triggers(), 'triggers' );
	}

	/**
	 * Triggers actions.
	 *
	 * @param string $trigger The trigger name.
	 * @param array $args The trigger args.
	 */
	public function trigger( $trigger, $args ) {

		$args['provides'] = empty( $args['provides'] ) ? array() : $args['provides'];

		if ( empty( $args['provides']['current_user'] ) ) {
			$args['provides']['current_user'] = get_current_user_id();
		}

		if ( ! isset( $args['subject_id'] ) ) {
			$user               = wp_get_current_user();
			$args['subject_id'] = ( isset( $user->ID ) ? (int) $user->ID : 0 );

			if ( empty( $args['email'] ) ) {
				$args['email'] = $user->user_email;
			}
		}

		do_action( 'noptin_fire_object_trigger_' . $trigger, $args );
	}

	/**
	 * Returns a list of available actions.
	 *
	 * @return array $actions The actions.
	 */
	public function get_actions() {
		return array();
	}

	/**
	 * Retrieves all filtered actions.
	 *
	 */
	public function get_all_actions() {
		return $this->filter( $this->get_actions(), 'actions' );
	}

	/**
	 * Retrieves available fields.
	 *
	 */
	abstract public function get_fields();

	/**
	 * Retrieves all filtered fields.
	 *
	 */
	public function get_all_fields() {
		$fields = $this->get_fields();

		// Maybe add newsletter subscription status.
		if ( 'person' === $this->object_type ) {
			$fields['newsletter'] = array(
				'label'   => __( 'Newsletter subscription status', 'newsletter-optin-box' ),
				'type'    => 'string',
				'options' => array(
					'yes' => __( 'subscribed', 'newsletter-optin-box' ),
					'no'  => __( 'unsubscribed', 'newsletter-optin-box' ),
				),
				'example' => "format='label'",
			);

			$fields['avatar_url'] = array(
				'label' => __( 'Avatar URL', 'newsletter-optin-box' ),
				'type'  => 'string',
			);
		}

		return $this->filter( $fields, 'fields' );
	}

	/**
	 * Filters the provided value.
	 *
	 */
	protected function filter( $value, $type ) {
		$value = apply_filters(
			"noptin_object_{$type}_{$this->type}",
			$value,
			$this
		);

		return apply_filters(
			"noptin_object_type_{$type}_{$this->object_type}",
			$value,
			$this
		);
	}

	/**
	 * Retrieves several items.
	 *
	 * @param array $filters The available filters.
	 * @return int[] $ids The object IDs.
	 */
	public function get_all( $filters ) {
		return array();
	}

	/**
	 * Retrieves a single record.
	 *
	 * @param mixed $record The record.
	 * @return Record $record The record.
	 */
	public function get( $record ) {
		$class = $this->record_class;

		return new $class( $record );
	}

	/**
	 * Retrieves a test object ID.
	 *
	 * @since 2.2.0
	 * @param \Noptin_Automation_Rule $rule
	 * @return int
	 */
	public function get_test_object_id( $rule ) {
		return 0;
	}

	/**
	 * Returns an array of related collections.
	 *
	 * @since 2.2.0
	 * @return array
	 */
	public function get_related_collections() {
		$filtered = Store::filtered( array( 'object_type' => $this->object_type ) );
		unset( $filtered[ $this->type ] );
		return $filtered;
	}
}
