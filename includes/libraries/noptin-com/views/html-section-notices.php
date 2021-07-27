<?php defined( 'ABSPATH' ) or exit(); ?>

<?php foreach ( $notices as $notice ) : ?>
	<div class="noptin-notice notice <?php echo sanitize_html_class( $notice['type'] ); ?>">
		<?php echo wpautop( $notice['message'] ); ?>
	</div>
<?php endforeach; ?>
