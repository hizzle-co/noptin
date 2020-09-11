<template>
	<div>
		<noptin-select v-if="is( 'select' )" :items="options" v-model="currentValue" v-bind="$attrs" :label="opts.label" :tooltip="opts.tooltip" :multiple="opts.multiselect"></noptin-select>

		<div class="field-wrapper noptin-textarea-wrapper" v-else-if="is( 'textarea' )">
			<label class="noptin-textarea-label"><span v-html="opts.label" class="noptin-label"></span></label>
			<div class="noptin-content">
				<textarea v-model="currentValue" v-bind="$attrs" :placeholder="opts.placeholder"></textarea>
			</div>
		</div>

		<div v-else-if="is( 'input' )">
			<color-picker v-if="isType( 'color' )" v-model="currentValue" v-bind="$attrs" :label="opts.label"></color-picker>
			<v-checkbox v-bind="$attrs"  v-else-if="isType( 'checkbox' ) || isType( 'checkbox_alt' )" v-model="currentValue" dense :hint="opts.tooltip">
				<template v-slot:label>
					<span v-html="opts.label"></span>
					<noptin-tip :tooltip="opts.tooltip">&nbsp;</noptin-tip>
				</template>
			</v-checkbox>
			<v-switch :label="opts.label" v-bind="$attrs" v-model="currentValue" v-else-if="isType( 'switch' )" dense :hint="opts.tooltip"></v-switch>
			<v-text-field :label="opts.label" :append-icon="mdiUpload" placeholder="http://example.com/image.jpg" @click:append="upload_image()" v-model="currentValue" v-else-if="isType( 'image' )" :hint="opts.tooltip"></v-text-field>
			<label class="noptin-text-wrapper field-wrapper" v-else>
				<span class="noptin-label">
					<span v-html="opts.label"></span>
					<noptin-tip :tooltip="opts.tooltip">&nbsp;</noptin-tip>
				</span>
				<div class="noptin-content">
					<input :placeholder="opts.placeholder" type="text" class="noptin-input-box" v-model="currentValue" v-bind="$attrs" autocomplete="off" />
				</div>
			</label>
		</div>
		<div v-else-if="is('radio_button')">
			<span>{{opts.label}}</span>
			<v-chip-group column v-model="currentValue" active-class="primary--text">
				<v-chip v-for="(label,value) in opts.options" :value="value" :key="value">{{label}}</v-chip>
			</v-chip-group>
		</div>

		<div v-else-if="is('multi_radio_button')">
			<span>{{opts.label}}</span>
			<v-chip-group multiple column v-model="currentValue" active-class="primary--text">
				<v-chip v-for="(label,value) in opts.options" :value="value" :key="value" filter>{{label}}</v-chip>
			</v-chip-group>
		</div>

		<div v-else-if="is('multi_checkbox')">
			<span>{{opts.label}}</span>
			<v-checkbox v-model="currentValue" v-for="(label, name) in opts.options" :key="name" :label="label" dense :value="name"></v-checkbox>
		</div>

		<div v-else-if="is('editor')" class="noptin-textarea-wrapper">
			<label>
				<span v-html="opts.label"></span>
				<noptin-tip :tooltip="opts.tooltip"></noptin-tip>
			</label>
			<noptineditor v-model="currentValue"></noptineditor>
		</div>

		<typography v-else-if="is( 'typography' )" :label="opts.label" v-model="currentValue"/>

		<advanced-typography v-else-if="is( 'advanced-typography' )" :label="opts.label" v-model="currentValue"/>

		<border v-else-if="is( 'border' )" :label="opts.label" v-model="currentValue"/>

		<p v-else-if="is( 'paragraph' )" v-html="opts.content"></p>

		<div v-else>{{opts}}</div>

	</div>
</template>

<script>

	import colorPicker from './color-picker.vue'
	import noptinTip from './tooltip.vue'
	import noptinSelect from './select.vue'
	import noptinEditorComponent from './css-editor.vue'
	import typography from './typography.vue'
	import border from './border.vue'
	import advancedTypography from './advanced-typography.vue'
	import { mdiInformation, mdiUpload } from '@mdi/js'

  	export default {
		props: ['value','opts','el','label','placeholder','restrict', 'tooltip'],

		// We do not want the root element of a component to inherit attributes
		inheritAttrs: false,

		data: () => ({
			mdiInformation,
			mdiUpload
		}),

		components: {
			'color-picker': colorPicker,
			'noptineditor': noptinEditorComponent,
			'advanced-typography': advancedTypography,
			typography,
			border,
			noptinTip,
			noptinSelect
		},

		computed: {
			options() {
				let options = []

				Object.keys( this.opts.options ).forEach( key => {
					options.push({
						text: this.opts.options[ key ],
						value: key
					})
				});

				return options
			},
			currentValue: {

				get () {
					return this.value
				},

				set ( newValue ) {
					this.updateValue( newValue )
				}
			}
		},

		methods: {

			is( el ) {
				return this.opts.el == el
			},

			isType( type ) {
				return this.opts.type && this.opts.type == type
			},

			updateValue( newValue ) {
				if ( this.currentValue != newValue ) {
					this.$emit('input', newValue);
				}
			},
			
			// Uploads an image via the WordPress media modal.
			upload_image() {
				let size = 'thumbnail'

				if ( this.opts.size ) {
					size = this.opts.size
				}

				var image = wp.media({
					title: 'Upload Image',
					multiple: false
				})
					.open()
					.on('select', (e) => {
						let uploaded_image = image.state().get('selection').first();

						if ( uploaded_image.toJSON().sizes[size] ) {
							this.updateValue( uploaded_image.toJSON().sizes[size].url );
						} else {
							this.updateValue( uploaded_image.toJSON().sizes['full'].url );
						}
					})
			},

		},

	}

	/**
	 * Form Appearance - Border (size,radius,type,color - Hover), Background ( Image, color, video )
	 * 
	 * Note, Prefix, Title, Sub-title, Description - Show/Hide, Text area, typography (size,color,family,alignment)
	 * Button - Label, Color, Border (size,radius,type,color - Hover), background
	 * icon/image - URL, Position, Opacity
	 * Fields - single line- hide, gdpr, Fields - 
	 * Templates - show in a popup, checkbox
	 * Another settings bar for - 
	 */
</script>
