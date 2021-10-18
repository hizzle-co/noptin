<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Displays registers sidebar widget
 *
 * @since       1.0.5
 */
class Noptin_Sidebar extends WP_Widget {

	/**
	 * Class Constructor.
	 */
	public function __construct() {

		// Prepare widget args.
		$widget_ops = array(
			'classname'   => 'noptin_widget_premade',
			'description' => __( 'Use this widget to add newsletter forms made using the Form Editor', 'newsletter-optin-box' ),
		);

		// Add it to the list of widgets.
		parent::__construct( 'noptin_widget_premade', 'Noptin Premade Form', $widget_ops );

	}

	/**
	 * Outputs the opt in form widget on the front end
	 *
	 * @access      public
	 * @since       1.0.5
	 * @return      void
	 * @param string $args     The widget args to use.
	 * @param string $instance The instance args to use.
	 */
	public function widget( $args, $instance ) {

		// Abort early if there is no form...
		if ( empty( $instance['form'] ) ) {
			return;
		}

		// ...or the form cannot be displayed on this page.
		$form = noptin_get_optin_form( trim( $instance['form'] ) );

		if ( 'sidebar' !== $form->optinType || ! $form->can_show() ) {
			return;
		}

		// Display the widget.
		echo $args['before_widget'];
		echo $form->get_html();
		echo $args['after_widget'];
	}

	/**
	 * Outputs a form select field
	 *
	 * @access      public
	 * @since       1.0.5
	 * @return      void
	 * @param       int $selected the currently selected form.
	 */
	public function forms_select( $selected ) {

		// Get all widget forms.
		$forms = $this->get_forms();

		// Create <option> tags for each form.
		foreach ( $forms as $form ) {

			// Fetch the form title.
			$name = esc_html( get_the_title( $form ) );

			// Is it selected?
			$_selected = selected( $form, $selected, true );

			echo "<option value='$form' $_selected>$name</option>";
		}
	}

	/**
	 * Returns a list of all published sidebar forms
	 *
	 * @access      public
	 * @since       1.0.5
	 * @return      int[]
	 */
	public function get_forms() {

		$args = array(
			'numberposts' => -1,
			'fields'      => 'ids',
			'post_type'   => 'noptin-form',
			'post_status' => 'publish',
			'meta_query'  => array(
				array(
					'key'     => '_noptin_optin_type',
					'value'   => 'sidebar',
					'compare' => '=',
				),
			),
		);

		return get_posts( $args );
	}

	/**
	 * Output widget settings
	 *
	 * @access      public
	 * @since       1.0.5
	 * @return      void
	 * @param       array $instance current instance options.
	 */
	public function form( $instance ) {
		$form = ! empty( $instance['form'] ) ? $instance['form'] : '';
		?>

	<p>

		<label for="<?php echo esc_attr( $this->get_field_id( 'form' ) ); ?>">
			<?php esc_attr_e( 'Form:', 'newsletter-optin-box' ); ?>
		</label>

		<select
			name="<?php echo esc_attr( $this->get_field_name( 'form' ) ); ?>"
			class="widefat"
			id="<?php echo esc_attr( $this->get_field_id( 'form' ) ); ?>"
		>
			<option value="" <?php selected( '', $form ); ?>><?php esc_html_e( 'Select a form', 'newsletter-optin-box' ); ?></option>
			<?php $this->forms_select( $form ); ?>
		</select>
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
		return array(
			'form' => ( ! empty( $new_instance['form'] ) ) ? absint( $new_instance['form'] ) : '',
		);

	}

}
