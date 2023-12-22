<?php

	namespace Hizzle\Noptin\Emails\Admin;

	defined( 'ABSPATH' ) || exit;

	/**
	 * @var array $query_args
	 */

	// Retrieve campaign object.
	$campaign = new \Hizzle\Noptin\Emails\Email( intval( $query_args['noptin_campaign'] ) );

	// If this is a new campaign...
	if ( ! $campaign->exists() ) {

		// Maybe show a 404 error.
		if ( ! empty( $query_args['noptin_campaign'] ) ) {
			include plugin_dir_path( __FILE__ ) . '404.php';
			return;
		}

		// Set the type.
		$campaign->type = sanitize_text_field( $query_args['noptin_email_type'] );

		// Set the sub type.
		if ( ! empty( $query_args['noptin_email_sub_type'] ) ) {
			$campaign->options[ $campaign->type . '_type' ] = sanitize_text_field( $query_args['noptin_email_sub_type'] );
		} else {
			$sub_types = $campaign->get_sub_types();

			if ( ! empty( $sub_types ) ) {
				include plugin_dir_path( __FILE__ ) . 'sub-type.php';
				return;
			}
		}

		// Set the sender.
		if ( ! empty( $query_args['noptin_email_sender'] ) ) {
			$campaign->options['email_sender'] = sanitize_text_field( $query_args['noptin_email_sender'] );
		} elseif ( $campaign->is_mass_mail() ) {
			include plugin_dir_path( __FILE__ ) . 'sender.php';
			return;
		}
	}

	// Display the editor.
	printf(
		'<div id="noptin-email-campaigns__editor" class="block-editor" data-campaign="%s"></div>',
		esc_attr( wp_json_encode( $campaign->to_array() ) )
	);
