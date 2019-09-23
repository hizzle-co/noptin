export default {

	templateData: function (key) {

		var data = {}

		if (noptinEditor && noptinEditor.templates[key]) {
			var template = noptinEditor.templates[key]['data']

			Object.keys(template).forEach(function (key) {
				data[key] = template[key]
			})

		}
		return data
	},

	applyTemplate: function (template, instance) {

		Object.keys(template).forEach(function (key) {
			instance[key] = template[key]
		})

		noptin.updateFormSizes(instance)

	},

	updateFormSizes: function (instance) {

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

		instance.formHeight = '250px'
		instance.formWidth = '620px'

	},

	updateCustomCss: function (css) {
		$('#formCustomCSS').text(css)
	},

	getColorThemeOptions: function () {
		var themes = []

		Object.keys(noptinEditor.color_themes).forEach(function (key) {
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

	getColorTheme: function (instance) {
		return instance.colorTheme.split(" ")
	},

	changeColorTheme: function (instance) {

		var colors = noptin.getColorTheme(instance)

		if (colors.length) {
			instance.noptinFormBg = colors[0]
			instance.noptinFormBorderColor = colors[2]
			instance.noptinButtonColor = colors[0]
			instance.noptinButtonBg = colors[1]
			instance.titleColor = colors[1]
			instance.descriptionColor = colors[1]
			instance.noteColor = colors[1]
		}


	},

}
