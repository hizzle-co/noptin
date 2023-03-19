<?php
defined( 'ABSPATH' ) || exit;

// fake post to prevent notices in wp_enqueue_scripts call
$GLOBALS['post'] = new WP_Post( (object) array( 'filter' => 'raw' ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

// render simple page with form in it.
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<meta name="robots" content="noindex, nofollow" />
	<title><?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>
	<?php
		wp_enqueue_scripts();
		wp_print_styles();
		wp_print_head_scripts();
		wp_custom_css_cb();
		wp_site_icon();
	?>
	<style type="text/css">
		body{ 
			background: white;
			width: 100%;
			max-width: 100%;
			text-align: left;
		}

		html, body, #page, #content {
			padding: 0 !important;
			margin: 0 !important;
		}

		/* hide all other elements */
		body::before,
		body::after,
		body > *:not(#noptin-form-preview) { 
			display:none !important; 
		}

		#noptin-form-preview {
			display: block !important;
			width: 100%;
			height: 100%;
			padding: 20px;
			border: 0;
			margin: 0;
			box-sizing: border-box;
		}

		#noptin-form-preview p.description{
			font-size: 14px;
			margin: 2px 0 5px;
			color: #646970;
		}
	</style>
</head>
<body class="page-template-default page">
	<div id="noptin-form-preview" class="page type-page status-publish hentry post post-content">
		<p class="description"><?php esc_html_e( 'The form may look slightly different than this when shown in a post, page or widget area.', 'newsletter-optin-box' ); ?></p>
		<?php show_noptin_form( $form_id ); ?>
	</div>
	<?php wp_footer(); ?>
</body>
</html>
