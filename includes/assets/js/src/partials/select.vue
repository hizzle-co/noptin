<template>
	<div :id="id">
    	<div class="noptin-select-wrapper field-wrapper" v-if="!multiple">
			<label class="noptin-select-label">
				<span v-html="label"></span>
				<noptin-tip :tooltip="tooltip">&nbsp;</noptin-tip>
			</label>
			<div class="noptin-content">
				<select v-model="currentValue" v-bind="$attrs">
					<option v-for="item in items" :key="item.text" :value="item.value">{{item.text}}</option>
				</select>
			</div>
		</div>

		<v-select v-if="multiple" :attach="'#' + id" :items="items" v-model="currentValue" v-bind="$attrs" :label="label" multiple dense outlined>
			<template v-slot:prepend-inner>
				<noptin-tip :tooltip="tooltip"></noptin-tip>
			</template>
		</v-select>
    </div>
</template>

<script>

	import noptinTip from './tooltip.vue';

  	export default {
		props: ['items', 'value', 'label', 'multiple', 'tooltip'],

		computed: {

			currentValue: {

				get () {
					return this.value
				},

				set ( newValue ) {
					if ( this.value != newValue ) {
						this.$emit('input', newValue);
					}
				}
			}

		},

		components: {
			noptinTip
		},

		data () {

			return {
				id : Math.random().toString(36).replace(/[^a-z]+/g, '')
			}
		},

	}
	  
</script>
