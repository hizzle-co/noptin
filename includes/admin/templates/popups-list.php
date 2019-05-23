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
        <div class="noptin-list-filter"><input type="search" placeholder="<?php _e( 'Search Forms', 'noptin' )?>"><div>
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
                        $title  = esc_html( $popup->post_title );
                        $status = ( 'draft' == $popup->post_status ) ? __('Inactive', 'noptin') : __('Active', 'noptin');
                        $date   = esc_html( $popup->post_date );
                        $url    = esc_url( admin_url( 'admin.php?page=noptin-pop-ups&popup_id=' ) . $popup->ID );

                        echo "<tr><td><a href='$url'>$title</td><td class='status-$status'>$status</td><td>$date</td></tr>";
                    }
            
                ?>
            </tbody>
        </table>
    </main>
</div>
</div>