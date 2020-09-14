export default {

	templateData (key) {

		var data = {}

		if (noptinEditor && noptinEditor.templates[key]) {
			var template = noptinEditor.templates[key]['data']

			Object.keys(template).forEach( (key) => {
				data[key] = template[key]
			})

		}
		return data
	},

	applyTemplate (template, instance) {

		Object.keys(template).forEach( (key) => {
			console.log(template[key])
			instance[key] = template[key]
		})

		instance.hideFields = false
		instance.gdprCheckbox = false

		this.updateFormSizes(instance)

	},

	updateFormSizes (instance) {

		if (instance.optinType == 'sidebar') {
			instance.formHeight = '400px'
			instance.formWidth = '300px'
			instance.singleLine = false
			return;
		}

		if (instance.optinType == 'popup') {
			instance.formWidth = '620px'
			instance.formHeight = '280px'
			return;
		}

		if (instance.optinType == 'slide_in') {
			instance.formWidth = '400px'
			instance.formHeight = '280px'
			return;
		}

		instance.formHeight = '280px'
		instance.formWidth = '620px'

	},

	updateCustomCss (css) {
		jQuery('#formCustomCSS').text(css)
	},

	getColorThemeOptions () {
		var themes = []

		Object.keys(noptinEditor.color_themes).forEach( (key) => {
			var theme = {
				text: key,
				value: noptinEditor.color_themes[key],
				imageSrc: noptin_params.icon,
				//description: "Description with Facebook",

			}
			themes.push(theme)
		})

		return themes
	},

	getColorTheme (instance) {
		return instance.colorTheme.split(" ")
	},

	changeColorTheme (instance) {

		var colors = this.getColorTheme(instance)

		if (colors.length) {
			instance.noptinFormBg = colors[0]
			instance.formBorder.border_color = colors[2]
			instance.noptinButtonColor = colors[0]
			instance.noptinButtonBg = colors[1]
			instance.titleColor = colors[1]
			instance.descriptionColor = colors[1]
			instance.noteColor = colors[1]
		}

	},

	getFormData (form) {

		let data = {},
			fields = jQuery(form).serializeArray()

		jQuery.each(fields, (i, field) => {
			data[field.name] = field.value
		});

		return data
	},

}
