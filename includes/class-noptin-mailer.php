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

		if( empty( $data['merge_tags'] ) ) {
			$data['merge_tags'] = array();
		}

		$subject = $this->merge( $subject, $data['merge_tags'] );

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

		if( empty( $data['merge_tags'] ) ) {
			$data['merge_tags'] = array();
		}

		$content = $this->merge( $content, $data['merge_tags'] );
		$content = apply_filters( 'noptin_email_body', $content, $data );

		if(! empty( $data['template'] ) ) {
			$content = $this->prepare_template( $content, $data );
		}

		return $content;

	}

	/**
	 * Attaches a template to an email
	 *
	 */
	public function prepare_template( $content, $data ) {

		// Ensure the template exists
		if(! file_exists( $data['template'] ) ) {
			return $content;
		}

		// Preview text
		$preview  = $this->get_preview_text( $data );
		$preview  = apply_filters( 'noptin_email_preview', $preview, $data );

		// Logo
		$logo  = $this->get_logo( $data );
		$logo  = apply_filters( 'noptin_email_logo', $logo, $data );

		// Content
		$main_content  = $this->get_content( $content, $data );
		$main_content  = apply_filters( 'noptin_email_content', $main_content, $content, $data );

		// Email footer
		$footer  = $this->get_footer( $data );
		$footer  = apply_filters( 'noptin_email_footer', $footer, $data );

		// Tracker
		$tracker  = $this->get_tracker( $data );
		$tracker  = apply_filters( 'noptin_email_tracker', $tracker, $data );

		// Title
		$title = '';

		if(! empty( $data['email_subject'] ) ) {
			$title = trim( $data['email_subject'] );
		}

		// Load it
		ob_start();
		include $data['template'];
		$email_content = ob_get_clean();

		// Parse merge tags
		$email_content = $this->merge( $email_content, $data['merge_tags'] );

		// Remove comments
		$email_content = preg_replace( "/<!--(.*)-->/Uis", '', $email_content );


		// Emogrify the email
		require_once get_noptin_include_dir( 'class-noptin-emogrifier.php' );

		try {
			$emogrifier     = new Noptin_Emogrifier( $email_content );
			$_email_content = $emogrifier->emogrify();
			$email_content  = $_email_content;
		} catch (Exception $e) {

		}

		// Remove multiple line breaks
		$email_content = preg_replace( "/[\r\n]+/", "\n", $email_content );

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

		// Default logo url
		$url      = '';
		$logo_url = apply_filters( 'noptin_email_logo_url', $url, $data );

		ob_start();
		include get_noptin_include_dir( 'admin/templates/email-templates/logo.php' );
		return ob_get_clean();
	}

	/**
	 * Retrieves the default email footer
	 *
	 */
	public function get_footer( $data = array() ) {

		ob_start();
		include get_noptin_include_dir( 'admin/templates/email-templates/footer.php' );
		return ob_get_clean();

	}

	/**
	 * Retrieves tracking code
	 *
	 */
	public function get_tracker( $data = array() ) {

		if( empty( $data['campaign_id'] ) || empty( $data['subscriber_id'] ) ) {
			return '';
		}
		$url = get_noptin_action_url( 'email_open' );

		$url = add_query_arg( array(
			'sid' => intval( $data['subscriber_id'] ),
			'cid' => intval( $data['campaign_id'] ),
		), $url );

		$url = esc_url( $url );

		return "<img src='$url' style='border:0;width:1px;height:1px;' />";

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
	public function merge( $content, $tags = array() ) {

		$tags = wp_parse_args( $this->get_default_merge_tags(), $tags );

		// Replace all available tags with their values
		foreach( $tags as $key => $value ) {
			$content = str_ireplace( "[[$key]]", $value, $content );
		}

		// Remove unavailable tags
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
		// TODO
	}

}
