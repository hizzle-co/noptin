<?php
/**
 * Forms API: Renderer.
 *
 * Renders forms on the front page.
 *
 * @since             1.6.2
 * @package           Noptin
 */

namespace Hizzle\Noptin\Forms;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Renders forms on the front page.
 *
 * @since 1.6.2
 */
class Renderer {

	/**
	 * @var string
	 */
	public static $shortcode = 'noptin';

	/**
	 * @var array
	 */
	public static $shortcode_atts = array();

	/**
	 * Constructor.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
	}

	/**
	 * Registers the [noptin] and [noptin-form] shortcodes
	 */
	public static function register_shortcodes() {
		add_shortcode( self::$shortcode, array( __CLASS__, 'shortcode' ) );
		add_shortcode( 'noptin-form', array( __CLASS__, 'legacy_shortcode' ) );
	}

	/**
	 * Renders the [noptin-form] shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function legacy_shortcode( $atts ) {
		if ( empty( $atts['id'] ) ) {
			return '';
		}

		$atts['form'] = $atts['id'];
		unset( $atts['id'] );

		// Render the form.
		return self::shortcode( $atts );
	}

	/**
	 * Renders the [noptin] shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		ob_start();
		self::display_form( $atts );
		return ob_get_clean();
	}

	/**
	 * Renders a form.
	 *
	 * @param array $atts The atts with which to display the opt-in form.
	 */
	public static function display_form( $atts = array() ) {
		add_filter( 'noptin_load_form_scripts', '__return_true' );
		\Hizzle\Noptin\Forms\Main::enqueue_scripts();

		// Reset shortcode attributes.
		self::$shortcode_atts = array();

		// Ensure atts is an array.
		if ( ! is_array( $atts ) ) {
			$atts = array();
		}

		// Backwards compatibility.
		if ( isset( $atts['success_msg'] ) ) {
			$atts['success'] = $atts['success_msg'];
			unset( $atts['success_msg'] );
		}

		// Blocks.
		if ( ! empty( $atts['className'] ) ) {
			$atts['html_class'] = isset( $atts['html_class'] ) ? $atts['html_class'] . ' ' . $atts['className'] : $atts['className'];
			unset( $atts['className'] );
		}

		// If form === -1, we render the default form with all fields on a single line.
		if ( isset( $atts['form'] ) && -1 === (int) $atts['form'] ) {
			unset( $atts['form'] );
			$atts = array_merge(
				array(
					'template' => 'condensed',
					'labels'   => 'hide',
				),
				$atts
			);
		}

		// Are we trying to display a saved form?
		$form   = false;
		$config = $atts;
		if ( isset( $atts['form'] ) && ! empty( $atts['form'] ) ) {

			// Maybe display a translated version.
			$atts['form'] = translate_noptin_form_id( (int) $atts['form'] );

			$form = noptin_get_optin_form( (int) $atts['form'] );

			// Make sure that the form is visible.
			if ( ! $form->can_show() ) {
				return;
			}

			// Set a flag if we're displaying a popup or slide-in.
			if ( $form->is_popup() || $form->is_slide_in() ) {
				$GLOBALS['noptin_showing_popup'] = true;
			}

			// Update view count.
			if ( ! noptin_is_preview() && ! $form->is_popup() && ! $form->is_slide_in() ) {
				increment_noptin_form_views( $form->id );
			}

			// Use the form id as the subscriber source.
			$atts['source'] = (int) $atts['form'];

			// Merge form settings with passed attributes.
			if ( ! is_legacy_noptin_form( (int) $atts['form'] ) ) {
				$atts = array_merge( $form->settings, $atts );
			} else {
				$atts = array_merge(
					array(
						'fields'     => $form->fields,
						'redirect'   => $form->redirect,
						'labels'     => empty( $form->showLabels ) ? 'hide' : 'show',
						'acceptance' => $form->gdprCheckbox ? $form->gdprConsentText : '',
						'submit'     => $form->noptinButtonLabel,
					),
					$atts
				);
			}
		}

		// Prepare default attributes.
		$default_atts = self::get_default_shortcode_atts();

		if ( ! empty( $atts['is_unsubscribe'] ) ) {
			$default_atts['submit'] = __( 'Unsubscribe', 'newsletter-optin-box' );
		}

		$default_atts = apply_filters( 'default_noptin_shortcode_atts', $default_atts, $atts );
		$atts         = shortcode_atts( $default_atts, $atts, self::$shortcode );

		$atts['noptin-config'] = $config;

		return self::render_form( $atts, $form );
	}

	/**
	 * Returns the default `[noptin]` shortcode attributes.
	 *
	 * @since 1.6.2
	 * @return array
	 */
	private static function get_default_shortcode_atts() {

		$atts = array(
			'fields'         => 'email', // Comma separated array of fields, or all
			'source'         => 'shortcode', // Source of the subscriber.
			'labels'         => 'show', // Whether or not to show the field label.
			'wrap'           => 'div', // Which element to wrap field values in.
			'styles'         => 'basic', // Set to inherit to inherit theme styles.
			'before_fields'  => '', // Content to display before form fields.
			'after_fields'   => '', // Content to display after form fields.
			'html_id'        => '', // ID of the form (auto-generated if not provided).
			'html_name'      => '', // HTML name of the form.
			'html_class'     => '', // HTML class of the form.
			'redirect'       => '', // An optional URL to redirect users after successful subscriptions.
			'acceptance'     => '', // Optional terms of service text.
			'submit'         => __( 'Subscribe', 'newsletter-optin-box' ),
			'template'       => 'normal',
			'is_unsubscribe' => '',
		);

		foreach ( array_keys( get_default_noptin_form_messages() ) as $msg ) {
			$atts[ $msg ] = '';
		}

		foreach ( get_noptin_custom_fields( true ) as $field ) {
			$atts[ $field['merge_tag'] . '_label' ]       = '';
			$atts[ $field['merge_tag'] . '_placeholder' ] = '';
		}

		return $atts;
	}

	/**
	 * Displays an optin form based on the passed args.
	 *
	 * @param array $args The args with which to display the opt-in form.
	 * @param \Noptin_Form|\Noptin_Form_Legacy|false $form The form to display.
	 *
	 * @return string
	 */
	protected static function render_form( $args = array(), $form = false ) {

		// Increment count.
		$count = wp_unique_id();

		// Maybe force a form id.
		$args['html_id'] = empty( $args['html_id'] ) ? 'noptin-form-' . absint( $count ) : $args['html_id'];

		// (Maybe) cache this instance.
		if ( empty( $form ) ) {
			$args['noptin-config'] = array_filter( $args );
		}

		// Run before output hook.
		do_action( 'before_output_noptin_form', $args );

		// Display the opening comment.
		echo '<!-- Noptin Newsletter Plugin v' . esc_html( noptin()->version ) . ' - https://wordpress.org/plugins/newsletter-optin-box/ -->';

		do_action( 'noptin_form_wrapper', $form, $args );

		// Opening wrapper.
		if ( $form && is_legacy_noptin_form( $form->id ) ) {
			self::before_display( $form, $args );
		}

		// Display the opening form tag.
		echo '<form ';

		noptin_attr(
			'form',
			array(
				'id'         => $args['html_id'],
				'class'      => self::get_css_classes( $args, $form ),
				'name'       => empty( $args['html_name'] ) ? false : $args['html_name'],
				'method'     => 'post',
				'novalidate' => true,
			),
			$args
		);

		echo '>';

		// Display additional content before form fields.
		self::before_fields( $args, $form );

		// Display form fields.
		self::display_fields( $args, $form );

		// Display standard fields.
		noptin_hidden_field( 'noptin_element_id', $count );
		noptin_hidden_field( 'source', $args['source'] );
		noptin_hidden_field( 'form_action', empty( $args['is_unsubscribe'] ) ? 'subscribe' : 'unsubscribe' );

		if ( ! empty( $args['noptin-config'] ) ) {
			noptin_hidden_field( 'noptin-config', noptin_encrypt( wp_json_encode( $args['noptin-config'] ) ) );
		}

		// Form id.
		if ( $form ) {
			noptin_hidden_field( 'noptin_form_id', $form->id );
		}

		echo '</form>';

		// Closing wrapper.
		if ( $form && is_legacy_noptin_form( $form->id ) ) {
			self::after_display( $form, $args );
		}

		echo '<!-- / Noptin Newsletter Plugin -->';
	}

	/**
	 * Get a space separated list of CSS classes for this form
	 *
	 * @param array $args The args with which to display the opt-in form.
	 * @param \Noptin_Form|\Noptin_Form_Legacy|false $form The form to display.
	 * @return string[]
	 */
	protected static function get_css_classes( $args, $form ) {

		if ( $form && is_legacy_noptin_form( $form->id ) ) {
			return array(
				'noptin-optin-form',
				$form->singleLine ? 'noptin-form-single-line' : 'noptin-form-new-line',
				'noptin-label-' . sanitize_html_class( $args['labels'] ),
			);
		}

		// Base classes.
		$classes = array(
			'noptin-newsletter-form',
			'noptin-form',
			$args['html_id'],
			! empty( $args['source'] ) ? 'noptin-form-source-' . sanitize_html_class( $args['source'] ) : '',
		);

		// Labels ( hidden / top / side ).
		if ( isset( $args['labels'] ) ) {
			$classes[] = 'noptin-label-' . sanitize_html_class( $args['labels'] );
		}

		// Styles ( none / basic / full ).
		if ( isset( $args['styles'] ) ) {
			$classes[] = 'noptin-styles-' . sanitize_html_class( $args['styles'] );
		}

		// Template.
		if ( isset( $args['template'] ) ) {
			$classes[] = 'noptin-template-' . sanitize_html_class( $args['template'] );

			if ( 'condensed' === $args['template'] ) {
				$classes[] = 'noptin-form-single-line';
			}
		}

		// Add classes from args.
		if ( ! empty( $args['html_class'] ) ) {
			$classes = array_merge( $classes, noptin_parse_list( $args['html_class'] ) );
		}

		return apply_filters( 'noptin_form_css_classes', $classes, $form );
	}

	private static function get_typography_css( $color, $typography ) {
		$css = '';

		if ( ! empty( $color ) ) {
			$css .= 'color: ' . $color . ';';
		}

		if ( ! empty( $typography['generated'] ) ) {
			$css .= $typography['generated'];
		}

		return $css;
	}

	/**
	 * Displays additional content before form fields.
	 *
	 * @param array $args The args with which to display the opt-in form.
	 * @param \Noptin_Form|\Noptin_Form_Legacy|false $form The form to display.
	 */
	protected static function before_fields( $args, $form ) {
		if ( isset( $args['before_fields'] ) && ! empty( $args['before_fields'] ) ) {
			echo wp_kses_post( do_shortcode( $args['before_fields'] ) );
		}

		if ( $form && is_legacy_noptin_form( $form->id ) ) {
			$show_header_text = ( ! $form->hidePrefix && ! empty( $form->prefix ) ) || ( ! $form->hideTitle && ! empty( $form->title ) ) || ( ! $form->hideDescription && ! empty( $form->description ) );
			$show_header      = ! empty( $form->image ) || $show_header_text;

			if ( $show_header ) {
				?>
					<div class="noptin-form-header <?php echo ! empty( $form->image ) ? esc_attr( "noptin-img-{$form->imagePos}" ) : 'no-image'; ?>">
						<?php if ( $show_header_text ) : ?>
						<div class="noptin-form-header-text">

							<?php if ( ! $form->hidePrefix ) : ?>
								<div style="<?php echo esc_attr( self::get_typography_css( $form->prefixColor, $form->prefixTypography ) ); ?>" class="noptin-form-prefix"><?php echo wp_kses_post( do_shortcode( $form->prefix ) ); ?></div>
							<?php endif; ?>

							<?php if ( ! $form->hideTitle ) : ?>
								<div style="<?php echo esc_attr( self::get_typography_css( $form->titleColor, $form->titleTypography ) ); ?>" class="noptin-form-heading"><?php echo wp_kses_post( do_shortcode( $form->title ) ); ?></div>
							<?php endif; ?>

							<?php if ( ! $form->hideDescription ) : ?>
								<div style="<?php echo esc_attr( self::get_typography_css( $form->descriptionColor, $form->descriptionTypography ) ); ?>" class="noptin-form-description"><?php echo wp_kses_post( do_shortcode( $form->description ) ); ?></div>
							<?php endif; ?>

						</div>
						<?php endif; ?>

						<?php if ( ! empty( $form->image ) ) : ?>
							<div class="noptin-form-header-image">
								<img alt="icon" src="<?php echo esc_url( $form->image ); ?>" />
							</div>
						<?php endif; ?>

					</div>
				<?php
			}
		}
	}

	/**
	 * Displays form fields.
	 *
	 * @param array $args The args with which to display the opt-in form.
	 * @param \Noptin_Form|\Noptin_Form_Legacy|false $form The form to display.
	 */
	protected static function display_fields( $args, $form ) {
		// Prepare form fields.
		$fields         = empty( $args['fields'] ) ? 'email' : $args['fields'];
		$fields         = prepare_noptin_form_fields( $fields );
		$wrap           = empty( $args['wrap'] ) ? 'p' : sanitize_html_class( $args['wrap'] );
		$is_legacy_form = $form && is_legacy_noptin_form( $form->id );
		$hide_fields    = $is_legacy_form && ! empty( $form->hideFields );
		$is_single_line = $is_legacy_form && $form->singleLine;

		// Change field labels.
		foreach ( $fields as $key => $field ) {
			$merge_tag = $field['merge_tag'];

			// Label.
			if ( ! empty( $args[ $merge_tag . '_label' ] ) ) {
				$fields[ $key ]['label'] = $args[ $merge_tag . '_label' ];
			}

			// Placeholder.
			if ( ! empty( $args[ $merge_tag . '_placeholder' ] ) ) {
				$fields[ $key ]['placeholder'] = $args[ $merge_tag . '_placeholder' ];
			}
		}

		if ( $form && is_legacy_noptin_form( $form->id ) ) {
			echo '<div class="noptin-form-footer">';
		}

		if ( ! $hide_fields ) {
			echo '<div class="noptin-form-fields">';

			do_action( 'before_display_noptin_form_fields', $fields, $args, $form );

			// For each form field...
			foreach ( $fields as $custom_field ) {

				// Wrap the HTML name field into noptin_fields[ $merge_tag ];
				$custom_field['wrap_name'] = true;

				// Set matching id.
				$custom_field['id'] = sanitize_html_class( $args['html_id'] . '__field-' . $custom_field['merge_tag'] );

				do_action( 'before_output_noptin_form_field', $custom_field, $args, $form );

				// Display the opening wrapper.
				self::display_opening_wrapper( $custom_field['merge_tag'], $wrap, $custom_field, $form );

				// Display the actual form field.
				if ( $form && is_legacy_noptin_form( $form->id ) && ! empty( $custom_field['type'] ) ) {
					printf( '<div class="noptin-field-%s">', esc_attr( $custom_field['type'] ) );
				}

				display_noptin_custom_field_input( $custom_field );

				if ( $form && is_legacy_noptin_form( $form->id ) && ! empty( $custom_field['type'] ) ) {
					echo '</div>';
				}

				// Display the closing wrapper.
				self::display_closing_wrapper( $custom_field, $wrap, $custom_field, $form );

				do_action( 'output_noptin_form_field', $custom_field, $args, $form );
			}

			// (Maybe) display an acceptance field.
			if ( ! $is_single_line ) {
				self::display_consent_field( $args, $form, $wrap );
			}

			self::display_submit_button( $wrap, $args, $form );

			do_action( 'after_display_noptin_form_fields', $fields, $args, $form );

			echo '</div>';

			// (Maybe) display an acceptance field.
			if ( $is_single_line ) {
				self::display_consent_field( $args, $form, $wrap );
			}
		}

		if ( $form && is_legacy_noptin_form( $form->id ) ) {
			?>
			<?php if ( ! $form->hideNote && ! empty( $form->note ) ) : ?>
				<div style="<?php echo esc_attr( self::get_typography_css( $form->noteColor, $form->noteTypography ) ); ?>" class="noptin-form-note"><?php echo wp_kses_post( do_shortcode( $form->note ) ); ?></div>
			<?php endif; ?>
			<div class="noptin-form-notice noptin-response" role="alert"></div>
			</div>
			<?php
		} else {
			echo '<div class="noptin-form-notice noptin-response" role="alert"></div>';
		}
	}

	/**
	 * Prints the opening wrapper.
	 *
	 * @param string $field_key Field key
	 * @param string $wrap The element to wrap the field in.
	 * @param array $extra_args Extra args parsed to hooks.
	 * @param \Noptin_Form|\Noptin_Form_Legacy|false $form The form to display.
	 */
	protected static function display_opening_wrapper( $field_key, $wrap, $extra_args = array(), $form = false ) {

		$args = array(
			'class' => 'noptin-form-field-wrapper noptin-form-field-' . sanitize_html_class( $field_key ),
		);

		if ( $form && is_legacy_noptin_form( $form->id ) ) {
			$args['class'] .= ' noptin-optin-field-wrapper noptin-optin-field-' . sanitize_html_class( $field_key );
		}

		if ( isset( $extra_args['id'] ) ) {
			$args['id'] = sanitize_html_class( $extra_args['id'] . '--wrapper' );
		}

		do_action( 'before_output_opening_noptin_form_field_wrapper', $field_key, $extra_args, $wrap, $form );

		?>
			<<?php echo esc_html( $wrap ); ?> <?php noptin_attr( 'form_field_wrapper', $args, $extra_args ); ?>>
		<?php

		do_action( 'after_output_opening_noptin_form_field_wrapper', $field_key, $extra_args, $wrap, $form );
	}

	/**
	 * Prints the closing wrapper.
	 *
	 * @param string $field_key Field key
	 * @param array $extra_args Extra args parsed to hooks.
	 */
	protected static function display_closing_wrapper( $field_key, $wrap, $extra_args = array(), $form = false ) {
		do_action( 'before_output_closing_noptin_form_field_wrapper', $field_key, $extra_args, $wrap, $form );
		echo '</' . esc_html( sanitize_html_class( $wrap ) ) . '>';
		do_action( 'after_output_closing_noptin_form_field_wrapper', $field_key, $extra_args, $wrap, $form );
	}

	/**
	 * Displays the consent field.
	 *
	 * @param array $args The args with which to display the opt-in form.
	 * @param \Noptin_Form|\Noptin_Form_Legacy|false $form The form to display.
	 */
	protected static function display_consent_field( $args, $form, $wrap ) {
		do_action( 'before_display_consent_field', $args, $form, $wrap );

		if ( '' === trim( $args['acceptance'] ) ) {
			return;
		}

		// Display the opening wrapper.
		self::display_opening_wrapper( 'consent', $wrap, array(), $form );

		?>

		<label>
			<input
				name="GDPR_consent"
				type='checkbox'
				value='1'
				class='noptin-checkbox-form-field noptin-gdpr-checkbox-wrapper'
				required="required"
			/><span><?php echo wp_kses_post( trim( $args['acceptance'] ) ); ?></span>
		</label>
		<?php
		// Display the closing wrapper.
		self::display_closing_wrapper( 'consent', $wrap, array(), $form );
	}

	/**
	 * Displays the submit button.
	 *
	 * @param string $wrap The element to wrap the field in.
	 * @param array $args The args with which to display the opt-in form.
	 * @param \Noptin_Form|\Noptin_Form_Legacy|false $form The form to display.
	 */
	protected static function display_submit_button( $wrap, $args, $form ) {
		do_action( 'before_output_noptin_form_submit_button', $form );

		// Opening wrapper.
		self::display_opening_wrapper( 'submit', $wrap, array(), $form );

		// Print the submit button.
		$button_atts = array(
			'type'  => 'submit',
			'id'    => sanitize_html_class( $args['html_id'] . '__submit' ),
			'class' => 'noptin-form-submit btn button btn-primary button-primary wp-element-button',
			'name'  => 'noptin-submit',
			'value' => empty( $args['submit'] ) ? __( 'Subscribe', 'newsletter-optin-box' ) : $args['submit'],
		);

		if ( $form && is_legacy_noptin_form( $form->id ) ) {
			$button_atts['style'] = '';

			if ( ! empty( $form->noptinButtonBg ) ) {
				$button_atts['style'] .= 'background-color: ' . $form->noptinButtonBg . ';';
			}

			if ( ! empty( $form->noptinButtonColor ) ) {
				$button_atts['style'] .= 'color: ' . $form->noptinButtonColor . ';';
			}

			if ( empty( $form->singleLine ) ) {
				$button_atts['class'] .= ' noptin-form-button-' . $form->buttonPosition;
			}
		}

		?>

			<input <?php noptin_attr( 'form_submit', $button_atts, $args ); ?> />

		<?php

		// Closing wrapper.
		self::display_closing_wrapper( 'submit', $wrap, array(), $form );

		do_action( 'output_noptin_form_submit_button', $form );
	}

	/**
	 * Renders the opening wrapper for a form.
	 *
	 * @param array $args The args with which to display the opt-in form.
	 * @param \Noptin_Form|\Noptin_Form_Legacy $form The form to display.
	 */
	private static function before_display( $form, $args = array() ) {

		$border = $form->formBorder;
		$colors = array(
			'background'  => $form->noptinFormBg,
			'border'      => empty( $border ) || empty( $border['border_color'] ) ? false : $border['border_color'],
			'button'      => $form->noptinButtonBg,
			'button-text' => $form->noptinButtonColor,
			'title'       => $form->titleColor,
			'description' => $form->descriptionColor,
			'prefix'      => $form->prefixColor,
			'note'        => $form->noteColor,
		);

		$attr = '';

		foreach ( $colors as $key => $color ) {
			if ( ! empty( $color ) ) {
				$attr .= " --noptin-$key-color: $color;";
			}
		}

		// Display the opening div tag.
		echo '<div ';

		noptin_attr(
			'noptin-optin-main-wrapper',
			array(
				'id'                   => sprintf( '%s__wrapper', $args['html_id'] ),
				'class'                => array(
					'noptin-optin-main-wrapper',
					sprintf( 'noptin-form-id-%d', $form->id ),
					sprintf( 'noptin-%s-main-wrapper', $form->optinType ),
				),
				'aria-hidden'          => $form->is_slide_in() || $form->is_popup() ? 'true' : false,
				'tabindex'             => $form->is_slide_in() || $form->is_popup() ? '-1' : false,
				'data-slide-direction' => $form->is_slide_in() ? $form->slideDirection : false,
				'aria-labelledby'      => sprintf( '%s__title', $args['html_id'] ),
				'style'                => $attr,
			),
			$args
		);

		echo '>';

		if ( $form->is_popup() ) {
			echo '<div class="noptin-popup__overlay" data-a11y-dialog-hide></div>';
			echo '<div class="noptin-popup__container">';
		}

		if ( ! empty( $form->CSS ) ) {
			$id_class   = sprintf( 'noptin-form-id-%d', $form->id );
			$type_class = sprintf( 'noptin-%s-main-wrapper', $form->optinType );
			printf(
				'<style>%s</style>',
				wp_strip_all_tags( // phpcs:ignore
					str_ireplace(
						'.noptin-optin-form-wrapper',
						sprintf( '.%s .noptin-optin-form-wrapper', $id_class ),
						str_ireplace(
							".$type_class",
							".$type_class.$id_class",
							$form->CSS
						)
					)
				)
			);
		}

		$styles = array(
			'background-image' => "url('$form->noptinFormBgImg')",
			'max-width'        => $form->formWidth,
			'min-height'       => $form->formHeight,
		);

		if ( is_numeric( $styles['max-width'] ) ) {
			$styles['max-width'] = $styles['max-width'] . 'px';
		}

		if ( empty( $styles['max-width'] ) ) {
			$styles['max-width'] = '100%';
		}

		if ( is_numeric( $styles['min-height'] ) ) {
			$styles['min-height'] = $styles['min-height'] . 'px';
		}

		if ( empty( $form->noptinFormBgImg ) ) {
			unset( $styles['background-image'] );
		}

		$wrapper_styles = '';
		foreach ( $styles as $prop => $val ) {
			$val             = esc_attr( $val );
			$wrapper_styles .= " $prop:$val;";
		}

		foreach ( noptin_parse_list( 'formBorder prefixAdvanced prefixTypography noteAdvanced noteTypography descriptionAdvanced descriptionTypography titleAdvanced titleTypography noteAdvanced noteTypography' ) as $_autogenerated_prop ) {
			if ( empty( ${$_autogenerated_prop}['generated'] ) ) {
				${$_autogenerated_prop}['generated'] = '';
			}
		}

		$wrapper_styles .= $form->formBorder['generated'];

		$atts = array(
			'style' => $wrapper_styles,
			'class' => array(
				'noptin-optin-form-wrapper',
				$form->imageMain ? "noptin-img-{$form->imageMainPos}" : 'no-image',
			),
		);

		if ( $form->is_slide_in() || $form->is_popup() ) {
			$trigger                   = defined( 'IS_NOPTIN_PREVIEW' ) ? 'immeadiate' : esc_attr( $form->triggerPopup );
			$atts['data-trigger']      = $trigger;
			$atts['data-hide-seconds'] = apply_filters( 'noptin_display_form_every_x_seconds', WEEK_IN_SECONDS, $form->hideSeconds ) * 1000;

			if ( 'after_click' === $trigger ) {
				$atts['data-value'] = $form->cssClassOfClick;
			}

			if ( 'on_scroll' === $trigger ) {
				$atts['data-value'] = $form->scrollDepthPercentage;
			}

			if ( 'after_delay' === $trigger ) {
				$atts['data-value'] = $form->timeDelayDuration;
			}
		}

		if ( $form->is_slide_in() ) {
			$atts['class'][] = "noptin-slide-from-$form->slideDirection";
		}

		echo '<div ';
		noptin_attr( 'noptin-optin-form-wrapper', $atts, $args );
		echo '><!-- Form ID: ' . esc_attr( $form->id ) . ' -->';
	}

	/**
	 * Renders the closing wrapper for a form.
	 *
	 * @param array $args The args with which to display the opt-in form.
	 * @param \Noptin_Form|\Noptin_Form_Legacy $form The form to display.
	 */
	private static function after_display( $form, $args = array() ) {
		if ( ! empty( $form->imageMain ) ) {
			?>
			<div class="noptin-form-main-image">
				<img alt="opt-in image" src="<?php echo esc_url( $form->imageMain ); ?>" />
			</div>
			<?php
		}

		if ( $form->is_slide_in() || $form->is_popup() ) {
			?>
				<button
					class="noptin-popup__close"
					type="button"
					data-a11y-dialog-hide
					aria-label="Close dialog"
					aria-controls="<?php echo esc_attr( $args['html_id'] ); ?>__wrapper"
				>
					<span aria-hidden="true">&times;</span>
				</button>
			<?php
		}

		echo '</div><!-- /Form ID: ' . esc_attr( $form->id ) . ' -->';

		if ( $form->is_popup() ) {
			echo '</div>';
		}
		echo '</div>';
	}
}
