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
	 * The campaign for this email.
	 *
	 * @var Hizzle\Noptin\Emails\Email
	 */
	public $campaign;

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
		$add_unsubscribe_url = apply_filters( 'noptin_add_unsubscribe_url_to_plain_text_email', true, $this );
		if ( false === stripos( $content, '[[unsubscribe_url]]' ) && true === $add_unsubscribe_url ) {
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

		$settings = get_noptin_email_template_settings( $template, $this->campaign );

		// Check if the template exists.
		$is_local_template = locate_noptin_template( "email-templates/$template/email-body.php" );

		// ... then process local template.
		if ( ! empty( $is_local_template ) ) {
			ob_start();

			// Heading.
			get_noptin_template(
				"email-templates/$template/email-header.php",
				array(
					'email_heading' => $this->heading,
					'settings'      => $settings,
				)
			);

			// Body.
			get_noptin_template(
				"email-templates/$template/email-body.php",
				array(
					'content'  => $content,
					'settings' => $settings,
				)
			);

			// Footer.
			get_noptin_template(
				"email-templates/$template/email-footer.php",
				array(
					'footer'   => wpautop( $this->footer_text ),
					'settings' => $settings,
				)
			);

			$email = ob_get_clean();
		}

		// Allow other plugins to filter generated email content.
		$email = apply_filters( 'noptin_email_after_apply_template', $email, $this );

		// Post-process and return.
		return $this->post_process( $email );
	}

	/**
	 * Generates a visual email.
	 *
	 * @return string
	 */
	public function generate_visual_email() {

		if ( ! empty( $this->campaign_id ) ) {
			$GLOBALS['post'] = get_post( $this->campaign_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			setup_postdata( $GLOBALS['post'] );
		}

		// Prepare vars.
		$content  = do_blocks( $this->content );
		$template = 'noptin-visual';
		$email    = $content;

		$settings = get_noptin_email_template_settings( $template, $this->campaign );

		// Check if the template exists.
		$is_local_template = locate_noptin_template( "email-templates/$template/email-body.php" );

		// ... then process local template.
		if ( ! empty( $is_local_template ) ) {
			ob_start();

			// Heading.
			get_noptin_template(
				"email-templates/$template/email-header.php",
				array(
					'email_heading' => $this->campaign->get_subject(),
					'settings'      => $settings,
				)
			);

			// Body.
			get_noptin_template(
				"email-templates/$template/email-body.php",
				array(
					'content'  => $content,
					'settings' => $settings,
				)
			);

			// Footer.
			get_noptin_template(
				"email-templates/$template/email-footer.php",
				array(
					'settings' => $settings,
				)
			);

			$email = ob_get_clean();
		}

		wp_reset_postdata();

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

		if ( empty( $content ) ) {
			return $content;
		}

		// Inject preheader.
		$content = $this->inject_preheader( $content );

		// Ensure that shortcodes are not wrapped in paragraphs.
		$content = shortcode_unautop( $content );

		// Process list items.
		$content = self::handle_item_lists_shortcode( $content );

		// Do merge tags.
		$content = noptin_parse_email_content_tags( $content );

		// Execute shortcodes.
		$content = do_shortcode( $content );

		// Make links clickable.
		$content = make_clickable( $content );

		// Add class to clickable links.
		$content = $this->add_class_to_clickable_links( $content );

		// Track opens.
		$content = $this->inject_tracking_pixel( $content );

		// Balance tags.
		$content = force_balance_tags( $content );

		// Remove double http://.
		$content = $this->fix_links_with_double_http( $content );

		// Make links trackable.
		$content = $this->make_links_trackable( $content );

		// Backup hrefs.
		$content = $this->backup_hrefs( $content );

		// Inline CSS styles.
		$content = $this->inline_styles( $content );

		// Remove unused classes and ids.
		$content = $this->clean_html( $content );

		// Restore hrefs.
		$content = $this->restore_hrefs( $content );

		// Filters a post processed email.
		return apply_filters( 'noptin_post_process_email_content', $content, $this );
	}

	public static function handle_item_lists_shortcode( $content ) {
		global $shortcode_tags;

		if ( empty( $content ) ) {
			return $content;
		}

		// Save original shortcodes
		$original_shortcodes = $shortcode_tags;

		// Remove all shortcodes
		$shortcode_tags = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		// Process shortcodes that begin with noptin_ and end with _list.
		foreach ( $original_shortcodes as $shortcode => $callback ) {
			if ( strpos( $shortcode, 'noptin_' ) === 0 && strpos( $shortcode, '_list' ) !== false ) {
				$shortcode_tags[ $shortcode ] = $callback; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}

		// Process your content
		$content = do_shortcode( $content );

		// Restore original shortcodes
		$shortcode_tags = $original_shortcodes; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		return $content;
	}

	private function clean_html( $html ) {

		// Check if DOMDocument is available.
		if ( ! class_exists( 'DOMDocument' ) || empty( $html ) ) {
			return $html;
		}

		// Parse the CSS.
		preg_match_all( '/\.([a-z0-9_-]+)/i', $html, $classes );
		preg_match_all( '/#([a-z0-9_-]+)/i', $html, $ids );

		// Convert the arrays to associative arrays for faster lookup.
		$remove_classes = array( 'noptin-block__margin-wrapper' );
		$all_classes    = array_diff_key( array_flip( $classes[1] ), array_flip( $remove_classes ) );
		$ids            = array_flip( $ids[1] );

		// Load the HTML.
		$doc = new DOMDocument();
		@$doc->loadHTML( $html );

		// Iterate over all elements.
		$xpath    = new DOMXPath( $doc );
		$elements = $xpath->query( '//*' );

		if ( empty( $elements ) ) {
			return $html;
		}

		/** @var \DOMNodeList $elements */
		foreach ( $elements as $element ) {
			$is_block_element = in_array( $element->nodeName, array( 'div', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li' ), true );

			// Remove empty paragraphs or those with only whitespace.
			if ( $is_block_element && $element->hasChildNodes() ) {
				$has_content = false;

				foreach ( $element->childNodes as $child ) {
					// Check if the child is an element or if the <p> tag contains non-whitespace text
					if ( XML_ELEMENT_NODE === $child->nodeType || ( XML_TEXT_NODE === $child->nodeType && trim( $child->nodeValue ) !== '' ) ) {
						$has_content = true;
						break;
					}
				}

				// If <p> tag only contains whitespace, remove it
				if ( ! $has_content ) {
					$element->parentNode->removeChild( $element );
				}
			} elseif ( $is_block_element && ! $element->hasChildNodes() ) {
				// If <p> tag has no children, remove it
				$element->parentNode->removeChild( $element );
			}

			// Remove tables with .noptin-button-block__wrapper that have a child a element with an empty or missing href attribute.
			if ( 'table' === $element->nodeName && $element->hasAttribute( 'class' ) && false !== strpos( $element->getAttribute( 'class' ), 'noptin-button-block__wrapper' ) ) {
				$anchors           = $element->getElementsByTagName( 'a' );
				$missing_href      = ! $anchors->item( 0 )->hasAttribute( 'href' ) || empty( $anchors->item( 0 )->getAttribute( 'href' ) );
				$missing_data_href = ! $anchors->item( 0 )->hasAttribute( 'data-href' ) || empty( $anchors->item( 0 )->getAttribute( 'data-href' ) );
				$has_either        = ! $missing_href || ! $missing_data_href;
				if ( 0 === $anchors->length || ! $has_either ) {
					$element->parentNode->removeChild( $element );
					continue;
				}
			}

			// If this is a table element with .noptin-image-block__wrapper class,
			// Remove it if the inner img tag has no src attribute.
			if ( 'table' === $element->nodeName && $element->hasAttribute( 'class' ) && false !== strpos( $element->getAttribute( 'class' ), 'noptin-image-block__wrapper' ) ) {
				$images = $element->getElementsByTagName( 'img' );
				if ( 0 === $images->length || ! $images->item( 0 )->hasAttribute( 'src' ) || empty( $images->item( 0 )->getAttribute( 'src' ) ) ) {
					$element->parentNode->removeChild( $element );
					continue;
				}
			}

			// Unwrap any p tags that contain block elements.
			if ( 'p' === $element->nodeName && $element->hasChildNodes() ) {
				$has_block = false;

				foreach ( $element->childNodes as $child ) {
					if ( in_array( $child->nodeName, array( 'div', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'table' ), true ) ) {
						$has_block = true;
						break;
					}
				}

				if ( $has_block ) {
					$fragment = $doc->createDocumentFragment();
					while ( $element->firstChild ) {
						$fragment->appendChild( $element->firstChild );
					}
					$element->parentNode->replaceChild( $fragment, $element );
				}
			}

			// Check if the class is used in the CSS.
			if ( $element->hasAttribute( 'class' ) ) {
				$class = $element->getAttribute( 'class' );

				// Split the class attribute.
				$classes = empty( $class ) ? array() : explode( ' ', $class );
				$styled  = array();

				// Loop over all classes.
				foreach ( $classes as $class ) {
					if ( isset( $all_classes[ $class ] ) ) {
						$styled[]  = $class;
					}
				}

				if ( 0 < count( $styled ) ) {
					$element->setAttribute( 'class', implode( ' ', $styled ) );
				} else {
					$element->removeAttribute( 'class' );
				}
			}

			// Check if the id is used in the CSS.
			if ( $element->hasAttribute( 'id' ) ) {
				$id = $element->getAttribute( 'id' );
				if ( ! isset( $ids[ $id ] ) ) {
					// Id is not used, remove it.
					$element->removeAttribute( 'id' );
				}
			}
		}

		// Save the HTML.
		return $doc->saveHTML();
	}

	private function backup_hrefs( $html ) {
		// Check if DOMDocument is available.
		if ( ! class_exists( 'DOMDocument' ) || empty( $html ) ) {
			return $html;
		}

		$doc = new DOMDocument();
		@$doc->loadHTML( $html );

		$xpath    = new DOMXPath( $doc );
		$elements = $xpath->query( '//*[@href]' );

		if ( empty( $elements ) ) {
			return $html;
		}

		foreach ( $elements as $element ) {
			$element->setAttribute( 'data-href', $element->getAttribute( 'href' ) );
			$element->removeAttribute( 'href' );
		}

		return $doc->saveHTML();
	}

	private function restore_hrefs( $html ) {
		// Check if DOMDocument is available.
		if ( ! class_exists( 'DOMDocument' ) || empty( $html ) ) {
			return $html;
		}

		$new_html = preg_replace_callback(
			'/<a(.*?)data-href=["\'](.*?)["\'](.*?)>/mi',
			function ( $matches ) {
				return "<a{$matches[1]}href=\"{$matches[2]}\"{$matches[3]}>";
			},
			$html
		);

		return empty( $new_html ) ? $html : $new_html;
	}

	public function add_class_to_clickable_links( $content ) {

		// Use regex to add the class to all links that start with http.
		$content = preg_replace_callback(
			'/<a\s+href=["\'](http[^"\']*)["\']([^>]*)>(http[^<]*)<\/a>/i',
			function ( $matches ) {
				// Add the class to the anchor tag.
				return sprintf(
					'<a href="%s" %s class="noptin-raw-link">%s</a>',
					esc_url( $matches[1] ),
					$matches[2],
					esc_html( $matches[3] )
				);
			},
			$content
		);

		return $content;
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

		// Use preg_replace to remove the CSS comments.
		$content = preg_replace( '/\/\*.*?\*\//s', '', $content );

		// Maybe abort early.
		if ( ! class_exists( 'DOMDocument' ) || ! class_exists( '\TijsVerkoyen\CssToInlineStyles\CssToInlineStyles' ) ) {
			return $content;
		}

		// Base styles.
		$styles = $this->get_email_styles();

		try {

			// create inliner instance
			$inliner = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles();

			// Inline styles.
			return $inliner->convert( $content, $styles );
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
				function ( $matches ) {
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
				function ( $matches ) {
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
	public static function merge_tags_unautop( $content, $is_blocks = false ) {

		$spaces = wp_spaces_regexp();

		// phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound,WordPress.WhiteSpace.PrecisionAlignment.Found -- don't remove regex indentation
		$pattern =
			'/'
			. '<p>'                              // Opening paragraph.
			. '(?:' . $spaces . ')*+'            // Optional leading whitespace.
			. '('                                // 1: The shortcode.
			.     '\\[\\[(.*?)\\]\\]'
			. ')'
			. '(?:' . $spaces . ')*+'            // Optional trailing whitespace.
			. '<\\/p>'                           // Closing paragraph.
			. '/';
		// phpcs:enable

		if ( $is_blocks ) {
			return preg_replace( $pattern, "<!-- wp:html -->$1<!-- /wp:html -->\n", $content );
		}

		return preg_replace( $pattern, "$1\n", $content );
	}
}
