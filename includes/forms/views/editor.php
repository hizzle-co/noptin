<?php
/**
 * Forms API: Displays the newsletter form editor.
 *
 * Displays the newsletter form editor
 *
 * @var Noptin_Form $form
 * @since   1.6.2
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Setting tabs.
$tabs = array(
	'form'       => __( 'Form', 'newsletter-optin-box' ),
	'messages'   => __( 'Messages', 'newsletter-optin-box' ),
	'settings'   => __( 'Settings', 'newsletter-optin-box' ),
	'email'      => __( 'Welcome Email', 'newsletter-optin-box' ),
);

$tabs = apply_filters( 'noptin_form_editor_tabs', $tabs );
$tab  = isset( $_GET['tab'] )  && array_key_exists( $_GET['tab'], $tabs ) ? noptin_clean( $_GET['tab'] ) : 'form';

add_thickbox();

?>

<div class="wrap noptin-form-editor">

	<h1 class="wp-heading-inline">
		<span><?php echo ! $form->exists() ? __( 'New Form', 'newsletter-optin-box' ) : __( 'Edit Form', 'newsletter-optin-box' ); ?></span>
	</h1>

	<form method="post" id="noptin-form-editor-app">
		<?php wp_nonce_field( 'noptin-save-form', 'noptin-save-form-nonce' ); ?>
		<input type="hidden" name="noptin_admin_action" value="noptin_editor_save_form">

		<?php if ( $form->exists() ) : ?>
			<input type="hidden" name="noptin_form[id]" value="<?php echo intval( $form->id ); ?>" />
		<?php endif; ?>

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">

				<div id="post-body-content">
					<div id="titlediv">
						<div id="titlewrap">
							<label class="screen-reader-text" id="title-prompt-text" for="title"><?php echo esc_html( __( 'Enter form name', 'newsletter-optin-box' ) ); ?></label>
							<?php
								$posttitle_atts = array(
									'type'         => 'text',
									'name'         => 'noptin_form[title]',
									'size'         => 30,
									'value'        => ! $form->exists() ? __( 'Newsletter Form', 'newsletter-optin-box' ) : $form->title,
									'placeholder'  => __( 'Enter form name', 'newsletter-optin-box' ),
									'id'           => 'title',
									'spellcheck'   => 'true',
									'autocomplete' => 'off',
								);

								echo sprintf( '<input %s />', noptin_attr( 'form-editor-title', $posttitle_atts ) );
							?>
						</div><!-- #titlewrap -->

						<div class="inside">
							<?php if ( $form->exists() ) : ?>
								<p class="description">
									<label for="noptin-shortcode"><?php echo esc_html( __( 'Copy this shortcode and paste it into your post, page, or text widget content:', 'newsletter-optin-box' ) ); ?></label>
									<span class="shortcode wp-ui-highlight"><input type="text" id="noptin-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value="[noptin form=<?php echo intval( $form->id ); ?>]" /></span>
								</p>
							<?php endif; ?>
						</div>

					</div><!-- #titlediv -->
				</div><!-- #post-body-content -->

				<div id="postbox-container-1" class="postbox-container">

					<div id="informationdiv" class="postbox">
						<h3><?php esc_html_e( 'Do you need help?', 'newsletter-optin-box' ); ?></h3>
						<div class="inside">
							<p><?php esc_html_e( 'Here are some available options to help solve your problems.', 'newsletter-optin-box' ); ?></p>
							<ol>

								<li><?php
									printf(
										/* translators: 1: FAQ, 2: Docs ("FAQ & Docs") */
										__( '%1$s and %2$s', 'newsletter-optin-box' ),
										sprintf(
											'<a href="https://contactform7.com/faq/">%s</a>',
											__( 'FAQ', 'newsletter-optin-box' )
										),
										sprintf(
											'<a href="https://contactform7.com/docs/">%s</a>',
											__( 'docs', 'newsletter-optin-box' )
										)
									);
								?></li>

								<li><?php
									printf(
										'<a href="https://wordpress.org/support/plugin/newsletter-optin-box/">%s</a>',
										__( 'Support forums', 'newsletter-optin-box' )
									);
								?></li>

							</ol>
						</div>
					</div><!-- #informationdiv -->

					<?php do_action( 'noptin_form_editor_side_metabox', $form ); ?>

				</div><!-- #postbox-container-1 -->

				<div id="postbox-container-2" class="postbox-container">
					<div id="noptin-form-editor-container">

						<nav class="nav-tab-wrapper" id="noptin-form-editor-nav-tab-wrapper" style="margin-bottom: 20px; margin-top: 20px; ">

							<?php

								foreach ( $tabs as $id => $label ) :

									printf(
										'<a href="%s" data-id="%s" class="nav-tab %s noptin-form-tab-%s">%s</a>',
										esc_url( add_query_arg( 'tab', $id ) ),
										esc_attr( $id ),
										$tab == $id ? 'nav-tab-active' : '',
										esc_attr( $id ),
										esc_html( $label )
									);

								endforeach;

							?>

						</nav>

						<?php

							foreach ( array_keys( $tabs ) as $id ) :

								printf(
									'<div data-id="%s" class="noptin-form-tab-content noptin-form-tab-content-%s %s">',
									esc_attr( $id ),
									esc_attr( $id ),
									$tab == $id ? 'noptin-form-tab-content-active' : ''
								);

								if ( file_exists( plugin_dir_path( __FILE__ ) . "tab-$id.php" ) ) {
									include plugin_dir_path( __FILE__ ) . "tab-$id.php";
								}

								do_action( "noptin_form_editor_tab_$id", $form );

								echo '</div>';
							endforeach;

						?>

					</div><!-- #noptin-form-editor-container -->

					<p class="submit">
						<input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Form', 'newsletter-optin-box' ); ?>" />&nbsp;
						<a href="#TB_inline?width=0&height=550&inlineId=noptin-form-variables" class="thickbox button-secondary">
							<span class="dashicons dashicons-info" style="vertical-align: middle;"></span>
							<?php esc_html_e( 'View available smart tags', 'newsletter-optin-box' ); ?>
						</a>
					</p>

				</div><!-- #postbox-container-2 -->

			</div><!-- #post-body -->

			<br class="clear" />
		</div><!-- #poststuff -->

	</form

</div><!-- .wrap -->

<?php // Content for Thickboxes ?>
<div id="noptin-form-variables" style="display: none;">
	<?php include plugin_dir_path( __FILE__ ) . 'dynamic-content-tags.php'; ?>
</div>
