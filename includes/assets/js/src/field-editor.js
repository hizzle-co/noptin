import noptinFind from 'lodash.find'
import noptinSelectComponent from './noptin-select.vue'

export default {

	props: noptinEditor.field_props,
	template: '#noptinFieldEditorTemplate',

	components: {

		//Select2
		'noptin-select': noptinSelectComponent,

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
				this.expandField(key)
			},
			removeField (item) {

				var key = this.fields.indexOf(item)
				if (key > -1) {
					this.fields.splice(key, 1)
				}

			},
			shallowCopy (obj) {
				return $.extend({}, obj)
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
			expandField (id) {
				var el = $('#' + id)

				//toggle arrows
				$(el).find('.dashicons-arrow-up-alt2').show()
				$(el).find('.dashicons-arrow-down-alt2').hide()

				//slide down the body
				$(el).find('.noptin-field-editor-body').slideDown()
			},
			collapseField (id) {
				var el = $('#' + id)

				//toggle arrows
				$(el).find('.dashicons-arrow-up-alt2').hide()
				$(el).find('.dashicons-arrow-down-alt2').show()

				//slide up the body
				$(el).find('.noptin-field-editor-body').slideUp()
			},
			collapseAll (id) {
				var that = this

				$.each(this.fields, (index, value) => {
					that.collapseField(value.key)
				});
			}
		},
}
