import popover from 'vue-popperjs'
import noptin from './noptin.js'

export default {

	data() {
		return noptinSettings.app;
	},

	components: {

		// Tooltips
		'noptin-tooltip': popover,

	},

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
					predefined: false,
					field_key: Math.random().toString(36).replace(/[^a-z]+/g, '') + this.custom_fields.length,
					new: true,
				}
			)
		},

		maybeUpdateMergeTag( field ) {
			if ( ! field.predefined && field.new ) {

				// Generate a merge tag from the label
				field.merge_tag = field.label.toString().trim().toLowerCase().replace( /[^a-z0-9]+/g,'_' )

				// Limit to 64 characters
				field.merge_tag = field.merge_tag.substring( 0, 64 )
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

		isFieldFirst(field) {
			return this.custom_fields.indexOf(field) == 0
		},

		isFieldLast(field) {
			return this.custom_fields.indexOf(field) == this.custom_fields.length - 1
		},

		moveUp(field) {
			let from = this.custom_fields.indexOf(field)
			let to = from - 1
			this.custom_fields.splice(to, 0, this.custom_fields.splice(from, 1)[0]);
		},

		moveDown(field) {
			let from = this.custom_fields.indexOf(field)
			let to = from + 1
			this.custom_fields.splice(to, 0, this.custom_fields.splice(from, 1)[0]);
		},

		fieldAllowsOptions(field) {
			return this.fieldTypes[field.type] && this.fieldTypes[field.type].supports_options
		},

		// Persists settings to the database.
		saveSettings() {

			const container = jQuery( '#noptin-settings-app' );

			//Provide visual feedback by fading the form
			container.fadeTo("fast", 0.33);

			//Prepare state data
			var error = this.error
			var saved = this.saved

			//Hide form notices
			container.find('.noptin-save-saved').hide()
			container.find('.noptin-save-error').hide()

			//Post the state data to the server
			jQuery.post(noptin_params.ajaxurl, {
				_ajax_nonce: noptin_params.nonce,
				action: "noptin_save_options",
				state: JSON.stringify( this.$data ),
			})

				//Show a success msg after we are done
				.done(() => {
					container
						.fadeTo("fast", 1)
						.find('.noptin-save-saved')
						.show()
						.html(`<p>${saved}</p>`)

						window.location.href = window.location.href.split('#')[0] + "&tab=" + this.currentTab;
				})

				//Else alert the user about the error.
				.fail(() => {
					container
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
}
