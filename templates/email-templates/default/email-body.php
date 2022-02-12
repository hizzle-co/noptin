<?php
/**
 * Displays the body for the fallback email template.
 *
 * Override this template by copying it to yourtheme/noptin/email-templates/default/email-body.php
 *
 * @var string $content
 */

if ( ! defined( 'ABSPATH' ) ) exit;

echo wp_kses_post( $content );
