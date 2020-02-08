<?php
/**
 * Optin Form Editor
 *
 * Responsible for editing the optin forms
 *
 * @since             1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class Noptin_Form_Editor_Quick {

	/**
	 * Id of the form being edited
	 *
	 * @access      public
	 * @since       1.0.0
	 */
	public $id = null;

	/**
	 * Post object of the form being edited
	 *
	 * @access      public
	 * @since       1.0.0
	 */
	public $post = null;

	/**
	 * Class Constructor.
	 */
	public function __construct( $id, $localize = false ) {
		$this->id   = $id;
		$this->post = noptin_get_optin_form( $id );

		if ( $localize ) {
			noptin_localize_optin_editor( $this->get_state() );
		}
	}

	/**
	 * Displays the editor
	 */
	public function output() {
		$steps = $this->steps();
		$state = $this->get_state();
		get_noptin_template( 'optin-form-editor-quick.php', compact( 'steps', 'state' ) );
	}

	/**
	 * Returns sidebar fields
	 */
	public function steps() {
		$steps = array(
			'step_1' => $this->get_step_1(),
			'step_2' => $this->get_step_2(),
			'step_3' => $this->get_step_3(),
			'step_4' => $this->get_step_4(),
			'step_5' => $this->get_step_5(),
			'step_6' => $this->get_step_6(),
			'step_7' => $this->get_step_7(),
		);
		return apply_filters( 'noptin_optin_form_editor_quick_steps', $steps, $this );
	}

	/**
	 * Returns step_1 fields
	 */
	public function get_step_1() {
		return array(

			// Hero.
			'step1Hero' => array(
				'el'      => 'hero',
				'content' => 'First, give your form a name then click on the type of form you want to create',
			),

			// Title.
			'optinName' => array(
				'el'      => 'input',
				'label'   => 'Form Name',
				'tooltip' => 'This name will help you identify the form when listing all forms',
			),

			// Form type.
			'optinType' => array(
				'el'    => 'optin_types',
				'label' => 'Form Type',
			),

		);
	}

	/**
	 * Returns step_2 fields
	 */
	public function get_step_2() {
		return array(

			// Hero.
			'step2Hero'     => array(
				'el'      => 'hero',
				'content' => 'Next, select the template you want to use then click on continue',
			),

			// Template.
			'optinTemplate' => array(
				'el' => 'optin_templates',
			),

		);
	}

	/**
	 * Returns step_3 fields
	 */
	public function get_step_3() {
		return array(

			// Hero.
			'step3Hero'  => array(
				'el'      => 'hero',
				'content' => 'Use a color theme to quickly style your form',
			),

			// Color Theme.
			'optinTheme' => array(
				'el' => 'color_themes',
			),

		);
	}

	/**
	 * Returns step_4 fields
	 */
	public function get_step_4() {
		return array(

			// Hero.
			'step4Hero' => array(
				'el'      => 'hero',
				'content' => 'Provide a title and description of your form',
			),

			// Title.
			'optinData' => array(
				'el' => 'optin_data',
			),

		);
	}

	/**
	 * Returns step_5 fields
	 */
	public function get_step_5() {
		return array(

			// Hero.
			'step5Hero'  => array(
				'el'      => 'hero',
				'content' => 'Do you want to attach an image to the form?',
			),

			// Title.
			'optinImage' => array(
				'el' => 'optin_image',
			),

		);
	}

	/**
	 * Returns step_6 fields
	 */
	public function get_step_6() {
		return array(

			// Hero.
			'step6Hero'   => array(
				'el'      => 'hero',
				'content' => 'Finally, set up the form fields',
			),

			// Title.
			'optinFields' => array(
				'el' => 'optin_fields',
			),

		);
	}

	/**
	 * Returns step_7 fields
	 */
	public function get_step_7() {
		return array(

			// Publish? More configuration? Back to forms overview.
			'optinName' => array(
				'el' => 'optin_done',
			),

		);
	}

	/**
	 * Returns the editor state as a JSON string
	 */
	public function get_state_json() {
		return wp_json_encode( $this->get_state() );
	}

	/**
	 * Returns the editor state
	 */
	public function get_state() {

		$saved_state = $this->post->get_all_data();
		unset( $saved_state['optinHTML'] );
		$state = array_replace( $saved_state, $this->get_misc_state() );
		return apply_filters( 'noptin_optin_form_editor_quick_state', $state, $this );

	}


	/**
	 * Returns misc state
	 */
	public function get_misc_state() {
		return array(
			'hasSuccess'            => false,
			'Success'               => '',
			'hasError'              => false,
			'Error'                 => '',
			'headerTitle'           => __( 'Editing', 'newsletter-optin-box' ),
			'saveText'              => __( 'Save', 'newsletter-optin-box' ),
			'savingText'            => __( 'Saving...', 'newsletter-optin-box' ),
			'saveAsTemplateText'    => __( 'Save As Template', 'newsletter-optin-box' ),
			'savingTemplateText'    => __( 'Saving Template...', 'newsletter-optin-box' ),
			'savingError'           => __( 'There was an error saving your form.', 'newsletter-optin-box' ),
			'savingSuccess'         => __( 'Your changes have been saved successfuly', 'newsletter-optin-box' ),
			'savingTemplateError'   => __( 'There was an error saving your template.', 'newsletter-optin-box' ),
			'savingTemplateSuccess' => __( 'Your template has been saved successfuly', 'newsletter-optin-box' ),
			'previewText'           => __( 'Preview', 'newsletter-optin-box' ),
			'isPreviewShowing'      => false,
			'colorTheme'            => '',
			'Template'              => '',
			'currentStep'           => 'step_1',
		);
	}

	/**
	 * Converts an array of ids to select2 option
	 */
	public function post_ids_to_options( $ids ) {

		// Return post ids array.
		if ( ! is_array( $ids ) ) {
			return array();
		}

		$options = array();
		foreach ( $ids as $id ) {
			$post_type      = get_post_type( $id );
			$title          = get_the_title( $id );
			$options[ $id ] = "[{$post_type}] $title";
		}

		return $options;
	}
}
