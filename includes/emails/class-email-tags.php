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
	 * @var Noptin_Automation_Rules_Smart_Tags|Noptin_Automation_Rules_Smart_Tags[]|null $smart_tags
	 */
	public $smart_tags = null;

	/**
	 * Register core hooks.
	 */
	public function add_hooks() {

		// Add hooks.
		add_action( 'init', array( $this, 'register' ), 0 );
		add_filter( 'noptin_parse_email_subject_tags', array( $this, 'replace_in_subject' ), 10, 2 );
		add_filter( 'noptin_parse_email_content_tags', array( $this, 'replace_in_body' ), 10, 2 );
	}

	/**
	 * @param string $string The string containing dynamic content tags.
	 * @param string $escape_function Escape mode for the replacement value. Leave empty for no escaping.
	 * @return string
	 */
	protected function replace( $content, $escape_function = '' ) {
		return $this->replace_with_brackets( $content, $escape_function );
	}

	private function replace_with_external_tags( $content, $method, $is_partial ) {
		$smart_tags = $this->smart_tags;

		if ( empty( $smart_tags ) ) {
			return $content;
		}

		if ( ! is_array( $smart_tags ) ) {
			$smart_tags = array( $smart_tags );
		}

		foreach ( $smart_tags as $smart_tag ) {
			if ( is_object( $smart_tag ) && is_callable( array( $smart_tag, $method ) ) ) {
				$smart_tag->is_partial = $is_partial;
				$content               = $smart_tag->$method( $content );
				$smart_tag->is_partial = false;
			}
		}

		return $content;
	}

	/**
	 * Replaces in subject
	 *
	 * @param string $content
	 * @param bool $is_partial
	 * @return string
	 */
	public function replace_in_subject( $content, $is_partial = false ) {

		$content = $this->replace_with_external_tags( $content, 'replace_in_text_field', $is_partial );

		$this->is_partial = $is_partial;
		$result           = $this->replace( $content, 'strip_tags' );
		$this->is_partial = false;
		return $result;
	}

	/**
	 * Replaces in the email body
	 *
	 * @param string $content
	 * @param bool $is_partial
	 * @return string
	 */
	public function replace_in_body( $content, $is_partial = false ) {

		$content = $this->replace_with_external_tags( $content, 'replace_in_body', $is_partial );

		$this->is_partial = $is_partial;
		$content          = $this->apply_special_tags( $content );
		$content          = $this->replace( $content, '' );
		$this->is_partial = false;
		return $content;
	}

	/**
	 * Register template tags
	 */
	public function register() {
		/** @var \WP_Locale $wp_locale */
		global $wp_locale;

		$this->tags['unsubscribe_url'] = array(
			'description' => __( 'The unsubscribe URL.', 'newsletter-optin-box' ),
			'callback'    => '\Hizzle\Noptin\Emails\Main::get_current_unsubscribe_url',
		);

		$this->tags['view_in_browser_url'] = array(
			'description' => __( 'The "View in Browser" URL.', 'newsletter-optin-box' ),
			'callback'    => '\Hizzle\Noptin\Emails\Main::get_current_view_in_browser_url',
		);

		$this->tags['blog_name'] = array(
			'description' => __( 'The website name.', 'newsletter-optin-box' ),
			'replacement' => get_bloginfo( 'name' ),
		);

		$this->tags['blog_description'] = array(
			'description' => __( 'The website description.', 'newsletter-optin-box' ),
			'replacement' => get_bloginfo( 'description' ),
		);

		$this->tags['home_url'] = array(
			'description' => __( 'The website URL.', 'newsletter-optin-box' ),
			'callback'    => 'home_url',
			'no_args'     => true,
		);

		$this->tags['date'] = array(
			'description' => __( 'The current date', 'newsletter-optin-box' ),
			'replacement' => date_i18n( get_option( 'date_format' ) ),
		);

		$this->tags['time'] = array(
			'description' => __( 'The current time', 'newsletter-optin-box' ),
			'replacement' => date_i18n( get_option( 'time_format' ) ),
		);

		$this->tags['year'] = array(
			'description' => __( 'The current year', 'newsletter-optin-box' ),
			'replacement' => date_i18n( 'Y' ),
		);

		$this->tags['month'] = array(
			'description'       => __( 'The current month', 'newsletter-optin-box' ),
			'replacement'       => current_time( 'm' ),
			'options'           => $wp_locale->month,
			'conditional_logic' => 'number',
		);

		$this->tags['day'] = array(
			'description'       => __( 'The day of the month', 'newsletter-optin-box' ),
			'replacement'       => current_time( 'j' ),
			'options'           => array_combine( range( 1, 31 ), range( 1, 31 ) ),
			'conditional_logic' => 'number',
		);

		$this->tags['weekday'] = array(
			'description'       => __( 'The day of the weekday', 'newsletter-optin-box' ),
			'replacement'       => current_time( 'w' ),
			'options'           => $wp_locale->weekday,
			'conditional_logic' => 'number',
		);

		$this->tags['noptin'] = array(
			'description' => __( 'Displays a personalized link to the Noptin website.', 'newsletter-optin-box' ),
			'callback'    => array( $this, 'noptin_url' ),
		);

		$this->tags['noptin_company'] = array(
			'description' => __( 'The company name that you set in Noptin > Settings > Emails.', 'newsletter-optin-box' ),
			'callback'    => array( $this, 'noptin_company' ),
		);

		$this->tags['subscriber_count'] = array(
			'description' => __( 'Replaced with the total number of subscribers', 'newsletter-optin-box' ),
			'callback'    => 'get_noptin_subscribers_count',
		);

		$this->tags['rule'] = array(
			'description' => __( 'Displays a horizontal rule', 'newsletter-optin-box' ),
			'callback'    => array( $this, 'get_horizontal_rule' ),
			'example'     => "rule height='3px' color='black' width='100%' margin='50px'",
		);

		$this->tags['spacer'] = array(
			'description' => __( 'Adds a blank vertical space', 'newsletter-optin-box' ),
			'callback'    => array( $this, 'get_spacer' ),
			'example'     => "spacer height='50px'",
		);

		$this->tags['button'] = array(
			'description' => __( 'Displays a button', 'newsletter-optin-box' ),
			'callback'    => array( $this, 'get_button' ),
			'example'     => "button text='Click Here' url='" . home_url() . "' background='brand' color='white' rounding='4px'",
		);

		// Ensure we have a replacement for [[email]].
		if ( ! isset( $this->tags['email'] ) ) {
			$this->tags['email'] = array(
				'description' => __( 'Current email address', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_email' ),
			);
		}

		$this->tags['posts'] = array(
			'description' => __( 'Displays a list of posts.', 'newsletter-optin-box' ),
			'callback'    => array( $this, 'get_posts' ),
			'example'     => 'posts style="list" post_type="post" limit="10"',
			'partial'     => true,
		);
	}

	/**
	 * Returns a horizontal rule
	 *
	 * @param array $args
	 * @return string
	 */
	public function get_horizontal_rule( $args = array() ) {
		$height = isset( $args['height'] ) ? $args['height'] : '3px';
		$color  = isset( $args['color'] ) ? $args['color'] : '#454545';
		$width  = isset( $args['width'] ) ? $args['width'] : '100%';
		$margin = isset( $args['margin'] ) ? $args['margin'] : '50px';

		return sprintf(
			'<hr style="border-width: 0; background: %s; color: %s; height:%s; width:%s; margin:%s auto;">',
			esc_attr( $color ),
			esc_attr( $color ),
			esc_attr( $height ),
			esc_attr( $width ),
			esc_attr( $margin )
		);
	}

	/**
	 * Returns a spacer
	 *
	 * @param array $args
	 * @return string
	 */
	public function get_spacer( $args = array() ) {
		$spacer = isset( $args['height'] ) ? $args['height'] : '50px';
		return sprintf( "<div style='line-height:%s;height:%s;'>&#8202;</div>", esc_attr( $spacer ), esc_attr( $spacer ) );
	}

	/**
	 * Returns a button
	 *
	 * @param array $args
	 * @return string
	 */
	public function get_button( $args = array() ) {
		$url        = isset( $args['url'] ) ? $args['url'] : home_url();
		$background = isset( $args['background'] ) ? $args['background'] : 'brand';
		$color      = isset( $args['color'] ) ? $args['color'] : 'white';
		$rounding   = isset( $args['rounding'] ) ? $args['rounding'] : '4px';
		$text       = isset( $args['text'] ) ? $args['text'] : 'Click Here';

		if ( 'brand' === $background ) {
			$brand_color = get_noptin_option( 'brand_color' );
			$background  = empty( $brand_color ) ? '#1a82e2' : $brand_color;
		}

		// Generate button.
		$button = sprintf(
			'<a href="%s" style="background: %s; border: none; text-decoration: none; padding: 15px 25px; color: %s; border-radius: %s; display:inline-block; mso-padding-alt:0;text-underline-color:%s"><span style="mso-text-raise:15pt;">%s</span></a>',
			esc_attr( $url ), // Use esc_attr instead of esc_url to allow for merge tags.
			esc_attr( $background ),
			esc_attr( $color ),
			esc_attr( $rounding ),
			esc_attr( $background ),
			esc_html( $text )
		);

		return $this->center( $button );
	}

	/**
	 * Centers content.
	 *
	 * @param array $args
	 * @return string
	 */
	public function center( $content ) {

		ob_start();
		?>
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
			<tr>
				<td align="center" style="padding: 12px;">
					<div style='text-align: center; padding: 20px;' align='center'>
						<?php echo wp_kses_post( $content ); ?>
					</div>
				</td>
			</tr>
		</table>
		<?php
			return ob_get_clean();
	}

	/**
	 * Noptin URL
	 *
	 * @return string
	 */
	public function noptin_url() {
		static $subscriber_count = null;

		if ( null === $subscriber_count ) {
			$subscriber_count = get_noptin_subscribers_count();
		}

		// Don't show link if subscriber count is greater than 100 (typical spammers).
		if ( $subscriber_count > 100 ) {
			return sprintf(
				'<a target="_blank" href="%s">WordPress</a>',
				home_url()
			);
		}

		return sprintf(
			'<a target="_blank" href="%s">Noptin</a>',
			noptin_get_upsell_url( 'https://noptin.com/', 'Powered By', 'Referrals' )
		);
	}

	/**
	 * Noptin company
	 *
	 * @return string
	 */
	public function noptin_company() {
		return get_noptin_option( 'company', '' );
	}

	/**
	 * Returns posts.
	 *
	 * @param array $args
	 * @return string
	 */
	public function get_posts( $args = array() ) {
		// Fetch the posts.
		$posts = $this->get_merge_tag_posts( $args );

		// Abort if we have no posts.
		if ( empty( $posts ) ) {
			if ( isset( $args['skiponempty'] ) && 'yes' === $args['skiponempty'] ) {
				$GLOBALS['noptin_email_force_skip'] = array(
					'message' => __( 'No posts found.', 'newsletter-optin-box' ),
				);
			}

			return '';
		}

		return $this->get_posts_html( $args, $posts );
	}

	/**
	 * Retrieves the content for the posts merge tag.
	 *
	 * @param array $args
	 * @return \WP_Post[]
	 */
	public function get_merge_tag_posts( $args = array() ) {

		$post_type = ! empty( $args['post_type'] ) ? $args['post_type'] : 'post';
		if ( ! noptin_has_alk() ) {
			$post_type = 'post';
		}

		$query = array(
			'numberposts'         => ! empty( $args['limit'] ) ? intval( $args['limit'] ) : 10,
			'post_type'           => $post_type,
			'ignore_sticky_posts' => true,
			'suppress_filters'    => true,
		);

		if ( ! empty( $args['since_last_send'] ) ) {
			$last_send = apply_filters( 'noptin_get_last_send_date', 0 );

			if ( $last_send ) {
				if ( is_numeric( $last_send ) ) {
					$last_send = new DateTime( "@$last_send" );
					$last_send->setTimezone( wp_timezone() );
					$last_send = $last_send->format( 'Y-m-d H:i' );
				}

				$query['date_query'] = array(
					array(
						'inclusive' => true,
						'after'     => $last_send,
					),
				);
			}
		}

		if ( ! noptin_has_alk() ) {
			return get_posts( $query );
		}

		// Advanced args.
		$advanced_args = array(
			'author'              => 'int',
			'author_name'         => 'string',
			'author__in'          => 'array_int',
			'author__not_in'      => 'array_int',
			'orderby'             => 'string',
			'order'               => 'string',
			'meta_key'            => 'string',
			'meta_value'          => 'string',
			'meta_value_num'      => 'float',
			'meta_compare'        => 'string',
			'nopaging'            => 'bool',
			'offset'              => 'int',
			'paged'               => 'int',
			'page'                => 'int',
			'ignore_sticky_posts' => 'bool',
		);

		// Allow to filter post digests by language.
		if ( function_exists( 'pll_languages_list' ) ) {
			$advanced_args['lang'] = 'string';
		}

		foreach ( $advanced_args as $key => $type ) {
			if ( isset( $args[ $key ] ) && '' !== $args[ $key ] ) {
				$value = $args[ $key ];

				if ( 'array_int' === $type ) {
					$value = noptin_parse_int_list( $value );
				} elseif ( 'int' === $type ) {
					$value = intval( $value );
				} elseif ( 'float' === $type ) {
					$value = floatval( $value );
				} elseif ( 'bool' === $type ) {
					$value = boolval( $value ) && 'false' !== $value;
				}

				$query[ $key ] = $value;

				if ( 'lang' === $key ) {
					$query['suppress_filters'] = false;
				}
			}
		}

		// Set the taxonomy query.
		$tax_query = array();

		foreach ( noptin_parse_list( $post_type ) as $_post_type ) {
			foreach ( get_object_taxonomies( $_post_type ) as $taxonomy ) {

				// Special treatment for tags.
				if ( 'post_tag' === $taxonomy ) {
					$allowed = array(
						'tag'           => 'string',
						'tag_id'        => 'int',
						'tag__and'      => 'array_int',
						'tag__in'       => 'array_int',
						'tag__not_in'   => 'array_int',
						'tag_slug__and' => 'array_string',
						'tag_slug__in'  => 'array_string',
					);

					foreach ( $allowed as $key => $type ) {
						if ( ! empty( $args[ $key ] ) ) {
							$value = $args[ $key ];
						} elseif ( ! empty( $args[ 'post_' . $key ] ) ) {
							$value = $args[ 'post_' . $key ];
						} else {
							continue;
						}

						if ( 'array_int' === $type ) {
							$value = wp_parse_id_list( $value );
						} elseif ( 'array_string' === $type ) {
							$value = noptin_parse_list( $value, true );
						} elseif ( 'int' === $type ) {
							$value = absint( $value );
						}

						$query[ $key ] = $value;
					}
					continue;
				}

				// Special treatment for categories.
				if ( 'category' === $taxonomy ) {
					$allowed = array(
						'cat'              => 'string',
						'category_name'    => 'string',
						'category__and'    => 'array_int',
						'category__in'     => 'array_int',
						'category__not_in' => 'array_int',
					);

					if ( isset( $args['category'] ) ) {
						$args['category_name'] = $args['category'];
					}

					foreach ( $allowed as $key => $type ) {
						if ( ! empty( $args[ $key ] ) ) {
							$value = $args[ $key ];
						} elseif ( ! empty( $args[ 'post_' . $key ] ) ) {
							$value = $args[ 'post_' . $key ];
						} else {
							continue;
						}

						if ( 'array_int' === $type ) {
							$value = wp_parse_id_list( $value );
						}

						$query[ $key ] = $value;
					}
					continue;
				}

				// Taxonomy slugs.
				if ( isset( $args[ $taxonomy ] ) ) {
					$tax_query[] = array(
						'taxonomy' => $taxonomy,
						'field'    => 'slug',
						'terms'    => noptin_parse_list( $args[ $taxonomy ], true ),
					);
				}

				// Taxonomy ids.
				if ( isset( $args[ $taxonomy . '_ids' ] ) ) {
					$tax_query[] = array(
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => noptin_parse_int_list( $args[ $taxonomy . '_ids' ] ),
					);
				}

				// Taxonomy slugs not in.
				if ( isset( $args[ $taxonomy . '_not_in' ] ) ) {
					$tax_query[] = array(
						'taxonomy' => $taxonomy,
						'field'    => 'slug',
						'terms'    => noptin_parse_list( $args[ $taxonomy . '_not_in' ], true ),
						'operator' => 'NOT IN',
					);
				}

				// Taxonomy ids not in.
				if ( isset( $args[ $taxonomy . '_ids_not_in' ] ) ) {
					$tax_query[] = array(
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => noptin_parse_int_list( $args[ $taxonomy . '_ids_not_in' ] ),
						'operator' => 'NOT IN',
					);
				}

				// Taxonomy slugs AND.
				if ( isset( $args[ $taxonomy . '_and' ] ) ) {
					$tax_query[] = array(
						'taxonomy' => $taxonomy,
						'field'    => 'slug',
						'terms'    => noptin_parse_list( $args[ $taxonomy . '_and' ], true ),
						'operator' => 'AND',
					);
				}

				// Taxonomy ids AND.
				if ( isset( $args[ $taxonomy . '_ids_and' ] ) ) {
					$tax_query[] = array(
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => noptin_parse_int_list( $args[ $taxonomy . '_ids_and' ] ),
						'operator' => 'AND',
					);
				}
			}
		}

		if ( ! empty( $tax_query ) ) {
			if ( 1 < count( $tax_query ) ) {
				$tax_query['relation'] = count( noptin_parse_list( $post_type ) ) > 1 ? 'OR' : 'AND';
			}

			$query['tax_query'] = $tax_query;
		}

		// Meta query.
		$query = $this->add_meta_query( $query, $args );
		$query = apply_filters( 'noptin_posts_merge_tag_query', $query, $args, $this );
		$posts = get_posts( $query );

		// Debug the query later.
		if ( defined( 'NOPTIN_IS_TESTING' ) && NOPTIN_IS_TESTING && ! empty( $GLOBALS['wpdb']->last_query ) ) {
			noptin_error_log( $query, 'Posts args' );
			noptin_error_log( $GLOBALS['wpdb']->last_query, 'Posts query' );
			noptin_error_log( count( $posts ), 'Posts posts' );
		}

		return $posts;
	}

	/**
	 * Processes the meta query.
	 *
	 * @param array $query
	 * @param array $args
	 * @return array
	 */
	private function add_meta_query( $query, $args ) {

		// Abort if we have no meta query.
		if ( empty( $args['meta_query'] ) ) {
			return $query;
		}

		// Meta query.
		wp_parse_str( $args['meta_query'], $meta_query );

		$prepared = array();
		$flat     = array();

		if ( ! is_array( $meta_query ) ) {
			return $query;
		}

		foreach ( $meta_query as $key => $value ) {

			// Relation.
			if ( 'relation' === $key ) {
				$prepared['relation'] = $value;
				continue;
			}

			// Custom field key.
			if ( ! is_array( $value ) ) {
				if ( ! is_numeric( $key ) ) {
					$flat[ $key ] = $value;
				}

				continue;
			}

			// Do we have a field key?
			if ( ! isset( $value['key'] ) ) {
				continue;
			}

			$single_query = array(
				'key'     => $value['key'],
				'compare' => isset( $value['compare'] ) ? $value['compare'] : '=',
			);

			// Value.
			if ( 'EXISTS' !== $single_query['compare'] && 'NOT EXISTS' !== $single_query['compare'] ) {

				// Maybe convert to array.
				if ( in_array( $single_query['compare'], array( 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' ), true ) ) {
					$single_query['value'] = noptin_parse_list( $value['value'], true );
				} else {
					$single_query['value'] = $value['value'];
				}

				// Type.
				if ( isset( $value['type'] ) ) {
					$single_query['type'] = $value['type'];

					// Dates.
					if ( in_array( $value['type'], array( 'DATE', 'DATETIME', 'TIME' ), true ) ) {

						// Format.
						if ( ! isset( $value['format'] ) ) {
							switch ( $value['type'] ) {
								case 'DATE':
									$value['format'] = 'Y-m-d';
									break;
								case 'DATETIME':
									$value['format'] = 'Y-m-d H:i:s';
									break;
								case 'TIME':
									$value['format'] = 'H:i:s';
									break;
							}
						}

						// Value.
						if ( is_array( $single_query['value'] ) ) {
							foreach ( $single_query['value'] as $k => $v ) {
								$single_query['value'][ $k ] = gmdate( strtotime( $v ), $value['format'] );
							}
						} else {
							$single_query['value'] = gmdate( strtotime( $single_query['value'] ), $value['format'] );
						}
					}
				}
			}

			$prepared[] = $single_query;
		}

		if ( ! empty( $flat ) ) {
			$prepared[] = $flat;
		}

		// Ensure we have relation if more than one query.
		if ( count( $prepared ) > 1 && ! isset( $prepared['relation'] ) ) {
			$prepared['relation'] = 'AND';
		}

		// Add the meta query if we have one.
		if ( ! empty( $prepared ) ) {
			$query['meta_query'] = $prepared;
		}

		return $query;
	}

	/**
	 * Get posts html to display.
	 *
	 * @param array $args
	 * @param \WP_Post[] $campaign_posts
	 *
	 * @return string
	 */
	public static function get_posts_html( $args = array(), $campaign_posts = array() ) {

		$template = isset( $args['style'] ) ? $args['style'] : 'list';

		// Allow overwriting this.
		$html = apply_filters( 'noptin_post_digest_html', null, $template, $campaign_posts );

		if ( null !== $html ) {
			return $html;
		}

		$args['campaign_posts'] = $campaign_posts;

		ob_start();
		get_noptin_template( 'post-digests/email-posts-' . $template . '.php', $args );
		return ob_get_clean();
	}

	/**
	 * Returns an array of special tags.
	 *
	 * @return array
	 */
	public static function get_special_tags() {
		return apply_filters(
			'noptin_email_special_tags',
			array(
				'noptin_conditional_email_content' => array( __CLASS__, 'conditional_email_content' ),
			)
        );
	}

	/**
	 * @see get_shortcode_regex
	 * @link https://regex101.com/r/6Nu7BT/1
	 */
	protected static function get_special_tags_regex( $tagnames = null ) {
		if ( null === $tagnames ) {
			$tagnames  = array_keys( self::get_special_tags() );
		}

		$tagregexp = implode( '|', array_map( 'preg_quote', $tagnames ) );

		/*
		* WARNING! Do not change this regex without changing self::apply_special_tags()
		*/

		// phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound -- don't remove regex indentation
		return '\\{'                             // Opening bracket.
			. '(\\{?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]].
			. "($tagregexp)"                     // 2: Shortcode name.
			. '(?![\\w-])'                       // Not followed by word character or hyphen.
			. '('                                // 3: Unroll the loop: Inside the opening shortcode tag.
			.     '[^\\}\\/]*'                   // Not a closing bracket or forward slash.
			.     '(?:'
			.         '\\/(?!\\})'               // A forward slash not followed by a closing bracket.
			.         '[^\\}\\/]*'               // Not a closing bracket or forward slash.
			.     ')*?'
			. ')'
			. '(?:'
			.     '(\\/)'                        // 4: Self closing tag...
			.     '\\}'                          // ...and closing bracket.
			. '|'
			.     '\\}'                          // Closing bracket.
			.     '(?:'
			.         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags.
			.             '[^\\{]*+'             // Not an opening bracket.
			.             '(?:'
			.                 '\\{(?!\\/\\2\\})' // An opening bracket not followed by the closing shortcode tag.
			.                 '[^\\{]*+'         // Not an opening bracket.
			.             ')*+'
			.         ')'
			.         '\\{\\/\\2\\}'             // Closing shortcode tag.
			.     ')?'
			. ')'
			. '(\\}?)';                          // 6: Optional second closing bracket for escaping shortcodes: [[tag]].
		// phpcs:enable
	}

	/**
	 * @see do_shortcode()
	 * @return string Content with shortcodes filtered out.
	 */
	public function apply_special_tags( $content ) {
		$special_tags = self::get_special_tags();

		if ( ! is_string( $content ) || ! str_contains( $content, '{' ) ) {
			return $content;
		}

		if ( empty( $special_tags ) || ! is_array( $special_tags ) ) {
			return $content;
		}

		// Find all registered tag names in $content.
		preg_match_all( '@\{([^<>&/\{\]\x00-\x20=]++)@', $content, $matches );
		$tagnames = array_intersect( array_keys( $special_tags ), $matches[1] );

		if ( empty( $tagnames ) ) {
			return $content;
		}

		$pattern = self::get_special_tags_regex( $tagnames );
		$result  = preg_replace_callback( "/$pattern/", array( $this, 'apply_special_tag' ), $content );
		return null === $result ? $content : $result;
	}

	/**
	 * Regular Expression callable for self::apply_special_tags() for calling shortcode hook.
	 *
	 * @see self::get_special_tags_regex() for details of the match array contents.
	 *
	 * @param array $m {
	 *     Regular expression match array.
	 *
	 *     @type string $0 Entire matched shortcode text.
	 *     @type string $1 Optional second opening bracket for escaping shortcodes.
	 *     @type string $2 Shortcode name.
	 *     @type string $3 Shortcode arguments list.
	 *     @type string $4 Optional self closing slash.
	 *     @type string $5 Content of a shortcode when it wraps some content.
	 *     @type string $6 Optional second closing bracket for escaping shortcodes.
	 * }
	 * @return string Shortcode output.
	 */
	protected function apply_special_tag( $m ) {
		$special_tags = self::get_special_tags();

		// Allow {{foo}} syntax for escaping a tag.
		if ( '{' === $m[1] && '}' === $m[6] ) {
			return substr( $m[0], 1, -1 );
		}

		$tag = $m[2];

		if ( ! is_callable( $special_tags[ $tag ] ?? '' ) ) {
			_doing_it_wrong(
				__FUNCTION__,
				/* translators: %s: Shortcode tag. */
				sprintf( 'Attempting to parse a shortcode without a valid callback: %s', $tag ),
				'4.3.0'
			);
			return $m[0];
		}

		$attr    = shortcode_parse_atts( $m[3] );
		$content = isset( $m[5] ) ? $m[5] : null;

		return $m[1] . call_user_func( $special_tags[ $tag ], $attr, $content, $this, $tag ) . $m[6];
	}

	/**
	 * Processes the conditional email content.
	 *
	 * @param array $attributes
	 * @param string $content
	 * @param Noptin_Email_Tags $tag_manager
	 * @param string $tag
	 * @return string
	 */
	public static function conditional_email_content( $attributes, $content, $tag_manager, $tag ) {
		// Abort if the conditional logic is not enabled.
		if ( empty( $attributes['enabled'] ) || 'false' === $attributes['enabled'] ) {
			return $content;
		}

		$action = $attributes['action'] ?? 'allow';
		$type   = $attributes['type'] ?? 'all';
		$rules  = $attributes['rules'] ?? array();

		if ( is_string( $rules ) ) {
			$rules = json_decode( rawurldecode( $rules ), true );
		}

		// Abort if we have no rules.
		if ( empty( $rules ) ) {
			return $content;
		}

		$skip_tags = $tag_manager->is_partial ? \Hizzle\Noptin\Emails\Records::conditional_logic_skip_tags( array() ) : array();
		$is_met    = $tag_manager->check_conditional_logic(
			array(
				'rules'   => $rules,
				'action'  => $action,
				'type'    => $type,
				'enabled' => true,
			),
			$skip_tags,
			false
		);

		// If we have more conditional logic that should be checked per recipient, return the content.
		if ( is_array( $is_met ) ) {
			return sprintf(
				'{noptin_conditional_email_content enabled="true" action="%s" type="%s" rules="%s"}%s{/noptin_conditional_email_content}',
				$action,
				$type,
				rawurlencode( json_encode( $is_met ) ),
				$content
			);
		}

		// If the conditional logic is not met, return an empty string.
		if ( ! $is_met ) {
			return '';
		}

		return $content;
	}
}
