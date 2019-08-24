(function ($) {

	if( 'undefined' == typeof noptinEditor ) {
		noptinEditor = {}
	}

	//List filter
	$(document).ready(function () {
		$(".noptin-list-filter input").on("keyup", function () {
			var value = $(this).val().toLowerCase();
			$('.noptin-list-table tbody tr').filter(function () {
				$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
			});
		});

	});

	var swatches = require('vue-swatches');
	var VueQuillEditor = require('vue-quill-editor');
	var Popover = require('vue-popperjs');
	var dragula = require('dragula');
	var noptinFind = require('lodash.find');

	//Color swatches
	Vue.component('noptin-swatch', swatches.default);


	//Quill Editor
	Vue.use(VueQuillEditor)

	//Tooltips
	Vue.component('noptin-tooltip', Popover);

	//Use divs instead of paragraphs
	var Block = VueQuillEditor.Quill.import('blots/block');
	Block.tagName = 'DIV';
	VueQuillEditor.Quill.register(Block, true);

	//helper functions
	var noptin = {
		templateData: function (key) {

			var data = {}

			if ( noptinEditor && noptinEditor.templates[key]) {
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
				instance.formWidth  = '300px'
				instance.singleLine = false
				return;
			}

			if (instance.optinType == 'popup') {
				instance.formWidth = '520px'
				instance.formHeight = '280px'
				return;
			}

			instance.formHeight = '250px'
			instance.formWidth = '520px'

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

	//Register dragula directive
	Vue.directive('noptin-dragula', {
		inserted: function (container, binding) {

			var list = binding.value
			var self = this;
			var dragIndex;
			var dragElm;
			this.drake = dragula([container], {
				revertOnSpill: true
			});

			this.drake.on('drag', function (el, source) {
				dragElm = el;
				dragIndex = domIndexOf(el, source);
			});

			this.drake.on('drop', function (dropElm, target, source) {
				if (!target) return;
				var dropIndex = domIndexOf(dropElm, target);
				if (target === container) {
					list.splice(dropIndex, 0, list.splice(dragIndex, 1)[0]);
				}
				refreshModel();

			})

			this.drake.on('cancel', refreshModel)
			this.drake.on('remove', refreshModel)

			function refreshModel() {
				// trigger rerendering of the v-for items to keep the dom elements under vue's control
				//self.vm[self.expression] = JSON.parse(JSON.stringify(self.vm[self.expression]))
			}

			function domIndexOf(child, parent) {
				return Array.prototype.indexOf.call(parent.children, child);
			}

		},
		unbind: function () {
			this.drake.destroy()
		}
	})


	Vue.component('field-editor', {
		props: ['fields','fieldTypes'],
		template: '#noptinFieldEditorTemplate',
		data: function () {
			return {}
		},
		methods: {
			addField: function () {
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
			removeField: function (item) {

				var key = this.fields.indexOf(item)
				if (key > -1) {
					this.fields.splice(key, 1)
				}

			},
			shallowCopy: function (obj) {
				return $.extend({}, obj)
			},
			getDefaultLabel: function (fieldType) {

				var data = noptinFind( this.fieldTypes, function( obj ){
					return obj.type === fieldType
				})

				if( data ) {
					return data['label']
				}

				return fieldType
			},
			expandField: function (id) {
				var el = $('#' + id)

				//toggle arrows
				$(el).find('.dashicons-arrow-up-alt2').show()
				$(el).find('.dashicons-arrow-down-alt2').hide()

				//slide down the body
				$(el).find('.noptin-field-editor-body').slideDown()
			},
			collapseField: function (id) {
				var el = $('#' + id)

				//toggle arrows
				$(el).find('.dashicons-arrow-up-alt2').hide()
				$(el).find('.dashicons-arrow-down-alt2').show()

				//slide up the body
				$(el).find('.noptin-field-editor-body').slideUp()
			},
			collapseAll: function (id) {
				var that = this

				$.each(this.fields, function (index, value) {
					that.collapseField(value.key)
				});
			}
		},

	})

	Vue.component('noptinform', {
		props: noptinEditor.design_props,
		template: '#noptinFormTemplate',
		data: function () {
			return {}
		}
	})

	var editorInstances = {}
	Vue.component('noptineditor', {
		props: ['value', 'id'],
		template: '<textarea :id="id"><slot></slot></textarea>',
		mounted: function () {
			var vmEditor = this
			var el = jQuery(this.$el)
			var editor = wp.codeEditor.initialize(el)
			editor.codemirror.on('change', function (cm, change) {
				vmEditor.$emit('input', cm.getValue())
			})
			editorInstances[this.id] = editor
			editorInstances[this.id].codemirror.getDoc().setValue(this.value);
		},
		watch: {
			value: function (value) {
				if (editorInstances[this.id]) {

					var editor = editorInstances[this.id].codemirror.getDoc()

					//set if values are not ===
					if (value != editor.getValue()) {
						editor.setValue(value)
					}

				}
			},
		}
	})

	Vue.component('noptin-select', {
		props: ['value'],
		template: '<select style="width: 100%;"><slot></slot></select>',
		mounted: function () {
			var that = this

			$(this.$el)

      			// init select2
				  .select2({width: 'resolve'})
				  .val(this.value)
				  .trigger('change')

				  // emit event on change.
				  .on('change', function () {
					that.$emit('input', this.value)
				  })


		},
		watch: {
			value: function (value) {
				// update value
				$(this.$el)
				.val(value)
				.trigger('change')
			},
		},
		destroyed: function () {
			$(this.$el).off().select2('destroy')
		}
	})

	var vm = new Vue({
		el: '#noptin-form-editor',
		data: jQuery.extend(true, {}, noptinEditor.data),
		computed: {
			_onlyShowOn: function () {
				return this.onlyShowOn && this.onlyShowOn.length > 0
			}
		},
		methods: {

			togglePanel: function (id) {

				var el = $('#' + id)
				var isOpen = $(el).hasClass('open')

				//toggle arrows
				$(el).find('> .noptin-popup-editor-panel-header .dashicons-arrow-up-alt2').slideToggle()
				$(el).find('> .noptin-popup-editor-panel-header .dashicons-arrow-down-alt2').slideToggle()

				//toggle the body with a sliding motion
				$(el).find('> .noptin-popup-editor-panel-body').slideToggle()

				//Toggle the open class
				$(el).toggleClass('open')


			},
			previewPopup: function () {
				this.isPreviewShowing = true
				var _html = jQuery('.noptin-popup-wrapper').html()
				jQuery("#noptin-popup-preview")
					.html(_html)
					.addClass('noptin-preview-showing')
					.find('.noptin-popup-close')
					.show()

				//Hide popup when user clicks outside
				jQuery("#noptin-popup-preview")
					.off('noptin-popup')
					.on('click', function (e) {
						var container = jQuery(this).find(".noptin-popup-form-wrapper");

						// if the target of the click isn't the container nor a descendant of the container
						if (!container.is(e.target) && container.has(e.target).length === 0) {
							vm.closePopup()
						}
					});
			},
			closePopup: function () {
				this.isPreviewShowing = false
				jQuery("#noptin-popup-preview").removeClass('noptin-preview-showing').html('')
			},
			saveAsTemplate: function () {
				var saveText = this.saveAsTemplateText
				this.saveAsTemplateText = this.savingTemplateText;
				var that = this

				jQuery.post(noptinEditor.ajaxurl, {
					_ajax_nonce: noptinEditor.nonce,
					action: "noptin_save_optin_form_as_template",
					state: vm.$data
				})
					.done(function () {
						that.showSuccess(that.savingTemplateSuccess)
						that.saveAsTemplateText = saveText
					})
					.fail(function () {
						that.showError(that.savingTemplateError)
						that.saveAsTemplateText = saveText
					})

			},

			upload_image: function (key) {
				var image = wp.media({
					title: 'Upload Image',
					multiple: false
				})
					.open()
					.on('select', function (e) {
						var uploaded_image = image.state().get('selection').first();
						vm[key] = uploaded_image.toJSON().sizes.thumbnail.url;
					})
			},
			showSuccess: function (msg) {
				this.hasSuccess = true;
				this.Success = msg;

				setTimeout(function () {
					vm.hasSuccess = false;
					vm.Success = '';
				}, 5000)
			},
			showError: function (msg) {
				this.hasError = true;
				this.Error = msg;

				setTimeout(function () {
					vm.hasError = false;
					vm.Error = '';
				}, 5000)
			},
			publish: function () {
				this.optinStatus = true
			},
			unpublish: function () {
				this.optinStatus = false
			},
			save: function () {
				var saveText = this.saveText
				this.saveText = this.savingText;
				var that = this

				jQuery.post(noptinEditor.ajaxurl, {
					_ajax_nonce: noptinEditor.nonce,
					action: "noptin_save_optin_form",
					state: vm.$data,
					html: jQuery('.noptin-popup-wrapper').html()
				})
					.done(function () {
						that.showSuccess(that.savingSuccess)
						that.saveText = saveText
					})
					.fail(function () {
						that.showError(that.savingError)
						that.saveText = saveText
					})

			}
		},

		watch: {
			Template: function () {
				var template = noptin.templateData(this.Template)
				noptin.applyTemplate(template, this)
			},
			optinStatus: function () {
				this.save()
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
			noptin.updateCustomCss(this.CSS)
			jQuery('.noptin-form-designer-loader').hide()
			jQuery(this.$el).find('.noptin-popup-editor-main-preview-name-textarea').focus()
		},
	})

	var vmQuick = new Vue({
		el: '#noptin-quick-form-editor',
		data: jQuery.extend(true, {}, noptinEditor.data),
		computed: {
			showingFullName: function () {
				return this.showNameField && !this.firstLastName
			},
			showingSingleName: function () {
				return this.showNameField && this.firstLastName
			},
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


	if( 'undefined' == typeof noptinSettings ) {
		noptinSettings = {}
	}

	window.noptinSettingsApp = new Vue({
		el: '#noptin-settings-app',
		data: jQuery.extend(true, {}, noptinSettings),
		computed: {
			_onlyShowOn: function () {
				return this.onlyShowOn && this.onlyShowOn.length > 0
			}
		},
		methods: {

			saveSettings: function () {
				var saveText = this.saveAsTemplateText
				this.saveAsTemplateText = this.savingTemplateText;
				var that = this

				jQuery.post(noptinEditor.ajaxurl, {
					_ajax_nonce: noptinEditor.nonce,
					action: "noptin_save_optin_form_as_template",
					state: vm.$data
				})
					.done(function () {
						that.showSuccess(that.savingTemplateSuccess)
						that.saveAsTemplateText = saveText
					})
					.fail(function () {
						that.showError(that.savingTemplateError)
						that.saveAsTemplateText = saveText
					})

			},

			upload_image: function (key) {
				var that = this;
				var image = wp.media({
					title: 'Upload Image',
					multiple: false
				})

					.open()
					.on('select', function (e) {
						var uploaded_image = image.state().get('selection').first();
						that[key] = uploaded_image.toJSON().sizes.thumbnail.url;
					})
			},
			showSuccess: function (msg) {
				this.hasSuccess = true;
				this.Success = msg;
				var that = this;
				setTimeout(function () {
					that.hasSuccess = false;
					that.Success = '';
				}, 5000)
			},
			showError: function (msg) {
				this.hasError = true;
				this.Error = msg;
				var that = this;

				setTimeout(function () {
					that.hasError = false;
					that.Error = '';
				}, 5000)
			},

		},

		mounted: function () {},
	})

	$('.noptin-tip').tooltipster();

})(jQuery);
