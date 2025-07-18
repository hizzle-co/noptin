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
 * Checks whether or not this is a new or legacy form.
 *
 * @since 1.6.2
 * @param int $form_id
 * @return bool
 */
function is_legacy_noptin_form( $form_id ) {

	// Check if it was created by the legacy editor.
	$state = get_post_meta( $form_id, '_noptin_state', true );

	if ( is_object( $state ) ) {
		$state = (array) $state;
	}

	if ( is_array( $state ) && ! empty( $state['fields'] ) ) {
		return true;
	}

	// Or the new editor.
	if ( '' !== get_post_meta( $form_id, 'form_settings', true ) ) {
		return false;
	}

	// This is a new form.
	return true;
}

/**
 * Generates the markup for displaying a subscription form.
 *
 * @param array $args Form args.
 * @since 1.6.0
 * @return string
 * @deprecated
 * @see show_noptin_form
 */
function get_noptin_subscription_form_html( $args ) {
	return show_noptin_form( $args, false );
}

/**
 * Displays a subscription form.
 *
 * @param array $args Form args.
 * @since 1.6.0
 * @deprecated
 * @see show_noptin_form
 */
function display_noptin_subscription_form( $args ) {
	show_noptin_form( $args );
}

/**
 * Build list of attributes into a string and apply contextual filter on string.
 *
 * The contextual filter is of the form `noptin_{context}_attributes.
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

	foreach ( $attributes as $name => $value ) {

		if ( ! $value ) {
			continue;
		}

		if ( 'class' === $name && is_array( $value ) ) {
			$value = implode( ' ', array_filter( $value ) );
		}

		if ( is_array( $value ) || is_object( $value ) ) {
			$value = wp_json_encode( $value );
		}

		if ( true === $value ) {
			echo esc_html( $name ) . ' ';
		} else {
			printf( '%s="%s" ', esc_html( $name ), esc_attr( trim( $value ) ) );
		}
	}
}

/**
 * Returns opt-in forms field types
 *
 * @return  array
 * @access  public
 * @since   1.0.8
 */
function get_noptin_optin_field_types() {
	$field_types = array();
	foreach ( get_noptin_custom_fields( true ) as $custom_field ) {

		$field_types[] = array(
			'label'            => $custom_field['label'],
			'type'             => $custom_field['merge_tag'],
			'supports_label'   => true,
			'supports_require' => true,
		);
	}

	return apply_filters( 'noptin_field_types', $field_types );
}

/**
 * Retrieves an optin form.
 *
 * @param int|Hizzle\Noptin\Forms\Form $id The id or Hizzle\Noptin\Forms\Form object of the optin to retrieve.
 * @since 1.0.5
 * @return Hizzle\Noptin\Forms\Form
 */
function noptin_get_optin_form( $id ) {
	return new Hizzle\Noptin\Forms\Form( $id );
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

		$sql    = $wpdb->prepare( $sql, $type ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$where .= " AND meta.meta_key='_noptin_optin_type'";
	}

	return $wpdb->get_var( "$sql $where;" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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

/**
 * Displays a subscription form.
 *
 * This is the correct way to display a subscription form. Do not call
 * class members directly.
 *
 * @param int|array $form_id_or_configuration An id of a saved form or an array of arguments with which to generate a form on the fly.
 * @param bool $display Whether to display the form or return its HTML.
 * @return string
 * @since 1.6.2
 */
function show_noptin_form( $form_id_or_configuration = array(), $display = true ) {
	// If a form id was passed, convert it into arguments.
	if ( is_numeric( $form_id_or_configuration ) ) {
		$form_id_or_configuration = array( 'form' => (int) $form_id_or_configuration );
	}

	// Ensure we have an array.
	if ( ! is_array( $form_id_or_configuration ) ) {
		$form_id_or_configuration = array();
	}

	// Generate the form markup.
	if ( ! $display ) {
		return \Hizzle\Noptin\Forms\Renderer::shortcode( $form_id_or_configuration );
	}

	\Hizzle\Noptin\Forms\Renderer::display_form( $form_id_or_configuration );
}

/**
 * Increments a form's view count.
 *
 * @param int $form_id An id of a saved form.
 * @param int $by The number of views to add to the form.
 * @see Noptin_Form_Manager::shortcode
 * @return string
 * @since 1.6.2
 */
function increment_noptin_form_views( $form_id, $by = 1 ) {

	$form_id = intval( $form_id );
	$by      = intval( $by );
	$count   = (int) get_post_meta( $form_id, '_noptin_form_views', true );
	update_post_meta( $form_id, '_noptin_form_views', $count + $by );
}

/**
 * Displays a hidden field.
 *
 * @param string $name Field name.
 * @param string $value Field value.
 * @since 1.6.2
 */
function noptin_hidden_field( $name, $value ) {
	printf(
		'<input type="hidden" name="%s" value="%s" />',
		esc_attr( $name ),
		esc_attr( $value )
	);
}

/**
 * Prepares form fields.
 *
 * @param string|array $fields Form fields.
 * @return array
 * @since 1.6.2
 */
function prepare_noptin_form_fields( $fields ) {

	// Ensure we have form fields.
	if ( empty( $fields ) ) {
		$fields = 'email';
	}

	// Are we returning all fields?
	if ( 'all' === $fields ) {
		return get_noptin_custom_fields( true );
	}

	// Prepare selected fields.
	$prepared = array();

	foreach ( noptin_parse_list( $fields ) as $custom_field ) {

		// Users can pass the merge tag instead of custom field data.
		if ( is_string( $custom_field ) ) {
			$custom_field = get_noptin_custom_field( $custom_field );
		} elseif ( is_array( $custom_field ) && ! empty( $custom_field['type'] ) ) {
			// Backwards compatibility for custom fields.
			if ( is_array( $custom_field['type'] ) ) {
				$custom_field = $custom_field['type'];
			}

			$_custom_field = $custom_field;
			$custom_field  = get_noptin_custom_field( $custom_field['type'] );

			// Abort if this is not a valid custom field.
			if ( empty( $custom_field ) ) {
				continue;
			}

			if ( ! empty( $_custom_field['label'] ) ) {
				$custom_field['label'] = $_custom_field['label'];
			}

			if ( isset( $_custom_field['required'] ) ) {
				$custom_field['required'] = $_custom_field['required'];
			}
		}

		if ( ! empty( $custom_field ) ) {
			$prepared[] = $custom_field;
		}
	}

	return $prepared;
}

/**
 * Returns an array of default form messages.
 *
 * @return array
 * @since 1.6.2
 */
function get_default_noptin_form_messages() {

	return apply_filters(
		'default_noptin_form_messages',
		array(
			'success'                => array(
				'label'       => __( 'Successfully subscribed', 'newsletter-optin-box' ),
				'description' => __( 'Shown when someone successfully fills the form.', 'newsletter-optin-box' ),
				'default'     => __( 'Thanks for subscribing to the newsletter.', 'newsletter-optin-box' ),
				'conditions'  => array(
					array(
						'key'   => 'subscribeAction',
						'value' => 'message',
					),
					array(
						'key'   => 'is_unsubscribe',
						'value' => false,
					),
				),
			),
			'invalid_email'          => array(
				'label'       => __( 'Invalid email address', 'newsletter-optin-box' ),
				'description' => __( 'Shown when someone enters an invalid email address.', 'newsletter-optin-box' ),
				'default'     => __( 'Please provide a valid email address.', 'newsletter-optin-box' ),
			),
			'required_field_missing' => array(
				'label'       => __( 'Required field missing', 'newsletter-optin-box' ),
				'description' => __( 'Shown when someone does not fill all required fields.', 'newsletter-optin-box' ),
				'default'     => __( 'Please fill in all the required fields.', 'newsletter-optin-box' ),
			),
			'accept_terms'           => array(
				'label'       => __( 'Terms not Accepted', 'newsletter-optin-box' ),
				'description' => __( 'Shown when someone does not check the acceptance checkbox.', 'newsletter-optin-box' ),
				'default'     => __( 'Please accept the terms and conditions first.', 'newsletter-optin-box' ),
			),
			'already_subscribed'     => array(
				'label'       => __( 'Already subscribed', 'newsletter-optin-box' ),
				'description' => __( 'Shown when an existing subscriber tries to sign-up again.', 'newsletter-optin-box' ),
				'default'     => __( 'You are already subscribed to the newsletter, thank you!', 'newsletter-optin-box' ),
				'conditions'  => array(
					array(
						'key'   => 'is_unsubscribe',
						'value' => false,
					),
				),
			),
			'error'                  => array(
				'label'       => __( 'Generic error', 'newsletter-optin-box' ),
				'description' => __( 'Shown when a generic error occurs.', 'newsletter-optin-box' ),
				'default'     => __( 'Oops. Something went wrong. Please try again later.', 'newsletter-optin-box' ),
			),
			'unsubscribed'           => array(
				'label'       => __( 'Unsubscribed', 'newsletter-optin-box' ),
				'description' => __( 'Shown when an existing subscriber unsubscribes via this form.', 'newsletter-optin-box' ),
				'default'     => __( 'You were successfully unsubscribed.', 'newsletter-optin-box' ),
				'conditions'  => array(
					array(
						'key'   => 'is_unsubscribe',
						'value' => true,
					),
				),
			),
			'not_subscribed'         => array(
				'label'       => __( 'Not subscribed', 'newsletter-optin-box' ),
				'description' => __( 'Shown when someone unsubscribes with an email that is not already subscribed.', 'newsletter-optin-box' ),
				'default'     => __( 'The given email address is not subscribed.', 'newsletter-optin-box' ),
				'conditions'  => array(
					array(
						'key'   => 'is_unsubscribe',
						'value' => true,
					),
				),
			),
			'updated'                => array(
				'label'       => __( 'Updated', 'newsletter-optin-box' ),
				'description' => __( 'Shown when an existing subscriber updates their details via this form.', 'newsletter-optin-box' ),
				'default'     => __( 'Thank you, your details have been updated.', 'newsletter-optin-box' ),
				'conditions'  => array(
					array(
						'key'   => 'update_existing',
						'value' => true,
					),
					array(
						'key'   => 'is_unsubscribe',
						'value' => false,
					),
				),
			),
		)
	);
}

/**
 * Returns a form message.
 *
 * @param string $key Message key.
 * @param string $fallback The fallback message to use.
 * @see get_default_noptin_form_messages()
 * @return array
 * @since 1.6.2
 */
function get_noptin_form_message( $key, $fallback = '' ) {

	// Retrieve from options.
	$message = get_noptin_option( $key . '_message' );

	if ( ! empty( $message ) ) {
		return $message;
	}

	// Retrieve from get_default_noptin_form_messages()
	$messages = get_default_noptin_form_messages();

	if ( isset( $messages[ $key ] ) ) {
		return $messages[ $key ]['default'];
	}

	return $fallback;
}

/**
 * Translates a form id to match the current site's language.
 *
 * @param int $form_id Current form id.
 * @return int Translated form id.
 * @since 1.6.2
 */
function translate_noptin_form_id( $form_id ) {

	// Do not translate previews.
	if ( ! empty( $_GET['legacy-widget-preview'] ) || defined( 'IS_NOPTIN_PREVIEW' ) || ( ! empty( $GLOBALS['wp']->query_vars['rest_route'] ) && false !== strpos( $GLOBALS['wp']->query_vars['rest_route'], 'noptin_widget_premade' ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return $form_id;
	}

	return apply_filters( 'translate_noptin_form_id', $form_id );
}

/**
 * Returns the default privacy text for forms.
 *
 * @return string
 * @since 1.6.2
 */
function get_default_noptin_form_privacy_text() {
	$privacy_policy_url = get_privacy_policy_url();

	if ( empty( $privacy_policy_url ) ) {
		$privacy_policy_url = 'https://noptin.com/privacy-policy/';
	}

	return sprintf(
		// translators: %1$s and %2$s are opening and closing link tags to the privacy policy, respectively.
		__( 'By subscribing, you agree to our %1$sprivacy policy%2$s and terms of service.', 'newsletter-optin-box' ),
		sprintf(
			'<a class="privacy-policy-link" href="%s" rel="privacy-policy">',
			esc_url( $privacy_policy_url )
		),
		'</a>'
	);
}
