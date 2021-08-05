<p <?php echo $restrict; ?> class="description"><?php _e( "Use this section to set the data you would like to collect from your email subscribers.", 'newsletter-optin-box' ); ?>&nbsp;<a href="https://noptin.com/guide/email-subscribers/custom-fields/"><?php _e( 'Learn More', 'newsletter-optin-box' ); ?></a></p>
<div <?php echo $restrict; ?> id="noptin-subscriber-fields-editor-available-fields" class="noptin-accordion-wrapper">

	<span v-for="field in <?php echo esc_attr( $id ); ?>">
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

				// Change field type.
				$args = array(
					'el'          => 'input',
					'label'       => __( 'Field Name', 'newsletter-optin-box' ),
					'description' => __( 'Enter a descriptive name for the field, for example, Phone Number', 'newsletter-optin-box' ),
					'@input'      => 'maybeUpdateMergeTag(field)',
				);
				Noptin_Vue::render_el( 'field.label', $args );

				// Options.
				$args = array(
					'el'          => 'textarea',
					'label'       => __( 'Available Options', 'newsletter-optin-box' ),
					'description' => __( 'Enter each available option on its own line', 'newsletter-optin-box' ),
					'restrict'    => 'fieldAllowsOptions(field)',
					'placeholder' => implode( PHP_EOL, array( 'Option 1', 'Option 2', 'Option 3' ) ),
				);
				Noptin_Vue::render_el( 'field.options', $args );

				// Fires before custom field settings.
				do_action( 'noptin_custom_field_settings' );

				// Change visibility.
				$args = array(
					'el'          => 'input',
					'type'        => 'checkbox_alt',
					'label'       => __( 'Editable', 'newsletter-optin-box' ),
					'description' => __( "Can subscriber's edit this field?", 'newsletter-optin-box' ),
					'restrict'    => '! isFieldPredefined(field)',
				);
				Noptin_Vue::render_el( 'field.visible', $args );

				// Change admin visibility.
				$args = array(
					'el'          => 'input',
					'type'        => 'checkbox_alt',
					'label'       => __( 'Subscribers table', 'newsletter-optin-box' ),
					'description' => __( 'Display this field on the subscribers overview table', 'newsletter-optin-box' ),
				);
				Noptin_Vue::render_el( 'field.subs_table', $args );
			?>

			<a href="#" v-if="! isFieldPredefined(field)" class="noptin-field-editor-delete" @click.prevent="removeField(field)"><?php _e( 'Delete Field', 'newsletter-optin-box' ); ?></a>
		</div>
	</span>

</div>
<p>
	<button class="button noptin-button-standout" type="button" @click.prevent="addField()"><?php _e( 'Add Field', 'newsletter-optin-box' ); ?></button>
</p>
