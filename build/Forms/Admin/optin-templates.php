<?php

	// Exit if accessed directly.
	defined( 'ABSPATH' ) || exit;

	// Fetch the privacy text.
	$noptin_privacy_text = get_default_noptin_form_privacy_text();

	// Return the array of available templates.
	return array(

		'PRO20200311'            => array(
			'title' => 'Professional',
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '0px',
				'noptinFormBg'          => '#f8f9fa',

				'imageMain'             => false,
				'formBorder'            => array(
					'style'         => 'none',
					'border_radius' => 0,
					'border_width'  => 0,
					'border_color'  => '#f8f9fa',
					'generated'     => 'border-style: none; border-radius: 0px; border-width: 0px; border-color: #f8f9fa;',
				),
				'image'                 => noptin()->plugin_url . 'includes/assets/images/avatar.png',
				'imagePos'              => 'left',
				'noptinButtonLabel'     => 'Sign Up',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#00d07e',
				'noptinButtonColor'     => '#ffffff',
				'hideTitle'             => false,
				'title'                 => 'Get Exclusive SEO Tips',
				'titleColor'            => '#010101',
				'hideDescription'       => false,
				'description'           => 'Receive the same tips I used to double my traffic in just two weeks!',
				'descriptionColor'      => '#010101',
				'hideNote'              => true,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#010101',
				'titleTypography'       => array(
					'font_size'   => '20',
					'font_weight' => '700',
					'line_height' => '1.5',
					'generated'   => 'font-size: 20px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'CSS'                   => '.noptin-optin-form-wrapper .noptin-form-header-image img{ border-radius: 50%; }',
			),

		),


		'owvP1565701640'         => array(
			'title' => 'Borderless',
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '10px',
				'noptinFormBg'          => '#ffffff',

				'formBorder'            => array(
					'style'     => 'none',
					'generated' => 'border-style: none;',
				),
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'SUBSCRIBE NOW',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#F44336',
				'noptinButtonColor'     => '#fefefe',
				'hideTitle'             => false,
				'title'                 => 'Subscribe For Latest Updates',
				'titleColor'            => '#191919',
				'hideDescription'       => false,
				'description'           => "We'll send you the best business news and informed analysis on what matters the most to you.",
				'descriptionColor'      => '#666666',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#607D8B',
				'titleTypography'       => array(
					'font_size'   => '32',
					'font_weight' => '700',
					'line_height' => '1.5',
					'style'       => 'italic',
					'generated'   => 'font-size: 32px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'CSS'                   => '.noptin-optin-form-wrapper .noptin-form-field{ text-align: center; }',
			),

		),

		'IEiH1565701672'         => array(
			'title' => 'Classic',
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '10px',
				'formWidth'             => '520px',
				'formHeight'            => '280px',
				'noptinFormBg'          => '#fafafa',

				'imageMain'             => false,
				'formBorder'            => array(
					'border_color' => '#009688',
					'generated'    => 'border-color: #009688;',
				),
				'image'                 => noptin()->plugin_url . 'includes/assets/images/email-icon.png',
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Subscribe Now',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#009688',
				'noptinButtonColor'     => '#fefefe',
				'hideTitle'             => false,
				'title'                 => 'Subscribe To Our Newsletter',
				'titleColor'            => '#191919',
				'hideDescription'       => false,
				'description'           => 'Enter your email to receive a weekly round-up of our best posts.',
				'descriptionColor'      => '#666666',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#607D8B',
				'titleTypography'       => array(
					'font_size'   => '20',
					'font_weight' => '700',
					'line_height' => '1.5',
					'generated'   => 'font-size: 20px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'CSS'                   => '.noptin-optin-form-wrapper *{}',
			),

		),

		'qQOv1565701677'         => array(
			'title' => 'Subheading',
			'data'  => array(
				'singleLine'            => false,
				'formRadius'            => '0px',
				'noptinFormBg'          => '#fafafa',
				'fields'                => array(
					array(
						'type'    => array(
							'label' => 'Enter your name here',
							'name'  => 'name',
							'type'  => 'name',
						),

						'require' => false,
						'key'     => 'key-fkluoh',
					),

					array(
						'type'    => array(
							'label' => 'Enter your email address',
							'name'  => 'email',
							'type'  => 'email',
						),

						'require' => true,
						'key'     => 'noptin_email_key',
					),

				),

				'formBorder'            => array(
					'border_color' => '#9E9E9E',
					'generated'    => 'border-color: #9E9E9E;',
				),
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Subscribe Now',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#009688',
				'noptinButtonColor'     => '#fefefe',
				'hideTitle'             => false,
				'title'                 => "DON'T MISS OUT!",
				'titleColor'            => '#009688',
				'description'           => 'Subscribe To Our Newsletter',
				'descriptionColor'      => '#666666',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#607D8B',
				'titleTypography'       => array(
					'font_size'   => '20',
					'font_weight' => '700',
					'line_height' => '1.5',
					'generated'   => 'font-size: 20px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '30',
					'font_weight' => '700',
					'line_height' => '1.5',
					'generated'   => 'font-size: 30px; font-weight: 700; line-height: 1.5;',
				),

			),

		),

		'ICkq1565701695'         => array(
			'title' => 'Border Top',
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '0px',
				'noptinFormBg'          => '#ffffff',
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'JOIN',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#795548',
				'noptinButtonColor'     => '#fefefe',
				'hideTitle'             => false,
				'title'                 => 'Join Our Newsletter',
				'titleColor'            => '#191919',
				'hideDescription'       => false,
				'description'           => 'get weekly access to our best tips, tricks and updates.',
				'descriptionColor'      => '#666666',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#607D8B',
				'titleTypography'       => array(
					'font_size'   => '20',
					'font_weight' => '700',
					'line_height' => '1.5',
					'generated'   => 'font-size: 20px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'formBorder'            => array(
					'formRadius' => 0,
					'border'     => array(
						'top'    => array(
							'style' => 'solid',
							'width' => 4,
							'color' => '#795548',
						),
						'left'   => array(
							'style' => 'none',
							'width' => 0,
							'color' => '#795548',
						),
						'right'  => array(
							'style' => 'none',
							'width' => 0,
							'color' => '#795548',
						),
						'bottom' => array(
							'style' => 'none',
							'width' => 0,
							'color' => '#795548',
						),
					),
					'generated'  => 'border-top-style: solid; border-top-width: 4px; border-top-color: #795548; border-left-style: none; border-right-style: none; border-bottom-style: none;',
				),
				'CSS'                   => '.noptin-optin-form-wrapper { box-shadow: 0 2px 5px 0 rgba(0,0,0,.16), 0 2px 10px 0 rgba(0,0,0,.12); }',
			),

		),

		'BLyQ1565701700'         => array(
			'title' => 'Feature Photo',
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '0px',
				'formWidth'             => '720px',
				'noptinFormBg'          => '#4CAF50',
				'noptinFormBgImg'       => noptin()->plugin_url . 'includes/assets/images/bg1.jpg',
				'fields'                => array(
					array(
						'type'    => array(
							'label' => 'First Name',
							'name'  => 'name',
							'type'  => 'name',
						),

						'require' => false,
						'key'     => 'key-ltoxdb',
					),

					array(
						'type'    => array(
							'label' => 'Email Address',
							'name'  => 'email',
							'type'  => 'email',
						),

						'require' => true,
						'key'     => 'noptin_email_key',
					),

				),

				'formBorder'            => array(
					'style'         => 'none',
					'border_radius' => 0,
					'border_width'  => 0,
					'border_color'  => '#000000',
					'generated'     => 'border-style: none; border-radius: 0px; border-width: 0px; border-color: #000000;',
				),
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Subscribe Now',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => 'rgba(0,0,0,0)',
				'noptinButtonColor'     => '#fefefe',
				'hideTitle'             => false,
				'title'                 => 'WANT THE INSIDE SCOOP?',
				'titleColor'            => '#f1f1f1',
				'hideDescription'       => false,
				'description'           => 'Weekly updates, curated reads and practical tips delivered to your inbox.',
				'descriptionColor'      => '#f2f2f2',
				'hideNote'              => true,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#607D8B',
				'titleTypography'       => array(
					'font_size'   => '20',
					'font_weight' => '700',
					'line_height' => '1.5',
					'generated'   => 'font-size: 20px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'CSS'                   => '.noptin-optin-form-wrapper{}

.noptin-optin-form-wrapper form .noptin-form-footer .noptin-form-submit {
	border:2px solid hsla(0,0%,100%,.3) !important;
    border-radius: 2px !important;
}

.noptin-optin-form-wrapper form .noptin-form-footer .noptin-form-field {
	border-radius: 2px !important;
	background-color: rgba(245, 245, 245, 0.8) !important;
    color: #000 !important;
	text-align: center !important;
}',
			),

		),

		'yXJo1565701704'         => array(
			'title' => 'Rounded Fields',
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '20px',
				'formWidth'             => '520px',
				'noptinFormBg'          => '#424242',

				'formBorder'            => array(
					'border_color' => '#424242',
					'generated'    => 'border-color: #424242;',
				),
				'image'                 => noptin()->plugin_url . 'includes/assets/images/email-icon.png',
				'imagePos'              => 'left',
				'noptinButtonLabel'     => 'Subscribe Now',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#607D8B',
				'noptinButtonColor'     => '#fafafa',
				'hideTitle'             => false,
				'title'                 => 'Subscribe To Our Newsletter',
				'titleColor'            => '#fafafa',
				'hideDescription'       => false,
				'description'           => 'Enter your email to receive a weekly round-up of our best posts. Learn more!',
				'descriptionColor'      => '#fafafa',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#fafafa',
				'titleTypography'       => array(
					'font_size'   => '20',
					'font_weight' => '700',
					'line_height' => '1.5',
					'generated'   => 'font-size: 20px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'CSS'                   => '/*Custom css*/

.noptin-optin-form-wrapper .noptin-optin-form.noptin-form-single-line .noptin-form-submit{
	border-radius: 30px;
}

@media only screen and (min-width: 768px) {

	.noptin-optin-form-wrapper .noptin-optin-form.noptin-form-single-line .noptin-form-submit{
		position: absolute;
		bottom: -1px;
		top: 0;
		right: 0px;
	}

}

.noptin-optin-form-wrapper .noptin-form-single-line .noptin-form-field{
 	border-radius: 30px !important;
	border: none !important;
}',
			),

		),

		'BMDQ1565701721'         => array(
			'title' => 'Emerald',
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '0px',
				'noptinFormBg'          => '#4CAF50',

				'imageMain'             => false,
				'formBorder'            => array(
					'border_color' => '#4CAF50',
					'generated'    => 'border-color: #4CAF50;',
				),
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Sign Up',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#ee4a03',
				'noptinButtonColor'     => '#ffffff',
				'hideTitle'             => false,
				'title'                 => 'Join our newsletter',
				'titleColor'            => '#fafafa',
				'hideDescription'       => false,
				'description'           => 'Get the best tips and updates delivered straight to your inbox.',
				'descriptionColor'      => '#fafafa',
				'hideNote'              => true,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#fafafa',
				'titleTypography'       => array(
					'font_size'   => '20',
					'font_weight' => '700',
					'line_height' => '1.5',
					'generated'   => 'font-size: 20px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'CSS'                   => '@media only screen and (min-width: 768px) {
	.noptin-optin-form-wrapper form.noptin-form-single-line .noptin-form-fields .noptin-form-field-submit{
		position: absolute;
		right: 12px;
		top: 6px;
		margin: 0;
	}
}

.noptin-optin-form-wrapper form.noptin-form-single-line .noptin-form-fields .noptin-form-field {
    border-radius: 4px;
 	padding: 1.2em 2em;
 border: 1px solid transparent;
 font-size: 16px;
}

.noptin-optin-form-wrapper form.noptin-form-single-line .noptin-form-header {
	flex: 0 0 auto;
 	justify-content: center;
}

.noptin-optin-form-wrapper form.noptin-form-single-line {
	justify-content: center
}
',
			),

		),

		'89zl1570214684'         => array(
			'title' => 'Blueprint',
			'data'  => array(
				'singleLine'            => false,
				'formRadius'            => '8px',
				'noptinFormBg'          => '#f1f5f8',
				'imageMain'             => false,
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Get free access',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#2779bd',
				'noptinButtonColor'     => '#fefefe',
				'hideTitle'             => false,
				'title'                 => 'Free resource inside',
				'titleColor'            => '#313131',
				'hideDescription'       => false,
				'description'           => 'Enter your email and get instant access to our free guide — practical, no fluff.',
				'descriptionColor'      => '#313131',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#313131',
				'titleTypography'       => array(
					'font_size'   => '20',
					'font_weight' => '700',
					'line_height' => '1.5',
					'generated'   => 'font-size: 20px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'formBorder'            => array(
					'style'         => 'solid',
					'border_radius' => 8,
					'border_width'  => 1,
					'border_color'  => '#6cb2eb',
					'generated'     => 'border-style: solid; border-radius: 8px; border-width: 1px; border-color: #6cb2eb;',
				),
			),

		),

		'r5g21565701726'         => array(
			'title' => 'Hearts',
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '0px',
				'noptinFormBg'          => '#009688',

				'imageMain'             => false,
				'formBorder'            => array(
					'border_color' => '#009688',
					'generated'    => 'border-color: #009688;',
				),
				'image'                 => noptin()->plugin_url . 'includes/assets/images/heart.png',
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Yes Please!',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#191919',
				'noptinButtonColor'     => '#ffffff',
				'hideTitle'             => false,
				'title'                 => 'Get some love!',
				'titleColor'            => '#ffffff',
				'hideDescription'       => false,
				'description'           => 'Drop your email below and get updates delivered straight to your inbox.',
				'descriptionColor'      => '#dff0fe',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#ffffff',
				'titleTypography'       => array(
					'font_size'   => '20',
					'font_weight' => '700',
					'line_height' => '1.5',
					'generated'   => 'font-size: 20px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'CSS'                   => '/*Custom css*/
.noptin-optin-form-wrapper form {
	padding: 10px;
	border: 6px #ffffff solid;
}',
			),

		),

		'conv21565701726'        => array(
			'title' => 'Rouge',
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '8px',
				'noptinFormBg'          => '#fb6970',

				'imageMain'             => false,
				'formBorder'            => array(
					'border_color' => '#fb6970',
					'generated'    => 'border-color: #fb6970;',
				),
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'SEND IT MY WAY!',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#ffffff',
				'noptinButtonColor'     => '#fb6970',
				'hideTitle'             => false,
				'title'                 => 'Optimize your opt-ins.',
				'titleColor'            => '#212f4f',
				'hideDescription'       => false,
				'description'           => 'Collecting emails is easier than ever with this opt-in focused Toolkit.  Get the resources you need sent straight to your inbox.',
				'descriptionColor'      => '#ffffff',
				'hideNote'              => true,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#fafafa',
				'titleTypography'       => array(
					'font_size'   => '20',
					'font_weight' => '700',
					'line_height' => '1.5',
					'generated'   => 'font-size: 20px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'CSS'                   => '/*Custom css*/

.noptin-optin-form-wrapper form .noptin-form-fields .noptin-form-field {
	background: 0 0;
}
			',
			),

		),

		'discount21565701726'    => array(
			'title' => 'Discount',
			'data'  => array(
				'singleLine'            => false,
				'formRadius'            => '1px',
				'noptinFormBg'          => '#263238',

				'imageMain'             => false,
				'formBorder'            => array(
					'border_color' => '#263238',
					'generated'    => 'border-color: #263238;',
				),
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'GET IT NOW!',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#607d8b',
				'noptinButtonColor'     => '#ffffff',
				'hidePrefix'            => false,
				'prefix'                => 'ENTER YOUR EMAIL AND GET',
				'prefixColor'           => '#607d8b',
				'hideTitle'             => false,
				'title'                 => '40% OFF',
				'titleColor'            => '#ffffff',
				'hideDescription'       => false,
				'description'           => 'on orders of $25 or more',
				'descriptionColor'      => '#607d8b',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => 'Entering your email also makes you eligible to receive future promotional emails.',
				'noteColor'             => '#607d8b',
				'titleTypography'       => array(
					'font_size'   => '85',
					'font_weight' => '700',
					'line_height' => '1.5',
					'generated'   => 'font-size: 85px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
			),

		),

		'discountalt21565701726' => array(
			'title' => 'Discount Alt',
			'data'  => array(
				'singleLine'            => false,
				'formRadius'            => '0px',
				'noptinFormBg'          => '#fefcfc',

				'imageMain'             => false,
				'formBorder'            => array(
					'border_color' => '#E0E0E0',
					'generated'    => 'border-color: #E0E0E0;',
				),
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'GET IT NOW!',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#313131',
				'noptinButtonColor'     => '#ffffff',
				'hideTitle'             => false,
				'title'                 => 'GET 10% OFF',
				'titleColor'            => '#313131',
				'hideDescription'       => false,
				'description'           => 'SUBSCRIBE TO OUR NEWSLETTER & RECEIVE A COUPON',
				'descriptionColor'      => '#313131',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => '* $50 MINIMUM PURCHASE',
				'noteColor'             => '#9E9E9E',
				'titleTypography'       => array(
					'font_size'   => '66',
					'font_weight' => '700',
					'line_height' => '1.5',
					'generated'   => 'font-size: 66px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'CSS'                   => '.noptin-optin-form-wrapper .noptin-optin-form .noptin-form-header .noptin-form-description {
	border-top: 1px solid #E0E0E0;
	border-bottom: 1px solid #E0E0E0;
}
			   ',
			),

		),

		'twobg21565701726'       => array(
			'title' => 'Two Backgrounds',
			'data'  => array(
				'singleLine'            => false,
				'formRadius'            => '0px',
				'noptinFormBg'          => '#607D8B',

				'imageMain'             => false,
				'formBorder'            => array(
					'border_color' => '#ffffff',
					'generated'    => 'border-color: #ffffff;',
				),
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Subscribe',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#313131',
				'noptinButtonColor'     => '#ffffff',
				'hideTitle'             => false,
				'title'                 => 'GET FREE UPDATES',
				'titleColor'            => '#ffffff',
				'hideDescription'       => false,
				'description'           => 'Short, focused updates every week. No spam, just good content.',
				'descriptionColor'      => '#ffffff',
				'hideNote'              => true,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#9E9E9E',
				'titleTypography'       => array(
					'font_size'   => '46',
					'font_weight' => '700',
					'line_height' => '1.5',
					'generated'   => 'font-size: 46px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'CSS'                   => '.noptin-optin-form-wrapper .noptin-optin-form .noptin-form-footer {
	background-color: aliceblue;
}',
			),

		),

		'minimal21565701726'     => array(
			'title' => 'Minimal',
			'data'  => array(
				'singleLine'        => true,
				'formHeight'        => '20px',
				'noptinFormBg'      => '#fff',
				'imagePos'          => 'right',
				'noptinButtonLabel' => 'Subscribe',
				'buttonPosition'    => 'block',
				'hideTitle'         => true,
				'title'             => 'Free Updates',
				'hideDescription'   => true,
				'description'       => 'Get free notifications whenever we publish new content.',
				'hideNote'          => false,
				'hideOnNoteClick'   => false,
				'note'              => $noptin_privacy_text,
				'CSS'               => '.noptin-optin-form-wrapper .noptin-form-footer{ padding: 0px; }',
			),

		),

		'upgrade21565701726'     => array(
			'title' => 'Content Upgrades',
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '40px',
				'noptinFormBg'          => '#ffffff',

				'imageMain'             => false,
				'image'                 => noptin()->plugin_url . 'includes/assets/images/mail-icon-alt.png',
				'imagePos'              => 'top',
				'noptinButtonLabel'     => 'Subscribe',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#4caf50',
				'noptinButtonColor'     => '#ffffff',
				'hideTitle'             => false,
				'title'                 => "The Marketer's Workbook",
				'titleColor'            => '#191919',
				'hideDescription'       => false,
				'description'           => 'Enter your email below to receive my best selling marketing ebook.',
				'descriptionColor'      => '#666666',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#607D8B',
				'titleTypography'       => array(
					'font_size'   => '20',
					'font_weight' => '700',
					'line_height' => '1.5',
					'generated'   => 'font-size: 20px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'formBorder'            => array(
					'border_color' => '#f1f1f1',
					'generated'    => 'border-color: #f1f1f1;',
				),
				'CSS'                   => '.noptin-optin-form-wrapper *{}',
			),

		),

		'spinvilla21565701726'   => array(
			'title' => 'Large Fields',
			'data'  => array(
				'singleLine'            => false,
				'formRadius'            => '0',
				'noptinFormBg'          => '#f0c97b',
				'fields'                => array(
					array(
						'type'    => array(
							'label' => 'First Name',
							'name'  => 'name',
							'type'  => 'name',
						),

						'require' => false,
						'key'     => 'noptin_name',
					),
					array(
						'type'    => array(
							'label' => 'Email Address',
							'name'  => 'email',
							'type'  => 'email',
						),

						'require' => true,
						'key'     => 'noptin_email_key',
					),

				),

				'imageMain'             => false,
				'formBorder'            => array(
					'style'         => 'none',
					'border_radius' => 0,
					'border_width'  => 0,
					'border_color'  => '#F9F7C9',
					'generated'     => 'border-style: none; border-radius: 0px; border-width: 0px; border-color: #F9F7C9;',
				),
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Subscribe Now',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#313131',
				'noptinButtonColor'     => '#ffffff',
				'hideTitle'             => false,
				'title'                 => 'Email Exclusive Offers!',
				'titleColor'            => '#576673',
				'hideDescription'       => false,
				'description'           => 'Sign up and we will instantly send you our best exclusive bonuses.',
				'descriptionColor'      => '#576673',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#576673',
				'titleTypography'       => array(
					'font_size'   => '40',
					'font_weight' => '700',
					'line_height' => '1.5',
					'generated'   => 'font-size: 40px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '19',
					'font_weight' => '500',
					'line_height' => '1.3',
					'generated'   => 'font-size: 19px; font-weight: 500; line-height: 1.3;',
				),
				'CSS'                   => '.noptin-optin-form-wrapper .noptin-form-footer .noptin-optin-field-wrapper:not(.noptin-optin-field-wrapper-hidden) .noptin-form-field {
	height: 55px !important;
	font-size: 17px;
	border: 2px #9a6807 solid;
}
.noptin-optin-form-wrapper .noptin-form-footer .noptin-form-submit{
	line-height: 2;
	font-size: 20px;
}',

			),

		),

		'midnight2026'           => array(
			'title' => 'Midnight',
			'data'  => array(
				'singleLine'             => true,
				'formRadius'             => '12px',
				'noptinFormBg'           => '#0f0f1a',
				'imageMain'              => false,
				'imagePos'               => 'right',
				'noptinButtonLabel'      => 'Join the list',
				'buttonPosition'         => 'block',
				'noptinButtonBg'         => '#6c63ff',
				'noptinButtonColor'      => '#ffffff',
				'hideTitle'              => false,
				'title'                  => 'Stay in the loop',
				'titleColor'             => '#ffffff',
				'hideDescription'        => false,
				'description'            => 'Get fresh ideas and updates delivered straight to your inbox. No spam, ever.',
				'descriptionColor'       => '#a0a0c0',
				'hideNote'               => false,
				'hideOnNoteClick'        => false,
				'note'                   => $noptin_privacy_text,
				'noteColor'              => '#6060a0',
				'titleTypography'        => array(
					'font_size'   => '26',
					'font_weight' => '700',
					'line_height' => '1.4',
					'generated'   => 'font-size: 26px; font-weight: 700; line-height: 1.4;',
				),
				'descriptionTypography'  => array(
					'font_size'   => '15',
					'font_weight' => '400',
					'line_height' => '1.6',
					'generated'   => 'font-size: 15px; font-weight: 400; line-height: 1.6;',
				),
				'formBorder'             => array(
					'style'         => 'solid',
					'border_radius' => 12,
					'border_width'  => 1,
					'border_color'  => '#2a2a40',
					'generated'     => 'border-style: solid; border-radius: 12px; border-width: 1px; border-color: #2a2a40;',
				),
				'fieldBg'                => '#1e1e30',
				'fieldBorder'            => array(
					'border'    => array(
						'color' => '#3a3a55',
						'style' => 'solid',
						'width' => '1px',
					),
					'generated' => 'border-width: 1px; border-style: solid; border-color: #3a3a55;',
				),
				'fieldTextColor'         => '#ffffff',
				'noptinButtonBgGradient' => 'linear-gradient(135deg, #6c63ff, #9b59b6)',
				'CSS'                    => '.noptin-optin-form-wrapper .noptin-form-submit {
	border: none !important;
	transition: opacity 0.2s;
}
.noptin-optin-form-wrapper .noptin-form-submit:hover {
	opacity: 0.88;
}',
			),
		),

		'sunrise2026'            => array(
			'title' => 'Sunrise',
			'data'  => array(
				'singleLine'            => false,
				'formRadius'            => '16px',
				'noptinFormBg'          => '#fff8f0',
				'fields'                => array(
					array(
						'type'    => array(
							'label' => 'First name',
							'name'  => 'name',
							'type'  => 'name',
						),
						'require' => false,
						'key'     => 'key-sunrise-name',
					),
					array(
						'type'    => array(
							'label' => 'Email address',
							'name'  => 'email',
							'type'  => 'email',
						),
						'require' => true,
						'key'     => 'noptin_email_key',
					),
				),
				'imageMain'             => false,
				'imagePos'              => 'top',
				'noptinButtonLabel'     => 'Count me in',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#ff6b35',
				'noptinButtonColor'     => '#ffffff',
				'hideTitle'             => false,
				'title'                 => 'Good things ahead',
				'titleColor'            => '#1a1a2e',
				'hideDescription'       => false,
				'description'           => "Weekly roundups, practical tips, and a few surprises. You'll want to be on this list.",
				'descriptionColor'      => '#555577',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#aaaacc',
				'titleTypography'       => array(
					'font_size'   => '28',
					'font_weight' => '700',
					'line_height' => '1.3',
					'generated'   => 'font-size: 28px; font-weight: 700; line-height: 1.3;',
				),
				'descriptionTypography' => array(
					'font_size'   => '15',
					'font_weight' => '400',
					'line_height' => '1.6',
					'generated'   => 'font-size: 15px; font-weight: 400; line-height: 1.6;',
				),
				'formBorder'            => array(
					'style'         => 'none',
					'border_radius' => 16,
					'border_width'  => 0,
					'border_color'  => '#ff6b35',
					'generated'     => 'border-style: none; border-radius: 16px; border-width: 0px; border-color: #ff6b35;',
				),
				'formBoxShadow'         => '0 8px 32px rgba(255, 107, 53, 0.15)',
				'noptinButtonRadius'    => '8px',
				'CSS'                   => '
.noptin-optin-form-wrapper .noptin-form-submit {
	letter-spacing: 0.5px;
}',
			),
		),

		'glassmorphism2026'      => array(
			'title' => 'Glass',
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '20px',
				'noptinFormBg'          => '#3a7bd5',
				'imageMain'             => false,
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Subscribe',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#ffffff',
				'noptinButtonColor'     => '#3a7bd5',
				'hideTitle'             => false,
				'title'                 => 'Join our newsletter',
				'titleColor'            => '#ffffff',
				'hideDescription'       => false,
				'description'           => 'Curated reads, tools and tips — delivered weekly.',
				'descriptionColor'      => 'rgba(255,255,255,0.8)',
				'hideNote'              => true,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => 'rgba(255,255,255,0.6)',
				'titleTypography'       => array(
					'font_size'   => '24',
					'font_weight' => '700',
					'line_height' => '1.4',
					'generated'   => 'font-size: 24px; font-weight: 700; line-height: 1.4;',
				),
				'descriptionTypography' => array(
					'font_size'   => '15',
					'font_weight' => '400',
					'line_height' => '1.6',
					'generated'   => 'font-size: 15px; font-weight: 400; line-height: 1.6;',
				),
				'formBorder'            => array(
					'style'         => 'solid',
					'border_radius' => 20,
					'border_width'  => 1,
					'border_color'  => 'rgba(255,255,255,0.3)',
					'generated'     => 'border-style: solid; border-radius: 20px; border-width: 1px; border-color: rgba(255,255,255,0.3);',
				),
				'noptinFormBgGradient'  => 'linear-gradient(135deg, #3a7bd5 0%, #00d2ff 100%)',
				'fieldBg'               => 'rgba(255,255,255,0.2)',
				'fieldBorder'           => array(
					'border'        => array(
						'color' => 'rgba(255,255,255,0.4)',
						'style' => 'solid',
						'width' => '1px',
					),
					'border_radius' => '8px',
					'generated'     => 'border-radius: 8px; border-width: 1px; border-style: solid; border-color: rgba(255,255,255,0.4);',
				),
				'fieldTextColor'        => '#ffffff',
				'noptinButtonRadius'    => '8px',
				'noptinButtonShadow'    => '0 4px 12px rgba(0,0,0,0.15)',
				'CSS'                   => '.noptin-optin-form-wrapper .noptin-form-field::placeholder {
	color: rgba(255,255,255,0.7) !important;
}
',
			),
		),

		'charcoal2026'           => array(
			'title' => 'Charcoal',
			'data'  => array(
				'singleLine'            => false,
				'formRadius'            => '0px',
				'noptinFormBg'          => '#1c1c1e',
				'imageMain'             => false,
				'imagePos'              => 'right',
				'noptinButtonLabel'     => "I'm in",
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#f5f5f5',
				'noptinButtonColor'     => '#1c1c1e',
				'hideTitle'             => false,
				'title'                 => 'The no-fluff newsletter',
				'titleColor'            => '#f5f5f5',
				'hideDescription'       => false,
				'description'           => 'Straight-to-the-point insights every Tuesday morning. Read in under 5 minutes.',
				'descriptionColor'      => '#8e8e93',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#636366',
				'titleTypography'       => array(
					'font_size'   => '26',
					'font_weight' => '700',
					'line_height' => '1.3',
					'generated'   => 'font-size: 26px; font-weight: 700; line-height: 1.3;',
				),
				'descriptionTypography' => array(
					'font_size'   => '15',
					'font_weight' => '400',
					'line_height' => '1.7',
					'generated'   => 'font-size: 15px; font-weight: 400; line-height: 1.7;',
				),
				'formBorder'            => array(
					'style'         => 'none',
					'border_radius' => 0,
					'border_width'  => 0,
					'border_color'  => '#1c1c1e',
					'generated'     => 'border-style: none;',
				),
				'fieldBg'               => '#2c2c2e',
				'fieldBorder'           => array(
					'border'        => array(
						'color' => '#3a3a3c',
						'style' => 'solid',
						'width' => '1px',
					),
					'border_radius' => '6px',
					'generated'     => 'border-radius: 6px; border-width: 1px; border-style: solid; border-color: #3a3a3c;',
				),
				'fieldTextColor'        => '#f5f5f5',
				'noptinButtonRadius'    => '6px',
				'CSS'                   => '
.noptin-optin-form-wrapper .noptin-form-submit {
	letter-spacing: 0.3px;
}',
			),
		),

		'sage2026'               => array(
			'title' => 'Sage',
			'data'  => array(
				'singleLine'            => false,
				'formRadius'            => '12px',
				'noptinFormBg'          => '#f0f4f0',
				'fields'                => array(
					array(
						'type'    => array(
							'label' => 'First name',
							'name'  => 'name',
							'type'  => 'name',
						),
						'require' => false,
						'key'     => 'key-sage-name',
					),
					array(
						'type'    => array(
							'label' => 'Email address',
							'name'  => 'email',
							'type'  => 'email',
						),
						'require' => true,
						'key'     => 'noptin_email_key',
					),
				),
				'imageMain'             => false,
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Send me the newsletter',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#3d6b4f',
				'noptinButtonColor'     => '#ffffff',
				'hideTitle'             => false,
				'title'                 => 'Grow with us',
				'titleColor'            => '#1a3a2a',
				'hideDescription'       => false,
				'description'           => 'Monthly tips on building better habits, sharper focus, and a calmer inbox.',
				'descriptionColor'      => '#4a6a5a',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#8aaa9a',
				'titleTypography'       => array(
					'font_size'   => '26',
					'font_weight' => '700',
					'line_height' => '1.3',
					'generated'   => 'font-size: 26px; font-weight: 700; line-height: 1.3;',
				),
				'descriptionTypography' => array(
					'font_size'   => '15',
					'font_weight' => '400',
					'line_height' => '1.6',
					'generated'   => 'font-size: 15px; font-weight: 400; line-height: 1.6;',
				),
				'formBorder'            => array(
					'style'         => 'solid',
					'border_radius' => 12,
					'border_width'  => 1,
					'border_color'  => '#c8ddd0',
					'generated'     => 'border-style: solid; border-radius: 12px; border-width: 1px; border-color: #c8ddd0;',
				),
				'fieldBg'               => '#ffffff',
				'fieldBorder'           => array(
					'border'        => array(
						'color' => '#c8ddd0',
						'style' => 'solid',
						'width' => '1px',
					),
					'border_radius' => '8px',
					'generated'     => 'border-radius: 8px; border-width: 1px; border-style: solid; border-color: #c8ddd0;',
				),
				'noptinButtonRadius'    => '8px',
				'CSS'                   => '
',
			),
		),

		'neon2026'               => array(
			'title' => 'Neon',
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '8px',
				'noptinFormBg'          => '#080820',
				'imageMain'             => false,
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Subscribe',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#00ffe5',
				'noptinButtonColor'     => '#080820',
				'hideTitle'             => false,
				'title'                 => 'Level up your skills',
				'titleColor'            => '#00ffe5',
				'hideDescription'       => false,
				'description'           => 'Tutorials, resources and deals for developers — every other week.',
				'descriptionColor'      => '#8888bb',
				'hideNote'              => true,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#555588',
				'titleTypography'       => array(
					'font_size'   => '26',
					'font_weight' => '700',
					'line_height' => '1.3',
					'generated'   => 'font-size: 26px; font-weight: 700; line-height: 1.3;',
				),
				'descriptionTypography' => array(
					'font_size'   => '14',
					'font_weight' => '400',
					'line_height' => '1.6',
					'generated'   => 'font-size: 14px; font-weight: 400; line-height: 1.6;',
				),
				'formBorder'            => array(
					'style'         => 'solid',
					'border_radius' => 8,
					'border_width'  => 1,
					'border_color'  => '#00ffe555',
					'generated'     => 'border-style: solid; border-radius: 8px; border-width: 1px; border-color: #00ffe555;',
				),
				'formBoxShadow'         => '0 0 40px rgba(0, 255, 229, 0.12)',
				'fieldBg'               => '#12122e',
				'fieldBorder'           => array(
					'border'        => array(
						'color' => '#00ffe540',
						'style' => 'solid',
						'width' => '1px',
					),
					'border_radius' => '6px',
					'generated'     => 'border-radius: 6px; border-width: 1px; border-style: solid; border-color: #00ffe540;',
				),
				'fieldTextColor'        => '#ffffff',
				'noptinButtonRadius'    => '6px',
				'noptinButtonShadow'    => '0 0 16px rgba(0,255,229,0.4)',
				'CSS'                   => '
.noptin-optin-form-wrapper .noptin-form-submit {
	letter-spacing: 0.5px;
}',
			),
		),

		'paper2026'              => array(
			'title' => 'Paper',
			'data'  => array(
				'singleLine'            => false,
				'formRadius'            => '4px',
				'noptinFormBg'          => '#faf9f6',
				'imageMain'             => false,
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Yes, send it my way',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#2d2d2d',
				'noptinButtonColor'     => '#faf9f6',
				'hideTitle'             => false,
				'title'                 => 'Worth reading every week',
				'titleColor'            => '#2d2d2d',
				'hideDescription'       => false,
				'description'           => "Real stories, honest takes and things we're thinking about. Subscribe and read along.",
				'descriptionColor'      => '#6b6b6b',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#aaaaaa',
				'titleTypography'       => array(
					'font_size'   => '28',
					'font_weight' => '700',
					'line_height' => '1.3',
					'generated'   => 'font-size: 28px; font-weight: 700; line-height: 1.3;',
				),
				'descriptionTypography' => array(
					'font_size'   => '15',
					'font_weight' => '400',
					'line_height' => '1.7',
					'generated'   => 'font-size: 15px; font-weight: 400; line-height: 1.7;',
				),
				'formBorder'            => array(
					'style'         => 'none',
					'border_radius' => 4,
					'border_width'  => 0,
					'border_color'  => '#e0ddd8',
					'generated'     => 'border-style: none;',
				),
				'formBoxShadow'         => '4px 4px 0 #e0ddd8',
				'fieldBg'               => '#ffffff',
				'fieldBorder'           => array(
					'border'        => array(
						'color' => '#d0cdc8',
						'style' => 'solid',
						'width' => '1px',
					),
					'border_radius' => '4px',
					'generated'     => 'border-radius: 4px; border-width: 1px; border-style: solid; border-color: #d0cdc8;',
				),
				'noptinButtonRadius'    => '4px',
				'CSS'                   => '.noptin-optin-form-wrapper {
	border-left: 4px solid #2d2d2d;
}
.noptin-optin-form-wrapper .noptin-form-submit {
	letter-spacing: 0.3px;
}',
			),
		),

		'coral2026'              => array(
			'title' => 'Coral',
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '24px',
				'noptinFormBg'          => '#ffffff',
				'imageMain'             => false,
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Subscribe',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#ff6b6b',
				'noptinButtonColor'     => '#ffffff',
				'hideTitle'             => false,
				'title'                 => "You'll actually look forward to this",
				'titleColor'            => '#1a1a2e',
				'hideDescription'       => false,
				'description'           => 'Short, useful, and never boring. Drop your email below.',
				'descriptionColor'      => '#666699',
				'hideNote'              => true,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#aaaacc',
				'titleTypography'       => array(
					'font_size'   => '24',
					'font_weight' => '700',
					'line_height' => '1.35',
					'generated'   => 'font-size: 24px; font-weight: 700; line-height: 1.35;',
				),
				'descriptionTypography' => array(
					'font_size'   => '15',
					'font_weight' => '400',
					'line_height' => '1.6',
					'generated'   => 'font-size: 15px; font-weight: 400; line-height: 1.6;',
				),
				'formBorder'            => array(
					'style'         => 'solid',
					'border_radius' => 24,
					'border_width'  => 2,
					'border_color'  => '#ffe0e0',
					'generated'     => 'border-style: solid; border-radius: 24px; border-width: 2px; border-color: #ffe0e0;',
				),
				'fieldBorder'           => array(
					'border'        => array(
						'color' => '#ffe0e0',
						'style' => 'solid',
						'width' => '1px',
					),
					'border_radius' => '20px',
					'generated'     => 'border-radius: 20px; border-width: 1px; border-style: solid; border-color: #ffe0e0;',
				),
				'noptinButtonRadius'    => '20px',
			),
		),

		'golden2026'             => array(
			'title' => 'Golden Hour',
			'data'  => array(
				'singleLine'            => false,
				'formRadius'            => '0px',
				'noptinFormBg'          => '#fffbe6',
				'imageMain'             => false,
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Sign me up',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#c8860a',
				'noptinButtonColor'     => '#ffffff',
				'hideTitle'             => false,
				'title'                 => 'The weekly brief',
				'titleColor'            => '#7a4e00',
				'hideDescription'       => false,
				'description'           => 'One email a week. The best stuff curated, zero filler.',
				'descriptionColor'      => '#a07030',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#c0a060',
				'titleTypography'       => array(
					'font_size'   => '30',
					'font_weight' => '700',
					'line_height' => '1.3',
					'generated'   => 'font-size: 30px; font-weight: 700; line-height: 1.3;',
				),
				'descriptionTypography' => array(
					'font_size'   => '15',
					'font_weight' => '400',
					'line_height' => '1.6',
					'generated'   => 'font-size: 15px; font-weight: 400; line-height: 1.6;',
				),
				'formBorder'            => array(
					'style'         => 'solid',
					'border_radius' => 0,
					'border_width'  => 2,
					'border_color'  => '#f0c840',
					'generated'     => 'border-style: solid; border-radius: 0px; border-width: 2px; border-color: #f0c840;',
				),
				'fieldBg'               => '#ffffff',
				'fieldBorder'           => array(
					'border'        => array(
						'color' => '#f0c840',
						'style' => 'solid',
						'width' => '1px',
					),
					'border_radius' => '4px',
					'generated'     => 'border-radius: 4px; border-width: 1px; border-style: solid; border-color: #f0c840;',
				),
				'noptinButtonRadius'    => '4px',
				'CSS'                   => '.noptin-optin-form-wrapper {
	border-top: 4px solid #f0c840;
}',
			),
		),

		'slate2026'              => array(
			'title' => 'Slate',
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '8px',
				'noptinFormBg'          => '#f8fafc',
				'imageMain'             => false,
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Get early access',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#334155',
				'noptinButtonColor'     => '#ffffff',
				'hideTitle'             => false,
				'title'                 => 'Built for busy professionals',
				'titleColor'            => '#0f172a',
				'hideDescription'       => false,
				'description'           => 'Industry insights without the noise. Drop your email to get on the list.',
				'descriptionColor'      => '#64748b',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#94a3b8',
				'titleTypography'       => array(
					'font_size'   => '24',
					'font_weight' => '700',
					'line_height' => '1.35',
					'generated'   => 'font-size: 24px; font-weight: 700; line-height: 1.35;',
				),
				'descriptionTypography' => array(
					'font_size'   => '15',
					'font_weight' => '400',
					'line_height' => '1.6',
					'generated'   => 'font-size: 15px; font-weight: 400; line-height: 1.6;',
				),
				'formBorder'            => array(
					'style'         => 'solid',
					'border_radius' => 8,
					'border_width'  => 1,
					'border_color'  => '#e2e8f0',
					'generated'     => 'border-style: solid; border-radius: 8px; border-width: 1px; border-color: #e2e8f0;',
				),
				'formBoxShadow'         => '0 1px 3px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04)',
				'fieldBg'               => '#ffffff',
				'fieldBorder'           => array(
					'border'        => array(
						'color' => '#e2e8f0',
						'style' => 'solid',
						'width' => '1px',
					),
					'border_radius' => '6px',
					'generated'     => 'border-radius: 6px; border-width: 1px; border-style: solid; border-color: #e2e8f0;',
				),
				'noptinButtonRadius'    => '6px',
			),
		),

		'terracotta2026'         => array(
			'title' => 'Terracotta',
			'data'  => array(
				'singleLine'            => false,
				'formRadius'            => '12px',
				'noptinFormBg'          => '#fdf0e8',
				'fields'                => array(
					array(
						'type'    => array(
							'label' => 'Your name',
							'name'  => 'name',
							'type'  => 'name',
						),
						'require' => false,
						'key'     => 'key-terra-name',
					),
					array(
						'type'    => array(
							'label' => 'Your email',
							'name'  => 'email',
							'type'  => 'email',
						),
						'require' => true,
						'key'     => 'noptin_email_key',
					),
				),
				'imageMain'             => false,
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Subscribe now',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#b85c38',
				'noptinButtonColor'     => '#ffffff',
				'hideTitle'             => false,
				'title'                 => 'Handcrafted for creators',
				'titleColor'            => '#5c2a14',
				'hideDescription'       => false,
				'description'           => 'Stories, strategies and ideas for independent creators. New issue every Friday.',
				'descriptionColor'      => '#8c5a3e',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#c09080',
				'titleTypography'       => array(
					'font_size'   => '26',
					'font_weight' => '700',
					'line_height' => '1.35',
					'generated'   => 'font-size: 26px; font-weight: 700; line-height: 1.35;',
				),
				'descriptionTypography' => array(
					'font_size'   => '15',
					'font_weight' => '400',
					'line_height' => '1.65',
					'generated'   => 'font-size: 15px; font-weight: 400; line-height: 1.65;',
				),
				'formBorder'            => array(
					'style'         => 'solid',
					'border_radius' => 12,
					'border_width'  => 1,
					'border_color'  => '#e8c4a8',
					'generated'     => 'border-style: solid; border-radius: 12px; border-width: 1px; border-color: #e8c4a8;',
				),
				'fieldBg'               => '#ffffff',
				'fieldBorder'           => array(
					'border'        => array(
						'color' => '#e8c4a8',
						'style' => 'solid',
						'width' => '1px',
					),
					'border_radius' => '8px',
					'generated'     => 'border-radius: 8px; border-width: 1px; border-style: solid; border-color: #e8c4a8;',
				),
				'noptinButtonRadius'    => '8px',
			),
		),

		'azure2026'              => array(
			'title' => 'Azure',
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '16px',
				'noptinFormBg'          => '#e8f4fd',
				'imageMain'             => false,
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Subscribe for free',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#0077cc',
				'noptinButtonColor'     => '#ffffff',
				'hideTitle'             => false,
				'title'                 => 'Think smarter, not harder',
				'titleColor'            => '#003a6b',
				'hideDescription'       => false,
				'description'           => 'Evidence-based strategies on productivity and growth, three times a week.',
				'descriptionColor'      => '#336699',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#6699bb',
				'titleTypography'       => array(
					'font_size'   => '26',
					'font_weight' => '700',
					'line_height' => '1.35',
					'generated'   => 'font-size: 26px; font-weight: 700; line-height: 1.35;',
				),
				'descriptionTypography' => array(
					'font_size'   => '15',
					'font_weight' => '400',
					'line_height' => '1.6',
					'generated'   => 'font-size: 15px; font-weight: 400; line-height: 1.6;',
				),
				'formBorder'            => array(
					'style'         => 'solid',
					'border_radius' => 16,
					'border_width'  => 1,
					'border_color'  => '#b8d8f0',
					'generated'     => 'border-style: solid; border-radius: 16px; border-width: 1px; border-color: #b8d8f0;',
				),
				'fieldBg'               => '#ffffff',
				'fieldBorder'           => array(
					'border'        => array(
						'color' => '#b8d8f0',
						'style' => 'solid',
						'width' => '1px',
					),
					'border_radius' => '10px',
					'generated'     => 'border-radius: 10px; border-width: 1px; border-style: solid; border-color: #b8d8f0;',
				),
				'noptinButtonRadius'    => '10px',
				'noptinButtonShadow'    => '0 2px 8px rgba(0,119,204,0.3)',
			),
		),

		'mono2026'               => array(
			'title' => 'Mono',
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '0px',
				'noptinFormBg'          => '#ffffff',
				'imageMain'             => false,
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Subscribe',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#000000',
				'noptinButtonColor'     => '#ffffff',
				'hideTitle'             => false,
				'title'                 => 'Read what matters',
				'titleColor'            => '#000000',
				'hideDescription'       => false,
				'description'           => 'A clean, focused newsletter. No ads, no tracking, just good writing.',
				'descriptionColor'      => '#555555',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#999999',
				'titleTypography'       => array(
					'font_size'   => '28',
					'font_weight' => '700',
					'line_height' => '1.25',
					'generated'   => 'font-size: 28px; font-weight: 700; line-height: 1.25;',
				),
				'descriptionTypography' => array(
					'font_size'   => '15',
					'font_weight' => '400',
					'line_height' => '1.6',
					'generated'   => 'font-size: 15px; font-weight: 400; line-height: 1.6;',
				),
				'formBorder'            => array(
					'style'         => 'solid',
					'border_radius' => 0,
					'border_width'  => 2,
					'border_color'  => '#000000',
					'generated'     => 'border-style: solid; border-radius: 0px; border-width: 2px; border-color: #000000;',
				),
				'fieldBg'               => '#ffffff',
				'fieldBorder'           => array(
					'border'        => array(
						'color' => '#000000',
						'style' => 'solid',
						'width' => '1px',
					),
					'border_radius' => '0px',
					'generated'     => 'border-radius: 0px; border-width: 1px; border-style: solid; border-color: #000000;',
				),
				'noptinButtonRadius'    => '0px',
				'CSS'                   => '.noptin-optin-form-wrapper .noptin-form-submit {
	letter-spacing: 1px;
	text-transform: uppercase;
	font-size: 13px !important;
}',
			),
		),

		'lavender2026'           => array(
			'title' => 'Lavender',
			'data'  => array(
				'singleLine'            => false,
				'formRadius'            => '16px',
				'noptinFormBg'          => '#f5f0ff',
				'imageMain'             => false,
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Get on the list',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#7c3aed',
				'noptinButtonColor'     => '#ffffff',
				'hideTitle'             => false,
				'title'                 => 'Your weekly dose of clarity',
				'titleColor'            => '#3b0764',
				'hideDescription'       => false,
				'description'           => 'Mental models, mindset shifts and small wins — in your inbox every Sunday.',
				'descriptionColor'      => '#6d28d9',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#a78bfa',
				'titleTypography'       => array(
					'font_size'   => '26',
					'font_weight' => '700',
					'line_height' => '1.35',
					'generated'   => 'font-size: 26px; font-weight: 700; line-height: 1.35;',
				),
				'descriptionTypography' => array(
					'font_size'   => '15',
					'font_weight' => '400',
					'line_height' => '1.65',
					'generated'   => 'font-size: 15px; font-weight: 400; line-height: 1.65;',
				),
				'formBorder'            => array(
					'style'         => 'solid',
					'border_radius' => 16,
					'border_width'  => 1,
					'border_color'  => '#ddd6fe',
					'generated'     => 'border-style: solid; border-radius: 16px; border-width: 1px; border-color: #ddd6fe;',
				),
				'formBoxShadow'         => '0 4px 24px rgba(124,58,237,0.12)',
				'fieldBg'               => '#ffffff',
				'fieldBorder'           => array(
					'border'        => array(
						'color' => '#ddd6fe',
						'style' => 'solid',
						'width' => '1px',
					),
					'border_radius' => '10px',
					'generated'     => 'border-radius: 10px; border-width: 1px; border-style: solid; border-color: #ddd6fe;',
				),
				'noptinButtonRadius'    => '10px',
			),
		),

		'forest2026'             => array(
			'title' => 'Forest',
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '8px',
				'noptinFormBg'          => '#1a2e1a',
				'imageMain'             => false,
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Start reading',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#4ade80',
				'noptinButtonColor'     => '#14532d',
				'hideTitle'             => false,
				'title'                 => 'Grow your monthly revenue',
				'titleColor'            => '#f0fdf4',
				'hideDescription'       => false,
				'description'           => 'Actionable growth strategies for SaaS founders. Sent every other Tuesday.',
				'descriptionColor'      => '#86efac',
				'hideNote'              => true,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#4ade80',
				'titleTypography'       => array(
					'font_size'   => '26',
					'font_weight' => '700',
					'line_height' => '1.3',
					'generated'   => 'font-size: 26px; font-weight: 700; line-height: 1.3;',
				),
				'descriptionTypography' => array(
					'font_size'   => '14',
					'font_weight' => '400',
					'line_height' => '1.65',
					'generated'   => 'font-size: 14px; font-weight: 400; line-height: 1.65;',
				),
				'formBorder'            => array(
					'style'         => 'solid',
					'border_radius' => 8,
					'border_width'  => 1,
					'border_color'  => '#2d5a2d',
					'generated'     => 'border-style: solid; border-radius: 8px; border-width: 1px; border-color: #2d5a2d;',
				),
				'fieldBg'               => '#2d4a2d',
				'fieldBorder'           => array(
					'border'        => array(
						'color' => '#3d6a3d',
						'style' => 'solid',
						'width' => '1px',
					),
					'border_radius' => '6px',
					'generated'     => 'border-radius: 6px; border-width: 1px; border-style: solid; border-color: #3d6a3d;',
				),
				'fieldTextColor'        => '#f0fdf4',
				'noptinButtonRadius'    => '6px',
			),
		),

		'warm2026'               => array(
			'title' => 'Warm White',
			'data'  => array(
				'singleLine'            => false,
				'formRadius'            => '20px',
				'noptinFormBg'          => '#ffffff',
				'imageMain'             => false,
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Join for free',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#f59e0b',
				'noptinButtonColor'     => '#ffffff',
				'hideTitle'             => false,
				'title'                 => 'Start your week right',
				'titleColor'            => '#111827',
				'hideDescription'       => false,
				'description'           => 'Positive, practical, and straight to the point. Over 12,000 readers already love it.',
				'descriptionColor'      => '#6b7280',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#9ca3af',
				'titleTypography'       => array(
					'font_size'   => '26',
					'font_weight' => '700',
					'line_height' => '1.35',
					'generated'   => 'font-size: 26px; font-weight: 700; line-height: 1.35;',
				),
				'descriptionTypography' => array(
					'font_size'   => '15',
					'font_weight' => '400',
					'line_height' => '1.65',
					'generated'   => 'font-size: 15px; font-weight: 400; line-height: 1.65;',
				),
				'formBorder'            => array(
					'style'         => 'solid',
					'border_radius' => 20,
					'border_width'  => 1,
					'border_color'  => '#f3f4f6',
					'generated'     => 'border-style: solid; border-radius: 20px; border-width: 1px; border-color: #f3f4f6;',
				),
				'formBoxShadow'         => '0 8px 40px rgba(0,0,0,0.08)',
				'fieldBg'               => '#fafafa',
				'fieldBorder'           => array(
					'border'        => array(
						'color' => '#e5e7eb',
						'style' => 'solid',
						'width' => '1px',
					),
					'border_radius' => '12px',
					'generated'     => 'border-radius: 12px; border-width: 1px; border-style: solid; border-color: #e5e7eb;',
				),
				'noptinButtonRadius'    => '12px',
				'noptinButtonShadow'    => '0 2px 8px rgba(245,158,11,0.35)',
			),
		),

		'ink2026'                => array(
			'title' => 'Ink',
			'data'  => array(
				'singleLine'            => false,
				'formRadius'            => '0px',
				'noptinFormBg'          => '#f7f3ee',
				'imageMain'             => false,
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Subscribe',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#1a1008',
				'noptinButtonColor'     => '#f7f3ee',
				'hideTitle'             => false,
				'title'                 => 'Writing worth your time',
				'titleColor'            => '#1a1008',
				'hideDescription'       => false,
				'description'           => 'Personal essays, long reads and ideas that stick. Once a week on Sunday.',
				'descriptionColor'      => '#5c4a30',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#a08060',
				'titleTypography'       => array(
					'font_size'   => '30',
					'font_weight' => '700',
					'line_height' => '1.25',
					'style'       => 'italic',
					'generated'   => 'font-size: 30px; font-weight: 700; line-height: 1.25; font-style: italic;',
				),
				'descriptionTypography' => array(
					'font_size'   => '15',
					'font_weight' => '400',
					'line_height' => '1.7',
					'generated'   => 'font-size: 15px; font-weight: 400; line-height: 1.7;',
				),
				'formBorder'            => array(
					'style'         => 'none',
					'border_radius' => 0,
					'border_width'  => 0,
					'border_color'  => '#1a1008',
					'generated'     => 'border-style: none;',
				),
				'fieldBg'               => '#ffffff',
				'fieldBorder'           => array(
					'border'        => array(
						'color' => '#c8b090',
						'style' => 'solid',
						'width' => '1px',
					),
					'border_radius' => '2px',
					'generated'     => 'border-radius: 2px; border-width: 1px; border-style: solid; border-color: #c8b090;',
				),
				'noptinButtonRadius'    => '2px',
				'CSS'                   => '.noptin-optin-form-wrapper {
	border-top: 3px solid #1a1008;
}
.noptin-optin-form-wrapper .noptin-form-submit {
	letter-spacing: 0.8px;
	text-transform: uppercase;
	font-size: 13px !important;
}',
			),
		),

		'deeppink2026'           => array(
			'title' => 'Deep Pink',
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '12px',
				'noptinFormBg'          => '#fff0f5',
				'imageMain'             => false,
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Join the community',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#be185d',
				'noptinButtonColor'     => '#ffffff',
				'hideTitle'             => false,
				'title'                 => 'Made for bold thinkers',
				'titleColor'            => '#831843',
				'hideDescription'       => false,
				'description'           => 'The newsletter that challenges the default, questions the obvious and backs it with data.',
				'descriptionColor'      => '#be185d',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#f9a8d4',
				'titleTypography'       => array(
					'font_size'   => '26',
					'font_weight' => '700',
					'line_height' => '1.35',
					'generated'   => 'font-size: 26px; font-weight: 700; line-height: 1.35;',
				),
				'descriptionTypography' => array(
					'font_size'   => '15',
					'font_weight' => '400',
					'line_height' => '1.6',
					'generated'   => 'font-size: 15px; font-weight: 400; line-height: 1.6;',
				),
				'formBorder'            => array(
					'style'         => 'solid',
					'border_radius' => 12,
					'border_width'  => 1,
					'border_color'  => '#fbb6ce',
					'generated'     => 'border-style: solid; border-radius: 12px; border-width: 1px; border-color: #fbb6ce;',
				),
				'fieldBg'               => '#ffffff',
				'fieldBorder'           => array(
					'border'        => array(
						'color' => '#fbb6ce',
						'style' => 'solid',
						'width' => '1px',
					),
					'border_radius' => '8px',
					'generated'     => 'border-radius: 8px; border-width: 1px; border-style: solid; border-color: #fbb6ce;',
				),
				'noptinButtonRadius'    => '8px',
				'noptinButtonShadow'    => '0 2px 10px rgba(190,24,93,0.25)',
			),
		),

		'ocean2026'              => array(
			'title' => 'Ocean',
			'data'  => array(
				'singleLine'            => false,
				'formRadius'            => '16px',
				'noptinFormBg'          => '#0e3a5c',
				'imageMain'             => false,
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'Subscribe for free',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#38bdf8',
				'noptinButtonColor'     => '#0e3a5c',
				'hideTitle'             => false,
				'title'                 => 'Deep insights, shallow noise',
				'titleColor'            => '#e0f2fe',
				'hideDescription'       => false,
				'description'           => 'The newsletter that cuts the clickbait and gets to what actually matters this week.',
				'descriptionColor'      => '#7dd3fc',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => $noptin_privacy_text,
				'noteColor'             => '#0ea5e9',
				'titleTypography'       => array(
					'font_size'   => '26',
					'font_weight' => '700',
					'line_height' => '1.35',
					'generated'   => 'font-size: 26px; font-weight: 700; line-height: 1.35;',
				),
				'descriptionTypography' => array(
					'font_size'   => '15',
					'font_weight' => '400',
					'line_height' => '1.65',
					'generated'   => 'font-size: 15px; font-weight: 400; line-height: 1.65;',
				),
				'formBorder'            => array(
					'style'         => 'solid',
					'border_radius' => 16,
					'border_width'  => 1,
					'border_color'  => '#1a5a8c',
					'generated'     => 'border-style: solid; border-radius: 16px; border-width: 1px; border-color: #1a5a8c;',
				),
				'fieldBg'               => '#164e7a',
				'fieldBorder'           => array(
					'border'        => array(
						'color' => '#2a7ab8',
						'style' => 'solid',
						'width' => '1px',
					),
					'border_radius' => '10px',
					'generated'     => 'border-radius: 10px; border-width: 1px; border-style: solid; border-color: #2a7ab8;',
				),
				'fieldTextColor'        => '#e0f2fe',
				'noptinButtonRadius'    => '10px',
			),
		),

	);
