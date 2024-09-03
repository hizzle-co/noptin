<?php

namespace Hizzle\Noptin\Integrations\Elementor;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integrations with Elementor.
 *
 * @since 2.1.0
 */
class Main extends \Hizzle\Noptin\Integrations\Form_Integration {

	/**
	 * @var string
	 */
	public $slug = 'elementor';

	/**
	 * @var string
	 */
	public $name = 'Elementor';

	private $ignore_fields = array( 'step', 'recaptcha', 'recaptcha_v3', 'honeypot', 'html' );

	/**
	 * Constructor
	 */
	public function __construct() {

		parent::__construct();

		// Process submission.
		add_action( 'elementor_pro/forms/new_record', array( $this, 'process_form' ) );

		// Register form action.
		add_action( 'elementor_pro/init', array( $this, 'register_form_action' ) );
	}

	/**
	 * Retrieves all forms.
	 *
	 * @return array
	 */
	protected function get_forms() {

		$forms           = array();
		$elementor_posts = get_posts(
			array(
				'post_type'        => get_post_types( array( 'public' => true ) ),
				'fields'           => 'ids',
				'posts_per_page'   => -1,
				'meta_key'         => '_elementor_data',
				'meta_value'       => 'form_fields',
				'meta_compare'     => 'LIKE',
				'post_status'      => array( 'publish', 'draft', 'future', 'pending', 'private' ),
				'suppress_filters' => true, // WPML (also to prevent any posts_where filters from modifying the query)
				'lang'             => '', // Polylang
			)
		);

		foreach ( $elementor_posts as $post_id ) {
			$elements = get_post_meta( $post_id, '_elementor_data', true );
			$forms    = array_merge( $forms, $this->get_all_inner_forms( json_decode( $elements ) ) );
		}

		return $forms;
	}

	/**
	 * Retrieves all inner forms from a given element.
	 *
	 * @param array $elements An array of elements.
	 */
	private function get_all_inner_forms( $elements ) {
		$forms = array();

		// Abort if no elements are found.
		if ( ! is_array( $elements ) ) {
			return $forms;
		}

		foreach ( $elements as $element ) {

			// Abort if not object.
			if ( ! is_object( $element ) ) {
				continue;
			}

			// Check for inner elements.
			if ( ! empty( $element->elements ) ) {
				$forms = array_merge( $forms, $this->get_all_inner_forms( $element->elements ) );
			}

			if ( ! isset( $element->elType ) || ! isset( $element->widgetType ) ) {
				continue;
			}

			if ( 'widget' === $element->elType && 'form' === $element->widgetType ) {
				$forms[ $element->id ] = array(
					'name'   => $element->settings->form_name . " (ID: {$element->id})",
					'fields' => $this->prepare_noptin_automation_rule_fields( $element->settings->form_fields ),
				);
			}
		}

		return $forms;
	}

	/**
	 * Prepares form fields.
	 *
	 * @param object[] $fields The form fields.
	 * @return array
	 */
	public function prepare_noptin_automation_rule_fields( $fields ) {

		$prepared_fields = array(
			'page_url'   => array(
				'description'       => __( 'Page URL', 'newsletter-optin-box' ),
				'conditional_logic' => 'string',
			),
			'user_agent' => array(
				'description'       => __( 'User Agent', 'newsletter-optin-box' ),
				'conditional_logic' => 'string',
			),
			'remote_ip'  => array(
				'description'       => __( 'IP Address', 'newsletter-optin-box' ),
				'conditional_logic' => 'string',
			),
		);

		// Loop through all fields.
		foreach ( $fields as $elementor_field ) {

			// Skip fields with no name.
			if ( empty( $elementor_field->field_label ) && empty( $elementor_field->placeholder ) && empty( $elementor_field->acceptance_text ) ) {
				continue;
			}

			if ( ! empty( $elementor_field->field_type ) && in_array( $elementor_field->field_type, $this->ignore_fields, true ) ) {
				continue;
			}

			$label = '';

			if ( ! empty( $elementor_field->field_label ) ) {
				$label = $elementor_field->field_label;
			} elseif ( ! empty( $elementor_field->placeholder ) ) {
				$label = $elementor_field->placeholder;
			} else {
				$label = $elementor_field->acceptance_text;
			}

			$field = array(
				'description'       => $label,
				'conditional_logic' => 'string',
			);

			if ( ! empty( $elementor_field->field_type ) && 'number' === $elementor_field->field_type ) {
				$field['conditional_logic'] = 'number';
			}

			$options = $this->get_field_options( $elementor_field );

			// Acceptance.
			if ( ! empty( $elementor_field->field_type ) && 'acceptance' === $elementor_field->field_type ) {
				$options = array(
					'on'  => 'Checked',
					'off' => 'Unchecked',
				);
			}

			if ( ! empty( $options ) ) {
				$field['options'] = $options;
			}

			$prepared_fields[ $elementor_field->custom_id ] = $field;
		}

		return $prepared_fields;
	}

	/**
	 * Retrieves the field options.
	 *
	 * @param object $field The field.
	 * @return array
	 */
	public function get_field_options( $field ) {

		// Abort if field has no options.
		if ( empty( $field->field_options ) ) {
			return array();
		}

		$options = array();

		// Split options by line.
		foreach ( preg_split( '/\r\n|\r|\n/', $field->field_options ) as $option ) {

			// Split option by pipe.
			$option = explode( '|', $option );

			// Use the label as the value if no value is provided.
			if ( ! isset( $option[1] ) ) {
				$option[1] = $option[0];
			}

			$options[ trim( $option[1] ) ] = trim( $option[0] );
		}

		return $options;
	}

	/**
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record The submitted record.
	 */
	public function process_form( $record ) {

		$form_id = $record->get_form_settings( 'id' );

		if ( empty( $form_id ) ) {
			return;
		}

		// Posted fields.
		$posted = array();

		foreach ( $record->get( 'fields' ) as $field ) {
			$value = $field['value'];

			if ( ! empty( $field['type'] ) ) {
				if ( in_array( $field['type'], $this->ignore_fields, true ) ) {
					continue;
				}

				if ( 'acceptance' === $field['type'] && empty( $value ) ) {
					$value = 'off';
				}
			}

			$posted[ $field['id'] ] = $value;
		}

		// Add meta.
		$posted = array_merge( wp_list_pluck( $record->get( 'meta' ), 'value' ), $posted );

		$this->process_form_submission( $form_id, $posted );
	}

	/**
	 * Registers the form action.
	 */
	public function register_form_action() {

		// Custom action.
		if ( ! function_exists( 'add_noptin_subscriber' ) ) {
			return;
		}

		// Instantiate the action class
		$action = new Action();

		// Register the action with form widget
		/** @var \ElementorPro\Modules\Forms\Module $forms */
		$forms = \ElementorPro\Plugin::instance()->modules_manager->get_modules( 'forms' );
		$forms->actions_registrar->register( $action, $action->get_name() );
	}
}
