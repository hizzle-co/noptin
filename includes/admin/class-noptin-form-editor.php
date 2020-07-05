<?php
/**
 * Optin Form Editor
 *
 * Responsible for editing the optin forms
 *
 * @since             1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class Noptin_Form_Editor {

	/**
	 * Id of the form being edited
	 *
	 * @access      public
	 * @since       1.0.0
	 */
	public $id = null;

	/**
	 * Post object of the form being edited
	 *
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

		if ( $localize ) {
			noptin_localize_optin_editor( $this->get_state() );
		}
	}

	/**
	 * Displays the editor
	 */
	public function output() {
		$sidebar = $this->sidebar_fields();
		$state   = $this->get_state();
		get_noptin_template( 'optin-form-editor.php', compact( 'sidebar', 'state' ) );
	}

	/**
	 * Returns sidebar fields
	 */
	public function sidebar_fields() {
		$fields = array(
			'settings'     => $this->get_setting_fields(),
			'design'       => $this->get_design_fields(),
			'integrations' => $this->get_integration_fields(),
		);

		$fields['settings']['label']     = __( 'Settings', 'newsletter-optin-box' );
		$fields['design']['label']       = __( 'Design', 'newsletter-optin-box' );
		$fields['integrations']['label'] = __( 'Integrations', 'newsletter-optin-box' );

		/**
		 * Filters the Noptin Form Editor's sidebar fields.
		 * 
		 * @param array $fields Sidebar fields.
		 * @param Noptin_Form_Editor $form_editor The form editor instance.
		 */
		return apply_filters( 'noptin_optin_form_editor_sidebar_section', $fields, $this );
	}

	/**
	 * Returns setting fields fields
	 */
	private function get_setting_fields() {
		$popup    = __( 'Popup Options', 'newsletter-optin-box' );
		$slide_in = __( 'Sliding Options', 'newsletter-optin-box' );
		return array(

			// Basic settings.
			'basic'           => array(
				'el'       => 'panel',
				'title'    => __( 'Basic Options', 'newsletter-optin-box' ),
				'id'       => 'basicSettings',
				'children' => $this->get_basic_settings(),
			),

			// Trigger Options.
			'trigger'         => array(
				'el'       => 'panel',
				'title'    => "<span v-if=\"optinType=='popup'\">$popup</span><span v-if=\"optinType=='slide_in'\">$slide_in</span>",
				'id'       => 'triggerSettings',
				'restrict' => "optinType=='popup' || optinType=='slide_in'",
				'children' => $this->get_trigger_settings(),
			),

			// Targeting Options.
			'targeting'       => array(
				'el'       => 'panel',
				'title'    => __( 'Page Targeting', 'newsletter-optin-box' ),
				'id'       => 'targetingSettings',
				'children' => $this->get_targeting_settings(),
			),

			// User targeting.
			/*
			'userTargeting' => array(
				'el'        => 'panel',
				'title'     => 'User Targeting',
				'id'        => 'userTargetingSettings',
				'children'  => $this->get_user_settings()
			),*/

			// Device targeting.
			'deviceTargeting' => array(
				'el'       => 'panel',
				'title'    => __( 'Device Targeting', 'newsletter-optin-box' ),
				'id'       => 'deviceTargetingSettings',
				'children' => $this->get_device_settings(),
			),

		);
	}

	/**
	 * Returns basic settings fields
	 */
	private function get_basic_settings() {
		return array(

			// Should we display the form on the frontpage?
			'optinStatus'     => array(
				'type'    => 'checkbox',
				'el'      => 'input',
				'tooltip' => __( 'Your website visitors will not see this form unless you check this box', 'newsletter-optin-box' ),
				'label'   => __( 'Publish', 'newsletter-optin-box' ),
			),

			// Form type.
			'optinType'       => array(
				'el'      => 'select',
				'label'   => __( 'Form type', 'newsletter-optin-box' ),
				'tooltip' => __( 'Select how you would like to display the form', 'newsletter-optin-box' ),
				'options' => array(
					'popup'    => __( 'Popup', 'newsletter-optin-box' ),
					'inpost'   => __( 'Shortcode', 'newsletter-optin-box' ),
					'sidebar'  => __( 'Widget', 'newsletter-optin-box' ),
					'slide_in' => __( 'Sliding', 'newsletter-optin-box' ),
				),
			),

			'inject'          => array(
				'el'       => 'select',
				'restrict' => "optinType=='inpost'",
				'label'    => __( 'Inject into post content', 'newsletter-optin-box' ),
				'tooltip'  => __( "Noptin can automatically embed this form into your post content. You can also find the form's shortcode below the form preview", 'newsletter-optin-box' ),
				'options'  => array(
					'0'      => __( "Don't inject", 'newsletter-optin-box' ),
					'before' => __( 'Before post content', 'newsletter-optin-box' ),
					'after'  => __( 'After post content', 'newsletter-optin-box' ),
					'both'   => __( 'Before and after post content', 'newsletter-optin-box' ),
				),
			),

			// What should happen after someone subscibes?
			'subscribeAction' => array(
				'el'      => 'select',
				'label'   => __( 'What should happen after the user subscribes', 'newsletter-optin-box' ),
				'options' => array(
					'message'  => __( 'Display a success message', 'newsletter-optin-box' ),
					'redirect' => __( 'redirect to a different page', 'newsletter-optin-box' ),
				),
			),

			// Success message after subscription.
			'successMessage'  => array(
				'type'     => 'textarea',
				'el'       => 'textarea',
				'label'    => __( 'Success message', 'newsletter-optin-box' ),
				'restrict' => "subscribeAction=='message'",
			),

			// Where should we redirect the user after subscription?
			'redirectUrl'     => array(
				'type'       => 'text',
				'el'         => 'input',
				'label'      => __( 'Redirect url', 'newsletter-optin-box' ),
				'placeholde' => 'http://example.com/success',
				'restrict'   => "subscribeAction=='redirect'",
			),

		);
	}

	/**
	 * Returns trigger settings fields
	 */
	private function get_trigger_settings() {
		return array(

			// Once per session.
			'DisplayOncePerSession' => array(
				'type'     => 'checkbox',
				'el'       => 'input',
				'tooltip'  => __( 'Uncheck to display the form once per session instead of once per week', 'newsletter-optin-box' ),
				'label'    => __( 'Display this form once per week', 'newsletter-optin-box' ),
				'restrict' => "triggerPopup!='after_click' && optinType=='popup'",
			),

			// Sliding direction.
			'slideDirection'       => array(
				'el'       => 'select',
				'label'    => __( 'The form will slide from...', 'newsletter-optin-box' ),
				'restrict' => "optinType=='slide_in'",
				'options'  => array(
					'top_left'      => __( 'Top Left', 'newsletter-optin-box' ),
					'left_top'      => __( 'Top Left Alt', 'newsletter-optin-box' ),
					'top_right'     => __( 'Top Right', 'newsletter-optin-box' ),
					'right_top'     => __( 'Top Right Alt', 'newsletter-optin-box' ),
					'bottom_left'   => __( 'Bottom Left', 'newsletter-optin-box' ),
					'left_bottom'   => __( 'Bottom Left Alt', 'newsletter-optin-box' ),
					'bottom_right'  => __( 'Bottom right', 'newsletter-optin-box' ),
					'right_bottom'  => __( 'Bottom right Alt', 'newsletter-optin-box' ),
				),
			),

			// trigger when.
			'triggerPopup'          => array(
				'el'      => 'select',
				'label'   => __( 'Show this form', 'newsletter-optin-box' ),
				'options' => array(
					'immeadiate'   => __( 'Immediately', 'newsletter-optin-box' ),
					'before_leave' => __( 'Before the user leaves', 'newsletter-optin-box' ),
					'on_scroll'    => __( 'After the user starts scrolling', 'newsletter-optin-box' ),
					'after_click'  => __( 'After clicking on something', 'newsletter-optin-box' ),
					'after_delay'  => __( 'After a time delay', 'newsletter-optin-box' ),
				),
			),

			// CSS class of the items to watch out for clicks.
			'cssClassOfClick'       => array(
				'type'     => 'text',
				'el'       => 'input',
				'label'    => __( 'CSS selector of the items to watch out for clicks', 'newsletter-optin-box' ),
				'restrict' => "triggerPopup=='after_click'",
			),

			// Time in seconds to delay.
			'timeDelayDuration'     => array(
				'type'     => 'text',
				'el'       => 'input',
				'label'    => __( 'Time in seconds to delay', 'newsletter-optin-box' ),
				'restrict' => "triggerPopup=='after_delay'",
			),

			// Scroll depth.
			'scrollDepthPercentage' => array(
				'type'     => 'text',
				'el'       => 'input',
				'label'    => __( 'Scroll depth in percentage after which the form will appear', 'newsletter-optin-box' ),
				'restrict' => "triggerPopup=='on_scroll'",
			),
		);
	}

	/**
	 * Returns setting fields fields
	 */
	private function get_targeting_settings() {

		$return = array(

			'targeting-info-text' => array(
				'el'      => 'paragraph',
				'content' => __( 'Display this optin...', 'newsletter-optin-box' ),
				'style'   => 'font-weight: bold;',
			),

			'showEverywhere'      => array(
				'type'     => 'checkbox',
				'el'       => 'input',
				'label'    => __( 'Everywhere', 'newsletter-optin-box' ),
				'restrict' => '!_onlyShowOn',
			),
			'showHome'            => array(
				'type'     => 'checkbox',
				'el'       => 'input',
				'label'    => __( 'Front page', 'newsletter-optin-box' ),
				'restrict' => '!showEverywhere && !_onlyShowOn',
			),
			'showBlog'            => array(
				'type'     => 'checkbox',
				'el'       => 'input',
				'label'    => __( 'Blog page', 'newsletter-optin-box' ),
				'restrict' => '!showEverywhere && !_onlyShowOn',
			),
			'showSearch'          => array(
				'type'     => 'checkbox',
				'el'       => 'input',
				'label'    => __( 'Search page', 'newsletter-optin-box' ),
				'restrict' => "optinType!='inpost' && !showEverywhere && !_onlyShowOn",
			),
			'showArchives'        => array(
				'type'     => 'checkbox',
				'el'       => 'input',
				'label'    => __( 'Archive pages', 'newsletter-optin-box' ),
				'restrict' => "optinType!='inpost' && !showEverywhere && !_onlyShowOn",
			),
			'showPostTypes'       => array(
				'el'       => 'multi_checkbox',
				'options'  => noptin_get_post_types(),
				'restrict' => '!showEverywhere && !_onlyShowOn',
			),
		);

		$return['neverShowOn'] = array(
			'el'          => 'input',
			'label'       => __( 'Never show on:', 'newsletter-optin-box' ),
			'options'     => $this->post->neverShowOn,
			'restrict'    => '!_onlyShowOn',
			'placeholder' => '1,10,25,' . noptin_clean_url( home_url( 'contact' ) ),
			'tooltip'     => __( 'Use a comma to separate post ids or urls where this form should not be displayed. All post type ids (page, products, etc) are supported, not just blog post ids.', 'newsletter-optin-box' ),
		);

		$return['onlyShowOn'] = array(
			'el'          => 'input',
			'label'       => 'Only show on:',
			'placeholder' => '3,14,5,' . noptin_clean_url( home_url( 'about' ) ),
			'tooltip'     => __( 'If you specify any posts here, all other targeting rule will be ignored, and this form will only be displayed on posts or urls that you specify here.', 'newsletter-optin-box' ),
			'options'     => $this->post->onlyShowOn,
		);

		return $return;
	}

	/**
	 * Returns setting fields fields
	 */
	private function get_user_settings() {
		return array(

			'whoCanSee' => array(
				'el'      => 'radio_button',
				'options' => array(
					'all'    => __( 'Everyone', 'newsletter-optin-box' ),
					'users'  => __( 'Logged in users', 'newsletter-optin-box' ),
					'guests' => __( 'Logged out users', 'newsletter-optin-box' ),
					'roles'  => __( 'specific user roles', 'newsletter-optin-box' ),
				),
				'label'   => __( 'Who can see this form?', 'newsletter-optin-box' ),
			),

			'userRoles' => array(
				'el'       => 'multiselect',
				'label'    => __( 'Select user roles', 'newsletter-optin-box' ),
				'restrict' => "whoCanSee=='roles'",
				'options'  => array(),
			),

		);

	}

	/**
	 * Returns setting fields fields
	 */
	private function get_device_settings() {
		return array(

			'hideSmallScreens' => array(
				'type'  => 'checkbox',
				'el'    => 'input',
				'label' => __( 'Hide on Mobile', 'newsletter-optin-box' ),
			),

			'hideLargeScreens' => array(
				'type'  => 'checkbox',
				'el'    => 'input',
				'label' => __( 'Hide on Desktops', 'newsletter-optin-box' ),
			),

		);

	}

	/**
	 * Returns integration settings fields
	 */
	private function get_integration_fields() {
		$fields = array(

			'mailchimp'    => array(
				'el'       => 'panel',
				'title'    => 'Mailchimp',
				'id'       => 'mailchimp',
				'children' => array(
					'mailchimptext' => array(
						'el'      => 'paragraph',
						'content' => sprintf(
							esc_html__( 'Install the %s to connect your mailchimp account.', 'newsletter-optin-box' ),
							sprintf( '<a target="_blank" href="https://noptin.com/product/mailchimp/?utm_medium=plugin-dashboard&utm_campaign=editor&utm_source=%s"> MailChimp addon</a>', get_home_url() )
						),
						'style'   => 'color:#F44336;',
					),
				),
			),
			
			'campaign_monitor' => array(
				'el'       => 'panel',
				'title'    => 'Campaign Monitor',
				'id'       => 'campaign_monitor',
				'children' => array(
					'campaign_monitor' => array(
						'el'      => 'paragraph',
						'content' => sprintf(
							esc_html__( 'Install the %s to connect your Campaign Monitor account.', 'newsletter-optin-box' ),
							sprintf( '<a target="_blank" href="https://noptin.com/product/campaign-monitor/?utm_medium=plugin-dashboard&utm_campaign=editor&utm_source=%s">Campaign Monitor addon</a>', get_home_url() )
						),
						'style'   => 'color:#F44336;',
					),
				),
			),

			'convertkit' => array(
				'el'       => 'panel',
				'title'    => 'ConvertKit',
				'id'       => 'convertkit',
				'children' => array(
					'convertkittext' => array(
						'el'      => 'paragraph',
						'content' => sprintf(
							esc_html__( 'Install the %s to connect your convertkit account.', 'newsletter-optin-box' ),
							sprintf( '<a target="_blank" href="https://noptin.com/product/convertkit/?utm_medium=plugin-dashboard&utm_campaign=editor&utm_source=%s"> ConvertKit addon</a>', get_home_url() )
						),
						'style'   => 'color:#F44336;',
					),
				),
			),

			'drip'         => array(
				'el'       => 'panel',
				'title'    => 'Drip',
				'id'       => 'drip',
				'children' => array(
					'drip'        => array(
						'el'      => 'paragraph',
						'content' => sprintf(
							esc_html__( 'Install the %s to connect your Drip account.', 'newsletter-optin-box' ),
							sprintf( '<a target="_blank" href="https://noptin.com/product/drip/?utm_medium=plugin-dashboard&utm_campaign=editor&utm_source=%s">Drip addon</a>', get_home_url() )
						),
						'style'   => 'color:#F44336;',
					),
				),
			),

		);

		ksort( $fields );
		return $fields;
	}

	/**
	 * Returns design settings fields
	 */
	private function get_design_fields() {
		return array(

			// Color themes.
			'colors'      => array(
				'el'       => 'panel',
				'title'    => __( 'Templates', 'newsletter-optin-box' ),
				'id'       => 'colorsDesign',
				'children' => $this->get_templates_settings(),
			),

			// Form Design.
			'form'        => array(
				'el'       => 'panel',
				'title'    => __( 'Form Appearance', 'newsletter-optin-box' ),
				'id'       => 'formDesign',
				'children' => $this->get_form_settings(),
			),

			// Fields Design.
			'fields'      => array(
				'el'       => 'panel',
				'title'    => __( 'Opt-in Fields', 'newsletter-optin-box' ),
				'id'       => 'fieldDesign',
				'children' => $this->get_field_settings(),
			),

			// Image Design.
			'image'       => array(
				'el'       => 'panel',
				'title'    => __( 'Image', 'newsletter-optin-box' ),
				'id'       => 'imageDesign',
				'children' => $this->get_image_settings(),
			),

			// Button Design.
			'button'      => array(
				'el'       => 'panel',
				'title'    => __( 'Button', 'newsletter-optin-box' ),
				'id'       => 'buttonDesign',
				'children' => $this->get_button_settings(),
			),

			// Title Design.
			'title'       => array(
				'el'       => 'panel',
				'title'    => __( 'Title', 'newsletter-optin-box' ),
				'id'       => 'titleDesign',
				'children' => $this->get_title_settings(),
			),

			// Description Design.
			'description' => array(
				'el'       => 'panel',
				'title'    => __( 'Description', 'newsletter-optin-box' ),
				'id'       => 'descriptionDesign',
				'children' => $this->get_description_settings(),
			),

			// Note Design.
			'note'        => array(
				'el'       => 'panel',
				'title'    => __( 'Note', 'newsletter-optin-box' ),
				'id'       => 'noteDesign',
				'children' => $this->get_note_settings(),
			),

			// Css Design.
			'css'         => array(
				'el'       => 'panel',
				'title'    => __( 'Custom CSS', 'newsletter-optin-box' ),
				'id'       => 'customCSS',
				'children' => $this->get_custom_css_settings(),
			),
		);
	}

	/**
	 * Returns Color themes Design Fields
	 */
	private function get_templates_settings() {

		return array(

			'Template'    => array(
				'el'      => 'select',
				'label'   => __( 'Apply a template', 'newsletter-optin-box' ),
				'tooltip' => __( 'All templates include custom css so remember to check out the Custom CSS panel after you apply a template', 'newsletter-optin-box' ),
				'options' => wp_list_pluck( noptin_get_optin_templates(), 'title' ),
			),

			'colorTheme'  => array(
				'el'      => 'select',
				'label'   => __( 'Apply a color theme', 'newsletter-optin-box' ),
				'options' => noptin_get_color_themes(),
			),

		);
	}

	/**
	 * Returns Form Design Fields
	 */
	private function get_form_settings() {
		return array(

			'borderSize'            => array(
				'type'    => 'text',
				'el'      => 'input',
				'label'   => __( 'Border Size', 'newsletter-optin-box' ),
				'tooltip' => __( "Set this to 0 if you don't want the form to have a border", 'newsletter-optin-box' ),
			),

			'formRadius'            => array(
				'type'    => 'text',
				'el'      => 'input',
				'label'   => __( 'Border Radius', 'newsletter-optin-box' ),
				'tooltip' => __( "Set this to 0 if you don't want the form to have rounded corners", 'newsletter-optin-box' ),
			),

			'formWidth'             => array(
				'type'     => 'text',
				'el'       => 'input',
				'restrict' => "optinType =='popup' || optinType =='slide_in'",
				'label'    => __( 'Preferred Width', 'newsletter-optin-box' ),
				'tooltip'  => __( 'The element will resize to 100% width on smaller devices', 'newsletter-optin-box' ),
			),

			'formHeight'            => array(
				'type'  => 'text',
				'el'    => 'input',
				'label' => __( 'Minimum Height', 'newsletter-optin-box' ),
			),

			'noptinFormBorderColor' => array(
				'type'  => 'color',
				'el'    => 'input',
				'label' => __( 'Border Color', 'newsletter-optin-box' ),
			),

			'noptinFormBg'          => array(
				'type'  => 'color',
				'el'    => 'input',
				'label' => __( 'Background Color', 'newsletter-optin-box' ),
			),

			'noptinFormBgImg'       => array(
				'type'  => 'image',
				'el'    => 'input',
				'size'  => 'full',
				'label' => __( 'Background Image', 'newsletter-optin-box' ),
			),

			'noptinFormBgVideo'     => array(
				'type'        => 'text',
				'el'          => 'input',
				'label'       => __( 'Background Video', 'newsletter-optin-box' ),
				'description' => __( 'Enter the full URL to an MP4 video file', 'newsletter-optin-box' ),
				'tooltip'     => __( 'Works best if the video dimensions are of the same ratio as the form', 'newsletter-optin-box' ),
			),

		);
	}

	/**
	 * Returns field Design Fields
	 */
	private function get_field_settings() {
		return array(

			'fields'       => array(
				'el'       => 'form_fields',
				'restrict' => "!hideFields",
			),

			'singleLine'   => array(
				'type'     => 'checkbox',
				'el'       => 'input',
				'label'    => __( 'Show all fields in a single line', 'newsletter-optin-box' ),
				'restrict' => "!hideFields",
			),

			'gdprCheckbox' => array(
				'type'     => 'checkbox',
				'el'       => 'input',
				'label'    => __( 'Show GDPR checkbox', 'newsletter-optin-box' ),
				'restrict' => "!hideFields",
			),

			'gdprConsentText' => array(
				'type'        => 'text',
				'el'          => 'input',
				'label'       => __( 'Consent Text', 'newsletter-optin-box' ),
				'restrict'    => "gdprCheckbox && !hideFields",
			),

			'hideFields'   => array(
				'type'     => 'checkbox',
				'el'       => 'input',
				'label'    => __( 'Hide opt-in fields', 'newsletter-optin-box' ),
			),

		);
	}


	/**
	 * Returns image Design Fields
	 */
	private function get_image_settings() {
		return array(

			'image'    => array(
				'type'  => 'image',
				'el'    => 'input',
				'label' => __( 'Image URL', 'newsletter-optin-box' ),
			),

			'imagePos' => array(
				'el'       => 'radio_button',
				'options'  => array(
					'top'    => __( 'Top', 'newsletter-optin-box' ),
					'left'   => __( 'Left', 'newsletter-optin-box' ),
					'right'  => __( 'Right', 'newsletter-optin-box' ),
					'bottom' => __( 'Bottom', 'newsletter-optin-box' ),
				),
				'label'    => __( 'Image Position', 'newsletter-optin-box' ),
				'restrict' => 'image',
			),

		);
	}

	/**
	 * Returns Button Design Fields
	 */
	private function get_button_settings() {
		return array(
			'noptinButtonLabel' => array(
				'type'  => 'text',
				'el'    => 'input',
				'label' => __( 'Button Label', 'newsletter-optin-box' ),
			),

			'buttonPosition'    => array(
				'el'       => 'radio_button',
				'options'  => array(
					'block' => __( 'Block', 'newsletter-optin-box' ),
					'left'  => __( 'Left', 'newsletter-optin-box' ),
					'right' => __( 'Right', 'newsletter-optin-box' ),
				),
				'label'    => __( 'Button Position', 'newsletter-optin-box' ),
				'restrict' => '!singleLine',
			),

			'noptinButtonBg'    => array(
				'type'  => 'color',
				'el'    => 'input',
				'label' => __( 'Button Background', 'newsletter-optin-box' ),
			),

			'noptinButtonColor' => array(
				'type'  => 'color',
				'el'    => 'input',
				'label' => __( 'Button Color', 'newsletter-optin-box' ),
			),
		);
	}

	/**
	 * Returns Title Design Fields
	 */
	private function get_title_settings() {
		return array(

			'hideTitle'  => array(
				'type'  => 'checkbox',
				'el'    => 'input',
				'label' => __( 'Hide title', 'newsletter-optin-box' ),
			),

			'title'      => array(
				'el'       => 'textarea',
				'label'    => __( 'Title', 'newsletter-optin-box' ),
				'restrict' => '!hideTitle',
			),

			'titleColor' => array(
				'type'     => 'color',
				'el'       => 'input',
				'label'    => __( 'Title Color', 'newsletter-optin-box' ),
				'restrict' => '!hideTitle',
			),
		);
	}

	/**
	 * Returns Description Design Fields
	 */
	private function get_description_settings() {
		return array(

			'hideDescription'  => array(
				'type'  => 'checkbox',
				'el'    => 'input',
				'label' => __( 'Hide description', 'newsletter-optin-box' ),
			),

			'description'      => array(
				'el'       => 'textarea',
				'label'    => __( 'Description', 'newsletter-optin-box' ),
				'restrict' => '!hideDescription',
			),
			'descriptionColor' => array(
				'type'     => 'color',
				'el'       => 'input',
				'label'    => __( 'Description Color', 'newsletter-optin-box' ),
				'restrict' => '!hideDescription',
			),

		);
	}

	/**
	 * Returns Note Design Fields
	 */
	private function get_note_settings() {
		return array(

			'hideNote'  => array(
				'type'  => 'checkbox',
				'el'    => 'input',
				'label' => __( 'Hide note', 'newsletter-optin-box' ),
			),

			/*
			'hideOnNoteClick'             => array(
				'type'                    => 'checkbox',
				'el'                      => 'input',
				'label'                   => 'Close popup when user clicks on note?',
				'restrict'                => "!hideNote && optinType=='popup'",
			),*/

			'note'      => array(
				'el'       => 'textarea',
				'label'    => __( 'Note', 'newsletter-optin-box' ),
				'restrict' => '!hideNote',
			),
			'noteColor' => array(
				'type'     => 'color',
				'el'       => 'input',
				'label'    => __( 'Note Color', 'newsletter-optin-box' ),
				'restrict' => '!hideNote',
			),

		);
	}

	/**
	 * Returns Custom css Fields
	 */
	private function get_custom_css_settings() {
		return array(

			'CSS' => array(
				'el'      => 'editor',
				'tooltip' => __( "Prefix all your styles with '.noptin-optin-form-wrapper' or else they will apply to all opt-in forms on the page", 'newsletter-optin-box' ),
				'label'   => __( 'Enter Your Custom CSS.', 'newsletter-optin-box' ) . ' <a href="https://noptin.com/guide/email-forms/opt-in-forms-editor/custom-css/" target="_blank">' . __( 'Read this first.', 'newsletter-optin-box' ) . '</a>',
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
		$state = array_merge( $saved_state, $this->get_misc_state() );

		/**
		 * Filters the Noptin Form Editor's state.
		 * 
		 * @param array $state Editor state.
		 * @param Noptin_Form_Editor $form_editor The form editor instance.
		 */
		return apply_filters( 'noptin_optin_form_editor_state', $state, $this );

	}


	/**
	 * Returns misc state
	 */
	public function get_misc_state() {
		return array(
			'hasSuccess'            => false,
			'Success'               => '',
			'hasError'              => false,
			'Error'                 => '',
			'currentSidebarSection' => 'design',
			'headerTitle'           => __( 'Editing', 'newsletter-optin-box' ),
			'saveText'              => __( 'Save', 'newsletter-optin-box' ),
			'savingText'            => __( 'Saving...', 'newsletter-optin-box' ),
			'saveAsTemplateText'    => __( 'Save As Template', 'newsletter-optin-box' ),
			'savingTemplateText'    => __( 'Saving Template...', 'newsletter-optin-box' ),
			'savingError'           => __( 'There was an error saving your form.', 'newsletter-optin-box' ),
			'savingSuccess'         => __( 'Your changes have been saved successfuly', 'newsletter-optin-box' ),
			'savingTemplateError'   => __( 'There was an error saving your template.', 'newsletter-optin-box' ),
			'savingTemplateSuccess' => __( 'Your template has been saved successfuly', 'newsletter-optin-box' ),
			'previewText'           => __( 'Preview', 'newsletter-optin-box' ),
			'isPreviewShowing'      => false,
			'colorTheme'            => '',
			'Template'              => '',
			'fieldTypes'            => get_noptin_optin_field_types(),
		);
	}

	/**
	 * Converts an array of ids to select2 option
	 */
	public function post_ids_to_options( $ids ) {

		// Return post ids array.
		if ( ! is_array( $ids ) ) {
			return array();
		}

		$options = array();
		foreach ( $ids as $id ) {
			$post_type      = get_post_type( $id );
			$title          = get_the_title( $id );
			$options[ $id ] = "[{$post_type}] $title";
		}

		return $options;
	}
}
