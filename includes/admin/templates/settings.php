<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <?php do_action( 'noptin_settings_page_top' ); ?>
    <form class="noptin-settings-tab-main-form" method="post" action="<?php echo admin_url('admin.php?page=noptin-settings') ?>">
        <table class="form-table">
            <tbody>
                <?php foreach ( Noptin_Settings::get_settings() as $id => $args ) {?>
                    <tr>
                        <th scope="row"><?php if(! empty( $args['label'] ) ) echo $args['label']; ?></th>
                        <td><?php Noptin_Settings::render_field(  $id, $args  )?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php wp_nonce_field(); ?>
        <?php submit_button(); ?>
    </form>
    <?php do_action( 'noptin_settings_page_top' ); ?>
</div>
