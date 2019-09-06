<?php
/**
 * Optin Form Editor
 *
 * Responsible for editing the optin forms
 *
 * @since             1.0.0
 *
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    die;
}

class Noptin_Form_Editor {

    /**
     * Id of the form being edited
     * @access      public
     * @since       1.0.0
     */
    public $id = null;

    /**
     * Post object of the form being edited
     * @access      public
     * @since       1.0.0
     */
    public $post = null;

    /**
     * Class Constructor.
     */
    public function __construct( $id, $localize = false ) {
        $this->id   = $id;
        $this->post = noptin_get_optin_form( $id );

        if( $localize ) {
            noptin_localize_optin_editor( $this->get_state() );
        }
    }

    /**
     * Displays the editor
     */
    public function output() {
        $sidebar = $this->sidebar_fields();
        $state   = $this->get_state();
        require plugin_dir_path(__FILE__) . 'templates/optin-form-editor.php';
    }

    /**
     * Returns sidebar fields
     */
    public function sidebar_fields() {
        $fields = array(
            'settings' 	   => $this->get_setting_fields(),
			'design'   	   => $this->get_design_fields(),
			'integrations' => $this->get_integration_fields(),
        );
        return apply_filters( 'noptin_optin_form_editor_sidebar_section', $fields, $this );
    }

    /**
     * Returns setting fields fields
     */
    private function get_setting_fields() {
        return array(

            //Basic settings
            'basic'         => array(
                'el'        => 'panel',
                'title'     => __( 'Basic Options', 'noptin' ),
                'id'        => 'basicSettings',
                'children'  => $this->get_basic_settings()
            ),

            //Trigger Options
            'trigger'         => array(
                'el'        => 'panel',
                'title'     => __( 'Popup Options', 'noptin' ),
                'id'        => 'triggerSettings',
                'restrict'  => "optinType=='popup'",
                'children'  => $this->get_trigger_settings()
            ),

            //Targeting Options
            'targeting'     => array(
                'el'        => 'panel',
                'title'     => __( 'Page Targeting', 'noptin' ),
                'id'        => 'targetingSettings',
                'children'  => $this->get_targeting_settings()
            ),

            //User targeting
            /*'userTargeting' => array(
                'el'        => 'panel',
                'title'     => 'User Targeting',
                'id'        => 'userTargetingSettings',
                'children'  => $this->get_user_settings()
            ),*/

            //Device targeting
            'deviceTargeting'   => array(
                'el'            => 'panel',
                'title'         => __( 'Device Targeting', 'noptin' ),
                'id'            => 'deviceTargetingSettings',
                'children'      => $this->get_device_settings()
            ),

        );
    }

    /**
     * Returns basic settings fields
     */
    private function get_basic_settings() {
        return array(

			//Should we display the form on the frontpage?
            'optinStatus'   => array(
                'type'      => 'checkbox',
				'el'        => 'input',
				'tooltip'   => __( 'Your website visitors will not see this form unless you check this box', 'noptin' ),
                'label'     => __( 'Published', 'noptin' ),
            ),


            //Form type
            'optinType'     => array(
                'el'        => 'select',
                'label'     => __( 'This form will be...', 'noptin' ),
                'options'   => array(
                    'popup'      => __( 'Displayed in a popup', 'noptin' ),
                    'inpost'     => __( 'Embedded in a post', 'noptin' ),
                    'sidebar'    => __( 'Added to a widget area', 'noptin' ),
                    //'flyin'      => 'Fly In Form',
                    //'bar'        => 'Notification bar',
                ),
            ),

            'inject'        => array(
                'el'        => 'select',
                'restrict'  => "optinType=='inpost'",
				'label'     => __( 'Inject into post content', 'noptin' ),
				'tooltip'	=> __( "Noptin can automatically embed this form into your post content. You can also find the form's shortcode below the form preview", 'noptin' ),
                'options'   => array(
                    '0'         => __( "Don't inject", 'noptin' ),
                    'before'    => __( 'Before post content', 'noptin' ),
                    'after'     => __( 'After post content', 'noptin' ),
                    'both'      => __( 'Before and after post content', 'noptin' ),
                ),
            ),

            //What should happen after someone subscibes?
            'subscribeAction' => array(
                'el'        => 'select',
                'label'     => __( 'What should happen after the user subscribes', 'noptin' ),
                'options'   => array(
                    'message'   => __( 'Display a success message', 'noptin' ),
                    'redirect'  => __( 'redirect to a different page', 'noptin' ),
                ),
            ),

            //Success message after subscription
            'successMessage' => array(
                'type'      => 'textarea',
                'el'        => 'textarea',
                'label'     => __( 'Success message', 'noptin' ),
                'restrict'  => "subscribeAction=='message'",
            ),

            //Where should we redirect the user after subscription?
            'redirectUrl' => array(
                'type'      => 'text',
                'el'        => 'input',
                'label'     => __( 'Redirect url', 'noptin' ),
                'placeholde'=> 'http://example.com/success',
                'restrict'  => "subscribeAction=='redirect'",
            ),

        );
    }

    /**
     * Returns trigger settings fields
     */
    private function get_trigger_settings() {
        return array(

			//Once per session
            'DisplayOncePerSession' => array(
                'type'      => 'checkbox',
				'el'        => 'input',
				'tooltip'   => __( 'Uncheck to display the popup on every page load', 'noptin' ),
				'label'     => __( 'Display this popup once per session', 'noptin' ),
				'restrict'  => "triggerPopup!='after_click'",
            ),

			//trigger when
			'triggerPopup'  => array(
				'el'        => 'select',
                'label'     => __( 'Show this popup', 'noptin' ),
                'options'   => array(
                    'immeadiate'      => __( 'Immediately', 'noptin' ),
                    'before_leave'    => __( 'Before the user leaves', 'noptin' ),
					'on_scroll'       => __( 'After the user starts scrolling', 'noptin' ),
					//'after_comment'   => 'After commenting',
					'after_click'     => __( 'After clicking on something', 'noptin' ),
					'after_delay'     => __( 'After a time delay', 'noptin' ),
                ),
			),

            //CSS class of the items to watch out for clicks
            'cssClassOfClick' => array(
                'type'      => 'text',
                'el'        => 'input',
                'label'     => __( 'CSS selector of the items to watch out for clicks', 'noptin' ),
                'restrict'  => "triggerPopup=='after_click'",
            ),

            //Time in seconds to delay
            'timeDelayDuration' => array(
                'type'      => 'text',
                'el'        => 'input',
                'label'     => __( 'Time in seconds to delay', 'noptin' ),
                'restrict'  => "triggerPopup=='after_delay'",
			),

			//Scroll depth
            'scrollDepthPercentage' => array(
                'type'      => 'text',
                'el'        => 'input',
                'label'     => __( 'Scroll depth in percentage after which the popup will be shown', 'noptin' ),
                'restrict'  => "triggerPopup=='on_scroll'",
            ),
        );
    }

    /**
     * Returns setting fields fields
     */
    private function get_targeting_settings() {

        $return = array(

            'targeting-info-text'   => array(
                'el'                => 'paragraph',
                'content'           => __( 'Display this optin...', 'noptin' ),
                'style'             => 'font-weight: bold;'
            ),

            'showEverywhere'        => array(
                'type'              => 'checkbox',
                'el'                => 'input',
				'label'             => __( 'Everywhere', 'noptin' ),
                'restrict'          => "!_onlyShowOn",
            ),
            'showHome'              => array(
                'type'              => 'checkbox',
                'el'                => 'input',
                'label'             => __( 'Front page', 'noptin' ),
                'restrict'          => "!showEverywhere && !_onlyShowOn",
            ),
            'showBlog'              => array(
                'type'              => 'checkbox',
                'el'                => 'input',
                'label'             => __( 'Blog page', 'noptin' ),
                'restrict'          => "!showEverywhere && !_onlyShowOn",
            ),
            'showSearch'            => array(
                'type'              => 'checkbox',
                'el'                => 'input',
                'label'             => __( 'Search page', 'noptin' ),
                'restrict'          => "optinType!='inpost' && !showEverywhere && !_onlyShowOn",
            ),
            'showArchives'          => array(
                'type'              => 'checkbox',
                'el'                => 'input',
                'label'             => __( 'Archive pages', 'noptin' ),
                'restrict'          => "optinType!='inpost' && !showEverywhere && !_onlyShowOn",
			),
			'showPostTypes'         => array(
                'el'                => 'multi_checkbox',
				'options'			=> noptin_get_post_types(),
                'restrict'          => "!showEverywhere && !_onlyShowOn",
            ),
        );

        $return["neverShowOn"]  = array(
            'el'                => 'input',
            'label'             => __( "Never show on:", 'noptin' ),
            'options'           => $this->post->neverShowOn,
			'restrict'          => "!_onlyShowOn",
			'placeholder'       => '1,10,25',
			'tooltip'           => __( "Use a comma to separate post ids where this form should not be displayed. All post type ids are supported, not just post ids.", 'noptin' ),
        );

        $return["onlyShowOn"]  = array(
            'el'                => 'input',
			'label'             => "Only show on:",
			'placeholder'       => '3,14,5',
			'tooltip'           => __( "If you specify any posts here, all other targeting rule will be ignored, and this form will only be displayed on posts that you specify here.", 'noptin' ),
            'options'           => $this->post->onlyShowOn,
        );

        return $return;
    }

    /**
     * Returns setting fields fields
     */
    private function get_user_settings() {
        return array(

            'whoCanSee'             => array(
                'el'                => 'radio_button',
                'options'           => array(
                    'all'           => __( 'Everyone', 'noptin' ),
                    'users'         => __( 'Logged in users', 'noptin' ),
                    'guests'        => __( 'Logged out users', 'noptin' ),
                    'roles'         => __( 'specific user roles', 'noptin' )
                ),
                'label'             => __( 'Who can see this form?', 'noptin' ),
            ),

            'userRoles'             => array(
                'el'                => 'multiselect',
                'label'             => __( 'Select user roles', 'noptin' ),
                'restrict'          => "whoCanSee=='roles'",
                'options'           => array(),
            ),

        );

    }

    /**
     * Returns setting fields fields
     */
    private function get_device_settings() {
        return array(

            'hideSmallScreens'      => array(
                'type'              => 'checkbox',
                'el'                => 'input',
                'label'             => __( 'Hide on Mobile', 'noptin' ),
            ),

            'hideLargeScreens'      => array(
                'type'              => 'checkbox',
                'el'                => 'input',
                'label'             => __( 'Hide on Desktops', 'noptin' ),
            ),

        );

	}

	/**
     * Returns integration settings fields
     */
    private function get_integration_fields() {
		return array(
			'nmi' => array(
				'el'        => 'panel',
				'title'     => 'Mailchimp',
				'id'        => 'nmi',
				'children'  => array(
					'nmitext'               => array(
                        'el'                => 'paragraph',
                        'content'           => sprintf(
							esc_html__( 'Install the %s to connect your mailchimp account.', 'noptin' ),
							sprintf( '<a target="_blank" href="https://noptin.com/product/mailchimp/?utm_medium=plugin-dashboard&utm_campaign=editor&utm_source=%s"> MailChimp addon</a>', get_home_url() )
							),
                        'style'             => 'color:#232222;'
                    ),
				)
			),
		);
	}


    /**
     * Returns design settings fields
     */
    private function get_design_fields() {
        return array(

			//Color themes
            'colors'        => array(
                'el'        => 'panel',
                'title'     => __( 'Templates', 'noptin' ),
                'id'        => 'colorsDesign',
                'children'  => $this->get_templates_settings()
			),

			//overlay Design
            'overlay'         => array(
                'el'        => 'panel',
				'title'     => __( 'Overlay', 'noptin' ),
				'restrict'  => "optinType=='popup'",
                'id'        => 'overlayDesign',
                'children'  => $this->get_overlay_settings()
			),

            //Form Design
            'form'         => array(
                'el'        => 'panel',
                'title'     => __( 'Form', 'noptin' ),
                'id'        => 'formDesign',
                'children'  => $this->get_form_settings()
			),

			//Fields Design
            'fields'        => array(
                'el'        => 'panel',
                'title'     => __( 'Fields', 'noptin' ),
                'id'        => 'fieldDesign',
                'children'  => $this->get_field_settings()
            ),

            //Image Design
            'image'         => array(
                'el'        => 'panel',
                'title'     => __( 'Image', 'noptin' ),
                'id'        => 'imageDesign',
                'children'  => $this->get_image_settings()
            ),

            //Button Design
            'button'        => array(
                'el'        => 'panel',
                'title'     => __( 'Button', 'noptin' ),
                'id'        => 'buttonDesign',
                'children'  => $this->get_button_settings()
            ),

            //Title Design
            'title'         => array(
                'el'        => 'panel',
                'title'     => __( 'Title', 'noptin' ),
                'id'        => 'titleDesign',
                'children'  => $this->get_title_settings()
            ),

            //Description Design
            'description'   => array(
                'el'        => 'panel',
                'title'     => __( 'Description', 'noptin' ),
                'id'        => 'descriptionDesign',
                'children'  => $this->get_description_settings()
            ),

            //Note Design
            'note'          => array(
                'el'        => 'panel',
                'title'     => __( 'Note', 'noptin' ),
                'id'        => 'noteDesign',
                'children'  => $this->get_note_settings()
            ),

            //Css Design
            'css'          => array(
                'el'        => 'panel',
                'title'     => __( 'Custom CSS', 'noptin' ),
                'id'        => 'customCSS',
                'children'  => $this->get_custom_css_settings()
            ),
        );
    }

    /**
     * Returns Color themes Design Fields
     */
    private function get_templates_settings() {

        $colors    = noptin_get_color_themes();
		$templates = array();

		foreach( noptin_get_optin_templates() as $key => $data ){
			$templates[$key] = $data['title'];
		}

        return array(

            'Template'          => array(
                'el'            => 'select',
				'label'         => __( 'Apply a template', 'noptin' ),
				'tooltip'       => __( 'All templates include custom css so remember to check out the Custom CSS panel after you apply a template', 'noptin' ),
                'options'       => $templates,
			),

			'colorTheme'        => array(
                'el'            => 'select',
                'label'         => __( 'Apply a color theme', 'noptin' ),
                'options'       => array_combine( array_values( $colors ), array_keys( $colors ) ),
            ),

        );
	}

	/**
     * Returns overlay Design Fields
     */
    private function get_overlay_settings() {
		return array(
			'noptinOverlayBg'          => array(
                'type'              => 'color',
                'el'                => 'input',
                'label'             => __( 'Background Color', 'noptin' ),
            ),

			'noptinOverlayBgImg'       => array(
                'type'      		=> 'image',
				'el'        		=> 'input',
				'size'        		=> 'full',
                'label'     		=> __( 'Background Image', 'noptin' ),
			),

		);
	}

    /**
     * Returns Form Design Fields
     */
    private function get_form_settings() {
        return array(

            'hideCloseButton' => array(
                'type'      => 'checkbox',
                'el'        => 'input',
                'restrict'  => "optinType=='popup'",
                'label'     => __('Hide Close Button', 'noptin'),
            ),

            'closeButtonPos'=> array(
                'el'        => 'select',
                'options'       => array(
                    'inside'        => __( 'Inside the form', 'noptin' ),
                    'outside'       => __( 'Outside the form', 'noptin' ),
					'along'         => __( 'Along the border', 'noptin' ),
					'top-right'     => __( 'Top Right', 'noptin' )
                ),
                'label'     => __( 'Close Button Position', 'noptin' ),
                'restrict'  => "optinType=='popup' && !hideCloseButton",
            ),

            'formRadius'     => array(
                'type'       => 'text',
                'el'         => 'input',
				'label'      => __( 'Border Radius', 'noptin' ),
				'tooltip'    => __( "Set this to 0 if you don't want the form to have rounded corners", 'noptin' ),
            ),

            'formWidth'             => array(
                'type'              => 'text',
                'el'                => 'input',
				'label'             => __( 'Preferred Width', 'noptin' ),
				'tooltip'    		=> __( "The element will resize to 100% width on smaller devices", 'noptin' ),
            ),

            'formHeight'            => array(
                'type'              => 'text',
                'el'                => 'input',
                'label'             => __( 'Minimum Height', 'noptin' ),
			),

			'noptinFormBorderColor' => array(
                'type'              => 'color',
                'el'                => 'input',
                'label'             => __( 'Border Color', 'noptin' ),
			),

			'noptinFormBg'          => array(
                'type'              => 'color',
                'el'                => 'input',
                'label'             => __( 'Background Color', 'noptin' ),
            ),

			'noptinFormBgImg'       => array(
                'type'      		=> 'image',
				'el'        		=> 'input',
				'size'        		=> 'full',
                'label'     		=> __( 'Background Image', 'noptin' ),
			),

			'noptinFormBgVideo'     => array(
                'type'      		=> 'text',
                'el'        		=> 'input',
				'label'     		=> __( 'Background Video', 'noptin' ),
				'description'       => __( 'Enter the full URL to an MP4 video file', 'noptin' ),
				'tooltip'       	=> __( 'Works best if the video dimensions are of the same ratio as the form', 'noptin' ),
            ),

        );
	}

	/**
     * Returns field Design Fields
     */
    private function get_field_settings() {
        return array(

            'fields'        => array(
                'el'        => 'form_fields',
			),

			'singleLine' => array(
                'type'      => 'checkbox',
                'el'        => 'input',
                'label'     => __( 'Show all fields in a single line', 'noptin' ),
            ),

        );
    }


    /**
     * Returns image Design Fields
     */
    private function get_image_settings() {
        return array(

            'image'         => array(
                'type'      => 'image',
                'el'        => 'input',
                'label'     => __( 'Image URL', 'noptin'),
            ),

            'imagePos'      => array(
                'el'        => 'radio_button',
                'options'       => array(
                    'top'       => __( 'Top', 'noptin' ),
                    'left'      => __( 'Left', 'noptin' ),
                    'right'     => __( 'Right', 'noptin' ),
                    'bottom'    => __( 'Bottom', 'noptin' )
                ),
                'label'     => __( 'Image Position', 'noptin' ),
                'restrict'  => 'image',
            ),

        );
    }

    /**
     * Returns Button Design Fields
     */
    private function get_button_settings() {
        return array(
            'noptinButtonLabel'     => array(
                'type'              => 'text',
                'el'                => 'input',
                'label'             => __( 'Button Label', 'noptin' ),
            ),

            'buttonPosition'=> array(
                'el'        => 'radio_button',
                'options'       => array(
                    'block'     => __( 'Block', 'noptin' ),
                    'left'      => __( 'Left', 'noptin' ),
                    'right'     => __( 'Right', 'noptin' )
                ),
                'label'     => __( 'Button Position', 'noptin' ),
                'restrict'  => '!singleLine',
            ),

            'noptinButtonBg'        => array(
                'type'              => 'color',
                'el'                => 'input',
                'label'             => __( 'Button Background', 'noptin' ),
            ),

            'noptinButtonColor'     => array(
                'type'              => 'color',
                'el'                => 'input',
                'label'             => __( 'Button Color', 'noptin' ),
            ),
        );
    }

    /**
     * Returns Title Design Fields
     */
    private function get_title_settings() {
        return array(

            'hideTitle'             => array(
                'type'              => 'checkbox',
                'el'                => 'input',
                'label'             => __( 'Hide title', 'noptin' ),
            ),

            'title'                 => array(
                'el'                => 'textarea',
                'label'             => __( 'Title', 'noptin' ),
                'restrict'          => '!hideTitle'
            ),

            'titleColor'            => array(
                'type'              => 'color',
                'el'                => 'input',
                'label'             => __( 'Title Color', 'noptin' ),
                'restrict'          => '!hideTitle'
            ),
        );
    }

    /**
     * Returns Description Design Fields
     */
    private function get_description_settings() {
        return array(

            'hideDescription'           => array(
                'type'                  => 'checkbox',
                'el'                    => 'input',
                'label'                 => __( 'Hide description', 'noptin' ),
            ),

            'description'               => array(
                'el'                    => 'textarea',
                'label'                 => __( 'Description', 'noptin' ),
                'restrict'              => '!hideDescription'
            ),
            'descriptionColor'          => array(
                'type'                  => 'color',
                'el'                    => 'input',
                'label'                 => __( 'Description Color', 'noptin' ),
                'restrict'              => '!hideDescription'
            ),

        );
    }

    /**
     * Returns Note Design Fields
     */
    private function get_note_settings() {
        return array(

            'hideNote'                    => array(
                'type'                    => 'checkbox',
                'el'                      => 'input',
                'label'                   => __( 'Hide note', 'noptin' ),
            ),

            /*'hideOnNoteClick'             => array(
                'type'                    => 'checkbox',
                'el'                      => 'input',
                'label'                   => 'Close popup when user clicks on note?',
                'restrict'                => "!hideNote && optinType=='popup'",
            ),*/

            'note'                        => array(
                'el'                      => 'textarea',
                'label'                   => __( 'Note', 'noptin' ),
                'restrict'                => '!hideNote'
            ),
            'noteColor'                   => array(
                'type'                    => 'color',
                'el'                      => 'input',
                'label'                   => __( 'Note Color', 'noptin' ),
                'restrict'                => '!hideNote'
            ),

        );
    }

    /**
     * Returns Custom css Fields
     */
    private function get_custom_css_settings() {
        return array(

            'CSS'          => array(
				'el'       => 'editor',
				'tooltip'  => __( "Prefix all your styles with '.noptin-optin-form-wrapper' or else they will apply to all opt-in forms on the page", 'noptin' ),
                'label'    => 'Enter Your Custom CSS <a href="https://noptin.com/guide/custom-css/" target="_blank">Read this first</a>',
            ),

        );
    }

    /**
     * Returns the editor state as a JSON string
     */
    public function get_state_json() {
        return wp_json_encode( $this->get_state() );
    }

    /**
     * Returns the editor state
     */
    public function get_state() {

        $saved_state = $this->post->get_all_data();
        unset( $saved_state['optinHTML'] );
		$state = array_replace( $saved_state, $this->get_misc_state());
        return apply_filters( 'noptin_optin_form_editor_state', $state, $this );

    }


    /**
     * Returns misc state
     */
    public function get_misc_state() {
        return array(
            'hasSuccess'                    => false,
            'Success'                       => '',
            'hasError'                      => false,
			'Error'                         => '',
			'currentSidebarSection'         => 'settings',
            'headerTitle'                   => __( 'Editing', 'noptin'),
            'saveText'                      => __( 'Save', 'noptin'),
            'savingText'                    => __( 'Saving...', 'noptin'),
            'saveAsTemplateText'            => __( 'Save As Template', 'noptin'),
            'savingTemplateText'            => __( 'Saving Template...', 'noptin'),
            'savingError'                   => __( 'There was an error saving your form.', 'noptin'),
            'savingSuccess'                 => __( 'Your changes have been saved successfuly', 'noptin'),
            'savingTemplateError'           => __( 'There was an error saving your template.', 'noptin'),
            'savingTemplateSuccess'         => __( 'Your template has been saved successfuly', 'noptin'),
            'previewText'                   => __( 'Preview', 'noptin'),
            'isPreviewShowing'              => false,
            'colorTheme'                    => '',
			'Template'                      => '',
			'fieldTypes'                    => get_noptin_optin_field_types(),
        );
    }

    /**
     * Converts an array of ids to select2 option
     */
    public function post_ids_to_options( $ids ) {

        //Return post ids array
        if(! is_array( $ids ) ) {
            return array();
        }

        $options = array();
        foreach( $ids as $id ) {
            $post_type      = get_post_type(  $id  );
            $title          = get_the_title(  $id  );
            $options[$id]   = "[{$post_type}] $title";
        }

        return $options;
    }
}
