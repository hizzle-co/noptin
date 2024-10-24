<?php
/**
 * Generic object action.
 *
 * @since 3.0.0
 */

namespace Hizzle\Noptin\Objects;

defined( 'ABSPATH' ) || exit;

/**
 * Generic object action.
 */
class Action extends \Noptin_Abstract_Action {

	/**
	 * @var string $object_type The object type.
	 */
	public $object_type;

	/**
	 * @var string $action_id
	 */
	public $action_id;

	/**
	 * @var array $action_args
	 */
	public $action_args;

	/**
	 * Constructor.
	 *
	 * @param string $action_id The action id.
	 * @param array  $action_args The action args.
	 * @param Collection $collection The collection.
	 * @since 3.0.0
	 */
	public function __construct( $action_id, $action_args, $collection ) {
		$this->object_type = $collection->type;
		$this->action_id   = $action_id;
		$this->action_args = $action_args;
		$this->category    = isset( $action_args['category'] ) ? $action_args['category'] : $collection->label;
		$this->integration = $collection->integration;
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return $this->action_id;
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return $this->action_args['label'];
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return $this->action_args['description'];
	}

	/**
	 * Retrieve the trigger's or action's image.
	 *
	 * @since 1.2.8
	 * @return string
	 */
	public function get_image() {

		if ( ! empty( $this->action_args['icon'] ) ) {
			return $this->action_args['icon'];
		}

		return Store::get_collection_config( $this->object_type, 'icon' );
	}

	/**
	 * Returns the fields needed for this action.
	 *
	 * @return array
	 */
	protected function get_action_fields() {
		$fields   = Store::fields( $this->object_type );
		$prepared = array();

		if ( ! empty( $this->action_args['extra_settings'] ) ) {
			$prepared = $this->action_args['extra_settings'];
		}

		$include = empty( $this->action_args['action_fields'] ) ? array() : $this->action_args['action_fields'];
		foreach ( $fields as $key => $args ) {

			// If needed for this action...
			if ( in_array( $key, $include, true ) || ( ! empty( $args['actions'] ) && in_array( $this->action_id, $args['actions'], true ) ) ) {
				if ( isset( $args['action_label'] ) ) {
					$args['label'] = $args['action_label'];
					unset( $args['action_label'] );
				}

				if ( isset( $args['action_props'] ) && isset( $args['action_props'][ $this->action_id ] ) ) {
					$args = array_merge( $args, $args['action_props'][ $this->action_id ] );
					unset( $args['action_props'] );
				}

				$prepared[ $key ] = $args;
			}
		}

		return $prepared;
	}

	/**
	 * @inheritdoc
	 */
	public function get_rule_table_description( $rule ) {
		$settings = $rule->get_action_settings();
		$meta     = array();

		foreach ( $this->get_action_fields() as $key => $args ) {

			if ( empty( $args['label'] ) && ! empty( $args['description'] ) ) {
				$args['label'] = $args['description'];
			}

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

			if ( ! empty( $args['show_in_meta'] ) || ! empty( $args['required'] ) ) {
				$value = isset( $settings[ $key ] ) ? $settings[ $key ] : '';

				if ( $value && ! empty( $args['options'] ) ) {
					$new_value = array();

					foreach ( (array) $value as $v ) {
						if ( isset( $args['options'][ $v ] ) ) {
							$new_value[] = $args['options'][ $v ];
						} else {
							$new_value[] = $v;
						}
					}

					$value = $new_value;
				}

				if ( is_array( $value ) ) {
					$value = implode( ', ', $value );
				}

				if ( $value ) {
					$meta[ esc_html( $args['label'] ) ] = esc_html( $value );
				}
			}
		}

		return $this->rule_action_meta( $meta, $rule );
	}

	/**
	 * @inheritdoc
	 */
	public function get_settings() {

		$settings = array();

		foreach ( $this->get_action_fields() as $key => $field ) {
			if ( empty( $field['label'] ) && ! empty( $field['description'] ) ) {
				$field['label'] = $field['description'];
				unset( $field['description'] );
			}

			if ( ! empty( $field['options'] ) ) {
				$field['el'] = 'select';
				unset( $field['type'] );
			}

			if ( isset( $field['el'] ) ) {
				$settings[ $key ] = $field;
				continue;
			}

			$settings[ $key ] = array(
				'type'        => 'text',
				'el'          => 'input',
				'label'       => $field['label'],
				'map_field'   => true,
				'placeholder' => isset( $field['placeholder'] ) ? $field['placeholder'] : sprintf(
					/* translators: %s: The field name. */
					__( 'Enter %s', 'newsletter-optin-box' ),
					strtolower( $field['label'] )
				),
				'description' => empty( $field['description'] ) ? '' : $field['description'],
			);

			if ( isset( $field['default'] ) ) {
				$settings[ $key ]['default'] = $field['default'];
			}
		}

		return $settings;
	}

	/**
	 * @inheritdoc
	 */
	public function can_run( $subject, $rule, $args ) {
		$settings = $rule->get_action_settings();

		foreach ( $this->get_action_fields() as $key => $args ) {

			// If required but not set...
			if ( ! empty( $args['required'] ) && ( ! isset( $settings[ $key ] ) || '' === $settings[ $key ] ) ) {
				throw new \Exception( sprintf( 'Error: "%s" not specified', esc_html( $args['label'] ) ) );
			}
		}

		if ( ! isset( $this->action_args['callback'] ) || ! is_callable( $this->action_args['callback'] ) ) {
			throw new \Exception( 'Error: Callback not specified' );
		}

		// Check if we have a custom can_run.
		if ( isset( $this->action_args['can_run'] ) && is_callable( $this->action_args['can_run'] ) ) {
			return call_user_func_array( $this->action_args['can_run'], array( $subject, $rule, $args ) );
		}

		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function run( $subject, $rule, $args ) {

		$settings = array();

		/** @var \Noptin_Automation_Rules_Smart_Tags $smart_tags */
		$smart_tags = $args['smart_tags'];

		foreach ( $this->get_action_fields() as $key => $args ) {
			$saved = $rule->get_action_setting( $key );

			if ( ! is_null( $saved ) && '' !== $saved ) {
				$settings[ $key ] = $smart_tags->replace_in_content( $rule->get_action_setting( $key ) );
			}
		}

		$args = array(
			'settings'   => $settings,
			'action_id'  => $this->action_id,
			'subject'    => $subject,
			'rule'       => $rule,
			'args'       => $args,
			'smart_tags' => $smart_tags,
		);

		$needed = isset( $this->action_args['callback_args'] ) ? $this->action_args['callback_args'] : array( 'settings', 'action_id', 'subject', 'rule', 'args', 'smart_tags' );

		// Order the arguments.
		$args = array_merge( array_flip( $needed ), $args );

		// Only pass the needed arguments.
		$args = array_values( array_intersect_key( $args, array_flip( $needed ) ) );

		// If we have an args limit, apply it.
		if ( isset( $this->action_args['callback_args_limit'] ) ) {
			$args = array_slice( $args, 0, $this->action_args['callback_args_limit'] );
		}

		return call_user_func_array( $this->action_args['callback'], $args );
	}

	/**
	 * @inheritdoc
	 */
	public function run_if() {
		return isset( $this->action_args['run_if'] ) ? $this->action_args['run_if'] : parent::run_if();
	}

	/**
	 * @inheritdoc
	 */
	public function skip_if() {
		return isset( $this->action_args['skip_if'] ) ? $this->action_args['skip_if'] : parent::skip_if();
	}
}
