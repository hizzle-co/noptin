<?php

namespace Hizzle\Noptin\DB;

/**
 * Container for a single subscriber.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Subscriber.
 */
class Subscriber extends \Hizzle\Store\Record {

	/**
	 * Returns the deprecated subscriber object.
	 *
	 * @return \Noptin_Subscriber
	 */
	public function get_deprecated_subscriber() {
		return new \Noptin_Subscriber( $this->get_id() );
	}

	/**
	 * Returns the subscriber's full name.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		return trim( $this->get_first_name( $context ) . ' ' . $this->get_last_name( $context ) );
	}

	/**
	 * Sets the subscriber's full name.
	 *
	 * @param string $value Full name.
	 */
	public function set_name( $value ) {

		if ( empty( $value ) ) {
			return;
		}

		$parts = explode( ' ', $value, 2 );

		$this->set_first_name( array_shift( $parts ) );

		if ( ! empty( $parts ) ) {
			$this->set_last_name( array_pop( $parts ) );
		}
	}

	/**
	 * Returns the first name.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_first_name( $context = 'view' ) {
		return $this->get_prop( 'first_name', $context );
	}

	/**
	 * Sets the first name.
	 *
	 * @param string $value First name.
	 */
	public function set_first_name( $value ) {
		$this->set_prop( 'first_name', sanitize_text_field( $value ) );
	}

	/**
	 * Returns the last name.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_last_name( $context = 'view' ) {
		return $this->get_prop( 'last_name', $context );
	}

	/**
	 * Sets the last name.
	 *
	 * @param string $value Last name.
	 */
	public function set_last_name( $value ) {
		$this->set_prop( 'last_name', sanitize_text_field( $value ) );
	}

	/**
	 * Returns the email address.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_email( $context = 'view' ) {
		return $this->get_prop( 'email', $context );
	}

	/**
	 * Sets the email address.
	 *
	 * @param string $value Email address.
	 */
	public function set_email( $value ) {
		$this->set_prop( 'email', sanitize_email( $value ) );
	}

	/**
	 * Checks if the subscriber is active.
	 *
	 * @return bool
	 */
	public function is_active() {
		return 'subscribed' === $this->get_status();
	}

	/**
	 * Returns the status.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
	}

	/**
	 * Sets the status.
	 *
	 * @param string $value Status.
	 */
	public function set_status( $value ) {
		if ( array_key_exists( $value, noptin_get_subscriber_statuses() ) ) {

			// If unsubscribing, record the activity.
			if ( $this->object_read && $this->is_active() && 'unsubscribed' === $value ) {
				$this->record_activity( 'Unsubscribed from the newsletter' );
			}

			$this->set_prop( 'status', $value );
		}
	}

	/**
	 * Gets the subscriber source.
	 *
	 * @return string
	 */
	public function get_source( $context = 'view' ) {
		return $this->get_prop( 'source', $context );
	}

	/**
	 * Sets the subscriber source.
	 *
	 * @param string $value Source.
	 */
	public function set_source( $value ) {
		$source = is_null( $value ) ? null : sanitize_text_field( $value );
		$this->set_prop( 'source', $source );
	}

	/**
	 * Gets the subscriber ip address.
	 *
	 * @return string
	 */
	public function get_ip_address( $context = 'view' ) {
		return $this->get_prop( 'ip_address', $context );
	}

	/**
	 * Sets the subscriber ip address.
	 *
	 * @param string $value IP address.
	 */
	public function set_ip_address( $value ) {
		$ip_address = is_null( $value ) ? null : sanitize_text_field( $value );
		$this->set_prop( 'ip_address', $ip_address );
	}

	/**
	 * Gets the subscriber conversion page.
	 *
	 * @return string
	 */
	public function get_conversion_page( $context = 'view' ) {
		return $this->get_prop( 'conversion_page', $context );
	}

	/**
	 * Sets the subscriber conversion page.
	 *
	 * @param string $value Conversion page.
	 */
	public function set_conversion_page( $value ) {
		$conversion_page = is_null( $value ) ? null : esc_url_raw( $value );
		$this->set_prop( 'conversion_page', $conversion_page );
	}

	/**
	 * Gets the subscriber confirmed status.
	 *
	 * @return bool
	 */
	public function get_confirmed( $context = 'view' ) {
		return $this->get_prop( 'confirmed', $context );
	}

	/**
	 * Sets the subscriber confirmed status.
	 *
	 * @param bool $value Confirmed status.
	 */
	public function set_confirmed( $value ) {
		$value = boolval( $value );
		$this->set_prop( 'confirmed', $value );

		// If the subscriber is confirmed, set the status to subscribed.
		if ( $value && $this->object_read && $this->exists() && 'subscribed' !== $this->get_status() ) {
			$this->set_status( 'subscribed' );
			$this->record_activity( 'Confirmed email address' );
		}
	}

	/**
	 * Gets the subscriber's confirmation key.
	 *
	 * @return string
	 */
	public function get_confirm_key( $context = 'view' ) {
		$confirm_key = $this->get_prop( 'confirm_key', $context );

		if ( empty( $confirm_key ) ) {
			$confirm_key = md5( wp_generate_password( 32, false ) . uniqid() );
			$this->set_confirm_key( $confirm_key );
		}

		return $confirm_key;
	}

	/**
	 * Sets the subscriber's confirmation key.
	 *
	 * @param string $value Confirmation key.
	 */
	public function set_confirm_key( $value ) {
		$confirm_key = empty( $value ) ? md5( wp_generate_password( 32, false ) . uniqid() ) : sanitize_text_field( $value );
		$this->set_prop( 'confirm_key', $confirm_key );
	}

	/**
	 * Get the subscriber's creation date.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return \Hizzle\Store\Date_Time|null
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Set the subscriber's creation date.
	 *
	 * @param \Hizzle\Store\Date_Time|string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Get the subscriber's modified date.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return \Hizzle\Store\Date_Time|null
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Set the subscriber's modified date.
	 *
	 * @param \Hizzle\Store\Date_Time|string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_modified( $date = null ) {
		$this->set_date_prop( 'date_modified', $date );
	}

	/**
	 * Fetches the subscriber's activity.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return array
	 */
	public function get_activity( $context = 'view' ) {
		$activity = json_decode( $this->get_prop( 'activity', $context ), true );
		return empty( $activity ) ? array() : $activity;
	}

	/**
	 * Sets the subscriber's activity.
	 *
	 * @param array|string $activity Activity.
	 */
	public function set_activity( $activity ) {
		$activity = empty( $activity ) ? array() : $activity;
		$activity = is_array( $activity ) ? wp_json_encode( $activity ) : $activity;
		$this->set_prop( 'activity', $activity );
	}

	/**
	 * Records a subscriber's activity.
	 *
	 * @param string $activity Activity.
	 */
	public function record_activity( $activity ) {
		$activity   = $this->get_activity();
		$activity[] = array(
			'time'    => time(),
			'content' => $activity,
		);

		// Only save the last 30 activities.
		if ( count( $activity ) > 30 ) {
			$activity = array_slice( $activity, -30 );
		}

		$this->set_activity( $activity );
	}

	/**
	 * Fetches the subscriber's sent email campaigns.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return array
	 */
	public function get_sent_campaigns( $context = 'view' ) {
		$sent_campaigns = json_decode( $this->get_prop( 'sent_campaigns', $context ), true );
		return empty( $sent_campaigns ) ? array() : $sent_campaigns;
	}

	/**
	 * Sets the subscriber's sent email campaigns.
	 *
	 *  @param array|string $sent_campaigns Sent email campaigns.
	 */
	public function set_sent_campaigns( $sent_campaigns ) {
		$sent_campaigns = empty( $sent_campaigns ) ? array() : $sent_campaigns;
		$sent_campaigns = is_array( $sent_campaigns ) ? wp_json_encode( $sent_campaigns ) : $sent_campaigns;
		$this->update_meta( 'sent_campaigns', $sent_campaigns );
	}

	/**
	 * Records a subscriber's sent email campaign.
	 *
	 * @param int $campaign_id Campaign ID.
	 */
	public function record_sent_campaign( $campaign_id ) {
		$campaign_id    = (string) $campaign_id;
		$sent_campaigns = $this->get_sent_campaigns();

		if ( ! isset( $sent_campaigns[ $campaign_id ] ) ) {
			$sent_campaigns[ $campaign_id ] = array(
				'time'         => array( time() ),
				'opens'        => array(),
				'clicks'       => array(),
				'unsubscribed' => false,
			);
		} else {
			$sent_campaigns[ $campaign_id ]['time'][] = time();
		}

		$this->set_sent_campaigns( $sent_campaigns );
		$this->save();
	}

	/**
	 * Records an opened email campaign.
	 *
	 * @param int $campaign_id Campaign ID.
	 */
	public function record_opened_campaign( $campaign_id ) {
		$campaign_id    = (string) $campaign_id;
		$sent_campaigns = $this->get_sent_campaigns();

		if ( isset( $sent_campaigns[ $campaign_id ] ) ) {
			$sent_campaigns[ $campaign_id ]['opens'][] = time();
			$this->set_sent_campaigns( $sent_campaigns );
			$this->save();

			// Fire action.
			if ( 1 === count( $sent_campaigns[ $campaign_id ]['opens'] ) ) {
				do_action( 'log_noptin_subscriber_campaign_open', $this->get_id(), $campaign_id );
			}
		}
	}

	/**
	 * Records a clicked link in an email campaign.
	 *
	 * @param int $campaign_id Campaign ID.
	 * @param string $url URL.
	 */
	public function record_clicked_link( $campaign_id, $url ) {
		$campaign_id    = (string) $campaign_id;
		$sent_campaigns = $this->get_sent_campaigns();

		if ( ! isset( $sent_campaigns[ $campaign_id ] ) ) {

			if ( ! isset( $sent_campaigns[ $campaign_id ]['clicks'][ $url ] ) ) {
				$sent_campaigns[ $campaign_id ]['clicks'][ $url ] = array();
			}

			$sent_campaigns[ $campaign_id ]['clicks'][ $url ][] = time();

			$this->set_sent_campaigns( $sent_campaigns );
			$this->save();

			// Fire action.
			if ( 1 === count( $sent_campaigns[ $campaign_id ]['clicks'][ $url ] ) ) {
				do_action( 'log_noptin_subscriber_campaign_click', $this->get_id(), $campaign_id, $url );
			}
		}
	}

	/**
	 * Records an unsubscribed email campaign.
	 *
	 * @param int $campaign_id Campaign ID.
	 */
	public function record_unsubscribed_campaign( $campaign_id ) {
		$campaign_id    = (string) $campaign_id;
		$sent_campaigns = $this->get_sent_campaigns();

		if ( isset( $sent_campaigns[ $campaign_id ] ) ) {
			$sent_campaigns[ $campaign_id ]['unsubscribed'] = true;
			$this->set_sent_campaigns( $sent_campaigns );
			$this->save();
		}
	}

	/**
	 * Retrieves the subscriber's edit URL.
	 *
	 * @return string
	 */
	public function get_edit_url() {
		return add_query_arg(
			array(
				'page'       => 'noptin-subscribers',
				'subscriber' => $this->get_id(),
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Returns the unsubscribe URL for the subscriber.
	 *
	 * @return string
	 */
	public function get_unsubscribe_url() {
		return get_noptin_action_url(
			'unsubscribe',
			noptin_encrypt(
				wp_json_encode(
					array( 'sid' => $this->get_id() )
				)
			)
		);
	}

	/**
	 * Returns the resubsribe URL for the subscriber.
	 *
	 * @return string
	 */
	public function get_resubscribe_url() {
		return get_noptin_action_url(
			'resubscribe',
			noptin_encrypt(
				wp_json_encode(
					array( 'sid' => $this->get_id() )
				)
			)
		);
	}

	/**
	 * Returns the subscription confirmation URL for the subscriber.
	 *
	 * @return string
	 */
	public function get_confirm_subscription_url() {
		return get_noptin_action_url(
			'confirm',
			noptin_encrypt(
				wp_json_encode(
					array( 'sid' => $this->get_id() )
				)
			)
		);
	}
}
