<?php
/**
 * Deprecated functions
 *
 * Contains deprecated functionality.
 *
 * @since             2.6.1
 * @package           Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require_once plugin_dir_path( __FILE__ ) . 'class-noptin-form.php';

/**
 * Returns optin templates.
 *
 * @since 1.0.7
 * @return array
 * @deprecated
 */
function noptin_get_optin_templates() {
	$custom_templates  = get_option( 'noptin_templates' );
	$inbuilt_templates = include plugin_dir_path( __FILE__ ) . 'optin-templates.php';

	if ( ! is_array( $custom_templates ) ) {
		$custom_templates = array();
	}

	return array_merge( $custom_templates, $inbuilt_templates );

}

/**
 * Registers the optin forms editor metabox
 *
 * @since 2.6.1
 * @return array
 * @deprecated
 * @internal
 * @ignore
 */
function noptin_register_form_editor_metabox( $post_type ) {

    if ( 'noptin-form' === $post_type ) {
        add_meta_box(
            'noptin_form_editor',
            __( 'Form Editor', 'newsletter-optin-box' ),
            function( $post ) {
                require_once plugin_dir_path( __FILE__ ) . 'class-noptin-form-editor.php';
		        $editor = new Noptin_Form_Editor( $post->ID, true );
		        $editor->output();
            },
            $post_type,
            'normal',
            'high'
        );
    }

}
add_action( 'add_meta_boxes', 'noptin_register_form_editor_metabox' );

/**
 * Function noptin editor localize.
 *
 * @param array $state the current editor state.
 * @since 1.0.5
 * @return void
 * @ignore
 * @deprecated
 */
function noptin_localize_optin_editor( $state ) {
	$props   = noptin_get_form_design_props();
	$props[] = 'DisplayOncePerSession';
	$props[] = 'timeDelayDuration';
	$props[] = 'scrollDepthPercentage';
	$props[] = 'cssClassOfClick';
	$props[] = 'triggerPopup';
	$props[] = 'slideDirection';

	$params = array(
		'ajaxurl'      => admin_url( 'admin-ajax.php' ),
		'api_url'      => get_home_url( null, 'wp-json/wp/v2/' ),
		'nonce'        => wp_create_nonce( 'noptin_admin_nonce' ),
		'data'         => $state,
		'templates'    => noptin_get_optin_templates(),
		'color_themes' => array_flip( noptin_get_color_themes() ),
		'design_props' => $props,
		'field_props'  => noptin_get_form_field_props(),
	);

	$params = apply_filters( 'noptin_form_editor_params', $params );

	wp_localize_script( 'noptin-optin-editor', 'noptinEditor', $params );
}

/**
 * Returns opt-in form properties.
 *
 * @since 1.0.5
 * @return array
 * @ignore
 * @deprecated
 */
function noptin_get_form_design_props() {
	return apply_filters(
		'noptin_form_design_props',
		array(
			'hideCloseButton',
			'borderSize',
			'gdprCheckbox',
			'gdprConsentText',
			'titleTypography',
			'titleAdvanced',
			'descriptionAdvanced',
			'descriptionTypography',
			'prefixTypography',
			'prefixAdvanced',
			'noteTypography',
			'noteAdvanced',
			'hideFields',
			'id',
			'imageMainPos',
			'closeButtonPos',
			'singleLine',
			'formRadius',
			'formWidth',
			'formHeight',
			'fields',
			'imageMain',
			'image',
			'imagePos',
			'noptinButtonLabel',
			'buttonPosition',
			'noptinButtonBg',
			'noptinButtonColor',
			'hideTitle',
			'title',
			'titleColor',
			'hidePrefix',
			'prefix',
			'prefixColor',
			'hideDescription',
			'description',
			'descriptionColor',
			'hideNote',
			'hideOnNoteClick',
			'note',
			'noteColor',
			'CSS',
			'optinType',
		)
	);

}

/**
 * Returns color themess.
 *
 * @since 1.0.7
 * @return array
 * @deprecated
 * @ignore
 */
function noptin_get_color_themes() {
	return apply_filters(
		'noptin_form_color_themes',
		array(
			'#e51c23 #fafafa #c62828' => __( 'Red', 'newsletter-optin-box' ), // Base color, Secondary color, border color.
			'#e91e63 #fafafa #ad1457' => __( 'Pink', 'newsletter-optin-box' ),
			'#9c27b0 #fafafa #6a1b9a' => __( 'Purple', 'newsletter-optin-box' ),
			'#673ab7 #fafafa #4527a0' => __( 'Deep Purple', 'newsletter-optin-box' ),
			'#9c27b0 #fafafa #4527a0' => __( 'Purple', 'newsletter-optin-box' ),
			'#3f51b5 #fafafa #283593' => __( 'Indigo', 'newsletter-optin-box' ),
			'#2196F3 #fafafa #1565c0' => __( 'Blue', 'newsletter-optin-box' ),
			'#03a9f4 #fafafa #0277bd' => __( 'Light Blue', 'newsletter-optin-box' ),
			'#00bcd4 #fafafa #00838f' => __( 'Cyan', 'newsletter-optin-box' ),
			'#009688 #fafafa #00695c' => __( 'Teal', 'newsletter-optin-box' ),
			'#4CAF50 #fafafa #2e7d32' => __( 'Green', 'newsletter-optin-box' ),
			'#8bc34a #191919 #558b2f' => __( 'Light Green', 'newsletter-optin-box' ),
			'#cddc39 #191919 #9e9d24' => __( 'Lime', 'newsletter-optin-box' ),
			'#ffeb3b #191919 #f9a825' => __( 'Yellow', 'newsletter-optin-box' ),
			'#ffc107 #191919 #ff6f00' => __( 'Amber', 'newsletter-optin-box' ),
			'#ff9800 #fafafa #e65100' => __( 'Orange', 'newsletter-optin-box' ),
			'#ff5722 #fafafa #bf360c' => __( 'Deep Orange', 'newsletter-optin-box' ),
			'#795548 #fafafa #3e2723' => __( 'Brown', 'newsletter-optin-box' ),
			'#607d8b #fafafa #263238' => __( 'Blue Grey', 'newsletter-optin-box' ),
			'#313131 #fafafa #607d8b' => __( 'Black', 'newsletter-optin-box' ),
			'#ffffff #191919 #191919' => __( 'White', 'newsletter-optin-box' ),
			'#aaaaaa #191919 #191919' => __( 'Grey', 'newsletter-optin-box' ),
		)
	);

}

/**
 * Returns form field props.
 *
 * @since 1.0.5
 * @return array
 * @deprecated
 * @ignore
 */
function noptin_get_form_field_props() {
	$props = array( 'fields', 'fieldTypes' );

	foreach ( get_noptin_connection_providers() as $key => $connection ) {

		if ( ! empty( $connection->list_providers ) ) {
			$props[] = "{$key}_list";
		}

		$props = $connection->add_custom_field_props( $props );
	}

	return apply_filters( 'noptin_form_field_props', $props );
}

/**
 * Returns the noptin action page.
 *
 * This function will always return 0 for new installs and
 * return the old actions page for existing installs.
 *
 * @return  int
 * @access  public
 * @deprecated 1.2.9 We are now using custom wp query vars.
 * @since   1.2.0
 */
function get_noptin_action_page() {
	return (int) get_option( 'noptin_actions_page', 0 );
}
