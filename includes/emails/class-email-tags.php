<?php
/**
 * Forms API: Dynamic Email Tags.
 *
 * Allows users to use dynamic tags in emails.
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Allows users to use dynamic tags in emails.
 *
 * @internal
 * @access private
 * @since 1.7.0
 * @ignore
 */
class Noptin_Email_Tags extends Noptin_Dynamic_Content_Tags {

	/**
	 * @var Noptin_Subscriber
	 */
	public $subscriber;

	/**
	 * @var WP_Post
	 */
	public $post;

	/**
	 * Register core hooks.
	 */
	public function add_hooks() {
		add_filter( 'noptin_merge_email_subject', array( $this, 'replace_in_subject' ), 10, 2 );
		add_filter( 'noptin_merge_email_body', array( $this, 'replace_in_body' ), 10, 2 );
	}

	/**
	 * @param string $string The string containing dynamic content tags.
	 * @param string $escape_function Escape mode for the replacement value. Leave empty for no escaping.
	 * @return string
	 */
	protected function replace( $string, $escape_function = '' ) {
		$this->escape_function = $escape_function;

		// Replace strings like this: [[tagname attr="value"]].
		$string = preg_replace_callback( '/\[\[(\w+)(\ +(?:(?!\[)[^\]\n])+)*\]\]/', array( $this, 'replace_tag' ), $string );

		// Call again to take care of nested variables.
		$string = preg_replace_callback( '/\[\[(\w+)(\ +(?:(?!\[)[^\]\n])+)*\]\]/', array( $this, 'replace_tag' ), $string );
		return $string;
	}

	/**
	 * Replaces in subject
	 *
	 * @param string $string
	 * @param Noptin_Subscriber $subscriber
	 * @return string
	 */
	public function replace_in_subject( $string, $subscriber ) {

		$this->subscriber = $subscriber;

		return $this->replace( $string, 'strip_tags' );
	}

	/**
	 * Replaces in the email body
	 *
	 * @param string $string
	 * @param Noptin_Subscriber $subscriber
	 * @return string
	 */
	public function replace_in_body( $string, $subscriber ) {

		$this->subscriber = $subscriber;

		return $this->replace( $string, 'wp_kses_post' );
	}

	/**
	 * Register template tags
	 */
	public function register() {

		$this->tags['date'] = array(
			'description' => sprintf( __( 'The current date. Example: %s.', 'newsletter-optin-box' ), '<strong>' . date_i18n( get_option( 'date_format' ), current_time( 'timestamp' ) ) . '</strong>' ),
			'replacement' => date_i18n( get_option( 'date_format' ), current_time( 'timestamp' ) ),
		);

		$this->tags['time'] = array(
			'description' => sprintf( __( 'The current time. Example: %s.', 'newsletter-optin-box' ), '<strong>' . date_i18n( get_option( 'time_format' ), current_time( 'timestamp' ) ) . '</strong>' ),
			'replacement' => date_i18n( get_option( 'time_format' ), current_time( 'timestamp' ) ),
		);

		foreach ( get_noptin_custom_fields() as $field ) {

			$merge_tag = sanitize_key( $field['merge_tag'] );

			$this->tags[ $merge_tag ] = array(
				'description' => strip_tags( $field['label'] ),
				'callback'    => array( $this, 'get_custom_field' ),
				'example'     => $merge_tag . " default=''",
			);

		}

		$this->tags['subscriber_count'] = array(
			'description' => __( 'Replaced with the total number of subscribers', 'newsletter-optin-box' ),
			'callback'    => array( $this, 'get_subscriber_count' ),
		);

		$this->tags['user'] = array(
			'description' => __( "A custom field's value of the WordPress user (if known).", 'newsletter-optin-box' ),
			'callback'    => array( $this, 'get_user_property' ),
			'example'     => "user property='user_email'",
		);

		$this->tags['post'] = array(
			'description' => __( 'Property of the page or post.', 'newsletter-optin-box' ),
			'callback'    => array( $this, 'get_post_property' ),
			'example'     => "post property='ID'",
		);

	}
  
	/**
	 * Returns the number of subscribers.
	 *
	 * @return int
	 */
	public function get_subscriber_count() {
		return get_noptin_subscribers_count();
	}

	/**
	 * Returns the unsubscribe URL
	 *
	 * @return string
	 */
	public function get_unsubscribe_url() {

		// Abort if no subscriber specified.
		if ( empty( $this->subscriber ) || ! $this->subscriber->exists() ) {
			return home_url();
		}

		// Either unsubscribe the user or the subscriber.
		$subscriber = $this->subscriber->is_virtual ? $this->subscriber->email : $this->subscriber->confirm_key;
		return get_noptin_action_url( 'unsubscribe', $subscriber );
	}

	/*
	 * Get property of related user.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	protected function get_user_property( $args = array() ) {
		$property = empty( $args['property'] ) ? 'user_email' : $args['property'];
		$default  = isset( $args['default'] ) ? $args['default'] : '';

		// Abort if we have no subscriber.
		if ( empty( $this->subscriber ) ) {
			return esc_html( $default );
		}

		// Fetch the user id.
		$user = $this->subscriber->get_wp_user();

		if ( empty( $user ) ) {
			return esc_html( $default );
		}

		// Fetch user object.
		$user = new WP_User( $user );

		if ( $user instanceof WP_User && isset( $user->{$property} ) ) {
			return esc_html( $user->{$property} );
		}

		return esc_html( $default );
	}

	/**
	 * Custom field value of the current subscriber (if known).
	 *
	 * @param array $args
	 * @param string $field
	 * @return string
	 */
	protected function get_custom_field( $args = array(), $field ) {
		$default = isset( $args['default'] ) ? $args['default'] : '';

		// Abort if no subscriber.
		if ( empty( $this->subscriber ) || ! $this->subscriber->has_prop( $field ) ) {
			return esc_html( $default );
		}

		$all_fields = wp_list_pluck( get_noptin_custom_fields(), 'type', 'merge_tag' );

		// Format field value.
		if ( isset( $all_fields[ $field ] ) ) {

			$value = $this->subscriber->get( $field );
			if ( 'checkbox' == $all_fields[ $field ] ) {
				return ! empty( $value ) ? __( 'Yes', 'newsletter-optin-box' ) : __( 'No', 'newsletter-optin-box' );
			}

			$value = wp_kses_post(
				format_noptin_custom_field_value(
					$this->subscriber->get( $field ),
					$all_fields[ $field ],
					$this->subscriber
				)
			);

			if ( "&mdash;" !== $value ) {
				return $value;
			}
		}

		return esc_html( $default );
	}

	/*
	 * Get property of current post
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	protected function get_post_property( $args = array() ) {

		$post     = empty( $this->post ) ? false: $this->post;
		$property = empty( $args['property'] ) ? 'ID' : $args['property'];
		$default  = isset( $args['default'] ) ? $args['default'] : '';

		if ( $post instanceof WP_Post && isset( $post->{$property} ) ) {
			return 'post_content' === $property ? wp_kses_post( $post->{$property} ) : esc_html( $post->{$property} );
		}

		return esc_html( $default );
	}

}
