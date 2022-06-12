<?php defined( 'ABSPATH' ) || exit; ?>
<v-card flat class="noptin-field-editor">
		<draggable :list="fields" :component-data="{accordion:true, focusable:true, hover: true}" tag="v-expansion-panels" ghost-class="noptin-sortable-ghost" class="mb-4 pl-0">
			<v-expansion-panel v-for="field in fields" :key="field.key" :id="field.key" class="mb-1">
				<v-expansion-panel-header>{{field.type.label}}</v-expansion-panel-header>
				<v-expansion-panel-content class="mt-2">

					<?php

						$field_types = get_noptin_optin_field_types();

						// Change field type.
						$args = array(
							'el'      => 'select',
							'label'   => 'Type',
							'options' => wp_list_pluck( $field_types, 'label', 'type' ),
							'normal'  => true,
							'@change' => 'field.type.label = getDefaultLabel(field.type.type)',
						);
						$args = Noptin_Vue::sanitize_el( 'field.type.type', $args );
						Noptin_Vue::select( 'field.type.type', $args );

						// Print field types specific settings.
						foreach ( $field_types as $field_type ) {
							do_action( 'noptin_field_type_settings', $field_type, $field_types );
						}

						?>
						<a href="#" class="noptin-field-editor-delete" @click.prevent="removeField(field)"><?php esc_html_e( 'Delete Field', 'newsletter-optin-box' ); ?></a>
				</v-expansion-panel-content>
			</v-expansion-panel>
		</draggable>

	<v-btn outlined color="primary" @click.prevent="addField"><?php esc_html_e( 'Add Field', 'newsletter-optin-box' ); ?></v-btn>

</v-card>
