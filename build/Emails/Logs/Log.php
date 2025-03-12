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

		$post = get_post( $id );

		if ( ! $post ) {
			return '';
		}

		return $post->post_title;
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

		return get_edit_post_link( $id );
	}
}
