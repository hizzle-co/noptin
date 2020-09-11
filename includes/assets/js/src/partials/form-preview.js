import tempForm from './noptin-temp-form.vue'
export default {
	template: jQuery( '#noptinOptinFormTemplate' ).html(),

	props: noptinEditor.design_props,

	// We do not want the root element of a component to inherit attributes
	inheritAttrs: false,

	components: {
		'noptin-temp-form': tempForm,
	},

	data () {
    	return {}
	},

	methods: {

		updateValue( prop, value ) {
			this.$emit('updatevalue', { prop, value } );
		}

	},

}
