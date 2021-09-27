<?php
/**
 * Forms API: Form editor
 *
 * Displays the form editor
 *
 * @since             1.6.0
 * @package           Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$tabs = array(
	'form'       => __( 'Form', 'newsletter-optin-box' ),
	'messages'   => __( 'Messages', 'newsletter-optin-box' ),
	'settings'   => __( 'Settings', 'newsletter-optin-box' ),
	'appearance' => __( 'Appearance', 'newsletter-optin-box' ),
	'email'      => __( 'Email', 'newsletter-optin-box' ),
);

$tabs = apply_filters( 'noptin_form_editor_tabs', $tabs );

?>

<div class="wrap noptin-form-editor">

	<h1 class="wp-heading-inline">
		<span><?php echo empty( $post ) ? __( 'New Form', 'newsletter-optin-box' ) : __( 'Edit Form', 'newsletter-optin-box' ); ?></span>
	</h1>

	<nav class="nav-tab-wrapper" style="margin-bottom: 20px; margin-top: 20px; ">

		<?php

			foreach ( $tabs as $key => $label ) :

				$id    = esc_attr( $id );
				$label = esc_html( $label );

				printf(
					'<a href="%s" class="nav-tab %s noptin-subscriber-tab-%s">%s</a>',
					esc_url( $url ),
					( ! empty( $_GET[ $id ] ) ) ? 'nav-tab-active' : '',
					esc_attr( $id ),
					$label
				);

			endforeach;

		?>

	</nav>

	<form method="post" action="<?php echo esc_url( add_query_arg( array() ) ); ?>" id="noptin-form-editor-form">
		<?php wp_nonce_field( 'noptin-save-form-' . $post_id ); ?>
		<input type="hidden" id="post_ID" name="post_ID" value="<?php echo esc_attr( $post_id ); ?>" />
		<input type="hidden" id="hiddenaction" name="action" value="save" />

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">

				<div id="post-body-content">
					<div id="titlediv">
						<div id="titlewrap">
							<label class="screen-reader-text" id="title-prompt-text" for="title"><?php echo esc_html( __( 'Enter title here', 'newsletter-optin-box' ) ); ?></label>
							<?php
								$posttitle_atts = array(
									'type'         => 'text',
									'name'         => 'post_title',
									'size'         => 30,
									'value'        => empty( $post ) ? __( 'Newsletter Form', 'newsletter-optin-box' ) : $post->post_title,
									'id'           => 'title',
									'spellcheck'   => 'true',
									'autocomplete' => 'off',
								);

								echo sprintf( '<input %s />', noptin_attr( 'form-editor-title', $posttitle_atts ) );
							?>
						</div><!-- #titlewrap -->

						<div class="inside">
							<?php if ( ! empty( $post ) ) : ?>
								<p class="description">
									<label for="noptin-shortcode"><?php echo esc_html( __( 'Copy this shortcode and paste it into your post, page, or text widget content:', 'newsletter-optin-box' ) ); ?></label>
									<span class="shortcode wp-ui-highlight"><input type="text" id="noptin-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value="[noptin-form id=<?php echo esc_attr( $post->ID ); ?>]" /></span>
								</p>
							<?php endif; ?>
						</div>
					</div><!-- #titlediv -->
				</div><!-- #post-body-content -->

				<div id="postbox-container-1" class="postbox-container">
					<div id="submitdiv" class="postbox">
						<div class="inside">
							<div class="submitbox" id="submitpost">
								<div id="minor-publishing-actions">
									<div class="hidden">
										<input type="submit" class="button-primary" name="wpcf7-save" value="<?php echo esc_attr( __( 'Save', 'newsletter-optin-box' ) ); ?>" />
									</div>
								</div><!-- #minor-publishing-actions -->

								<div id="misc-publishing-actions">
									<?php do_action( 'noptin_form_editor_misc_pub_section', $post_id ); ?>
								</div><!-- #misc-publishing-actions -->

								<div id="major-publishing-actions">