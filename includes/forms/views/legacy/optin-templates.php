<?php defined( 'ABSPATH' ) || exit; ?>
<?php

	return array(

		'PRO20200311'            => array(
			'title' => __( 'Professional', 'newsletter-optin-box' ),
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '0px',
				'formWidth'             => '520px',
				'formHeight'            => '280px',
				'noptinFormBg'          => '#f8f9fa',
				'noptinFormBgImg'       => '',
				'noptinFormBgVideo'     => '',
				'fields'                => array(
					array(
						'type'    => array(
							'label' => __( 'Email Address', 'newsletter-optin-box' ),
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
					'border_color'  => '#f8f9fa',
					'generated'     => 'border-style: none; border-radius: 0px; border-width: 0px; border-color: #f8f9fa;',
				),
				'image'                 => noptin()->plugin_url . 'includes/assets/images/avatar.png',
				'imagePos'              => 'left',
				'noptinButtonLabel'     => __( 'Sign Up', 'newsletter-optin-box' ),
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#00d07e',
				'noptinButtonColor'     => '#ffffff',
				'prefix'                => '',
				'hideTitle'             => false,
				'title'                 => __( 'Get Exclusive SEO Tips', 'newsletter-optin-box' ),
				'titleColor'            => '#010101',
				'hideDescription'       => false,
				'description'           => __( 'Receive the same tips I used to double my traffic in just two weeks!', 'newsletter-optin-box' ),
				'descriptionColor'      => '#010101',
				'hideNote'              => true,
				'hideOnNoteClick'       => false,
				'note'                  => __( 'By subscribing, you agree with our <a href="">privacy policy</a> and our terms of service.', 'newsletter-optin-box' ),
				'noteColor'             => '#010101',
				'titleTypography'       => array(
					'font_size'   => '20',
					'font_weight' => '700',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 20px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'CSS'                   => '.noptin-optin-form-wrapper form.noptin-form-single-line .noptin-form-fields .noptin-form-submit{
	position: absolute;
	right: 10px;
	top: 6px;
	bottom: 6px;
	padding-top: 2px;
}

.noptin-optin-form-wrapper .noptin-form-header-image img{
    border-radius: 50%;
}

.noptin-optin-form-wrapper form.noptin-form-single-line .noptin-form-fields {
	position: relative;
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


		'owvP1565701640'         => array(
			'title' => __( 'Borderless', 'newsletter-optin-box' ),
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '10px',
				'formWidth'             => '520px',
				'formHeight'            => '280px',
				'noptinFormBg'          => '#ffffff',
				'noptinFormBgImg'       => '',
				'noptinFormBgVideo'     => '',
				'fields'                => array(
					array(
						'type'    => array(
							'label' => __( 'Enter your email address here', 'newsletter-optin-box' ),
							'name'  => 'email',
							'type'  => 'email',
						),

						'require' => 'true',
						'key'     => 'noptin_email_key',
					),

				),

				'imageMain'             => '',
				'noptinFormBorderColor' => '#ffffff',
				'formBorder'            => array(
					'style'         => 'none',
					'border_radius' => 0,
					'border_width'  => 0,
					'border_color'  => '#ffffff',
					'generated'     => 'border-style: none; border-radius: 0px; border-width: 0px; border-color: #ffffff;',
				),
				'image'                 => '',
				'imagePos'              => 'right',
				'noptinButtonLabel'     => __( 'SUBSCRIBE NOW', 'newsletter-optin-box' ),
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#F44336',
				'noptinButtonColor'     => '#fefefe',
				'prefix'                => '',
				'hideTitle'             => false,
				'title'                 => __( 'Subscribe For Latest Updates', 'newsletter-optin-box' ),
				'titleColor'            => '#191919',
				'hideDescription'       => false,
				'description'           => __( "We'll send you the best business news and informed analysis on what matters the most to you.", 'newsletter-optin-box' ),
				'descriptionColor'      => '#666666',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => __( 'By subscribing, you agree with our <a href="">privacy policy</a> and our terms of service.', 'newsletter-optin-box' ),
				'noteColor'             => '#607D8B',
				'titleTypography'       => array(
					'font_size'   => '32',
					'font_weight' => '700',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => 'italic',
					'generated'   => 'font-size: 32px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'CSS'                   => '.noptin-optin-form-wrapper .noptin-form-field{
	text-align: center;
 	font-weight: 500;
}

.noptin-optin-form-wrapper form .noptin-form-footer .noptin-form-submit{
	font-weight: 500;
}',
			),

		),

		'IEiH1565701672'         => array(
			'title' => __( 'Classic', 'newsletter-optin-box' ),
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '10px',
				'formWidth'             => '520px',
				'formHeight'            => '280px',
				'noptinFormBg'          => '#fafafa',
				'noptinFormBgImg'       => '',
				'noptinFormBgVideo'     => '',
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

				'imageMain'             => false,
				'noptinFormBorderColor' => '#009688',
				'image'                 => noptin()->plugin_url . 'includes/assets/images/email-icon.png',
				'imagePos'              => 'right',
				'noptinButtonLabel'     => __( 'Subscribe Now', 'newsletter-optin-box' ),
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#009688',
				'noptinButtonColor'     => '#fefefe',
				'prefix'                => '',
				'hideTitle'             => false,
				'title'                 => __( 'Subscribe To Our Newsletter', 'newsletter-optin-box' ),
				'titleColor'            => '#191919',
				'hideDescription'       => false,
				'description'           => sprintf(
					/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
					__( 'Enter your email to receive a weekly round-up of our best posts.  %1$sLearn more! %2$s', 'newsletter-optin-box' ),
					'<a href="https://noptin.com/guide">',
					'</a>'
				),
				'descriptionColor'      => '#666666',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => __( 'By subscribing, you agree with our <a href="">privacy policy</a> and our terms of service.', 'newsletter-optin-box' ),
				'noteColor'             => '#607D8B',
				'titleTypography'       => array(
					'font_size'   => '20',
					'font_weight' => '700',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 20px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'CSS'                   => '.noptin-optin-form-wrapper *{}',
			),

		),

		'qQOv1565701677'         => array(
			'title' => __( 'Subheading', 'newsletter-optin-box' ),
			'data'  => array(
				'singleLine'            => false,
				'formRadius'            => '0px',
				'formWidth'             => '520px',
				'formHeight'            => '280px',
				'noptinFormBg'          => '#fafafa',
				'noptinFormBgImg'       => '',
				'noptinFormBgVideo'     => '',
				'fields'                => array(
					array(
						'type'    => array(
							'label' => __( 'Enter your name here', 'newsletter-optin-box' ),
							'name'  => 'first_name',
							'type'  => 'first_name',
						),

						'require' => false,
						'key'     => 'key-fkluoh',
					),

					array(
						'type'    => array(
							'label' => __( 'Enter your email address', 'newsletter-optin-box' ),
							'name'  => 'email',
							'type'  => 'email',
						),

						'require' => true,
						'key'     => 'noptin_email_key',
					),

				),

				'imageMain'             => '',
				'noptinFormBorderColor' => '#9E9E9E',
				'image'                 => '',
				'imagePos'              => 'right',
				'noptinButtonLabel'     => __( 'Subscribe Now', 'newsletter-optin-box' ),
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#009688',
				'noptinButtonColor'     => '#fefefe',
				'prefix'                => '',
				'hideTitle'             => false,
				'title'                 => __( "DON'T MISS OUT!", 'newsletter-optin-box' ),
				'titleColor'            => '#009688',
				'hideDescription'       => '',
				'description'           => __( 'Subscribe To Our Newsletter', 'newsletter-optin-box' ),
				'descriptionColor'      => '#666666',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => __( 'By subscribing, you agree with our <a href="">privacy policy</a> and our terms of service.', 'newsletter-optin-box' ),
				'noteColor'             => '#607D8B',
				'titleTypography'       => array(
					'font_size'   => '20',
					'font_weight' => '700',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 20px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '30',
					'font_weight' => '700',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 30px; font-weight: 700; line-height: 1.5;',
				),
				'CSS'                   => '',

			),

		),

		'ICkq1565701695'         => array(
			'title' => __( 'Border Top', 'newsletter-optin-box' ),
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '0px',
				'formWidth'             => '520px',
				'formHeight'            => '280px',
				'noptinFormBg'          => '#ffffff',
				'noptinFormBgImg'       => '',
				'noptinFormBgVideo'     => '',
				'fields'                => array(
					array(
						'type'    => array(
							'label' => __( 'Email Address', 'newsletter-optin-box' ),
							'name'  => 'email',
							'type'  => 'email',
						),

						'require' => true,
						'key'     => 'noptin_email_key',
					),

				),

				'imageMain'             => '',
				'noptinFormBorderColor' => '#795548',
				'image'                 => '',
				'imagePos'              => 'right',
				'noptinButtonLabel'     => 'JOIN',
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#795548',
				'noptinButtonColor'     => '#fefefe',
				'prefix'                => '',
				'hideTitle'             => false,
				'title'                 => __( 'Join Our Newsletter', 'newsletter-optin-box' ),
				'titleColor'            => '#191919',
				'hideDescription'       => false,
				'description'           => __( 'get weekly access to our best tips, tricks and updates.', 'newsletter-optin-box' ),
				'descriptionColor'      => '#666666',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => __( 'By subscribing, you agree with our <a href="">privacy policy</a> and our terms of service.', 'newsletter-optin-box' ),
				'noteColor'             => '#607D8B',
				'titleTypography'       => array(
					'font_size'   => '20',
					'font_weight' => '700',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 20px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'formBorder'            => array(
					'style'        => 'solid',
					'formRadius'   => 0,
					'border_width' => 4,
					'border_color' => '#795548',
					'generated'    => 'border-style: solid; border-radius: 0px; border-width: 4px; border-color: #795548;',
				),
				'CSS'                   => '.noptin-optin-form-wrapper {
	border-top-style: solid !important;
	border-left-style: none !important;
	border-right-style: none !important;
	border-bottom-style: none !important;
	box-shadow: 0 2px 5px 0 rgba(0,0,0,.16), 0 2px 10px 0 rgba(0,0,0,.12);
}

.noptin-optin-form-wrapper form .noptin-form-header {
	text-align: left;
 	justify-content: left;
}',

			),

		),

		'BLyQ1565701700'         => array(
			'title' => 'smartpassiveincome.com',
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '0px',
				'formWidth'             => '720px',
				'formHeight'            => '280px',
				'noptinFormBg'          => '#4CAF50',
				'noptinFormBgVideo'     => '',
				'noptinFormBgImg'       => noptin()->plugin_url . 'includes/assets/images/bg1.jpg',
				'fields'                => array(
					array(
						'type'    => array(
							'label' => __( 'First Name', 'newsletter-optin-box' ),
							'name'  => 'first_name',
							'type'  => 'first_name',
						),

						'require' => false,
						'key'     => 'key-ltoxdb',
					),

					array(
						'type'    => array(
							'label' => __( 'Email Address', 'newsletter-optin-box' ),
							'name'  => 'email',
							'type'  => 'email',
						),

						'require' => true,
						'key'     => 'noptin_email_key',
					),

				),

				'imageMain'             => '',
				'formBorder'            => array(
					'style'         => 'none',
					'border_radius' => 0,
					'border_width'  => 0,
					'border_color'  => '#000000',
					'generated'     => 'border-style: none; border-radius: 0px; border-width: 0px; border-color: #000000;',
				),
				'image'                 => '',
				'imagePos'              => 'right',
				'noptinButtonLabel'     => __( 'Subscribe Now', 'newsletter-optin-box' ),
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => 'rgba(0,0,0,0)',
				'noptinButtonColor'     => '#fefefe',
				'prefix'                => '',
				'hideTitle'             => false,
				'title'                 => 'WANT THE INSIDE SCOOP?',
				'titleColor'            => '#f1f1f1',
				'hideDescription'       => false,
				'description'           => __( 'Use the custom CSS panel to change the background colors', 'newsletter-optin-box' ),
				'descriptionColor'      => '#f2f2f2',
				'hideNote'              => true,
				'hideOnNoteClick'       => false,
				'note'                  => __( 'By subscribing, you agree with our <a href="">privacy policy</a> and our terms of service.', 'newsletter-optin-box' ),
				'noteColor'             => '#607D8B',
				'titleTypography'       => array(
					'font_size'   => '20',
					'font_weight' => '700',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 20px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
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
			'title' => __( 'Rounded Fields', 'newsletter-optin-box' ),
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '20px',
				'formWidth'             => '520px',
				'formHeight'            => '280px',
				'noptinFormBg'          => '#424242',
				'noptinFormBgImg'       => '',
				'noptinFormBgVideo'     => '',
				'fields'                => array(
					array(
						'type'    => array(
							'label' => __( 'Email Address', 'newsletter-optin-box' ),
							'name'  => 'email',
							'type'  => 'email',
						),

						'require' => false,
						'key'     => 'key-pyuiwgwtoi',
					),

				),

				'imageMain'             => '',
				'noptinFormBorderColor' => '#424242',
				'image'                 => noptin()->plugin_url . 'includes/assets/images/email-icon.png',
				'imagePos'              => 'left',
				'noptinButtonLabel'     => __( 'Subscribe Now', 'newsletter-optin-box' ),
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#607D8B',
				'noptinButtonColor'     => '#fafafa',
				'prefix'                => '',
				'hideTitle'             => false,
				'title'                 => __( 'Subscribe To Our Newsletter', 'newsletter-optin-box' ),
				'titleColor'            => '#fafafa',
				'hideDescription'       => false,
				'description'           => __( 'Enter your email to receive a weekly round-up of our best posts. Learn more!', 'newsletter-optin-box' ),
				'descriptionColor'      => '#fafafa',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => __( 'By subscribing, you agree with our <a href="">privacy policy</a> and our terms of service.', 'newsletter-optin-box' ),
				'noteColor'             => '#fafafa',
				'titleTypography'       => array(
					'font_size'   => '20',
					'font_weight' => '700',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 20px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'CSS'                   => '/*Custom css*/

.noptin-optin-form-wrapper .noptin-form-single-line .noptin-form-fields{
 	position: relative;
}

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
			'title' => 'Backlinko',
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '0px',
				'formWidth'             => '520px',
				'formHeight'            => '280px',
				'noptinFormBg'          => '#4CAF50',
				'noptinFormBgImg'       => '',
				'noptinFormBgVideo'     => '',
				'fields'                => array(
					array(
						'type'    => array(
							'label' => __( 'Email Address', 'newsletter-optin-box' ),
							'name'  => 'email',
							'type'  => 'email',
						),

						'require' => true,
						'key'     => 'noptin_email_key',
					),

				),

				'imageMain'             => false,
				'noptinFormBorderColor' => '#4CAF50',
				'image'                 => '',
				'imagePos'              => 'right',
				'noptinButtonLabel'     => __( 'Sign Up', 'newsletter-optin-box' ),
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#ee4a03',
				'noptinButtonColor'     => '#ffffff',
				'prefix'                => '',
				'hideTitle'             => false,
				'title'                 => __( 'Try Applying the Green Color Theme!', 'newsletter-optin-box' ),
				'titleColor'            => '#fafafa',
				'hideDescription'       => true,
				'description'           => __( 'Click on the design tab to change the appearance of this form.', 'newsletter-optin-box' ),
				'descriptionColor'      => '#fafafa',
				'hideNote'              => true,
				'hideOnNoteClick'       => false,
				'note'                  => __( 'By subscribing, you agree with our <a href="">privacy policy</a> and our terms of service.', 'newsletter-optin-box' ),
				'noteColor'             => '#fafafa',
				'titleTypography'       => array(
					'font_size'   => '20',
					'font_weight' => '700',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 20px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'CSS'                   => '@media only screen and (min-width: 768px) {
	.noptin-optin-form-wrapper form.noptin-form-single-line .noptin-form-fields .noptin-form-submit{
		position: absolute;
		right: 10px;
		top: 6px;
		bottom: 6px;
		padding-top: 2px;
	}
}

.noptin-optin-form-wrapper form.noptin-form-single-line .noptin-form-fields .noptin-form-field {
    border-radius: 4px;
 	padding: 1.2em 2em;
 border: 1px solid transparent;
 font-size: 16px;
}

.noptin-optin-form-wrapper form.noptin-form-single-line .noptin-form-fields{
 position: relative;
}

.noptin-optin-form-wrapper form.noptin-form-single-line .noptin-form-header {
	flex: 0 0 auto;
 	justify-content: center;
}

.noptin-optin-form-wrapper form.noptin-form-single-line {
	justify-content: center
}

.noptin-popup-main-wrapper{
 	background-color: #e4e2dd;
}',
			),

		),

		'89zl1570214684'         => array(
			'title' => 'Michael Thiessen',
			'data'  => array(
				'singleLine'            => false,
				'formRadius'            => '8px',
				'formWidth'             => '520px',
				'formHeight'            => '250px',
				'noptinFormBg'          => '#f1f5f8',
				'noptinFormBgImg'       => '',
				'noptinFormBgVideo'     => '',
				'fields'                => array(
					array(
						'type'    => array(
							'label' => __( 'Email Address', 'newsletter-optin-box' ),
							'name'  => 'email',
							'type'  => 'email',
						),

						'require' => true,
						'key'     => 'noptin_email_key',
					),

				),

				'imageMain'             => false,
				'noptinFormBorderColor' => '#6cb2eb',
				'image'                 => '',
				'imagePos'              => 'right',
				'noptinButtonLabel'     => __( 'Download Now', 'newsletter-optin-box' ),
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#2779bd',
				'noptinButtonColor'     => '#fefefe',
				'prefix'                => '',
				'hideTitle'             => false,
				'title'                 => __( 'Get my FREE book on VueJS', 'newsletter-optin-box' ),
				'titleColor'            => '#313131',
				'hideDescription'       => false,
				'description'           => '<ul><li><strong>144 pages</strong> of content</li><li>Solve common, <strong>frustrating</strong> problems</li><li>Understand confusing errors</li><li>And so much more!</li></ul>',
				'descriptionColor'      => '#313131',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => __( 'By subscribing, you agree with our <a href="">privacy policy</a> and our terms of service.', 'newsletter-optin-box' ),
				'noteColor'             => '#313131',
				'titleTypography'       => array(
					'font_size'   => '20',
					'font_weight' => '700',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 20px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'formBorder'            => array(
					'style'         => 'solid',
					'border_radius' => 8,
					'border_width'  => 1,
					'border_color'  => '#6cb2eb',
					'generated'     => 'border-style: solid; border-radius: 8px; border-width: 0px; border-color: #6cb2eb;',
				),
				'CSS'                   => '',
			),

		),

		'r5g21565701726'         => array(
			'title' => __( 'Hearts', 'newsletter-optin-box' ),
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '0px',
				'formWidth'             => '520px',
				'formHeight'            => '250px',
				'noptinFormBg'          => '#009688',
				'noptinFormBgImg'       => '',
				'fields'                => array(
					array(
						'type'    => array(
							'label' => __( 'Email Address', 'newsletter-optin-box' ),
							'name'  => 'email',
							'type'  => 'email',
						),

						'require' => true,
						'key'     => 'key-irmofawahh',
					),

				),

				'imageMain'             => false,
				'noptinFormBorderColor' => '#009688',
				'image'                 => noptin()->plugin_url . 'includes/assets/images/heart.png',
				'imagePos'              => 'right',
				'noptinButtonLabel'     => __( 'Yes Please!', 'newsletter-optin-box' ),
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#191919',
				'noptinButtonColor'     => '#ffffff',
				'prefix'                => '',
				'hideTitle'             => false,
				'title'                 => __( 'Get some love!', 'newsletter-optin-box' ),
				'titleColor'            => '#ffffff',
				'hideDescription'       => false,
				'description'           => __( 'Use the Custom CSS tab to change the background color of this form.', 'newsletter-optin-box' ),
				'descriptionColor'      => '#dff0fe',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => __( 'By subscribing, you agree with our <a href="">privacy policy</a> and our terms of service.', 'newsletter-optin-box' ),
				'noteColor'             => '#ffffff',
				'titleTypography'       => array(
					'font_size'   => '20',
					'font_weight' => '700',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 20px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
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
			'title' => 'Convertkit',
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '8px',
				'formWidth'             => '620px',
				'formHeight'            => '280px',
				'noptinFormBg'          => '#fb6970',
				'noptinFormBgImg'       => '',
				'fields'                => array(
					array(
						'type'    => array(
							'label' => __( 'Email Address', 'newsletter-optin-box' ),
							'name'  => 'email',
							'type'  => 'email',
						),

						'require' => true,
						'key'     => 'noptin_email_key',
					),

				),

				'imageMain'             => false,
				'noptinFormBorderColor' => '#fb6970',
				'image'                 => '',
				'imagePos'              => 'right',
				'noptinButtonLabel'     => __( 'SEND IT MY WAY!', 'newsletter-optin-box' ),
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#ffffff',
				'noptinButtonColor'     => '#fb6970',
				'prefix'                => '',
				'hideTitle'             => false,
				'title'                 => __( 'Optimize your opt-ins.', 'newsletter-optin-box' ),
				'titleColor'            => '#212f4f',
				'hideDescription'       => false,
				'description'           => __( 'Collecting emails is easier than ever with this opt-in focused Toolkit.  Get the resources you need sent straight to your inbox.', 'newsletter-optin-box' ),
				'descriptionColor'      => '#ffffff',
				'hideNote'              => true,
				'hideOnNoteClick'       => false,
				'note'                  => __( 'By subscribing, you agree with our <a href="">privacy policy</a> and our terms of service.', 'newsletter-optin-box' ),
				'noteColor'             => '#fafafa',
				'titleTypography'       => array(
					'font_size'   => '20',
					'font_weight' => '700',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 20px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'CSS'                   => '/*Custom css*/
.noptin-optin-form-wrapper .noptin-form-submit{
	border-radius: 5px !important;
	font-weight: 400 !important;
	letter-spacing: .8px !important;
	font-size: 15px !important;
}

.noptin-optin-form-wrapper form .noptin-form-fields .noptin-form-field {
	border-radius: 5px !important;
	background: 0 0 !important;
	border: 1px solid #fff !important;
	font-size: 18px !important;
	padding: 10px 8px !important;
}
			',
			),

		),

		'discount21565701726'    => array(
			'title' => __( 'Discount', 'newsletter-optin-box' ),
			'data'  => array(
				'singleLine'            => false,
				'formRadius'            => '1px',
				'formWidth'             => '700px',
				'formHeight'            => '500px',
				'noptinFormBg'          => '#263238',
				'noptinFormBgImg'       => '',
				'fields'                => array(
					array(
						'type'    => array(
							'label' => __( 'Enter your email here', 'newsletter-optin-box' ),
							'name'  => 'email',
							'type'  => 'email',
						),

						'require' => true,
						'key'     => 'noptin_email_key',
					),

				),

				'imageMain'             => false,
				'noptinFormBorderColor' => '#263238',
				'image'                 => '',
				'imagePos'              => 'right',
				'noptinButtonLabel'     => __( 'GET IT NOW!', 'newsletter-optin-box' ),
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#607d8b',
				'noptinButtonColor'     => '#ffffff',
				'hidePrefix'            => false,
				'prefix'                => __( 'ENTER YOUR EMAIL AND GET', 'newsletter-optin-box' ),
				'prefixColor'           => '#607d8b',
				'hideTitle'             => false,
				'title'                 => __( '40% OFF', 'newsletter-optin-box' ),
				'titleColor'            => '#ffffff',
				'hideDescription'       => false,
				'description'           => __( 'on orders of $25 or more', 'newsletter-optin-box' ),
				'descriptionColor'      => '#607d8b',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => __( 'Entering your email also makes you eligible to receive future promotional emails.', 'newsletter-optin-box' ),
				'noteColor'             => '#607d8b',
				'titleTypography'       => array(
					'font_size'   => '85',
					'font_weight' => '700',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 85px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'CSS'                   => '',
			),

		),

		'discountalt21565701726' => array(
			'title' => __( 'Discount Alt', 'newsletter-optin-box' ),
			'data'  => array(
				'singleLine'            => false,
				'formRadius'            => '0px',
				'formWidth'             => '600px',
				'formHeight'            => '280px',
				'noptinFormBg'          => '#fefcfc',
				'noptinFormBgImg'       => '',
				'fields'                => array(
					array(
						'type'    => array(
							'label' => __( 'Enter Your Email Address', 'newsletter-optin-box' ),
							'name'  => 'email',
							'type'  => 'email',
						),

						'require' => true,
						'key'     => 'noptin_email_key',
					),

				),

				'imageMain'             => false,
				'noptinFormBorderColor' => '#E0E0E0',
				'image'                 => '',
				'imagePos'              => 'right',
				'noptinButtonLabel'     => __( 'GET IT NOW!', 'newsletter-optin-box' ),
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#313131',
				'noptinButtonColor'     => '#ffffff',
				'prefix'                => '',
				'hideTitle'             => false,
				'title'                 => 'GET 10% OFF',
				'titleColor'            => '#313131',
				'hideDescription'       => false,
				'description'           => '<p>' . __( 'SUBSCRIBE TO OUR NEWSLETTER & RECEIVE A COUPON', 'newsletter-optin-box' ) . '</p>',
				'descriptionColor'      => '#313131',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => __( '* $50 MINIMUM PURCHASE', 'newsletter-optin-box' ),
				'noteColor'             => '#9E9E9E',
				'titleTypography'       => array(
					'font_size'   => '66',
					'font_weight' => '700',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 66px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
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
			'title' => __( 'Two Backgrounds', 'newsletter-optin-box' ),
			'data'  => array(
				'singleLine'            => false,
				'formRadius'            => '0px',
				'formWidth'             => '600px',
				'formHeight'            => '280px',
				'noptinFormBg'          => '#607D8B',
				'noptinFormBgImg'       => noptin()->plugin_url . 'includes/assets/images/double-bg.png',
				'fields'                => array(
					array(
						'type'    => array(
							'label' => __( 'Enter Your Email Address', 'newsletter-optin-box' ),
							'name'  => 'email',
							'type'  => 'email',
						),

						'require' => true,
						'key'     => 'noptin_email_key',
					),

				),

				'imageMain'             => false,
				'noptinFormBorderColor' => '#ffffff',
				'image'                 => '',
				'imagePos'              => 'right',
				'noptinButtonLabel'     => __( 'Subscribe', 'newsletter-optin-box' ),
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#313131',
				'noptinButtonColor'     => '#ffffff',
				'prefix'                => '',
				'hideTitle'             => false,
				'title'                 => 'GET FREE UPDATES',
				'titleColor'            => '#ffffff',
				'hideDescription'       => false,
				'description'           => __( 'This template uses custom CSS to set a different background for the footer', 'newsletter-optin-box' ),
				'descriptionColor'      => '#ffffff',
				'hideNote'              => true,
				'hideOnNoteClick'       => false,
				'note'                  => __( 'By subscribing, you agree with our <a href="">privacy policy</a> and our terms of service.', 'newsletter-optin-box' ),
				'noteColor'             => '#9E9E9E',
				'titleTypography'       => array(
					'font_size'   => '46',
					'font_weight' => '700',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 46px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'CSS'                   => '.noptin-optin-form-wrapper .noptin-optin-form .noptin-form-footer {
	background-color: aliceblue;
}',
			),

		),

		'minimal21565701726'     => array(
			'title' => __( 'Minimal', 'newsletter-optin-box' ),
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '1px',
				'formWidth'             => '400px',
				'formHeight'            => '180px',
				'noptinFormBg'          => '#263238',
				'noptinFormBgImg'       => '',
				'fields'                => array(
					array(
						'type'    => array(
							'label' => __( 'Enter your email here', 'newsletter-optin-box' ),
							'name'  => 'email',
							'type'  => 'email',
						),

						'require' => true,
						'key'     => 'noptin_email_key',
					),

				),

				'imageMain'             => false,
				'noptinFormBorderColor' => '#263238',
				'image'                 => '',
				'imagePos'              => 'right',
				'noptinButtonLabel'     => __( 'Subscribe', 'newsletter-optin-box' ),
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#607d8b',
				'noptinButtonColor'     => '#ffffff',
				'prefix'                => '',
				'hideTitle'             => true,
				'title'                 => __( 'Free Updates', 'newsletter-optin-box' ),
				'titleColor'            => '#607d8b',
				'hideDescription'       => true,
				'description'           => __( 'Get free notifications whenever we publish new content.', 'newsletter-optin-box' ),
				'descriptionColor'      => '#607d8b',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => __( 'By subscribing, you agree with our <a href="">privacy policy</a> and our terms of service.', 'newsletter-optin-box' ),
				'noteColor'             => '#607d8b',
				'titleTypography'       => array(
					'font_size'   => '20',
					'font_weight' => '700',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 20px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'CSS'                   => '.noptin-optin-form-wrapper *{}',
			),

		),

		'upgrade21565701726'     => array(
			'title' => __( 'Content Upgrades', 'newsletter-optin-box' ),
			'data'  => array(
				'singleLine'            => true,
				'formRadius'            => '40px',
				'formWidth'             => '520px',
				'formHeight'            => '280px',
				'noptinFormBg'          => '#ffffff',
				'noptinFormBgImg'       => '',
				'fields'                => array(
					array(
						'type'    => array(
							'label' => __( 'Your Email Address', 'newsletter-optin-box' ),
							'name'  => 'email',
							'type'  => 'email',
						),

						'require' => true,
						'key'     => 'noptin_email_key',
					),

				),

				'imageMain'             => false,
				'noptinFormBorderColor' => '#4caf50',
				'image'                 => noptin()->plugin_url . 'includes/assets/images/mail-icon-alt.png',
				'imagePos'              => 'top',
				'noptinButtonLabel'     => __( 'Subscribe', 'newsletter-optin-box' ),
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#4caf50',
				'noptinButtonColor'     => '#ffffff',
				'prefix'                => '',
				'hideTitle'             => false,
				'title'                 => __( "The Marketer's Workbook", 'newsletter-optin-box' ),
				'titleColor'            => '#191919',
				'hideDescription'       => false,
				'description'           => __( 'Enter your email below to receive my best selling marketing ebook.', 'newsletter-optin-box' ),
				'descriptionColor'      => '#666666',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => __( 'By subscribing, you agree with our <a href="">privacy policy</a> and our terms of service.', 'newsletter-optin-box' ),
				'noteColor'             => '#607D8B',
				'titleTypography'       => array(
					'font_size'   => '20',
					'font_weight' => '700',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 20px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '16',
					'font_weight' => '500',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 16px; font-weight: 500; line-height: 1.5;',
				),
				'CSS'                   => '.noptin-optin-form-wrapper *{}',
			),

		),

		'spinvilla21565701726'   => array(
			'title' => __( 'Large Fields', 'newsletter-optin-box' ),
			'data'  => array(
				'singleLine'            => false,
				'formRadius'            => '0',
				'formWidth'             => '520px',
				'formHeight'            => '280px',
				'noptinFormBg'          => '#f0c97b',
				'noptinFormBgImg'       => '',
				'fields'                => array(
					array(
						'type'    => array(
							'label' => __( 'First Name', 'newsletter-optin-box' ),
							'name'  => 'first_name',
							'type'  => 'first_name',
						),

						'require' => false,
						'key'     => 'noptin_first_name',
					),
					array(
						'type'    => array(
							'label' => __( 'Last Name', 'newsletter-optin-box' ),
							'name'  => 'last_name',
							'type'  => 'last_name',
						),

						'require' => false,
						'key'     => 'noptin_last_name',
					),
					array(
						'type'    => array(
							'label' => __( 'Email Address', 'newsletter-optin-box' ),
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
				'image'                 => '',
				'imagePos'              => 'right',
				'noptinButtonLabel'     => __( 'Subscribe Now', 'newsletter-optin-box' ),
				'buttonPosition'        => 'block',
				'noptinButtonBg'        => '#313131',
				'noptinButtonColor'     => '#ffffff',
				'prefix'                => '',
				'hideTitle'             => false,
				'title'                 => __( 'Email Exclusive Offers!', 'newsletter-optin-box' ),
				'titleColor'            => '#576673',
				'hideDescription'       => false,
				'description'           => __( 'Sign up and we will instantly send you our best exclusive bonuses.', 'newsletter-optin-box' ),
				'descriptionColor'      => '#576673',
				'hideNote'              => false,
				'hideOnNoteClick'       => false,
				'note'                  => __( 'By subscribing, you agree with our <a href="">privacy policy</a> and our terms of service.', 'newsletter-optin-box' ),
				'noteColor'             => '#576673',
				'titleTypography'       => array(
					'font_size'   => '40',
					'font_weight' => '700',
					'line_height' => '1.5',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 40px; font-weight: 700; line-height: 1.5;',
				),
				'descriptionTypography' => array(
					'font_size'   => '19',
					'font_weight' => '500',
					'line_height' => '1.3',
					'decoration'  => '',
					'style'       => '',
					'generated'   => 'font-size: 19px; font-weight: 500; line-height: 1.3;',
				),
				'descriptionAdvanced'   => array(
					'padding'   => array(
						'left'  => '50',
						'right' => '50',
					),
					'margin'    => array(
						'top' => '18',
					),
					'generated' => 'margin-top: 18px;padding-right: 50px;padding-left: 50px;',
					'classes'   => '',
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
	);
