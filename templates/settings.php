<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap noptin-settings" id="noptin-settings-app">
	<h1><?php esc_html( get_admin_page_title() ); ?></h1>

	<?php do_action( 'noptin_settings_page_top' ); ?>

	<form @submit.prevent="saveSettings" class="noptin-settings-tab-main-form" novalidate>

		<nav class="nav-tab-wrapper">
			<?php foreach ( Noptin_Settings::get_sections() as $section_id => $section_title ) : ?>
				<a
					href="#"
					:class="tabClass('<?php echo esc_attr( $section_id ); ?>')"
					@click.prevent="switchTab('<?php echo esc_attr( $section_id ); ?>')"
				><?php echo esc_html( is_array( $section_title ) ? $section_title['label'] : $section_title ); ?></a>
			<?php endforeach; ?>
		</nav>

		<div class="noptin-sections-wrapper">

			<?php foreach ( array_filter( Noptin_Settings::get_sections(), 'is_array' ) as $section_id => $section ) : ?>
				<ul class="subsubsub" v-show="currentTab=='<?php echo esc_attr( $section_id ); ?>'">
					<?php foreach ( $section['children'] as $subsection_id => $subsection_title ) : ?>
						<li>
							<a href='#' :class="sectionClass('<?php echo esc_attr( $subsection_id ); ?>')" @click.prevent="switchSection('<?php echo esc_attr( $subsection_id ); ?>')"><?php echo esc_html( $subsection_title ); ?></a>
							<span class="subsubsub_sep"> | </span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endforeach; ?>

		</div>

		<div class="settings-body noptin-fields">

			<div class="noptin-save-saved" style="display:none"></div>
			<div class="noptin-save-error" style="display:none"></div>

			<?php foreach ( Noptin_Settings::get_settings() as $setting_id => $args ) : ?>
				<div <?php Noptin_Settings::section_conditional( $args ); ?>>

					<?php if ( ! empty( $args['el'] ) && 'settings_section' === $args['el'] ) : ?>

						<div id="noptin-settings-section-<?php echo esc_attr( $setting_id ); ?>" class="noptin-settings-section <?php echo esc_attr( empty( $args['class'] ) ? '' : $args['class'] ); ?>" :class="{ open: isOpenPanel('<?php echo esc_attr( $setting_id ); ?>') }">
							<div class="noptin-section-header" @click="togglePanel('<?php echo esc_attr( $setting_id ); ?>')">
								<div class='title'>
									<span><?php echo wp_kses( $args['heading'], noptin_kses_post_vue() ); ?></span>
									<p><?php echo wp_kses( $args['description'], noptin_kses_post_vue() ); ?></p>
								</div>
								<span class='badge'><?php echo wp_kses( $args['badge'], noptin_kses_post_vue() ); ?></span>
								<span class='icon'></span>
							</div>

							<div class="noptin-section-body">
								<?php foreach ( $args['children'] as $child_id => $child_args ) : ?>
									<div <?php Noptin_Settings::section_conditional( $child_args ); ?>>
										<?php Noptin_Vue::render_el( $child_id, $child_args ); ?>
									</div>
								<?php endforeach; ?>
							</div>
						</div>

					<?php else : ?>
						<?php Noptin_Vue::render_el( $setting_id, $args ); ?>
					<?php endif; ?>

				</div>
			<?php endforeach; ?>

			<?php do_action( 'noptin_settings_page_before_submit_button' ); ?>
			<?php submit_button(); ?>

		</div>

	</form>
	<?php do_action( 'noptin_settings_page_bottom' ); ?>
</div>
