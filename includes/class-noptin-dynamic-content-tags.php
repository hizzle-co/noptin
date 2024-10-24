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

		if ( is_array( $tag ) ) {
			return array_map( array( $this, 'remove_tag' ), $tag );
		}

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
	 * Searches a tag from the given list of tags.
	 *
	 * @param string $tag
	 * @return array|null
	 */
	public static function search( $tag, $tags ) {

		if ( isset( $tags[ $tag ] ) ) {
			return $tags[ $tag ];
		}

		// Search deprecated tags.
		foreach ( $tags as $key => $value ) {
			if ( ! empty( $value['deprecated'] ) && in_array( $tag, noptin_parse_list( $value['deprecated'] ), true ) ) {
				return array_merge(
					$value,
					array( 'use_tag' => $key )
				);
			}
		}

		// Convert first occurence of _ to .
		$alt_tag = preg_replace( '/_/', '.', $tag, 1 );

		if ( isset( $tags[ $alt_tag ] ) ) {
			return array_merge(
				$tags[ $alt_tag ],
				array( 'use_tag' => $alt_tag )
			);
		}

		// Guess the tag.
		foreach ( $tags as $key => $value ) {

			// Check without prefix.
			if ( -1 !== strpos( $key, '.' ) ) {
				$without_prefix = explode( '.', $key );
				array_shift( $without_prefix );

				if ( implode( '.', $without_prefix ) === $tag ) {
					return array_merge(
						$value,
						array( 'use_tag' => $key )
					);
				}
			}
		}

		return null;
	}

	/**
	 * @return array|null
	 */
	public function get( $tag ) {
		return self::search( $tag, $this->all() );
	}

	/**
	 * @return string
	 */
	public function get_all_tags_as_html() {
		$html = '<table class="noptin-tags-table"><thead><tr><th style="width: 200px;">Tag</th><th>Value</th></tr></thead><tbody>';

		foreach ( $this->all() as $tag => $config ) {
			$value = $this->replace_with_brackets(
				'[[' . ( empty( $config['example'] ) ? $tag : $config['example'] ) . ']]',
				$this->escape_function
			);

			$html .= '<tr><th style="border: 1px solid #424242;padding: 4px; width: 200px;">' . esc_html( $tag ) . '</th><td style="border: 1px solid #424242;padding: 4px;">' . wp_kses_post( $value ) . '</td></tr>';
		}

		return $html . '</tbody></table>';
	}

	/**
	 * @param array $matches
	 *
	 * @return string
	 */
	protected function replace_tag( $matches ) {
		$tag = $matches[1];

		// Handle the special [[noptin_all_tags_as_html]] tag.
		if ( 'noptin_all_tags_as_html' === $tag ) {
			return $this->get_all_tags_as_html();
		}

		$config = $this->get( $tag );

		if ( ! empty( $config['use_tag'] ) ) {
			$tag = $config['use_tag'];
		}

		// Abort if tag is not supported.
		if ( empty( $config ) ) {
			return $matches[0];
		}

		// (Maybe) Skip non-partial tags.
		if ( $this->is_partial && empty( $config['partial'] ) ) {
			return $matches[0];
		}

		// Generate replacement.
		$replacement = '';

		// Parse attributes.
		$attributes = array();
		if ( isset( $matches[2] ) ) {
			$attribute_string = html_entity_decode( $matches[2] );
			$attributes       = shortcode_parse_atts( $attribute_string );
		}

		if ( isset( $config['replacement'] ) ) {
			$replacement = $config['replacement'];
		} elseif ( isset( $config['callback'] ) ) {

			// call function
			if ( empty( $config['no_args'] ) ) {
				$replacement = call_user_func( $config['callback'], $attributes, $tag, $config );
			} else {
				$replacement = call_user_func( $config['callback'] );
			}
		}

		if ( is_array( $replacement ) ) {
			$is_all_scalar = array_reduce(
				$replacement,
				function ( $carry, $item ) {
					return $carry && (is_string( $item ) || is_numeric( $item ));
				},
				true
			);

			if ( ! wp_is_numeric_array( $replacement ) || ! $is_all_scalar ) {
				$replacement = wp_json_encode( $replacement );
			} else {
				$replacement = implode( ', ', $replacement );
			}
		}

		// Convert dates.
		if ( is_a( $replacement, 'DateTime' ) ) {
			$replacement = $replacement->format( get_option( 'date_format' ) );
		}

		// Convert booleans.
		if ( is_bool( $replacement ) ) {
			$replacement = $replacement ? 'yes' : 'no';
		}

		// Nulls.
		if ( is_null( $replacement ) ) {
			$replacement = '';
		}

		if ( ! is_scalar( $replacement ) ) {
			$replacement = wp_json_encode( $replacement );
		}

		$replacement = (string) $replacement;

		if ( '' === $replacement && isset( $attributes['default'] ) ) {
			$replacement = trim( $attributes['default'] );
		}

		if ( is_callable( $this->escape_function ) ) {
			$replacement = call_user_func( $this->escape_function, $replacement );
		}

		return $replacement;
	}

	/**
	 * Replaces dynamic content tags in the given string. Uses { and } as delimiters.
	 *
	 * @param string $string The string containing dynamic content tags.
	 * @param string $escape_function Escape mode for the replacement value. Leave empty for no escaping.
	 * @return string
	 */
	protected function replace( $content, $escape_function = '' ) {

		return $this->replace_with_regex(
			$content,
			$this->get_regex( '{', '}' ),
			$escape_function
		);
	}

	/**
	 * Replaces dynamic content tags in the given string. Uses [[ and ]] as delimiters.
	 *
	 * @param string $string The string containing dynamic content tags.
	 * @param string $escape_function Escape mode for the replacement value. Leave empty for no escaping.
	 * @return string
	 */
	protected function replace_with_brackets( $content, $escape_function = '' ) {

		// Replace strings like this: [[tagname attr="value"]].
		$content = $this->replace_with_regex(
			$content,
			$this->get_regex( '[[', ']]' ),
			$escape_function
		);

		$content = $this->replace_with_regex(
			$content,
			$this->get_regex( '&#91;&#91;', '&#93;&#93;' ),
			$escape_function
		);

		return $content;
	}

	/**
	 * @param string $content The content containing dynamic content tags.
	 * @param string $regex The regex to use for matching tags.
	 * @param string $escape_function Escape mode for the replacement value. Leave empty for no escaping.
	 * @return string
	 */
	protected function replace_with_regex( $content, $regex, $escape_function = '' ) {

		if ( is_array( $content ) ) {
			foreach ( $content as $key => $value ) {
				$content[ $key ] = $this->replace_with_regex( $value, $regex, $escape_function );
			}

			return $content;
		}

		if ( ! is_string( $content ) || empty( $content ) ) {
			return $content;
		}

		$this->escape_function = $escape_function;

		// Replace strings like this: {tagname attr="value"}.
		$content = $this->preg_replace( $regex, $content );

		// Call again to take care of nested variables, e.g, {tagname attr="{value}"}.
		return $this->preg_replace( $regex, $content );
	}

	private function preg_replace( $regex, $content ) {

		$replaced = preg_replace_callback(
			$regex,
			array( $this, 'replace_tag' ),
			$content
		);

		if ( null === $replaced ) {
			return $content;
		}

		return $replaced;
	}

	/**
	 * Retrieves the regexx
	 *
	 * @param string $opening_tag
	 * @param string $closing_tag
	 */
	protected function get_regex( $opening_tag, $closing_tag ) {

		// https://regex101.com/r/2tTENO/1
		return sprintf(
			'/%1$s(?P<name>[\\]?\w[\w\.\/-]*\w)(?P<attributes>\ +(?:(?!%1$s|\n|%2$s).)+)*%2$s/',
			preg_quote( $opening_tag, '/' ),
			preg_quote( $closing_tag, '/' )
		);
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
	 * @param string $text
	 *
	 * @return string
	 */
	public function replace_in_text_field( $text ) {
		return $this->replace( $text, 'wp_strip_all_tags' );
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

		if ( ! empty( $GLOBALS['current_noptin_email'] ) ) {
			return sanitize_email( $GLOBALS['current_noptin_email'] );
		}

		// Trying retrieving from posted email address.
		$listener = \Hizzle\Noptin\Forms\Main::$listener;
		if ( $listener ) {
			if ( ! empty( $listener->submitted['email'] ) ) {
				return sanitize_email( $listener->submitted['email'] );
			}

			if ( ! empty( $listener->submitted['noptin_fields']['email'] ) ) {
				return sanitize_email( $listener->submitted['noptin_fields']['email'] );
			}
		}

		// then , try logged-in user.
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			return sanitize_email( $user->user_email );
		}

		return '';
	}

	/**
	 * Checks if conditional logic if met.
	 *
	 * @since 1.2.8
	 * @param array $conditional_logic The conditional logic.
	 * @param string[] $skip_tags The tags to skip.
	 * @return bool|array
	 */
	public function check_conditional_logic( $conditional_logic, $skip_tags = array() ) {

		// Retrieve the conditional logic.
		$action  = $conditional_logic['action']; // allow or prevent.
		$type    = $conditional_logic['type']; // all or any.
		$skipped = array();

		// Loop through each rule.
		foreach ( $conditional_logic['rules'] as $rule ) {
			// Get current value.
			$full_value    = empty( $rule['full'] ) ? '[[' . $rule['type'] . ']]' : $rule['full'];
			$current_value = $this->get_conditional_logic_value( $full_value, $skip_tags );
			$compare_value = $this->get_conditional_logic_value( noptin_clean( $rule['value'] ), $skip_tags );

			if ( false === $current_value || false === $compare_value ) {
				$rule['full']  = false === $current_value ? $full_value : $current_value;
				$rule['value'] = false === $compare_value ? $rule['value'] : $compare_value;
				$skipped[]     = $rule;
				continue;
			}

			// If the rule is met.
			if ( noptin_is_conditional_logic_met( $current_value, $compare_value, $rule['condition'] ) ) {

				// If we're using the "any" condition, we can stop here.
				if ( 'any' === $type ) {
					return 'allow' === $action;
				}
			} elseif ( 'all' === $type ) {
				return 'allow' !== $action;
			}
		}

		if ( ! empty( $skipped ) ) {
			return $skipped;
		}

		$matched = 'all' === $type;

		return 'allow' === $action ? $matched : ! $matched;
	}

	/**
	 * Returns conditional logic value.
	 *
	 * @since 1.2.8
	 * @param string|array $value The conditional logic.
	 * @param string[] $skip_tags The tags to skip.
	 * @return mixed|array
	 */
	private function get_conditional_logic_value( $value, $skip_tags = array() ) {

		if ( is_string( $value ) && strpos( $value, '[[' ) !== false ) {

			// Check if $current_value contains any of the strings in $skip_tags
			foreach ( $skip_tags as $skip_tag ) {
				if ( strpos( $value, $skip_tag ) !== false ) {
					return false; // Skip to the next rule
				}
			}

			$value = $this->replace_in_text_field( $value );
		}

		return $value;
	}
}
