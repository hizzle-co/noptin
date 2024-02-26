<?php
/**
 * Displays posts in a grid.
 *
 * Override this template by copying it to yourtheme/noptin/post-digests/email-posts-grid.php
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

<!--[if true]>
<table role="presentation" width="100%" style="all:unset;opacity:0;">
	<tr>
<![endif]-->

<!--[if false]></td></tr></table><![endif]-->
<div style="display:table;width:100%;max-width:100%;">
	<!--[if true]>
	<td width="50%" valign="top">
	<![endif]-->
	<!--[if !true]><!-->
	<div class="post-digest-grid-one" style="display:table-cell;vertical-align: top;width:50%;padding-right: 20px;">
	<!--<![endif]-->
		<?php foreach ( $campaign_posts as $i => $campaign_post ) : ?>
			<?php if ( noptin_is_even( $i ) ) : ?>

			<?php
				$GLOBALS['post'] = $campaign_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				setup_postdata( $campaign_post );
			?>
			<div class="digest-grid-post digest-grid-post-type-<?php echo esc_attr( sanitize_html_class( $campaign_post->post_type ) ); ?>">

				<?php if ( has_post_thumbnail( $campaign_post ) ) : ?>
					<p class="digest-grid-post-image-container">
						<a href="<?php echo esc_url( get_permalink( $campaign_post ) ); ?>" style="display: block;" target="_blank">
							<img src="<?php echo esc_url( get_the_post_thumbnail_url( $campaign_post, 'medium' ) ); ?>" alt="<?php echo esc_attr( get_the_title( $campaign_post ) ); ?>" style="width: 100%; max-width: 100%; height: auto; margin: auto; display: block;">
						</a>
					</p>
				<?php endif; ?>

				<p class="digest-grid-post-title">
					<a href="<?php echo esc_url( get_permalink( $campaign_post ) ); ?>" target="_blank">
						<?php echo wp_kses_post( get_the_title( $campaign_post ) ); ?>
					</a>
				</p>

				<p class="digest-grid-post-excerpt">
					<?php echo wp_kses_post( noptin_get_post_excerpt( $campaign_post, 100 ) ); ?>
				</p>

				<p class="digest-grid-post-meta">

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
			</div>
			<?php endif; ?>
		<?php endforeach; ?>
	<!--[if !true]><!-->
	</div>
	<!--<![endif]-->

	<!--[if true]>
    </td>
	<![endif]-->
	<!--[if true]>
	<td width="50%" valign="top">
	<![endif]-->
	<!--[if !true]><!-->
    <div class="post-digest-grid-two" style="display:table-cell;vertical-align: top;width:50%">
	<!--<![endif]-->
	<?php foreach ( $campaign_posts as $i => $campaign_post ) : ?>
		<?php if ( ! noptin_is_even( $i ) ) : ?>

		<?php
			$GLOBALS['post'] = $campaign_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			setup_postdata( $campaign_post );
		?>

		<div class="digest-grid-post digest-grid-post-type-<?php echo sanitize_html_class( $campaign_post->post_type ); ?>">

			<?php if ( has_post_thumbnail( $campaign_post ) ) : ?>
				<p class="digest-grid-post-image-container">
					<a href="<?php echo esc_url( get_permalink( $campaign_post ) ); ?>" style="display: block;" target="_blank">
						<img src="<?php echo esc_url( get_the_post_thumbnail_url( $campaign_post, 'medium' ) ); ?>" alt="<?php echo esc_attr( get_the_title( $campaign_post ) ); ?>" style="width: 100%; max-width: 100%; height: auto; margin: auto; display: block;">
					</a>
				</p>
			<?php endif; ?>

			<p class="digest-grid-post-title">
				<a href="<?php echo esc_url( get_permalink( $campaign_post ) ); ?>" target="_blank">
					<?php echo wp_kses_post( get_the_title( $campaign_post ) ); ?>
				</a>
			</p>

			<p class="digest-grid-post-excerpt">
				<?php echo wp_kses_post( noptin_get_post_excerpt( $campaign_post, 100 ) ); ?>
			</p>

			<p class="digest-grid-post-meta">

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
		</div>
		<?php endif; ?>
	<?php endforeach; ?>
	<!--[if !true]><!-->
	</div>
	<!--<![endif]-->
	<!--[if true]>
	</td>
	<![endif]-->
</div>
<!--[if true]>
	</tr>
</table>
<![endif]-->

<?php
	wp_reset_postdata();
