<!DOCTYPE html>
<html <?php language_attributes(); ?>>

    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="profile" href="http://gmpg.org/xfn/11">
        <meta name="robots" content="noindex, nofollow" />
        <title><?php echo esc_html( $action_label ); ?> - <?php esc_html_e( 'Noptin Newsletter', 'newsletter-optin-box' ); ?></title>
        <?php $colors = noptin_get_color_scheme(); ?>
        <style>
            * {
			    margin: 0;
				padding: 0;
				box-sizing: border-box;
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
				text-align: center;
			}

			.icon {
				width: 64px;
				height: 64px;
				background: <?php echo esc_attr( $colors['primary'] ); ?>;
				border-radius: 50%;
				display: flex;
				align-items: center;
				justify-content: center;
				margin: 0 auto 24px;
				color: white;
				font-size: 32px;
			}

			h1 {
				color: #1a202c;
				font-size: 24px;
				font-weight: 600;
				margin-bottom: 12px;
			}

			p {
				color: #4a5568;
				font-size: 16px;
				line-height: 1.6;
				margin-bottom: 32px;
			}

            button {
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

            button:hover {
				background: <?php echo esc_attr( $colors['primary_dark'] ); ?>;
				transform: translateY(-2px);
				box-shadow: 0 4px 12px <?php echo esc_attr( $colors['shadow_rgba'] ); ?>;
			}

            button:active {
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
		    <div class="icon">✓</div>
			<h1><?php echo esc_html( $action_label ); ?></h1>
			<p><?php echo esc_html( $action_description ); ?></p>
			<form id="noptin-autosubmit-form" method="post" action="<?php echo esc_url( add_query_arg( 'noptin-autosubmit', '1' ) ); ?>">
				<input type="hidden" name="noptin-autosubmit" value="1">
					<button type="submit">
						<?php esc_html_e( 'Confirm', 'newsletter-optin-box' ); ?>
					</button>
				</form>
		</div>
	</body>
</html>
