<?php
/**
 * Admin section
 *
 * Simple WordPress optin form
 *
 * @since             1.0.0
 *
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    die;
}

$sidebar = apply_filters('noptin_popup_sidebar_section', array(

    //Settings section
    'settings' => array(

        'section_title' => array(
            'el'        => 'paragraph',
            'content'   => "Use this panel to configure your popup form settings",
        ),

        'basic'        => array(
            'el'       => 'panel',
            'title'    => 'Basic Options',
            'id'       => 'basicSettings',
            'children' => array(
                'optinName' => array(
                    'el'        => 'input',
                    'label'     => 'Popup Name',
                ),
                'optinStatus'   => array(
                    'el'        => 'select',
                    'label'     => 'Popup Status',
                    'options'   => array(
                        'publish'   => 'Active',
                        'draft'     => 'Inactive',
                    ),
                ),
                'subscribeAction' => array(
                    'el'        => 'select',
                    'label'     => 'What should happen after the user subscribes',
                    'options'   => array(
                        'message'   => 'Display a success message',
                        'redirect'  => 'redirect to a different page',
                        'close'     => 'Close the popup form',
                    ),
                ),
                'successMessage' => array(
                    'type'      => 'text',
                    'el'        => 'input',
                    'label'     => 'Success message',
                    'restrict'  => "subscribeAction=='message'",
                ),
                'redirectUrl' => array(
                    'type'      => 'text',
                    'el'        => 'input',
                    'label'     => 'Redirect url',
                    'placeholde'=> 'http://example.com/success',
                    'restrict'  => "subscribeAction=='redirect'",
                ),
            ),
        ),

        //Trigger Options
        'trigger' => array(
                'el'       => 'panel',
                'title'    => 'Trigger Options',
                'id'       => 'triggerSettings',
                'children' => array(
                    'info-text'     => array(
                        'el'        => 'paragraph',
                        'content'   => 'Display this popup...',
                        'style'     => 'font-weight: bold;'
                    ),
                    'displayImmeadiately' => array(
                        'type'      => 'checkbox',
                        'el'        => 'input',
                        'label'     => 'Immeadiately the page is loaded',
                    ),
                    'DisplayOncePerSession' => array(
                        'type'      => 'checkbox',
                        'el'        => 'input',
                        'label'     => 'Once per session',
                    ),
                    'enableExitIntent' => array(
                        'type'      => 'checkbox',
                        'el'        => 'input',
                        'label'     => 'Before the user leavess',
                    ),
                    'enableScrollDepth' => array(
                        'type'      => 'checkbox',
                        'el'        => 'input',
                        'label'     => 'After the user starts scrolling',
                    ),
                    'scrollDepthPercentage' => array(
                        'type'      => 'text',
                        'el'        => 'input',
                        'label'     => 'Scroll percentage after which the popup should be shown',
                        'restrict'  => 'enableScrollDepth',
                    ),
                    'triggerAfterCommenting' => array(
                        'type'      => 'checkbox',
                        'el'        => 'input',
                        'label'     => 'After the user leaves a comment',
                    ),
                    'triggerOnClick' => array(
                        'type'      => 'checkbox',
                        'el'        => 'input',
                        'label'     => 'After the user clicks something',
                    ),
                    'cssClassOfClick' => array(
                        'type'      => 'text',
                        'el'        => 'input',
                        'label'     => 'CSS class of the items to watch out for clicks',
                        'restrict'  => 'triggerOnClick',
                    ),
                    'enableTimeDelay' => array(
                        'type'      => 'checkbox',
                        'el'        => 'input',
                        'label'     => 'After a time delay',
                    ),
                    'timeDelayDuration' => array(
                        'type'      => 'text',
                        'el'        => 'input',
                        'label'     => 'Time in seconds to delay',
                        'restrict'  => 'enableTimeDelay',
                    ),
                ),
        ),

        //Restriction Options
        'targeting'    => array(
            'el'       => 'panel',
            'title'    => 'Targeting Options',
            'id'       => 'targetingSettings',
            'children' => array(

                //Archives
                'hideOnArchives'        => array(
                    'type'              => 'checkbox',
                    'el'                => 'input',
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
                    'el'                => 'radio',
                    'label'             => 'Select Action',
                    'restrict'          => 'hideOnPages',
                    'options'           => array(
                        'show'  => 'Show on selected pages',
                        'hide'  => 'Hide on selected pages'
                    ),
                ),
                'pagesToHide'           => array(
                    'el'                => 'multiselect',
                    'label'             => 'Select pages on which to show/hide the popups',
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
                    'el'                => 'radio',
                    'label'             => 'Select Action',
                    'restrict'          => 'hideOnPosts',
                    'options'           => array(
                        'show'  => 'Show on selected posts',
                        'hide'  => 'Hide on selected posts'
                    ),
                ),
                'postsToHide'           => array(
                    'el'                => 'multiselect',
                    'label'             => 'Select posts on which to show/hide the popups',
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
                    'el'                => 'radio',
                    'label'             => 'Select Action',
                    'restrict'          => 'hideOnPostTypes',
                    'options'           => array(
                        'show'  => 'Show on selected post types',
                        'hide'  => 'Hide on selected post types'
                    ),
                ),
                'postTypesToHide'       => array(
                    'el'                => 'multiselect',
                    'label'             => 'Select post types on which to show/hide the popups',
                    'restrict'          => 'hideOnPostTypes',
                    'options'           => noptin_get_post_types(),
                ),

                //Tags
                /*'hideOnTags'           => array(
                    'type'              => 'checkbox',
                    'el'                => 'input',
                    'label'             => 'Show/Hide on specific tags',
                ),
                'tagsToHide'           => array(
                    'el'                => 'multiselect',
                    'label'             => 'Select tags on which to show/hide the popups',
                    'restrict'          => 'hideOnTags',
                    'options'           => 'tags',
                ),
                'tagRestrictType'      => array(
                    'type'              => 'radio',
                    'el'                => 'input',
                    'label'             => 'Select Action',
                    'restrict'          => 'hideOnTags',
                    'options'           => array(
                        'show'  => 'Show on selected tags',
                        'hide'  => 'Hide on selected tags'
                    ),
                ),

                //Cats
                'hideOnCats'            => array(
                    'type'              => 'checkbox',
                    'el'                => 'input',
                    'label'             => 'Show/Hide on specific categories',
                ),
                'catsToHide'            => array(
                    'el'                => 'multiselect',
                    'label'             => 'Select categories on which to show/hide the popups',
                    'restrict'          => 'hideOnCats',
                    'options'           => 'categories',
                ),
                'catRestrictType'       => array(
                    'el'                => 'radio',
                    'label'             => 'Select Action',
                    'restrict'          => 'hideOnCats',
                    'options'           => array(
                        'show'  => 'Show on selected categories',
                        'hide'  => 'Hide on selected categories'
                    ),
                ),*/
            ),

        ),
    ),


    'design'   => array(

        'section_title' => array(
            'el'       => 'paragraph',
            'content'  => 'Use this panel to change the appearance of your popup form',
        ),

        //Form Design
        'form'         => array(
            'el'       => 'panel',
            'title'    => 'Form',
            'id'       => 'formDesign',
            'children' => array(
                
                'removeBranding' => array(
                    'type'      => 'checkbox',
                    'el'        => 'input',
                    'label'     => 'Remove Branding',
                ),
                'hideCloseButton' => array(
                    'type'      => 'checkbox',
                    'el'        => 'input',
                    'label'     => 'Hide Close Button',
                ),
                'singleLine' => array(
                    'type'      => 'checkbox',
                    'el'        => 'input',
                    'label'     => 'Show all fields in a single line',
                ),
                'buttonPosition'=> array(
                    'el'        => 'select',
                    'options'       => array(
                        'block'     => 'Full Width',
                        'left'      => 'Left',
                        'right'     => 'Right'
                    ),
                    'label'     => 'Button Position',
                    'restrict'  => '!singleLine',
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
            ),
        ),

        //Button Design
        'button'       => array(
            'el'       => 'panel',
            'title'    => 'Button',
            'id'       => 'buttonDesign',
            'children' => array(
                'noptinButtonLabel'     => array(
                    'type'              => 'text',
                    'el'                => 'input',
                    'label'             => 'Button Label',
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
                
            ),
        ),

        //Title design
        'title'        => array(
            'el'       => 'panel',
            'title'    => 'Title',
            'id'       => 'titleDesign',
            'children' => array(
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
                
            ),
        ),

        //Description design
        'description'  => array(
            'el'       => 'panel',
            'title'    => 'Description',
            'id'       => 'descriptionDesign',
            'children' => array(
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
                
            ),
        ),

        //Note design
        'note'          => array(
            'el'        => 'panel',
            'title'     => 'Note',
            'id'        => 'noteDesign',
            'children' => array(
                'noptin_hide_note'   => array(
                    'type'           => 'checkbox',
                    'el'             => 'input',
                    'label'          => 'Hide note',
                ),

                'noptin_note_text'   => array(
                    'type'           => 'text',
                    'el'             => 'input',
                    'label'          => 'Note',
                    'restrict'       => '!noptin_hide_note'
                ),
                'noptin_note_color'  => array(
                    'type'           => 'color',
                    'el'             => 'input',
                    'label'          => 'Note Color',
                    'restrict'       => '!noptin_hide_note'
                ),
                
            ),
        ),

        //Custom css
        'css'          => array(
            'el'        => 'panel',
            'title'     => 'Custom CSS',
            'id'        => 'customCSS',
            'children' => array(
                'custom_css'   => array(
                    'el'       => 'textarea',
                    'label'    => 'Enter Your Custom CSS',
                    '@input'   => 'updateCustomCss()'
                ),
                
            ),
        ),
    ), 
));

$meta = get_post_meta( $popup, '_noptin_state' );

$settings = array(

    //Panels
    'noteDesignOpen'                => false,
    'descriptionDesignOpen'         => false,
    'titleDesignOpen'               => false,
    'buttonDesignOpen'              => false,
    'formDesignOpen'                => false,
    'targetingSettingsOpen'         => false,
    'triggerSettingsOpen'           => false,
    'basicSettingsOpen'             => false,
    'customCSSOpen'                 => false,
    'currentSidebarSection'         => 'settings',

    //Opt in options
    'removeBranding'                => false,
    'hideCloseButton'               => false,
    'optinName'                     => 'Sample Name',
    'optinStatus'                   => 'draft',
    'singleLine'                    => true,
    'buttonPosition'                => 'block',
    'showNameField'                 => false,
    'requireNameField'              => false,
    'firstLastName'                 => false,
    'subscribeAction'               => 'close', //close, redirect
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
    'isPreviewShowing'              => false,
    
);

if( is_array( $meta ) ) {
    $settings = wp_parse_args( $meta, $settings );
}

$popup = get_post( $popup );
$settings[ 'optinName' ]   = $popup->post_title;
$settings[ 'optinStatus' ] = $popup->post_status;

$settings = wp_json_encode( apply_filters('noptin_popup_sidebar_settings', $settings ) );
?>
<style id="popupCustomCSS"></style>
<div class="noptin-popup-designer">
    
    <div id="noptin-popups-app" v-if="!isPreviewShowing">
        <div class="noptin-popup-editor-header" tabindex="-1">
            <div class="noptin-popup-editor-title">Popup Editor &mdash; {{optinName}}</div>
            <div class="noptin-popup-editor-toolbar">
                <button @click="previewPopup()" type="button" class="button button-secondary noptin-popup-editor-header-button">Preview</button>
                <button type="button" class="button button-primary noptin-popup-editor-header-button">Save</button>
            </div>
        </div>
        <div class="noptin-divider"></div>
        <div class="noptin-popup-editor-body">
            <div class="noptin-popup-editor-main">
                <div class="noptin-popup-editor-main-preview">
                    <div  class="noptin-popup-wrapper"  :style="custom_css">
                        <div  class="noptin-popup-form-wrapper" :style="{backgroundColor: noptinFormBg, width: formWidth, minHeight: formHeight, borderColor: noptinFormBorderColor}">
                            <form :class="singleLine ? 'noptin-popup-single-line' : 'noptin-popup-new-line'">
                                <div v-if="!noptin_hide_title" :style="{color:noptin_title_color}" class="noptin-popup-form-heading" v-html="noptin_title_text"></div>
                                <div v-if="!noptin_hide_description"  :style="{color:noptin_description_color}" class="noptin-popup-form-description" v-html="noptin_description_text"></div>
                                <div class="noptin-popup-fields" :style="{display: singleLine? 'flex' : 'block'}">
                                    <input v-if="showingFullName" type="text" class="noptin-popup-field" placeholder="Full Names" :required="requireNameField" />
                                    <input v-if="showingSingleName" type="text" class="noptin-popup-field" placeholder="First Name" :required="requireNameField" />
                                    <input v-if="showingSingleName" type="text" class="noptin-popup-field" placeholder="Last Name" :required="requireNameField" />
                                    <input type="email" class="noptin-popup-field" placeholder="Email Address" required />
                                    <input :value="noptinButtonLabel" type="submit" :style="{backgroundColor:noptinButtonBg, color: noptinButtonColor}" :class="singleLine ? '' : 'noptin-popup-botton-' + buttonPosition" class="noptin-popup-submit"/>
                                    <input type="hidden" class="noptin_form_redirect"/>
                                </div>
                                <p v-if="!noptin_hide_note" :style="{ color: noptin_note_color}" class="noptin-popup-note" v-html="noptin_note_text"></p>
                            <div style="border:1px solid rgba(6, 147, 227, 0.8);display:none;padding:10px;margin-top:10px" class="noptin_feedback_success"></div>
                            <div style="border:1px solid rgba(227, 6, 37, 0.8);display:none;padding:10px;margin-top:10px" class="noptin_feedback_error"></div>
                            </form>
                            <span v-if="!hideCloseButton" class="noptin-popup-close" title="close"><svg enable-background="new 0 0 24 24" id="Layer_1" version="1.0" viewBox="0 0 24 24" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><path d="M12,2C6.5,2,2,6.5,2,12c0,5.5,4.5,10,10,10s10-4.5,10-10C22,6.5,17.5,2,12,2z M16.9,15.5l-1.4,1.4L12,13.4l-3.5,3.5   l-1.4-1.4l3.5-3.5L7.1,8.5l1.4-1.4l3.5,3.5l3.5-3.5l1.4,1.4L13.4,12L16.9,15.5z"/></g></svg></span> 
                        </div>
                    </div>
                </div>
            </div>
            <div class="noptin-popup-editor-sidebar">
                <div class="noptin-popup-editor-sidebar-header">
                    <ul>
                        <?php
                            foreach ( array_keys($sidebar) as $panel ) {
                                $_panel = ucfirst($panel);
                                echo "
                                    <li>
                                        <button 
                                            class='noptin-popup-editor-sidebar-{$panel}'
                                            :class=\"{ active: currentSidebarSection == '$panel' }\"
                                            @click=\"currentSidebarSection = '$panel'\">$_panel</button>
                                    </li>
                                ";
                            }
                        ?>
                    </ul>
                </div>
                <div class="noptin-popup-editor-sidebar-body">

                <?php
                    foreach ( $sidebar as $panel => $fields ) {
                        echo " <div class='noptin-popup-editor-{$panel}-fields'  v-if=\"'{$panel}'==currentSidebarSection\">";
                        foreach( $fields as $id=>$field ){
                            noptin_render_editor_field( $id, $field );
                        }
                        echo "</div>";
                    }
                ?>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="noptin-popup-preview"></div>
<script>
var vm = new Vue({
    el: '#noptin-popups-app',
    data: <?php echo $settings;?>,
    computed: {
        showingFullName: function(){
            return this.showNameField && !this.firstLastName
        },
        showingSingleName: function(){
            return this.showNameField && this.firstLastName
        }
    },
    methods: {
        previewPopup: function(){
            this.isPreviewShowing = true
            var _html = jQuery('.noptin-popup-wrapper').html()
            jQuery("#noptin-popup-preview")
                .html(_html)
                .addClass('noptin-preview-showing')
                .find('.noptin-popup-close')
                .show()
                .on( 'click', function(){
                    vm.closePopup()
                })

            //Hide popup when user clicks outside
            jQuery("#noptin-popup-preview")
                .off('noptin-popup')
                .on( 'click', function(e){
                    var container = jQuery(this).find(".noptin-popup-form-wrapper");

                    // if the target of the click isn't the container nor a descendant of the container
                    if (!container.is(e.target) && container.has(e.target).length === 0){
                        vm.closePopup()
                    }
                });
        },
        closePopup: function(){
            this.isPreviewShowing = false
            jQuery("#noptin-popup-preview").removeClass('noptin-preview-showing').html('')
        },
        updateCustomCss: function(){
            jQuery('#popupCustomCSS').text(this.custom_css)
        },
    },
    mounted: function() {
        jQuery('#popupCustomCSS').text(this.custom_css)
    },
})
</script>