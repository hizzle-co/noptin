<?php

	// Load css styles.
	include plugin_dir_path( __FILE__ ) . 'styles.php';

	// Load the tracker.
	echo $tracker; 

	// Display the hero text.
	if ( ! empty( $hero_text ) ) {
		echo "<h1>$hero_text</h1>";
	}

	// Display the email content.
	echo wpautop( $email_body );

	// Display the call to action button.
	if ( ! empty( $cta_url ) ) {
		include plugin_dir_path( __FILE__ ) . 'button.php';
	}

	// After CTA text.
	if ( ! empty( $after_cta_text ) ) {
		echo wpautop( $after_cta_text );
	}

	// After CTA text2.
	if ( ! empty( $after_cta_text2 ) ) {
		echo wpautop( $after_cta_text2 );
	}

	echo "<p>&nbsp;</p> <p>&nbsp;</p>";

	// Permission text.
	if ( ! empty( $permission_text ) ) {

		if ( class_exists( 'Email_Customizer_Mailer' ) ) {
			Email_Customizer_Mailer::$custom_footer_1 = $permission_text;
		} else {
			echo $permission_text;
		}

	}

	// Footer text.
	if ( ! empty( $footer_text ) ) {

		if ( class_exists( 'Email_Customizer_Mailer' ) ) {
			Email_Customizer_Mailer::$custom_footer_2 = $footer_text;
		} else {
			echo $footer_text;
		}

	}
