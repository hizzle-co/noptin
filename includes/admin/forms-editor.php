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
        $this->post = get_post( $id );

        if( $localize ) {
            $this->localize_script();
        }
    }

    /**
     * Localizes JS script
     */
    public function localize_script() {
        $params = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'api_url' => get_home_url( null, 'wp-json/wp/v2/'),
            'nonce'   => wp_create_nonce('noptin_admin_nonce'),
            'data'    => $this->get_state(),
        );
        wp_localize_script('noptin', 'noptinEditor', $params);
    }

    /**
     * Displays the editor
     */
    public function output() {
        $sidebar = $this->sidebar_fields();
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
                'title'     => 'Targeting Options',
                'id'        => 'targetingSettings',
                'children'  => $this->get_targeting_settings()
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
                'content'   => 'Display this popup...',
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
        return array(

            //Archives
            'hideOnArchives'        => array(
                'type'              => 'checkbox',
                'el'                => 'input',
                'restrict'          => "optinType!='inpost'",
                'label'             => 'Hide on archive pages',
            ),

            'hideOnMobile'          => array(
                'type'              => 'checkbox',
                'el'                => 'input',
                'label'             => 'Hide on small screens',
            ),

            //Pages
            'hideOnPages'           => array(
                'type'              => 'checkbox',
                'el'                => 'input',
                'label'             => 'Show/Hide on specific pages',
            ),
            'pageRestrictType'      => array(
                'el'                => 'radio_button',
                'restrict'          => 'hideOnPages',
                'options'           => array(
                    'show'  => 'Show',
                    'hide'  => 'Hide'
                ),
            ),
            'pagesToHide'           => array(
                'el'                => 'multiselect',
                'label'             => 'Select pages on which to {{pageRestrictType}} the popup',
                'restrict'          => 'hideOnPages',
                'options'           => 'pages',
            ),

            //Posts
            'hideOnPosts'           => array(
                'type'              => 'checkbox',
                'el'                => 'input',
                'label'             => 'Show/Hide on specific posts',
            ),
            'postRestrictType'      => array(
                'el'                => 'radio_button',
                'restrict'          => 'hideOnPosts',
                'options'           => array(
                    'show'  => 'Show',
                    'hide'  => 'Hide'
                ),
            ),
            'postsToHide'           => array(
                'el'                => 'multiselect',
                'label'             => 'Select posts on which to {{postRestrictType}} the popup',
                'restrict'          => 'hideOnPosts',
                'options'           => 'posts',
            ),

            //PostTypes
            'hideOnPostTypes'       => array(
                'type'              => 'checkbox',
                'el'                => 'input',
                'label'             => 'Show/Hide on specific post types',
            ),
            'postTypesRestrictType' => array(
                'el'                => 'radio_button',
                'restrict'          => 'hideOnPostTypes',
                'options'           => array(
                    'show'  => 'Show',
                    'hide'  => 'Hide'
                ),
            ),
            'postTypesToHide'       => array(
                'el'                => 'multiselect',
                'label'             => 'Select post types on which to {{postTypesRestrictType}} the popup',
                'restrict'          => 'hideOnPostTypes',
                'options'           => noptin_get_post_types(),
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

            //Form Design
            'form'         => array(
                'el'        => 'panel',
                'title'     => 'Form',
                'id'        => 'formDesign',
                'children'  => $this->get_form_settings()
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
     * Returns Form Design Fields
     */
    private function get_form_settings() {
        return array(

            'removeBranding' => array(
                'type'      => 'checkbox',
                'el'        => 'input',
                'label'     => 'Remove Branding',
            ),

            'hideCloseButton' => array(
                'type'      => 'checkbox',
                'el'        => 'input',
                'restrict'  => "optinType=='popup'",
                'label'     => 'Hide Close Button',
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

            'formWidth'             => array(
                'type'              => 'text',
                'el'                => 'input',
                'label'             => 'Form Width',
            ),

            'formHeight'            => array(
                'type'              => 'text',
                'el'                => 'input',
                'label'             => 'Form Height',
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

            'noptin_hide_title'     => array(
                'type'              => 'checkbox',
                'el'                => 'input',
                'label'             => 'Hide title',
            ),

            'noptin_title_text'     => array(
                'type'              => 'text',
                'el'                => 'input',
                'label'             => 'Title',
                'restrict'          => '!noptin_hide_title'
            ),

            'noptin_title_color'    => array(
                'type'              => 'color',
                'el'                => 'input',
                'label'             => 'Title Color',
                'restrict'          => '!noptin_hide_title'
            ),
        );
    }

    /**
     * Returns Description Design Fields
     */
    private function get_description_settings() {
        return array(

            'noptin_hide_description'   => array(
                'type'                  => 'checkbox',
                'el'                    => 'input',
                'label'                 => 'Hide description',
            ),

            'noptin_description_text'   => array(
                'el'                    => 'textarea',
                'label'                 => 'Description',
                'restrict'              => '!noptin_hide_description'
            ),
            'noptin_description_color'  => array(
                'type'                  => 'color',
                'el'                    => 'input',
                'label'                 => 'Description Color',
                'restrict'              => '!noptin_hide_description'
            ),
            
        );
    }

    /**
     * Returns Note Design Fields
     */
    private function get_note_settings() {
        return array(

            'noptin_hide_note'            => array(
                'type'                    => 'checkbox',
                'el'                      => 'input',
                'label'                   => 'Hide note',
            ),

            'noptin_hide_on_note_click'   => array(
                'type'                    => 'checkbox',
                'el'                      => 'input',
                'label'                   => 'Close popup when user clicks on note?',
                'restrict'                => "!noptin_hide_note && optinType=='popup'",
            ),

            'noptin_note_text'            => array(
                'type'                    => 'text',
                'el'                      => 'input',
                'label'                   => 'Note',
                'restrict'                => '!noptin_hide_note'
            ),
            'noptin_note_color'           => array(
                'type'                    => 'color',
                'el'                      => 'input',
                'label'                   => 'Note Color',
                'restrict'                => '!noptin_hide_note'
            ),
            
        );
    }

    /**
     * Returns Custom css Fields
     */
    private function get_custom_css_settings() {
        return array(

            'custom_css'   => array(
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

        $saved_state = get_post_meta( $this->post->ID, '_noptin_state', true );
        if(! is_array( $saved_state ) ) {
            $saved_state = array();
        }

        $_saved_state = array();
        foreach( $saved_state as $key => $value ){
            if( 'false' == $value ) {
                $_saved_state[$key] = false;
                continue;
            }
            if( 'true' == $value ) {
                $_saved_state[$key] = true;
                continue;
            }
            $_saved_state[$key] = $value;
        }

        $state = array_replace( $this->get_panels_state(), $this->get_options_state(), $_saved_state, $this->get_misc_state());
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
            'triggerSettingsOpen'           => false,
            'basicSettingsOpen'             => true,
            'customCSSOpen'                 => false,
            'currentSidebarSection'         => 'settings',
        );
    }

    /**
     * Returns the default options state
     */
    private function get_options_state() {
        return array(
            
            //Opt in options
            'removeBranding'                => false,
            'hideCloseButton'               => false,
            'optinName'                     => 'Sample Name',
            'optinStatus'                   => 'draft',
            'optinType'                     => 'popup',
            'singleLine'                    => true,
            'buttonPosition'                => 'block',
            'showNameField'                 => false,
            'requireNameField'              => false,
            'firstLastName'                 => false,
            'subscribeAction'               => 'message', //close, redirect
            'successMessage'                => 'Thank you for subscribing to our newsletter',
            'redirectUrl'                   => '',

            //Form Design
            'noptinFormBg'                  => '#2196f3',
            'noptinFormBorderColor'         => '#2196f3',
            'noptinFormBorderRound'         => true,
            'formWidth'                     => '520px',
            'formHeight'                    => '250px',

            //Button designs
            'noptinButtonBg'                => '#fafafa',
            'noptinButtonColor'             => '#2196F3',
            'noptinButtonLabel'             => 'SUBSCRIBE',

            //Title design
            'noptin_hide_title'             => false,
            'noptin_title_text'             => 'Subscribe To Our Newsletter',
            'noptin_title_color'            => '#fafafa',

            //Description design
            'noptin_hide_description'       => false,
            'noptin_description_text'       => 'Join our mailing list to receive the latest news and updates from our team.',
            'noptin_description_color'      => '#fafafa',

            //Note design
            'noptin_hide_note'              => true,
            'noptin_note_text'              => 'Your privacy is our priority',
            'noptin_note_color'             => '#d8d8d8',
            'noptin_hide_on_note_click'     => false,

            //Trigger Options
            'enableTimeDelay'               => false,
            'timeDelayDuration'             => 10,
            'enableExitIntent'              => false,
            'enableScrollDepth'             => false,
            'scrollDepthPercentage'         => 25,
            'hideOnMobile'                  => true,
            'DisplayOncePerSession'         => true,
            'triggerOnClick'                => false,
            'cssClassOfClick'               => '',
            'triggerAfterCommenting'        => false,
            'displayImmeadiately'           => true,

            //Restriction Options
            'hideOnPages'                   => false,
            'pageRestrictType'              => 'show', //hide
            'pagesToHide'                   => array(),
            'hideOnPosts'                   => false,
            'postRestrictType'              => 'show', //hide
            'postsToHide'                   => array(),
            'hideOnTags'                    => false,
            'tagRestrictType'               => 'show', //hide
            'tagsToHide'                    => array(),
            'hideOnPostTypes'               => false,
            'postTypesRestrictType'         => 'show', //hide
            'postTypesToHide'               => array(),
            'hideOnCats'                    => false,
            'catRestrictType'               => 'show', //hide
            'catsToHide'                    => array(),
            'hideOnArchives'                => false,

            //custom css                    
            'custom_css'                    => '',
        
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
            'savingError'                   => __( 'There was an error saving your form.', 'noptin'),
            'savingSuccess'                 => __( 'Your changes have been saved successfuly', 'noptin'),
            'previewText'                   => __( 'Preview', 'noptin'),
            'isPreviewShowing'              => false,
            'optinName'                     => $this->post->post_title,
            'optinStatus'                   => $this->post->post_status,
            'id'                            => $this->post->ID,
        );
    }
}
