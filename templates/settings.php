<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap noptin-settings" id="noptin-settings-app">

	<?php

		// Display the title.
		echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';

		// Fire a hook before printing the settings page.
		do_action( 'noptin_settings_page_top' );

	?>

	<form @submit.prevent="saveSettings" class="noptin-settings-tab-main-form" novalidate>

		<nav class="nav-tab-wrapper">
			<?php

				foreach ( Noptin_Settings::get_sections() as $section_id => $section_title ) :

					// For those sections that have sub-sections.
					if ( is_array( $section_title ) ) {
						$section_title = $section_title['label'];
					}

					printf(
						'<a href="#" :class="tabClass(\'%s\')" @click.prevent="switchTab(\'%s\')">%s</a>\n\t\t\t',
						esc_attr( $section_id ),
						esc_attr( $section_id ),
						esc_html( $section_title )
					);

				endforeach;
			?>
		</nav>

		<div class="noptin-sections-wrapper">

			<?php

				foreach ( Noptin_Settings::get_sections() as $key => $section ) :

					// Abort if it doesn't have sub-sections.
					if ( ! is_array( $section ) ) {
						continue;
					}

					$key         = esc_attr( $key );
					$subsections = array();

					foreach ( $section['children'] as $subsection_id => $subsection_title ) {
						$subsection_title = esc_html( $subsection_title );
						$subsection_id    = esc_attr( $subsection_id );
						$subsections[]    = "<a href='#' :class=\"sectionClass('$subsection_id')\" @click.prevent=\"switchSection('$subsection_id')\">$subsection_title</a>";
					}

					echo "<ul class='subsubsub' v-show=\"currentTab=='" . esc_attr( $key ) . "'\">\n\t<li>";
					echo join( " | </li>\n\t<li>", $subsections );
					echo "</li>\n</ul>\n";

				endforeach;

			?>

		</div>

		<div class="settings-body noptin-fields">

			<div class="noptin-save-saved" style="display:none"></div>
			<div class="noptin-save-error" style="display:none"></div>

			<?php

				foreach ( Noptin_Settings::get_settings() as $id => $args ) :
					$condition = Noptin_Settings::get_section_conditional( $args );

					echo "<div $condition>";

					if ( ! empty( $args['el'] ) && 'settings_section' === $args['el'] ) {
						$class = empty( $args['class'] ) ? '' : esc_attr( $args['class'] );
						echo "<div id='noptin-settings-section-$id' class='noptin-settings-section $class' :class=\"{ open: isOpenPanel('$id') }\">";
						echo    "<div class='noptin-section-header' @click=\"togglePanel('$id')\">
									<div class='title'>
										<span>{$args['heading']}</span>
										<p>{$args['description']}</p>
									</div>
									<span class='badge'>{$args['badge']}</span>
									<span class='icon'></span>
								</div>";
						echo "<div class='noptin-section-body'>";
						foreach ( $args['children'] as $id => $args ) :
							$condition = Noptin_Settings::get_section_conditional( $args );

							echo "<div $condition>";
							Noptin_Vue::render_el( $id, $args );
							echo '</div>';

						endforeach;
						echo '</div>';
						echo '</div>';

					} else {
						Noptin_Vue::render_el( $id, $args );
					}

					echo '</div>';

				endforeach;
				submit_button();

			?>
		</div>


	</form>
	<?php do_action( 'noptin_settings_page_bottom' ); ?>
</div>
