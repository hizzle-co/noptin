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
            $this->localize_script();
        }
    }

    /**
     * Localizes JS script
     */
    public function localize_script() {
        $params = array(
            'ajaxurl'   => admin_url('admin-ajax.php'),
            'api_url'   => get_home_url( null, 'wp-json/wp/v2/'),
            'nonce'     => wp_create_nonce('noptin_admin_nonce'),
            'data'      => $this->get_state(),
            'templates' => wp_json_encode ( noptin_get_optin_templates() ),
        );
        wp_localize_script('noptin', 'noptinEditor', $params);
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

            //Settings field title
            'section_title' => array(
                'el'        => 'paragraph',
                'content'   => "Use this panel to configure your optin form settings",
            ),

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
                'el'        => 'radio_button',
                'label'     => 'Form Status',
                'options'   => array(
                    'publish'   => 'Active',
                    'draft'     => 'Inactive',
                ),
            ),

            //Form type
            'optinType'     => array(
                'el'        => 'radio_button',
                'label'     => 'Form Type',
                '@change'    => 'changeFormType()',
                'options'   => array(
                    'popup'      => 'Popup',
                    'inpost'     => 'InPost',
                    'sidebar'    => 'Sidebar',
                    //'flyin'      => 'Fly In Form',
                    //'bar'        => 'Notification bar',
                ),
            ),

            'inpost-info-text'     => array(
                'el'        => 'paragraph',
                'restrict'  => "optinType=='inpost'",
                'content'   => 'Shortcode <strong>[noptin-form id={{id}}]</strong>',
            ),

            //What should happen after someone subscibes?
            'subscribeAction' => array(
                'el'        => 'select',
                'label'     => 'What should happen after the user subscribes',
                'options'   => array(
                    'message'   => 'Display a success message',
                    'redirect'  => 'redirect to a different page',
                    'close'     => 'Close the opt-in form',
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

            //Help text
            'info-text'     => array(
                'el'        => 'paragraph',
                'content'   => 'Display this optin...',
                'style'     => 'font-weight: bold;'
            ),

            //Display immeadiately
            'displayImmeadiately' => array(
                'type'      => 'checkbox',
                'el'        => 'input',
                'label'     => 'Immeadiately the page is loaded',
            ),

            //Once per session
            'DisplayOncePerSession' => array(
                'type'      => 'checkbox',
                'el'        => 'input',
                'label'     => 'Once per session',
            ),

            //Before the user leaves
            'enableExitIntent' => array(
                'type'      => 'checkbox',
                'el'        => 'input',
                'label'     => 'Before the user leaves',
            ),

            //After the user starts scrolling
            'enableScrollDepth' => array(
                'type'      => 'checkbox',
                'el'        => 'input',
                'label'     => 'After the user starts scrolling',
            ),

            //After the user leaves a comment
            'triggerAfterCommenting' => array(
                'type'      => 'checkbox',
                'el'        => 'input',
                'label'     => 'After the user leaves a comment',
            ),

            //After the user clicks something
            'triggerOnClick' => array(
                'type'      => 'checkbox',
                'el'        => 'input',
                'label'     => 'After the user clicks something',
            ),

            //CSS class of the items to watch out for clicks
            'cssClassOfClick' => array(
                'type'      => 'text',
                'el'        => 'input',
                'label'     => 'CSS class of the items to watch out for clicks',
                'restrict'  => 'triggerOnClick',
            ),

            //After a time delay
            'enableTimeDelay' => array(
                'type'      => 'checkbox',
                'el'        => 'input',
                'label'     => 'After a time delay',
            ),

            //Time in seconds to delay
            'timeDelayDuration' => array(
                'type'      => 'text',
                'el'        => 'input',
                'label'     => 'Time in seconds to delay',
                'restrict'  => 'enableTimeDelay',
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
        );

        foreach( noptin_get_post_types() as $name => $label ) {
            $return["showOn$name"] = array(
                'type'              => 'checkbox',
                'el'                => 'input',
                'label'             => $label,
                'restrict'          => "!showEverywhere && !_onlyShowOn",
            );
        }

        $return["neverShowOn"]  = array(
            'el'                => 'multiselect',
            'label'             => "Never show on:",
            'options'           => $this->post_ids_to_options( $this->post->neverShowOn),
            'data'              => 'all_posts',
            'restrict'          => "!_onlyShowOn",
        );

        $return["onlyShowOn"]  = array(
            'el'                => 'multiselect',
            'label'             => "Only show on:",
            'options'           => $this->post_ids_to_options( $this->post->onlyShowOn),
            'data'              => 'all_posts',
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

            'hideMediumScreens'     => array(
                'type'              => 'checkbox',
                'el'                => 'input',
                'label'             => 'Hide on Tablets',
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

            //Settings field title
            'section_title' => array(
                'el'        => 'paragraph',
                'content'   => "Use this panel to change the appearance of your popup form",
            ),

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
        $templates = noptin_get_optin_templates();
        return array(

            'colorTheme'        => array(
                'el'            => 'select',
                'label'         => 'Select a color theme',
                'options'       => array_combine( array_values( $colors ), array_keys( $colors ) ),
                '@input'        => 'changeColorTheme()',
            ),

            'Template'          => array(
                'el'            => 'select',
                'label'         => 'Select a template',
                'options'       => array_combine( array_keys( $templates ), array_keys( $templates ) ),
                '@input'        => 'changeTemplate()',
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
                'label'     => 'Hide Close Button',
            ),

            'closeButtonPos'=> array(
                'el'        => 'select',
                'options'       => array(
                    'inside'        => 'Inside the form',
                    'outside'       => 'Outside the form',
                    'along'         => 'Along the border'
                ),
                'label'     => 'Close Button Position',
                'restrict'  => "optinType=='popup' && !hideCloseButton",
            ),

            'singleLine' => array(
                'type'      => 'checkbox',
                'el'        => 'input',
                'label'     => 'Show all fields in a single line',
            ),

            'showNameField' => array(
                'type'      => 'checkbox',
                'el'        => 'input',
                'label'     => 'Display the name field',
            ),

            'firstLastName' => array(
                'type'      => 'checkbox',
                'el'        => 'input',
                'label'     => 'Request for both the first and last names',
                'restrict'  => 'showNameField',
            ),

            'requireNameField' => array(
                'type'      => 'checkbox',
                'el'        => 'input',
                'label'     => 'Require the user to fill out the name field',
                'restrict'  => 'showNameField',
            ),

            'formRadius'     => array(
                'type'       => 'text',
                'el'         => 'input',
                'label'      => 'Border Radius',
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

            'hideOnNoteClick'             => array(
                'type'                    => 'checkbox',
                'el'                      => 'input',
                'label'                   => 'Close popup when user clicks on note?',
                'restrict'                => "!hideNote && optinType=='popup'",
            ),

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
                '@input'   => 'updateCustomCss()'
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
        $state = array_replace( $saved_state, $this->get_panels_state(), $this->get_misc_state());
        return apply_filters( 'noptin_optin_form_editor_state', $state, $this );

    }


    /**
     * Returns the default panels state
     */
    private function get_panels_state() {
        return array(
            'noteDesignOpen'                => false,
            'descriptionDesignOpen'         => false,
            'titleDesignOpen'               => false,
            'buttonDesignOpen'              => false,
            'formDesignOpen'                => false,
            'targetingSettingsOpen'         => false,
            'userTargetingSettingsOpen'     => false,
            'deviceTargetingSettingsOpen'   => false,
            'triggerSettingsOpen'           => false,
            'basicSettingsOpen'             => false,
            'customCSSOpen'                 => false,
            'colorsDesignOpen'              => false,
            'imageDesignOpen'               => false,
            'currentSidebarSection'         => 'settings',
        );
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
