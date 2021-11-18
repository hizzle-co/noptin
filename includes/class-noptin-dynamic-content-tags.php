<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class Noptin_Dynamic_Content_Tags
 *
 * @access private
 * @ignore
 */
abstract class Noptin_Dynamic_Content_Tags {

	/**
	 * @var string The escape function for replacement values.
	 */
	protected $escape_function = null;

	/**
	 * @var array Array of registered dynamic content tags
	 */
	protected $tags = array();

	/**
	 * Register template tags
	 */
	protected function register() {

		// Global tags can go here
		$this->tags['cookie'] = array(
			'description' => sprintf( __( 'Data from a cookie.', 'newsletter-optin-box' ) ),
			'callback'    => array( $this, 'get_cookie' ),
			'example'     => "cookie name='my_cookie' default='Default Value'",
		);

		$this->tags['email'] = array(
			'description' => __( 'The email address of the current visitor (if known).', 'newsletter-optin-box' ),
			'callback'    => array( $this, 'get_email' ),
		);

		$this->tags['current_url'] = array(
			'description' => __( 'The URL of the page.', 'newsletter-optin-box' ),
			'callback'    => 'noptin_get_request_url',
		);

		$this->tags['current_path'] = array(
			'description' => __( 'The path of the page.', 'newsletter-optin-box' ),
			'replacement' => esc_html( $_SERVER['REQUEST_URI'] ),
		);

		$this->tags['date'] = array(
			'description' => sprintf( __( 'The current date. Example: %s.', 'newsletter-optin-box' ), '<strong>' . date_i18n( get_option( 'date_format' ), current_time( 'timestamp' ) ) . '</strong>' ),
			'replacement' => date_i18n( get_option( 'date_format' ), current_time( 'timestamp' ) ),
		);

		$this->tags['time'] = array(
			'description' => sprintf( __( 'The current time. Example: %s.', 'newsletter-optin-box' ), '<strong>' . date_i18n( get_option( 'time_format' ), current_time( 'timestamp' ) ) . '</strong>' ),
			'replacement' => date_i18n( get_option( 'time_format' ), current_time( 'timestamp' ) ),
		);

		$this->tags['language'] = array(
			'description' => sprintf( __( 'The current language. Example: %s.', 'newsletter-optin-box' ), '<strong>' . get_locale() . '</strong>' ),
			'callback'    => 'get_locale',
		);

		$this->tags['ip'] = array(
			'description' => sprintf( __( 'The visitor\'s IP address. Example: %s.', 'newsletter-optin-box' ), '<strong>' . noptin_get_user_ip() . '</strong>' ),
			'callback'    => 'noptin_get_user_ip',
		);

		$this->tags['subscriber'] = array(
			'description' => sprintf( __( "A custom field's value of the current subscriber (if known).", 'newsletter-optin-box' ) ),
			'callback'    => array( $this, 'get_subscriber_field' ),
			'example'     => "subscriber field='first_name' default='there'",
		);

		$this->tags['user'] = array(
			'description' => sprintf( __( 'The property of the currently logged-in user.', 'newsletter-optin-box' ) ),
			'callback'    => array( $this, 'get_user_property' ),
			'example'     => "user property='user_email'",
		);

		$this->tags['post'] = array(
			'description' => sprintf( __( 'Property of the current page or post.', 'newsletter-optin-box' ) ),
			'callback'    => array( $this, 'get_post_property' ),
			'example'     => "post property='ID'",
		);

	}

	/**
	 * @return array
	 */
	public function all() {
		if ( $this->tags === array() ) {
			$this->register();
		}

		return $this->tags;
	}

	/**
	 * @param array $matches
	 *
	 * @return string
	 */
	protected function replace_tag( $matches ) {
		$tags = $this->all();
		$tag  = $matches[1];

		if ( isset( $tags[ $tag ] ) ) {
			$config      = $tags[ $tag ];
			$replacement = '';

			if ( isset( $config['replacement'] ) ) {
				$replacement = $config['replacement'];
			} else if ( isset( $config['callback'] ) ) {

				// Parse attributes.
				$attributes = array();
				if ( isset( $matches[2] ) ) {
					$attribute_string = $matches[2];
					$attributes       = shortcode_parse_atts( $attribute_string );
				}

				// call function
				$replacement = call_user_func( $config['callback'], $attributes, $tag );
			}

			if ( is_callable( $this->escape_function ) ) {
				$replacement = call_user_func( $this->escape_function, $replacement );
			}

			return $replacement;
		}

		// default to not replacing it
		return $matches[0];
	}

	/**
	 * @param string $string The string containing dynamic content tags.
	 * @param string $escape_function Escape mode for the replacement value. Leave empty for no escaping.
	 * @return string
	 */
	protected function replace( $string, $escape_function = '' ) {
		$this->escape_function = $escape_function;

		// Replace strings like this: {tagname attr="value"}.
		$string = preg_replace_callback( '/\{(\w+)(\ +(?:(?!\{)[^}\n])+)*\}/', array( $this, 'replace_tag' ), $string );

		// Call again to take care of nested variables.
		$string = preg_replace_callback( '/\{(\w+)(\ +(?:(?!\{)[^}\n])+)*\}/', array( $this, 'replace_tag' ), $string );
		return $string;
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	protected function replace_in_html( $string ) {
		return $this->replace( $string, 'esc_html' );
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	protected function replace_in_attributes( $string ) {
		return $this->replace( $string, 'esc_attr' );
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	protected function replace_in_url( $string ) {
		return $this->replace( $string, 'urlencode' );
	}

	/**
	 * Gets data variable from cookie.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	protected function get_cookie( $args = array() ) {
		if ( empty( $args['name'] ) ) {
			return '';
		}

		$name    = $args['name'];
		$default = isset( $args['default'] ) ? $args['default'] : '';

		if ( isset( $_COOKIE[ $name ] ) ) {
			return esc_html( stripslashes( $_COOKIE[ $name ] ) );
		}

		return esc_html( $default );
	}

	/*
	 * Custom field value of the current subscriber (if known).
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	protected function get_subscriber_field( $args = array() ) {
		$field      = empty( $args['field'] ) ? 'first_name' : $args['field'];
		$default    = isset( $args['default'] ) ? $args['default'] : '';
		$subscriber = new Noptin_Subscriber( get_current_noptin_subscriber_id() );

		// Ensure the subscriber and the field exist.
		if ( ! $subscriber->exists() || ! $subscriber->has_prop( $field ) ) {
			return esc_html( $default );
		}

		$all_fields = wp_list_pluck( get_noptin_custom_fields(), 'type', 'merge_tag' );

		// Format field value.
		if ( isset( $all_fields[ $field ] ) ) {

			$value = $subscriber->get( $field );
			if ( 'checkbox' == $all_fields[ $field ] ) {
				return ! empty( $value ) ? __( 'Yes', 'newsletter-optin-box' ) : __( 'No', 'newsletter-optin-box' );
			}

			$value = wp_kses_post(
					format_noptin_custom_field_value(
					$subscriber->get( $field ),
					$all_fields[ $field ],
					$subscriber
				)
			);

			if ( "&mdash;" !== $value ) {
				return $value;
			}
		}

		return esc_html( $default );
	}

	/*
	 * Get property of currently logged-in user
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	protected function get_user_property( $args = array() ) {
		$property = empty( $args['property'] ) ? 'user_email' : $args['property'];
		$default  = isset( $args['default'] ) ? $args['default'] : '';
		$user     = wp_get_current_user();

		if ( $user instanceof WP_User && isset( $user->{$property} ) ) {
			return esc_html( $user->{$property} );
		}

		return esc_html( $default );
	}

	/*
	 * Get property of viewed post
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	protected function get_post_property( $args = array() ) {
		global $post;
		$property = empty( $args['property'] ) ? 'ID' : $args['property'];
		$default  = isset( $args['default'] ) ? $args['default'] : '';

		if ( $post instanceof WP_Post && isset( $post->{$property} ) ) {
			return 'post_content' === $property ? wp_kses_post( $post->{$property} ) : esc_html( $post->{$property} );
		}

		return esc_html( $default );
	}

	/**
	 * @return string
	 */
	protected function get_email() {

		// Trying retrieving from posted email address.
		if ( ! empty( noptin()->forms->listener->submitted['email'] ) ) {
			return sanitize_email( noptin()->forms->listener->submitted['email'] );
		}

		if ( ! empty( noptin()->forms->listener->submitted['noptin_fields']['email'] ) ) {
			return sanitize_email( noptin()->forms->listener->submitted['noptin_fields']['email'] );
		}

		// then , try logged-in user.
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			return sanitize_email( $user->user_email );
		}

		return '';
	}

}
