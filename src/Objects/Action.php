<?php

namespace Hizzle\Noptin\Objects;

/**
 * Generic object action.
 *
 * @since 2.2.0
 */

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
	 * @param string $action_id The trigger id.
	 * @param array  $action_args The trigger args.
	 * @param Collection $collection The collection.
	 * @since 2.2.0
	 */
	public function __construct( $action_id, $action_args, $collection ) {
		$this->object_type = $collection->type;
		$this->action_id   = $action_id;
		$this->action_args = $action_args;
		$this->category    = $collection->label;
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
	 * Retrieve the actions's rule table description.
	 *
	 * @param \Noptin_Automation_Rule $rule
	 * @return array
	 */
	public function get_rule_table_description( $rule ) {
		$settings = $rule->action_settings;
		$fields   = Store::fields( $this->object_type );
		$meta     = array();

		foreach ( $fields as $key => $args ) {
			if ( ! empty( $args['required'] ) && empty( $settings[ $key ] ) ) {
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
				$meta[ esc_html( $args['label'] ) ] = isset( $settings[ $key ] ) ? esc_html( $settings[ $key ] ) : '';
			}
		}

		return $this->rule_action_meta( $meta, $rule );
	}

	/**
	 * @inheritdoc
	 */
	public function get_settings() {

		$settings   = array();
		$all_fields = Store::fields( $this->object_type );
		$fields     = isset( $this->action_args['fields'] ) ? $this->action_args['fields'] : array_keys( $all_fields );

		// Maybe add extra fields.
		if ( ! empty( $this->action_args['extra_settings'] ) ) {
			$settings = $this->action_args['extra_settings'];
		}

		foreach ( $fields as $field_key ) {

			if ( ! isset( $all_fields[ $field_key ] ) ) {
				continue;
			}

			$field = $all_fields[ $field_key ];

			$settings[ $field_key ] = array(
				'type'        => 'text',
				'el'          => 'input',
				'label'       => $field['label'],
				'map_field'   => true,
				'placeholder' => sprintf(
					/* translators: %s: The field name. */
					__( 'Enter %s', 'newsletter-optin-box' ),
					strtolower( $field['label'] )
				),
				'description' => empty( $field['description'] ) ? '' : $field['description'],
			);
		}

		return $settings;
	}

	/**
	 * Run the action.
	 *
	 * @param mixed $subject The subject.
	 * @param \Noptin_Automation_Rule $rule The automation rule used to trigger the action.
	 * @param array $args Extra arguments passed to the action.
	 * @return void
	 */
	public function run( $subject, $rule, $args ) {

		$settings = array();

		/** @var \Noptin_Automation_Rules_Smart_Tags $smart_tags */
		$smart_tags = $args['smart_tags'];

		foreach ( wp_unslash( $rule->action_settings ) as $key => $value ) {

			if ( '' === $value ) {
				continue;
			}

			$settings[ $key ] = is_scalar( $value ) ? $smart_tags->replace_in_content( $value ) : $value;
		}

		call_user_func_array(
			array( $this->action_args['callback'] ),
			array(
				$settings,
				$subject,
				$rule,
				$args,
				$smart_tags,
			)
		);
	}
}
