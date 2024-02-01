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
 * @param \Hizzle\Noptin\Emails\Email $email
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
		'campaign'     => $email,
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

	if ( empty( $html ) ) {
		return '';
	}

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
	// Replace [noptin] with [[noptin]].
	$content = str_replace( '[noptin]', '[[noptin]]', $content );

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
 * @return \Hizzle\Noptin\Emails\Email
 */
function noptin_get_email_campaign_object( $campaign_id ) {
	return new \Hizzle\Noptin\Emails\Email( $campaign_id );
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
				'image'        => array(
					'icon' => 'email',
					'fill' => '#008000',
				),
				'is_active'    => true,
				'is_installed' => true,
			),

			'woocommerce_customers' => array(
				'label'        => __( 'WooCommerce Customers', 'newsletter-optin-box' ),
				'description'  => __( "Send an email to all your WooCommerce customers, customers who've bought specific products, etc.", 'newsletter-optin-box' ),
				'image'        => 'https://noptin.com/wp-content/uploads/2023/04/woocommerce-badge-64x64.png',
				'is_active'    => function_exists( 'WC' ),
				'is_installed' => defined( 'NOPTIN_ADDONS_PACK_VERSION' ),
			),

			'wp_users'              => array(
				'label'        => __( 'WordPress Users', 'newsletter-optin-box' ),
				'description'  => __( 'Send an email to your WordPress Users. You can filter recipients by their user roles.', 'newsletter-optin-box' ),
				'image'        => array(
					'icon' => 'wordpress',
					'fill' => '#464342',
				),
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
				'label'       => __( 'Classic', 'newsletter-optin-box' ),
				'description' => __( 'Your email will be embedded inside a template', 'newsletter-optin-box' ),
			),

			'plain_text' => array(
				'label'       => __( 'Plain Text', 'newsletter-optin-box' ),
				'description' => __( 'Send a plain text email. It will contain no HTML which means open tracking and click tracking will not work.', 'newsletter-optin-box' ),
			),

			'raw_html'   => array(
				'label'       => __( 'Raw HTML', 'newsletter-optin-box' ),
				'description' => __( "This is useful if you're using a drag and drop email builder. Please note that you should include an unsubscribe link by using the [[unsubscribe_url]] merge tag.", 'newsletter-optin-box' ),
			),

		)
	);

}

/**
 * Returns an array of email sub types.
 *
 * @param string $type
 * @since 2.3.0
 * @return array
 */
function get_noptin_campaign_sub_types( $type ) {
	return apply_filters( 'noptin_' . $type . '_sub_types', array() );
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
 * Returns an array of email template settings.
 *
 * @param string|null $template
 * @param Hizzle\Noptin\Emails\Email $email
 * @return array
 */
function get_noptin_email_template_settings( $template, $email = null ) {
	$defaults = get_noptin_email_template_defaults();

	if ( ! isset( $defaults[ $template ] ) ) {
		return array();
	}

	$settings = $defaults[ $template ];

	if ( ! empty( $email ) ) {
		foreach ( $settings as $key => $value ) {
			$overide = $email->get( $key );

			if ( ! empty( $overide ) ) {
				$settings[ $key ] = $overide;
			}
		}
	}

	// Convert font_size to px.
	if ( ! empty( $settings['font_size'] ) && is_numeric( $settings['font_size'] ) ) {
		$settings['font_size'] = $settings['font_size'] . 'px';
	}

	return $settings;
}

/**
 * Returns an array of email template defaults.
 *
 * @since 1.7.0
 * @return array
 */
function get_noptin_email_template_defaults() {

	$brand_color = get_noptin_option( 'brand_color' );
	$brand_color = empty( $brand_color ) ? '#1a82e2' : $brand_color;

	$defaults = array(
		'noptin-visual' => array(
			'color'             => '#111111',
			'button_background' => $brand_color,
			'button_color'      => '#ffffff',
			'background_color'  => '#f1f1f1',
			'custom_css'        => '',
			'font_family'       => 'Arial, Helvetica, sans-serif',
			'font_size'         => '14px',
			'font_style'        => 'normal',
			'font_weight'       => 'normal',
			'line_height'       => '1.5',
			'block_css'         => (object) array(),
		),
		'paste'         => array(
			'color'              => '#111111',
			'footer_text_color'  => '#666666',
			'content_background' => '#ffffff',
			'background_color'   => '#e9ecef',
			'width'              => '600px',
			'font_family'        => 'Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol"',
			'font_size'          => '16px',
			'font_style'         => 'normal',
			'font_weight'        => 'normal',
			'line_height'        => '1.5',
		),
		'plain'         => array(
			'color'              => '#454545',
			'footer_text_color'  => '#666666',
			'content_background' => '#ffffff',
			'background_color'   => '#ffffff',
			'width'              => '600px',
			'font_family'        => 'Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol"',
			'font_size'          => '15px',
			'font_style'         => 'normal',
			'font_weight'        => 'normal',
			'line_height'        => '1.4',
		),
		'merriweather'  => array(
			'color'              => '#454545',
			'footer_text_color'  => '#666666',
			'content_background' => '#ffffff',
			'background_color'   => '#d2c7ba',
			'width'              => '600px',
			'font_family'        => '\'Merriweather\', Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol"',
			'font_size'          => '15px',
			'font_style'         => 'normal',
			'font_weight'        => 'normal',
			'line_height'        => '1.5',
		),
	);

	foreach ( array_keys( get_noptin_email_templates() ) as $template ) {

		if ( ! isset( $defaults[ $template ] ) ) {
			$defaults[ $template ] = array();
		}

		$defaults[ $template ]['link_color'] = $brand_color;

		// Set custom_css is not set.
		if ( ! isset( $defaults[ $template ]['custom_css'] ) ) {
			$defaults[ $template ]['custom_css'] = '';
		}
	}

	return apply_filters( 'noptin_email_template_defaults', $defaults );
}

/**
 * Returns an array of email delay units.
 *
 * @since 1.7.0
 * @return array
 */
function get_noptin_email_delay_units( $singular = false ) {

	$units = array(
		'minutes' => __( 'Minutes', 'newsletter-optin-box' ),
		'hours'   => __( 'Hours', 'newsletter-optin-box' ),
		'days'    => __( 'Days', 'newsletter-optin-box' ),
		'weeks'   => __( 'Weeks', 'newsletter-optin-box' ),
		'months'  => __( 'Months', 'newsletter-optin-box' ),
		'years'   => __( 'Years', 'newsletter-optin-box' ),
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
 * Wraps blocks in a section.
 */
function noptin_email_wrap_blocks( $blocks, $footer_text = '', $heading_text = '' ) {

	$placeholder = esc_attr__( 'Add footer text here', 'newsletter-optin-box' );
	$footer      = '<!-- wp:paragraph { "anchor":"footer-text","placeholder":"' . $placeholder . '","style":{"noptin":{"typography":{"textAlign":"center","fontSize":13},"color":{"text":"#666666","link":"#111111"}}}} --> <p style="text-align:center;font-size:13px;color:#666666" class="wp-block-paragraph footer-text">' . $footer_text . '</p> <!-- /wp:paragraph -->';

	if ( empty( $blocks ) ) {
		$blocks = '<!-- wp:paragraph --> <p class="wp-block-paragraph"></p> <!-- /wp:paragraph -->';
	}

	// Prepend heading block if we have a heading.
	if ( ! empty( $heading_text ) ) {
		$placeholder = esc_attr__( 'Add heading text here', 'newsletter-optin-box' );
		$blocks      = '<!-- wp:heading { "anchor":"heading-text","placeholder":"' . $placeholder . '"--> <h2 class="wp-block-heading heading-text">' . $heading_text . '</h2> <!-- /wp:heading -->' . $blocks;
	}

	return '<!-- wp:noptin/group {"anchor":"main-content-wrapper","style":{"noptin":{"align":"center","color":{"background":"#ffffff"}}}} --> <div class="wp-block-noptin-group main-content-wrapper"><table width="600px" align="center" cellpadding="0" cellspacing="0" role="presentation" style="width:600px;max-width:100%;border-collapse:separate;background-color:#ffffff"><tbody><tr><td class="noptin-block-group__inner" align="center"><table border="0" cellpadding="0" cellspacing="0" width="100%"><tbody><tr><td style="background-color:#ffffff">' . $blocks . '</td></tr></tbody></table></td></tr></tbody></table></div> <!-- /wp:noptin/group --> <!-- wp:noptin/group {"anchor":"main-footer-wrapper","style":{"noptin":{"align":"center","color":{"background":""}}}} --> <div class="wp-block-noptin-group main-footer-wrapper"><table width="600px" align="center" cellpadding="0" cellspacing="0" role="presentation" style="width:600px;max-width:100%;border-collapse:separate"><tbody><tr><td class="noptin-block-group__inner" align="center"><table border="0" cellpadding="0" cellspacing="0" width="100%"><tbody><tr><td>' . $footer . '</td></tr></tbody></table></td></tr></tbody></table></div> <!-- /wp:noptin/group -->';
}
