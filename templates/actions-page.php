<!DOCTYPE html>
<html <?php language_attributes(); ?>>

    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="profile" href="http://gmpg.org/xfn/11">
        <meta name="robots" content="noindex, nofollow" />
        <title><?php echo __( 'Noptin Newsletter', 'newsletter-optin-box' ); ?></title>
        <style>
            .noptin-actions-page {
                background: #f5f5f5;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                overflow: auto;
            }

            .noptin-actions-page-inner {
                max-width: 400px;
                background: #fff;
                padding: 20px;
                border: 2px solid #616161;
                border-radius: 4px;
                font-size: 16px;
            }

            h1 {
                display: block;
                font-size: 2em;
                margin-block-start: 0.67em;
                margin-block-end: 0.67em;
                margin-inline-start: 0px;
                margin-inline-end: 0px;
                font-weight: bold;
            }

        </style>
    </head>

    <body class='noptin-actions-page'>
        <div class="noptin-actions-page-inner">
            <?php echo do_shortcode( '[noptin_action_page]' ); ?>
        </div>
    </body>
</html>
