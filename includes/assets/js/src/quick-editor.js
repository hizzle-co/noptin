import swatches from 'vue-swatches'
import popover from 'vue-popperjs'
import noptinMediumEditor from 'medium-editor'
import draggable from 'vuedraggable'
import fieldEditor from './field-editor.js'
import noptinForm from './noptin-form.js'
import noptin from './noptin.js'
import noptinEditorComponent from './css-editor.vue'
import noptinSelectComponent from './noptin-select.vue'

//Work in progress
var vmQuick = new Vue({

	components: {

		 //Drag drop
		draggable,

		 //Color swatches
		'noptin-swatch': swatches,

		 //Tooltips
		'noptin-tooltip': popover,

		//Optin fields editor
		'field-editor': fieldEditor,

		//Renders the optin forms
		'noptinform': noptinForm,

		//Custom CSS Editor
		'noptineditor': noptinEditorComponent,

		//Select2
		'noptin-select': noptinSelectComponent,

		//WYIWYG
		'noptin-rich-text': noptinMediumEditor,

	},

	el: '#noptin-quick-form-editor',

	data: jQuery.extend(true, {}, noptinEditor.data),

	computed: {

		_onlyShowOn: function () {
			return this.onlyShowOn && this.onlyShowOn.length > 0
		},

		titleEditorOptions: function () {
			return {
				theme: 'snow',
				modules: {
					toolbar: [
						['bold', 'italic', 'underline', 'strike'],
						[{ 'color': [] }, { 'background': [] }],
						[{ 'size': ['small', false, 'large', 'huge'] }],
						[{ 'align': [] }],
					]
				},
			}
		},

		descriptionEditorOptions: function () {
			return {
				theme: 'snow',
				modules: {
					toolbar: [
						['bold', 'italic', 'underline', 'strike'],
						[{ 'color': [] }, { 'background': [] }],
						[{ 'list': 'ordered' }, { 'list': 'bullet' }],
						[{ 'size': ['small', false, 'large', 'huge'] }],
						[{ 'align': [] }],
					]
				},
			}
		}
	},

	methods: {

		upload_image: function (key) {
			var image = wp.media({
				title: 'Upload Image',
				multiple: false
			})
				.open()
				.on('select', function (e) {
					var uploaded_image = image.state().get('selection').first();
					vmQuick[key] = uploaded_image.toJSON().sizes.thumbnail.url;
				})
		},
		showSuccess: function (msg) {
			this.hasSuccess = true;
			this.Success = msg;

			setTimeout(function () {
				vmQuick.hasSuccess = false;
				vmQuick.Success = '';
			}, 5000)
		},
		showError: function (msg) {
			this.hasError = true;
			this.Error = msg;

			setTimeout(function () {
				vmQuick.hasError = false;
				vmQuick.Error = '';
			}, 5000)
		},
		finalize: function () {
			this.currentStep = 'step_7'

			jQuery.post(noptinEditor.ajaxurl, {
				_ajax_nonce: noptinEditor.nonce,
				action: "noptin_save_optin_form",
				state: vmQuick.$data,
				html: jQuery('.noptin-popup-wrapper').html()
			})

		}
	},
	watch: {
		Template: function () {
			var template = noptin.templateData(this.Template)
			noptin.applyTemplate(template, this)
		},
		CSS: function () {
			noptin.updateCustomCss(this.CSS)
		},
		optinType: function () {
			noptin.updateFormSizes(this)
		},
		colorTheme: function () {
			noptin.changeColorTheme(this)
		}
	},
	mounted: function () {

		if(! noptinEditor.templates ) {
			return;
		}

		noptin.updateCustomCss(this.CSS)

		jQuery('#formCustomCSS').text(this.CSS)
		jQuery('.noptin-form-designer-loader').hide()
		$('.noptin-tip').tooltipster();

		var ddData = []

		Object.keys(noptinEditor.templates).forEach(function (key) {
			var template = {
				text: noptinEditor.templates[key]['title'],
				value: key,
				imageSrc: noptin_params.icon,
				//description: "Description with Facebook",

			}
			ddData.push(template)
		})

		$('.ddslickTemplates').ddslick({
			data: ddData,
			selectText: "Select A Template",
			onSelected: function (data) {
				vmQuick.Template = data.selectedData.value;
			}
		});

		var themes = noptin.getColorThemeOptions()

		$('.ddslickThemes').ddslick({
			data: themes,
			selectText: "Apply a theme",
			onSelected: function (data) {
				vmQuick.colorTheme = data.selectedData.value;
			}
		});

	},

})

export default vmQuick
