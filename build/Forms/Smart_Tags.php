<?php
/**
 * Forms API: Dynamic Form Tags.
 *
 * Allows users to use dynamic tags in opt-in forms.
 *
 * @since   1.6.2
 * @package Noptin
 */

namespace Hizzle\Noptin\Forms;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Allows users to use dynamic tags in newsletter forms.
 *
 * @internal
 * @access private
 * @since 1.6.2
 * @ignore
 */
class Smart_Tags extends \Hizzle\Noptin\Core\Dynamic_Content_Tags {

	/**
	 * Post associated with the current form submission.
	 *
	 * @var \WP_Post|null
	 */
	private $context_post;

	/**
	 * URL associated with the current form submission.
	 *
	 * @var string
	 */
	private $context_url = '';

	/**
	 * Register core hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
		add_filter( 'noptin_form_response_html', array( $this, 'replace_in_form_response' ) );
		add_filter( 'noptin_form_text', array( $this, 'replace_in_form_content' ) );
		add_filter( 'noptin_form_redirect_url', array( $this, 'replace_in_url' ) );
	}

	/**
	 * Replaces the form content to include merge tags.
	 *
	 * @param string $response
	 * @return int
	 */
	public function replace_in_form_content( $response ) {
		return $this->replace( $response, 'wp_kses_post' );
	}

	/**
	 * Replaces the subscription response to include merge tags.
	 *
	 * @param string $response
	 * @return int
	 */
	public function replace_in_form_response( $response ) {

		// We do not want an infinite loop.
		$response = str_replace( '{response}', '', $response );

		return $this->replace( $response, 'wp_kses_post' );
	}

	/**
	 * Register template tags
	 */
	public function register() {
		parent::register();

		$this->tags['post']['callback'] = array( $this, 'get_context_post_property' );

		$this->tags['current_url']['callback'] = array( $this, 'get_context_url' );
		unset( $this->tags['current_url']['replacement'] );

		$this->tags['current_path']['callback'] = array( $this, 'get_context_path' );
		$this->tags['current_path']['no_args']  = true;
		unset( $this->tags['current_path']['replacement'] );

		$this->tags['post_meta'] = array(
			'label'       => __( 'Current post custom field', 'newsletter-optin-box' ),
			'description' => __( 'The value of a custom field on the page or post where the form was submitted.', 'newsletter-optin-box' ),
			'callback'    => array( $this, 'get_post_meta' ),
			'attributes'  => array(
				'key' => array(
					'el'          => 'input',
					'type'        => 'text',
					'label'       => __( 'Custom field key', 'newsletter-optin-box' ),
					'description' => __( 'Enter the custom field name as stored on the post.', 'newsletter-optin-box' ),
					'default'     => '',
				),
			),
		);

		$this->tags['response'] = array(
			'description' => __( 'Replaced with the form response (error or success messages).', 'newsletter-optin-box' ),
			'callback'    => array( $this, 'get_form_response' ),
		);

		$this->tags['data'] = array(
			'description' => sprintf( __( 'Data from the URL or a submitted form.', 'newsletter-optin-box' ) ),
			'callback'    => array( $this, 'get_data' ),
			'example'     => "data key='UTM_SOURCE' default='Default Source'",
			'attributes'  => array(
				'key' => array(
					'el'          => 'input',
					'type'        => 'text',
					'label'       => __( 'Data key', 'newsletter-optin-box' ),
					'description' => __( 'The URL parameter or submitted field to retrieve.', 'newsletter-optin-box' ),
					'default'     => '',
				),
			),
		);

		$this->tags['subscriber_count'] = array(
			'description' => __( 'Replaced with the total number of subscribers', 'newsletter-optin-box' ),
			'callback'    => array( $this, 'get_subscriber_count' ),
		);
	}

	/**
	 * Replaces merge tags using the page context submitted with a form.
	 *
	 * @param mixed    $value    Configured default value.
	 * @param Listener $listener Current form listener.
	 * @return mixed
	 */
	public function replace_for_submission( $value, $listener ) {
		$this->context_url  = esc_url_raw( $listener->get_submitted( 'conversion_page' ) );
		$context_post_id    = absint( $listener->get_submitted( 'conversion_page_id' ) );
		$url_post_id        = $this->context_url ? url_to_postid( $this->context_url ) : 0;
		$context_post_id    = $url_post_id ? $url_post_id : $context_post_id;
		$this->context_post = $context_post_id ? get_post( $context_post_id ) : null;

		$previous_post = $GLOBALS['post'] ?? null;
		if ( $this->context_post instanceof \WP_Post ) {
			$GLOBALS['post'] = $this->context_post;
		}

		$replaced = $this->replace( $value, '' );

		$GLOBALS['post'] = $previous_post;
		return $replaced;
	}

	/**
	 * Replaces merge tags in a visible field's default value.
	 *
	 * Visible defaults are evaluated when the form is rendered so the visitor can
	 * review or change them before submitting the form.
	 *
	 * @param mixed $value Configured default value.
	 * @return mixed
	 */
	public function replace_for_render( $value ) {
		$this->context_url = '';
		$render_post       = get_post();

		if ( ! $render_post instanceof \WP_Post ) {
			$queried_object = get_queried_object();
			$render_post    = $queried_object instanceof \WP_Post ? $queried_object : null;
		}

		$this->context_post = $render_post;

		$previous_post = $GLOBALS['post'] ?? null;
		if ( $this->context_post instanceof \WP_Post ) {
			$GLOBALS['post'] = $this->context_post;
		}

		$replaced = $this->replace( $value, '' );

		$GLOBALS['post'] = $previous_post;

		return $replaced;
	}

	/**
	 * Returns the submitted page URL.
	 */
	public function get_context_url() {
		return $this->context_url ? $this->context_url : noptin_get_request_url();
	}

	/**
	 * Returns the submitted page path.
	 */
	public function get_context_path() {
		$url = $this->get_context_url();
		return (string) wp_parse_url( $url, PHP_URL_PATH );
	}

	/**
	 * Returns a custom field from the submitted page or post.
	 *
	 * @param array $args Merge tag attributes.
	 * @return mixed
	 */
	public function get_post_meta( $args = array() ) {
		if ( ! $this->context_post instanceof \WP_Post || empty( $args['key'] ) ) {
			return '';
		}

		return get_post_meta( $this->context_post->ID, sanitize_text_field( $args['key'] ), true );
	}

	/**
	 * Returns a property from the current form page or post.
	 *
	 * @param array $args Merge tag attributes.
	 * @return string
	 */
	public function get_context_post_property( $args = array() ) {
		$post     = $this->context_post instanceof \WP_Post ? $this->context_post : get_post();
		$property = empty( $args['property'] ) ? 'ID' : sanitize_key( $args['property'] );
		$default  = isset( $args['default'] ) ? $args['default'] : '';

		if ( $post instanceof \WP_Post && isset( $post->{$property} ) ) {
			return 'post_content' === $property ? wp_kses_post( $post->{$property} ) : esc_html( $post->{$property} );
		}

		return esc_html( $default );
	}

	/**
	 * Returns the form response
	 *
	 * @return string
	 */
	public function get_form_response() {
		$listener = Main::$listener;
		return $listener ? $listener->get_response_html() : '';
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
	 * Gets data value from GET or POST variables.
	 *
	 * @param array $args
	 * @return string
	 */
	public function get_data( $args = array() ) {
		if ( empty( $args['key'] ) ) {
			return '';
		}

		// Prepare value.
		$default = isset( $args['default'] ) ? $args['default'] : '';
		$key     = $args['key'];
		$data    = \Hizzle\Noptin\Forms\Main::$listener ? \Hizzle\Noptin\Forms\Main::$listener->submitted : array();
		$value   = isset( $data[ $key ] ) ? wp_unslash( $data[ $key ] ) : $default;
		$value   = isset( $data['noptin_fields'][ $key ] ) ? wp_unslash( $data['noptin_fields'][ $key ] ) : $value;

		// Turn array into readable value.
		if ( is_array( $value ) ) {
			$value = array_filter( $value );
			$value = implode( ', ', $value );
		}

		return esc_html( $value );
	}
}
