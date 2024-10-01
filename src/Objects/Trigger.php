<?php

/**
 * Generic object trigger.
 *
 * @since 3.0.0
 */

namespace Hizzle\Noptin\Objects;

defined( 'ABSPATH' ) || exit;

/**
 * Generic object trigger.
 */
class Trigger extends \Noptin_Abstract_Trigger {

	/**
	 * @var string $object_type The object type.
	 */
	public $object_type;

	/**
	 * @var string $trigger_id
	 */
	public $trigger_id;

	/**
	 * @var array $trigger_args
	 */
	public $trigger_args;

	/**
	 * Constructor.
	 *
	 * @param string $trigger_id The trigger id.
	 * @param array  $trigger_args The trigger args.
	 * @param Collection $collection The collection.
	 * @since 3.0.0
	 */
	public function __construct( $trigger_id, $trigger_args, $collection ) {
		$this->object_type  = $collection->type;
		$this->trigger_id   = $trigger_id;
		$this->trigger_args = $trigger_args;
		$this->category     = isset( $trigger_args['category'] ) ? $trigger_args['category'] : $collection->label;
		$this->integration  = $collection->integration;

		// Set the contexts.
		$this->contexts[] = $collection->context;

		if ( ! empty( $trigger_args['subject'] ) && $trigger_args['subject'] !== $collection->type ) {
			$this->contexts[] = Store::get_collection_config( $trigger_args['subject'], 'context' );
		}

		if ( ! empty( $trigger_args['provides'] ) ) {
			foreach ( $this->trigger_args['provides'] as $object_type ) {
				$this->contexts[] = Store::get_collection_config( strtok( $object_type, '.' ), 'context' );
			}
		}

		foreach ( Store::all() as $_collection ) {
			if ( in_array( $collection->type, $_collection->provides, true ) ) {
				$this->contexts[] = $_collection->context;
			}
		}

		$this->contexts = array_unique( $this->contexts );

		if ( ! empty( $trigger_args['mail_config'] ) ) {
			$this->mail_config = $trigger_args['mail_config'];
		}

		if ( ! empty( $trigger_args['alias'] ) ) {
			$this->alias = $trigger_args['alias'];
		}

		if ( ! empty( $trigger_args['previous_name'] ) ) {
			add_filter( 'noptin_automation_rule_migrate_triggers', array( $this, 'migrate_trigger' ) );
		}

		add_action( 'noptin_fire_object_trigger_' . $this->trigger_id, array( $this, 'fire_trigger' ) );
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return $this->trigger_id;
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return $this->trigger_args['label'];
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return $this->trigger_args['description'];
	}

	/**
	 * Retrieve the trigger's or action's image.
	 *
	 * @since 1.2.8
	 * @return string
	 */
	public function get_image() {

		if ( ! empty( $this->trigger_args['icon'] ) ) {
			return $this->trigger_args['icon'];
		}

		return Store::get_collection_config( $this->object_type, 'icon' );
	}

	/**
	 * Returns an array of known smart tags.
	 *
	 * @since 3.0.0
	 * @return array
	 */
	public function get_known_smart_tags() {

		$args = array();

		// Add extra args.
		if ( ! empty( $this->trigger_args['extra_args'] ) ) {
			$args = array_merge(
				$args,
				Store::convert_fields_to_smart_tags(
					$this->trigger_args['extra_args'],
					$this->object_type,
					Store::get_collection_config( $this->object_type ),
					Store::get_collection_config( $this->object_type, 'smart_tags_prefix' )
				)
			);
		}

		// Add subject smart tags.
		if ( ! empty( $this->trigger_args['subject'] ) && $this->trigger_args['subject'] !== $this->object_type ) {
			$args = Store::smart_tags( $this->trigger_args['subject'], true );
		}

		// Add object args.
		$args = array_merge(
			$args,
			Store::smart_tags( $this->object_type, true )
		);

		// Add provided args.
		if ( ! empty( $this->trigger_args['provides'] ) ) {
			$custom_labels = isset( $this->trigger_args['custom_labels'] ) ? $this->trigger_args['custom_labels'] : array();

			foreach ( $this->trigger_args['provides'] as $object_type ) {
				$group  = isset( $custom_labels[ $object_type ] ) ? $custom_labels [ $object_type ] : true;
				$prefix = false !== strpos( $object_type, '.' ) ? $object_type : true;
				$args   = array_merge(
					$args,
					Store::smart_tags( strtok( $object_type, '.' ), $group, $prefix )
				);
			}
		}

		// Add generic smart tags.
		$args = array_merge(
			$args,
			parent::get_known_smart_tags()
		);

		unset( $args['user_logged_in'] );
		return $args;
	}

	/**
	 * @inheritdoc
	 */
	public function get_settings() {

		$settings = array();

		if ( ! empty( $this->trigger_args['extra_settings'] ) ) {
			$settings = $this->trigger_args['extra_settings'];
		}

		return array_merge( $settings, parent::get_settings() );
	}

	/**
	 * @inheritdoc
	 */
	public function get_rule_table_description( $rule ) {
		$settings = $rule->get_trigger_settings();
		$meta     = array();

		if ( ! empty( $this->trigger_args['extra_settings'] ) ) {
			foreach ( $this->trigger_args['extra_settings'] as $key => $args ) {

				// If required but not set...
				if ( ! empty( $args['required'] ) && ( ! isset( $settings[ $key ] ) || '' === $settings[ $key ] ) ) {
					return sprintf(
						'<span class="noptin-rule-error">%s</span>',
						sprintf(
							// translators: %s is the field label.
							esc_html__( 'Error: "%s" not specified', 'newsletter-optin-box' ),
							$args['label']
						)
					);
				}

				if ( ! isset( $settings[ $key ] ) || '' === $settings[ $key ] || ! is_scalar( $settings[ $key ] ) ) {
					continue;
				}

				if ( ! empty( $args['show_in_meta'] ) || ! empty( $args['required'] ) ) {
					$value = isset( $settings[ $key ] ) ? esc_html( $settings[ $key ] ) : '';

					if ( $value && ! empty( $args['options'] ) ) {
						$value = isset( $args['options'][ $value ] ) ? $args['options'][ $value ] : $value;
					}

					$meta[ esc_html( $args['label'] ) ] = $value;
				}
			}
		}

		return $this->rule_trigger_meta( $meta, $rule ) . parent::get_rule_table_description( $rule );
	}

	/**
	 * Fires the trigger.
	 *
	 * @param array $args The trigger args.
	 * @since 3.0.0
	 */
	public function fire_trigger( $args ) {

		try {
			$collection = Store::get( $this->object_type );

			if ( empty( $collection ) ) {
				throw new \Exception( 'Collection not registered' );
			}

			$args    = apply_filters( 'noptin_collection_type_trigger_args', $args, $collection, $this );
			$subject = $this->prepare_current_objects( $args );
		} catch ( \Exception $e ) {
			noptin_error_log( $e->getMessage() );
			return;
		}

		// Record activity.
		if ( ! empty( $args['url'] ) && ! empty( $args['email'] ) ) {
			noptin_record_subscriber_activity(
				$args['email'],
				trim(
					sprintf(
						'%s <a href="%s">view</a> %s',
						$this->get_name(),
						esc_url_raw( $args['url'] ),
						! empty( $args['activity'] ) ? ' - ' . $args['activity'] : ''
					)
				)
			);
		}

		$this->trigger( $subject, $args );
	}

	/**
	 * Fetches the correct subject.
	 *
	 * @param mixed $subject_id The subject ID.
	 * @since 3.0.0
	 * @return false|\WP_User|Record
	 */
	protected function get_collection_subject( $subject_id ) {

		if ( empty( $this->trigger_args['subject'] ) ) {
			return get_userdata( $subject_id );
		}

		$collection = Store::get( $this->trigger_args['subject'] );

		if ( empty( $collection ) ) {
			return false;
		}

		$subject = $collection->get( $subject_id );

		if ( empty( $subject ) || ( 'current_user' !== $collection->type && ! $subject->exists() ) ) {
			return false;
		}

		return $subject;
	}

	/**
	 * Serializes the trigger args.
	 *
	 * @since 3.0.0
	 * @param array $args The args.
	 * @return false|array
	 */
	public function serialize_trigger_args( $args ) {
		unset( $args['smart_tags'] );
		unset( $args['subject'] );
		return $args;
	}

	/**
	 * Unserializes the trigger args.
	 *
	 * @since 3.0.0
	 * @param array $args The args.
	 * @return array|false
	 */
	public function unserialize_trigger_args( $args ) {

		// Fetch person.
		$subject = $this->prepare_current_objects( $args );

		// Prepare args.
		$prepared = $this->prepare_trigger_args( $subject, $args );

		// Check for any changes that shouldn't be allowed.
		if ( ! empty( $args['unserialize'] ) ) {
			foreach ( $args['unserialize'] as $key => $original_value ) {
				$current_value = $prepared['smart_tags']->replace_in_text_field( '[[' . $key . ']]' );
				if ( noptin_clean( $original_value ) !== $current_value ) {
					throw new \Exception(
						sprintf(
							'%s changed from "%s" to "%s"',
							esc_html( $key ),
							esc_html( $current_value ),
							esc_html( $original_value )
						)
					);
				}
			}
		}

		return $prepared;
	}

	/**
	 * Prepares the object trigger args.
	 *
	 * @since 3.0.0
	 * @param array $args The args.
	 * @return array|false
	 */
	private function prepare_current_objects( $args ) {
		global $noptin_current_objects;

		// Make sure we have an array.
		$noptin_current_objects = array();

		// Fetch collection.
		$collection = Store::get( $this->object_type );

		if ( empty( $collection ) ) {
			throw new \Exception( 'Collection "' . esc_html( $this->object_type ) . '" not registered' );
		}

		// Prepare current title tag.
		$GLOBALS['noptin_current_title_tag'] = $collection->field_to_merge_tag( $collection->title_field );

		// Fetch person.
		$subject = $this->get_collection_subject( $args['subject_id'] );

		if ( empty( $subject ) ) {
			throw new \Exception( 'Subject not found' );
		}

		if ( ! empty( $this->trigger_args['subject'] ) ) {
			$noptin_current_objects[ $this->trigger_args['subject'] ] = $subject;
		}

		// Fetch object.
		$object = $collection->get( $args['object_id'] );

		if ( empty( $object ) || ! $object->exists() ) {
			throw new \Exception( esc_html( $this->object_type ) . ' not found' );
		}

		$noptin_current_objects[ $this->object_type ] = $object;

		// Provided objects.
		if ( ! empty( $args['provides'] ) ) {
			foreach ( $args['provides'] as $object_type => $id ) {
				if ( empty( $id ) ) {
					continue;
				}

				$collection = false !== strpos( $object_type, '.' ) ? Store::get( strtok( $object_type, '.' ) ) : Store::get( $object_type );

				if ( empty( $collection ) ) {
					throw new \Exception( 'Provided collection "' . esc_html( $object_type ) . '" not registered' );
				}

				$object = $collection->get( $id );

				if ( empty( $object ) || ( 'current_user' !== $object_type && ! $object->exists() && false !== strpos( $object_type, '.' ) ) ) {
					throw new \Exception( esc_html( $object_type ) . ' not found' );
				}

				$noptin_current_objects[ $object_type ] = $object;
			}
		}

		return $subject;
	}

	/**
	 * Prepares email test data.
	 *
	 * @since 3.0.0
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule
	 * @return \Noptin_Automation_Rules_Smart_Tags
	 * @throws \Exception
	 */
	public function get_test_smart_tags( $rule ) {

		$collection = Store::get( $this->object_type );

		if ( empty( $collection ) ) {
			throw new \Exception( 'Collection not registered' );
		}

		$args = $collection->get_test_args( $rule );
		$args = apply_filters( 'noptin_' . $collection->type . '_test_args', $args, $rule, $this->trigger_id );

		if ( empty( $args ) ) {
			throw new \Exception( 'No test data available for this trigger.' );
		}

		// If we're providing current user and we have a user ID, use it.
		if ( ! empty( $this->trigger_args['provides'] ) && in_array( 'current_user', $this->trigger_args['provides'], true ) ) {
			if ( empty( $args['provides'] ) ) {
				$args['provides'] = array();
			}

			$args['provides']['current_user'] = get_current_user_id();
		}

		$args = apply_filters( 'noptin_' . $collection->type . '_collection_trigger_args', $args, $collection );
		$args = apply_filters( 'noptin_collection_type_trigger_args', $args, $collection, $this );

		// Fetch person.
		$subject = $this->prepare_current_objects( $args );

		// Prepare args.
		$prepared = $this->prepare_trigger_args( $subject, $args );

		return $prepared['smart_tags'];
	}

	/**
	 * Prepares trigger args.
	 *
	 * @since 1.11.0
	 * @param mixed $subject The subject.
	 * @param array $args Extra arguments passed to the action.
	 * @return array
	 */
	public function prepare_trigger_args( $subject, $args ) {

		if ( empty( $args['email'] ) && is_callable( array( $subject, 'get_email' ) ) ) {
			$args['email'] = $subject->get_email();
		}

		return parent::prepare_trigger_args( $subject, $args );
	}

	/**
	 * Migrates triggers.
	 *
	 * @since 3.0.0
	 *
	 * @param array $triggers The triggers.
	 */
	public function migrate_trigger( $triggers ) {

		$previous_name = $this->trigger_args['previous_name'];
		$new_name      = $this->get_id();
		$triggers[]    = array(
			'id'         => 'rename_' . $previous_name,
			'trigger_id' => $previous_name,
			'callback'   => function ( &$automation_rule ) use ( $new_name ) {

				/** @var \Hizzle\Noptin\Automation_Rules\Automation_Rule $automation_rule */
				$automation_rule->set_trigger_id( $new_name );
			},
		);

		return $triggers;
	}
}
