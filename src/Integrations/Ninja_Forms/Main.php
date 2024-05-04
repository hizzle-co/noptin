<?php

namespace Hizzle\Noptin\Integrations\Ninja_Forms;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles integrations with Ninja Forms.
 *
 * @since 2.1.0
 */
class Main extends \Hizzle\Noptin\Integrations\Form_Integration {

	/**
	 * @var string
	 */
	public $slug = 'ninja_forms';

	/**
	 * @var string
	 */
	public $name = 'Ninja Forms';

	/**
	 * Constructor
	 */
	public function __construct() {

		parent::__construct();

		// Process form submission.
		add_action( 'ninja_forms_after_submission', array( $this, 'process_form' ) );

		// Custom action.
		add_action( 'nf_init', array( $this, 'register_action' ) );
	}

	/**
	 * Retrieves all forms.
	 *
	 * @return array
	 */
	protected function get_forms() {

		// Get all forms.
		$all_forms = Ninja_Forms()->form()->get_forms();
		$all_forms = is_array( $all_forms ) ? $all_forms : array();
		$prepared  = array();

		/** @var \NF_Database_Models_Form[] $all_forms */
		foreach ( $all_forms as $form ) {
			$fields = array();

			foreach ( Ninja_Forms()->form( $form->get_id() )->get_fields() as $ninja_field ) {

				/** @var \NF_Database_Models_Field $ninja_field */
				if ( in_array( $ninja_field->get_setting( 'type' ), array( 'submit', 'spam', 'html', 'hr' ), true ) ) {
					continue;
				}

				$logic = 'string';

				if ( in_array( $ninja_field->get_setting( 'type' ), array( 'number', 'calc', 'starrating' ), true ) ) {
					$logic = 'number';
				}

				$fields[ $ninja_field->get_setting( 'key' ) ] = array(
					'description'       => $ninja_field->get_setting( 'label' ),
					'conditional_logic' => $logic,
				);

				// Maybe add options.
				$options = $ninja_field->get_setting( 'options' );

				if ( is_array( $options ) ) {
					$fields[ $ninja_field->get_setting( 'key' ) ]['options'] = wp_list_pluck(
						$options,
						'label',
						'value'
					);
				} elseif ( 'checkbox' === $ninja_field->get_setting( 'type' ) ) {
					$fields[ $ninja_field->get_setting( 'key' ) ]['options'] = array(
						'1' => 'Checked',
						'0' => 'Unchecked',
					);
				}
			}

			$prepared[ $form->get_id() ] = array(
				'name'   => $form->get_setting( 'title' ),
				'fields' => $fields,
			);
		}

		return $prepared;
	}

	/**
	 * Process form submission.
	 */
	public function process_form( $data ) {

		$form_id = $data['form_id'];
		$posted  = wp_list_pluck( $data['fields'], 'value', 'key' );

		// Trigger action.
		$this->process_form_submission( $form_id, $posted );
	}

	/**
	 * Registers ninja forms action.
	 *
	 * @since 1.5.5
	 */
	public function register_action() {

		// Custom action.
		if ( ! function_exists( 'add_noptin_subscriber' ) ) {
			return;
		}

		$ninja_forms                    = \Ninja_Forms::instance();
		$ninja_forms->actions['noptin'] = new Action();
	}
}
