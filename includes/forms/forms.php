<?php
/**
 * Forms API: Form functions
 *
 * Contains functions for manipulating Noptin forms
 *
 * @since             1.6.0
 * @package           Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Are we using the new forms?
 *
 * @since 1.6.1
 * @return bool
 */
function is_using_new_noptin_forms() {
	$new_form = get_option( 'noptin_use_new_forms' );
	return apply_filters( 'is_using_new_noptin_forms', ! empty( $new_form ) );
}
add_filter( 'is_using_new_noptin_forms', '__return_true' );

/**
 * Generates the markup for displaying a subscription form.
 *
 * @param array $args Form args.
 * @since 1.6.0
 * @return string
 */
function get_noptin_subscription_form_html( $args ) {
	ob_start();
	display_noptin_subscription_form( $args );
	return ob_get_clean();
}

/**
 * Displays a subscription form.
 *
 * @param array $args Form args.
 * @since 1.6.0
 */
function display_noptin_subscription_form( $args ) {
	$GLOBALS['noptin_load_scripts' ] = 1;

	/**
	 * Filters the arguments used to generate a newsletter subscription form.
	 *
	 * @since 1.6.0
	 *
	 * @param array $args Passed arguments.
	 */
	$args = apply_filters( 'noptin_subscription_form_args', $args );

	// Label position.
	$args['html_class'] .= ' noptin-label-' . $args['labels'];
	$args['html_class'] .= ' noptin-styles-' . $args['styles'];
	$args['html_class'] .= ' noptin-template-' . $args['template'];

	// Format form fields.
	if ( 'all' == $args['fields'] ) {
		$args['fields'] = wp_list_filter( map_deep( get_noptin_custom_fields(), 'noptin_sanitize_booleans' ), array( 'visible' => true ) );
	}

	/**
	 * Runs just before displaying a newsletter subscription form.
	 *
	 * @since 1.6.0
	 *
	 * @param array $args
	 */
	do_action( 'noptin_before_output_form', $args );

	// Display the opening comment.
	echo '<!-- Noptin Newsletter Plugin v' . esc_html( noptin()->version ) . ' - https://wordpress.org/plugins/newsletter-optin-box/ -->';

	// Display the opening form tag.
	printf(
		'<form %s>',
		noptin_attr(
			'form',
			array(
				'id'         => empty( $args['html_id'] ) ? false : $args['html_id'],
				'class'      => 'noptin-newsletter-form ' . trim( $args['html_class'] ),
				'name'       => empty( $args['html_name'] ) ? false : $args['html_name'],
				'method'     => 'post',
				'novalidate' => true,
			),
			$args
		)
	);

	// Display form title.
	if ( ! empty( $args['title'] ) ) {

		/**
		 * Fires before displaying the newsletter subscription form title.
		 *
		 * @since 1.6.0
		 *
		 * @param array $args
		 */
		do_action( 'noptin_before_output_form_title', $args );

		printf(
			'<h3 %s>%s</h3>',
			noptin_attr(
				'form_title',
				array(
					'id'     => empty( $args['html_id'] ) ? false : $args['html_id'] . '_title',
					'class'  => 'noptin-form-title',
				),
				$args
			),
			wp_kses_post( $args['title'] )
		);

		/**
		 * Fires after displaying the newsletter subscription form title.
		 *
		 * @since 1.6.0
		 *
		 * @param array $args
		 */
		do_action( 'noptin_output_form_title', $args );

	}

	// Display form description.
	if ( ! empty( $args['description'] ) ) {

		/**
		 * Fires before displaying the newsletter subscription form description.
		 *
		 * @since 1.6.0
		 *
		 * @param array $args
		 */
		do_action( 'noptin_before_output_form_description', $args );

		printf(
			'<%s %s>%s</%s>',
			$args['wrap'],
			noptin_attr(
				'form_description',
				array(
					'id'     => empty( $args['html_id'] ) ? false : $args['html_id'] . '_description',
					'class'  => 'noptin-form-description',
				),
				$args
			),
			wp_kses_post( $args['description'] ),
			$args['wrap']
		);

		/**
		 * Fires after displaying the newsletter subscription form description.
		 *
		 * @since 1.6.0
		 *
		 * @param array $args
		 */
		do_action( 'noptin_output_form_description', $args );

	}

	/**
	 * Fires before displaying the newsletter subscription form fields.
	 *
	 * @since 1.6.0
	 *
	 * @param array $args
	 */
	do_action( 'noptin_before_output_form_fields', $args );

	// Display form fields.
	echo '<div class="noptin-form-fields">';
		$processed_fields = array();

		// For each form field...
		foreach ( noptin_parse_list( $args['fields'] ) as $custom_field ) {

			// Users can pass the merge tag instead of custom field data.
			if ( is_string( $custom_field ) ) {
				$custom_field = get_noptin_custom_field( $custom_field );
			} else {
				$_custom_field = get_noptin_custom_field( $custom_field['type'] );

				if ( empty( $_custom_field ) ) {
					continue;
				}

				unset( $custom_field['type'] );
				$custom_field  = array_merge( $_custom_field, $custom_field );
			}

			if ( empty( $custom_field ) ) {
				continue;
			}

			// Wrap the HTML name field into noptin_fields[ $merge_tag ];
			$custom_field['wrap_name'] = true;

			// Flag this field as processed.
			$processed_fields[] = $custom_field['merge_tag'];

			/**
			 * Fires before displaying a single newsletter subscription form field.
			 *
			 * @since 1.6.0
			 *
			 * @param array $custom_field
			 * @param array $args
			 */
			do_action( "noptin_before_output_form_field", $custom_field, $args );

			// Display the opening wrapper.
			printf(
				'<%s %s>',
				$args['wrap'],
				noptin_attr(
					'form_field_wrapper',
					array(
						'id'     => empty( $args['html_id'] ) ? false : $args['html_id'] . '_field_' . $custom_field['merge_tag'],
						'class'  => 'noptin-form-field-wrapper noptin-form-field-' . $custom_field['merge_tag'],
					),
					array( $args, $custom_field )
				)
			);

			// Display the actual form field.
			display_noptin_custom_field_input( $custom_field );

			// Display the closing wrapper.
			echo '</' . $args['wrap'] . '>';

			/**
			 * Fires after displaying a single newsletter subscription form field.
			 *
			 * @since 1.6.0
			 *
			 * @param array $custom_field
			 * @param array $args
			 */
			do_action( "noptin_output_form_field", $custom_field, $args );

		}

		/**
		 * Fires before displaying the newsletter subscription form submit button.
		 *
		 * @since 1.6.0
		 *
		 * @param array $args
		 */
		do_action( "noptin_before_output_form_submit_button", $args );

		// Opening wrapper.
		printf(
			'<%s %s>',
			$args['wrap'],
			noptin_attr(
				'form_field_wrapper',
				array(
					'id'     => empty( $args['html_id'] ) ? false : $args['html_id'] . '_field_submit',
					'class'  => 'noptin-form-field-wrapper noptin-form-field-submit',
				),
				array( $args, array() )
			)
		);

		// Print the submit button.
		printf(
			'<button %s>%s</button>',
			noptin_attr(
				'form_submit',
				array(
					'type'   => 'submit',
					'id'     => empty( $args['html_id'] ) ? false : $args['html_id'] . '_submit',
					'class'  => 'noptin-form-submit btn button btn-primary button-primary',
					'name'   => 'noptin-submit',
				),
				$args
			),
			esc_html( $args['submit'] )
		);

		echo '</' . $args['wrap'] . '>';

		/**
		 * Fires after displaying the newsletter subscription form submit button.
		 *
		 * @since 1.6.0
		 *
		 * @param array $args
		 */
		do_action( "noptin_output_form_submit_button", $args );

	echo '</div>';

	/**
	 * Fires after displaying the newsletter subscription form fields.
	 *
	 * @since 1.6.0
	 *
	 * @param array $args
	 */
	do_action( 'noptin_output_form_fields', $args );

	// Display misc data.
	printf( '<input type="hidden" name="source" value="%s" />', esc_attr( $args['source'] ) );
	printf( '<input type="hidden" name="_wpnonce" value="%s" />', wp_create_nonce( 'noptin_subscription_nonce' ) );
	printf( '<input type="hidden" name="conversion_page" value="%s" />', esc_url_raw( add_query_arg( array() ) ) );
	printf( '<input type="hidden" name="action" value="%s" />', 'noptin_process_ajax_subscriber' );
	printf( '<input type="hidden" name="processed_fields" value="%s" />', esc_attr( implode( ', ', $processed_fields ) ) );
	printf( '<label style="display: none !important;">%s <input style="display: none !important;" type="text" name="noptin_ign" value="" tabindex="-1" autocomplete="off" /></label>', __( "Leave this field empty if you're not a bot:", 'newsletter-optin-box' ) );

	if ( ! empty( $args['redirect'] ) ) {
		printf( '<input type="hidden" name="redirect_url" class="noptin_redirect_url" value="%s" />', esc_url_raw( $args['redirect'] ) );
	}

	if ( ! empty( $args['success_msg'] ) ) {
		printf( '<input type="hidden" name="success_msg" class="noptin_success_msg" value="%s" />', esc_attr( $args['success_msg'] ) );
	}

	// Extra attributes.
	$default_keys = array_keys( noptin()->forms->get_default_shortcode_atts() );
	foreach ( $args as $key => $value ) {

		if ( in_array( $key, $default_keys ) || '' === $value ) {
			continue;
		}

		printf(
			'<input type="hidden" name="noptin_misc[%s]" class="noptin_misc_%s" value="%s" />',
			esc_attr( $key ),
			sanitize_html_class( $key ),
			esc_attr( is_scalar( $value ) ? $value : wp_json_encode( $value ) )
		);
	}

	// Display form note.
	if ( ! empty( $args['note'] ) ) {

		/**
		 * Fires before displaying the newsletter subscription form note.
		 *
		 * @since 1.6.0
		 *
		 * @param array $args
		 */
		do_action( 'noptin_before_output_form_note', $args );

		printf(
			'<%s %s>%s</%s>',
			$args['wrap'],
			noptin_attr(
				'form_note',
				array(
					'id'     => empty( $args['html_id'] ) ? false : $args['html_id'] . '_note',
					'class'  => 'noptin-form-note',
				),
				$args
			),
			wp_kses_post( $args['note'] ),
			$args['wrap']
		);

		/**
		 * Fires after displaying the newsletter subscription form note.
		 *
		 * @since 1.6.0
		 *
		 * @param array $args
		 */
		do_action( 'noptin_output_form_note', $args );

	}

	printf(
		'<div %s></div>',
		noptin_attr(
			'form_notice',
			array(
				'id'     => empty( $args['html_id'] ) ? false : $args['html_id'] . '_notice',
				'class'  => 'noptin-form-notice',
				'role'   => 'alert',
			),
			$args
		)
	);

	echo '<div class="noptin-loader"><span></span></div>';

	echo '</form><!-- / Noptin Newsletter Plugin -->';

	/**
	 * Runs just after displaying a newsletter subscription form.
	 *
	 * @since 1.6.0
	 *
	 * @param array $args
	 */
	do_action( 'noptin_output_form', $args );

}

/**
 * Build list of attributes into a string and apply contextual filter on string.
 *
 * The contextual filter is of the form `noptin_{context}_attributes` && `noptin_{$context}_attributes_output`.
 *
 * @since 1.6.0
 *
 * @param string $context    The context, to build filter name.
 * @param array  $attributes Optional. Attributes to display.
 * @param mixed  $args       Optional. Custom data to pass to filter.
 * @return string String of HTML attributes and values.
 */
function noptin_attr( $context, $attributes = array(), $args = array() ) {

	$attributes = apply_filters( "noptin_{$context}_attributes", $attributes, $args, $context );

	$output = '';
	foreach ( $attributes as $name => $value ) {

		if ( ! $value ) {
			continue;
		}

		if ( true === $value ) {
			$output .= esc_html( $name ) . ' ';
		} else {
			$output .= sprintf( '%s="%s" ', esc_html( $name ), trim( esc_attr( $value ) ) );
		}

	}

	return trim( apply_filters( "noptin_{$context}_attributes_output", trim( $output ), $attributes, $args, $context ) );

}

/**
 * Retrieves the URL to the forms creation page
 *
 * @since   1.0.5
 * @return  string
 */
function get_noptin_new_form_url() {
	return noptin()->forms->new_form_url();
}

/**
 * Retrieves the URL to a forms edit url
 *
 * @param int $form_id
 * @since   1.1.1
 * @return  string
 */
function get_noptin_edit_form_url( $form_id ) {
	return noptin()->forms->edit_form_url( $form_id );
}

/**
 * Retrieves the URL to the forms overview page
 *
 * @since   1.0.5
 * @return  string   The forms page url
 */
function get_noptin_forms_overview_url() {
	return noptin()->forms->view_forms_url();
}

/**
 * Returns opt-in forms field types
 *
 * @return  array
 * @access  public
 * @since   1.0.8
 */
function get_noptin_optin_field_types() {
	return apply_filters( 'noptin_field_types', array() );
}

/**
 * Retrieves an optin form.
 *
 * @param int|Noptin_Form $id The id or Noptin_Form object of the optin to retrieve.
 * @since 1.0.5
 * @return Noptin_Form|Noptin_Form_Legacy
 */
function noptin_get_optin_form( $id ) {
	return noptin()->forms->get_form( $id );
}

/**
 * Retrieves the total opt-in forms count.
 *
 * @param string $type Optionally filter by opt-in type.
 * @since 1.0.6
 * @return int
 */
function noptin_count_optin_forms( $type = '' ) {
	global $wpdb;

	$sql   = "SELECT COUNT(`ID`) FROM {$wpdb->posts} as forms";
	$where = "WHERE `post_type`='noptin-form'";

	if ( ! empty( $type ) ) {
		$sql = "$sql LEFT JOIN {$wpdb->postmeta} as meta
			ON meta.post_id = forms.ID
			AND meta.meta_key = '_noptin_optin_type'
			AND meta.meta_value = %s";

		$sql    = $wpdb->prepare( $sql, $type );
		$where .= " AND meta.meta_key='_noptin_optin_type'";
	}

	return $wpdb->get_var( "$sql $where;" );
}

/**
 * Creates an optin form.
 *
 * @since 1.0.5
 * @return WP_Error|int
 */
function noptin_create_optin_form( $data = false ) {
	return noptin()->forms->create_form( $data );
}

/**
 * Deletes an optin form.
 *
 * @since 1.0.5
 */
function noptin_delete_optin_form( $id ) {
	return wp_delete_post( $id, true );
}

/**
 * Duplicates an optin form.
 *
 * @since 1.0.5
 * @return int
 */
function noptin_duplicate_optin_form( $id ) {
	$form = noptin_get_optin_form( $id );
	$form->duplicate();
	return $form->id;
}

/**
 * Returns all optin forms.
 *
 * @since 1.2.6
 * @return Noptin_Form[]|Noptin_Form_Legacy[]
 */
function get_noptin_optin_forms( array $args = array() ) {
	$defaults = array(
		'numberposts' => -1,
		'post_status' => array( 'draft', 'publish' ),
	);

	$args              = wp_parse_args( $args, $defaults );
	$args['post_type'] = 'noptin-form';
	$args['fields']    = 'ids';
	$forms             = get_posts( $args );

	return array_map( 'noptin_get_optin_form', $forms );

}
