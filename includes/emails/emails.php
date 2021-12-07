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
 * Processes email tags.
 *
 * @since 1.7.0
 * @param string $content
 * @param Noptin_Subscriber $subscriber
 * @param string $context Either body or subject.
 * @return bool
 */
function noptin_handle_email_tags( $content, $subscriber, $context = 'body' ) {

	if ( $context === 'body' ) {
		return apply_filters( 'noptin_merge_email_body', $content, $subscriber );
	}

	return apply_filters( 'noptin_merge_email_subject', $content, $subscriber );

}
