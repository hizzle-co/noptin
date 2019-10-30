<?php
/**
 * class Noptin_Mailer class.
 *
 */

defined( 'ABSPATH' ) || exit;

class Noptin_Mailer {

	/**
	 * Returns the email subject
	 *
	 */
	public function get_subject( $data = array() ) {

		if( empty( $data['email_subject'] ) ) {
			return '';
		}

		$subject = trim( $data['email_subject'] );

		if(! empty( $data['merge_tags'] ) ) {
			$subject = $this->merge( $subject, $data['merge_tags'] );
		}

		return $subject;

	}

	/**
	 * Returns the email body
	 *
	 */
	public function get_email( $data = array() ) {

		$content     = __( 'No content' );
		if(! empty( $data['email_body'] ) ) {
			$content = $data['email_body'];
		}

		$content = trim( $content );

		if(! empty( $data['template'] ) ) {
			$content = $this->prepare_template( $content, $data );
		}

		if( empty( $data['merge_tags'] ) ) {
			$data['merge_tags'] = array();
		}
		$content = $this->merge( $content, $data['merge_tags'] );

		return $content;

	}

	/**
	 * Attaches a template to an email
	 *
	 */
	public function prepare_template( $content, $data ) {

		//Ensure the template exists
		if(! file_exists( $data['template'] ) ) {
			return $content;
		}

		//Preview text
		$preview  = $this->get_preview_text( $data );
		$preview  = apply_filters( 'noptin_email_preview', $preview, $data );

		//Logo
		$logo  = $this->get_logo( $data );
		$logo  = apply_filters( 'noptin_email_logo', $logo, $data );

		//Content
		$main_content  = $this->get_content( $content, $data );
		$main_content  = apply_filters( 'noptin_email_content', $main_content, $content, $data );

		//Email footer
		$footer  = $this->get_default_footer( $data );
		$footer  = apply_filters( 'noptin_email_footer', $footer, $data );

		//Title
		$title = '';

		if(! empty( $data['email_subject'] ) ) {
			$title = trim( $data['email_subject'] );
		}

		//Load it
		ob_start();
		include $data['template'];
		$email_content = ob_get_clean();

		//Remove comments
		$email_content = preg_replace( "/<!--(.*)-->/Uis", '', $email_content );

		//Emogrify the email
		$noptin = noptin();
		require_once $noptin->plugin_path . 'includes/class-noptin-emogrifier.php';

		try {
			$emogrifier     = new Noptin_Emogrifier( $email_content );
			$_email_content = $emogrifier->emogrify();
			$email_content  = $_email_content;
		} catch (Exception $e) {
			$email_content = $emogrifier->emogrify();
		}

		return $email_content;
	}

	/**
	 * Retrieves the markup for the email preview text
	 *
	 */
	public function get_preview_text( $data = array() ) {

		if( empty( $data['preview_text'] ) ) {
			return '';
		}

		$preview_text = trim( $data['preview_text'] );

		ob_start();
		include get_noptin_include_dir( 'admin/templates/email-templates/preview-text.php' );
		return ob_get_clean();
	}

	/**
	 * Retrieves the content markup for the email
	 *
	 */
	public function get_content( $content, $data = array() ) {

		ob_start();
		include get_noptin_include_dir( 'admin/templates/email-templates/content.php' );
		return ob_get_clean();
	}

	/**
	 * Retrieves the markup for the email logo
	 *
	 */
	public function get_logo( $data = array() ) {

		//Default logo url
		$noptin = noptin();
		$url    = '';

		//Maybe replace it with the websites logo
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		if( $custom_logo_id ) {
			$logo_url = wp_get_attachment_image_src( $custom_logo_id );
			if( is_array( $logo_url ) && !empty( $logo_url[0] ) ) {
				$url = $logo_url[0];
			}
		}

		//Or the company logo
		$company_logo = get_noptin_option( 'company_logo', '');
		if(! empty( $company_log ) ) {
			$url = $company_logo;
		}

		$logo_url = $footer  = apply_filters( 'noptin_email_logo_url', $url, $data );

		ob_start();
		include get_noptin_include_dir( 'admin/templates/email-templates/logo.php' );
		return ob_get_clean();
	}

	/**
	 * Retrieves the default email footer
	 *
	 */
	public function get_default_footer( $data = array() ) {

		ob_start();
		include get_noptin_include_dir( 'admin/templates/email-templates/default-email-footer.php' );
		return ob_get_clean();

	}

	/**
	 * Retrieves the default merge tags
	 *
	 */
	public function get_default_merge_tags( ) {

		$default_merge_tags    = array(
			'blog_name' 	   => get_bloginfo('name'),
			'blog_description' => get_bloginfo('description'),
			'home_url'		   => get_home_url(),
			'noptin_country'   => get_noptin_option( 'country', ''),
			'noptin_state'     => get_noptin_option( 'state', ''),
			'noptin_city'      => get_noptin_option( 'city', ''),
			'noptin_address'   => get_noptin_option( 'address', ''),
			'noptin_company'   => get_noptin_option( 'company', ''),
		);
		return $default_merge_tags;

	}

	/**
	 * Merges the email body with the specified merge tags
	 *
	 */
	public function merge( $content, $tags ) {

		$tags = wp_parse_args( $this->get_default_merge_tags(), $tags );

		//Replace all available tags with their values
		foreach( $tags as $key => $value ) {
			$content = str_ireplace( "[[$key]]", $value, $content );
		}

		//Remove unavailable tags
		$content = preg_replace( "/\[\[\w+]\]/", '', $content );

		return $content;
	}

	/**
	 * Retrieves email headers
	 *
	 */
	public function get_headers() {

		$headers    = array('Content-Type: text/html; charset=UTF-8');

		$from_email = get_noptin_option( 'from_email' );
		if( is_email($from_email) ) {
			$name       = get_noptin_option( 'from_name', 'Noptin' );
			$from_email = get_noptin_option( 'from_email' );
			$headers[]  = "From:$name <$from_email>";
			$headers[]  = "Reply-To:$name <$from_email>";
		}

		return $headers;

	}

	/**
	 * Sends an email immeadiately
	 *
	 */
	public function send( $to, $subject, $email ) {

		$headers = $this->get_headers();
		return wp_mail( $to, $subject, $email, $headers );

	}

	/**
	 * Sends an email in the background
	 *
	 */
	public function background_send( $to, $subject, $email ) {


	}

}
