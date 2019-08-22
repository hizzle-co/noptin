<?php

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) {
    die;
}

    /**
     * Handles display of Vue Apps
     *
     * @since       1.0.8
     */

    class Noptin_Vue{

    /**
	 * registers action and filter hooks
	 */
	public static function init_hooks() {

		add_action( 'noptin_render_editor_panel', array( __CLASS__, 'panel'), 10, 2 );
		add_action( 'noptin_render_editor_radio', array( __CLASS__, 'radio'), 10, 2 );
		add_action( 'noptin_render_editor_radio_button', array( __CLASS__, 'radio_button'), 10, 2 );
		add_action( 'noptin_render_editor_paragraph', array( __CLASS__, 'paragraph'), 10, 2 );
		add_action( 'noptin_render_editor_hero', array( __CLASS__, 'hero'), 10, 2 );
		add_action( 'noptin_render_editor_textarea', array( __CLASS__, 'textarea'), 10, 2 );
		add_action( 'noptin_render_editor_editor', array( __CLASS__, 'editor'), 10, 2 );
		add_action( 'noptin_render_editor_form_fields', array( __CLASS__, 'form_fields'), 10, 2 );
		add_action( 'noptin_render_editor_select', array( __CLASS__, 'select'), 10, 2 );
		add_action( 'noptin_render_editor_multiselect', array( __CLASS__, 'select'), 10, 2 );
		add_action( 'noptin_render_editor_multi_checkbox', array( __CLASS__, 'multi_checkbox'), 10, 2 );
		add_action( 'noptin_render_editor_input', array( __CLASS__, 'input'), 10, 2 );

    }

    /**
     * Displays tooltips
     *
     * @access      public
     * @since       1.0.8
     * @return      void
     */
    public static function display_tooltip( $msg, $echo=true ) {

		//Generate the tooltip markup
		$tooltip  = "
			<noptin-tooltip trigger='hover' :options=\"{placement: 'top'}\">
				<div class='popper'>$msg</div>
				<span class='dashicons dashicons-info' slot='reference'></span>
			</noptin-tooltip>";

		//Maybe print the tooltip
		if( $echo ) {
			echo $tooltip;
		}

		return $tooltip;
	}

	/**
	 * Sanitizes the element
	 */
	public static function sanitize_el( $id, $el ) {

		//Restrict markup
		if( empty( $el['restrict'] ) ) {
			$el['restrict'] = '';
		} else {
			$el['restrict'] = 'v-if="' . $el['restrict'] . '"';
		}

		//Css id
		$el['css_id'] = wp_generate_password( '4', false ) . time() . $id;

		//tooltips
		if( empty( $el['tooltip'] ) ) {
			$el['tooltip'] = '';
		} else {
			$el['tooltip'] = self::display_tooltip( $el['tooltip'], false );
		}

		//Label
		if( empty( $el['label'] ) ) {
			$el['label'] = '';
		}

		//class
		$el['_class'] = '';
		if(! empty( $el['class'] ) ) {
			$el['_class'] = $el['class'];
			unset( $el['class'] );
		}

		//description
		$description = '';

		if(! empty( $field['description'] ) ) {
			$description = '<p class="description">' . $field['description'] . '</p>';
		}

		$field['description'] = $description;

		//Attributes
		$attrs = '';
		foreach( $el as $attr=>$val ){
			if( is_scalar( $val) && !in_array( $attr, array( 'restrict', 'description', 'tooltip', 'css_id', 'label', 'el', 'type', 'content', '_class' ) ) ) {
				$val     = esc_attr($val);
				$attrs   = "$attrs $attr='$val'";
			}
		}
		$el['attrs'] = $attrs;

		return $el;

    }

	/**
	 * Renders an element or component
	 * @param $id string Required. Unique id of the rendered field
	 * @param $field array Required. The args of field to render
	 * @param $panel string Optional. The panel where this field will be rendered
	 * @return void
	 */
	public static function render_el( $id, $field, $panel = false ) {

		//Ensure an element has been specified
		if(!empty($field['el'])){
			$field   = self::sanitize_el( $id, $field );
			$element = $field['el'];


			/**
			 * Fires when rendering an editor field
			 *
			 * @since 1.0.0
			 *
			*/
			do_action( "noptin_render_editor_{$element}", $id, $field, $panel );
		}

	}

	/**
	 * Renders a panel
	 */
	public static function panel( $id, $panel ) {

		//Don't display empty panels
		if( empty( $panel['children'] ) ){
			return;
		}

		//Default panel state
		$style1 = 'display:none';
		$style2 = 'display:inline-block';

		if(! empty( $panel['open'] ) ) {
			$style1 = 'display:inline-block';
			$style2 = 'display:none';
		}

		//Echo the panel opening wrapper
		printf(
            '<div %s id="%s" class="noptin-popup-editor-panel">
                <div class="noptin-popup-editor-panel-header" @click="togglePanel(\'%s\')">
                    <span class="dashicons dashicons-arrow-up-alt2 noptin-popup-editor-panel-toggle"  style="%s"></span>
                    <span class="dashicons dashicons-arrow-down-alt2 noptin-popup-editor-panel-toggle"  style="%s"></span>
                    <h2 class="noptin-popup-editor-panel-title">%s %s</h2>
                </div>
				<div class="noptin-popup-editor-panel-body" style="%s">',
			$panel['restrict'],
			$panel['css_id'],
			$panel['css_id'],
			$style1,
			$style2,
			$panel['title'],
			$panel['tooltip'],
			$style1
		);

		//Display all the children
		foreach( $panel['children'] as $id=>$field ){
			self::render_el( $id, $field );
		}

		//Display panel wrapper close
		echo "</div></div>";

	}

	/**
	 * Renders radio input
	 */
	public static function radio( $id, $field ) {

		$attrs   = $field['attrs'];
		$options = '';
		if(is_array($field['options'])) {
			foreach( $field['options'] as $val => $label ){
				$options .= "<label><input $attrs type='radio' v-model='$id' value='$val' class='screen-reader-text'>$label <span class='noptin-checkmark'></span> </label>";
			}
		}

		printf(
			'<div class="noptin-radio-wrapper field-wrapper" %s><span>%s %s</span>%s</div>',
			$field['restrict'],
			$field['label'],
			$field['tooltip'],
			$options
		);

	}

	/**
	 * Renders radio buttons
	 */
	public static function radio_button( $id, $field ) {

		$attrs   = $field['attrs'];
		$options = '';
		if(is_array($field['options'])) {
			foreach( $field['options'] as $val => $label ){
				$options .= "<label><input $attrs type='radio' v-model='$id' value='$val' class='screen-reader-text'><span>$label</span></label>";
			}
		}
		printf(
			'<fieldset class="noptin-radio-button-wrapper field-wrapper" %s>
				<legend>%s %s</legend>
				<div class="noptin-buttons">%s</div></fieldset>',
			$field['restrict'],
			$field['label'],
			$field['tooltip'],
			$options
		);

    }

	/**
	 * Renders paragraph
	 */
	public static function paragraph( $id, $field ) {

		//Abort if there is no content
		if( empty($field['content']) ){
			return;
		}

		printf(
			'<p %s %s class="noptin-padded %s">%s %s</p>',
			$field['restrict'],
			$field['attrs'],
			$field['_class'],
			$field['content'],
			$field['tooltip'],
		);

	}

	/**
	 * Renders hero text
	 */
	public static function hero( $id, $field ) {

		//Abort if there is no content
		if( empty($field['content']) ){
			return;
		}

		printf(
			'<h2 %s %s class="noptin-hero %s">%s %s</h2>',
			$field['restrict'],
			$field['attrs'],
			$field['_class'],
			$field['content'],
			$field['tooltip'],
		);

	}

	/**
	 * Renders a textarea
	 */
	public static function textarea( $id, $field ) {

		printf(
			'<div %s class="field-wrapper noptin-textarea-wrapper %s">
				<label class="noptin-textarea-label">%s %s</label>
				<div><textarea %s v-model="%s"></textarea>%s</div>
			</div>',
			$field['restrict'],
			$field['_class'],
			$field['label'],
			$field['tooltip'],
			$field['attrs'],
			$id,
			$field['description']
		);

	}

	/**
	 * Renders a css editor
	 */
	public static function editor( $id, $field ) {

		printf(
			'<div %s class="noptin-textarea-wrapper %s">
				<label>%s %s</label>
				<noptineditor %s id="%s" v-model="%s"></noptineditor>
			</div>',
			$field['restrict'],
			$field['_class'],
			$field['label'],
			$field['tooltip'],
			$field['attrs'],
			$id,
			$id,
		);

	}

	/**
	 * Renders the form fields editor
	 */
	public static function form_fields( $id, $field ) {
		echo "<field-editor :fields='$id'></field-editor>";
	}


	/**
	 * Renders a select field
	 */
	public static function select( $id, $field ) {

		if(! isset( $field['placeholder'] ) ) {
			$field['placeholder'] = 'Select';
		}

		$extra = '';
    	if( 'multiselect' == $field['el'] ) {
        	$extra = 'multiselect';
		}

		if(! empty( $field['taggable'] ) ) {
			$extra .= ' taggable :create-option =" val => ({ label: val, val: val })"';
			unset( $field['taggable'] );
		}

		if( empty($field['options']) ) {
			$field['options'] = array();
		}

		$options = array();
    	foreach( $field['options'] as $val => $name ){
        	$options[] = array(
            	'val'   => esc_attr( $val ),
            	'label' => esc_attr( $name ),
        	);
		}
		$options = wp_json_encode( $options );

		printf(
			'<div %s class="noptin-select-wrapper %s field-wrapper">
				<label>%s %s</label>
				<noptin-select
					:reduce="option => option.val"
					:clearable="false"
					:searchable="false"
					:options=\'%s\'
					%s %s
					v-model="%s">
				</noptin-select>
			</div>',
			$field['restrict'],
			$field['_class'],
			$field['label'],
			$field['tooltip'],
			$options,
			$field['attrs'],
			$extra,
			$id,
		);

	}

	/**
	 * Renders multi_checkbox input
	 */
	public static function multi_checkbox( $id, $field ) {

		foreach( $field['options'] as $name => $label ) {
			printf(
				'<label %s class="field-wrapper noptin-checkbox-wrapper %s">
					<input value="%s" type="checkbox" v-model="%s" %s class="screen-reader-text"/>
					<span class="noptin-checkmark"></span> <span class="noptin-label">%s %s</span>
				</label>',
				$field['restrict'],
				$field['_class'],
				$name,
				$id,
				$field['attrs'],
				$label,
				$field['tooltip']
			);
		}



	}

	/**
	 * Renders input field
	 */
	public static function input( $id, $field ) {

		//If no input type is set, set it to text
		if( empty($field['type']) ){
			$field['type'] = 'text';
		}

		$class 		= "noptin-{$field['type']}-wrapper field-wrapper";
		$_class		= $field['_class'];
		$attrs 		= $field['attrs'];
		$type  		= $field['type'];
		$restrict   = $field['restrict'];
		$label      = $field['label'];
		$tooltip    = $field['tooltip'];
		$description = empty( $field['description'] ) ? '' : $field['description'];


		switch ( $type ) {

			//Color picker
			case 'color':
				echo "<div class='$class $_class' $restrict><span class='noptin-label'>$label $tooltip</span> <noptin-swatch colors='material-basic' show-fallback v-model='$id' popover-to='left'></noptin-swatch>$description</div>";
				break;

			case 'switch':
				$on  = empty($field['on'])? ''  : '<span class="on">' . $field['on'] . '</span>';
				$off = empty($field['off'])? '' : '<span class="off">' . $field['off'] . '</span>';
				echo "<label class='$class $_class' $restrict><input type='checkbox' v-model='$id' class='screen-reader-text'> <span class='noptin-switch-slider'><span> </span></span><span class='noptin-label'> $label $tooltip</span>$description</label>";
				break;

			case 'checkbox':
				echo "<label class='$class $_class' $restrict><input v-model='$id' type='checkbox' $attrs class='screen-reader-text'/> <span class='noptin-checkmark'></span> <span class='noptin-label'>$label $tooltip</span>$description</label>";
				break;

			case 'checkbox_alt':
				echo "<label class='$class $_class' $restrict><span class='noptin-label'>$label $tooltip</span><div><input v-model='$id' type='checkbox' $attrs/> $description</div></label>";
				break;

			case 'image':
				echo "<div class='$class $_class' $restrict><span class='noptin-label'>$label $tooltip</span> <div><div class='image-uploader'><input v-model='$id' type='text' $attrs /> <input @click=\"upload_image('$id')\" type='button' class='button button-secondary' value='Upload Image' /></div>$description</div></div>";
				break;

			default:
				echo "<label class='$class' $restrict><span class='noptin-label'>$label $tooltip</span> <div class='noptin-content'><input class='$_class' v-model='$id' type='$type' $attrs />$description</div></label>";
				break;
		}

	}

}

Noptin_Vue::init_hooks();
