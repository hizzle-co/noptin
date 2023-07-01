<?php defined( 'ABSPATH' ) || exit; ?>
<p <?php echo $restrict; ?> class="description"><?php esc_html_e( 'Collect more information from your subscribers by adding custom fields.', 'newsletter-optin-box' ); ?>&nbsp;<a href="https://noptin.com/guide/email-subscribers/custom-fields/" target="_blank"><?php esc_html_e( 'Learn More', 'newsletter-optin-box' ); ?></a></p>
<div <?php echo $restrict; ?> id="noptin-subscriber-fields-editor-available-fields" class="noptin-accordion-wrapper" style="max-width: 700px;">

	<span v-for="field in <?php echo esc_attr( $id ); ?>" :key="field.field_key">
		<h4 class="noptin-accordion-heading">
			<button aria-expanded="false" class="noptin-accordion-trigger" :aria-controls="'noptin-accordion-block-<?php echo esc_attr( $id ); ?>__' + field.merge_tag" type="button" @click.prevent="toggleAccordion('noptin-accordion-block-<?php echo esc_attr( $id ); ?>__' + field.merge_tag)">
				<span class="title">{{field.label}}</span>
				<code class="badge">[[{{field.merge_tag}}]]</code>
				<span class="icon"></span>
			</button>
		</h4>
		<div :id="'noptin-accordion-block-<?php echo esc_attr( $id ); ?>__' + field.merge_tag" class="noptin-accordion-panel" hidden="hidden">
			<?php

				$field_types = get_noptin_custom_field_types();
				$field_types = wp_list_filter( $field_types, array( 'predefined' => false ) );

				// Change field type.
				$args = array(
					'el'          => 'select',
					'label'       => __( 'Field Type', 'newsletter-optin-box' ),
					'options'     => wp_list_pluck( $field_types, 'label' ),
					'restrict'    => '! isFieldPredefined(field)',
					'description' => __( 'Select the field type', 'newsletter-optin-box' ),
					'normal'      => true,
				);
				Noptin_Vue::render_el( 'field.type', $args );

				// Change field name.
				$args = array(
					'el'          => 'input',
					'label'       => __( 'Field Name', 'newsletter-optin-box' ),
					'description' => __( 'Enter a descriptive name for the field, for example, Phone Number', 'newsletter-optin-box' ),
					'@input'      => 'maybeUpdateMergeTag(field)',
				);
				Noptin_Vue::render_el( 'field.label', $args );

				// Placeholder.
				$args = array(
					'el'          => 'input',
					'label'       => __( 'Placeholder', 'newsletter-optin-box' ),
					'description' => __( 'Optional. Enter the default placeholder for this field', 'newsletter-optin-box' ),
					'restrict'    => "field.type == 'text' || field.type == 'textarea' || field.type == 'number' || field.type == 'email' || field.type == 'first_name' || field.type == 'last_name'",
				);
				Noptin_Vue::render_el( 'field.placeholder', $args );

				// Options.
				$args = array(
					'el'          => 'textarea',
					'label'       => __( 'Available Options', 'newsletter-optin-box' ),
					'description' => __( 'Enter one option per line. You can use pipes to separate values and labels.', 'newsletter-optin-box' ),
					'restrict'    => 'fieldAllowsOptions(field)',
					'placeholder' => implode( PHP_EOL, array( 'Option 1', 'Option 2', 'Option 3' ) ),
				);
				Noptin_Vue::render_el( 'field.options', $args );

				// Default value.
				$args = array(
					'el'          => 'input',
					'label'       => __( 'Default value', 'newsletter-optin-box' ),
					'description' => __( 'Optional. Enter the default value for this field', 'newsletter-optin-box' ),
				);
				Noptin_Vue::render_el( 'field.default_value', $args );

				do_action( 'noptin_custom_field_settings' );

				// Change visibility.
				$args = array(
					'el'          => 'input',
					'type'        => 'checkbox_alt',
					'label'       => __( 'Editable', 'newsletter-optin-box' ),
					'description' => __( "Can subscriber's view and edit this field?", 'newsletter-optin-box' ),
					'restrict'    => "field.merge_tag != 'email'",
				);
				Noptin_Vue::render_el( 'field.visible', $args );

				// Change required status.
				$args = array(
					'el'          => 'input',
					'type'        => 'checkbox_alt',
					'label'       => __( 'Required', 'newsletter-optin-box' ),
					'description' => __( 'Subscribers MUST fill this field whenever it is added to a subscription form.', 'newsletter-optin-box' ),
					'restrict'    => "field.merge_tag != 'email'",
				);
				Noptin_Vue::render_el( 'field.required', $args );

			?>

			<a href="#" v-if="! isFieldPredefined(field)" class="noptin-field-editor-delete" @click.prevent="removeField(field)"><?php esc_html_e( 'Delete Field', 'newsletter-optin-box' ); ?></a>
			<span v-if="! isFieldFirst(field)">
				<span v-if="field.merge_tag != 'email'">&nbsp;|&nbsp;</span>
				<a href="#"  @click.prevent="moveUp(field)"><?php esc_html_e( 'Move Up', 'newsletter-optin-box' ); ?></a>
			</span>
			<span v-if="! isFieldLast(field)">&nbsp;|&nbsp;
				<a href="#"  @click.prevent="moveDown(field)"><?php esc_html_e( 'Move Down', 'newsletter-optin-box' ); ?></a>
			</span>
		</div>
	</span>

</div>
<p>
	<button class="button noptin-button-standout" type="button" @click.prevent="addField()"><?php esc_html_e( 'Add Field', 'newsletter-optin-box' ); ?></button>
</p>
