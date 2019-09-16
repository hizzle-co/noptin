<div class="wrap noptin-settings" id="noptin-settings-app">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <?php do_action( 'noptin_settings_page_top' ); ?>
    <form @submit.prevent="saveSettings" class="noptin-settings-tab-main-form" method="post" action="<?php echo admin_url('admin.php?page=noptin-settings') ?>">
		<nav class="nav-tab-wrapper">
			<?php foreach ( Noptin_Settings::get_sections() as $id => $title ) {?>
				<a
					href=""
					@click.prevent="currentTab='<?php echo $id ?>'"
					:class="{ 'nav-tab-active': currentTab == '<?php echo $id ?>' }"
					<?php
						if( $id == 'sender' ) {
							echo 'v-if="notify_new_post"';
						}
					?>
					class="nav-tab"><?php echo $title;?></a>
			<?php } ?>
		</nav>
		<div class="settings-body noptin-fields">
			<div class="noptin-save-saved" style="display:none"></div>
			<div class="noptin-save-error" style="display:none"></div>
			<?php foreach ( Noptin_Settings::get_settings() as $id => $args ) {?>
                <div <?php echo  Noptin_Settings::get_section_conditional( $args )?>>
					<?php Noptin_Vue::render_el(  $id, $args  )?>
				</div>
            <?php } ?>
			<?php submit_button(); ?>
		</div>
    </form>
    <?php do_action( 'noptin_settings_page_top' ); ?>
</div>
