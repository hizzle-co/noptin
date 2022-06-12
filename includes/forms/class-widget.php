<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Registers the newsletter widget
 *
 * @since       1.0.5
 */
class Noptin_Sidebar extends WP_Widget {

	/**
	 * Class Constructor.
	 */
	public function __construct() {

		// Register widget.
		parent::__construct(
			'noptin_widget_premade', // Base ID (forgive the poor naming)
			__( 'Noptin Newsletter Form', 'newsletter-optin-box' ), // Name
			array(
				'description' => __( 'Displays a newsletter sign-up form', 'newsletter-optin-box' ),
			)
		);

	}

	/**
	 * Displays the widget on the front end
	 *
	 * @see WP_Widget::widget()
	 * @access      public
	 * @since       1.0.5
	 * @return      void
	 * @param string $args     Widget arguments.
	 * @param string $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {

		// Ensure $instance is an array.
		if ( ! is_array( $instance ) ) {
			$instance = array();
		}

		// Abort early if the provided form is not visible...
		if ( ! empty( $instance['form'] ) && -1 !== $instance['form'] ) {

			$form = noptin_get_optin_form( absint( $instance['form'] ) );

			if ( ! $form->can_show() ) {
				return;
			}
		}

		// Display opening wrapper.
		echo $args['before_widget']; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

		// Display title.
		if ( ! empty( $instance['title'] ) ) {
			$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		}

		// Display newsletter form.
		show_noptin_form( $instance );

		// Display the closing wrapper.
		echo $args['after_widget']; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Returns a list of all published forms
	 *
	 * @access      public
	 * @since       1.0.5
	 * @return      int[]
	 */
	public function get_forms() {

		$forms = get_posts(
			array(
				'numberposts' => -1,
				'post_status' => array( 'publish' ),
				'post_type'   => 'noptin-form',
			)
		);
		$data  = array();

		foreach ( $forms as $form ) {
			$data[] = array(
				'label' => $form->post_title,
				'value' => $form->ID,
			);
		}

		return $data;
	}

	/**
	 * Output widget settings
	 *
	 * @access      public
	 * @since       1.0.5
	 * @return      void
	 * @param       array $settings Previously saved values from database.
	 */
	public function form( $settings ) {

		// ensure $settings is an array
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$forms = $this->get_forms();
		$form  = isset( $settings['form'] ) ? $settings['form'] : 0;
		$title = isset( $settings['title'] ) ? $settings['title'] : __( 'Newsletter', 'newsletter-optin-box' );

		?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'newsletter-optin-box' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'form' ) ); ?>"><?php esc_html_e( 'Form:', 'newsletter-optin-box' ); ?></label>

			<select name="<?php echo esc_attr( $this->get_field_name( 'form' ) ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'form' ) ); ?>">
				<option value="0" <?php selected( empty( $form ) ); ?>><?php esc_html_e( 'Default form', 'newsletter-optin-box' ); ?></option>
				<option value="-1" <?php selected( $form, '-1' ); ?>><?php esc_html_e( 'Single-line / Horizontal Form', 'newsletter-optin-box' ); ?></option>
				<?php foreach ( $forms as $_form ) : ?>
					<option value="<?php echo esc_attr( $_form['value'] ); ?>" <?php selected( $form, $_form['value'] ); ?>><?php echo esc_attr( $_form['label'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>

		<?php
			/**
			 * Runs right after the widget settings form is outputted
			 *
			 * @param array $settings Saved settings.
			 * @param Noptin_Sidebar $widget
			 */
			do_action( 'noptin_form_widget_form', $settings, $this );
		?>

		<p class="description">
			<?php
				printf(
					// translators: %1$s is a link to the newsletter form editor.
					wp_kses_post( __( 'You can edit or create new newsletter sign-up forms in the <a href="%s">Noptin forms overview</a> page.', 'newsletter-optin-box' ) ),
					esc_url( admin_url( 'edit.php?post_type=noptin-form' ) )
				);
			?>
		</p>

		<?php
	}

	/**
	 * Saves widget settings
	 *
	 * @access      public
	 * @since       1.0.5
	 * @return      array
	 * @param       array $new_instance new instance options.
	 * @param       array $old_instance old instance options.
	 */
	public function update( $new_instance, $old_instance ) {

		if ( ! empty( $new_instance['title'] ) ) {
			$new_instance['title'] = sanitize_text_field( $new_instance['title'] );
		}

		if ( ! empty( $new_instance['form'] ) ) {
			$new_instance['form'] = intval( $new_instance['form'] );
		}

		/**
		 * Filters the widget settings before they are saved.
		 *
		 * @param array $new_settings
		 * @param array $old_settings
		 * @param Noptin_Sidebar $widget
		 * @ignore
		 */
		return apply_filters( 'noptin_form_widget_update_settings', $new_instance, $old_instance, $this );
	}

}
