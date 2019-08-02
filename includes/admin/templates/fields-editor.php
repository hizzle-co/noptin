<div class="noptin-field-editor">
  <button type="button" class="button button-secondary" @click="addField">Add Field</button>

  <ul v-noptin-dragula="fields">
    <li v-for="field in fields" :key="field.key" class="noptin-field-editor-field" :id="field.key" :class="field.type">
		<div class="noptin-field-editor-header">
			<span class="noptin-field-editor-title">{{ field.label }}</span>
			<span @click="collapseField(field.key)" class="dashicons dashicons-arrow-up-alt2" style="display:none"></span>
            <span @click="expandField(field.key)" class="dashicons dashicons-arrow-down-alt2" style="display:inline-block"></span>
		</div>
		<div class="noptin-field-editor-body" style="display:none">
			<div class="noptin-select-wrapper">
				<label>Type
					<noptin-select
						:reduce="reduceOption"
						:clearable='false'
						:options='fieldTypes'
						v-model="field.type"
						:searchable='false'
						@change="field.label=field.type">
					</noptin-select>
				</label>
			</div>
			<div class="noptin-text-wrapper">
				<label>Label<input type="text" v-model="field.label"/></label>
			</div>
			<div class="noptin-text-wrapper" v-if="hasCustomName(field.type)">
				<label>Name<input type="text" v-model="field.name"/></label>
			</div>

			<label class="noptin-checkbox-wrapper">
				<input type="checkbox" class='screen-reader-text' v-model="field.require"/>
				<span class='noptin-checkmark'></span>
				<span class='noptin-label'>Is this field required?</span>
			</label>
			<a href="#" class="noptin-field-editor-delete" @click.prevent="removeField(field)">Delete Field</a>

		</div>

    </li>
  </ul>

</div>
