<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="http://gmpg.org/xfn/11">

<meta name="robots" content="noindex, nofollow" />

<?php wp_head(); ?>
</head>

<body <?php body_class( 'noptin-actions-page' ); ?>>


<?php do_shortcode( '[noptin_action_page]' ); ?>

<?php wp_footer(); ?>

</body>
</html>
