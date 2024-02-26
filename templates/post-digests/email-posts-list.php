<?php
/**
 * Displays posts in a list.
 *
 * Override this template by copying it to yourtheme/noptin/post-digests/email-posts-list.php
 *
 * @var WP_Post[] $campaign_posts
 * @var string $title
 * @var string $description
 */

defined( 'ABSPATH' ) || exit;

?>

<?php if ( ! empty( $title ) ) : ?>
	<h2 class="digest-list-title"><?php echo wp_kses_post( $title ); ?></h2>
<?php endif; ?>

<?php if ( ! empty( $description ) ) : ?>
	<p class="digest-list-description"><?php echo wp_kses_post( $description ); ?></p>
<?php endif; ?>

<?php foreach ( $campaign_posts as $i => $campaign_post ) : ?>

	<?php
		$GLOBALS['post'] = $campaign_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $campaign_post );
	?>

	<table cellspacing="0" cellpadding="0" class="digest-list-post digest-list-post-type-<?php echo esc_attr( sanitize_html_class( $campaign_post->post_type ) ); ?>">
		<tbody>
			<tr style="vertical-align:top">

				<?php if ( has_post_thumbnail( $campaign_post ) ) : ?>
					<td class="d-xs-block" width="150" style="width:150px; padding: 0;">
						<a href="<?php echo esc_url( get_permalink( $campaign_post ) ); ?>" style="display: block;" target="_blank">
							<img src="<?php echo esc_url( get_the_post_thumbnail_url( $campaign_post, 'thumbnail' ) ); ?>" alt="<?php echo esc_attr( get_the_title( $campaign_post ) ); ?>" style="width: 100%; max-width: 100%; height: auto; margin: auto; display: block;">
						</a>
					</td>
				<?php endif; ?>

				<td class="pl-xs-0 d-xs-block digest-list-post-content" style="padding-left: <?php echo has_post_thumbnail( $campaign_post ) ? '20px' : '0'; ?>;">

					<p class="digest-list-post-title">
						<a href="<?php echo esc_url( get_permalink( $campaign_post ) ); ?>" target="_blank">
							<?php echo wp_kses_post( get_the_title( $campaign_post ) ); ?>
						</a>
					</p>

					<p class="digest-list-post-excerpt">
						<?php echo wp_kses_post( noptin_get_post_excerpt( $campaign_post, 100 ) ); ?>
					</p>

					<p class="digest-list-post-meta">

						<a href="<?php echo esc_url( get_author_posts_url( $campaign_post->post_author ) ); ?>" target="_blank">
							<?php
								$user = get_userdata( $campaign_post->post_author );
								if ( $user && ! empty( $user->display_name ) ) {
									echo esc_html( $user->display_name );
								}
							?>
						</a>
						<?php

						$categories_list = get_the_category_list( ',', '', $campaign_post->ID );

						if ( $categories_list ) {
							/* translators: 1: list of categories. */
							printf( esc_html__( 'in %1$s', 'newsletter-optin-box' ), wp_kses_post( current( explode( ',', $categories_list ) ) ) );
						}

						?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>

<?php endforeach; ?>

<?php
	wp_reset_postdata();
