<?php
/**
 * Displays the footer for the fallback email template.
 *
 * Override this template by copying it to yourtheme/noptin/email-templates/none/email-footer.php
 *
 * @var string $footer
 */

if ( ! defined( 'ABSPATH' ) ) exit;

?>

        <div class="noptin-no-template-email-footer" style="color: #757575; font-size: 12px;">
            <br><span>&ndash;</span><br>
            <?php echo wp_kses_post( $footer ); ?>
        </div>

        </div>
    </body>
</html>
