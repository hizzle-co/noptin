<?php
/**
 * Displays posts in a list.
 *
 * Override this template by copying it to yourtheme/noptin/post-digests/email-posts-list.php
 *
 * @var WP_Post[] $posts
 */

if ( ! defined( 'ABSPATH' ) ) exit;

?>

<style type="text/css">
    @media only screen and (max-width: 480px){
        #noptin-posts-list {
            width:100% !important;
        }

        .noptin-posts-list-col {
            display:block !important;
            width:100% !important;
        }

        .noptin-posts-list-content {
            font-size:16px !important;
            line-height:125% !important;
			padding-left: 0 !important;
        }

    }
</style>

<?php if ( is_array( $posts ) ): ?>

	<table role="presentation" border="0" cellspacing="0" cellpadding="0" style="width: 100%;" id="noptin-posts-list"><tbody>

		<?php foreach ( $posts as $post ): ?>
			<tr>
				<td style="padding-bottom: 30px;">

					<table border="0" cellpadding="10" cellspacing="0" width="100%"><tbody>
						<tr>

							<?php if ( has_post_thumbnail( $post ) ) : ?>
        						<td align="center" valign="top" width="50%" class="noptin-posts-list-col">
            						<table border="0" cellpadding="10" cellspacing="0" width="100%"><tbody>
										<tr>
											<td class="noptin-posts-list-content">
												<a href="<?php echo esc_url( get_permalink( $post ) ); ?>">
													<img src="<?php echo esc_url( get_the_post_thumbnail_url( $post, 'medium' ) ); ?>" alt="<?php echo esc_attr( get_the_title( $post ) ); ?>" style="width: 100%; max-width: 100%; height: auto; margin: auto; display: block;">
												</a>
											</td>
                						</tr>
            						</tbody></table>
        						</td>
							<?php endif; ?>

					        <td align="center" valign="top" width="<?php echo has_post_thumbnail( $post ) ? '50%' : '100%' ?>" class="noptin-posts-list-col">
            					<table border="0" cellpadding="10" cellspacing="0" width="100%"><tbody>
									<tr>
										<td valign="top" class="noptin-posts-list-content" style="padding-left: <?php echo has_post_thumbnail( $post ) ? '20px' : '0' ?>;">

											<h2>
												<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" style="color: currentColor;">
													<?php echo wp_kses_post( get_the_title( $post ) ); ?>
												</a>
											</h2>

											<p>
												<?php echo wp_kses_post( get_the_excerpt( $post ) ); ?>
											</p>

											<p>
												[[button text="<?php esc_attr_e( 'Read more', 'newsletter-optin-box' ); ?>" url="<?php echo esc_attr( get_permalink( $post ) ); ?>"]]
											</p>
										</td>
                					</tr>
            					</tbody></table>
        					</td>
    					</tr>

					</tbody></table>

				</td>
			</tr>
		<?php endforeach; ?>

	</tbody></table>

<?php endif; ?>
