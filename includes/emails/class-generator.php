<?php
/**
 * Emails API: Generator.
 *
 * Generates an email's content.
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Generates an email's content.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
class Noptin_Email_Generator {

	/**
	 * Which type of image to generate.
	 *
	 * Example, normal, plain_text, raw_html.
	 *
	 * @see get_noptin_email_types()
	 * @var string
	 */
	public $type = 'normal';

	/**
	 * The unprocessed email content.
	 *
	 * @var string
	 */
	public $content = '';

	/**
	 * The template to use.
	 *
	 * @see get_noptin_email_templates()
	 * @var string
	 */
	public $template = 'paste';

	/**
	 * The heading text to pass onto the template.
	 *
	 * @var string
	 */
	public $heading = '';

	/**
	 * The footer text to pass onto the template.
	 *
	 * @var string
	 */
	public $footer_text = '';

	/**
	 * The preview text to pass onto the template.
	 *
	 * @var string
	 */
	public $preview_text = '';

	/**
	 * The recipient for this email.
	 *
	 * @var array
	 */
	public $recipient = array();

	/**
	 * The campaign for this email.
	 *
	 * @var int|false
	 */
	public $campaign_id = false;

	/**
	 * Whether or not to track the email.
	 *
	 * @var bool
	 */
	public $track = true;

	/**
	 * Class constructor.
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {

		// Prefill class variables.
		foreach ( $args as $key => $value ) {
			$this->$key = $value;
		}

	}

	/**
	 * Generates the email's content.
	 *
	 * @param array $args
	 * @return string|WP_Error
	 */
	public function generate( $args = array() ) {

		// Prefill class variables.
		foreach ( $args as $key => $value ) {
			$this->$key = $value;
		}

		// Ensure we have an email type.
		if ( empty( $this->type ) ) {
			$this->type = 'normal';
		}

		// Ensure we have content.
		if ( empty( $this->content ) ) {
			return new WP_Error( 'missing_content', __( 'The email body cannot be empty.', 'newsletter-optin-box' ) );
		}

		$method = array( $this, "generate_{$this->type}_email" );
		$email  = $this->content;

		if ( is_callable( $method ) ) {
			$email = call_user_func( $method );
		}

		return apply_filters( 'generate_noptin_email', $email, $this );

	}

	/**
	 * Generates a plain text email.
	 *
	 * @return string
	 */
	public function generate_plain_text_email() {

		// Prepare content.
		$content = trim( $this->content );

		// Maybe add an unsubscribe url.
		if ( false === stripos( $content, '[[unsubscribe_url]]' ) ) {
			$content .= "\n\n[" . __( 'Unsubscribe', 'newsletter-optin-box' ) . ']([[unsubscribe_url]])';
		}

		// Render merge tags.
		$content = noptin_parse_email_content_tags( $content );

		// Ensure that shortcodes are not wrapped in paragraphs.
		$content = shortcode_unautop( $content );

		// Execute shortcodes.
		$content = do_shortcode( $content );

		// Balance tags.
		$content = force_balance_tags( $content );

		// Remove double http://.
		$content = $this->fix_links_with_double_http( $content );

		// Strip HTML.
		$content = noptin_convert_html_to_text( $content );

		// Filters a post processed email.
		return apply_filters( 'noptin_generate_plain_text_email_content', $content, $this );

	}

	/**
	 * Generates a raw HTML email.
	 *
	 * @return string
	 */
	public function generate_raw_html_email() {
		$this->preview_text = '';
		return $this->post_process( trim( $this->content ) );
	}

	/**
	 * Generates a template-based email.
	 *
	 * @return string
	 */
	public function generate_normal_email() {

		// Prepare vars.
		$content  = $this->content;
		$template = $this->template;
		$email    = $content;

		// Ensure the chosen template is supported.
		if ( ! array_key_exists( $template, get_noptin_email_templates() ) ) {
			$template       = 'none';
			$this->template = 'none';
		}

		// Check if the template exists.
		$is_local_template = locate_noptin_template( "email-templates/$template/email-body.php" );

		// ... then process local template.
		if ( ! empty( $is_local_template ) ) {

			ob_start();
			get_noptin_template( "email-templates/$template/email-header.php", array( 'email_heading' => $this->heading ) );
			get_noptin_template( "email-templates/$template/email-body.php", array( 'content' => $content ) );
			get_noptin_template( "email-templates/$template/email-footer.php", array( 'footer' => wpautop( $this->footer_text ) ) );
			$email = ob_get_clean();

		}

		// Allow other plugins to filter generated email content.
		$email = apply_filters( 'noptin_email_after_apply_template', $email, $this );

		// Post-process and return.
		return $this->post_process( $email );
	}

	/**
	 * Post processes the email content.
	 *
	 * @param string $content
	 * @return string
	 */
	public function post_process( $content ) {

		// Inject preheader.
		$content = $this->inject_preheader( $content );

		// Ensure that merge tags are not wrapped in paragraphs.
		//$content = $this->merge_tags_unautop( $content );

		// Do merge tags.
		$content = noptin_parse_email_content_tags( $content );

		// Make links clickable.
		$content = make_clickable( $content );

		// Track opens.
		$content = $this->inject_tracking_pixel( $content );

		// Ensure that shortcodes are not wrapped in paragraphs.
		$content = shortcode_unautop( $content );

		// Execute shortcodes.
		$content = do_shortcode( $content );

		// Balance tags.
		$content = force_balance_tags( $content );

		// Remove double http://.
		$content = $this->fix_links_with_double_http( $content );

		// Make links trackable.
		$content = $this->make_links_trackable( $content );

		// Inline CSS styles.
		$content = $this->inline_styles( $content );

		// Filters a post processed email.
		return apply_filters( 'noptin_post_process_email_content', $content, $this );
	}

	/**
	 * Fix any duplicate http in links, can happen due to merge tags
	 *
	 * @param string $content
	 * @return string
	 */
	public function fix_links_with_double_http( $content ) {
		$content = str_replace( '"http://http://', '"http://', $content );
		$content = str_replace( '"https://https://', '"https://', $content );
		$content = str_replace( '"http://https://', '"https://', $content );
		$content = str_replace( '"https://http://', '"http://', $content );
		return $content;
	}

	/**
	 * Checks if we can track the current campaign.
	 *
	 * @return bool
	 */
	public function can_track() {

		// Abort if we have no recipient or tracking is disabled for this campaign...
		if ( empty( $this->recipient ) || empty( $this->track ) ) {
			return false;
		}

		// ... or store wide.
		$track_campaign_stats = get_noptin_option( 'track_campaign_stats', true );
		if ( empty( $track_campaign_stats ) ) {
			return false;
		}

		return apply_filters( 'noptin_can_track_campaign', true, $this );
	}

	/**
	 * Makes campaign links trackable.
	 *
	 * @param string $content The email content.
	 */
	public function make_links_trackable( $content ) {

		// Abort if tracking is disabled.
		if ( ! $this->can_track() ) {
			return $content;
		}

		// Replace URLs with new trackable ones.
		$_content = preg_replace_callback(
			'/<a(.*?)href=["\'](.*?)["\'](.*?)>/mi',
			array( $this, 'make_links_trackable_callback' ),
			$content
		);

		// Abort if the preg_replace failed.
		if ( empty( $_content ) ) {
			return $content;
		}

		// Returned the new content.
		return $_content;

	}

	/**
	 * Callback for making campaign links trackable.
	 *
	 * @param array $matches
	 */
	public function make_links_trackable_callback( $matches ) {

		// Prepare base tracking url && args.
		$url = str_replace( '&amp;', '&', $matches[2] );

		// Skip action page URLs.
		if ( false === strpos( $url, 'noptin_ns' ) ) {

			$args = array_merge(
				$this->recipient,
				array( 'to' => $url )
			);

			$url = get_noptin_action_url( 'email_click', noptin_encrypt( wp_json_encode( $args ) ) );
		}

		$pre  = $matches[1];
		$post = $matches[3];
		return "<a $pre href='$url' $post >";

	}

	/**
	 * Retrieves email styles.
	 *
	 * @return string
	 */
	public function get_email_styles() {

		ob_start();
		get_noptin_template( 'email-templates/email-styles.php', array( 'generator' => $this ) );
		$styles = ob_get_clean();

		// Fetch portion between <style> tags.
		preg_match( '/<style[^>]*>(.*?)<\/style>/s', $styles, $matches );

		if ( ! empty( $matches[1] ) ) {
			$styles = $matches[1];
		}

		return $styles;
	}

	/**
	 * Apply inline styles to dynamic content.
	 *
	 * @param string|null $content
	 * @return string
	 */
	public function inline_styles( $content ) {

		// Check if this is PHP 5.6 and above.
		// TODO: Switch to PHP 7 and upgrade emogrifier.
		if ( version_compare( phpversion(), '5.6', '<' ) || empty( $content ) ) {
			return $content;
		}

		// Maybe abort early.
		if ( ! class_exists( 'DOMDocument' ) || ! class_exists( '\TijsVerkoyen\CssToInlineStyles\CssToInlineStyles' ) ) {
			return $content;
		}

		// Base styles.
		$styles = $this->get_email_styles();

		try {

			// Emogrifier urlencodes hrefs, copy the href to a new attribute and restore it after inlining.
			$content = preg_replace_callback(
				'/<a(.*?)href=["\'](.*?)["\'](.*?)>/mi',
				function( $matches ) {
					return "<a {$matches[1]} data-href=\"{$matches[2]}\" {$matches[3]}>";
				},
				$content
			);

			// create inliner instance
			$inliner = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles();

			// Inline styles.
			$content = $inliner->convert( $content, $styles );

			// Restore hrefs.
			$content = preg_replace_callback(
				'/<a(.*?)data-href=["\'](.*?)["\'](.*?)>/mi',
				function( $matches ) {
					return "<a {$matches[1]} href=\"{$matches[2]}\" {$matches[3]}>";
				},
				$content
			);

			return $content;
		} catch ( Exception $e ) {

			log_noptin_message( $e->getMessage() );
			return $content;

		} catch ( Symfony\Component\CssSelector\Exception\SyntaxErrorException $e ) {
            log_noptin_message( $e->getMessage() );
			return $content;
        }

	}

	/**
	 * Injects tracking pixel before closing </body> tag
	 *
	 * @param string $content
	 * @return string
	 */
	public function inject_tracking_pixel( $content ) {

		// Only track if it is permitted.
		if ( $this->can_track() ) {

			return preg_replace_callback(
				'/<\/body[^>]*>/',
				function( $matches ) {
					return $this->get_tracker() . $matches[0];
				},
				$content,
				1
			);

		}

		return $content;
	}

	/**
	 * Returns the code used to track email opens.
	 *
	 * @since 1.7.0
	 */
	protected function get_tracker() {
		$url = get_noptin_action_url( 'email_open', noptin_encrypt( wp_json_encode( $this->recipient ) ) );
		return '<img src="' . esc_url( $url ) . '" height="1" width="1" alt="" style="border:0;display:inline">';
	}

	/**
	 * Injects preheader HTML after opening <body> tag
	 *
	 * @since 1.7.0
	 * @param string $content
	 * @return string
	 */
	public function inject_preheader( $content ) {

		// Ensure a preview text is set.
		if ( ! empty( $this->preview_text ) ) {

			return preg_replace_callback(
				'/<body[^>]*>/',
				function( $matches ) {
					return $matches[0] . '<div class="preheader" style="display: none !important; max-width: 0; max-height: 0; overflow: hidden; font-size: 1px; line-height: 1px; color: #fff; opacity: 0;">' . esc_html( $this->preview_text ) . '</div>';
				},
				$content,
				1
			);

		}

		return $content;

	}

	/**
	 * Don't auto-p wrap merge tags that stand alone
	 *
	 * Ensures that merge tags are not wrapped in `<p>...</p>`.
	 *
	 * @since 1.0.0
	 *
	 * @global array $shortcode_tags
	 *
	 * @param string $content The content.
	 * @return string The filtered content.
	 */
	public function merge_tags_unautop( $content ) {

		$spaces    = wp_spaces_regexp();

		// phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound,WordPress.WhiteSpace.PrecisionAlignment.Found -- don't remove regex indentation
		$pattern =
			'/'
			. '<p>'                              // Opening paragraph.
			. '(?:' . $spaces . ')*+'            // Optional leading whitespace.
			. '('                                // 1: The shortcode.
			.     '\\[\\[([\\w\\.]+)(\\ +(?:(?!\\[)[^\\]\n])+)*\\]\\]'
			. ')'
			. '(?:' . $spaces . ')*+'            // Optional trailing whitespace.
			. '<\\/p>'                           // Closing paragraph.
			. '/';
		// phpcs:enable

		return preg_replace( $pattern, '$1', $content );
	}

}
