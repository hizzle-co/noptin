<template>

	<div class="d-flex">
		<label class="flex-grow-1 flex-shrink-0">{{label}}</label>
		<slot></slot>
		<popper trigger="clickToOpen" :options="{ placement: 'left' }">

			<v-card class="popper">

				<v-card flat color="transparent">
					<v-subheader>Font Size</v-subheader>
					<v-card-text class="pt-0">
						<v-slider min="1" max="200" v-model="font_size" :thumb-label="true" color="grey darken-4" track-color="grey" :hide-details="true"></v-slider>
					</v-card-text>
				</v-card>

				<v-card flat color="transparent">
					<v-subheader>Font Weight</v-subheader>
					<v-card-text class="pt-0">
						<v-slider min="100" max="900" step="100" v-model="font_weight" :thumb-label="true" color="grey darken-4" track-color="grey" :hide-details="true"></v-slider>
					</v-card-text>
				</v-card>

				<v-card flat color="transparent">
					<v-subheader>Line Height</v-subheader>
					<v-card-text class="pt-0">
						<v-slider min="0.1" max="10" step="0.1" :thumb-label="true" v-model="line_height" color="grey darken-4" track-color="grey" :hide-details="true"></v-slider>
					</v-card-text>
				</v-card>

				<v-card flat color="transparent">
					<v-card-text class="pt-0">
						<noptin-select :items="decorations" v-model="decoration" label="Decoration"></noptin-select>
					</v-card-text>
				</v-card>

				<v-card flat color="transparent">
					<v-card-text class="pt-0">
						<noptin-select :items="styles" v-model="style" label="Style"></noptin-select>
					</v-card-text>
				</v-card>

				<v-card flat color="transparent">
					<v-card-text class="pt-0">
						<noptin-select :items="fonts" v-model="family" label="Family"></noptin-select>
					</v-card-text>
				</v-card>
			</v-card>

			<div slot="reference" class="d-flex">
				<v-btn icon>
					<v-icon>{{mdiPencilBox}}</v-icon>
				</v-btn>
			</div>

		</popper>
	</div>
</template>

<script>
	import Popper from 'vue-popperjs';
	import { mdiPencilBox } from '@mdi/js';
	import noptinSelect from './select.vue';

  	export default {

		// We do not want the root element of a component to inherit attributes
		inheritAttrs: false,

		data: () => ({
			mdiPencilBox
		}),

		props: ['value','label'],

		computed: {

			decorations () {
				return [
					{
						text: 'Default',
						value: '',
					},
					{
						text: 'Underline',
						value: 'underline',
					},
					{
						text: 'Overline',
						value: 'overline',
					},
					{
						text: 'Line Through',
						value: 'line-through',
					},
					{
						text: 'None',
						value: 'none',
					}
				]
			},

			styles() {
				return [
					{
						text: 'Default',
						value: '',
					},
					{
						text: 'Normal',
						value: 'normal',
					},
					{
						text: 'Italic',
						value: 'italic',
					},
					{
						text: 'Oblique',
						value: 'oblique',
					}
				]

			},

			fonts() {
				return [
					{
						text: 'Default',
						value: '',
					},
					{
						text: 'System',
						value: 'apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif',
					},
					{
						text: 'Georgia',
						value: 'Georgia, serif',
					},
					{
						text: 'Palatino Linotype',
						value: '"Palatino Linotype", "Book Antiqua", Palatino, serif',
					},
					{
						text: 'Times New Roman',
						value: '"Times New Roman", Times, serif',
					},
					{
						text: 'Arial',
						value: 'Arial, Helvetica, sans-serif',
					},
					{
						text: 'Arial Black',
						value: '"Arial Black", Gadget, sans-serif',
					},
					{
						text: 'Comic Sans MS',
						value: '"Comic Sans MS", cursive, sans-serif',
					},
					{
						text: 'Impact',
						value: 'Impact, Charcoal, sans-serif',
					},
					{
						text: 'Lucida Sans Unicode',
						value: '"Lucida Sans Unicode", "Lucida Grande", sans-serif',
					},
					{
						text: 'Tahoma',
						value: 'Tahoma, Geneva, sans-serif',
					},
					{
						text: 'Trebuchet MS',
						value: '"Trebuchet MS", Helvetica, sans-serif',
					},
					{
						text: 'Verdana',
						value: 'Verdana, Geneva, sans-serif',
					},
					{
						text: 'Courier New',
						value: '"Courier New", Courier, monospace',
					},
					{
						text: 'Lucida Console',
						value: '"Lucida Console", Monaco, monospace',
					},
				]

			},

			font_size: {

				get () {
					if ( this.value.font_size ) {
						return this.value.font_size
					}
					return '30'
				},

				set ( font_size ) {
					this.update('font_size', font_size);
				}
			},

			font_weight: {

				get () {
					if ( this.value.font_weight ) {
						return this.value.font_weight
					}
					return '600'
				},

				set ( font_weight ) {
					this.update('font_weight', font_weight);
				}
			},

			line_height: {

				get () {
					if ( this.value.line_height ) {
						return this.value.line_height
					}
					return '1.5'
				},

				set ( line_height ) {
					this.update('line_height', line_height );
				}
			},

			decoration: {

				get () {
					if ( this.value.decoration ) {
						return this.value.decoration
					}
					return 'none'
				},

				set ( decoration ) {
					this.update('decoration', decoration );
				}
			},

			style: {

				get () {
					if ( this.value.style ) {
						return this.value.style
					}
					return ''
				},

				set ( style ) {
					this.update('style', style );
				}
			},

			family: {

				get () {
					if ( this.value.family ) {
						return this.value.family
					}
					return ''
				},

				set ( family ) {
					this.update('family', family );
				}
			},

		},

		components: {
			'popper': Popper,
			noptinSelect
		},

		methods: {
            update( key, value ) {
				let styles = jQuery.extend( true, {}, this.value )
				styles[ key ] = value
				styles['generated'] = this.getStyles( styles )
				this.$emit('input', styles);
			},

			getStyles( styles ) {
				let generated = `font-size: ${styles.font_size}px;font-weight: ${styles.font_weight};line-height: ${styles.line_height};`;

				if ( styles.decoration ) {
					generated = `${generated} text-decoration: ${styles.decoration};`
				}

				if ( styles.style ) {
					generated = `${generated} font-style: ${styles.style};`
				}

				if ( styles.family ) {
					generated = `${generated} font-family: ${styles.family};`
				}

				return generated;
			}

		}
  	}
</script>
