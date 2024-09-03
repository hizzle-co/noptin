<?php
/**
 * Retrieves the form settings fields.
 *
 */

defined( 'ABSPATH' ) || exit;

$editor_settings = array(
	'settings'     => array(
		'label'           => __( 'Settings', 'newsletter-optin-box' ),

		// Basic settings.
		'basic'           => array(
			'el'       => 'panel',
			'title'    => __( 'Basic Options', 'newsletter-optin-box' ),
			'id'       => 'basicSettings',
			'children' => array(

				'inject'          => array(
					'el'         => 'select',
					'conditions' => array(
						array(
							'key'   => 'optinType',
							'value' => 'inpost',
						),
					),
					'label'      => __( 'Inject into post content', 'newsletter-optin-box' ),
					'tooltip'    => __( "Noptin can automatically embed this form into your post content. You can also find the form's shortcode below the form preview", 'newsletter-optin-box' ),
					'options'    => array(
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
					'type'       => 'textarea',
					'el'         => 'textarea',
					'label'      => __( 'Success message', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'   => 'subscribeAction',
							'value' => 'message',
						),
					),
				),

				// Where should we redirect the user after subscription?
				'redirectUrl'     => array(
					'type'       => 'text',
					'el'         => 'input',
					'label'      => __( 'Redirect url', 'newsletter-optin-box' ),
					'placeholde' => 'http://example.com/success',
					'conditions' => array(
						array(
							'key'   => 'subscribeAction',
							'value' => 'redirect',
						),
					),
				),

			),
		),

		// Trigger Options.
		'trigger'         => array(
			'el'         => 'panel',
			'title'      => __( 'Trigger Options', 'newsletter-optin-box' ),
			'id'         => 'triggerSettings',
			'conditions' => array(
				array(
					'key'      => 'optinType',
					'operator' => 'includes',
					'value'    => array( 'popup', 'slide_in' ),
				),
			),
			'children'   => array(

				// Sliding direction.
				'slideDirection'        => array(
					'el'         => 'select',
					'label'      => __( 'The form will slide from...', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'      => 'optinType',
							'operator' => '==',
							'value'    => 'slide_in',
						),
					),
					'options'    => array(
						'top_left'     => __( 'Top Left', 'newsletter-optin-box' ),
						'left_top'     => __( 'Top Left Alt', 'newsletter-optin-box' ),
						'top_right'    => __( 'Top Right', 'newsletter-optin-box' ),
						'right_top'    => __( 'Top Right Alt', 'newsletter-optin-box' ),
						'center_left'  => __( 'Center Left', 'newsletter-optin-box' ),
						'center_right' => __( 'Center Right', 'newsletter-optin-box' ),
						'bottom_left'  => __( 'Bottom Left', 'newsletter-optin-box' ),
						'left_bottom'  => __( 'Bottom Left Alt', 'newsletter-optin-box' ),
						'bottom_right' => __( 'Bottom right', 'newsletter-optin-box' ),
						'right_bottom' => __( 'Bottom right Alt', 'newsletter-optin-box' ),
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

				'before_leave_warning'  => array(
					'el'         => 'paragraph',
					'content'    => __( 'This option only works for users using a mouse. It will not work for touch devices.', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'   => 'triggerPopup',
							'value' => 'before_leave',
						),
					),
				),

				// CSS class of the items to watch out for clicks.
				'cssClassOfClick'       => array(
					'type'        => 'text',
					'el'          => 'input',
					'label'       => __( 'Selector', 'newsletter-optin-box' ),
					'description' => __( 'CSS selector of the items to watch out for clicks', 'newsletter-optin-box' ),
					'conditions'  => array(
						array(
							'key'   => 'triggerPopup',
							'value' => 'after_click',
						),
					),
				),

				// Time in seconds to delay.
				'timeDelayDuration'     => array(
					'type'             => 'number',
					'el'               => 'input',
					'label'            => __( 'Delay', 'newsletter-optin-box' ),
					'description'      => __( 'Time in seconds to delay', 'newsletter-optin-box' ),
					'conditions'       => array(
						array(
							'key'   => 'triggerPopup',
							'value' => 'after_delay',
						),
					),
					'customAttributes' => array(
						'suffix' => __( 'seconds', 'newsletter-optin-box' ),
					),
				),

				// Scroll depth.
				'scrollDepthPercentage' => array(
					'type'             => 'number',
					'el'               => 'input',
					'label'            => __( 'Scroll depth', 'newsletter-optin-box' ),
					'description'      => __( 'Scroll depth in percentage after which the form will appear', 'newsletter-optin-box' ),
					'conditions'       => array(
						array(
							'key'   => 'triggerPopup',
							'value' => 'on_scroll',
						),
					),
					'customAttributes' => array(
						'suffix' => '%',
					),
				),

				// Hide for X seconds.
				'hideSeconds'           => array(
					'type'             => 'number',
					'el'               => 'input',
					'label'            => __( 'Hide for', 'newsletter-optin-box' ),
					'description'      => __( 'Hide the form for X seconds after a user closes it', 'newsletter-optin-box' ),
					'placeholder'      => WEEK_IN_SECONDS,
					'customAttributes' => array(
						'suffix' => __( 'seconds', 'newsletter-optin-box' ),
					),
				),
			),
		),

		// Targeting Options.
		'targeting'       => array(
			'el'       => 'panel',
			'title'    => __( 'Page Targeting', 'newsletter-optin-box' ),
			'id'       => 'targetingSettings',
			'children' => array(

				'targeting-info-text' => array(
					'el'      => 'paragraph',
					'content' => __( 'Where do you want to show this subscription form?', 'newsletter-optin-box' ),
					'style'   => 'font-weight: bold;',
				),

				'showEverywhere'      => array(
					'type'       => 'checkbox',
					'el'         => 'input',
					'label'      => __( 'Everywhere', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'      => 'onlyShowOn',
							'operator' => 'empty',
						),
					),
				),

				'showPlaces'          => array(
					'el'         => 'select',
					'multiple'   => true,
					'label'      => __( 'Only show on selected pages', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'      => 'showEverywhere',
							'operator' => '!=',
							'value'    => true,
						),
						array(
							'key'      => 'onlyShowOn',
							'operator' => 'empty',
						),
					),
					'options'    => array_merge(
						array(
							'showHome'     => __( 'Front page', 'newsletter-optin-box' ),
							'showBlog'     => __( 'Blog page', 'newsletter-optin-box' ),
							'showSearch'   => __( 'Search page', 'newsletter-optin-box' ),
							'showArchives' => __( 'Archive pages', 'newsletter-optin-box' ),
						),
						noptin_get_post_types()
					),
				),

				'neverShowOn'         => array(
					'el'          => 'input',
					'label'       => __( 'Never show on:', 'newsletter-optin-box' ),
					'options'     => array(),
					'conditions'  => array(
						array(
							'key'      => 'onlyShowOn',
							'operator' => 'empty',
						),
					),
					'placeholder' => '1,10,25,' . noptin_clean_url( home_url( 'contact' ) ),
					'description' => __( 'Use a comma to separate post ids or urls where this form should not be displayed. All post type ids (page, products, etc) are supported, not just blog post ids.', 'newsletter-optin-box' ),
				),

				'onlyShowOn'          => array(
					'el'          => 'input',
					'label'       => 'Only show on:',
					'placeholder' => '3,14,5,' . noptin_clean_url( home_url( 'about' ) ),
					'description' => __( 'If you specify any posts here, all other targeting rules will be ignored, and this form will only be displayed on posts or urls that you specify here.', 'newsletter-optin-box' ),
					'options'     => array(),
				),
			),
		),

		// User targeting.
		'userTargeting'   => array(
			'el'       => 'panel',
			'title'    => 'User Targeting',
			'id'       => 'userTargetingSettings',
			'children' => array(

				'whoCanSee' => array(
					'el'      => 'select',
					'options' => array(
						'all'    => __( 'Everyone', 'newsletter-optin-box' ),
						'users'  => __( 'Logged in users', 'newsletter-optin-box' ),
						'guests' => __( 'Logged out users', 'newsletter-optin-box' ),
						'roles'  => __( 'specific user roles', 'newsletter-optin-box' ),
					),
					'label'   => __( 'Who can see this form?', 'newsletter-optin-box' ),
				),

				'userRoles' => array(
					'el'         => 'select',
					'multiple'   => true,
					'label'      => __( 'Select user roles', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'   => 'whoCanSee',
							'value' => 'roles',
						),
					),
					'options'    => array_map( 'translate_user_role', wp_list_pluck( array_reverse( get_editable_roles() ), 'name' ) ),
				),

			),
		),

		// Device targeting.
		'deviceTargeting' => array(
			'el'       => 'panel',
			'title'    => __( 'Device Targeting', 'newsletter-optin-box' ),
			'id'       => 'deviceTargetingSettings',
			'children' => array(

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

			),
		),

		// Subscriber tags.
		'tags'            => array(
			'el'       => 'panel',
			'title'    => __( 'Tags', 'newsletter-optin-box' ),
			'id'       => 'apTags',
			'children' => array(

				'tags' => array(
					'type'        => 'text',
					'el'          => 'input',
					'label'       => __( 'Subscriber Tags', 'newsletter-optin-box' ),
					'placeholder' => 'Example tag 1, tag 2, tag 3',
					'description' => __( 'Enter a comma separated list of tags to assign subscribers who sign up using this form', 'newsletter-optin-box' ),
				),

			),
		),
	),
	'design'       => array(
		'label'       => __( 'Design', 'newsletter-optin-box' ),

		// Form Design.
		'form'        => array(
			'el'       => 'panel',
			'title'    => __( 'Form Appearance', 'newsletter-optin-box' ),
			'id'       => 'formDesign',
			'children' => array(

				'formWidth'  => array(
					'el'      => 'unit',
					'label'   => __( 'Preferred Width', 'newsletter-optin-box' ),
					'tooltip' => __( 'The element will resize to 100% width on smaller devices', 'newsletter-optin-box' ),
				),

				'formHeight' => array(
					'el'    => 'unit',
					'label' => __( 'Minimum Height', 'newsletter-optin-box' ),
				),

			),
		),

		// Fields Design.
		'fields'      => array(
			'el'       => 'panel',
			'title'    => __( 'Opt-in Fields', 'newsletter-optin-box' ),
			'id'       => 'fieldDesign',
			'children' => array(

				'fields'          => array(
					'el'               => 'repeater',
					'conditions'       => array(
						array(
							'key'   => 'hideFields',
							'value' => false,
						),
					),
					'customAttributes' => array(
						'repeaterKey'         => array(
							'from'      => 'type.label',
							'fallback'  => 'type.type',
							'to'        => 'key',
							'newOnly'   => true,
							'maxLength' => 20,
							'display'   => false,
						),
						'hideLabelFromVision' => true,
						'fields'              => array(
							'type.type'  => array(
								'el'         => 'select',
								'label'      => __( 'Field Type', 'newsletter-optin-box' ),
								'options'    => wp_list_pluck(
									get_noptin_custom_fields( true ),
									'label',
									'merge_tag'
								),
								'label_sets' => 'type.label',
							),
							'type.label' => array(
								'el'          => 'input',
								'label'       => __( 'Frontend Label', 'newsletter-optin-box' ),
								'description' => __( 'Leave empty to use the default label.', 'newsletter-optin-box' ),
							),
						),
					),
				),

				'singleLine'      => array(
					'type'       => 'switch',
					'el'         => 'input',
					'label'      => __( 'Show all fields in a single line', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'   => 'hideFields',
							'value' => false,
						),
					),
				),

				'showLabels'      => array(
					'type'  => 'switch',
					'el'    => 'input',
					'label' => __( 'Show field labels outside the fields', 'newsletter-optin-box' ),
				),

				'gdprCheckbox'    => array(
					'type'       => 'switch',
					'el'         => 'input',
					'label'      => __( 'Show GDPR checkbox', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'   => 'hideFields',
							'value' => false,
						),
					),
				),

				'gdprConsentText' => array(
					'type'       => 'text',
					'el'         => 'input',
					'label'      => __( 'Consent Text', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'   => 'hideFields',
							'value' => false,
						),
						array(
							'key'   => 'gdprCheckbox',
							'value' => true,
						),
					),
				),

				'hideFields'      => array(
					'type'  => 'switch',
					'el'    => 'input',
					'label' => __( 'Hide opt-in fields', 'newsletter-optin-box' ),
				),

			),
		),

		// Image Design.
		'image'       => array(
			'el'       => 'panel',
			'title'    => __( 'Image', 'newsletter-optin-box' ),
			'id'       => 'imageDesign',
			'children' => array(

				'noptinFormBgImg' => array(
					'type'        => 'image',
					'el'          => 'input',
					'size'        => 'full',
					'label'       => __( 'Background Image', 'newsletter-optin-box' ),
					'placeholder' => 'https://example.com/bg-image.jpg',
				),

				'image'           => array(
					'type'        => 'image',
					'el'          => 'input',
					'label'       => __( 'Avatar URL', 'newsletter-optin-box' ),
					'placeholder' => 'https://example.com/avatar.jpg',
				),

				'imagePos'        => array(
					'el'         => 'toggle_group',
					'options'    => array(
						'top'    => __( 'Top', 'newsletter-optin-box' ),
						'left'   => __( 'Left', 'newsletter-optin-box' ),
						'right'  => __( 'Right', 'newsletter-optin-box' ),
						'bottom' => __( 'Bottom', 'newsletter-optin-box' ),
					),
					'label'      => __( 'Avatar Position', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'      => 'image',
							'operator' => '!empty',
						),
					),
				),

				'imageMain'       => array(
					'type'        => 'image',
					'el'          => 'input',
					'label'       => __( 'Image URL', 'newsletter-optin-box' ),
					'size'        => 'full',
					'placeholder' => 'https://example.com/image.jpg',
				),

				'imageMainPos'    => array(
					'el'         => 'toggle_group',
					'options'    => array(
						'top'    => __( 'Top', 'newsletter-optin-box' ),
						'left'   => __( 'Left', 'newsletter-optin-box' ),
						'right'  => __( 'Right', 'newsletter-optin-box' ),
						'bottom' => __( 'Bottom', 'newsletter-optin-box' ),
					),
					'label'      => __( 'Image Position', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'      => 'imageMain',
							'operator' => '!empty',
						),
					),
				),

			),
		),

		// Button Design.
		'button'      => array(
			'el'       => 'panel',
			'title'    => __( 'Button', 'newsletter-optin-box' ),
			'id'       => 'buttonDesign',
			'children' => array(
				'noptinButtonLabel' => array(
					'type'  => 'text',
					'el'    => 'input',
					'label' => __( 'Text', 'newsletter-optin-box' ),
				),

				'buttonPosition'    => array(
					'el'         => 'toggle_group',
					'options'    => array(
						'block' => __( 'Block', 'newsletter-optin-box' ),
						'left'  => __( 'Left', 'newsletter-optin-box' ),
						'right' => __( 'Right', 'newsletter-optin-box' ),
					),
					'label'      => __( 'Position', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'   => 'singleLine',
							'value' => false,
						),
					),
				),
			),
		),

		// Prefix Design.
		'prefix'      => array(
			'el'       => 'panel',
			'title'    => __( 'Prefix', 'newsletter-optin-box' ),
			'id'       => 'prefixDesign',
			'children' => array(

				'hidePrefix'       => array(
					'type'  => 'checkbox',
					'el'    => 'input',
					'label' => __( 'Hide prefix', 'newsletter-optin-box' ),
				),

				'prefix'           => array(
					'el'         => 'textarea',
					'label'      => __( 'Prefix', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'   => 'hidePrefix',
							'value' => false,
						),
					),
				),

				'prefixTypography' => array(
					'el'         => 'typography',
					'label'      => __( 'Typography', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'   => 'hidePrefix',
							'value' => false,
						),
					),
				),

				'prefixAdvanced'   => array(
					'el'         => 'advanced',
					'label'      => __( 'Advanced', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'   => 'hidePrefix',
							'value' => false,
						),
					),
				),

			),
		),

		// Title Design.
		'title'       => array(
			'el'       => 'panel',
			'title'    => __( 'Heading', 'newsletter-optin-box' ),
			'id'       => 'titleDesign',
			'children' => array(

				'hideTitle'       => array(
					'type'  => 'checkbox',
					'el'    => 'input',
					'label' => __( 'Hide heading', 'newsletter-optin-box' ),
				),

				'title'           => array(
					'el'         => 'textarea',
					'label'      => __( 'Heading', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'   => 'hideTitle',
							'value' => false,
						),
					),
				),

				'titleTypography' => array(
					'el'         => 'typography',
					'label'      => __( 'Typography', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'   => 'hideTitle',
							'value' => false,
						),
					),
				),

				'titleAdvanced'   => array(
					'el'         => 'advanced',
					'label'      => __( 'Advanced', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'   => 'hideTitle',
							'value' => false,
						),
					),
				),

			),
		),

		// Description Design.
		'description' => array(
			'el'       => 'panel',
			'title'    => __( 'Sub-heading', 'newsletter-optin-box' ),
			'id'       => 'descriptionDesign',
			'children' => array(

				'hideDescription'       => array(
					'type'  => 'checkbox',
					'el'    => 'input',
					'label' => __( 'Hide sub-heading', 'newsletter-optin-box' ),
				),

				'description'           => array(
					'el'         => 'textarea',
					'label'      => __( 'Sub-heading', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'   => 'hideDescription',
							'value' => false,
						),
					),
				),

				'descriptionTypography' => array(
					'el'         => 'typography',
					'label'      => __( 'Typography', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'   => 'hideDescription',
							'value' => false,
						),
					),
				),

				'descriptionAdvanced'   => array(
					'el'         => 'advanced',
					'label'      => __( 'Advanced', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'   => 'hideDescription',
							'value' => false,
						),
					),
				),

			),
		),

		// Note Design.
		'note'        => array(
			'el'       => 'panel',
			'title'    => __( 'Note', 'newsletter-optin-box' ),
			'id'       => 'noteDesign',
			'children' => array(

				'hideNote'       => array(
					'type'  => 'checkbox',
					'el'    => 'input',
					'label' => __( 'Hide note', 'newsletter-optin-box' ),
				),

				'note'           => array(
					'el'         => 'textarea',
					'label'      => __( 'Note', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'   => 'hideNote',
							'value' => false,
						),
					),
				),

				'noteTypography' => array(
					'el'         => 'typography',
					'label'      => __( 'Typography', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'   => 'hideNote',
							'value' => false,
						),
					),
				),

				'noteAdvanced'   => array(
					'el'         => 'advanced',
					'label'      => __( 'Advanced', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'   => 'hideNote',
							'value' => false,
						),
					),
				),

			),
		),

		// Css Design.
		'css'         => array(
			'el'       => 'panel',
			'title'    => __( 'Custom CSS', 'newsletter-optin-box' ),
			'id'       => 'customCSS',
			'children' => array(

				'CSS' => array(
					'el'          => 'textarea',
					'description' => __( "Prefix all your styles with '.noptin-optin-form-wrapper' or else they will apply to all opt-in forms on the page", 'newsletter-optin-box' ),
					'label'       => __( 'Enter Your Custom CSS.', 'newsletter-optin-box' ),
				),

			),
		),

		// Colors.
		'colors'      => array(
			'el'    => 'color_panel',
			'label' => __( 'Colors', 'newsletter-optin-box' ),
			'id'    => 'colorSettings',
			'items' => array(
				array(
					'key'   => 'noptinFormBg',
					'label' => __( 'Background', 'newsletter-optin-box' ),
				),
				array(
					'key'   => 'descriptionColor',
					'label' => __( 'Text', 'newsletter-optin-box' ),
				),
				array(
					'key'        => 'titleColor',
					'label'      => __( 'Heading', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'   => 'hideTitle',
							'value' => false,
						),
					),
				),
				array(
					'key'        => 'prefixColor',
					'label'      => __( 'Prefix', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'   => 'hidePrefix',
							'value' => false,
						),
					),
				),
				array(
					'key'        => 'noteColor',
					'label'      => __( 'Note', 'newsletter-optin-box' ),
					'conditions' => array(
						array(
							'key'   => 'hideNote',
							'value' => false,
						),
					),
				),
				array(
					'key'   => 'noptinButtonBg',
					'label' => __( 'Button', 'newsletter-optin-box' ),
					'tabs'  => array(
						array(
							'key'   => 'noptinButtonBg',
							'label' => __( 'Background', 'newsletter-optin-box' ),
						),
						array(
							'key'   => 'noptinButtonColor',
							'label' => __( 'Text', 'newsletter-optin-box' ),
						),
					),
				),
			),
		),

		// Form border.
		'formBorder'  => array(
			'el'    => 'border',
			'label' => __( 'Border', 'newsletter-optin-box' ),
		),
	),
	'integrations' => array(
		'label' => __( 'Integrations', 'newsletter-optin-box' ),
	),
);

// Loop through all custom fields.
foreach ( get_noptin_multicheck_custom_fields() as $field ) {

	// Skip if no options.
	if ( empty( $field['options'] ) ) {
		continue;
	}

	// Basic settings.
	$editor_settings['settings'][ $field['merge_tag'] ] = array(
		'el'       => 'panel',
		'title'    => $field['label'],
		'id'       => $field['merge_tag'] . 'Settings',
		'children' => array(

			'cf-info-text'      => array(
				'el'      => 'paragraph',
				'content' => sprintf(
					/* translators: %s is the context, e.g hobbies, lists */
					__( 'Select the %s to add new subscribers who sign up via this form.', 'newsletter-optin-box' ),
					$field['label']
				),
				'style'   => 'font-weight: bold;',
			),

			$field['merge_tag'] => array(
				'el'       => 'select',
				'multiple' => true,
				'options'  => noptin_newslines_to_array( $field['options'] ),
				'label'    => '',
			),

		),
	);
}

// Add filters for all known taxonomies.
foreach ( array_keys( noptin_get_post_types() ) as $noptin_post_type ) {
	/** @var WP_Taxonomy[] $post_type_taxonomies */
	$post_type_taxonomies = wp_list_filter(
		get_object_taxonomies( $noptin_post_type, 'objects' ),
		array(
			'public' => true,
		)
	);

	foreach ( $post_type_taxonomies as $taxonomy ) {
		$editor_settings['settings']['targeting']['children'][ 'showTaxonomy_' . $taxonomy->name ] = array(
			'el'          => 'input',
			'label'       => $taxonomy->label,
			'conditions'  => array(
				array(
					'key'   => 'showEverywhere',
					'value' => false,
				),
				array(
					'key'      => 'onlyShowOn',
					'operator' => 'empty',
				),
				array(
					'key'      => 'showPlaces',
					'operator' => '^includes',
					'value'    => $noptin_post_type,
				),
			),
			'description' => sprintf(
				/* translators: %s is the taxonomy label */
				__( 'Enter a comma-separated list of %s ids, slugs, or names to show this form on', 'newsletter-optin-box' ),
				strtolower( isset( $taxonomy->labels->singular_name ) ? $taxonomy->labels->singular_name : $taxonomy->label )
			),
			'tooltip'     => __( 'Prefix the id with a minus sign to exclude it from the list', 'newsletter-optin-box' ),
		);
	}
}

// Upsell interations.
if ( noptin_upsell_integrations() ) {
	foreach ( \Noptin_COM::get_connections() as $connection ) {
		$key  = sanitize_key( str_replace( '-', '_', $connection->slug ) );
		$name = esc_html( $connection->name );
		$href = esc_url( noptin_get_upsell_url( $connection->connect_url, $key, 'subscription-forms' ) );

		$editor_settings['integrations'][ $key ] = array(
			'el'       => 'panel',
			'title'    => $name,
			'id'       => $key,
			'children' => array(
				"{$key}text" => array(
					'el'      => 'paragraph',
					'content' => sprintf(
						// translators: %1$s is the name of the integration, %2$s is the link to the integration's website.
						esc_html__( 'Install the %1$s to add new subscribers to %2$s.', 'newsletter-optin-box' ),
						'<a target="_blank" href="' . $href . '"> ' . $name . ' addon</a>',
						$name
					),
					'style'   => 'color:#F44336;',
					'raw'     => true,
				),
			),
		);
	}
}

return $editor_settings;
