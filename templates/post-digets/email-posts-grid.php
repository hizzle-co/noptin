<?php
/**
 * Displays posts in a grid.
 *
 * Override this template by copying it to yourtheme/noptin/post-digests/email-posts-grid.php
 *
 * @var WP_Post[] $posts
 */

if ( ! defined( 'ABSPATH' ) ) exit;

?>

<?php if ( is_array( $posts ) ): ?>

	<style type="text/css">

		table.noptin-posts-grid {
			width: 100%;
		}

		.noptin-posts-grid-container {
			font-size: 0px;
			margin: 10px 0 10px;
		}

		.noptin-posts-grid-item {
			width: 48%;
			display: inline-block;
			text-align:left;
			padding: 0 0 30px;
			vertical-align:top;
			word-wrap:break-word;
			margin-right: 4%;
			font-size: 14px;
		}

		table.noptin-posts-grid-item img {
			max-width: 100%;
			height: auto !important;
		}

		@media (max-width: 480px) {
			.noptin-posts-grid-item {
            	width:100% !important;
				margin-right: 0 !important;
				display: block !important;
    			text-align: left !important;
        	}
		}

	</style>

	<table cellspacing="0" cellpadding="0" class="noptin-posts-grid">
		<tbody><tr><td style="padding: 0;"><div class="noptin-posts-grid-container">

			<?php foreach ( $posts as $post ): ?>

				<div class="noptin-posts-grid-item" style="<?php echo ( $n % 2 ? '' : 'margin-right: 0;' ) ?>">

					<?php if ( has_post_thumbnail( $post ) ) : ?>
						<a href="<?php echo esc_url( get_permalink( $post ) ); ?>">
							<img src="<?php echo esc_url( get_the_post_thumbnail_url( $post, 'medium' ) ); ?>" alt="<?php echo esc_attr( get_the_title( $post ) ); ?>" style="width: 100%; max-width: 100%; height: auto; margin: auto; display: block;">
						</a>
					<?php endif; ?>

					<h3>
						<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" style="color: currentColor;"><?php echo wp_kses_post( get_the_title( $post ) ); ?></a>
					</h3>

					<p>
						<?php echo wp_kses_post( get_the_excerpt( $post ) ); ?>
					</p>

					<p>
						[[button text="<?php esc_attr_e( 'Read more', 'newsletter-optin-box' ); ?>" url="<?php echo esc_attr( get_permalink( $post ) ); ?>"]]
					</p>

				</div>

			<?php $n++; endforeach; ?>

		</div></td></tr></tbody>
	</table>

<?php endif; ?>
