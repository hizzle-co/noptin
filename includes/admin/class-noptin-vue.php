<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

	/**
	 * Handles display of Vue Apps
	 *
	 * @since       1.0.8
	 */

class Noptin_Vue {

	/**
	 * registers action and filter hooks
	 */
	public static function init_hooks() {

		add_action( 'noptin_render_editor_panel', array( __CLASS__, 'panel' ), 10, 2 );
		add_action( 'noptin_render_editor_radio', array( __CLASS__, 'radio' ), 10, 2 );
		add_action( 'noptin_render_editor_radio_button', array( __CLASS__, 'radio_button' ), 10, 2 );
		add_action( 'noptin_render_editor_button', array( __CLASS__, 'button' ), 10, 2 );
		add_action( 'noptin_render_editor_paragraph', array( __CLASS__, 'paragraph' ), 10, 2 );
		add_action( 'noptin_render_editor_hero', array( __CLASS__, 'hero' ), 10, 2 );
		add_action( 'noptin_render_editor_textarea', array( __CLASS__, 'textarea' ), 10, 2 );
		add_action( 'noptin_render_editor_editor', array( __CLASS__, 'editor' ), 10, 2 );
		add_action( 'noptin_render_editor_form_fields', array( __CLASS__, 'form_fields' ), 10, 2 );
		add_action( 'noptin_render_editor_select', array( __CLASS__, 'select' ), 10, 2 );
		add_action( 'noptin_render_editor_multiselect', array( __CLASS__, 'select' ), 10, 2 );
		add_action( 'noptin_render_editor_multi_checkbox', array( __CLASS__, 'multi_checkbox' ), 10, 2 );
		add_action( 'noptin_render_editor_input', array( __CLASS__, 'input' ), 10, 2 );
		add_action( 'noptin_render_editor_custom_fields', array( __CLASS__, 'custom_fields' ), 10, 2  );

		add_filter( 'noptin_field_types', array( __CLASS__, 'get_field_types' ), 5 );
		add_action( 'noptin_field_type_settings', array( __CLASS__, 'print_field_type_settings' ), 5 );
		add_action( 'noptin_field_type_settings', array( __CLASS__, 'print_field_type_required_settings' ), 100000 );
		add_action( 'noptin_field_type_optin_markup', array( __CLASS__, 'print_default_markup' ), 5 );
		add_action( 'noptin_field_type_frontend_optin_markup', array( __CLASS__, 'print_frontend_markup' ), 10, 2 );

	}

	/**
	 * Displays tooltips
	 *
	 * @access      public
	 * @since       1.0.8
	 * @return      void
	 */
	public static function display_tooltip( $msg, $echo = true ) {

		// Generate the tooltip markup.
		$tooltip = "
			<noptin-tooltip trigger='hover' :options=\"{placement: 'bottom'}\">
				<div class='popper'>$msg</div>
				<span class='dashicons dashicons-info' slot='reference'></span>
			</noptin-tooltip>";

		// Maybe print the tooltip.
		if ( $echo ) {
			echo $tooltip;
		}

		return $tooltip;
	}

	/**
	 * Sanitizes the element
	 */
	public static function sanitize_el( $id, $el ) {

		// Restrict markup.
		if ( empty( $el['restrict'] ) ) {
			$el['restrict'] = '';
		} else {
			$el['restrict'] = 'v-if="' . $el['restrict'] . '"';
		}

		// Css id.
		$el['css_id'] = wp_generate_password( '4', false ) . time() . $id;

		// tooltips.
		if ( empty( $el['tooltip'] ) ) {
			$el['tooltip'] = '';
		} else {
			$el['tooltip'] = self::display_tooltip( $el['tooltip'], false );
		}

		// Label.
		if ( empty( $el['label'] ) ) {
			$el['label'] = '';
		}

		// class.
		$el['_class'] = '';
		if ( ! empty( $el['class'] ) ) {
			$el['_class'] = $el['class'];
			unset( $el['class'] );
		}

		// description.
		$description = '';

		if ( ! empty( $el['description'] ) ) {
			$description = '<p class="description">' . $el['description'] . '</p>';
		}

		$el['description'] = $description;

		// Attributes.
		$attrs = '';
		foreach ( $el as $attr => $val ) {
			if ( is_scalar( $val ) && ! in_array( $attr, array( 'restrict', 'description', 'tooltip', 'css_id', 'label', 'el', 'type', 'content', '_class', 'default' ) ) ) {
				$val   = esc_attr( $val );
				$attrs = "$attrs $attr='$val'";
			}
		}
		$el['attrs'] = $attrs;

		return $el;

	}

	/**
	 * Renders an element or component
	 *
	 * @param $id string Required. Unique id of the rendered field
	 * @param $field array Required. The args of field to render
	 * @param $panel string Optional. The panel where this field will be rendered
	 * @return void
	 */
	public static function render_el( $id, $field, $panel = false ) {

		// Ensure an element has been specified.
		if ( ! empty( $field['el'] ) ) {
			$id      = esc_attr( $id );
			$field   = self::sanitize_el( $id, $field );
			$element = $field['el'];

			/**
			 * Fires when rendering an editor field
			 *
			 * @since 1.0.0
			*/
			do_action( "noptin_render_editor_{$element}", $id, $field, $panel );
		}

	}

	/**
	 * Renders a panel
	 */
	public static function panel( $id, $panel ) {

		// Don't display empty panels.
		if ( empty( $panel['children'] ) ) {
			return;
		}

		// Default panel state.
		$style1 = 'display:none';
		$style2 = 'display:inline-block';

		if ( ! empty( $panel['open'] ) ) {
			$style1 = 'display:inline-block';
			$style2 = 'display:none';
		}

		// Echo the panel opening wrapper.
		printf(
			'<div %s id="%s" class="noptin-popup-editor-panel">
                <div class="noptin-popup-editor-panel-header" @click="togglePanel(\'%s\')">
					<h2 class="noptin-popup-editor-panel-title">
						<button type="button">
							<span class="dashicons dashicons-arrow-up-alt2 noptin-popup-editor-panel-toggle"  style="%s"></span>
							<span class="dashicons dashicons-arrow-down-alt2 noptin-popup-editor-panel-toggle"  style="%s"></span>
							%s %s
						</button>
					</h2>
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

		// Display all the children.
		foreach ( $panel['children'] as $id => $field ) {
			self::render_el( $id, $field );
		}

		// Display panel wrapper close.
		echo '</div></div>';

	}

	/**
	 * Renders radio input
	 */
	public static function radio( $id, $field ) {

		$attrs   = $field['attrs'];
		$options = '';
		if ( is_array( $field['options'] ) ) {
			foreach ( $field['options'] as $val => $label ) {
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
		if ( is_array( $field['options'] ) ) {
			foreach ( $field['options'] as $val => $label ) {
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
	 * Renders buttons
	 */
	public static function button( $id, $field ) {

		printf(
			'<a %s %s class="button %s" href="%s">%s %s</a>',
			$field['restrict'],
			$field['attrs'],
			$field['_class'],
			$field['url'],
			$field['label'],
			$field['tooltip']
		);

	}

	/**
	 * Renders paragraph
	 */
	public static function paragraph( $id, $field ) {

		// Abort if there is no content.
		if ( empty( $field['content'] ) ) {
			return;
		}

		printf(
			'<p %s %s class="noptin-padded %s">%s %s</p>',
			$field['restrict'],
			$field['attrs'],
			$field['_class'],
			$field['content'],
			$field['tooltip']
		);

	}

	/**
	 * Renders hero text
	 */
	public static function hero( $id, $field ) {

		// Abort if there is no content.
		if ( empty( $field['content'] ) ) {
			return;
		}

		printf(
			'<h2 %s %s class="noptin-hero %s">%s %s</h2>',
			$field['restrict'],
			$field['attrs'],
			$field['_class'],
			$field['content'],
			$field['tooltip']
		);

	}

	/**
	 * Renders a textarea
	 */
	public static function textarea( $id, $field ) {

		printf(
			'<div %s class="field-wrapper noptin-textarea-wrapper %s">
				<label class="noptin-textarea-label">%s %s</label>
				<div class="noptin-content"><textarea %s v-model="%s"></textarea>%s</div>
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
			$id
		);

	}

	public static function custom_fields( $id, $field ) {
		$restrict = $field['restrict'];
		get_noptin_template( 'subscriber-fields-editor.php', compact( 'id', 'restrict' ) );
	}

	public static function form_fields( $id, $field ) {
		echo "<field-editor {$field['restrict']} v-bind='\$data'></field-editor>";
	}

	/**
	 * Returns opt-in form field types
	 */
	public static function get_field_types( $field_types = array() ) {

		foreach ( get_noptin_custom_fields( true ) as $custom_field ) {

			$field_types[] = array(
				'label'            => $custom_field['label'],
				'type'             => $custom_field['merge_tag'],
				'supports_label'   => true,
				'supports_require' => true,
			);

		}

		return $field_types;
	}

	/**
	 * Renders a select field
	 */
	public static function print_field_type_settings( $field_type = array() ) {

		$type = $field_type['type'];
		$v_if = "v-if=\"field.type.type=='$type'\"";

		// Field label.
		if ( ! empty( $field_type['supports_label'] ) ) {

			$label = __( 'Frontend Label', 'newsletter-optin-box' );
			echo "<div class='noptin-text-wrapper' $v_if>
					<label>$label<input type='text' v-model='field.type.label'/></label>
				</div>";

		}

		// Field name.
		if ( ! empty( $field_type['supports_name'] ) ) {

			$label = __( 'Admin Label', 'newsletter-optin-box' );
			$tip   = esc_attr__( 'You will use this as a merge tag in your emails.', 'newsletter-optin-box' );
			echo "<div class='noptin-text-wrapper' $v_if>
					<label>$label<noptin-tip tooltip='$tip'></noptin-tip><input type='text' v-model='field.type.name'/></label>
				</div>";

		}

		// Field value.
		if ( ! empty( $field_type['supports_value'] ) ) {

			$label = __( 'Value', 'newsletter-optin-box' );
			echo "<div class='noptin-text-wrapper' $v_if>
					<label>$label<input type='text' v-model='field.type.value'/></label>
				</div>";

		}

		// Field value.
		if ( ! empty( $field_type['supports_options'] ) ) {

			$label = __( 'Options', 'newsletter-optin-box' );
			echo "<div class='noptin-textarea-wrapper' $v_if>
					<label>$label<textarea v-model='field.type.options'/></textarea></label>
				</div>";

		}

	}

	/**
	 * Renders a select field
	 */
	public static function print_field_type_required_settings( $field_type = array() ) {

		$type  = $field_type['type'];
		$v_if  = "v-if=\"field.type.type=='$type'\"";
		$label = __( 'Is this field required?', 'newsletter-optin-box' );

		// Required.
		if ( ! empty( $field_type['supports_require'] ) ) {

			echo '
				<label class="noptin-checkbox-wrapper" ' . $v_if . '>
					<input type="checkbox" class="screen-reader-text" v-model="field.require"/>
					<span class="noptin-checkmark"></span>
					<span class="noptin-label">'. $label . '</span>
				</label>';

		}

	}

	/**
	 * Renders a the default fields markup
	 */
	public static function print_default_markup() {
		?>
		<input 		v-if="field.type.type=='name'" 			   name='name' 			  type="text" 		class="noptin-form-field" 			:placeholder="field.type.label" :required="field.require" />
		<input 		v-if="field.type.type=='text'" 			  :name='field.type.name' type="text" 		class="noptin-form-field" 			:placeholder="field.type.label" :required="field.require" />
		<input 		v-if="field.type.type=='hidden'" 		  :name='field.type.name' type="hidden" 	v-model="field.type.value"/>
		<label 		v-if="field.type.type=='checkbox'"><input :name='field.type.name' type="checkbox"   value="1"   class="noptin-checkbox-form-field"  :required="field.require" /><span>{{field.type.label}}</span></label>
		<textarea   v-if="field.type.type=='textarea'" 		  :name='field.type.name' 					class="noptin-form-field" 			:placeholder="field.type.label" :required="field.require"></textarea>
		<select     v-if="field.type.type=='dropdown'"          :name='field.type.name' class="noptin-form-field" :required="field.require">
			<option>{{field.type.label}}</option>
			<option v-for="(option, index) in field.type.options.split(',')" :key="index">{{option | optionize}}</option>
		</select>

		<?php

	}

	/**
	 * Renders a the frontend fields markup
	 */
	public static function print_frontend_markup( $field, $data ) {

		// Labels.
		$label = '';
		if ( ! empty( $field['type']['label'] ) ) {
			$label = esc_attr( $field['type']['label'] );
		}

		// Required fields.
		$required = '';
		if ( ! empty( $field['require'] ) && 'false' !== $field['require'] ) {
			$required = 'required';
		}

		// Field names.
		$name = esc_attr( $field['key'] );

		// Full name.
		if ( 'name' === $field['type']['type'] ) {
			echo "<input name='$name' type='text' class='noptin-form-field' placeholder='$label'  $required />";
		}

		// Text.
		if ( 'text' === $field['type']['type'] ) {
			echo "<input name='$name' type='text' class='noptin-form-field' placeholder='$label'  $required />";
		}

		// Hidden.
		if ( 'hidden' === $field['type']['type'] ) {
			$value = esc_attr( $field['type']['value'] );
			echo "<input name='$name' type='hidden' value='$value' />";
		}

		// Checkbox.
		if ( 'checkbox' === $field['type']['type'] ) {
			$value = '1'; // Use static value to prevent problems with translated values being saved into the database
			echo "<label><input name='$name' type='checkbox' value='$value' class='noptin-checkbox-form-field' $required/><span>$label</span></label>";
		}

		// Textarea.
		if ( 'textarea' === $field['type']['type'] ) {
			echo "<textarea name='$name' class='noptin-form-field' placeholder='$label' $required></textarea>";
		}

		// Select.
		if ( 'dropdown' === $field['type']['type'] ) {
			echo "<select name='$name' class='noptin-form-field' $required>";
			echo "<option selected='selected'>$label</option>";

			foreach ( explode( ',', $field['type']['options'] ) as $option ) {

				if ( empty( $option ) ) {
					continue;
				}

				$option = explode( '|', $option );
				$label  = esc_html( $option[0] );
				$value  = isset( $option[1] ) ? esc_attr( $option[1] ) : esc_attr( $option[0] );

				echo "<option value='$value'>$label</option>";
			}

			echo "</select>";
		}

	}

	/**
	 * Renders a select field
	 */
	public static function select( $id, $field ) {

		if ( 'multiselect' == $field['el'] ) {
			$field['attrs'] .= ' multiple="multiple"';
		}

		if ( empty( $field['tags'] ) ) {
			$field['attrs'] .= ' tags="no"';
		}

		if ( empty( $field['options'] ) ) {
			$field['options'] = array();
		}

		$options = '';

		if ( isset( $field['placeholder'] ) ) {
			$placeholder = esc_html( $field['placeholder'] );
			$options .= "<option value='' disabled>$placeholder</option>";
		}

		foreach ( $field['options'] as $val => $name ) {
			$val      = esc_attr( $val );
			$name     = esc_html( $name );
			$options .= "<option value='$val'>$name</option>";
		}

		$el = empty( $field['normal'] ) ? 'noptin-select' : 'select';

		printf(
			'<div %s class="noptin-select-wrapper %s field-wrapper">
				<label class="noptin-select-label">%s %s</label>
				<div class="noptin-content"><%s v-model="%s" %s>%s</%s>%s</div>
			</div>',
			$field['restrict'],
			$field['_class'],
			$field['label'],
			$field['tooltip'],
			$el,
			$id,
			$field['attrs'],
			$options,
			$el,
			$field['description']
		);

	}

	/**
	 * Renders multi_checkbox input
	 */
	public static function multi_checkbox( $id, $field ) {

		foreach ( $field['options'] as $name => $label ) {
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

		// If no input type is set, set it to text.
		if ( empty( $field['type'] ) ) {
			$field['type'] = 'text';
		}

		$class       = "noptin-{$field['type']}-wrapper field-wrapper";
		$_class      = $field['_class'];
		$attrs       = $field['attrs'];
		$type        = $field['type'];
		$restrict    = $field['restrict'];
		$label       = $field['label'];
		$tooltip     = $field['tooltip'];
		$description = empty( $field['description'] ) ? '' : $field['description'];

		switch ( $type ) {

			// Color picker.
			case 'color':
				echo "<div class='$class $_class' $restrict><span class='noptin-label'>$label $tooltip</span> <noptin-swatch v-model='$id' popover-x='left'></noptin-swatch>$description</div>";
				break;

			case 'switch':
				$on  = empty( $field['on'] ) ? '' : '<span class="on">' . $field['on'] . '</span>';
				$off = empty( $field['off'] ) ? '' : '<span class="off">' . $field['off'] . '</span>';
				echo "<label class='$class $_class' $restrict><input type='checkbox' v-model='$id' class='screen-reader-text'> <span class='noptin-switch-slider'><span> </span></span><span class='noptin-label'> $label $tooltip</span>$description</label>";
				break;

			case 'checkbox':
				echo "<label class='$class $_class' $restrict><input v-model='$id' type='checkbox' $attrs class='screen-reader-text'/> <span class='noptin-checkmark'></span> <span class='noptin-label'>$label $tooltip</span>$description</label>";
				break;

			case 'checkbox_alt':
				echo "<label class='$class $_class' $restrict><span class='noptin-label'>$label $tooltip</span><div><input v-model='$id' type='checkbox' $attrs/> $description</div></label>";
				break;

			case 'image':
				$size = empty( $field['size'] ) ? 'thumbnail' : trim( $field['size'] );
				$submit_text = esc_attr__( 'Upload Image', 'newsletter-optin-box' );
				echo "<div class='$class $_class' $restrict><span class='noptin-label'>$label $tooltip</span> <div><div class='image-uploader'><input v-model='$id' placeholder='http://' type='text' $attrs /> <input @click=\"upload_image('$id', '$size')\" type='button' class='button button-secondary' value='$submit_text' /></div>$description</div></div>";
				break;

			default:
				echo "<label class='$class' $restrict><span class='noptin-label'>$label $tooltip</span> <div class='noptin-content'><input class='$_class' v-model='$id' type='$type' $attrs />$description</div></label>";
				break;
		}

	}

}
