import noptinFind from 'lodash.find'
import noptinSelectComponent from './noptin-select.vue'
import draggable from 'vuedraggable'
import noptinTip from './tooltip.vue'
import {
		VCard,
		VBtn,
		VExpansionPanels,
    	VExpansionPanel,
    	VExpansionPanelHeader,
		VExpansionPanelContent
	} from 'vuetify/lib';

export default {

	props: noptinEditor.field_props,
	template: '#noptinFieldEditorTemplate',

	// We do not want the root element of a component to inherit attributes
	inheritAttrs: false,

	components: {

		//Select2
		'noptin-select': noptinSelectComponent,
		draggable,
		VCard,
		VExpansionPanels,
		VExpansionPanel,
		VExpansionPanelHeader,
		VExpansionPanelContent,
		VBtn,
		noptinTip
	},

	data () {
    	return {}
	},

	methods: {
		addField () {
			var total = this.fields.length
			var rand = Math.random() + total
			var key = 'key-' + rand.toString(36).replace(/[^a-z]+/g, '')
				this.fields.push(
					{
						type: {
							label: 'Text',
							name: 'text',
							type: 'text'
						},
						require: false,
						key: key,
					}
				)

				this.collapseAll()
			},
			removeField (item) {

				var key = this.fields.indexOf(item)
				if (key > -1) {
					this.fields.splice(key, 1)
				}

			},
			shallowCopy (obj) {
				return jQuery.extend({}, obj)
			},
			getDefaultLabel (fieldType) {

				var data = noptinFind(this.fieldTypes,  (obj) => {
					return obj.type === fieldType
				})

				if (data) {
					return data['label']
				}

				return fieldType
			},

		},
}
