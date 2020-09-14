<template>

	<div class="d-flex mb-2">
		<label class="flex-grow-1 flex-shrink-0">{{label}}</label>
		<slot></slot>
		<popper trigger="clickToOpen" :options="{ placement: 'left' }">

			<v-card class="popper border-settings-popup text-left">

				<v-card flat color="transparent">
					<v-card-text>
						<v-select attach=".border-settings-popup" :items="styles" v-model="style" label="Border Style" outlined dense :hide-details="true"></v-select>
					</v-card-text>
				</v-card>

				<v-card flat color="transparent">
					<v-subheader>Size</v-subheader>
					<v-card-text class="pt-0">
						<v-slider min="0" max="100" v-model="border_width" :thumb-label="true" color="grey darken-4" track-color="grey" :hide-details="true"></v-slider>
					</v-card-text>
				</v-card>

				<v-card flat color="transparent">
					<v-subheader>Rounding</v-subheader>
					<v-card-text class="pt-0">
						<v-slider min="0" max="300" step="1" :thumb-label="true" v-model="border_radius" color="grey darken-4" track-color="grey" :hide-details="true"></v-slider>
					</v-card-text>
				</v-card>

				<v-card flat color="transparent">
					<v-card-text class="pb-0">
						<color-picker v-model="border_color" label="Border Color"></color-picker>
					</v-card-text>
				</v-card>

			</v-card>

			<div slot="reference" class="d-flex mb-2">
				<v-btn icon>
					<v-icon>{{mdiPencilBox}}</v-icon>
				</v-btn>
			</div>

		</popper>
	</div>
</template>

<script>
	import Popper from 'vue-popperjs';
	import colorPicker from './color-picker.vue';
	import { mdiPencilBox } from '@mdi/js';

  	export default {

		// We do not want the root element of a component to inherit attributes
		inheritAttrs: false,

		props: ['value','label'],

		data: () => ({
			mdiPencilBox
		}),

		computed: {

			styles() {
				return [
					{
						text: 'Default',
						value: '',
					},
					{
						text: 'None',
						value: 'none',
					},
					{
						text: 'Dotted',
						value: 'dotted',
					},
					{
						text: 'Solid',
						value: 'solid',
					},
					{
						text: 'Dashed',
						value: 'dashed',
					},
					{
						text: 'Groove',
						value: 'groove',
					},
					{
						text: 'Ridged',
						value: 'ridge',
					},
					{
						text: 'Inset',
						value: 'inset',
					},
					{
						text: 'Outset',
						value: 'outset',
					}
				]

			},

			border_color: {

				get () {
					if ( this.value.border_color ) {
						return this.value.border_color
					}
					return '#f1f1f1'
				},

				set ( border_color ) {
					this.update('border_color', border_color);
				}
			},

			border_width: {

				get () {
					if ( this.value.border_width ) {
						return this.value.border_width
					}
					return '2'
				},

				set ( border_width ) {
					this.update('border_width', border_width);
				}
			},

			border_radius: {

				get () {
					if ( this.value.border_radius ) {
						return this.value.border_radius
					}
					return '0'
				},

				set ( border_radius ) {
					this.update('border_radius', border_radius );
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

		},

		components: {
			'popper': Popper,
			'color-picker': colorPicker
		},

		watch: {

			// Watch the value
			value : {
    			handler () {
					console.log( this.value )
					this.value.generated = this.getStyles( this.value )
				},
    			immediate: true
			},

		},

		methods: {
            update( key, value ) {
				let styles = jQuery.extend( true, {}, this.value )
				styles[ key ] = value
				styles['generated'] = this.getStyles( styles )
				this.$emit('input', styles);
			},

			getStyles( styles ) {
				let generated = '';

				if ( styles.style ) {
					generated = `border-style: ${styles.style};`
				}

				if ( styles.border_color ) {
					generated = `${generated} border-color: ${styles.border_color};`
				}

				if ( styles.border_width || styles.border_width == 0 ) {
					generated = `${generated} border-width: ${styles.border_width}px;`
				}

				if ( styles.border_radius || styles.border_radius == 0 ) {
					generated = `${generated} border-radius: ${styles.border_radius}px;`
				}

				return generated;
			}

		},

  	}
</script>
