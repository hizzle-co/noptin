<!DOCTYPE html>
<html <?php language_attributes(); ?>>

    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="profile" href="http://gmpg.org/xfn/11">
        <meta name="robots" content="noindex, nofollow" />
        <title><?php esc_html_e( 'Noptin Newsletter', 'newsletter-optin-box' ); ?></title>
        <?php $colors = noptin_get_color_scheme(); ?>
        <style>
            * {
			    margin: 0;
				padding: 0;
				box-sizing: border-box;
                word-wrap: break-word;
			}

			body {
				font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
				background: linear-gradient(135deg, <?php echo esc_attr( $colors['gradient_start'] ); ?> 0%, <?php echo esc_attr( $colors['gradient_end'] ); ?> 100%);
				min-height: 100vh;
				display: flex;
				align-items: center;
				justify-content: center;
				padding: 20px;
			}

            .container {
				background: white;
				border-radius: 12px;
				box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
				padding: 40px;
				max-width: 500px;
				width: 100%;
			}

            h1 {
                display: block;
                font-size: 2em;
                margin-block-start: 0;
                margin-block-end: 0.67em;
                margin-inline-start: 0px;
                margin-inline-end: 0px;
                font-weight: bold;
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

            button, .button {
				background: <?php echo esc_attr( $colors['primary'] ); ?>;
				color: white;
				border: none;
				border-radius: 8px;
				padding: 14px 32px;
				font-size: 16px;
				font-weight: 600;
				cursor: pointer;
				transition: all 0.3s ease;
				width: 100%;
				max-width: 200px;
			}

            button:hover, .button:hover {
				background: <?php echo esc_attr( $colors['primary_dark'] ); ?>;
				transform: translateY(-2px);
				box-shadow: 0 4px 12px <?php echo esc_attr( $colors['shadow_rgba'] ); ?>;
			}

            button:active, .button:hover {
				transform: translateY(0);
			}

            @media (max-width: 480px) {
				.container {
					padding: 30px 20px;
				}
				h1 {
					font-size: 20px;
				}
				p {
					font-size: 14px;
				}
			}
        </style>
    </head>

    <body>
		<div class="container">
		    <?php echo do_shortcode( '[noptin_action_page]' ); ?>
		</div>
	</body>
</html>
