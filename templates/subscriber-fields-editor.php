<p <?php echo $restrict; ?> class="description"><?php _e( 'Collect more information from your subscribers by adding custom fields.', 'newsletter-optin-box' ); ?>&nbsp;<a href="https://noptin.com/guide/email-subscribers/custom-fields/" target="_blank"><?php _e( 'Learn More', 'newsletter-optin-box' ); ?></a></p>
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
					'description' => __( 'Enter one option per line.', 'newsletter-optin-box' ),
					'restrict'    => 'fieldAllowsOptions(field)',
					'placeholder' => implode( PHP_EOL, array( 'Option 1', 'Option 2', 'Option 3' ) ),
				);
				Noptin_Vue::render_el( 'field.options', $args );

				foreach ( get_noptin_connection_providers() as $key => $connection ) {

					if ( empty( $connection->list_providers ) ) {
						continue;
					}

					$label = sprintf(
						__( '%s Equivalent', 'newsletter-optin-box' ),
						$connection->name
					);

					$placeholder = sprintf(
						__( 'Select %s Equivalent', 'newsletter-optin-box' ),
						$connection->name
					);

					if ( $connection->supports( 'universal_fields' ) ) {

						// Field args.
						$fields = $connection->list_providers->get_fields();

						if ( empty( $fields ) ) {
							continue;
						}

						$args = array(
							'el'          => 'select',
							'label'       => $label,
							'options'     => $fields,
							'restrict'    => '! isFieldPredefined(field) && ' . $connection->get_enable_integration_option_name(),
							'placeholder' => $placeholder,
						);
						Noptin_Vue::render_el( "field.$key", $args );

						continue;
					}

					foreach ( $connection->list_providers->get_lists() as $list ) {

						$fields = $list->get_fields();
						if ( empty( $fields ) ) {
							continue;
						}

						// Field args.
						$_list      = esc_attr( $list->get_id() );
						$_name      = esc_html( $list->get_name() );
						$list_field = "{$key}_{$_list}";
						$args       = array(
							'el'          => 'select',
							'label'       => $label . " ($_name)",
							'options'     => $fields,
							'restrict'    => '! isFieldPredefined(field) && ' . $connection->get_enable_integration_option_name(),
							'placeholder' => $placeholder,
						);

						Noptin_Vue::render_el( "field.$list_field", $args );

					}

				}

				do_action( 'noptin_custom_field_settings' );

				// Change visibility.
				$args = array(
					'el'          => 'input',
					'type'        => 'checkbox_alt',
					'label'       => __( 'Editable', 'newsletter-optin-box' ),
					'description' => __( "Can subscriber's view and edit this field?", 'newsletter-optin-box' ),
					'restrict'    => '! isFieldPredefined(field)',
				);
				Noptin_Vue::render_el( 'field.visible', $args );

				// Change admin visibility.
				$args = array(
					'el'          => 'input',
					'type'        => 'checkbox_alt',
					'label'       => __( 'Subscribers table', 'newsletter-optin-box' ),
					'description' => __( 'Display this field on the subscribers overview table', 'newsletter-optin-box' ),
					'restrict'    => "field.merge_tag != 'email'",
				);
				Noptin_Vue::render_el( 'field.subs_table', $args );
			?>

			<a href="#" v-if="! isFieldPredefined(field)" class="noptin-field-editor-delete" @click.prevent="removeField(field)"><?php _e( 'Delete Field', 'newsletter-optin-box' ); ?></a>
			<span v-if="! isFieldFirst(field)">&nbsp;|&nbsp;
				<a href="#"  @click.prevent="moveUp(field)"><?php _e( 'Move Up', 'newsletter-optin-box' ); ?></a>
			</span>
			<span v-if="! isFieldLast(field)">&nbsp;|&nbsp;
				<a href="#"  @click.prevent="moveDown(field)"><?php _e( 'Move Down', 'newsletter-optin-box' ); ?></a>
			</span>
		</div>
	</span>

</div>
<p>
	<button class="button noptin-button-standout" type="button" @click.prevent="addField()"><?php _e( 'Add Field', 'newsletter-optin-box' ); ?></button>
</p>
