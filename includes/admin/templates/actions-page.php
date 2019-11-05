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


<?php
	while ( have_posts() ) :

		the_post();
		the_content();

	endwhile; // End of the loop.
?>

<?php wp_footer(); ?>

</body>
</html>
