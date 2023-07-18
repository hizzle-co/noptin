<?php
/**
 * Emails API: functions.
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sends an email.
 *
 * A true return value does not automatically mean that the user received the
 * email successfully. It just only means that the method used was able to
 * process the request without any errors.
 *
 * @param array $args An array of arguments.
 * @param array $args {
 *      An array of arguments to pass to the email sender.
 *
 *     @type string $unsubscribe_url         URL to unsubscribe from further emails.
 *     @type string $content_type            Content type of the email.
 *     @type string $from_name               Name of the sender.
 *     @type string $from_email              Email address of the sender.
 *     @type string $reply_to                Reply-to email address.
 *     @type string[] $attachments           Paths to files to attach.
 *     @type string[] $headers               Additional headers.
 *     @type string $message                 Email content.
 *     @type string $subject                 Email subject.
 *     @type string|string[]  $recipients    Array or comma-separated list of email recipients.
 *     @type bool  $disable_template_plugins Default 'true'. Whether or not to disable email template plugins.
 * }
 * @param bool $background Whether or not to send the email in the background.
 * @see Noptin_Email_Sender
 * @return bool|void Whether the email was sent successfully. Returns nothing if sending in the background.
 */
function noptin_send_email( $args, $background = false ) {

	if ( is_wp_error( $args['message'] ) ) {
		log_noptin_message( $args['message'] );
		return false;
	}

	if ( empty( $args['message'] ) ) {
		return false;
	}

	$args       = apply_filters( 'noptin_send_email_args', $args );
	$background = isset( $args['background'] ) ? $args['background'] : $background;

	if ( ! $background ) {
		return noptin()->emails->sender->send( $args );
	}

	return noptin()->emails->sender->bg_send( $args );
}

/**
 * Generates the content of an email.
 *
 * @since 1.7.0
 * @param Noptin_Automated_Email|Noptin_Newsletter_Email $email
 * @param array $recipient
 * @param bool $track
 * @return string|WP_Error
 */
function noptin_generate_email_content( $email, $recipient, $track = true ) {

	$content = $email->get_content( $email->get_email_type() );

	if ( 'normal' === $email->get_email_type() && ! $email->parent_id ) {
		$content = wpautop( trim( $content ) );
	}

	$args = array(
		'type'         => $email->get_email_type(), // normal, raw_html, plain_text
		'content'      => $content,
		'template'     => $email->get_template(),
		'heading'      => $email->get( 'heading' ),
		'footer_text'  => $email->get( 'footer_text' ),
		'preview_text' => $email->get( 'preview_text' ),
		'campaign_id'  => $email->id,
		'track'        => $track,
		'recipient'    => $recipient,
	);

	$generator = new Noptin_Email_Generator();
	return $generator->generate( $args );
}

/**
 * Converts HTML email to text email.
 *
 * @param string $html
 * @since 1.7.0
 * @return string
 */
function noptin_convert_html_to_text( $html ) {

	// Abort if DOMDocument not loaded.
	if ( ! class_exists( 'DOMDocument' ) ) {
		return wp_strip_all_tags( $html );
	}

	try {
		return Noptin_HTML_Text::convert( $html );
	} catch ( Exception $e ) {
		return wp_strip_all_tags( $html );
	}

}

/**
 * Processes email subject tags.
 *
 * @since 1.7.0
 * @param string $subject
 * @return string
 */
function noptin_parse_email_subject_tags( $subject, $partial = false ) {
	return apply_filters( 'noptin_parse_email_subject_tags', $subject, $partial );
}

/**
 * Processes email content tags.
 *
 * @since 1.7.0
 * @param string $content
 * @return string
 */
function noptin_parse_email_content_tags( $content, $partial = false ) {
	return apply_filters( 'noptin_parse_email_content_tags', $content, $partial );
}

/**
 * Returns the URL to create a new automated email.
 *
 * @since 1.7.0
 * @return string
 */
function noptin_get_new_automation_url() {

	return add_query_arg(
		array(
			'page'        => 'noptin-email-campaigns',
			'section'     => 'automations',
			'sub_section' => 'new_campaign',
		),
		admin_url( '/admin.php' )
	);

}

/**
 * Retrieves an email campaign's object.
 *
 * @since 2.0.0
 * @param int $campaign_id
 * @return Noptin_Automated_Email|Noptin_Newsletter_Email|false
 */
function noptin_get_email_campaign_object( $campaign_id ) {
	$campaign_type = get_post_meta( $campaign_id, 'campaign_type', true );

	if ( empty( $campaign_type ) ) {
		return false;
	}

	if ( 'newsletter' === $campaign_type ) {
		return new Noptin_Newsletter_Email( $campaign_id );
	}

	if ( 'automation' === $campaign_type ) {
		return new Noptin_Automated_Email( $campaign_id );
	}

	return apply_filters( 'noptin_get_email_campaign_object', false, $campaign_id, $campaign_type );
}

/**
 * Returns an array of email senders.
 *
 * @since 1.5.2
 * @return array
 */
function get_noptin_email_senders( $full = false ) {

	// Prepare senders.
	$senders = apply_filters(
		'noptin_email_senders',
		array(

			'noptin'                => array(
				'label'        => __( 'Noptin Subscribers', 'newsletter-optin-box' ),
				'description'  => __( 'Send an email to your active subscribers. You can filter recipients by subscription source, tags, lists or custom fields.', 'newsletter-optin-box' ),
				'image'        => '<svg xmlns="http://www.w3.org/2000/svg" fill="#008000" viewBox="0 0 122.88 122.88"><path d="M61.44,0A61.46,61.46,0,1,1,18,18,61.21,61.21,0,0,1,61.44,0ZM32.22,79.39,52.1,59.46,32.22,43.25V79.39ZM54.29,61.24,33.79,81.79H88.91L69.33,61.24l-6.46,5.51h0a1.42,1.42,0,0,1-1.8,0l-6.78-5.53Zm17.18-1.82L90.66,79.55V43.07L71.47,59.42ZM34,41.09l27.9,22.76L88.65,41.09Zm65.4-17.64a53.72,53.72,0,1,0,15.74,38,53.56,53.56,0,0,0-15.74-38Z"/></svg>',
				'is_active'    => true,
				'is_installed' => true,
			),

			'woocommerce_customers' => array(
				'label'        => __( 'WooCommerce Customers', 'newsletter-optin-box' ),
				'description'  => __( "Send an email to all your WooCommerce customers, customers who've bought specific products, etc.", 'newsletter-optin-box' ),
				'image'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 503.81 299.89"><path fill="#7f54b3" d="M46.75,0H456.84a46.94,46.94,0,0,1,47,47V203.5a46.94,46.94,0,0,1-47,47H309.78L330,299.89l-88.78-49.43H47a46.94,46.94,0,0,1-47-47V47A46.77,46.77,0,0,1,46.76,0Z"/><path fill="#fff" d="M28.69,42.8c2.86-3.89,7.16-5.94,12.9-6.35Q57.25,35.24,59.41,51.2,68.94,115.4,80.09,160l44.85-85.4q6.15-11.67,15.36-12.29c9-.61,14.54,5.12,16.8,17.2,5.12,27.24,11.67,50.38,19.45,70q8-78,27-112.64c3.07-5.73,7.57-8.6,13.51-9A17.8,17.8,0,0,1,230,32a16,16,0,0,1,6.35,11.67,17.79,17.79,0,0,1-2,9.83c-8,14.75-14.55,39.53-19.87,73.93-5.12,33.39-7,59.4-5.73,78a24.29,24.29,0,0,1-2.46,13.52c-2.46,4.51-6.15,7-10.86,7.37-5.32.41-10.85-2.05-16.17-7.57Q150.64,189.54,134,131.48q-20,39.32-29.49,59c-12.09,23.14-22.33,35-30.93,35.64C68,226.51,63.3,221.8,59.2,212Q43.54,171.72,25.41,56.52A17.44,17.44,0,0,1,28.69,42.8ZM468.81,75C461.43,62.05,450.58,54.27,436,51.2A53.72,53.72,0,0,0,425,50c-19.66,0-35.63,10.24-48.13,30.72a108.52,108.52,0,0,0-16,57.75q0,23.66,9.83,40.55c7.37,12.91,18.23,20.69,32.77,23.76A53.64,53.64,0,0,0,414.54,204c19.86,0,35.83-10.24,48.12-30.72a109.73,109.73,0,0,0,16-58C478.84,99.33,475.36,86,468.81,75ZM443,131.69c-2.86,13.51-8,23.55-15.56,30.31-5.94,5.32-11.47,7.57-16.59,6.55-4.92-1-9-5.32-12.08-13.31a52,52,0,0,1-3.69-18.64,71.48,71.48,0,0,1,1.43-14.95,66.29,66.29,0,0,1,10.86-24.37c6.76-10,13.92-14.13,21.3-12.7,4.91,1,9,5.33,12.08,13.31a52,52,0,0,1,3.69,18.64A71.47,71.47,0,0,1,443,131.69ZM340.6,75c-7.37-12.91-18.43-20.69-32.76-23.76A53.79,53.79,0,0,0,296.78,50c-19.66,0-35.64,10.24-48.13,30.72a108.52,108.52,0,0,0-16,57.75q0,23.66,9.83,40.55c7.37,12.91,18.22,20.69,32.76,23.76A53.72,53.72,0,0,0,286.33,204c19.87,0,35.84-10.24,48.13-30.72a109.72,109.72,0,0,0,16-58C350.43,99.33,347.16,86,340.6,75Zm-26,56.73c-2.86,13.51-8,23.55-15.56,30.31-5.94,5.32-11.47,7.57-16.59,6.55-4.91-1-9-5.32-12.08-13.31a52,52,0,0,1-3.69-18.64,71.48,71.48,0,0,1,1.43-14.95A66.29,66.29,0,0,1,279,97.28c6.76-10,13.92-14.13,21.3-12.7,4.91,1,9,5.33,12.08,13.31A52,52,0,0,1,316,116.53a60.45,60.45,0,0,1-1.44,15.16Z"/></svg>',
				'is_active'    => function_exists( 'WC' ),
				'is_installed' => defined( 'NOPTIN_ADDONS_PACK_VERSION' ),
			),

			'wp_users'              => array(
				'label'        => __( 'WordPress Users', 'newsletter-optin-box' ),
				'description'  => __( 'Send an email to your WordPress Users. You can filter recipients by their user roles.', 'newsletter-optin-box' ),
				'image'        => '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 256 255" version="1.1"><g fill="#464342"><path d="M18.1239675,127.500488 C18.1239675,170.795707 43.284813,208.211252 79.7700163,225.941854 L27.5938862,82.985626 C21.524813,96.5890081 18.1239675,111.643057 18.1239675,127.500488 L18.1239675,127.500488 Z M201.345041,121.980878 C201.345041,108.462829 196.489366,99.1011382 192.324683,91.8145041 C186.780098,82.8045528 181.583089,75.1745041 181.583089,66.1645528 C181.583089,56.1097886 189.208976,46.7501789 199.950569,46.7501789 C200.435512,46.7501789 200.89548,46.8105366 201.367935,46.8375935 C181.907772,29.0091707 155.981008,18.1239675 127.50465,18.1239675 C89.2919675,18.1239675 55.6727154,37.7298211 36.1147317,67.4258211 C38.6809756,67.5028293 41.0994472,67.5569431 43.1536911,67.5569431 C54.5946016,67.5569431 72.3043902,66.1687154 72.3043902,66.1687154 C78.2007154,65.8211382 78.8958699,74.4814309 73.0057886,75.1786667 C73.0057886,75.1786667 67.0803252,75.8759024 60.4867642,76.2213984 L100.318699,194.699447 L124.25574,122.909138 L107.214049,76.2172358 C101.323967,75.8717398 95.744,75.1745041 95.744,75.1745041 C89.8497561,74.8290081 90.540748,65.8169756 96.4349919,66.1645528 C96.4349919,66.1645528 114.498602,67.5527805 125.246439,67.5527805 C136.685268,67.5527805 154.397138,66.1645528 154.397138,66.1645528 C160.297626,65.8169756 160.990699,74.4772683 155.098537,75.1745041 C155.098537,75.1745041 149.160585,75.8717398 142.579512,76.2172358 L182.107577,193.798244 L193.017756,157.340098 C197.746472,142.211122 201.345041,131.34465 201.345041,121.980878 L201.345041,121.980878 Z M129.42361,137.068228 L96.6056585,232.43135 C106.404423,235.31187 116.76722,236.887415 127.50465,236.887415 C140.242211,236.887415 152.457366,234.685398 163.827512,230.68722 C163.534049,230.218927 163.267642,229.721496 163.049106,229.180358 L129.42361,137.068228 L129.42361,137.068228 Z M223.481756,75.0225691 C223.95213,78.5066667 224.218537,82.2467642 224.218537,86.2699187 C224.218537,97.3694959 222.145561,109.846894 215.901659,125.448325 L182.490537,222.04774 C215.00878,203.085008 236.881171,167.854829 236.881171,127.502569 C236.883252,108.485724 232.025496,90.603187 223.481756,75.0225691 L223.481756,75.0225691 Z M127.50465,0 C57.2003902,0 0,57.1962276 0,127.500488 C0,197.813073 57.2003902,255.00722 127.50465,255.00722 C197.806829,255.00722 255.015545,197.813073 255.015545,127.500488 C255.013463,57.1962276 197.806829,0 127.50465,0 L127.50465,0 Z M127.50465,249.162927 C60.4243252,249.162927 5.84637398,194.584976 5.84637398,127.500488 C5.84637398,60.4201626 60.4222439,5.84637398 127.50465,5.84637398 C194.582894,5.84637398 249.156683,60.4201626 249.156683,127.500488 C249.156683,194.584976 194.582894,249.162927 127.50465,249.162927 L127.50465,249.162927 Z"/></g></svg>',
				'is_active'    => true,
				'is_installed' => defined( 'NOPTIN_ADDONS_PACK_VERSION' ),
			),

		)
	);

	// Filter inactive senders.
	$senders = wp_list_filter( $senders, array( 'is_active' => true ) );

	// Are we returning the full array?
	if ( ! $full ) {
		return wp_list_pluck( wp_list_filter( $senders, array( 'is_installed' => true ) ), 'label' );
	}

	// Return.
	return $senders;
}

/**
 * Returns an array of email types.
 *
 * @since 1.7.0
 * @return array
 */
function get_noptin_email_types() {

	return apply_filters(
		'noptin_email_types',
		array(

			'normal'     => array(
				'label'       => __( 'Standard (Recommended)', 'newsletter-optin-box' ),
				'description' => __( 'Your email will be embedded inside a template', 'newsletter-optin-box' ),
			),

			'plain_text' => array(
				'label'       => __( 'Plain Text', 'newsletter-optin-box' ),
				'description' => __( 'Sends a plain text email. It will contain no HTML which means open tracking and click tracking will not work.', 'newsletter-optin-box' ),
			),

			'raw_html'   => array(
				'label'       => __( 'Raw HTML', 'newsletter-optin-box' ),
				'description' => __( "This is useful if you're using a drag and drop email builder. Please note that you should include an unsubscribe link by using the [[unsubscribe_url]] merge tag.", 'newsletter-optin-box' ),
			),

		)
	);

}

/**
 * Returns an array of email templates.
 *
 * @since 1.7.0
 * @return array
 */
function get_noptin_email_templates() {

	$templates = array(
		'default'      => __( 'No template', 'newsletter-optin-box' ),
		'paste'        => __( 'Paste', 'newsletter-optin-box' ),
		'plain'        => __( 'Plain', 'newsletter-optin-box' ),
		'merriweather' => __( 'Merriweather', 'newsletter-optin-box' ),
	);

	return apply_filters( 'noptin_email_templates', $templates );
}

/**
 * Returns an array of email delay units.
 *
 * @since 1.7.0
 * @return array
 */
function get_noptin_email_delay_units( $singular = false ) {

	$units = array(
		'minutes' => __( 'Minute(s)', 'newsletter-optin-box' ),
		'hours'   => __( 'Hour(s)', 'newsletter-optin-box' ),
		'days'    => __( 'Day(s)', 'newsletter-optin-box' ),
		'weeks'   => __( 'Week(s)', 'newsletter-optin-box' ),
		'months'  => __( 'Month(s)', 'newsletter-optin-box' ),
		'years'   => __( 'Year(s)', 'newsletter-optin-box' ),
	);

	if ( $singular ) {
		$units = array(
			'minutes' => __( 'Minute', 'newsletter-optin-box' ),
			'hours'   => __( 'Hour', 'newsletter-optin-box' ),
			'days'    => __( 'Day', 'newsletter-optin-box' ),
			'weeks'   => __( 'Week', 'newsletter-optin-box' ),
			'months'  => __( 'Month', 'newsletter-optin-box' ),
			'years'   => __( 'Year', 'newsletter-optin-box' ),
		);
	}

	return apply_filters( 'noptin_email_delay_units', $units, $singular );
}

/**
 * Returns the global footer text.
 *
 * @since 1.7.0
 * @return string
 */
function get_noptin_footer_text() {
	return get_noptin_option( 'footer_text', get_default_noptin_footer_text() );
}

/**
 * Returns the default footer text.
 *
 * @since 1.7.0
 * @return string
 */
function get_default_noptin_footer_text() {
	return apply_filters(
		'default_noptin_footer_text',
		sprintf(
			/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
			__( '[[blog_name]] &mdash; Powered by [[noptin]] | %1$sUnsubscribe%2$s', 'newsletter-optin-box' ),
			'<a href="[[unsubscribe_url]]" rel="nofollow" target="_blank">',
			'</a>'
		)
	);
}

/**
 * Increments a campaign stat.
 *
 * @since 1.7.0
 * @param int $campaign_id
 * @param string $stat
 */
function increment_noptin_campaign_stat( $campaign_id, $stat ) {

	// Increment stat.
	$current = (int) get_post_meta( $campaign_id, $stat, true );
	update_post_meta( $campaign_id, $stat, $current + 1 );

	// Increment parent stat.
	$parent = get_post_parent( $campaign_id );

	if ( $parent ) {
		increment_noptin_campaign_stat( $parent->ID, $stat );
	}

}

/**
 * Decreaments a campaign stat.
 *
 * @since 1.7.0
 * @param int $campaign_id
 * @param string $stat
 */
function decrease_noptin_campaign_stat( $campaign_id, $stat ) {

	// Increment stat.
	$current = (int) get_post_meta( $campaign_id, $stat, true );
	update_post_meta( $campaign_id, $stat, max( $current - 1, 0 ) );

	// Increment parent stat.
	$parent = get_post_parent( $campaign_id );

	if ( $parent ) {
		decrease_noptin_campaign_stat( $parent->ID, $stat );
	}

}

/**
 * Retrieves an email recipient by id and sender.
 *
 * @since 1.10.1
 * @param int $id The recipient id.
 * @param string $sender The sender.
 * @return array|false An array containing the recipient email and name, or false if none found.
 */
function get_noptin_email_recipient( $id, $sender ) {

	return apply_filters( "noptin_{$sender}_email_recipient", false, $id );
}

/**
 * Retrieves a URL to send emails to specified reciepients.
 *
 * @since 1.10.1
 * @param array $recipients An array of recipient ids.
 * @param string $sender The sender.
 * @return string A URL to send emails to specified reciepients.
 */
function get_noptin_email_recipients_url( $recipients, $sender ) {
	$recipients = implode( ',', noptin_parse_int_list( $recipients ) );

	return add_query_arg(
		array(
			'noptin_recipients' => rawurlencode( $recipients ),
			'section'           => 'newsletters',
			'sub_section'       => 'edit_campaign',
			'campaign'          => rawurlencode( $sender ),
		),
		admin_url( 'admin.php?page=noptin-email-campaigns' )
	);
}

/**
 * Logs a debugging message.
 *
 * @param string $log The message to log.
 * @param string|bool $title The title of the message, or pass false to disable the backtrace.
 * @param string $file The file from which the error was logged.
 * @param string $line The line that contains the error.
 * @param bool $exit Whether or not to exit function execution.
 */
function noptin_error_log( $log, $title = '', $file = '', $line = '', $exit = false ) {

	if ( true === apply_filters( 'noptin_error_log', true ) ) {

		// Ensure the log is a scalar.
		if ( ! is_scalar( $log ) ) {
			$log = print_r( $log, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		}

		// Add title.
		if ( ! empty( $title ) ) {
			$log  = $title . ' ' . trim( $log );
		}

		// Add the file to the label.
		if ( ! empty( $file ) ) {
			$log .= ' in ' . $file;
		}

		// Add the line number to the label.
		if ( ! empty( $line ) ) {
			$log .= ' on line ' . $line;
		}

		// Log the message.
		error_log( trim( $log ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

		// ... and a backtrace.
		if ( false !== $title && false !== $file ) {
			error_log( 'Backtrace ' . wp_debug_backtrace_summary() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_wp_debug_backtrace_summary
		}
	}

	// Maybe exit.
	if ( $exit ) {
		exit;
	}

}

/**
 * Displays the available merge tags text.
 *
 * @param string $text The text to display.
 * @since 1.10.3
 */
function noptin_email_display_merge_tags_text( $text = '' ) {

	if ( apply_filters( 'noptin_email_has_listed_available_merge_tags', false ) ) {
		add_thickbox();
		$atts = array(
			'href'  => '#TB_inline?width=0&height=550&inlineId=noptin-automation-rule-smart-tags',
			'class' => 'thickbox',
		);
	} else {
		$atts = array(
			'href'   => noptin_get_upsell_url( '/guide/sending-emails/email-tags/#available-merge-tags', 'email-tags', 'email-campaigns' ),
			'target' => '_blank',
		);
	}

	?>
	<p class="description">
		<?php echo wp_kses_post( $text ); ?>
		<?php esc_html_e( 'You can use email tags to provide dynamic values.', 'newsletter-optin-box' ); ?>
		<a <?php noptin_attr( 'available_email_tags', $atts ); ?>>
			<?php esc_html_e( 'View available tags', 'newsletter-optin-box' ); ?>
		</a>
	</p>
	<?php
}
