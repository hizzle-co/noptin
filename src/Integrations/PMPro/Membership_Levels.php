<?php

namespace Hizzle\Noptin\Integrations\PMPro;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for PMPro membership level.
 *
 * @since 3.0.0
 */
class Membership_Levels extends \Hizzle\Noptin\Objects\Collection {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->integration       = 'paid-memberships-pro';
		$this->type              = 'pmpro_membership_level';
		$this->smart_tags_prefix = 'level';
		$this->label             = __( 'Membership Levels', 'newsletter-optin-box' );
		$this->singular_label    = __( 'Membership Level', 'newsletter-optin-box' );
		$this->record_class      = __NAMESPACE__ . '\Membership_Level';
		$this->icon              = array(
			'icon' => 'groups',
			'fill' => '#0c3d54',
		);

		add_filter( 'noptin_collection_type_register_trigger_args', array( $this, 'filter_register_trigger_args' ), 10, 2 );
		add_filter( 'noptin_collection_type_trigger_args', array( $this, 'filter_trigger_args' ), 10, 3 );

		parent::__construct();
	}

	/**
	 * Retrieves available fields.
	 *
	 */
	public function get_fields() {

		return array(
			'id'                => array(
				'label' => __( 'ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'name'              => array(
				'label' => __( 'Name', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'description'       => array(
				'label' => __( 'Description', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'confirmation'      => array(
				'label' => __( 'Confirmation Message', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'initial_payment'   => array(
				'label' => __( 'Initial Payment', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'billing_amount'    => array(
				'label' => __( 'Billing Amount', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'cycle_number'      => array(
				'label' => __( 'Cycle Number', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'cycle_period'      => array(
				'label'   => __( 'Cycle Period', 'newsletter-optin-box' ),
				'type'    => 'string',
				'options' => array(
					'Day'   => __( 'Day', 'paid-memberships-pro' ),
					'Week'  => __( 'Week', 'paid-memberships-pro' ),
					'Month' => __( 'Month', 'paid-memberships-pro' ),
					'Year'  => __( 'Year', 'paid-memberships-pro' ),
				),
			),
			'billing_limit'     => array(
				'label' => __( 'Billing Cycle Limit', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'trial_amount'      => array(
				'label' => __( 'Trial Amount', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'trial_limit'       => array(
				'label' => __( 'Trial Limit', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'allow_signups'     => array(
				'label' => __( 'Allow New Signups', 'newsletter-optin-box' ),
				'type'  => 'boolean',
			),
			'expiration_number' => array(
				'label' => __( 'Expiration Number', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'expiration_period' => array(
				'label'   => __( 'Expiration Period', 'newsletter-optin-box' ),
				'type'    => 'string',
				'options' => array(
					'Day'   => __( 'Day', 'paid-memberships-pro' ),
					'Week'  => __( 'Week', 'paid-memberships-pro' ),
					'Month' => __( 'Month', 'paid-memberships-pro' ),
					'Year'  => __( 'Year', 'paid-memberships-pro' ),
				),
			),
			'meta'              => array(
				'label'          => __( 'Meta Value', 'newsletter-optin-box' ),
				'type'           => 'string',
				'example'        => 'key="my_key"',
				'skip_smart_tag' => true,
			),
		);
	}

	/**
	 * Registers user fields.
	 *
	 * @param \Hizzle\Noptin\Objects\Collection $collection
	 * @since 3.0.0
	 */
	public function filter_register_trigger_args( $args, $collection ) {

		if ( empty( $args['provides'] ) ) {
			$args['provides'] = array();
		}

		$provide = array();

		if ( 'person' === $collection->object_type ) {
			$provide[ $this->type . '.' . $collection->type ] = $this->singular_label . '(' . $collection->singular_label . ')';
		}

		$provides = $args['provides'];
		if ( ! empty( $args['subject'] ) ) {
			$provides[] = $args['subject'];
		}

		foreach ( $provides as $provided ) {
			$provided_collection = \Hizzle\Noptin\Objects\Store::get( $provided );

			if ( $provided_collection && 'person' === $provided_collection->object_type ) {
				$provide[ $this->type . '.' . $provided_collection->type ] = $this->singular_label . '(' . $provided_collection->singular_label . ')';
			}
		}

		$args['provides'] = array_merge( $args['provides'], array_keys( $provide ) );

		if ( empty( $args['custom_labels'] ) ) {
			$args['custom_labels'] = array();
		}

		$args['custom_labels'] = array_merge( $args['custom_labels'], $provide );

		return $args;
	}

	/**
	 * Registers user fields.
	 *
	 * @param \Hizzle\Noptin\Objects\Collection $collection
	 * @param \Hizzle\Noptin\Objects\Trigger $trigger
	 * @since 3.0.0
	 */
	public function filter_trigger_args( $args, $collection, $trigger ) {

		$args['provides'] = empty( $args['provides'] ) ? array() : $args['provides'];

		if ( 'person' === $collection->object_type ) {
			$args['provides'][ $this->type . '.' . $collection->type ] = $this->get_person_user_id( $args['object_id'], $collection->type );
		}

		if ( ! empty( $trigger->trigger_args['subject'] ) ) {
			$args['provides'][ $this->type . '.' . $trigger->trigger_args['subject'] ] = $this->get_person_user_id( $args['subject_id'], $trigger->trigger_args['subject'] );
		}

		foreach ( $args['provides'] as $provided => $id ) {
			if ( false !== strpos( $provided, '.' ) ) {
				continue;
			}

			$provided_collection = \Hizzle\Noptin\Objects\Store::get( $provided );

			if ( $provided_collection && 'person' === $provided_collection->object_type ) {
				$args['provides'][ $this->type . '.' . $provided_collection->type ] = $this->get_person_user_id( $id, $provided_collection->type );
			}
		}

		return $args;
	}

	/**
	 * Retrieves a person email.
	 *
	 * @since 3.0.0
	 */
	private function get_person_user_id( $id, $collection ) {

		if ( empty( $id ) ) {
			return 0;
		}

		$collection = \Hizzle\Noptin\Objects\Store::get( $collection );
		if ( empty( $collection ) || 'person' !== $collection->object_type ) {
			return 0;
		}

		/** @var \Hizzle\Noptin\Objects\Person $record */
		$record = $collection ? $collection->get( $id ) : 0;

		if ( empty( $record ) || ! $record->exists() ) {
			return 0;
		}

		$user = get_user_by( 'email', $record->get_email() );

		if ( empty( $user ) ) {
			return 0;
		}

		$level = pmpro_getMembershipLevelForUser( $user->ID );

		return empty( $level ) ? 0 : $level->id;
	}
}
