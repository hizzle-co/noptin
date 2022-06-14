<?php defined( 'ABSPATH' ) || exit; ?>
<div class="nav-tab-wrapper noptin-nav-tab-wrapper">

<?php

	$email_tab = empty( $_GET['section'] ) ? 'newsletters' : $_GET['section'];  // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	foreach ( $tabs as $key => $label ) {

		printf(
			'<a href="%s" class="%s">%s</a>',
			esc_url(
				add_query_arg(
					array(
						'page'    => 'noptin-email-campaigns',
						'section' => rawurlencode( $key ),
					),
					admin_url( '/admin.php' )
				)
			),
			$email_tab === $key ? 'nav-tab nav-tab-active' : 'nav-tab',
			esc_html( $label )
		);

	}

	echo '</div>';
