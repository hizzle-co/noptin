<?php
/**
 * Displays the header for the default email template.
 *
 * Override this template by copying it to yourtheme/noptin/email-templates/default/email-header.php
 *
 * @var string $email_heading
 */

defined( 'ABSPATH' ) || exit;

?>
<h1><?php echo wp_kses_post( $email_heading ); ?></h1>
