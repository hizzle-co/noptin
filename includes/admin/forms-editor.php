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
            'settings' => $this->get_setting_fields(),
            'design'   => $this->get_design_fields(),
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
                'title'     => 'Basic Options',
                'id'        => 'basicSettings',
                'children'  => $this->get_basic_settings()
            ),

            //Trigger Options
            'trigger'         => array(
                'el'        => 'panel',
                'title'     => 'Trigger Options',
                'id'        => 'triggerSettings',
                'restrict'  => "optinType=='popup'",
                'children'  => $this->get_trigger_settings()
            ),

            //Targeting Options
            'targeting'     => array(
                'el'        => 'panel',
                'title'     => 'Page Targeting',
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
                'title'         => 'Device Targeting',
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

            //Title
            'optinName' => array(
                'el'        => 'input',
                'label'     => 'Form Name',
			),

			//Should we display the form on the frontpage?
            'optinStatus'   => array(
                'type'      => 'checkbox',
                'el'        => 'input',
                'label'     => 'Activate this form',
            ),


            //Form type
            'optinType'     => array(
                'el'        => 'select',
                'label'     => 'This form will be',
                'options'   => array(
                    'popup'      => 'Displayed in a popup',
                    'inpost'     => 'Embedded in a post',
                    'sidebar'    => 'Added to a widget area',
                    //'flyin'      => 'Fly In Form',
                    //'bar'        => 'Notification bar',
                ),
            ),

            'inject'        => array(
                'el'        => 'select',
                'restrict'  => "optinType=='inpost'",
                'label'     => 'Inject into post content',
                'options'   => array(
                    '0'         => "Don't inject",
                    'before'    => 'Before post content',
                    'after'     => 'After post content',
                    'both'      => 'Before and after post content',
                ),
            ),

            //What should happen after someone subscibes?
            'subscribeAction' => array(
                'el'        => 'select',
                'label'     => 'What should happen after the user subscribes',
                'options'   => array(
                    'message'   => 'Display a success message',
                    'redirect'  => 'redirect to a different page',
                ),
            ),

            //Success message after subscription
            'successMessage' => array(
                'type'      => 'textarea',
                'el'        => 'textarea',
                'label'     => 'Success message',
                'restrict'  => "subscribeAction=='message'",
            ),

            //Where should we redirect the user after subscription?
            'redirectUrl' => array(
                'type'      => 'text',
                'el'        => 'input',
                'label'     => 'Redirect url',
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
                'label'     => 'Display this popup once per session',
            ),

			//trigger when
			'triggerPopup'  => array(
				'el'        => 'select',
                'label'     => 'Show this popup',
                'options'   => array(
                    'immeadiate'      => 'Immeadiately',
                    'before_leave'    => 'Before the user leaves',
					'on_scroll'       => 'After the user starts scrolling',
					//'after_comment'   => 'After commenting',
					'after_click'     => 'After clicking something',
					'after_delay'     => 'After a time delay',
                ),
			),

            //CSS class of the items to watch out for clicks
            'cssClassOfClick' => array(
                'type'      => 'text',
                'el'        => 'input',
                'label'     => 'CSS selector of the items to watch out for clicks',
                'restrict'  => "triggerPopup=='after_click'",
            ),

            //Time in seconds to delay
            'timeDelayDuration' => array(
                'type'      => 'text',
                'el'        => 'input',
                'label'     => 'Time in seconds to delay',
                'restrict'  => "triggerPopup=='after_delay'",
			),

			//Scroll depth
            'scrollDepthPercentage' => array(
                'type'      => 'text',
                'el'        => 'input',
                'label'     => 'Scroll depth in percentage after which the popup will be shown',
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
                'content'           => 'Display this optin...',
                'style'             => 'font-weight: bold;'
            ),

            'showEverywhere'        => array(
                'type'              => 'checkbox',
                'el'                => 'input',
                'label'             => 'Everywhere',
                'restrict'          => "!_onlyShowOn",
            ),
            'showHome'              => array(
                'type'              => 'checkbox',
                'el'                => 'input',
                'label'             => 'Front page',
                'restrict'          => "!showEverywhere && !_onlyShowOn",
            ),
            'showBlog'              => array(
                'type'              => 'checkbox',
                'el'                => 'input',
                'label'             => 'Blog page',
                'restrict'          => "!showEverywhere && !_onlyShowOn",
            ),
            'showSearch'            => array(
                'type'              => 'checkbox',
                'el'                => 'input',
                'label'             => 'Search page',
                'restrict'          => "optinType!='inpost' && !showEverywhere && !_onlyShowOn",
            ),
            'showArchives'          => array(
                'type'              => 'checkbox',
                'el'                => 'input',
                'label'             => 'Archives',
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
            'label'             => "Never show on:",
            'options'           => $this->post->neverShowOn,
			'restrict'          => "!_onlyShowOn",
			'tooltip'           => "Use a comma to separate post ids where this form should not be displayed",
        );

        $return["onlyShowOn"]  = array(
            'el'                => 'input',
            'label'             => "Only show on:",
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
                    'all'           => 'Everyone',
                    'users'         => 'Logged in users',
                    'guests'        => 'Logged out users',
                    'roles'         => 'specific user roles'
                ),
                'label'             => 'Who can see this form?',
            ),

            'userRoles'             => array(
                'el'                => 'multiselect',
                'label'             => 'Select user roles',
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
                'label'             => 'Hide on Mobile',
            ),

            'hideLargeScreens'      => array(
                'type'              => 'checkbox',
                'el'                => 'input',
                'label'             => 'Hide on Desktops',
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
                'title'     => 'Templates',
                'id'        => 'colorsDesign',
                'children'  => $this->get_templates_settings()
            ),

            //Form Design
            'form'         => array(
                'el'        => 'panel',
                'title'     => 'Form',
                'id'        => 'formDesign',
                'children'  => $this->get_form_settings()
			),

			//Fields Design
            'fields'        => array(
                'el'        => 'panel',
                'title'     => 'Fields',
                'id'        => 'fieldDesign',
                'children'  => $this->get_field_settings()
            ),

            //Image Design
            'image'         => array(
                'el'        => 'panel',
                'title'     => 'Image',
                'id'        => 'imageDesign',
                'children'  => $this->get_image_settings()
            ),

            //Button Design
            'button'        => array(
                'el'        => 'panel',
                'title'     => 'Button',
                'id'        => 'buttonDesign',
                'children'  => $this->get_button_settings()
            ),

            //Title Design
            'title'         => array(
                'el'        => 'panel',
                'title'     => 'Title',
                'id'        => 'titleDesign',
                'children'  => $this->get_title_settings()
            ),

            //Description Design
            'description'   => array(
                'el'        => 'panel',
                'title'     => 'Description',
                'id'        => 'descriptionDesign',
                'children'  => $this->get_description_settings()
            ),

            //Note Design
            'note'          => array(
                'el'        => 'panel',
                'title'     => 'Note',
                'id'        => 'noteDesign',
                'children'  => $this->get_note_settings()
            ),

            //Css Design
            'css'          => array(
                'el'        => 'panel',
                'title'     => 'Custom CSS',
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
                'label'         => 'Apply a template',
                'options'       => $templates,
			),

			'colorTheme'        => array(
                'el'            => 'select',
                'label'         => 'Apply a color theme',
                'options'       => array_combine( array_values( $colors ), array_keys( $colors ) ),
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
                    'inside'        => 'Inside the form',
                    'outside'       => 'Outside the form',
					'along'         => 'Along the border',
					'top-right'     => 'Top Right'
                ),
                'label'     => 'Close Button Position',
                'restrict'  => "optinType=='popup' && !hideCloseButton",
            ),

            'formRadius'     => array(
                'type'       => 'text',
                'el'         => 'input',
                'label'      => 'Form Border Radius',
            ),

            'formWidth'             => array(
                'type'              => 'text',
                'el'                => 'input',
                'label'             => 'Form Width',
            ),

            'formHeight'            => array(
                'type'              => 'text',
                'el'                => 'input',
                'label'             => 'Minimum Form Height',
            ),

            'noptinFormBg'          => array(
                'type'              => 'color',
                'el'                => 'input',
                'label'             => 'Form Background',
            ),

            'noptinFormBorderColor' => array(
                'type'              => 'color',
                'el'                => 'input',
                'label'             => 'Border Color',
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
                'label'     => 'Show all fields in a single line',
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
                'label'     => 'Image URL',
            ),

            'imagePos'      => array(
                'el'        => 'radio_button',
                'options'       => array(
                    'top'       => 'Top',
                    'left'      => 'Left',
                    'right'     => 'Right',
                    'bottom'    => 'Bottom'
                ),
                'label'     => 'Image Position',
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
                'label'             => 'Button Label',
            ),

            'buttonPosition'=> array(
                'el'        => 'radio_button',
                'options'       => array(
                    'block'     => 'Block',
                    'left'      => 'Left',
                    'right'     => 'Right'
                ),
                'label'     => 'Button Position',
                'restrict'  => '!singleLine',
            ),

            'noptinButtonBg'        => array(
                'type'              => 'color',
                'el'                => 'input',
                'label'             => 'Button Background',
            ),

            'noptinButtonColor'     => array(
                'type'              => 'color',
                'el'                => 'input',
                'label'             => 'Button Color',
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
                'label'             => 'Hide title',
            ),

            'title'                 => array(
                'el'                => 'textarea',
                'label'             => 'Title',
                'restrict'          => '!hideTitle'
            ),

            'titleColor'            => array(
                'type'              => 'color',
                'el'                => 'input',
                'label'             => 'Title Color',
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
                'label'                 => 'Hide description',
            ),

            'description'               => array(
                'el'                    => 'textarea',
                'label'                 => 'Description',
                'restrict'              => '!hideDescription'
            ),
            'descriptionColor'          => array(
                'type'                  => 'color',
                'el'                    => 'input',
                'label'                 => 'Description Color',
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
                'label'                   => 'Hide note',
            ),

            /*'hideOnNoteClick'             => array(
                'type'                    => 'checkbox',
                'el'                      => 'input',
                'label'                   => 'Close popup when user clicks on note?',
                'restrict'                => "!hideNote && optinType=='popup'",
            ),*/

            'note'                        => array(
                'el'                      => 'textarea',
                'label'                   => 'Note',
                'restrict'                => '!hideNote'
            ),
            'noteColor'                   => array(
                'type'                    => 'color',
                'el'                      => 'input',
                'label'                   => 'Note Color',
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
                'label'    => 'Enter Your Custom CSS',
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
