<div class="nav-tab-wrapper noptin-nav-tab-wrapper">

<?php

	foreach ( $tabs as $key => $label ) {

		$url = esc_url(
			add_query_arg(
				array(
					'page'    => 'noptin-email-campaigns',
					'section' => $key,
				),
				admin_url( '/admin.php' )
			)
		);

		$class = 'nav-tab';

		if ( $tab === $key ) {
			$class = 'nav-tab nav-tab-active';
		}

		echo "<a href='$url' class='$class'>$label</a>";
	}

	echo '</div>';
