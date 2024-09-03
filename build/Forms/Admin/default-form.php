<?php defined( 'ABSPATH' ) || exit; ?>
<?php

	return array(
		'optinType'             => 'inpost',
		'singleLine'            => false,
		'gdprCheckbox'          => false,
		'hideSeconds'           => WEEK_IN_SECONDS,
		'gdprConsentText'       => __( 'I consent to receive promotional emails about your products and services.', 'newsletter-optin-box' ),
		'fields'                => array(
			array(
				'type'    => array(
					'label' => __( 'Email Address', 'newsletter-optin-box' ),
					'name'  => 'email',
					'type'  => 'email',
				),
				'require' => 'true',
				'key'     => 'noptin_email_key',
			),
		),
		'hideFields'            => false,
		'inject'                => '0',
		'buttonPosition'        => 'block',
		'subscribeAction'       => 'message', // redirect.
		'successMessage'        => get_noptin_option( 'success_message' ),
		'redirectUrl'           => '',

		// Form Design.
		'noptinFormBg'          => '#eeeeee',
		'noptinFormBorderColor' => '#eeeeee',
		'borderSize'            => '4px',
		'formWidth'             => '620px',
		'formHeight'            => '280px',

		// image Design.
		'image'                 => '',
		'imagePos'              => 'right',
		'imageMain'             => '',
		'imageMainPos'          => 'right',

		// Button designs.
		'noptinButtonLabel'     => __( 'Subscribe Now', 'newsletter-optin-box' ),

		// Title design.
		'hideTitle'             => false,
		'title'                 => __( 'JOIN OUR NEWSLETTER', 'newsletter-optin-box' ),
		'titleColor'            => '#313131',
		'titleTypography'       => array(
			'font_size'   => '30',
			'font_weight' => '700',
			'line_height' => '1.5',
			'decoration'  => '',
			'style'       => '',
			'generated'   => 'font-size: 30px; font-weight: 700; line-height: 1.5;',
		),
		'titleAdvanced'         => array(
			'margin'    => new stdClass(),
			'padding'   => array(
				'top' => '4',
			),
			'generated' => 'padding-top: 4px;',
			'classes'   => '',
		),

		// Title design.
		'hidePrefix'            => true,
		'prefix'                => __( 'Prefix', 'newsletter-optin-box' ),
		'prefixColor'           => '#313131',
		'prefixTypography'      => array(
			'font_size'   => '20',
			'font_weight' => '500',
			'line_height' => '1.3',
			'decoration'  => '',
			'style'       => '',
			'generated'   => 'font-size: 20px; font-weight: 500; line-height: 1.3;',
		),
		'prefixAdvanced'        => array(
			'margin'    => new stdClass(),
			'padding'   => array(
				'top' => '4',
			),
			'generated' => 'padding-top: 4px;',
			'classes'   => '',
		),

		// Description design.
		'hideDescription'       => false,
		'description'           => __( 'And get notified everytime we publish a new blog post.', 'newsletter-optin-box' ),
		'descriptionColor'      => '#32373c',
		'descriptionTypography' => array(
			'font_size'   => '16',
			'font_weight' => '500',
			'line_height' => '1.3',
			'decoration'  => '',
			'style'       => '',
			'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.3;',
		),
		'descriptionAdvanced'   => array(
			'padding'   => new stdClass(),
			'margin'    => array(
				'top' => '18',
			),
			'generated' => 'margin-top: 18px;',
			'classes'   => '',
		),

		// Note design.
		'hideNote'              => false,
		'note'                  => __( 'By subscribing, you agree with our <a href="">privacy policy</a> and our terms of service.', 'newsletter-optin-box' ),
		'noteColor'             => '#607D8B',
		'hideOnNoteClick'       => false,
		'noteTypography'        => array(
			'font_size'   => '',
			'font_weight' => '',
			'line_height' => '',
			'decoration'  => '',
			'style'       => '',
			'generated'   => '',
		),
		'noteAdvanced'          => array(
			'padding'   => new stdClass(),
			'margin'    => array(
				'top' => '10',
			),
			'generated' => 'margin-top: 10px;',
			'classes'   => '',
		),

		// Trigger Options.
		'timeDelayDuration'     => 4,
		'scrollDepthPercentage' => 25,
		'cssClassOfClick'       => '#id .class',
		'triggerPopup'          => 'immeadiate',
		'slideDirection'        => 'bottom_right',

		// Restriction Options.
		'showEverywhere'        => true,
		'showPlaces'            => array(
			'showHome',
			'showBlog',
			'post',
		),
		'neverShowOn'           => '',
		'onlyShowOn'            => '',
		'whoCanSee'             => 'all',
		'userRoles'             => array(),
		'hideSmallScreens'      => false,
		'hideLargeScreens'      => false,
		'showPostTypes'         => array(),

		// custom css.
		'CSS'                   => '.noptin-optin-form-wrapper *{}',
		'tags'                  => '',

	);
