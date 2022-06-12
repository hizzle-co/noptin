<!DOCTYPE html>
<html <?php language_attributes(); ?>>

    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="profile" href="http://gmpg.org/xfn/11">
        <meta name="robots" content="noindex, nofollow" />
        <title><?php esc_html_e( 'Noptin Newsletter', 'newsletter-optin-box' ); ?></title>
        <style>
            .noptin-actions-page {
                background: #f5f5f5;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                overflow: auto;
            }

            .noptin-actions-page-inner {
                width: 90%;
                max-width: 500px;
                background: #fff;
                padding: 20px;
                border: 2px solid #616161;
                border-radius: 4px;
                font-size: 16px;
                margin-top: 20px;
                margin-bottom: 20px;
                word-wrap: break-word;
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

            .noptin-actions-page-inner * {
                word-wrap: break-word;
            }

            .noptin-text {
                display: block;
                width: 100%;
                height: calc(1.6em + .9rem + 2px);
                padding: .45rem 1.2rem;
                font-size: 1rem;
                font-weight: 300;
                line-height: 1.6;
                color: #495057;
                background-color: #fff;
                background-clip: padding-box;
                border: 1px solid #ced4da;
                border-radius: .25rem;
                box-sizing: border-box;
                transition: border-color 0.15s ease-in-out,box-shadow 0.15s ease-in-out
            }

            @media (prefers-reduced-motion: reduce) {
                .noptin-text {
                    transition: none
                }
            }

            .noptin-text::-ms-expand {
                background-color: transparent;
                border: 0
            }

            .noptin-text:-moz-focusring {
                color: transparent;
                text-shadow: 0 0 0 #495057
            }

            .noptin-text:focus {
                color: #495057;
                background-color: #fff;
                border-color: #73b1e9;
                outline: 0;
                box-shadow: 0 0 0 .2rem rgba(30,115,190,0.25)
            }

            .noptin-text::placeholder {
                color: #6c757d;
                opacity: 1
            }

            .noptin-text:disabled,.noptin-text[readonly] {
                background-color: #e9ecef;
                opacity: 1
            }

            .noptin-text:focus::-ms-value {
                color: #495057;
                background-color: #fff
            }

            .noptin-label {
                padding-top: calc(.45rem + 1px);
                padding-bottom: calc(.45rem + 1px);
                margin-bottom: 0;
                font-size: inherit;
                line-height: 1.6
            }

            textarea.noptin-text {
                height: auto
            }

            .noptin-form-text {
                display: block;
                margin-top: .25rem
            }

        </style>
    </head>

    <body class='noptin-actions-page'>
        <div class="noptin-actions-page-inner">
            <?php echo do_shortcode( '[noptin_action_page]' ); ?>
        </div>
    </body>
</html>
