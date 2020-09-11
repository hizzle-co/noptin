<template>

	<div class="d-flex">
		<v-subheader class="flex-grow-1 flex-shrink-0 text-left">{{label}}</v-subheader>
		<slot></slot>
		<popper trigger="clickToOpen" :options="{ placement: 'left' }">

			<v-card class="popper">
				<v-card flat color="transparent">
					<v-subheader>Top</v-subheader>
					<v-card-text class="pt-0">
						<v-slider min="0" max="50" v-model="top" :thumb-label="true" color="grey darken-4" track-color="grey" :hide-details="true"></v-slider>
					</v-card-text>
				</v-card>

				<v-card flat color="transparent">
					<v-subheader>Right</v-subheader>
					<v-card-text class="pt-0">
						<v-slider min="0" max="50" v-model="right" :thumb-label="true" color="grey darken-4" track-color="grey" :hide-details="true"></v-slider>
					</v-card-text>
				</v-card>

				<v-card flat color="transparent">
					<v-subheader>Bottom</v-subheader>
					<v-card-text class="pt-0">
						<v-slider min="0" max="50" v-model="bottom" :thumb-label="true" color="grey darken-4" track-color="grey" :hide-details="true"></v-slider>
					</v-card-text>
				</v-card>

				<v-card flat color="transparent">
					<v-subheader>Left</v-subheader>
					<v-card-text class="pt-0">
						<v-slider min="0" max="50" v-model="left" :thumb-label="true" color="grey darken-4" track-color="grey" :hide-details="true"></v-slider>
					</v-card-text>
				</v-card>

			</v-card>

			<div slot="reference" class="d-flex mt-2">
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

  	export default {

		// We do not want the root element of a component to inherit attributes
		inheritAttrs: false,

		props: ['value','label', 'space'],

		data: () => ({
			mdiPencilBox
		}),

		computed: {

			top: {

				get () {
					if ( this.value.top ) {
						return this.value.top
					}
					return 0
				},

				set ( top ) {
					this.update('top', top);
				}
			},

			left: {

				get () {
					if ( this.value.left ) {
						return this.value.left
					}
					return 0
				},

				set ( left ) {
					this.update('left', left);
				}
			},

			right: {

				get () {
					if ( this.value.right ) {
						return this.value.right
					}
					return 0
				},

				set ( right ) {
					this.update('right', right );
				}
			},

			bottom: {

				get () {
					if ( this.value.bottom ) {
						return this.value.bottom
					}
					return 0
				},

				set ( bottom ) {
					this.update('bottom', bottom );
				}
			},

		},

		components: {
			'popper': Popper
		},

		methods: {
            update( key, value ) {
				let styles    = jQuery.extend( true, {}, this.value )
				styles[ key ] = value
				styles.generated = this.getStyles( styles )
				this.$emit('input', styles);
			},

			getStyles( styles ) {
				let spacing = this.space
				let generated = ''

				if ( 'undefined' != typeof styles.top ) {
					generated = `${generated} ${spacing}-top: ${styles.top}px;`
				}

				if ( 'undefined' != typeof styles.left ) {
					generated = `${generated} ${spacing}-left: ${styles.left}px;`
				}

				if ( 'undefined' != typeof styles.right ) {
					generated = `${generated} ${spacing}-right: ${styles.right}px;`
				}

				if ( 'undefined' != typeof styles.bottom ) {
					generated = `${generated} ${spacing}-bottom: ${styles.bottom}px;`
				}

				return generated;
			}
		},

  	}
</script>
