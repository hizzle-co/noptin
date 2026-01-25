<?php

/**
 * Container for a single email log.
 *
 * @version 1.0.0
 */

namespace Hizzle\Noptin\Emails\Logs;

defined( 'ABSPATH' ) || exit;

/**
 * Email Log.
 */
class Log extends \Hizzle\Store\Record {

	/**
	 * Get the campaign title.
	 *
	 * @return string
	 */
	public function get_campaign_title() {
		$id = $this->get( 'campaign_id' );

		if ( empty( $id ) ) {
			return '';
		}

		$post = \Hizzle\Noptin\Emails\Email::from( $id );

		if ( ! $post->exists() ) {
			return '(Deleted)';
		}

		$name = $post->name;

		if ( empty( $name ) ) {
			$name = $post->subject;
		}

		return $name;
	}

	/**
	 * Get the campaign URL.
	 *
	 * @return string
	 */
	public function get_campaign_url() {
		$id = $this->get( 'campaign_id' );

		if ( empty( $id ) ) {
			return '';
		}

		$post = \Hizzle\Noptin\Emails\Email::from( $id );

		if ( ! $post->exists() ) {
			return '';
		}

		return $post->get_edit_url();
	}

	/**
	 * Gets the log's formatted activity info.
	 *
	 * @return string
	 */
	public function get_formatted_activity_info( $context = 'view' ) {
		$info = $this->get_meta( 'formatted_activity_info' );

		if ( is_string( $info ) ) {
			return wp_kses_post( $info );
		}

		$info = $this->get( 'activity_info', $context );

		if ( 'click' !== $this->get( 'activity' ) || empty( $info ) ) {
			return is_string( $info ) ? wp_kses_post( $info ) : '';
		}

		$text = str_replace( array( home_url(), 'https://', 'http://' ), '', $info );
		if ( strlen( $text ) > 15 ) {
			$text = substr( $text, 0, 15 ) . '...';
		}

		return sprintf(
			'<a href="%s" title="%s" target="_blank">%s</a>',
			esc_url( $info ),
			esc_attr( $info ),
			esc_html( $text )
		);
	}
}
