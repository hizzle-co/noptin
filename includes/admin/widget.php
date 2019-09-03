<?php
/**
 * Adds a newsletter optin widget
 *
 * Simple WordPress optin form
 *
 * @since             1.0.0
 *
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    die;
}

/**
 * Widget class
 *
 * @since       1.0.0
 */

class Noptin_Widget extends WP_Widget {

    //Colors
    public $colors = array(
        'transparent' => 'Inherit From Theme',
        '#e51c23' => 'Red',
        '#e91e63' => 'Pink',
        '#9c27b0' => 'Purple',
        '#673ab7' => 'Deep Purple',
        '#3f51b5' => 'Indigo',
        '#2196F3' => 'Blue',
        '#03a9f4' => 'Light Blue',
        '#00bcd4' => 'Cyan',
        '#009688' => 'Teal',
        '#4CAF50' => 'Green',
        '#8bc34a' => 'Light Green',
        '#cddc39' => 'Lime',
        '#ffeb3b' => 'Yellow',
        '#ffc107' => 'Amber',
        '#ff9800' => 'Orange',
        '#ff5722' => 'Deep Orange',
        '#795548' => 'Brown',
        '#607d8b' => 'Blue Grey',
        '#313131' => 'Black',
        '#fff' => 'White',
        '#aaa' => 'Grey',
    );

    // class constructor
    public function __construct() {
        $widget_ops = array(
            'classname' => 'noptin_widget',
            'description' => __( 'Use this widget to create and add a simple newsletter subscription widget', 'noptin' ),
        );
        parent::__construct( 'noptin_widget', 'Noptin New Form', $widget_ops );
    }

    // output the widget content on the front-end
    public function widget($args, $instance) {
		echo $args['before_widget'];

		//ID
		$id = $args['widget_id'];

        //Title
        $title = '';
	    if ( ! empty( $instance['title'] ) ) {
            $_title = apply_filters( 'widget_title', $instance['title'] );
		    $title = $args['before_title'] . $_title . $args['after_title'];
        }

        //Description
        $desc = '';
        if ( ! empty( $instance['desc'] ) ) {
		    $desc = '<p class="noptin-widget-desc">' . $instance['desc'] . '</p>';
        }

        //Redirect
        $redirect = '';
        if ( ! empty( $instance['redirect'] ) ) {
            $_redirect = esc_url($instance['redirect']);
		    $redirect = '<input class="noptin_form_redirect" name="noptin-redirect" type="hidden" value="' . $_redirect . '"/>';
        }

        //Submit button
        $submit = empty( $instance['submit'] ) ? esc_attr('Submit') : esc_attr($instance['submit']);

        //Colors
        $bg_color =  sanitize_hex_color( $instance['bg_color'] );
        $color    =  sanitize_hex_color( $instance['color'] );
        $h2_col   =  sanitize_hex_color( $instance['h2_col'] );
        $btn_col  =  sanitize_hex_color( $instance['btn_col'] );
		$has_bg   = !empty( $instance['bg_color'] ) && 'transparent' != $instance['bg_color'];
    ?>
    <style>

		#<?php echo $id; ?> .noptin-email-optin-widget {
			<?php
				if( $has_bg  ) {
					echo "min-height: 400px; padding: 32px;padding-top: 80px; background-color: $bg_color  !important;";
				}

				if( $color && 'transparent' != $instance['color']  ) {
					echo "color: $color; !important";
				}
			?>

            box-sizing: border-box !important;
        }

        #<?php echo $id; ?> .noptin-email-optin-widget .widget-title{
			<?php
				if( $h2_col && 'transparent' != $instance['h2_col']  ) {
					echo "color: $h2_col; !important";
				}
			?>
        }

		#<?php echo $id; ?> .noptin-email-optin-widget  .noptin-widget-email-input,
		#<?php echo $id; ?> .noptin-email-optin-widget  .noptin-widget-email-input:active{
			width: 100%;
			padding: 12px;
			outline: none;
        }

        #<?php echo $id; ?> .noptin-email-optin-widget .noptin_feedback_success{
            border:1px solid rgba(6, 147, 227, 0.8);
            display:none;
            padding:10px;
            margin-top:10px;
        }

        #<?php echo $id; ?> .noptin-email-optin-widget .noptin_feedback_error{
            border:1px solid rgba(227, 6, 37, 0.8);
            display:none;
            padding:10px;
            margin-top:10px;
        }

        #<?php echo $id; ?> .noptin-email-optin-widget .noptin-widget-submit-input{
            margin-top: 5px;
            display: block;
			width: 100%;
			padding: 12px;

			<?php
				if( $btn_col && 'transparent' != $instance['btn_col']  ) {
					echo "background-color: $btn_col; !important";
				}
			?>

        }
    </style>
    <div class="noptin-email-optin-widget">
        <form>
        <?php echo $title . $desc . $redirect;?>
        <input class="noptin-widget-email-input noptin_form_input_email" name="email" type="email" placeholder="Email Address" required >
        <input class="noptin-widget-submit-input" value="<?php echo $submit;?>" type="submit">
        <div class="noptin_feedback_success"></div>
        <div class="noptin_feedback_error"></div>
        </form>
    </div>

	<?php echo $args['after_widget'];
    }

    //Displays color select boxes
    public function noptin_color_select($color) {
        foreach ( $this->colors as $hex => $name ) {

			$hex = esc_attr( $hex );
			$name = esc_html($name);
			echo "<option value='$hex' ";

			//Check if the current field is being shown
			selected( $color, $hex );

			echo ">$name</option>";
        }
    }

    // output the option form field in admin Widgets screen
    public function form($instance) {
        $title    = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'FREE NEWSLETTER', 'noptin' );
        $desc     = ! empty( $instance['desc'] ) ? $instance['desc'] : esc_html__( 'Subscribe to our newsletter today and be the first to know when we publish a new blog post.', 'noptin' );
        $submit   = ! empty( $instance['submit'] ) ? $instance['submit'] : esc_html__( 'SUBSCRIBE NOW', 'noptin' );
        $bg_color = ! empty( $instance['bg_color'] ) ? $instance['bg_color'] : '#2196F3';
        $color    = ! empty( $instance['color'] ) ? $instance['color'] : '#fff';
        $h2_col   = ! empty( $instance['h2_col'] ) ? $instance['h2_col'] : '#fff';
        $btn_col  = ! empty( $instance['btn_col'] ) ? $instance['btn_col'] : '#e51c23';
        $redirect = ! empty( $instance['redirect'] ) ? $instance['redirect'] : '';

?>
	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
	<?php esc_attr_e( 'Title:', 'noptin' ); ?>
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
	<?php esc_attr_e( 'Description:', 'noptin' ); ?>
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
	<?php esc_attr_e( 'Redirect:', 'noptin' ); ?>
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
	<?php esc_attr_e( 'Background Color:', 'noptin' ); ?>
	</label>

	<select
        name="<?php echo esc_attr( $this->get_field_name( 'bg_color' ) ); ?>"
        class="widefat"
        id="<?php echo esc_attr( $this->get_field_id( 'bg_color' ) ); ?>"
        >
        <?php $this->noptin_color_select($bg_color );?>
    </select>
    </p>


    <p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'h2_col' ) ); ?>">
	<?php esc_attr_e( 'Title Color:', 'noptin' ); ?>
	</label>

    <select
        name="<?php echo esc_attr( $this->get_field_name( 'h2_col' ) ); ?>"
        class="widefat"
        id="<?php echo esc_attr( $this->get_field_id( 'h2_col' ) ); ?>"
        >
        <?php $this->noptin_color_select($h2_col );?>
    </select>
    </p>

    <p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'color' ) ); ?>">
	<?php esc_attr_e( 'Text Color:', 'noptin' ); ?>
	</label>
	<select
        name="<?php echo esc_attr( $this->get_field_name( 'color' ) ); ?>"
        class="widefat"
        id="<?php echo esc_attr( $this->get_field_id( 'color' ) ); ?>"
        >
        <?php $this->noptin_color_select($color );?>
    </select>
    </p>

    <p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'btn_col' ) ); ?>">
	<?php esc_attr_e( 'Button Color:', 'noptin' ); ?>
	</label>
	<select
        name="<?php echo esc_attr( $this->get_field_name( 'btn_col' ) ); ?>"
        class="widefat"
        id="<?php echo esc_attr( $this->get_field_id( 'btn_col' ) ); ?>"
        >
        <?php $this->noptin_color_select($btn_col );?>
    </select>
	</p>

    <label for="<?php echo esc_attr( $this->get_field_id( 'submit' ) ); ?>">
	<?php esc_attr_e( 'Submit Button Text:', 'noptin' ); ?>
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

    // save options
    public function update($new_instance, $old_instance) {
        return array(
            'title'    => ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '',
            'submit'   => ( ! empty( $new_instance['submit'] ) ) ? strip_tags( $new_instance['submit'] ) : '',
            'desc'     => ( ! empty( $new_instance['desc'] ) ) ? strip_tags( $new_instance['desc'] ) : '',
            'bg_color' => ( ! empty( $new_instance['bg_color'] ) ) ? $new_instance['bg_color'] : 'transparent',
            'color'    => ( ! empty( $new_instance['color'] ) ) ? $new_instance['color'] : '#313131',
            'h2_col'   => ( ! empty( $new_instance['h2_col'] ) ) ? $new_instance['h2_col'] : '#313131',
            'btn_col'  => ( ! empty( $new_instance['btn_col'] ) ) ? $new_instance['btn_col'] : '#ffc107',
            'redirect' => ( ! empty( $new_instance['redirect'] ) ) ? esc_url( $new_instance['redirect'] ) : '',

        );

    }
}
