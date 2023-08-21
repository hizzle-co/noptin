<?php
/**
 * Forms API: Form Element.
 *
 * Displays the actual opt-in forms.
 *
 * @since   1.6.2
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Displays the actual opt-in forms.
 *
 * @see show_noptin_form()
 * @since 1.6.2
 * @ignore
 * @internal
 * @ignore
 */
class Noptin_Form_Element {

	/**
	 * @var int Unique element id.
	 * @see Noptin_Form_Output_Manager::$count
	 */
	public $id;

	/**
	 * @var array Shortcode / form-setting args.
	 * @see Noptin_Form_Output_Manager::shortcode()
	 */
	public $args;

	/**
	 * Class constructor.
	 *
	 * @param int $id Unique element id.
	 * @param array $config Shortcode / form-setting args.
	 */
	public function __construct( $id, $args = array() ) {
		$this->id     = $id;

		/**
		 * Filters the arguments used to generate a newsletter subscription form.
		 *
		 * @since 1.6.2
		 *
		 * @param array $args Passed arguments.
		 * @param Noptin_Form_Element $form_element Form element.
		 */
		$this->args = apply_filters( 'noptin_subscription_form_args', $args, $this );
	}

	/**
	 * Gets the form response string
	 *
	 * @param boolean $force_show
	 * @return string
	 */
	public function get_response_html() {
		return noptin()->forms->listener->get_response_html();
	}

	/**
	 * @return string
	 */
	protected function get_response_position() {
		$position = 'after';
		$content  = $this->before_fields_content() . $this->after_fields_content();

		// check if content contains {response} tag
		if ( stripos( $content, '{response}' ) !== false ) {
			return '';
		}

		/**
		 * Filters the position for the form response.
		 *
		 * Valid values are "before" and "after". Will have no effect if `{response}` is used in the form content.
		 *
		 * @param string $position
		 * @param Noptin_Form_Element $form_element
		 * @since 1.6.2
		 */
		return (string) apply_filters( 'noptin_form_response_position', $position, $this );

	}

	/**
	 * Retrieves the content to display before opt-in fields.
	 *
	 * @return string
	 */
	protected function before_fields_content() {
		$content = isset( $this->args['before_fields'] ) ? $this->args['before_fields'] : '';

		/**
		 * Filters the content displayed before displaying the form fields.
		 *
		 * @since 1.6.2
		 *
		 * @param string $content Content to display.
		 * @param Noptin_Form_Element $form_element Form element.
		 */
		return apply_filters( 'noptin_before_fields_content', $content, $this );

	}

	/**
	 * Displays additional content before normal form fields.
	 *
	 * @return string
	 */
	protected function before_fields() {

		// Maybe display content before form fields.
		echo wp_kses_post( $this->before_fields_content() );

		// Maybe display submission response.
		if ( $this->get_response_position() === 'before' ) {
			echo wp_kses_post( $this->get_response_html() );
		}

		/**
		 * Fires before displaying form fields.
		 *
		 * @since 1.6.2
		 *
		 * @param Noptin_Form_Element $form_element
		 */
		do_action( 'before_output_noptin_form_fields', $this );

	}

	/**
	 * Displays the form fields.
	 *
	 * @return string
	 */
	protected function display_fields() {

		// Prepare form fields.
		$fields = empty( $this->args['fields'] ) ? 'email' : $this->args['fields'];
		$fields = prepare_noptin_form_fields( $fields );
		$wrap   = empty( $this->args['wrap'] ) ? 'p' : sanitize_html_class( $this->args['wrap'] );

		echo '<div class="noptin-form-fields">';

		// For each form field...
		foreach ( $fields as $custom_field ) {

			// Wrap the HTML name field into noptin_fields[ $merge_tag ];
			$custom_field['wrap_name'] = true;

			// Set matching id.
			$custom_field['id'] = sanitize_html_class( $this->args['html_id'] . '__field-' . $custom_field['merge_tag'] );

			/**
			 * Fires before displaying a single newsletter subscription form field.
			 *
			 * @since 1.6.2
			 *
			 * @param array $custom_field
			 * @param Noptin_Form_Element $form_element
			 */
			do_action( 'before_output_noptin_form_field', $custom_field, $this );

			// Display the opening wrapper.
			$this->display_opening_wrapper( $custom_field['merge_tag'], $custom_field );

			// Display the actual form field.
			display_noptin_custom_field_input( $custom_field );

			// Display the closing wrapper.
			$this->display_closing_wrapper( $custom_field, $custom_field );

			/**
			 * Fires after displaying a single newsletter subscription form field.
			 *
			 * @since 1.6.2
			 *
			 * @param array $custom_field
			 * @param Noptin_Form_Element $form_element
			 */
			do_action( 'output_noptin_form_field', $custom_field, $this );

		}

		// (Maybe) display an acceptance field.
		if ( '' !== trim( $this->args['acceptance'] ) ) {

			// Display the opening wrapper.
			$this->display_opening_wrapper( 'consent' );

			?>

			<label>
				<input
					name="GDPR_consent"
					id="<?php echo esc_attr( $custom_field['id'] ); ?>"
					type='checkbox'
					value='1'
					class='noptin-checkbox-form-field'
					required
				/><span><?php echo wp_kses_post( trim( $this->args['acceptance'] ) ); ?></span>
			</label>

			<?php
			// Display the closing wrapper.
			$this->display_closing_wrapper( 'consent' );

		}

		$this->display_submit_button( $wrap );

		echo '</div>';

	}

	/**
	 * Displays the submit button.
	 *
	 */
	protected function display_submit_button() {

		/**
		 * Fires before displaying the newsletter subscription form submit button.
		 *
		 * @since 1.6.0
		 *
		 * @param Noptin_Form_Element $form_element
		 */
		do_action( 'before_output_noptin_form_submit_button', $this );

		// Opening wrapper.
		$this->display_opening_wrapper( 'submit' );

		// Print the submit button.
		$button_atts = array(
			'type'  => 'submit',
			'id'    => sanitize_html_class( $this->args['html_id'] . '__submit' ),
			'class' => 'noptin-form-submit btn button btn-primary button-primary wp-element-button',
			'name'  => 'noptin-submit',
		);

		$button_text = empty( $this->args['submit'] ) ? __( 'Subscribe', 'newsletter-optin-box' ) : $this->args['submit'];

		?>

			<button <?php noptin_attr( 'form_submit', $button_atts, $this->args ); ?>><?php echo esc_html( $button_text ); ?></button>

		<?php

		// Closing wrapper.
		$this->display_closing_wrapper( 'submit' );

		/**
		 * Fires after displaying the newsletter subscription form submit button.
		 *
		 * @since 1.6.0
		 *
		 * @param Noptin_Form_Element $form_element
		 */
		do_action( 'output_noptin_form_submit_button', $this );

	}

	/**
	 * Prints the opening wrapper.
	 *
	 * @param string $field_key Field key
	 * @param array $extra_args Extra args parsed to hooks.
	 */
	protected function display_opening_wrapper( $field_key, $extra_args = array() ) {

		$args = array(
			'id'    => sanitize_html_class( $this->args['html_id'] . '__' . $field_key . '--wrapper' ),
			'class' => 'noptin-form-field-wrapper noptin-form-field-' . sanitize_html_class( $field_key ),
		);

		$wrap = empty( $this->args['wrap'] ) ? 'p' : sanitize_html_class( $this->args['wrap'] );

		do_action( 'before_output_opening_noptin_form_field_wrapper', $field_key, $extra_args, $this );

		?>
			<<?php echo esc_html( $wrap ); ?> <?php noptin_attr( 'form_field_wrapper', $args, array( $this->args, $extra_args ) ); ?>>
		<?php

		do_action( 'after_output_opening_noptin_form_field_wrapper', $field_key, $extra_args, $this );
	}

	/**
	 * Prints the closing wrapper.
	 *
	 * @param string $field_key Field key
	 * @param array $extra_args Extra args parsed to hooks.
	 */
	protected function display_closing_wrapper( $field_key, $extra_args = array() ) {
		do_action( 'before_output_closing_noptin_form_field_wrapper', $field_key, $extra_args, $this );

		echo '</' . esc_html( empty( $this->args['wrap'] ) ? 'p' : sanitize_html_class( $this->args['wrap'] ) ) . '>';

		do_action( 'after_output_closing_noptin_form_field_wrapper', $field_key, $extra_args, $this );
	}

	/**
	 * Retrieves the content to display after opt-in fields.
	 *
	 * @return string
	 */
	protected function after_fields_content() {
		$content = isset( $this->args['after_fields'] ) ? $this->args['after_fields'] : '';

		/**
		 * Filters the content displayed after displaying the form fields.
		 *
		 * @since 1.6.2
		 *
		 * @param string $content Content to display.
		 * @param Noptin_Form_Element $form_element Form element.
		 */
		return apply_filters( 'noptin_after_fields_content', $content, $this );

	}

	/**
	 * Get HTML to be added _after_ the HTML of the form fields.
	 *
	 * @return string
	 */
	protected function after_fields() {

		// Maybe display content after form fields.
		echo wp_kses_post( $this->after_fields_content() );

		// Maybe display submission response.
		if ( $this->get_response_position() === 'after' ) {
			echo wp_kses_post( $this->get_response_html() );
		}

		/**
		 * Fires after displaying form fields.
		 *
		 * @since 1.6.2
		 *
		 * @param Noptin_Form_Element $form_element
		 */
		do_action( 'after_output_noptin_form_fields', $this );

	}

	/**
	 * @return string
	 */
	protected function display_hidden_fields() {

		// Display standard fields.
		noptin_hidden_field( 'noptin_nonce', wp_create_nonce( 'noptin_subscription_nonce' ) );
		noptin_hidden_field( 'conversion_page', noptin_get_request_url() );
		noptin_hidden_field( 'action', 'noptin_process_ajax_subscriber' );
		noptin_hidden_field( 'noptin_process_request', '1' );
		noptin_hidden_field( 'noptin_timestamp', time() );
		noptin_hidden_field( 'noptin_element_id', $this->id );
		noptin_hidden_field( 'noptin_unique_id', $this->args['unique_id'] );
		noptin_hidden_field( 'source', $this->args['source'] );
		noptin_hidden_field( 'form_action', empty( $this->args['is_unsubscribe'] ) ? 'subscribe' : 'unsubscribe' );

		// Honeypot.
		?>
			<label style="display: none !important;">Leave this field empty if you're not a robot: <input type="text" name="noptin_ign" value="" tabindex="-1" autocomplete="off" /></label>
		<?php

	}

	/**
	 * Generates the HTML for this form element.
	 *
	 * @return string
	 */
	public function generate_html() {

		ob_start();
		$this->display();
		return ob_get_clean();

	}

	/**
	 * Displays this form element.
	 *
	 * @return string
	 */
	public function display() {

		/**
		 * Runs just before displaying a newsletter subscription form.
		 *
		 * @since 1.6.2
		 *
		 * @param Noptin_Form_Element $form_element
		 */
		do_action( 'before_output_noptin_form', $this );

		// Display the opening comment.
		echo '<!-- Noptin Newsletter Plugin v' . esc_html( noptin()->version ) . ' - https://wordpress.org/plugins/newsletter-optin-box/ -->';

		// Display the opening form tag.
		echo '<form ';

		noptin_attr(
			'form',
			array(
				'id'         => $this->args['html_id'],
				'class'      => $this->get_css_classes(),
				'name'       => empty( $this->args['html_name'] ) ? false : $this->args['html_name'],
				'method'     => 'post',
				'novalidate' => true,
				'data-id'    => absint( $this->id ),
			),
			$this
		);

		echo '>';

		// Display additional content before form fields.
		$this->before_fields();

		// Display form fields.
		$this->display_fields();

		// Display hidden fields.
		$this->display_hidden_fields();

		// Display additional content after form fields.
		$this->after_fields();

		// Loading spinner.
		echo '<div class="noptin-loader"><span></span></div>';

		// Close the output.
		echo '</form><!-- / Noptin Newsletter Plugin -->';

		/**
		 * Runs just after displaying a newsletter subscription form.
		 *
		 * @since 1.6.2
		 *
		 * @param Noptin_Form_Element $form_element
		 */
		do_action( 'after_output_noptin_form', $this );

	}

	/**
	 * Get a space separated list of CSS classes for this form
	 *
	 * @return string
	 */
	protected function get_css_classes() {
		$classes = array();

		// Base classes.
		$classes[] = 'noptin-newsletter-form';
		$classes[] = 'noptin-form';
		$classes[] = 'noptin-form-' . absint( $this->id );
		$classes[] = 'noptin-form-source-' . sanitize_html_class( $this->get_source() );

		// Add form classes if this specific form element was submitted.
		if ( $this->was_submitted() ) {
			$classes[] = 'noptin-form-submitted';

			if ( ! $this->has_errors() ) {
				$classes[] = 'noptin-has-success';
			} else {
				$classes[] = 'noptin-has-error';
			}
		}

		// Labels ( hidden / top / side ).
		if ( isset( $this->args['labels'] ) ) {
			$classes[] = 'noptin-label-' . sanitize_html_class( $this->args['labels'] );
		}

		// Styles ( none / basic / full ).
		if ( isset( $this->args['styles'] ) ) {
			$classes[] = 'noptin-styles-' . sanitize_html_class( $this->args['styles'] );
		}

		// Template.
		if ( isset( $this->args['template'] ) ) {
			$classes[] = 'noptin-template-' . sanitize_html_class( $this->args['template'] );
		}

		// Add classes from args.
		if ( ! empty( $this->args['html_class'] ) ) {
			$classes = array_merge( $classes, noptin_parse_list( $this->args['html_class'] ) );
		}

		/**
		 * Filters `class` attributes for the `<form>` element.
		 *
		 * @param array $classes
		 * @param Noptin_Form_Element $element
		 */
		$classes = apply_filters( 'noptin_form_css_classes', $classes, $this );

		return implode( ' ', $classes );
	}

	/**
	 * Checks if the form has any errors.
	 *
	 * @return bool
	 */
	public function has_errors() {
		return $this->was_submitted() && $this->get_listener()->error->has_errors();
	}

	/**
	 * Checks if the form was submitted in this request.
	 *
	 * @return bool
	 */
	public function was_submitted() {
		return $this->get_listener()->processed_form === $this->id;
	}

	/**
	 * Returns the listener instance.
	 *
	 * @return Noptin_Form_Listener
	 */
	public function get_listener() {
		return noptin()->forms->listener;
	}

	/**
	 * Returns the source for submissions of this element.
	 *
	 * @return string|int
	 */
	public function get_source() {

		if ( empty( $this->args['source'] ) ) {
			return 'shortcode';
		}

		return is_numeric( $this->args['source'] ) ? absint( $this->args['source'] ) : sanitize_text_field( $this->args['source'] );
	}

}
