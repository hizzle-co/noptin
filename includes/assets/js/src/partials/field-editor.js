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

	data() {
		return {}
	},

	methods: {
		addField() {
			var total = this.fields.length
			var rand = Math.random() + total
			var key = 'field_' + rand.toString(36).replace(/[^a-z]+/g, '')
			this.fields.push(
				{
					type: {
						label: 'Label',
						name: key,
						type: 'text',
						options: 'Label 1 | Value 1, Label 2 | Value 2',
					},
					require: false,
					key: key,
				}
			)

		},
		removeField(item) {

			var key = this.fields.indexOf(item)
			if (key > -1) {
				this.fields.splice(key, 1)
			}

		},
		shallowCopy(obj) {
			return jQuery.extend({}, obj)
		},
		getDefaultLabel(fieldType) {

			var data = noptinFind(this.fieldTypes, (obj) => {
				return obj.type === fieldType
			})

			if (data) {
				return data['label']
			}

			return fieldType
		},

	},

	filters: {
		optionize: function (value) {
		  if (!value) return ''

		  value = value.toString().split('|').splice(0,1).join('')
		  return value.toString().trim()
		},
		formatMergeTag: function (value) {
			if (!value) return ''

			return '[[' + value.toString().trim() + ']]'
		}
	}
}
