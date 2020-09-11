<template>

	<div class="d-flex mb-2">
		<label class="flex-grow-1 flex-shrink-0">{{label}}</label>
		<slot></slot>
		<popper trigger="clickToOpen">

			<v-card class="popper">
				<v-color-picker :value="value" v-bind="$attrs" @input="updateValue" mode="hexa" hide-mode-switch></v-color-picker>
			</v-card>

			<div slot="reference" class="d-flex mb-2">
				<v-btn icon>
					<v-icon :color="value=='#FFFFFF' ? '#f1f1f1' : value">{{mdiPencilBox}}</v-icon>
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

		props: ['label','value'],

		components: {
			'popper': Popper
		},

		data: () => ({
			mdiPencilBox
		}),

		methods: {
            updateValue( newValue ) {

				if ( newValue.hex ) {
					this.$emit('input', newValue.hex);
				} else {
					this.$emit('input', newValue);
				}
            }
		}
  	}
</script>