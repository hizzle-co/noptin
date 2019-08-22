<?php
/**
 * class Noptin_New_Post_Notify class.
 *
 * @extends Noptin_New_Post_Notify
 */

defined( 'ABSPATH' ) || exit;

class Noptin_New_Post_Notify extends Noptin_Async_Request {

	/**
	 * @var string
	 */
	protected $action  = 'noptin_post_notification';

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */
	public function handle(  ) {
		global $wpdb;

		if( empty( $_POST['post'] ) ) {
			return false;
		}

		$post = get_post( $_POST['post'] );

		//Fetch next 5 subscribers
		$table  	 = $wpdb->prefix . 'noptin_subscribers';
		$sql    	 = $wpdb->prepare( "SELECT * FROM $table WHERE `active`=0 LIMIT %d, 1000", 0 );
		$subscribers = $wpdb->get_results( $sql );


		if( empty( $subscribers ) ) {
			return false;
		}

		if( ! $post || 'publish' != $post->post_status ) {
			return false;
		}

		$email = $this->get_email( $post );

		foreach ( $subscribers as $subscriber ) {
			$this->notify( $subscriber, $email, $post );
		}

		update_post_meta( $post->ID , 'noptin_count_subscribers_notified_of_post', count( $subscribers ) );

	}

	/**
	 * Retrives the email body
	 *
	 */
	protected function get_email( $post ) {
		global $noptin_new_post_notify_post;

		$noptin   				     = noptin();
		$noptin_new_post_notify_post = $post;

		ob_start();

		include $noptin->plugin_path . 'includes/admin/templates/email-templates/new-post.php';

		return ob_get_clean();
	}

	/**
	 * Sends a new email
	 *
	 */
	protected function notify( $subscriber, $email, $post ) {

		$email = prepare_noptin_email( $email, $subscriber );
		$email = str_ireplace( "[[noptin_author]]", get_the_author_meta( 'display_name', $post->post_author ), $email);
		$email = str_ireplace( "[[cta_url]]", get_permalink( $post->ID ), $email);
		$email = str_ireplace( "[[cta_text]]", __( 'Continue Reading', 'noptin'), $email);

		//Content
		$content = "<p>Hello [[first_name]],</p><p>I just published a new post on [[blog_name]].</p><p>[[excerpt]]</p>";

		$_content = get_noptin_option('new_post_content');
		if(! $_content ) {
			$content = $_content;
		}

		$_content = get_post_meta( $post->ID, 'noptin_post_notify_content', true );
		if(! $_content ) {
			$content = $_content;
		}

		$email = str_ireplace( "[[content]]", $content, $email);

		//Subject
		$subject = get_noptin_option('new_post_subject');
		if(! $subject ) {
			$subject = '[[title]]';
		}

		//Preview
		$preview = get_noptin_option('new_post_preview_text');
		if(! $preview ) {
			$preview = __( 'We just published a new blog post. Hope you like it.', 'noptin');
		}
		$email   = str_ireplace( "[[preheader]]", $preview, $email );

		//Convert content
		$subject   = str_ireplace( "[[title]]", get_the_title( $post ), $subject );
		$subject   = str_ireplace( "[[email_title]]", get_the_title( $post ), $subject );
		$subject   = str_ireplace( "[[blog_name]]", get_bloginfo('name'), $subject );
		$subject   = str_ireplace( "[[blog_description]]", get_bloginfo('description'), $subject );
		$subject   = str_ireplace( "[[excerpt]]", get_the_excerpt( $post ), $subject );
		$subject   = str_ireplace( "[[post_content]]", $post->post_content, $subject );
		$email     = str_ireplace( "[[title]]", get_the_title( $post ), $email );
		$email     = str_ireplace( "[[email_title]]", get_the_title( $post ), $email );
		$email     = str_ireplace( "[[blog_name]]", get_bloginfo('name'), $email );
		$email     = str_ireplace( "[[blog_description]]", get_bloginfo('description'), $email );
		$email     = str_ireplace( "[[excerpt]]", get_the_excerpt( $post ), $email );
		$email     = str_ireplace( "[[post_content]]", $post->post_content, $email );


		//Names
		$email   = str_ireplace( "[[first_name]]", $subscriber->first_name, $email);
		$email   = str_ireplace( "[[second_name]]", $subscriber->second_name, $email);
		$subject = str_ireplace( "[[first_name]]", $subscriber->first_name, $subject);
		$subject = str_ireplace( "[[second_name]]", $subscriber->second_name, $subject);
		$preview = str_ireplace( "[[first_name]]", $subscriber->first_name, $preview);
		$preview = str_ireplace( "[[second_name]]", $subscriber->second_name, $preview);

		//Subscriber meta
		$meta = get_noptin_subscriber_meta( $subscriber->id );
		foreach( $meta as $key=>$values ) {

			if( isset( $values[0] ) && is_string( $values[0] ) ) {

				$value   = esc_html( $values[0] );
				$email   = str_ireplace( "[[$key]]", $value, $email);
				$subject = str_ireplace( "[[$key]]", $value, $subject);
				$preview = str_ireplace( "[[$key]]", $value, $preview);

			}

		}


		//Send the email
		$headers = array('Content-Type: text/html; charset=UTF-8');

		$from_email = get_noptin_option( 'from_email' );
		if( is_email($from_email) ) {
			$name = get_noptin_option( 'from_name' );
			$from_email = get_noptin_option( 'from_email' );
			$headers[] = "From:$name <$from_email>";
			$headers[] = "Reply-To:$name <$from_email>";
		}

		wp_mail( $subscriber->email, $subject, $email, $headers );

	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		parent::complete();

		// Show notice to user or perform some other arbitrary task...
	}

}
