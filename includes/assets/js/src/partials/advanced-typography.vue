<template>
	<div class="d-flex">
		<label class="flex-grow-1 flex-shrink-0">{{label}}</label>
		<slot></slot>
		<popper trigger="clickToOpen" :options="{ placement: 'left' }">
			<v-card class="popper">

				<v-card flat color="transparent">
					<v-subheader>Alignment</v-subheader>
					<v-card-text class="pt-0">
						<v-btn-toggle group dense v-model="alignment">
							<v-btn value="left">
								<v-icon>{{mdiFormatAlignLeft}}</v-icon>
							</v-btn>

							<v-btn value="center">
								<v-icon>{{mdiFormatAlignCenter}}</v-icon>
							</v-btn>

							<v-btn value="right">
								<v-icon>{{mdiFormatAlignRight}}</v-icon>
							</v-btn>

							<v-btn value="justify">
								<v-icon>{{mdiFormatAlignJustify}}</v-icon>
							</v-btn>
						</v-btn-toggle>
					</v-card-text>
				</v-card>

				<spacing label="Margin" v-model="margin" space="margin"></spacing>

				<spacing label="Padding" v-model="padding" space="padding"></spacing>

				<v-card flat color="transparent">
					<v-card-text class="pt-0 pb-0">
						<label class="noptin-text-wrapper field-wrapper">
							<span class="noptin-label">
								<span>CSS Classes</span>
							</span>
							<div class="noptin-content">
								<input placeholder="class-1 class-2" class="noptin-input-box" v-model="classes" type="text" />
							</div>
						</label>
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
	import spacing from './spacing.vue';
	import {
				mdiPencilBox,
				mdiFormatAlignCenter, 
				mdiFormatAlignJustify, 
				mdiFormatAlignLeft, 
				mdiFormatAlignRight 
			} from '@mdi/js';

  	export default {

		// We do not want the root element of a component to inherit attributes
		inheritAttrs: false,

		props: ['label','value'],

		data: () => ({
			mdiPencilBox,
			mdiFormatAlignCenter, 
			mdiFormatAlignJustify, 
			mdiFormatAlignLeft, 
			mdiFormatAlignRight 
		}),

		computed: {

			alignment: {

				get () {
					return this.value.alignment
				},

				set ( alignment ) {
					this.update('alignment', alignment);
				}
			},

			classes: {

				get () {
					return this.value.classes
				},

				set ( classes ) {
					this.update('classes', classes);
				}
			},

			margin: {

				get () {
					if ( this.value.margin ) {
						return this.value.margin
					}
					return {}
				},

				set ( margin ) {
					this.update('margin', margin);
				}
			},

			padding: {

				get () {
					if ( this.value.padding ) {
						return this.value.padding
					}
					return {}
				},

				set ( padding ) {
					this.update('padding', padding);
				}
			},

		},

		components: {
			'popper': Popper,
			spacing,
		},

		methods: {

            getStyles( styles ) {
				let generated = ''

				if ( styles.padding && styles.padding.generated ) {
					generated = this.padding.generated
				}

				if ( styles.margin && styles.margin.generated ) {
					generated = `${generated} ${this.margin.generated}`
				}

				if ( styles.alignment ) {
					generated = `${generated} text-align: ${styles.alignment};`
				}

				return generated
			},

			update( key, value ) {
				let styles = jQuery.extend( true, {}, this.value )
				styles[ key ] = value
				styles['generated'] = this.getStyles( styles )
				this.$emit('input', styles);
			}
		}
  	}
</script>