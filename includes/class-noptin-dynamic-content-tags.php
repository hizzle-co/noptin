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
	public $tags = array();

	/**
	 * Whether we're only replacing partial merge tags.
	 */
	public $is_partial = false;

	/**
	 * Registers a new tag
	 */
	public function add_tag( $tag, $details ) {
		$this->tags[ $tag ] = $details;
	}

	/**
	 * Removes a tag
	 */
	public function remove_tag( $tag ) {

		if ( isset( $this->tags[ $tag ] ) ) {
			unset( $this->tags[ $tag ] );
		}

	}

	/**
	 * Register template tags
	 */
	protected function register() {

		// Global tags can go here
		$this->tags['cookie'] = array(
			'description' => __( 'Data from a cookie.', 'newsletter-optin-box' ),
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
			'no_args'     => true,
		);

		$this->tags['current_path'] = array(
			'description' => __( 'The path of the page.', 'newsletter-optin-box' ),
			'replacement' => esc_html( $_SERVER['REQUEST_URI'] ),
		);

		$this->tags['date'] = array(
			// translators: %s is the current date.
			'description' => sprintf( __( 'The current date. Example: %s.', 'newsletter-optin-box' ), '<strong>' . date_i18n( get_option( 'date_format' ) ) . '</strong>' ),
			'callback'    => 'Noptin_Dynamic_Content_Tags::get_date',
			'example'     => 'date format="j, F Y" localized=1',
		);

		$this->tags['time'] = array(
			// translators: %s is the current time.
			'description' => sprintf( __( 'The current time. Example: %s.', 'newsletter-optin-box' ), '<strong>' . date_i18n( get_option( 'time_format' ) ) . '</strong>' ),
			'replacement' => date_i18n( get_option( 'time_format' ) ),
		);

		$this->tags['language'] = array(
			// translators: %s is the current language.
			'description' => sprintf( __( 'The current language. Example: %s.', 'newsletter-optin-box' ), '<strong>' . get_locale() . '</strong>' ),
			'callback'    => 'get_locale',
			'no_args'     => true,
		);

		$this->tags['ip'] = array(
			// translators: %s is the current IP address.
			'description' => sprintf( __( 'The visitor\'s IP address. Example: %s.', 'newsletter-optin-box' ), '<strong>' . noptin_get_user_ip() . '</strong>' ),
			'callback'    => 'noptin_get_user_ip',
			'no_args'     => true,
		);

		$this->tags['subscriber'] = array(
			'description' => __( "A custom field's value of the current subscriber (if known).", 'newsletter-optin-box' ),
			'callback'    => array( $this, 'get_subscriber_field' ),
			'example'     => "subscriber field='first_name' default='there'",
		);

		$this->tags['user'] = array(
			'description' => __( 'The property of the currently logged-in user.', 'newsletter-optin-box' ),
			'callback'    => array( $this, 'get_user_property' ),
			'example'     => "user property='user_email'",
		);

		$this->tags['post'] = array(
			'description' => __( 'Property of the current page or post.', 'newsletter-optin-box' ),
			'callback'    => array( $this, 'get_post_property' ),
			'example'     => "post property='ID'",
		);

	}

	/**
	 * @return array
	 */
	public function all() {
		if ( array() === $this->tags ) {
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

		// Abort if tag is not supported.
		if ( ! isset( $tags[ $tag ] ) ) {
			return $matches[0];
		}

		// (Maybe) Skip non-partial tags.
		if ( $this->is_partial && empty( $tags[ $tag ]['partial'] ) ) {
			return $matches[0];
		}

		// Generate replacement.
		$config      = $tags[ $tag ];
		$replacement = '';

		// Parse attributes.
		$attributes = array();
		if ( isset( $matches[2] ) ) {
			$attribute_string = $matches[2];
			$attributes       = shortcode_parse_atts( $attribute_string );
		}

		if ( isset( $config['replacement'] ) ) {
			$replacement = $config['replacement'];
		} elseif ( isset( $config['callback'] ) ) {

			// call function
			if ( empty( $config['no_args'] ) ) {
				$replacement = call_user_func( $config['callback'], $attributes, $tag );
			} else {
				$replacement = call_user_func( $config['callback'] );
			}
		}

		if ( is_array( $replacement ) ) {
			if ( ! is_scalar( current( $replacement ) ) ) {
				$replacement = wp_json_encode( $replacement );
			} else {
				$replacement = implode( ', ', $replacement );
			}
		}

		// Convert booleans.
		if ( is_bool( $replacement ) ) {
			$replacement = $replacement ? '1' : '0';
		}

		if ( ! is_scalar( $replacement ) ) {
			$replacement = wp_json_encode( $replacement );
		}

		$replacement = (string) $replacement;

		if ( ( '' === $replacement || null === $replacement ) && isset( $attributes['default'] ) ) {
			$replacement = trim( $attributes['default'] );
		}

		if ( is_callable( $this->escape_function ) ) {
			$replacement = call_user_func( $this->escape_function, $replacement );
		}

		return $replacement;
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
	public function replace_in_body( $string ) {
		return $this->replace( $string, '' );
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	public function replace_in_content( $string ) {
		return $this->replace( $string, 'wp_kses_post' );
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	public function replace_in_html( $string ) {
		return $this->replace( $string, 'esc_html' );
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	public function replace_in_attributes( $string ) {
		return $this->replace( $string, 'esc_attr' );
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	public function replace_in_url( $string ) {
		return $this->replace( $string, 'urlencode' );
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	public function replace_in_text_field( $string ) {
		return $this->replace( $string, 'noptin_clean' );
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	public function replace_in_email( $string ) {
		return $this->replace( $string, 'sanitize_email' );
	}

	/**
	 * Gets data variable from cookie.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public static function get_cookie( $args = array() ) {
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

	/**
	 * Gets a formatted date.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public static function get_date( $args = array() ) {
		$time      = ! empty( $args['relative'] ) ? strtotime( $args['relative'] ) : time();
		$time      = $time + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
		$format    = ! empty( $args['format'] ) ? $args['format'] : 'Y-m-d';
		$localized = ! empty( $args['localized'] );

		if ( $localized ) {
			return date_i18n( $format, $time );
		}

		return gmdate( $format, $time );
	}

	/**
	 * Gets a formatted time.
	 *
	 * @param array $args
	 * @return string
	 */
	public static function get_time( $args = array() ) {
		$time      = ! empty( $args['relative'] ) ? strtotime( $args['relative'] ) : time();
		$time      = $time + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
		$format    = ! empty( $args['format'] ) ? $args['format'] : 'H:i:s';
		$localized = ! empty( $args['localized'] );

		if ( $localized ) {
			return date_i18n( $format, $time );
		}

		return gmdate( $format, $time );
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
		$default    = isset( $args['default'] ) ? esc_html( $args['default'] ) : '';
		$subscriber = noptin_get_subscriber( get_current_noptin_subscriber_id() );

		// Ensure the subscriber and the field exist.
		if ( ! $subscriber->exists() ) {
			return $default;
		}

		$value = $subscriber->get( $field );

		if ( is_null( $value ) || '' === $value || array() === $value ) {
			return $default;
		}

		if ( is_bool( $value ) ) {
			return $value ? __( 'Yes', 'newsletter-optin-box' ) : __( 'No', 'newsletter-optin-box' );
		}

		if ( is_array( $value ) ) {
			return implode( ', ', $value );
		}

		return $value;
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
