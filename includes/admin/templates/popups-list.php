<?php
/**
 * View popups
 *
 *
 * @since             1.0.0
 *
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    die;
}

$link = esc_url( add_query_arg( 'action', 'new' ) );

?>
<div class="noptin-popup-designer noptin-list">
    <div class="noptin-list-inner">
    <header class="noptin-list-header">
        <div class="noptin-list-action">
            <a href="<?php echo $link; ?>" class="noptin-add-button"><?php _e( 'Add New Popup', 'noptin'); ?></a>
        </div>
        <div class="noptin-list-filter">
            <span class="dashicons dashicons-search"></span>
            <input type="search" placeholder="<?php _e( 'Search Forms', 'noptin' )?>">
        </div>
    </header>
    <main class="content">
        <table class="noptin-list-table">
            <thead>
                <tr>
                    <th><?php _e( 'Title', 'noptin' )?></th>
                    <th><?php _e( 'Status', 'noptin' )?></th>
                    <th><?php _e( 'Date Created', 'noptin' )?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    foreach( $popups as $popup ){
                        $status         = ( 'draft' == $popup->post_status ) ? __('Inactive', 'noptin') : __('Active', 'noptin');
                        $url            = esc_url( admin_url( 'admin.php?page=noptin-pop-ups&popup_id=' ) . $popup->ID );
                        $delete         = esc_url( admin_url( 'admin.php?page=noptin-pop-ups&action=delete&delete=' ) . $popup->ID );
                        
                        printf(
                            '<tr><td><a title="%s" href="%s">%s</a><div class="noptin-form-actions"><span>%s | </span><span>%s</span></div></td><td  class="status-%s">%s</td><td>%s</td></tr>',
                            esc_attr( __('Click To Edit Pop-up ', 'noptin') . $popup->post_title ),
                            $url,
                            esc_html( $popup->post_title ),
                            "<a onClick=\"return confirm('This will permanently delete the form. Are you sure?')\" href='$delete' class='noptin-delete'>Delete</a>",
                            "<a href='$url'>Edit</a>",
                            $status,
                            $status,
                            $popup->post_date
                        );

                    }
            
                ?>
            </tbody>
        </table>
    </main>
</div>
</div>