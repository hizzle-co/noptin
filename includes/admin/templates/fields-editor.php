<div class="noptin-field-editor">
  <button type="button" class="button button-secondary" @click="addField"><?php _e( 'Add Field',  'newsletter-optin-box' ); ?></button>

  <draggable :list="fields" tag="ul" ghost-class="noptin-sortable-ghost">
    <li v-for="field in fields" :key="field.key" class="noptin-field-editor-field" :id="field.key">
		<div class="noptin-field-editor-header">
			<span class="noptin-field-editor-title">{{ field.type.label }}</span>
			<span @click="collapseField(field.key)" class="dashicons dashicons-arrow-up-alt2" style="display:none"></span>
            <span @click="expandField(field.key)" class="dashicons dashicons-arrow-down-alt2" style="display:inline-block"></span>
		</div>
		<div class="noptin-field-editor-body" style="display:none">
			<?php

				$field_types = get_noptin_optin_field_types();

				// Change field type.
				$args  = array(
					'el'        => 'select',
					'label'     => 'Type',
					'options'   => wp_list_pluck( $field_types, 'label', 'type' ),
					'@input'    => "field.type.label=getDefaultLabel(field.type.type)",
				);
				$args  = Noptin_Vue::sanitize_el( 'field.type.type', $args );
				Noptin_Vue::select( 'field.type.type', $args );

				// Print field types specific settings.
				foreach( $field_types as $field_type ) {
					do_action( 'noptin_field_type_settings', $field_type, $field_types );
				}

			?>

			<a href="#" class="noptin-field-editor-delete" @click.prevent="removeField(field)"><?php _e( 'Delete Field',  'newsletter-optin-box' ); ?></a>

		</div>

	</li>
	</draggable>

</div>
