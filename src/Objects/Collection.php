<?php

/**
 * Collection of records.
 *
 * @since   1.0.0
 */

namespace Hizzle\Noptin\Objects;

defined( 'ABSPATH' ) || exit;

/**
 * Base object type.
 */
abstract class Collection {

	/**
	 * @var string the record class.
	 */
	public $record_class = '\Hizzle\Noptin\Objects\Record';

	/**
	 * @var string icon
	 */
	public $icon = 'database';

	/**
	 * @var string object type.
	 */
	public $object_type = 'generic';

	/**
	 * @var string type.
	 */
	public $type;

	/**
	 * @var string prefix.
	 */
	public $smart_tags_prefix = null;

	/**
	 * @var string label.
	 */
	public $label;

	/**
	 * @var string label.
	 */
	public $singular_label;

	/**
	 * @var string integration.
	 */
	public $integration;

	/**
	 * @var string $can_list Can list.
	 */
	public $can_list = false;

	/**
	 * @var string $show_tab Whether to display this collection as a custom tab.
	 */
	public $show_tab = false;

	/**
	 * @var Record|null $current_item Current item.
	 */
	public $current_item = null;

	/**
	 * @var string $context
	 */
	public $context;

	/**
	 * @var array $provides
	 */
	public $provides = array();

	// Template.
	public $title_field       = '';
	public $description_field = '';
	public $image_field       = '';
	public $url_field         = '';
	public $meta_field        = '';
	public $is_stand_alone    = true;

	/**
	 * Class constructor.
	 */
	public function __construct() {

		// Set automation rule smart tags prefix.
		if ( is_null( $this->smart_tags_prefix ) ) {
			$this->smart_tags_prefix = $this->type;
		}

		if ( empty( $this->context ) ) {
			$this->context = 'noptin/' . preg_replace( '/[^a-z0-9\-]/', '-', strtolower( $this->type ) ) . '-template';
		}

		if ( $this->can_list ) {

			// Register object.
			add_filter( 'noptin_email_editor_objects', array( $this, 'register_object' ) );

			// Register shortcode.
			add_shortcode( 'noptin_' . $this->plural_type() . '_list', array( $this, 'handle_list_shortcode' ) );

			// Register email type.
			if ( $this->is_stand_alone ) {
				new Digest( $this );
			}
		}

		// Load automation rule.
		if ( did_action( 'noptin_automation_rules_load' ) ) {
			$this->load_automation_rules( noptin()->automation_rules );
		} else {
			add_action( 'noptin_automation_rules_load', array( $this, 'load_automation_rules' ) );
		}

		// Register merge tags.
		add_action( 'noptin_before_send_email', array( $this, 'register_temporary_merge_tags' ) );
		add_action( 'noptin_register_temporary_merge_tags', array( $this, 'register_temporary_merge_tags' ) );

		// Unregister merge tags.
		add_action( 'noptin_after_send_email', array( $this, 'unregister_temporary_merge_tags' ) );
		add_action( 'noptin_unregister_temporary_merge_tags', array( $this, 'unregister_temporary_merge_tags' ) );
	}

	/**
	 * Loads the automation rule triggers and actions.
	 *
	 * @param \Noptin_Automation_Rules $rules The automation rules instance.
	 */
	public function load_automation_rules( $rules ) {

		// Register triggers.
		foreach ( $this->get_all_triggers() as $key => $args ) {
			$args['provides'] = empty( $args['provides'] ) ? array() : noptin_parse_list( $args['provides'] );

			if ( empty( $args['subject'] ) ) {
				$args['subject'] = 'current_user';
			}

			// Only auto-provide the current user if the subject is not a WordPress user.
			if ( ! in_array( $args['subject'], Users::$user_types, true ) || 'post_author' === $args['subject'] ) {
				$args['provides'] = array_merge( $args['provides'], array( 'current_user' ) );
			}

			$args['provides'] = $this->filter( $args['provides'], 'provided_collections' );

			$manual_recipients = array();
			if ( 'current_user' !== $args['subject'] && in_array( 'current_user', $args['provides'], true ) ) {
				$current_user      = Store::get( 'current_user' );

				if ( $current_user ) {
					$manual_recipients = array_merge( $manual_recipients, $current_user->get_manual_recipients() );
				}
			}

			$subject = $this->type === $args['subject'] ? $this : Store::get( $args['subject'] );

			if ( $subject ) {
				$manual_recipients = array_merge( $manual_recipients, $subject->get_manual_recipients() );
			}

			if ( ! empty( $manual_recipients ) ) {
				$mail_config                      = $args['mail_config'] ?? array();
				$mail_config['manual_recipients'] = array_merge(
					$mail_config['manual_recipients'] ?? array(),
					$manual_recipients
				);
				$args['mail_config']              = $mail_config;
			}

			$args = apply_filters( 'noptin_collection_type_register_trigger_args', $args, $this );

			$rules->add_trigger(
				new Trigger( $key, $args, $this )
			);
		}

		// Register actions.
		foreach ( $this->get_all_actions() as $key => $args ) {
			$rules->add_action(
				new Action( $key, $args, $this )
			);
		}
	}

	/**
	 * Returns a list of available triggers.
	 *
	 * @return array $triggers The triggers.
	 */
	public function get_triggers() {
		return array();
	}

	/**
	 * Retrieves all filtered triggers.
	 *
	 */
	public function get_all_triggers() {
		return $this->filter( $this->get_triggers(), 'triggers' );
	}

	/**
	 * Triggers actions.
	 *
	 * @param string $trigger The trigger name.
	 * @param array $args The trigger args.
	 */
	public function trigger( $trigger, $args ) {

		$args['provides'] = empty( $args['provides'] ) ? array() : $args['provides'];

		if ( empty( $args['provides']['current_user'] ) ) {
			$args['provides']['current_user'] = get_current_user_id();
		}

		if ( ! isset( $args['subject_id'] ) ) {
			$user               = wp_get_current_user();
			$args['subject_id'] = ( isset( $user->ID ) ? (int) $user->ID : 0 );

			if ( empty( $args['email'] ) ) {
				$args['email'] = $user->user_email;
			}
		}

		$args = $this->filter( $args, 'trigger_args' );

		do_action( 'noptin_fire_object_trigger_' . $trigger, $args );
	}

	/**
	 * Returns a list of available (actions).
	 *
	 * @return array $actions The actions.
	 */
	public function get_actions() {
		return array();
	}

	/**
	 * Retrieves all filtered actions.
	 *
	 */
	public function get_all_actions() {
		return $this->filter( $this->get_actions(), 'actions' );
	}

	/**
	 * Retrieves available fields.
	 *
	 */
	abstract public function get_fields();

	/**
	 * Retrieves available filters.
	 *
	 * @return array
	 */
	public function get_filters() {
		return array();
	}

	/**
	 * Retrieves the manual recipients.
	 */
	public function get_manual_recipients() {
		return array();
	}

	/**
	 * Returns the template for the list shortcode.
	 */
	protected function get_list_shortcode_template() {
		$template = array();

		if ( ! empty( $this->title_field ) ) {
			$template['heading'] = \Hizzle\Noptin\Emails\Admin\Editor::merge_tag_to_block_name( $this->field_to_merge_tag( $this->title_field ) );
		}

		if ( ! empty( $this->description_field ) ) {
			$template['description'] = $this->field_to_merge_tag( $this->description_field );
		}

		if ( ! empty( $this->image_field ) ) {
			$template['image'] = \Hizzle\Noptin\Emails\Admin\Editor::merge_tag_to_block_name( $this->field_to_merge_tag( $this->image_field ) );
		}

		if ( ! empty( $this->url_field ) ) {
			$template['button'] = \Hizzle\Noptin\Emails\Admin\Editor::merge_tag_to_block_name( $this->field_to_merge_tag( $this->url_field ) );
		}

		if ( ! empty( $this->meta_field ) ) {
			$template['meta'] = $this->field_to_merge_tag( $this->meta_field );
		}

		return $template;
	}

	/**
	 * Converts a field to a merge tag.
	 *
	 * @return string $merge_tag The merge tag.
	 */
	public function field_to_merge_tag( $field, $attributes = '' ) {

		if ( empty( $field ) ) {
			return '';
		}

		if ( ! empty( $attributes ) ) {

			// If we have an array of attributes, convert it to a string.
			if ( is_array( $attributes ) ) {
				$prepared = '';

				foreach ( $attributes as $key => $value ) {
					$prepared .= " {$key}='{$value}'";
				}

				$attributes = $prepared;
			}

			$attributes = ' ' . $attributes;
		}

		return "[[{$this->smart_tags_prefix}.{$field}{$attributes}]]";
	}

	/**
	 * Converts a field to an image block.
	 *
	 */
	public function featured_image_block() {
		$block_name  = \Hizzle\Noptin\Emails\Admin\Editor::merge_tag_to_block_name( $this->field_to_merge_tag( $this->image_field ) );
		$block_class = str_replace( '/', '-', $block_name );
		$class_name  = 'wp-block-' . $block_class . ' noptin-image-block__wrapper ' . $block_class;

		ob_start();
		?>
			<!-- wp:<?php echo esc_html( $block_name ); ?> {"anchor":"<?php echo esc_html( $class_name ); ?>"} -->
			<table border="0" cellpadding="0" cellspacing="0" role="presentation" class="<?php echo esc_html( $class_name ); ?>">
				<tbody>
					<tr>
						<td>
							<div class="noptin-block__margin-wrapper" style="display:inline-block;max-width:100%">
								<?php if ( ! empty( $this->url_field ) ) : ?>
									<a href="<?php echo esc_html( $this->field_to_merge_tag( $this->url_field ) ); ?>" style="display:block;text-decoration:none;max-width:100%;line-height:0">
								<?php endif; ?>
									<img
										src="<?php echo esc_html( $this->field_to_merge_tag( $this->image_field ) ); ?>"
										alt="<?php echo esc_html( $this->field_to_merge_tag( $this->title_field ) ); ?>"
										border="0"
										style="max-width:100%" />
								<?php if ( ! empty( $this->url_field ) ) : ?>
									</a>
								<?php endif; ?>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
			<!-- /wp:<?php echo esc_html( $block_name ); ?> -->
		<?php
		return ob_get_clean();
	}

	/**
	 * Converts a field to an image block.
	 *
	 */
	public function read_more_block() {
		$block_name = \Hizzle\Noptin\Emails\Admin\Editor::merge_tag_to_block_name( $this->field_to_merge_tag( $this->url_field ) );

		ob_start();
		?>
			<!-- wp:<?php echo esc_html( $block_name ); ?> {"style":{"noptin":{"border":{"radius":"5px"},"typography":{"textTransform":"none","textDecoration":"none","textAlign":"center"},"align":"left"}}} -->
			<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" class="wp-block-<?php echo esc_attr( str_replace( '/', '-', $block_name ) ); ?> noptin-button-block__wrapper">
                <tbody>
                    <tr>
                        <td align="left">
                            <div class="noptin-block__margin-wrapper">
                                <table>
                                    <tbody>
                                        <tr>
                                            <td role="presentation" valign="middle" class="noptin-button-link__wrapper" style="text-align:center;cursor:auto;border-radius:5px">
                                                <a class="noptin-button-link" href="<?php echo esc_html( $this->field_to_merge_tag( $this->url_field ) ); ?>" style="text-transform:none;text-decoration:none;text-align:center;border-radius:5px;margin:0px;display:block;word-break:break-word"><?php esc_html_e( 'Read more', 'newsletter-optin-box' ); ?></a>
											</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
			<!-- /wp:<?php echo esc_html( $block_name ); ?> -->
		<?php
		return ob_get_clean();
	}

	/**
	 * Retrieves all filtered fields.
	 *
	 */
	public function get_all_fields() {
		$fields = $this->get_fields();

		// Maybe add newsletter subscription status.
		if ( 'person' === $this->object_type ) {
			$fields['newsletter'] = array(
				'label'   => __( 'Newsletter subscription status', 'newsletter-optin-box' ),
				'type'    => 'string',
				'options' => array(
					'yes' => __( 'subscribed', 'newsletter-optin-box' ),
					'no'  => __( 'unsubscribed', 'newsletter-optin-box' ),
				),
			);

			$fields['avatar_url'] = array(
				'label' => __( 'Avatar URL', 'newsletter-optin-box' ),
				'type'  => 'string',
			);
		}

		return $this->filter( $fields, 'fields' );
	}

	/**
	 * Filters the provided value.
	 *
	 */
	protected function filter( $value, $type ) {
		return apply_filters(
			"noptin_{$this->type}_collection_{$type}",
			$value,
			$this
		);
	}

	/**
	 * Retrieves several items.
	 *
	 * @param array $filters The available filters.
	 * @return int[] $ids The object IDs.
	 */
	public function get_all( $filters ) {
		return array();
	}

	/**
	 * Retrieves several items by email.
	 *
	 */
	public function get_all_by_email( $email_address, $limit = 25 ) {
		return array();
	}

	/**
	 * Retrieves a single record.
	 *
	 * @param mixed $record The record.
	 * @return Record $record The record.
	 */
	public function get( $record ) {
		$class = $this->record_class;

		return new $class( $record );
	}

	/**
	 * Registers the custom tab.
	 *
	 * @since 3.0.0
	 * @return array
	 */
	public function get_custom_tab_details() {
		return array(
			'title'        => $this->label,
			'type'         => 'table',
			'emptyMessage' => sprintf(
				// translators: %s is the object type label, e.g. "orders".
				__( 'No %s found.', 'newsletter-optin-box' ),
				strtolower( $this->label )
			),
			'headers'      => $this->get_custom_tab_headers(),
		);
	}

	/**
	 * Fetches the custom tab headers.
	 *
	 * @since 3.0.0
	 * @return array
	 */
	protected function get_custom_tab_headers() {
		return array();
	}

	/**
	 * Custom tab callback.
	 *
	 * @since 3.0.0
	 * @param string $email_address
	 * @return array
	 */
	public function process_custom_tab( $email_address ) {

		$prepared = array();

		foreach ( $this->get_all_by_email( $email_address ) as $record ) {
			$record = $this->get( $record );

			if ( $record->exists() ) {
				$prepared[] = $record->prepare_custom_tab();
			}
		}

		return array_filter( $prepared );
	}

	/**
	 * Retrieves a test object args.
	 *
	 * @since 3.0.0
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule
	 * @throws \Exception
	 * @return array
	 */
	public function get_test_args( $rule ) {
		return array();
	}

	/**
	 * Returns an array of related collections.
	 *
	 * @since 3.0.0
	 * @return Collection[]
	 */
	public function get_related_collections() {
		$filtered = Store::filtered( array( 'object_type' => $this->object_type ) );
		unset( $filtered[ $this->type ] );
		return $filtered;
	}

	/**
	 * (Maybe) Registers the object.
	 */
	public function register_object( $objects ) {

		$objects[ $this->type ] = array(
			'object_type'    => $this->object_type,
			'icon'           => $this->icon,
			'type'           => $this->type,
			'name'           => $this->plural_type(),
			'label'          => $this->label,
			'singular_label' => $this->singular_label,
			'filters'        => $this->get_filters(),
			'merge_tags'     => noptin_prepare_merge_tags_for_js( Store::smart_tags( $this->type, $this->singular_label ) ),
			'template'       => $this->get_list_shortcode_template(),
			'provides'       => $this->provides,
			'is_stand_alone' => $this->is_stand_alone,
			'query_defaults' => (object) $this->get_query_defaults(),
		);

		return $objects;
	}

	/**
	 * Retrieves the collection defaults.
	 *
	 * @return array
	 */
	protected function get_query_defaults() {
		return apply_filters(
			"noptin_{$this->type}_collection_query_defaults",
			array()
		);
	}

	/**
	 * Returns the type as a plural string.
	 */
	public function plural_type() {
		return $this->type . 's';
	}

	/**
	 * Prepares a query filter.
	 *
	 * @param array $filters The filters.
	 * @param string $key The filter key.
	 * @return array $filters The prepared filters.
	 */
	protected function prepare_query_filter( $filters, $key ) {

		// Abort if no value or items.
		if ( empty( $filters[ $key ] ) || empty( $filters[ $key ]['items'] ) || ! empty( $filters[ $key ]['disabled'] ) || ! is_array( $filters[ $key ]['items'] ) ) {
			unset( $filters[ $key ] );
			return $filters;
		}

		$prepared = $filters[ $key ]['items'];

		if ( ! empty( $filters[ $key ]['relation'] ) ) {
			$prepared['relation'] = $filters[ $key ]['relation'];
		}

		$filters[ $key ] = $prepared;
		return $filters;
	}

	/**
	 * Generates date filters.
	 *
	 */
	protected function generate_date_filters() {
		return array(
			'since_last_send'  => array(
				'label'           => __( 'Since Last Send', 'newsletter-optin-box' ),
				'el'              => 'input',
				'type'            => 'checkbox',
				'description'     => sprintf(
					/* translators: %s: Object type label. */
					__( 'Only show %s created since last send', 'newsletter-optin-box' ),
					strtolower( $this->label )
				),
				'show_by_default' => true,
				'default'         => false,
			),
			'published_before' => array(
				'label'       => __( 'Date Before', 'newsletter-optin-box' ),
				'el'          => 'input',
				'type'        => 'text',
				'placeholder' => sprintf(
					/* translators: %s: Examples. */
					__( 'Examples: %s', 'newsletter-optin-box' ),
					implode(
						', ',
						array(
							gmdate( 'Y-m-d' ),
							'-7 days',
							'1 year ago',
						)
					)
				),
				'description' => sprintf(
					/* translators: %s: Object type label. */
					__( 'Show %s created before this date.', 'newsletter-optin-box' ),
					strtolower( $this->label )
				),
			),
			'published_after'  => array(
				'label'       => __( 'Date After', 'newsletter-optin-box' ),
				'el'          => 'input',
				'type'        => 'text',
				'placeholder' => sprintf(
					/* translators: %s: Examples. */
					__( 'Examples: %s', 'newsletter-optin-box' ),
					implode(
						', ',
						array(
							gmdate( 'Y-m-d' ),
							'-7 days',
							'1 year ago',
						)
					)
				),
				'description' => sprintf(
					/* translators: %s: Object type label. */
					__( 'Show %s created after this date.', 'newsletter-optin-box' ),
					strtolower( $this->label )
				),
			),
		);
	}

	/**
	 * Prepares a date query filter.
	 *
	 * @param array $filters The filters.
	 */
	protected function prepare_date_query_filter( $filters ) {
		$date_query = array();

		foreach ( array( 'published_before', 'published_after', 'since_last_send' ) as $date ) {
			if ( ! empty( $filters[ $date ] ) ) {
				if ( 'since_last_send' === $date ) {
					$last_send = apply_filters( 'noptin_get_last_send_date', 0 );

					if ( $last_send ) {
						if ( is_numeric( $last_send ) ) {
							$last_send = new \DateTime( "@$last_send" );
							$last_send->setTimezone( wp_timezone() );
							$last_send = $last_send->format( 'Y-m-d H:i' );
						}

						$date_query[] = array(
							'inclusive' => true,
							'after'     => $last_send,
						);
					}
				} else {
					$key          = 'published_before' === $date ? 'before' : 'after';
					$date_query[] = array(
						'inclusive' => true,
						$key        => $filters[ $date ],
					);
				}

				unset( $filters[ $date ] );
			}
		}

		if ( ! empty( $date_query ) ) {
			$filters['date_query'] = $date_query;
		} else {
			unset( $filters['date_query'] );
		}

		return $filters;
	}

	/**
	 * Generates taxonomy filters.
	 *
	 * @param string $post_type The post type.
	 */
	protected function generate_taxonomy_filters( $post_type, $taxonomies = null ) {

		if ( ! is_array( $taxonomies ) ) {
			$taxonomies = wp_list_pluck(
				wp_list_filter(
					get_object_taxonomies( $post_type, 'objects' ),
					array(
						'public' => true,
					)
				),
				'label',
				'name'
			);
		}

		$filters = array();

		foreach ( $taxonomies as $taxonomy => $label ) {
			if ( 'category' === $taxonomy ) {
				$field     = 'category__in';
				$field_not = 'category__not_in';
			} elseif ( 'post_tag' === $taxonomy ) {
				$field     = 'tag__in';
				$field_not = 'tag__not_in';
			} else {
				$field     = 'tax_in_' . $taxonomy;
				$field_not = 'tax_not_in_' . $taxonomy;
			}

			$term_options = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				)
			);

			$prepared_options = array();

			foreach ( $term_options as $term ) {
				$prepared_options[] = array(
					'value'  => $term->term_id,
					'label'  => $term->name,
					'render' => $term->count > 0 ? sprintf(
						'<span class="noptin-taxonomy-term__name">%s</span> <span class="noptin-taxonomy-term__count">(%d)</span>',
						$term->name,
						$term->count
					) : null,
				);
			}

			$filters[ $field ] = array(
				'label'           => $label,
				'el'              => 'select',
				'multiple'        => true,
				'description'     => sprintf(
					/* translators: %s: Taxonomy name. */
					__( 'Filter by %s.', 'newsletter-optin-box' ),
					strtolower( $label )
				),
				'options'         => $prepared_options,
				'show_by_default' => true,
			);

			$filters[ $field_not ] = array(
				'label'       => sprintf(
					// translators: %s is the filter label, e.g, "Tags".
					__( '%s - Exclude', 'newsletter-optin-box' ),
					$label
				),
				'el'          => 'select',
				'multiple'    => true,
				'description' => sprintf(
					// translators: %s is the filter label, e.g, "Tags".
					__( 'Exclude %s.', 'newsletter-optin-box' ),
					strtolower( $label )
				),
				'options'     => $prepared_options,
			);
		}

		return $filters;
	}

	/**
	 * Prepares a taxonomy query filter.
	 *
	 * @param array $filters The filters.
	 */
	protected function prepare_tax_query_filter( $filters ) {
		$tax_query = array();

		foreach ( $filters as $key => $value ) {

			// Tax in.
			if ( 0 === strpos( $key, 'tax_in_' ) ) {
				if ( ! empty( $value ) ) {
					$tax_query[] = array(
						'taxonomy' => str_replace( 'tax_in_', '', $key ),
						'terms'    => wp_parse_id_list( $value ),
					);
				}

				unset( $filters[ $key ] );
				continue;
			}

			// Tax not in.
			if ( 0 === strpos( $key, 'tax_not_in_' ) ) {
				if ( ! empty( $value ) ) {
					$tax_query[] = array(
						'taxonomy' => str_replace( 'tax_not_in_', '', $key ),
						'terms'    => wp_parse_id_list( $value ),
						'operator' => 'NOT IN',
					);
				}

				unset( $filters[ $key ] );
				continue;
			}
		}

		if ( ! empty( $tax_query ) ) {
			$filters['tax_query'] = $tax_query;
		}

		return $filters;
	}

	/**
	 * Handles the list shortcode.
	 *
	 * @param array $atts The shortcode attributes.
	 * @return string $template The shortcode HTML.
	 */
	public function handle_list_shortcode( $atts, $template ) {

		if ( ! noptin_has_active_license_key() ) {
			return $template;
		}

		$atts = shortcode_atts(
			array(
				'query'       => 'number=10&order=desc&orderby=date',
				'number'      => 3, // Only used when merge_tag is set.
				'columns'     => 1,
				'skiponempty' => 'no',
				'responsive'  => 'yes',
				'merge_tag'   => '',
			),
			$atts,
			'noptin_' . $this->plural_type() . '_list'
		);

		if ( ! empty( $atts['merge_tag'] ) ) {
			$tag   = '[[' . $atts['merge_tag'] . ' number="' . $atts['number'] . '" return=ids]]';
			$items = noptin_parse_email_subject_tags( $tag );

			if ( $tag !== $items ) {
				$items = wp_parse_id_list( $items );
			}
		} else {
			parse_str( rawurldecode( html_entity_decode( $atts['query'] ) ), $query );

			// loop query and convert booleans.
			foreach ( $query as $key => $value ) {
				if ( 'true' === $value ) {
					$query[ $key ] = true;
				} elseif ( 'false' === $value ) {
					$query[ $key ] = false;
				}
			}

			$items = $this->get_all( $query );
		}

		// Debug the query later.
		if ( defined( 'NOPTIN_IS_TESTING' ) && NOPTIN_IS_TESTING ) {
			noptin_error_log( $items, 'Item collection results' );
		}

		if ( ! is_array( $items ) || empty( $items ) ) {
			if ( 'yes' === $atts['skiponempty'] ) {
				$GLOBALS['noptin_email_force_skip'] = array(
					'message' => sprintf(
						// translators: %s is the object type label, e.g. "orders".
						__( 'No %s found.', 'newsletter-optin-box' ),
						strtolower( $this->label )
					),
				);
			}

			return '';
		}

		$post                 = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
		$tags                 = new Tags( $this->type );
		$columns              = absint( $atts['columns'] ) ? absint( $atts['columns'] ) : 1;
		$cols                 = array_fill( 0, $columns, array() );
		$width                = round( 100 / $columns, 10 );
		$provided_tags        = array();
		$provided_collections = array();

		foreach ( $this->provides as $provided_type ) {
			$provided_tags[ $provided_type ]        = new Tags( $provided_type );
			$provided_collections[ $provided_type ] = Store::get( $provided_type );
		}

		// Loop through the items and add them to the appropriate columns.
		foreach ( $items as $index => $item ) {
			$cols[ $index % $columns ][] = $item;
		}

		$wrapper_class = 'noptin-records__wrapper noptin-' . sanitize_html_class( $this->plural_type() ) . '__wrapper';

		if ( $columns > 1 ) {
			$wrapper_class .= ' noptin-columns noptin-columns__' . absint( $columns );

			if ( 'yes' === $atts['responsive'] ) {
				$wrapper_class .= ' noptin-is-stacked-on-mobile';
			}
		}

		$html = '<div class="' . esc_attr( $wrapper_class ) . '">';

		// Render each column.
		$column_class = 'noptin-records__column noptin-' . sanitize_html_class( $this->plural_type() ) . '__column';

		if ( $columns > 1 ) {
			$column_class .= ' noptin-column';

			if ( 'yes' === $atts['responsive'] ) {
				$column_class .= ' noptin-is-stacked-on-mobile';
			}
		}

		foreach ( $cols as $column_items ) {
			$html .= '<div class="' . esc_attr( $column_class ) . '" style="width: ' . esc_attr( $width ) . '%;">';

			if ( empty( $column_items ) ) {
				$html .= '&nbsp;';
			}

			// Render each item.
			foreach ( $column_items as $item ) {
				// Prepare item.
				$this->prepare_item( $item );

				// Generate template.
				$html .= $tags->replace_record_fields( $this->current_item, $template );

				// Replace related fields.
				foreach ( $provided_collections as $collection_type => $collection ) {
					if ( empty( $collection ) ) {
						continue;
					}

					$provided_id = (int) $this->current_item->provide( $collection_type );
					$collection->prepare_item( $provided_id );
					$html = $provided_tags[ $collection_type ]->replace_record_fields( $collection->current_item, $html );
					$collection->cleanup_item( $provided_id );
				}

				// Cleanup item.
				$this->cleanup_item( $item );
			}

			$html .= '</div>';
		}

		$html .= '</div>';

		// Restore post.
		if ( 'post_type' === $this->object_type ) {
			if ( ! empty( $post ) ) {
				$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				setup_postdata( $post );
			} else {
				wp_reset_postdata();
			}
		}

		return $html;
	}

	/**
	 * Prepares a single item.
	 *
	 * @param int $item The item.
	 */
	public function prepare_item( $item ) {
		$this->current_item = $this->get( $item );
	}

	/**
	 * Cleans up after a single item.
	 *
	 * @param Record|null $previous_item The item.
	 */
	public function cleanup_item( $previous_item ) {
		$this->current_item = $previous_item;
	}

	protected function meta_key_tag_config() {
		return array(
			'label'          => __( 'Meta Value', 'newsletter-optin-box' ),
			'type'           => 'string',
			'example'        => 'key="my_key"',
			'skip_smart_tag' => true,
			'block'          => array(
				'title'       => sprintf(
					/* translators: %s: object type label */
					__( '%s Meta', 'newsletter-optin-box' ),
					$this->singular_label
				),
				'description' => __( 'Displays a custom field value.', 'newsletter-optin-box' ),
				'icon'        => 'ellipsis',
				'metadata'    => array(
					'ancestor' => array( $this->context ),
				),
				'element'     => 'div',
				'settings'    => array(
					'key'     => array(
						'label'       => __( 'Meta Key / Field Key', 'newsletter-optin-box' ),
						'el'          => 'input',
						'type'        => 'text',
						'description' => __( 'The meta key or field key to display.', 'newsletter-optin-box' ),
					),
					'default' => array(
						'label'       => __( 'Default Value', 'newsletter-optin-box' ),
						'el'          => 'input',
						'type'        => 'text',
						'description' => __( 'The default value to display if not set.', 'newsletter-optin-box' ),
					),
				),
			),
		);
	}

	/**
	 * Registers temporary merge tags.
	 *
	 */
	public function register_temporary_merge_tags() {
		global $noptin_current_objects;

		$recipient = \Hizzle\Noptin\Emails\Main::$current_email_recipient;

		if ( isset( $recipient[ $this->type ] ) ) {
			if ( ! is_array( $noptin_current_objects ) ) {
				$noptin_current_objects = array();
			}

			$noptin_current_objects[ $this->type ] = $this->get( $recipient[ $this->type ] );

			foreach ( Store::smart_tags( $this->type, true ) as $tag => $config ) {
				noptin()->emails->tags->add_tag( $tag, $config );
			}
		}
	}

	/**
	 * Unregisters temporary merge tags.
	 *
	 */
	public function unregister_temporary_merge_tags() {
		global $noptin_current_objects;

		$recipient = \Hizzle\Noptin\Emails\Main::$current_email_recipient;
		if ( is_array( $noptin_current_objects ) && isset( $recipient[ $this->type ] ) ) {
			unset( $noptin_current_objects[ $this->type ] );
			noptin()->emails->tags->remove_tag( array_keys( Store::smart_tags( $this->type, true ) ) );
		}
	}

	/**
	 * Returns the current instance.
	 */
	public function get_instance() {
		return $this;
	}
}
