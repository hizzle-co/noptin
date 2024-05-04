<?php

namespace Hizzle\Noptin\Integrations;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Base Form integration
 *
 * @since 3.0.0
 */
abstract class Form_Integration extends Automation_Integration {

	/**
	 * @var string The form plugin slug.
	 * @since 2.0.0
	 */
	public $slug;

	/**
	 * @var string The form plugin name.
	 * @since 2.0.0
	 */
	public $name;

	/**
	 * @var array An array of forms.
	 */
	protected $forms = null;

	/**
	 * Constructor
	 */
	public function __construct() {

		parent::__construct();

		add_filter( 'noptin_' . $this->slug . '_forms', array( $this, 'filter_forms' ), $this->priority );
	}

	/**
	 * Retrieves all forms.
	 *
	 * @return array
	 */
	abstract protected function get_forms();

	/**
	 * @inheritDoc
	 */
	public function register_triggers( $rules ) {
		$rules->add_trigger( new \Noptin_Form_Submit_Trigger( $this->slug, $this->name ) );
	}

	/**
	 * Filters forms.
	 *
	 * @param array $forms An array of forms.
	 * @return array
	 */
	public function filter_forms( $forms ) {

		// Return cached forms.
		if ( is_array( $this->forms ) ) {
			return array_replace( $forms, $this->forms );
		}

		// Cache forms.
		$this->forms = $this->get_forms();
		return array_replace( $forms, $this->forms );
	}

	/**
     * @param int $form_id The form ID.
	 * @param arary $submitted_data The submitted data.
     */
    public function process_form_submission( $form_id, $submitted_data ) {
        do_action( 'noptin_' . $this->slug . '_form_submitted', $form_id, $submitted_data );
	}
}
