import popover from 'vue-popperjs'
import noptin from './noptin.js'
import noptinSelectComponent from './noptin-select.vue'

var settingsApp = new Vue({

	components: {

		// Tooltips
		'noptin-tooltip': popover,

		// Select2
		'noptin-select': noptinSelectComponent,

	},

	el: '#noptin-settings-app',

	data: jQuery.extend(
		true,
		{},
		noptinSettings
	),

	methods: {

		// Switches to a new tab.
		switchTab( tab ) {
			this.currentTab     = tab
			this.currentSection = 'main'
		},

		// Switches to a new section.
		switchSection( section ) {
			this.currentSection = section
		},

		// Returns a given tab's class.
		tabClass( tab ) {

			if ( this.currentTab === tab ) {
				return 'nav-tab nav-tab-active'
			}

			return 'nav-tab'
		},

		// Returns a given section's class.
		sectionClass( section ) {

			if ( this.currentSection === section ) {
				return 'current'
			}

			return ''
		},

		// Toggles an active panel.
		togglePanel( panel ) {

			var index = this.openSections.indexOf(panel);

			if (index === -1) {
				this.openSections.push(panel);
			} else {
				this.openSections.splice(index, 1);
			}

		},

		// Toggles an accordion.
		toggleAccordion( panel_id ) {

			let panel = jQuery( '#' + panel_id )
			let button = panel.prev( '.noptin-accordion-heading' ).find('.noptin-accordion-trigger')
			let isExpanded = ( 'true' === button.attr( 'aria-expanded' ) );

			if ( isExpanded ) {
				button.attr( 'aria-expanded', 'false' );
				panel.attr( 'hidden', true );
			} else {
				button.attr( 'aria-expanded', 'true' );
				panel.attr( 'hidden', false );
			}
		},

		// Checks if the panel is open.
		isOpenPanel( panel ) {
			return -1 !== this.openSections.indexOf(panel);
		},

		addField() {
			let total = this.custom_fields.length
			this.custom_fields.push(
				{
					type: 'text',
					merge_tag: 'field_' + total,
					label: 'Field' + total,
					visible: true,
					subs_table: false,
					predefined: false,
					new: true,
				}
			)
		},

		maybeUpdateMergeTag( field ) {
			if ( ! field.predefined && field.new ) {
				field.merge_tag = field.label.toString().trim().toLowerCase().replace( /[^a-z0-9]+/g,'_' )
			}
		},

		removeField(item) {

			var key = this.custom_fields.indexOf(item)
			if (key > -1) {
				this.custom_fields.splice(key, 1)
			}

		},

		isFieldPredefined(field) {
			return this.fieldTypes[field.type] && this.fieldTypes[field.type].predefined
		},

		fieldAllowsOptions(field) {
			return this.fieldTypes[field.type] && this.fieldTypes[field.type].supports_options
		},

		// Persists settings to the database.
		saveSettings() {

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
				.done(() => {
					$(el)
						.fadeTo("fast", 1)
						.find('.noptin-save-saved')
						.show()
						.html(`<p>${saved}</p>`)

						window.location.href = window.location.href.split('#')[0] + "&tab=" + this.currentTab;
				})

				//Else alert the user about the error.
				.fail(() => {
					$(el)
						.fadeTo("fast", 1)
						.find('.noptin-save-error')
						.show()
						.html(`<p>${error}</p>`)
				})

		},

		//Handles image uploads
		upload_image(key) {
			var size = 'large'

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

					if (uploaded_image.toJSON().sizes[size]) {
						this[key] = uploaded_image.toJSON().sizes[size].url;
					} else {
						this[key] = uploaded_image.toJSON().sizes['full'].url;
					}

				})
		}

	},

	mounted() {
		//Runs after mounting
	},
})

export default settingsApp
