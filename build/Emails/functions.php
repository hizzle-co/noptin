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
 * @see Noptin_Email_Sender
 * @return bool|void Whether the email was sent successfully. Returns nothing if sending in the background.
 */
function noptin_send_email( $args ) {

	$args = apply_filters( 'noptin_send_email_args', $args );

	if ( is_wp_error( $args['message'] ) ) {
		log_noptin_message( $args['message'] );
		return;
	}

	if ( empty( $args['message'] ) ) {
		return;
	}

	return \Hizzle\Noptin\Emails\Sender::send( $args );
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

	// Abort if DOMDocument not loaded or mbstring not enabled.
	if ( ! class_exists( 'DOMDocument' ) || ! function_exists( 'mb_convert_encoding' ) ) {
		return wp_strip_all_tags( $html );
	}

	try {
		$converter = new Noptin_HTML_Text( $html );
		return $converter->getText();
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
	if ( empty( $subject ) ) {
		return '';
	}

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
	if ( empty( $content ) ) {
		return '';
	}

	return apply_filters( 'noptin_parse_email_content_tags', $content, $partial );
}

/**
 * Retrieves an email campaign's object.
 *
 * @since 2.0.0
 * @param int $campaign_id
 * @return \Hizzle\Noptin\Emails\Email
 */
function noptin_get_email_campaign_object( $campaign_id ) {
	return \Hizzle\Noptin\Emails\Email::from( $campaign_id );
}

/**
 * Sends an email campaign.
 *
 * @since 3.0.0
 * @param int|\Hizzle\Noptin\Emails\Email $campaign_id
 * @return bool|\WP_Error
 */
function noptin_send_email_campaign( $campaign_id, $alt_smart_tags = null ) {
	noptin()->emails->tags->smart_tags = $alt_smart_tags;
	$campaign                          = \Hizzle\Noptin\Emails\Email::from( $campaign_id );
	$result                            = $campaign->send();
	noptin()->emails->tags->smart_tags = null;
	return $result;
}

/**
 * Returns an array of email senders.
 *
 * @since 1.5.2
 * @return array
 */
function get_noptin_email_senders( $full = false ) {

	// Prepare senders.
	$senders = array(

		'noptin' => array(
			'label'        => __( 'Noptin Subscribers', 'newsletter-optin-box' ),
			'description'  => __( 'Send a bulk email to your active subscribers. You can filter recipients by subscription source, tags, lists or custom fields.', 'newsletter-optin-box' ),
			'image'        => array(
				'icon' => 'email',
				'fill' => '#008000',
			),
			'is_installed' => true,
			'is_local'     => true,
		),
	);

	foreach ( noptin()->integrations_new->get_all_known_integrations() as $integration ) {
		if ( ! empty( $integration['mass_mail'] ) ) {
			$sender             = $integration['mass_mail']['id'];
			$senders[ $sender ] = $integration['mass_mail'];

			$senders[ $sender ]['integration']  = $integration['slug'];
			$senders[ $sender ]['is_installed'] = false;

			if ( empty( $senders[ $sender ]['image'] ) ) {
				$senders[ $sender ]['image'] = $integration['icon_url'];
			}

			if ( ! isset( $senders[ $sender ]['is_local'] ) ) {
				$senders[ $sender ]['is_local'] = true;
			}
		}
	}

	$senders = apply_filters( 'noptin_email_senders', $senders );

	// Are we returning the full array?
	if ( ! $full ) {
		return wp_list_pluck( wp_list_filter( $senders, array( 'is_installed' => true ) ), 'label' );
	}

	// Return.
	return $senders;
}

/**
 * Returns the default email type.
 *
 * @since 3.0.0
 * @return string
 */
function get_default_noptin_email_type() {

	if ( noptin_has_alk() ) {
		return 'visual';
	}

	return 'normal';
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

			'visual'     => array(
				'label'       => __( 'Visual', 'newsletter-optin-box' ),
				'description' => __( 'Compose your email using the block editor', 'newsletter-optin-box' ),
			),

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
			'link_color'        => $brand_color,
			'block_css'         => (object) array(),
			'background_image'  => '',
		),
		'paste'         => array(
			'color'              => '#111111',
			'footer_text_color'  => '#666666',
			'content_background' => '#ffffff',
			'background_color'   => '#e9eaec',
			'width'              => '600px',
			'font_family'        => 'Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol"',
			'font_size'          => '16px',
			'font_style'         => 'normal',
			'font_weight'        => 'normal',
			'line_height'        => '1.5',
			'link_color'         => $brand_color,
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
			'link_color'         => $brand_color,
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
			'link_color'         => $brand_color,
		),
	);

	foreach ( array_keys( get_noptin_email_templates() ) as $template ) {
		if ( ! isset( $defaults[ $template ] ) ) {
			$defaults[ $template ] = array();
		}

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
 * @param int $amount
 */
function increment_noptin_campaign_stat( $campaign_id, $stat, $amount = 1 ) {

	// Increment stat.
	$current = (float) get_post_meta( $campaign_id, $stat, true );
	update_post_meta( $campaign_id, $stat, $current + $amount );

	// Increment parent stat.
	$parent = get_post_parent( $campaign_id );

	if ( $parent ) {
		increment_noptin_campaign_stat( $parent->ID, $stat, $amount );
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
			'noptin_recipients'   => rawurlencode( $recipients ),
			'noptin_email_type'   => 'newsletter',
			'noptin_campaign'     => 0,
			'noptin_email_sender' => rawurlencode( $sender ),
		),
		admin_url( 'admin.php?page=noptin-email-campaigns' )
	);
}

/**
 * Wraps blocks in a section.
 */
function noptin_email_wrap_blocks( $blocks, $footer_text = '', $heading_text = '' ) {

	$placeholder = esc_attr__( 'Add footer text here', 'newsletter-optin-box' );
	$footer      = '<!-- wp:paragraph { "anchor":"footer-text","placeholder":"' . $placeholder . '","style":{"noptin":{"typography":{"textAlign":"center","fontSize":13},"color":{"text":"#666666","link":"#111111"}}}} --> <p style="text-align:center;font-size:13px;color:#666666" class="wp-block-paragraph footer-text">' . $footer_text . '</p> <!-- /wp:paragraph -->';

	if ( empty( $blocks ) ) {
		$blocks = noptin_email_wrap_paragraph_block();
	}

	// Prepend heading block if we have a heading.
	if ( ! empty( $heading_text ) ) {
		$placeholder = esc_attr__( 'Add heading text here', 'newsletter-optin-box' );
		$blocks      = '<!-- wp:heading { "anchor":"heading-text","placeholder":"' . $placeholder . '"--> <h2 class="wp-block-heading heading-text">' . $heading_text . '</h2> <!-- /wp:heading -->' . $blocks;
	}

	return sprintf(
		'%s %s',
		noptin_email_wrap_group_block( $blocks ),
		noptin_email_wrap_group_block( $footer, '' )
	);
}

/**
 * Returns a paragraph block.
 */
function noptin_email_wrap_paragraph_block( $content = '' ) {
	return sprintf(
		'<!-- wp:paragraph --> <p class="wp-block-paragraph">%s</p> <!-- /wp:paragraph -->' . "\n",
		$content
	);
}

/**
 * Wraps a group block.
 */
function noptin_email_wrap_group_block( $content = '', $background_color = '#ffffff' ) {
	ob_start();
	?>
<!-- wp:noptin/group {"style":{"noptin":{"align":"center","color":{"background":"<?php echo esc_attr( $background_color ); ?>"}},"spacing":{"padding":{"top":"20px","right":"20px","bottom":"20px","left":"20px"}}}} -->
<div class="wp-block-noptin-group">
	<!--[if true]>
	
		<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:600px;max-width:100%;">
			<tbody>
				<tr>
					<td class="noptin-block-group__inner" align="center">
	
	<![endif]-->
	<!--[if !true]><!--><div class="noptin-block-group__inner" style="width:100%;max-width:600px;margin-left:auto;margin-right:auto;margin-top:0;margin-bottom:0"><!--<![endif]--><table border="0" cellpadding="0" cellspacing="0" width="100%"><tbody><tr><td style="padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px<?php echo empty( $background_color ) ? '' : ';background-color:' . esc_attr( $background_color ); ?>">{{CONTENT}}</td></tr></tbody></table><!--[if !true]><!--></div><!--<![endif]-->
	<!--[if true]>
	
					</td>
				</tr>
			</tbody>
		</table>
	<![endif]-->
	</div>
<!-- /wp:noptin/group -->
	<?php

	return str_replace( '{{CONTENT}}', $content, ob_get_clean() );
}

/**
 * Returns an array of email recipients.
 *
 * @return array
 */
function noptin_prepare_email_recipients( $unprepared ) {

	// Some people errorneously use semicolons instead of commas.
	if ( is_string( $unprepared ) ) {
		$unprepared = str_replace( ';', ',', $unprepared );
	}

	$recipients = array();

	foreach ( noptin_parse_list( $unprepared, true ) as $recipient ) {
		$track     = false === stripos( $recipient, '--notracking' );
		$recipient = trim( str_ireplace( '--notracking', '', $recipient ) );

		if ( ! empty( $recipient ) ) {
			$recipients[ $recipient ] = $track;
			continue;
		}
	}

	return $recipients;
}

/**
 * Pauses an email campaign.
 *
 * @param int $campaign_id
 * @param string $reason
 * @since 3.0.0
 */
function noptin_pause_email_campaign( $campaign_id, $reason = '', $pause_for = HOUR_IN_SECONDS ) {
	// Skip if not published.
	if ( 'publish' !== get_post_status( $campaign_id ) ) {
		return;
	}

	update_post_meta( $campaign_id, 'paused', 1 );

	if ( ! empty( $reason ) ) {
		update_post_meta( $campaign_id, '_bulk_email_last_error', array( 'message' => $reason ) );
	} else {
		delete_post_meta( $campaign_id, '_bulk_email_last_error' );
	}

	if ( ! empty( $pause_for ) ) {
		schedule_noptin_background_action( time() + $pause_for, 'noptin_resume_email_campaign', $campaign_id );
	}
}

/**
 * Resumes an email campaign.
 *
 * @param int $campaign_id
 * @since 3.0.0
 */
function noptin_resume_email_campaign( $campaign_id ) {
	delete_post_meta( $campaign_id, 'paused' );
	delete_post_meta( $campaign_id, '_bulk_email_last_error' );
	delete_noptin_background_action( 'noptin_resume_email_campaign', $campaign_id );

	do_action( 'noptin_email_campaign_resumed', $campaign_id );
}
add_action( 'noptin_resume_email_campaign', 'noptin_resume_email_campaign' );

/**
 * Supports ecommerce tracking.
 *
 * @since 3.0.0
 * @param int $campaign_id
 */
function noptin_supports_ecommerce_tracking() {
	return apply_filters( 'noptin_supports_ecommerce_tracking', false );
}

/**
 * Records an ecommerce purchase.
 *
 * @since 3.0.0
 * @param array $args {
 *     The purchase arguments.
 *
 *     @type float       $total           The purchase amount.
 *     @type string|null $email           The purchaser’s email address.
 *     @type string      $formatted_total The formatted amount.
 *     @type int         $order_id        The order ID.
 * }
 * @return void
 */
function noptin_record_ecommerce_purchase( $args ) {
	// Skip if ecommerce tracking is disabled...
	if ( ! get_noptin_option( 'enable_ecommerce_tracking', true ) || ! is_array( $args ) ) {
		return;
	}

	// ... or the order id has already been recorded.
	if ( noptin_order_id_recorded( $args['order_id'] ?? 0 ) ) {
		return;
	}

	// Prepare the amount.
	if ( ! is_numeric( $args['total'] ?? null ) || empty( $args['total'] ) ) {
		return;
	}

	$amount = floatval( $args['total'] );

	// Prepare the email address.
	if ( ! is_string( $args['email'] ?? null ) || ! is_email( $args['email'] ) ) {
		return;
	}

	$email_address = sanitize_email( $args['email'] );

	// Fetch the related campaign id ( one clicked either last 30 days or since last purchase ).
	$date_since = \Hizzle\Noptin\Emails\Logs\Main::query(
		array(
			'activity'           => 'purchase',
			'email'              => $email_address,
			'date_created_after' => '-30 days',
			'orderby'            => 'date_created',
			'order'              => 'DESC',
			'number'             => 1,
			'fields'             => 'date_created',
		)
	);
	$date_since = current( $date_since );

	$campaigns = \Hizzle\Noptin\Emails\Logs\Main::query(
		array(
			'activity'           => 'click',
			'email'              => $email_address,
			'date_created_after' => ! empty( $date_since ) ? "{$date_since} UTC" : '-30 days',
			'orderby'            => 'date_created',
			'order'              => 'DESC',
			'number'             => 1,
			'fields'             => 'campaign_id',
		)
	);

	$campaign_id = current( $campaigns );

	// Log the purchase.
	\Hizzle\Noptin\Emails\Logs\Main::create(
		'purchase',
		$campaign_id,
		$email_address,
		$args['order_id'] ?? 0,
		array(
			'formatted_activity_info' => is_string( $args['formatted_total'] ?? null ) ? $args['formatted_total'] : noptin_format_amount( $amount ),
		)
	);

	// Increase campaign stats.
	if ( empty( $campaign_id ) ) {
		return;
	}

	$lifetime_revenue = (float) get_option( 'noptin_emails_lifetime_revenue', 0 );
	update_option( 'noptin_emails_lifetime_revenue', $lifetime_revenue + $amount, false );

	increment_noptin_campaign_stat( $campaign_id, '_revenue', $amount );
}

/**
 * Check if a given order id has already been recorded.
 *
 * @since 3.0.0
 * @param string $order_id The prefixed order id, e.g, $integration::1234.
 * @param string $activity The activity type. Either purchase or refund.
 * @return bool True if the order id has already been recorded.
 */
function noptin_order_id_recorded( $order_id, $activity = 'purchase' ) {
	if ( empty( $order_id ) || ! noptin_has_alk() ) {
		return true;
	}

	// Fetch the related log id.
	$count = \Hizzle\Noptin\Emails\Logs\Main::query(
		array(
			'activity'      => $activity,
			'activity_info' => $order_id,
		),
		'count'
	);

	return $count > 0;
}

/**
 * Returns the maximum number of emails per period.
 *
 * @return int Zero if unlimited.
 */
function noptin_max_emails_per_period() {
	return apply_filters( 'noptin_max_emails_per_period', get_noptin_option( 'per_hour', 0 ) );
}

/**
 * Checks if the email sending limit has been reached.
 */
function noptin_email_sending_limit_reached() {
	$max_emails = (int) noptin_max_emails_per_period();
	if ( empty( $max_emails ) ) {
		return false; // No limit.
	}

	$has_reached_limit = ! empty( $max_emails ) && noptin_emails_sent_this_period() >= $max_emails;
	return apply_filters( 'noptin_email_sending_limit_reached', $has_reached_limit );
}

/**
 * Returns the email sending rolling period in seconds.
 *
 * @return int Number of seconds for the rolling period. Default is 1 hour (3600 seconds).
 */
function noptin_get_email_sending_rolling_period() {
	$period = get_noptin_option( 'email_sending_rolling_period', '1hours' );
	if ( empty( $period ) ) {
		$period = '1hours'; // Default to 1 hour.
	}

	$seconds = noptin_convert_time_unit_to_seconds( $period );

	if ( empty( $seconds ) || ! is_numeric( $seconds ) || $seconds < 1 ) {
		$seconds = HOUR_IN_SECONDS; // Default to 1 hour.
	}

	return apply_filters( 'noptin_email_sending_rolling_period', $seconds );
}

/**
 * Returns the number of emails sent this period.
 *
 * @return int Number of emails sent this period.
 */
function noptin_emails_sent_this_period() {
	$args = array(
		'activity'           => 'send',
		'date_created_after' => gmdate( 'Y-m-d H:i:s e', time() - noptin_get_email_sending_rolling_period() ),
	);

	$emails_sent = (int) Hizzle\Noptin\Emails\Logs\Main::query( $args, 'count' );

	return apply_filters( 'noptin_emails_sent_this_period', $emails_sent );
}

/**
 * Returns the next email send time.
 *
 * @return int|false
 */
function noptin_get_next_email_send_time() {

	$args = array(
		'activity'           => 'send',
		'date_created_after' => gmdate( 'Y-m-d H:i:s e', time() - noptin_get_email_sending_rolling_period() ),
		'orderby'            => 'date_created',
		'order'              => 'ASC',
		'number'             => 1,
	);

	/** @var \Hizzle\Noptin\Emails\Logs\Log[] $oldest_log */
	$oldest_log = Hizzle\Noptin\Emails\Logs\Main::query( $args );

	if ( empty( $oldest_log ) ) {
		return false;
	}

	/** @var \Hizzle\Store\Date_Time $time */
	$time = $oldest_log[0]->get( 'date_created' );
	if ( ! $time instanceof Hizzle\Store\Date_Time ) {
		return false;
	}

	$time = $time->getTimestamp() + noptin_get_email_sending_rolling_period();
	if ( $time < time() ) {
		// If the time is in the past, we return the current time.
		return time();
	}

	return $time;
}

/**
 * Checks if an email campaign was sent to a given email address.
 *
 * @param int $campaign_id
 * @param string $email_address
 * @param int|string $since
 */
function noptin_email_campaign_sent_to( $campaign_id, $email_address, $since = false ) {
	return \Hizzle\Noptin\Emails\Logs\Main::did_activity(
		'send',
		$campaign_id,
		$email_address,
		$since
	);
}
