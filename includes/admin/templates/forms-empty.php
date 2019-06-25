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
<div class="noptin-popup-designer noptin-list not-found">
    <div class="noptin-list-inner-not-found">
        <p><?php _e( 'No email opt-in forms found. Why not create one?', 'noptin'); ?></p>
        <div><a href="<?php echo $link; ?>" class="noptin-add-button"><?php _e( 'Create New Form', 'noptin'); ?></a></div>
    </div>
</div>