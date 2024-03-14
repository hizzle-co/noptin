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

		foreach ( $fields as $key => $args ) {

			// If needed for this action...
			if ( ! empty( $args['actions'] ) && in_array( $this->action_id, $args['actions'], true ) ) {
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
				$value = isset( $settings[ $key ] ) ? esc_html( $settings[ $key ] ) : '';

				if ( $value && ! empty( $args['options'] ) ) {
					$value = isset( $args['options'][ $value ] ) ? $args['options'][ $value ] : $value;
				}

				$meta[ esc_html( $args['label'] ) ] = $value;
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

			if ( isset( $field['el'] ) ) {
				$settings[ $key ] = $field;
				continue;
			}

			$settings[ $key ] = array(
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
				return false;
			}
		}

		return isset( $this->action_args['callback'] ) && is_callable( $this->action_args['callback'] );
	}

	/**
	 * @inheritdoc
	 */
	public function run( $subject, $rule, $args ) {

		$settings = array();

		/** @var \Noptin_Automation_Rules_Smart_Tags $smart_tags */
		$smart_tags = $args['smart_tags'];

		foreach ( $this->get_action_fields() as $key => $args ) {
			$settings[ $key ] = $smart_tags->replace_in_content( $rule->get_action_setting( $key ) );
		}

		call_user_func_array(
			$this->action_args['callback'],
			array(
				$settings,
				$this->action_id,
				$subject,
				$rule,
				$args,
				$smart_tags,
			)
		);
	}
}
