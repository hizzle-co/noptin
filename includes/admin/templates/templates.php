<?php

	return array(
    	'owvP1565701640' => array(
            'title' => __( 'Borderless', 'noptin' ),
            'data' => array (
                'hideCloseButton'   => false,
                'closeButtonPos'    => 'top-right',
                'singleLine' 		=> true,
                'formRadius' 		=> '10px',
                'formWidth' 		=> '520px',
                'formHeight' 		=> '280px',
				'noptinFormBg' 	    => '#fff',
				'noptinFormBgImg'   => '',
				'noptinFormBgVideo' => '',
                'fields' 			=> array (
                    array(
                        'type' => array (
                            'label' => __( 'Enter your email address here', 'noptin' ),
                            'name' => 'email',
                            'type' => 'email'
						),

                        'require' => 'true',
                        'key' => 'noptin_email_key',
                    )

				),

                'imageMain' => '',
                'noptinFormBorderColor' => '#fff',
                'image' => '',
                'imagePos' => 'right',
                'noptinButtonLabel' => __( 'SUBSCRIBE NOW', 'noptin' ),
                'buttonPosition' => 'block',
                'noptinButtonBg' => '#F44336',
                'noptinButtonColor' => '#fefefe',
                'hideTitle' => false,
                'title' => __( 'Subscribe For Latest Updates', 'noptin' ),
                'titleColor' => '#191919',
                'hideDescription' => false,
                'description' => __( "We'll send you the best business news and informed analysis on what matters the most to you.", 'noptin' ),
                'descriptionColor' => '#666666',
                'hideNote' => false,
                'hideOnNoteClick' => false,
                'note' => __( 'We promise not to spam you. You can unsubscribe at any time.', 'noptin' ),
                'noteColor' => '#607D8B',
                'CSS' => ".noptin-optin-form-wrapper .noptin-form-field{
	text-align: center;
 	font-weight: 500;
}

.noptin-optin-form-wrapper .noptin-form-heading {
    font-size: 32px;
    font-style: italic;
    line-height: 1.6;
}

.noptin-optin-form-wrapper form .noptin-form-footer .noptin-form-submit{
	font-weight: 500;
}",
			),

        ),

    'IEiH1565701672' => array(
        'title' => __( 'Default', 'noptin' ),
        'data' => array(
            'hideCloseButton' => false,
            'closeButtonPos' => 'along',
            'singleLine' => true,
            'formRadius' => '10px',
            'formWidth' => '520px',
            'formHeight' => '280px',
			'noptinFormBg' => '#fafafa',
			'noptinFormBgImg' => '',
			'noptinFormBgVideo' => '',
            'fields' => array(
                array(
                    'type' => array(
                        'label' => __( 'Email Address', 'noptin' ),
                        'name' => 'email',
                        'type' => 'email',
					),

                    'require' => 'true',
                    'key' => 'noptin_email_key',
				),

            ),

            'imageMain' => false,
            'noptinFormBorderColor' => '#009688',
            'image' => 'https://github.com/hizzle-co/noptin/raw/master/includes/assets/images/email-icon.png',
            'imagePos' => 'right',
            'noptinButtonLabel' => __( 'Subscribe Now', 'noptin' ),
            'buttonPosition' => 'block',
            'noptinButtonBg' => '#009688',
            'noptinButtonColor' => '#fefefe',
            'hideTitle' => false,
            'title' => __( 'Subscribe To Our Newsletter', 'noptin' ),
            'titleColor' => '#191919',
            'hideDescription' => false,
            'description' => "Enter your email to receive a weekly round-up of our best posts. <a href='https://noptin.com/guide'>Learn more!</a>",
            'descriptionColor' => '#666666',
            'hideNote' => '1',
            'hideOnNoteClick' => false,
            'note' => __( "We don't spam people", 'noptin' ),
            'noteColor' => '#607D8B',
            'CSS' => '.noptin-optin-form-wrapper *{}'
        )

	),

    'qQOv1565701677' => array(
        'title' => __( 'Subheading', 'noptin' ),
        'data' => array(
            'hideCloseButton' => 'false',
            'closeButtonPos' => 'along',
            'singleLine' => false,
            'formRadius' => '0px',
            'formWidth' => '520px',
            'formHeight' => '280px',
			'noptinFormBg' => '#fafafa',
			'noptinFormBgImg' => '',
			'noptinFormBgVideo' => '',
            'fields' => array(
                array(
                    'type' => array(
                        'label' => __( 'Enter your name here', 'noptin' ),
                        'name' => 'name',
                        'type' => 'name'
					),

                    'require' => false,
                    'key' => 'key-fkluoh'
				),

                array(
                    'type' => array(
                        'label' => __( 'Enter your email address', 'noptin' ),
                        'name' => 'email',
                        'type' => 'email'
					),

                    'require' => true,
                    'key' => 'noptin_email_key'
                )

			),

            'imageMain' => '',
            'noptinFormBorderColor' => '#9E9E9E',
            'image' => '',
            'imagePos' => 'right',
            'noptinButtonLabel' => __( 'Subscribe Now', 'noptin' ),
            'buttonPosition' => 'block',
            'noptinButtonBg' => '#009688',
            'noptinButtonColor' => '#fefefe',
            'hideTitle' => false,
            'title' => __( "DON'T MISS OUT!", 'noptin' ),
            'titleColor' => '#009688',
            'hideDescription' => '',
            'description' => __( 'Subscribe To Our Newsletter', 'noptin' ),
            'descriptionColor' => '#666666',
            'hideNote' => false,
            'hideOnNoteClick' => false,
            'note' => __( "We don't spam people", 'noptin' ),
            'noteColor' => '#607D8B',
            'CSS' => '.noptin-optin-form-wrapper form .noptin-form-header .noptin-form-description{
	font-size: 30px;
 	padding-top: 0.217391304em;
}'

        )

	),

    'ICkq1565701695' => array(
        'title' => __( 'Border Top', 'noptin' ),
        'data' => array(
            'hideCloseButton' => false,
            'closeButtonPos'  => 'outside',
            'singleLine' 		=> true,
            'formRadius' 		=> '0px',
            'formWidth' 		=> '520px',
            'formHeight' 		=> '280px',
			'noptinFormBg' 	=> '#ffffff',
			'noptinFormBgImg' => '',
			'noptinFormBgVideo' => '',
            'fields' => array(
                array(
                    'type' => array(
                        'label' => __( 'Email Address', 'noptin' ),
                        'name' => 'email',
                        'type' => 'email',
					),

                    "require" => true,
                    "key" => "noptin_email_key"
                )

			),

            'imageMain' => '',
            'noptinFormBorderColor' => '#795548',
            'image' => '',
            'imagePos' => 'right',
            'noptinButtonLabel' => 'JOIN',
            'buttonPosition' => 'block',
            'noptinButtonBg' => '#795548',
            'noptinButtonColor' => '#fefefe',
            'hideTitle' => false,
            'title' => __( 'Join Our Newsletter', 'noptin' ),
            'titleColor' => '#191919',
            'hideDescription' => false,
            'description' => __( 'get weekly access to our best tips, tricks and updates.', 'noptin' ),
            'descriptionColor' => '#666666',
            'hideNote' => false,
            'hideOnNoteClick' => false,
            'note' => __( 'No spam. We hate it more than you do.', 'noptin' ),
            'noteColor' => '#607D8B',
            'CSS' => ".noptin-optin-form-wrapper {
	border: none;
 border-top: 4px solid;
 box-shadow: 0 2px 5px 0 rgba(0,0,0,.16), 0 2px 10px 0 rgba(0,0,0,.12);
}

.noptin-optin-form-wrapper form .noptin-form-header {
	text-align: left;
 	justify-content: left;
}"

    	)

),

    'BLyQ1565701700' => array(
        'title' => 'smart passive income',
        'data' => array(
            'hideCloseButton' => false,
            'closeButtonPos'  => 'along',
            'singleLine' 	  => true,
            'formRadius' 	  => '0px',
            'formWidth' 	  => '720px',
            'formHeight' 	  => '280px',
			'noptinFormBg'    => '#4CAF50',
			'noptinFormBgVideo' => '',
			'noptinFormBgImg' => 'https://github.com/hizzle-co/noptin/raw/master/includes/assets/images/bg1.jpg',
            'fields' => array(
                array(
                    'type' => array(
                        'label' => __( 'First Name', 'noptin' ),
                        'name' => 'first_name',
                        'type' => 'first_name',
					),

                	'require' => false,
                	'key' => 'key-ltoxdb'
            	),

                array(
                    'type' => array(
                        'label' => __( 'Email Address', 'noptin' ),
                        'name' => 'email',
                        'type' => 'email'
					),

                    'require' => true,
                	'key' => 'noptin_email_key'
                )

			),

            'imageMain' => '',
            'noptinFormBorderColor' => 'rgba(0,0,0,0)',
            'image' => '',
            'imagePos' => 'right',
            'noptinButtonLabel' => __( 'Subscribe Now', 'noptin' ),
            'buttonPosition' => 'block',
            'noptinButtonBg' => 'rgba(0,0,0,0)',
            'noptinButtonColor' => '#fefefe',
            'hideTitle' => false,
            'title' => 'WANT THE INSIDE SCOOP?',
            'titleColor' => '#f1f1f1',
            'hideDescription' => false,
            'description' => __( "Use the custom CSS panel to change the background colors", 'noptin' ),
            'descriptionColor' => '#f2f2f2',
            'hideNote' => true,
            'hideOnNoteClick' => false,
            'note' => __( "We don't spam people", 'noptin' ),
            'noteColor' => '#607D8B',
            'CSS' => ".noptin-optin-form-wrapper{}

.noptin-optin-form-wrapper form .noptin-form-footer .noptin-form-submit {
	border:2px solid hsla(0,0%,100%,.3) !important;
    border-radius: 2px !important;
}

.noptin-optin-form-wrapper form .noptin-form-footer .noptin-form-field {
 border:2px solid hsla(0,0%,100%,.3) !important;
	border-radius: 2px !important;
	background-color: rgba(245, 245, 245, 0.8) !important;
    color: #000 !important;
 text-align: center !important;
}",
        )

	),

    'yXJo1565701704' => array(
        'title' => __( 'Rounded Fields', 'noptin' ),
        'data' => array(
            'hideCloseButton' => false,
            'closeButtonPos' => 'top-right',
            'singleLine' => true,
            'formRadius' => '20px',
            'formWidth' => '520px',
            'formHeight' => '280px',
			'noptinFormBg' => '#e91e63',
			'noptinFormBgImg' => '',
			'noptinFormBgVideo' => '',
            'fields' => array(
                array(
                    'type' => array(
                        'label' => __( 'Email Address', 'noptin' ),
                        'name' => 'email',
                        'type' => 'email'
					),

                    'require' => false,
                    'key' => 'key-pyuiwgwtoi'
                )

			),

            'imageMain' => '',
            'noptinFormBorderColor' => '#E91E63',
            'image' => "https://github.com/hizzle-co/noptin/raw/master/includes/assets/images/email-icon.png",
            'imagePos' => 'left',
            'noptinButtonLabel' => __( 'Subscribe Now', 'noptin' ),
            'buttonPosition' => 'block',
            'noptinButtonBg' => '#673AB7',
            'noptinButtonColor' => '#fafafa',
            'hideTitle' => false,
            'title' => __( 'Subscribe To Our Newsletter', 'noptin' ),
            'titleColor' => '#fafafa',
            'hideDescription' => false,
            'description' => __( "Enter your email to receive a weekly round-up of our best posts. Learn more!", 'noptin' ),
            'descriptionColor' => '#fafafa',
            'hideNote' => false,
            'hideOnNoteClick' => false,
            'note' => __( "We don't spam people", 'noptin' ),
            'noteColor' => '#fafafa',
            'CSS' =>  '/*Custom css*/

.noptin-optin-form-wrapper .noptin-form-single-line .noptin-form-fields{
 	position: relative;
}

.noptin-optin-form-wrapper .noptin-form-single-line .noptin-form-submit{
 	border-radius: 30px;
    position: absolute;
    bottom: 0;
    top: 0;
    right: -3px;
}

.noptin-optin-form-wrapper .noptin-form-single-line .noptin-form-field{
 	border-radius: 30px !important;
	border: none !important;
}',
		),

    ),

    'BMDQ1565701721' => array(
        'title' => 'Backlinko',
        'data' => array(
            'hideCloseButton' => false,
            'closeButtonPos' => 'top-right',
            'singleLine' => true,
            'formRadius' => '0px',
            'formWidth' => '520px',
            'formHeight' => '280px',
			'noptinFormBg' => '#8dbf42',
			'noptinFormBgImg' => '',
			'noptinFormBgVideo' => '',
            'fields' => array(
               array(
                    'type' => array(
                        'label' => __( 'Email Address', 'noptin' ),
                        'name' => 'email',
                        'type' => 'email'
					),

                    'require' => true,
                    'key' => 'noptin_email_key'
                )

			),

            'imageMain' => false,
            'noptinFormBorderColor' => '#8dbf42',
            'image' => '',
            'imagePos' => 'right',
            'noptinButtonLabel' => __( 'Sign Up', 'noptin' ),
            'buttonPosition' => 'block',
            'noptinButtonBg' => '#ee4a03',
            'noptinButtonColor' => '#ffffff',
            'hideTitle' => false,
            'title' => __( 'Try Applying the Green Color Theme!', 'noptin' ),
            'titleColor' => '#fafafa',
            'hideDescription' => true,
            'description' => __( 'Click on the design tab to change the appearance of this form.', 'noptin' ),
            'descriptionColor' => '#fafafa',
            'hideNote' => true,
            'hideOnNoteClick' => false,
            'note' => __( 'Your privacy is our priority', 'noptin' ),
            'noteColor' => '#fafafa',
            'CSS' => ".noptin-optin-form-wrapper form.noptin-form-single-line .noptin-form-fields .noptin-form-submit{
	position: absolute;
 	right: 5px;
 	top: 6px;
 	bottom: 6px;
 	border-radius: 4px;
 	font-weight: 500;
	border: 1px solid transparent;
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
}"
        )

	),

    'r5g21565701726' => array(
        'title' => __( 'Hearts', 'noptin' ),
        'data' => array(
            'hideCloseButton' => false,
            'closeButtonPos' => 'outside',
            'singleLine' => true,
            'formRadius' => '0px',
            'formWidth' => '520px',
            'formHeight' => '250px',
			'noptinFormBg' => '#ffffff',
			'noptinFormBgImg' => '',
            'fields' => array(
                array(
                    'type' => array(
                        'label' => __( 'Email Address', 'noptin' ),
                        'name' => 'email',
                        'type' => 'email'
					),

                    'require' => true,
                    'key' => 'key-irmofawahh'
                )

			),

            'imageMain' => false,
            'noptinFormBorderColor' => '#009688',
            'image' => "https://raw.githubusercontent.com/hizzle-co/noptin/master/includes/assets/images/heart.png",
            'imagePos' => 'right',
            'noptinButtonLabel' => __( 'Yes Please!', 'noptin' ),
            'buttonPosition' => 'block',
            'noptinButtonBg' => '#191919',
            'noptinButtonColor' => '#ffffff',
            'hideTitle' => false,
            'title' => __( 'Get some love!', 'noptin' ),
            'titleColor' => '#fff',
            'hideDescription' => false,
            'description' => __( 'Use the Custom CSS tab to change the background color of this form.', 'noptin' ),
            'descriptionColor' => '#dff0fe',
            'hideNote' => true,
            'hideOnNoteClick' => false,
            'note' => __( 'Your privacy is our priority', 'noptin' ),
            'noteColor' => '#191919',
            'CSS' =>  "/*Custom css*/
.noptin-optin-form-wrapper form {
	background-color: #009688;
 padding: 10px;
 margin: 6px;
}"
        )

    )

);
