import swatches from 'vue-swatches'
import popover from 'vue-popperjs'
import noptin from './noptin.js'
import noptinEditorComponent from './css-editor.vue'
import noptinSelectComponent from './noptin-select.vue'

var settingsApp = new Vue({

	components: {

		 //Color swatches
		'noptin-swatch': swatches,

		 //Tooltips
		'noptin-tooltip': popover,

		//Custom CSS Editor
		'noptineditor': noptinEditorComponent,

		//Select2
		'noptin-select': noptinSelectComponent,

	},

	el: '#noptin-settings-app',

	data: jQuery.extend( true, {}, noptinSettings ),

	methods: {

		saveSettings () {

			var $ = jQuery

			//Provide visual feedback by fading the form
			$(this.$el).fadeTo("fast", 0.33);

			//Prepare state data
			var data = this.$data
			var error = this.error
			var saved = this.saved
			var el = this.$el

			//Hide form notices
			$(this.$el).find('.noptin-save-saved').hide()
			$(this.$el).find('.noptin-save-error').hide()

			//Post the state data to the server
			jQuery.post(noptin_params.ajaxurl, {
				_ajax_nonce: noptin_params.nonce,
				action: "noptin_save_options",
				state: data
			})

				//Show a success msg after we are done
				.done( () => {
					$(el)
						.fadeTo("fast", 1)
						.find('.noptin-save-saved')
						.show()
						.html(`<p>${saved}</p>`)
				})

				//Else alert the user about the error
				.fail( () => {
					$(el)
						.fadeTo("fast", 1)
						.find('.noptin-save-error')
						.show()
						.html(`<p>${error}</p>`)
				})

		},

		//Handles image uploads
		upload_image (key, size) {

			if ('undefined' == typeof size) {
				size = 'thumbnail'
			}

			//Init the media uploader script
			var image = wp.media({
				title: 'Upload Image',
				multiple: false
			})

				//The open the media uploader modal
				.open()

				//Update the associated key with the selected image's url
				.on('select', e => {
					let uploaded_image = image.state().get('selection').first();

					if ( uploaded_image.toJSON().sizes[size] ) {
						this[key] = uploaded_image.toJSON().sizes[size].url;
					} else {
						this[key] = uploaded_image.toJSON().sizes['full'].url;
					}
					
				})
		}

	},

	mounted () {
		//Runs after mounting
	},
})

export default settingsApp
