<?php
/**
 * Forms API: Forms output manager.
 *
 * Keeps track of each opt-in form we display.
 *
 * @since   1.6.2
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Keeps track of each opt-in form we display.
 *
 * @internal
 * @access private
 * @since 1.6.2
 * @ignore
 */
class Noptin_Form_Output_Manager {

	/**
	 * @var int The # of forms outputted
	 */
	public $count = 0;

	/**
	 * @var string
	 */
	public static $shortcode = 'noptin';

	/**
	 * Add hooks
	 */
	public function add_hooks() {
		// enable shortcodes in form content.
		add_filter( 'noptin_before_fields_content', 'do_shortcode', 14 );
		add_filter( 'noptin_after_fields_content', 'do_shortcode', 14 );
		add_action( 'init', array( $this, 'register_shortcode' ) );
	}

	/**
	 * Registers the [noptin] shortcode
	 */
	public function register_shortcode() {
		add_shortcode( self::$shortcode, array( $this, 'shortcode' ) );
		add_shortcode( 'noptin-form', array( $this, 'legacy_shortcode' ) ); // legacy shortcode.
	}

	/**
	 * Returns the default `[noptin]` shortcode attributes.
	 *
	 * @since 1.6.2
	 * @return array
	 */
	public function get_default_shortcode_atts() {

		$atts = array(
			'fields'         => 'email', // Comma separated array of fields, or all
			'source'         => 'shortcode', // Source of the subscriber.
			'labels'         => 'show', // Whether or not to show the field label.
			'wrap'           => 'div', // Which element to wrap field values in.
			'styles'         => 'basic', // Set to inherit to inherit theme styles.
			'before_fields'  => '', // Content to display before form fields.
			'after_fields'   => '', // Content to display after form fields.
			'html_id'        => '', // ID of the form (auto-generated if not provided).
			'html_name'      => '', // HTML name of the form.
			'html_class'     => '', // HTML class of the form.
			'redirect'       => '', // An optional URL to redirect users after successful subscriptions.
			'acceptance'     => '', // Optional terms of service text.
			'submit'         => __( 'Subscribe', 'newsletter-optin-box' ),
			'template'       => 'normal',
			'is_unsubscribe' => '',
		);

		foreach ( array_keys( get_default_noptin_form_messages() ) as $msg ) {
			$atts[ $msg ] = '';
		}

		return $atts;
	}

	/**
	 * @deprecated
	 */
	public function legacy_shortcode( $atts ) {

		// Abort early if no id is specified
		if ( empty( $atts['id'] ) ) {
			return '';
		}

		return $this->shortcode( array( 'form' => $atts['id'] ) );
	}

	/**
	 * @param array $attributes
	 * @param string $content
	 * @return string
	 */
	public function shortcode( $atts = array(), $content = '' ) {
		ob_start();
		$this->display_form( $atts );
		return ob_get_clean();
	}

	/**
	 * Displays an optin form based on the passed args.
	 *
	 * @param array $atts The atts with which to display the opt-in form.
	 */
	public function display_form( $atts = array() ) {

		if ( ! is_array( $atts ) ) {
			$atts = array();
		}

		// Check if we're displaying a form shortcode, that way, no need to cache args.
		$is_one_att        = count( $atts ) === 1 || ! empty( $atts['className'] ) || ! empty( $atts['title'] );
		$is_form_shortcode = false;

		// Backwards compatibility.
		if ( isset( $atts['success_msg'] ) ) {
			$atts['success'] = $atts['success_msg'];
		}

		// Blocks.
		if ( ! empty( $atts['className'] ) ) {
			$atts['html_class'] = isset( $atts['html_class'] ) ? $atts['html_class'] . ' ' . $atts['className'] : $atts['className'];
		}

		if ( isset( $atts['form'] ) && -1 === (int) $atts['form'] ) {
			unset( $atts['form'] );
			$atts = array_merge(
				array(
					'template' => 'condensed',
					'labels'   => 'hide',
				),
				$atts
			);
		}

		// Are we trying to display a saved form?
		if ( isset( $atts['form'] ) && ! empty( $atts['form'] ) ) {

			// Maybe display a translated version.
			$atts['form'] = translate_noptin_form_id( (int) $atts['form'] );

			// Abort early if trying to render a legacy form.
			if ( is_legacy_noptin_form( (int) $atts['form'] ) ) {
				$form = new Noptin_Form_Legacy( (int) $atts['form'] );

				if ( $form->can_show() ) {
					$form->display();
				}

				return;
			}

			// Use the form id as the subscriber source.
			$atts['source'] = (int) $atts['form'];

			// Make sure that the form is visible.
			$form = new Noptin_Form( (int) $atts['form'] );

			if ( ! $form->can_show() ) {
				return;
			}

			// Merge form settings with passed attributes.
			$atts = array_merge( $form->settings, $atts );

			// Update view count.
			if ( ! noptin_is_preview() ) {
				increment_noptin_form_views( $form->id );
			}

			$is_form_shortcode = $is_one_att;
		}

		// Prepare default attributes.
		$default_atts = $this->get_default_shortcode_atts();

		if ( ! empty( $atts['is_unsubscribe'] ) ) {
			$default_atts['submit'] = __( 'Unsubscribe', 'newsletter-optin-box' );
		}

		/**
		 * Filters the default [noptin] shortcode attributes.
		 *
		 * @since 1.6.2
		 *
		 * @param array $default_atts The default [noptin] shortcode attributes.
		 * @param array $atts The user-provided [noptin] shortcode attributes.
		 * @param Noptin_Form_Output_Manager $output_manager Output manager.
		 */
		$default_atts = apply_filters( 'default_noptin_shortcode_atts', $default_atts, $atts );
		$atts         = shortcode_atts( $default_atts, $atts, self::$shortcode );

		return $this->render_form( $atts, $is_form_shortcode );
	}

	/**
	 * Displays an optin form based on the passed args.
	 *
	 * @param array $args The args with which to display the opt-in form.
	 * @param bool $is_form_shortcode
	 *
	 * @return string
	 */
	protected function render_form( $args = array(), $is_form_shortcode = false ) {

		// Increment count.
		$this->count++;

		// Maybe force a form id.
		$args['html_id'] = empty( $args['html_id'] ) ? 'noptin-form-' . absint( $this->count ) : $args['html_id'];

		// Set a unique id for these args.
		$args['unique_id'] = 'noptin_frm_' . md5( wp_json_encode( $args ) );

		// (Maybe) cache this instance.
		if ( ! $is_form_shortcode && ! get_option( $args['unique_id'] ) ) {
			update_option( $args['unique_id'], $args, true );
		}

		// Generate the form HTML.
		$element = new Noptin_Form_Element( $this->count, $args );

		// Display the form.
		$element->display();

	}

}
