<?php
/**
 * Displays the header for the fallback email template.
 *
 * Override this template by copying it to yourtheme/noptin/email-templates/default/email-header.php
 *
 * @var string $email_heading
 */

if ( ! defined( 'ABSPATH' ) ) exit;

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
		<meta http-equiv="x-ua-compatible" content="ie=edge">
		<title><?php echo esc_html( $email_heading  ); ?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="robots" content="noindex, nofollow" />
		<?php include plugin_dir_path( __FILE__ ) . 'styles.php'; ?>
	</head>
	<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
		<h1><?php echo wp_kses_post( $email_heading ); ?></h1>