<template>
	<v-app id="noptin-form-editor-body">

		<v-app-bar app absolute clipped-right outlined elevation="0" :style="darkMode ? '' : 'background-color: #FFFFFF'">
			<v-text-field hide-details outlined dense clearable autofocus label="Form Name" v-model="optinName"></v-text-field>

    		<v-spacer></v-spacer>

			<v-btn color="primary" :loading="isSaving" @click.prevent="publish()">
				<span v-if="optinStatus">{{saveText}}</span>
				<span v-else>{{publishText}}</span>
			</v-btn>

			<v-menu offset-y>
    			<template v-slot:activator="{ on, attrs }">
        			<v-btn icon v-bind="attrs" v-on="on">
						<v-icon>{{icons.dots}}</v-icon>
					</v-btn>
    			</template>
    			<v-list>
					<v-list-item ripple link v-if="optinType == 'popup'" @click="previewPopup()">
    					<v-list-item-content>
        					<v-list-item-title>Preview</v-list-item-title>
      					</v-list-item-content>
    				</v-list-item>
					<v-list-item ripple link @click="unpublish()">
    					<v-list-item-content>
        					<v-list-item-title v-if="optinStatus">Switch to Draft</v-list-item-title>
							<v-list-item-title v-else>Save Draft</v-list-item-title>
      					</v-list-item-content>
    				</v-list-item>
					<v-list-item ripple link @click="publish()">
    					<v-list-item-content>
        					<v-list-item-title v-if="optinStatus">{{saveText}}</v-list-item-title>
							<v-list-item-title v-else>{{publishText}}</v-list-item-title>
      					</v-list-item-content>
    				</v-list-item>
					<v-list-item ripple link v-if="darkMode" @click="darkMode=false">
    					<v-list-item-content>
        					<v-list-item-title>Light Mode</v-list-item-title>
      					</v-list-item-content>
    				</v-list-item>
					<v-list-item ripple link v-if="!darkMode" @click="darkMode=true">
    					<v-list-item-content>
        					<v-list-item-title>Dark Mode</v-list-item-title>
      					</v-list-item-content>
    				</v-list-item>
					
      			</v-list>
    		</v-menu>

  		</v-app-bar>

		<!-- Sizes your content based upon application components -->
		<v-main>

			<!-- Provides the application the proper gutter -->
			<v-container fluid>
				<v-sheet color="transparent">
					<v-dialog v-model="isSaving" persistent width="300">
						<v-card color="primary" dark>
        					<v-card-text>
								{{savingText}}
          						<v-progress-linear indeterminate color="white" class="mb-0"></v-progress-linear>
        					</v-card-text>
      					</v-card>
    				</v-dialog>
					<v-snackbar v-model="hasSuccess" :timeout="4000" absolute bottom color="success" outlined right app>
						<div v-html="Success"></div>
						<template v-slot:action="{ attrs }">
							<v-btn color="blue" text v-bind="attrs" @click="hasSuccess = false">Close</v-btn>
      					</template>
    				</v-snackbar>
					<v-snackbar v-model="hasError" :timeout="4000" absolute bottom color="error" outlined left app>
      					<div v-html="Error"></div>
						<template v-slot:action="{ attrs }">
							<v-btn color="blue" text v-bind="attrs" @click="hasError = false">Close</v-btn>
      					</template>
    				</v-snackbar>
					<form-preview class="mt-4" v-bind="$data" @updatevalue="updateSetting($event.prop,$event.value)"></form-preview>
					<v-card class="mx-auto mt-2" color="transparent" max-width="344" flat>
						<v-card-text v-if="optinType=='inpost'">{{shortcode}} <strong>[noptin-form id={{id}}]</strong></v-card-text>
						<v-card-text v-if="optinType=='sidebar'" v-html="sidebarUsage"></v-card-text>
					</v-card>			
				</v-sheet>

			</v-container>
		</v-main>

		<v-navigation-drawer right app absolute clipped width="300">
			<v-sheet>
				<v-tabs v-model="currentSidebarSection" slider-size="2" show-arrows>
					<v-tab v-for="(content,tab) in settingTabs" :key="tab">
						<span v-if="content.label">{{content.label}}</span>
						<span v-else>{{tab | capitalize }}</span>
					</v-tab>
				</v-tabs>
				<v-divider />
				<v-tabs-items v-model="currentSidebarSection">
					<v-tab-item v-for="(content,tab) in settingTabs" :key="tab"><v-sheet style="margin-left: 1px;">

						<v-expansion-panels accordion focusable hover class="noptin-sidebar-expansion-panels">
							<v-expansion-panel v-for="(panel) in visiblePanels(content)" :key="panel.id">
								<v-expansion-panel-header>{{panel.title}}</v-expansion-panel-header>
								<v-expansion-panel-content class="mt-2">

									<div v-for="(opts,model) in panel.children" :key="model">
										<div v-if="isVisible(opts)">
											<sidebar-setting :value="getSetting(model)" @input="updateSetting(model,$event)" :opts="opts" v-bind="opts" v-if="opts.el != 'form_fields'"></sidebar-setting>
											<field-editor v-else v-bind='$data'></field-editor>
										</div>
									</div>
								</v-expansion-panel-content>
							</v-expansion-panel>
						</v-expansion-panels></v-sheet>
					</v-tab-item>
				</v-tabs-items>
			</v-sheet>

      	</v-navigation-drawer>

		<v-footer app absolute>
			<v-col class="text-center" cols="12" >
    			{{ new Date().getFullYear() }} — <strong>Noptin</strong>
    		</v-col>
		</v-footer>

	</v-app>
</template>

<script>
	import sidebarSetting from './sidebar-setting.vue'
	import fieldEditor from './field-editor.js'
	import formPreview from './form-preview.js'
	import noptin from './noptin.js'
	import popups from './popups'
	import { mdiCog, mdiPencilBox, mdiDotsVertical } from '@mdi/js'

  	export default {
		name: 'App',

		computed: {
			_onlyShowOn() {
				return this.onlyShowOn && this.onlyShowOn.length > 0
			},

			settingTabs() {

				return this.filter( this.sidebarSettings, function( val, key ) {
					return key == 'sdesign'
				})

			},
			wrapperStyles() {
				let generated = this.formBorder.generated

				if ( this.noptinFormBg ) {
					generated = `${generated}; background-color: ${this.noptinFormBg};`
				}

				if ( this.noptinFormBgImg ) {
					generated = `${generated} background-image: url('${this.noptinFormBgImg}');`
				}

				if ( this.formWidth ) {
					generated = `${generated} width: ${this.formWidth};`
				}

				if ( this.formHeight ) {
					generated = `${generated} min-height: ${this.formHeight};`
				}

				if ( this.descriptionColor ) {
					generated = `${generated} color: ${this.descriptionColor};`
				}

				generated = `.post-type-noptin-form .noptin-optin-form-wrapper { ${generated} }`
				return generated
			}
		},

		components: {
			'sidebar-setting': sidebarSetting,
			'field-editor': fieldEditor,
			'form-preview': formPreview,
		},

		methods: {

			// Filters an associative array
			filter( obj, predicate ) {

				let result = {}, key;

				for ( key in obj ) {
					if ( obj.hasOwnProperty( key ) && ! predicate( obj[key], key ) ) {
						result[key] = obj[key];
					}

				}

    			return result;
			},

			// Returns an array of visible panels.
			visiblePanels( panels ) {

				return this.filter( panels, ( val, key ) => {
					return ! val.title || ! this.isVisible( val )
				})

			},

			// Changes the active sidebar.
			activateSidebar( sidebar ) {
				if ( this.activeSidebar == sidebar ) {
					this.activeSidebar = false
				} else {
					this.activeSidebar = sidebar
				}
			},

			// Returns the active sidebar.
			isActiveSidebar( sidebar ) {
				return this.activeSidebar == sidebar
			},

			// Displays a success message.
			showSuccess(msg) {
				this.hasSuccess = true;
				this.Success = msg;
			},

			// Displays an error message.
			showError(msg) {
				this.hasError = true;
				this.Error = msg;
			},

			// Retrieves a setting value.
			getSetting(key) {
				return this[key]
			},

			// Updates a setting.
			updateSetting(key,value) {
				this.unsaved = true
				this[key] = value
			},

			// Establishes whether a setting field is visible.
			isVisible(opts) {

				if ( ! opts.restrict ) {
					return true;
				}

				return eval( opts.restrict )

			},

			// Saves a form.
			save() {

				if ( this.isSaving ) {
					return
				}

				// Display indicators.
				this.isSaving = true

				// Form state.
				var data = jQuery.extend( true, {}, this.$data );

				// Get rid of some redudant data.
				if ( data.skip_state_fields ) {

					jQuery.each( data.skip_state_fields, function( i, value ) {
						if ( data[value] ) {
							delete data[value]
						}
					} )

					delete data.skip_state_fields
				}

				this.unsaved = false

				// Save the form.
				jQuery.post( noptinEditor.ajaxurl, {
					_ajax_nonce: noptinEditor.nonce,
					action: "noptin_save_optin_form",
					state: data
				})

				// Show the success message.
				.done(() => {
					this.showSuccess(this.savingSuccess)
				})

				// Display an error on failure.
				.fail(() => {
					this.showError( this.savingError )
				})

				// Remove the loader on success/failure.
				.always(() => {
					this.isSaving = false
				})

			},

			// Publishes a form.
			publish() {
				this.optinStatus = true
				this.save()
			},

			// Reverts a form to draft.
			unpublish() {
				this.optinStatus = false
				this.save()
			},

			// Preview popups
			previewPopup() {
				popups.open( jQuery('.noptin-optin-form-wrapper').clone() )
			},

		},

		data: () => {
			return jQuery.extend(
				true,
				{
					activeSidebar: 'design',
					darkMode: false,
					unsaved: false,
					icons: {
						cog: mdiCog,
						pencil: mdiPencilBox,
						dots: mdiDotsVertical,
					}
				},
				noptinEditor.data,
			)
		},

		watch: {

			// Applies a template.
			Template() {
				var template = noptin.templateData( this.Template )
				noptin.applyTemplate( template, this )
			},

			// Switch color theme.
			darkMode : {
    			handler () {
					this.$vuetify.theme.dark = this.darkMode
				},
    			immediate: true
    		},

			// Save the form when the form status changes.
			optinStatus() {
				this.save()
			},

			// Updates the custom css for the form
			CSS : {
    			handler: ( css ) => noptin.updateCustomCss( css ),
    			immediate: true
    		},

			// Updates generated form css.
			wrapperStyles : {
    			handler: ( styles ) => jQuery('#generatedCustomCSS').text( styles ),
    			immediate: true
    		},

			// Resizes the form when the opt-in type changes.
			optinType() {
				noptin.updateFormSizes(this)
			},

			// Updates the color theme.
			colorTheme() {
				noptin.changeColorTheme(this)
			}
		},

		created() {

			jQuery('.noptin-form-designer-loader').hide()
			jQuery(this.$el).find('.noptin-popup-editor-main-preview-name-textarea').focus()

			// Positioning
			jQuery('#noptin_form_editor .noptin-popup-designer').css({
				top: jQuery('#wpadminbar').height(),
				left: jQuery('#adminmenuwrap').width(),
			})

			jQuery(window).on('resize', function(){
				jQuery('#noptin_form_editor .noptin-popup-designer').css({
					top: jQuery('#wpadminbar').height(),
					left: jQuery('#adminmenuwrap').width(),
				})
			});

			jQuery(window).on('beforeunload', () => {
				if ( this.unsaved ) {
					return "Changes you made may not be saved.";
				}
			});

		}

	};

	/**
	 * Pause/Stop button - When sending, show number of send/remaining
	 * Background modal - image, video, color, gradient
	 */
</script>