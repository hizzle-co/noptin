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
		// enable shortcodes in form content
		add_filter( 'noptin_before_fields_content', 'do_shortcode', 14 );
		add_filter( 'noptin_after_fields_content', 'do_shortcode', 14 );
		add_action( 'init', array( $this, 'register_shortcode' ) );
	}

	/**
	 * Registers the [noptin] shortcode
	 */
	public function register_shortcode() {
		add_shortcode( self::$shortcode, array( $this, 'shortcode' ) );
	}

	/**
	 * Returns the default `[noptin]` shortcode attributes.
	 *
	 * @since 1.6.2
	 * @return array
	 */
	public function get_default_shortcode_atts() {

		return array(
			'fields'        => 'email', // Comma separated array of fields, or all
			'source'        => 'shortcode', // Manual source of the subscriber. Can also be a form id.
			'labels'        => 'hide', // Whether or not to show the field label.
			'wrap'          => 'p', // Which element to wrap field values in.
			'styles'        => 'basic', // Set to inherit to inherit theme styles.
			'before_fields' => '', // Content to display before form fields.
			'after_fields'  => '', // Content to display after form fields.
			'html_id'       => '', // ID of the form (auto-generated if not provided).
			'html_name'     => '', // HTML name of the form.
			'html_class'    => '', // HTML class of the form.
			'redirect'      => '', // An optional URL to redirect users after successful subscriptions.
			'success_msg'   => '', // Overide the success message shown to users after successful subscriptions.
			'submit'        => __( 'Subscribe', 'newsletter-optin-box' ),
			'template'      => 'normal',
		);

	}

	/**
	 * Returns the connections `[noptin]` shortcode attributes.
	 *
	 * @since 1.6.2
	 * @return array
	 */
	public function get_connections_shortcode_atts() {
		$atts = array();

		foreach ( get_noptin_connection_providers() as $key => $connection ) {

			if ( empty( $connection->list_providers ) ) {
				continue;
			}

			$atts["{$key}_list"] = $connection->get_default_list_id();

			if ( $connection->supports( 'tags' ) ) {
				$atts["{$key}_tags"] = get_noptin_option( "noptin_{$key}_default_tags", '' );
			}

			// Secondary fields.
			foreach ( array_keys( $connection->list_providers->get_secondary() ) as $secondary ) {
				$atts["{$key}_$secondary"] = get_noptin_option( "noptin_{$key}_default_{$secondary}", '' );
			}

		}

		return $atts;
	}

	/**
	 * @param array $attributes
	 * @param string $content
	 * @return string
	 */
	public function shortcode( $atts = array(), $content = '' ) {

		// Maybe merge with saved values.
		if ( isset( $atts['form'] ) ) {

			// Use the form id as the subscriber source.
			$atts['source'] = $atts['form'];

			// Make sure that the form is published.
			$form = get_post( $atts['form'] );

			if ( empty( $form ) || ( ! current_user_can( 'manage_options') && 'publish' !== $form->post_status ) ) {
				return '<p><strong style="color: red;">' . __( 'Form not found', 'newsletter-optin-box' ) . '</strong></p>';
			}

			// Check if this is a legacy form.
			$is_legacy = get_post_meta( $form->ID, '_noptin_state', true );

			if ( ! empty( $is_legacy ) ) {
				$form = new Noptin_Form_Legacy( $form->ID );
				return $form->get_html();
			}

			// Merge form settings with passed attributes.
			$form_settings = get_post_meta( $form->ID, 'form_settings', true );

			if ( ! empty( $form_settings ) ) {
				$atts = array_merge( $form_settings, $atts );
			}

			// Update view count.
			increment_noptin_form_views( $form->ID );
		}

		// Prepare default attributes.
		$default_atts = array_merge(
			$this->get_default_shortcode_atts(),
			$this->get_connections_shortcode_atts()
		);

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

		return $this->get_form_html( $atts );
	}

	/**
	 * Displays an optin form based on the passed args.
	 *
	 * @param array $args The args with which to display the opt-in form.
	 *
	 * @return string
	 */
	protected function get_form_html( $args = array() ) {

		// Increment count.
		$this->count++;

		// Maybe force a form id.
		$args['html_id'] = empty( $args['html_id'] ) ? 'noptin-form-' . absint( $this->count ) : $args['html_id'];

		// Set a unique id for these args.
		$args['unique_id'] = 'noptin_frm_' . md5( wp_json_encode( $args ) );

		// (Maybe) cache this instance.
		$saved = get_option( $args['unique_id'] );

		if ( ! $saved ) {
			update_option( $args['unique_id'], $args, true );
		}

		// Generate the form HTML.
		$element = new Noptin_Form_Element( $this->count, $args );

		/**
		 * Filters the generated HTML markup of a newsletter subscription form.
		 *
		 * @since 1.6.2
		 *
		 * @param string $form_html The HTML markup.
		 * @param Noptin_Form_Element $form_element Form element.
		 * @param Noptin_Form_Output_Manager $output_manager Output manager.
		 */
		$html = apply_filters( 'noptin_form_html', $element->generate_html(), $element, $this );

		return $html;

	}

}
