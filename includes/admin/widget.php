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

    // class constructor
    public function __construct() {
        $widget_ops = array( 
            'classname' => 'noptin_widget',
            'description' => 'Add a newsletter optin box to any sidebar or widget area.',
        );
        parent::__construct( 'noptin_widget', 'Noptin Email Subscription', $widget_ops );
    }

    // output the widget content on the front-end
    public function widget($args, $instance) {

        //Abort early if there is no form...
        if ( empty( $instance['form'] ) ) {
            return;
        }

        //...or the form cannot be displayed on this page
        $form = noptin_get_optin_form( trim( $instance['form'] ) );

        if( 'sidebar' != $form->optinType || !$form->can_show() ) {
            return;
        }

        echo $args['before_widget'];
        echo $form->optinHTML;
        echo $args['after_widget'];
    }

    //Displays forms select box
    public function noptin_forms_select($form) {

        //Get all widget forms
        $forms = noptin_get_optin_forms( '_noptin_optin_type', 'sidebar' );
        foreach ( $forms as $_form ) {
            
            if( 'publish' != $_form->post_status ) {
                continue;
            }
			$id   = esc_attr( $_form->ID );
			$name = esc_html( $_form->post_title );
			echo "<option value='$id' ";
			
			//Check if the current field is being shown
			selected( $id, $form );
			
			echo ">$name</option>";
        }
    }

    // output the option form field in admin Widgets screen
    public function form($instance) {
        $form    = ! empty( $instance['form'] ) ? $instance['form'] : '';

?>
    
    <p>

	    <label for="<?php echo esc_attr( $this->get_field_id( 'form' ) ); ?>">
	        <?php esc_attr_e( 'Form:', 'noptin' ); ?>
	    </label> 
	
	    <select 
            name="<?php echo esc_attr( $this->get_field_name( 'form' ) ); ?>"
            class="widefat"
            id="<?php echo esc_attr( $this->get_field_id( 'form' ) ); ?>"
        >
            <option value="" disabled <?php selected( '', $form ); ?>><?php esc_html_e( 'Select form', 'noptin' ); ?></option>
            <?php $this->noptin_forms_select($form);?>
        </select>
    </p>

	<?php
    }

    // save options
    public function update($new_instance, $old_instance) {
        return array(
            'form'    => ( ! empty( $new_instance['form'] ) ) ? absint( $new_instance['form'] ) : '',
        );

    }
}