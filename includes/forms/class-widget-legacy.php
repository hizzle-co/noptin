<?php
/**
 * Adds a newsletter optin widget
 *
 * Simple WordPress optin form
 *
 * @since             1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Widget class
 *
 * @since       1.0.0
 */
class Noptin_Widget extends WP_Widget {

	/**
	 * Available colors.
	 *
	 * @var array
	 */
	public $colors = array();

	/**
	 * Class constructor.
	 */
	public function __construct() {

		$this->colors = array(
			'transparent' => __( 'Inherit From Theme', 'newsletter-optin-box' ),
			'#e51c23'     => __( 'Red', 'newsletter-optin-box' ),
			'#e91e63'     => __( 'Pink', 'newsletter-optin-box' ),
			'#9c27b0'     => __( 'Purple', 'newsletter-optin-box' ),
			'#673ab7'     => __( 'Deep Purple', 'newsletter-optin-box' ),
			'#3f51b5'     => __( 'Indigo', 'newsletter-optin-box' ),
			'#2196F3'     => __( 'Blue', 'newsletter-optin-box' ),
			'#03a9f4'     => __( 'Light Blue', 'newsletter-optin-box' ),
			'#00bcd4'     => __( 'Cyan', 'newsletter-optin-box' ),
			'#009688'     => __( 'Teal', 'newsletter-optin-box' ),
			'#4CAF50'     => __( 'Green', 'newsletter-optin-box' ),
			'#8bc34a'     => __( 'Light Green', 'newsletter-optin-box' ),
			'#cddc39'     => __( 'Lime', 'newsletter-optin-box' ),
			'#ffeb3b'     => __( 'Yellow', 'newsletter-optin-box' ),
			'#ffc107'     => __( 'Amber', 'newsletter-optin-box' ),
			'#ff9800'     => __( 'Orange', 'newsletter-optin-box' ),
			'#ff5722'     => __( 'Deep Orange', 'newsletter-optin-box' ),
			'#795548'     => __( 'Brown', 'newsletter-optin-box' ),
			'#607d8b'     => __( 'Blue Grey', 'newsletter-optin-box' ),
			'#313131'     => __( 'Black', 'newsletter-optin-box' ),
			'#fff'        => __( 'White', 'newsletter-optin-box' ),
			'#aaa'        => __( 'Grey', 'newsletter-optin-box' ),
		);

		$widget_ops = array(
			'classname'   => 'noptin_widget',
			'description' => __( 'Use this widget to create and add a simple newsletter subscription widget', 'newsletter-optin-box' ),
		);
		parent::__construct( 'noptin_widget', 'Noptin New Form', $widget_ops );
	}

	/**
	 * Outputs the widget content on the front-end.
	 *
	 * @param string $args     The widget args to use.
	 * @param string $instance The instance args to use.
	 */
	public function widget( $args, $instance ) {

		if ( ! noptin_should_show_optins() ) {
			return;
		}

		if ( ! is_array( $instance ) ) {
			$instance = array();
		}

		$instance = wp_parse_args(
			$instance,
			array(
				'bg_color' => '',
				'color'    => '',
				'h2_col'   => '',
				'btn_col'  => '',
			)
		);

		echo $args['before_widget']; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

		// ID.
		$id = '#' . empty( $args['widget_id'] ) ? uniqid( 'noptin-widget-' ) : $args['widget_id'];

		// Submit button.
		$submit = empty( $instance['submit'] ) ? __( 'Submit', 'newsletter-optin-box' ) : $instance['submit'];

		// Colors.
		$bg_color = sanitize_hex_color( $instance['bg_color'] );
		$color    = sanitize_hex_color( $instance['color'] );
		$h2_col   = sanitize_hex_color( $instance['h2_col'] );
		$btn_col  = sanitize_hex_color( $instance['btn_col'] );
		$class    = ! empty( $bg_color ) ? 'noptin-email-optin-widget-has-bg' : '';
		?>
	<style>

		<?php
		if ( $bg_color ) {
			echo esc_html( $id ) . ' .noptin-email-optin-widget { background-color: ' . esc_html( $bg_color ) . ' !important; }';
		}

		if ( $color ) {
			echo esc_html( $id ) . ' .noptin-email-optin-widget { color: ' . esc_html( $color ) . ' !important; }';
		}

		if ( $h2_col ) {
			echo esc_html( $id ) . ' .noptin-email-optin-widget .widget-title { color: ' . esc_html( $h2_col ) . ' !important; }';
		}

		if ( $btn_col ) {
			echo esc_html( $id ) . ' .noptin-email-optin-widget .noptin-widget-submit-input { background-color: ' . esc_html( $btn_col ) . ' !important; }';
		}
		?>
	</style>
	<div class="noptin-email-optin-widget <?php echo esc_attr( $class ); ?>">
		<form>

		<?php
			if ( ! empty( $instance['title'] ) ) {
				$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
				echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
			}
		?>

		<?php if ( ! empty( $instance['desc'] ) ) : ?>
			<p class="noptin-widget-desc"><?php echo esc_html( $instance['desc'] ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $instance['redirect'] ) ) : ?>
			<input class="noptin_form_redirect" name="noptin-redirect" type="hidden" value="<?php echo esc_url( $instance['redirect'] ); ?>"/>
		<?php endif; ?>

		<input class="noptin-widget-email-input noptin_form_input_email" name="email" type="email" placeholder="<?php esc_attr_e( 'Email Address', 'newsletter-optin-box' ); ?>" required >
		<?php do_action( 'before_noptin_quick_widget_submit', $args ); ?>
		<input class="noptin-widget-submit-input" value="<?php echo esc_attr( $submit ); ?>" type="submit">
		<div class="noptin_feedback_success"></div>
		<div class="noptin_feedback_error"></div>
		</form>
	</div>

		<?php
		echo $args['after_widget']; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Displays color select boxes.
	 *
	 * @param string $color the currently selected color.
	 */
	public function noptin_color_select( $color ) {
		foreach ( $this->colors as $hex => $name ) {

			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $hex ),
				selected( $color, $hex, false ),
				esc_html( $name )
			);

		}
	}

	/**
	 * Displays the widget settings field.
	 *
	 * @param array $instance current instance options.
	 */
	public function form( $instance ) {
		$title    = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'FREE NEWSLETTER', 'newsletter-optin-box' );
		$desc     = ! empty( $instance['desc'] ) ? $instance['desc'] : esc_html__( 'Subscribe to our newsletter today and be the first to know when we publish a new blog post.', 'newsletter-optin-box' );
		$submit   = ! empty( $instance['submit'] ) ? $instance['submit'] : esc_html__( 'SUBSCRIBE NOW', 'newsletter-optin-box' );
		$bg_color = ! empty( $instance['bg_color'] ) ? $instance['bg_color'] : 'transparent';
		$color    = ! empty( $instance['color'] ) ? $instance['color'] : 'transparent';
		$h2_col   = ! empty( $instance['h2_col'] ) ? $instance['h2_col'] : 'transparent';
		$btn_col  = ! empty( $instance['btn_col'] ) ? $instance['btn_col'] : 'transparent';
		$redirect = ! empty( $instance['redirect'] ) ? $instance['redirect'] : '';

		?>
	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
		<?php esc_attr_e( 'Title:', 'newsletter-optin-box' ); ?>
	</label>

	<input
		class="widefat"
		id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
		name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
		type="text"
		value="<?php echo esc_attr( $title ); ?>">
	</p>

	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'desc' ) ); ?>">
		<?php esc_attr_e( 'Description:', 'newsletter-optin-box' ); ?>
	</label>

	<input
		class="widefat"
		id="<?php echo esc_attr( $this->get_field_id( 'desc' ) ); ?>"
		name="<?php echo esc_attr( $this->get_field_name( 'desc' ) ); ?>"
		type="text"
		value="<?php echo esc_attr( $desc ); ?>">
	</p>

	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'redirect' ) ); ?>">
		<?php esc_attr_e( 'Redirect:', 'newsletter-optin-box' ); ?>
	</label>

	<input
		class="widefat"
		id="<?php echo esc_attr( $this->get_field_id( 'redirect' ) ); ?>"
		name="<?php echo esc_attr( $this->get_field_name( 'redirect' ) ); ?>"
		type="text"
		placeholder="Optional. Where should we redirect a user after they sign up?"
		value="<?php echo esc_attr( $redirect ); ?>">
	</p>

	<p>

	<label for="<?php echo esc_attr( $this->get_field_id( 'bg_color' ) ); ?>">
		<?php esc_attr_e( 'Background Color:', 'newsletter-optin-box' ); ?>
	</label>

	<select
		name="<?php echo esc_attr( $this->get_field_name( 'bg_color' ) ); ?>"
		class="widefat"
		id="<?php echo esc_attr( $this->get_field_id( 'bg_color' ) ); ?>"
		>
		<?php $this->noptin_color_select( $bg_color ); ?>
	</select>
	</p>


	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'h2_col' ) ); ?>">
		<?php esc_attr_e( 'Title Color:', 'newsletter-optin-box' ); ?>
	</label>

	<select
		name="<?php echo esc_attr( $this->get_field_name( 'h2_col' ) ); ?>"
		class="widefat"
		id="<?php echo esc_attr( $this->get_field_id( 'h2_col' ) ); ?>"
		>
		<?php $this->noptin_color_select( $h2_col ); ?>
	</select>
	</p>

	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'color' ) ); ?>">
		<?php esc_attr_e( 'Text Color:', 'newsletter-optin-box' ); ?>
	</label>
	<select
		name="<?php echo esc_attr( $this->get_field_name( 'color' ) ); ?>"
		class="widefat"
		id="<?php echo esc_attr( $this->get_field_id( 'color' ) ); ?>"
		>
		<?php $this->noptin_color_select( $color ); ?>
	</select>
	</p>

	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'btn_col' ) ); ?>">
		<?php esc_attr_e( 'Button Color:', 'newsletter-optin-box' ); ?>
	</label>
	<select
		name="<?php echo esc_attr( $this->get_field_name( 'btn_col' ) ); ?>"
		class="widefat"
		id="<?php echo esc_attr( $this->get_field_id( 'btn_col' ) ); ?>"
		>
		<?php $this->noptin_color_select( $btn_col ); ?>
	</select>
	</p>

	<label for="<?php echo esc_attr( $this->get_field_id( 'submit' ) ); ?>">
		<?php esc_attr_e( 'Submit Button Text:', 'newsletter-optin-box' ); ?>
	</label>
	<input
		class="widefat"
		id="<?php echo esc_attr( $this->get_field_id( 'submit' ) ); ?>"
		name="<?php echo esc_attr( $this->get_field_name( 'submit' ) ); ?>"
		type="text"
		value="<?php echo esc_attr( $submit ); ?>">
	</p>
		<?php
	}

	/**
	 * Saves widget options.
	 *
	 * @param array $new_instance new instance options.
	 * @param array $old_instance old instance options.
	 */
	public function update( $new_instance, $old_instance ) {
		return array(
			'title'    => ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '',
			'submit'   => ( ! empty( $new_instance['submit'] ) ) ? sanitize_text_field( $new_instance['submit'] ) : '',
			'desc'     => ( ! empty( $new_instance['desc'] ) ) ? sanitize_text_field( $new_instance['desc'] ) : '',
			'bg_color' => ( ! empty( $new_instance['bg_color'] ) ) ? $new_instance['bg_color'] : 'transparent',
			'color'    => ( ! empty( $new_instance['color'] ) ) ? $new_instance['color'] : 'transparent',
			'h2_col'   => ( ! empty( $new_instance['h2_col'] ) ) ? $new_instance['h2_col'] : 'transparent',
			'btn_col'  => ( ! empty( $new_instance['btn_col'] ) ) ? $new_instance['btn_col'] : 'transparent',
			'redirect' => ( ! empty( $new_instance['redirect'] ) ) ? esc_url( $new_instance['redirect'] ) : '',

		);

	}
}
