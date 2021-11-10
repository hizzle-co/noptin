<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' )  ) {
	die;
}

/**
 * Sends a an email to subscribers.
 *
 * @since       1.3.0
 */
class Noptin_Email_Action extends Noptin_Abstract_Action {

    /**
     * Constructor.
     *
     * @since 1.3.0
     * @return string
     */
    public function __construct() {
        add_action( 'noptin_render_editor_email_action_content', array( $this, 'render_tinymce' ) );
    }

    /**
     * Renders the email editor.
     *
     * @since 1.3.0
     * @return string
     */
    public function render_tinymce() {

        echo '<div class="noptin-editor-wrapper field-wrapper">';
        printf(
            '<label class="noptin-label">%s</label>',
            __( 'Email Body', 'newsletter-optin-box' )
        );
        echo '<div class="noptin-content">';

        $content = '';
        if ( ! empty( $_GET['edit'] ) && is_numeric( $_GET['edit'] ) ) {
            $rule = new Noptin_Automation_Rule( $_GET['edit'] );
            $settings = $rule->action_settings;

            if ( ! empty( $settings['email_content'] ) ) {
                $content = wp_unslash( $settings['email_content'] );
            }

        }

        wp_editor(
			$content,
			'noptinemailbody',
			array(
				'media_buttons'    => true,
				'drag_drop_upload' => true,
				'textarea_rows'    => 15,
				'textarea_name'    => 'noptinemailbody',
				'tabindex'         => 4,
				'tinymce'          => array(
					'theme_advanced_buttons1' => 'bold,italic,underline,|,bullist,numlist,blockquote,|,link,unlink,|,spellchecker,fullscreen,|,formatselect,styleselect',
				),
			)
        );

        echo '</div></div>';

    }

    /**
     * @inheritdoc
     */
    public function get_id() {
        return 'email';
    }

    /**
     * @inheritdoc
     */
    public function get_name() {
        return __( 'Send Email', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_description() {
        return __( 'Sends the subscriber an email', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_rule_description( $rule ) {

        $settings = $rule->action_settings;

        if ( empty( $settings['email_subject'] ) ) {
            return __( 'send them an email', 'newsletter-optin-box' );
        }

        $email_subject = esc_html( $settings['email_subject'] );
        return sprintf(
            __( 'send them an email with the subject %s', 'newsletter-optin-box' ),
           "<code>$email_subject</code>"
        );

    }

    /**
     * @inheritdoc
     */
    public function get_keywords() {
        return array(
            'noptin',
            'email',
            'send email'
        );
    }

    /**
     * @inheritdoc
     */
    public function get_settings() {
        return array(
            'email_subject'   => array(
				'el'          => 'input',
				'label'       => __( 'Email Subject', 'newsletter-optin-box' ),
				'description' => __( 'What is the subject of the email?', 'newsletter-optin-box' ),
            ),
            'email_preview'   => array(
				'el'          => 'input',
				'label'       => __( 'Preview Text', 'newsletter-optin-box' ),
				'description' => __( 'Enter an optional text that should be displayed next to the subject.', 'newsletter-optin-box' ),
            ),
            'email_content'   => array(
				'el'          => 'email_action_content',
				'label'       => __( 'Email Content', 'newsletter-optin-box' ),
				'description' => __( 'Enter the email content. Shortcodes and merge tags are allowed.', 'newsletter-optin-box' ),
            ),
            'permission_reminder'   => array(
				'el'          => 'textarea',
				'label'       => __( 'Permission Reminder', 'newsletter-optin-box' ),
                'description' => __( 'Shortcodes and merge tags are allowed.', 'newsletter-optin-box' ),
                'default'     => noptin()->mailer->get_permission_text( array() ),
            ),
            'email_footer'    => array(
				'el'          => 'textarea',
				'label'       => __( 'Footer Text', 'newsletter-optin-box' ),
                'description' => __( 'Shortcodes and merge tags are allowed.', 'newsletter-optin-box' ),
                'default'     => noptin()->mailer->get_footer_text( array() ),
            )
        );
    }

    /**
     * Sends an email to the subscriber.
     *
     * @since 1.3.0
     * @param Noptin_Subscriber $subscriber The subscriber.
     * @param Noptin_Automation_Rule $rule The automation rule used to trigger the action.
     * @param array $args Extra arguments passed to the action.
     * @return void
     */
    public function run( $subscriber, $rule, $args ) {

        $email_content = $rule->action_settings['email_content'];
        $email_subject = $rule->action_settings['email_subject'];
        $email_preview = isset( $rule->action_settings['email_preview'] ) ? $rule->action_settings['email_preview'] : '';

        if ( $subscriber->is_virtual ) {
            $merge_tags = $subscriber->to_array();
        } else {
            $merge_tags = get_noptin_subscriber_merge_fields(  $subscriber->id  );
        }

        if ( is_array( $args ) ) {
            $merge_tags = array_merge( $merge_tags, $args );
        }

		$item  = array(
			'subscriber_id' 	=> $subscriber->is_virtual ? 0 : $subscriber->id,
			'email' 			=> $subscriber->email,
			'email_body'	    => wp_kses_post( stripslashes_deep( $email_content ) ),
			'email_subject' 	=> esc_html( stripslashes_deep( $email_subject ) ),
			'preview_text'  	=> esc_html( stripslashes_deep( $email_preview ) ),
            'merge_tags'		=> $merge_tags,
            'permission_text'   => isset( $rule->action_settings['permission_reminder'] ) ? $rule->action_settings['permission_reminder'] : '',
            'footer_text'       => isset( $rule->action_settings['email_footer'] ) ? $rule->action_settings['email_footer'] : '',
		);

		$item = apply_filters( 'noptin_email_action_email_details', $item, $subscriber, $rule, $args );

        // Sends the email.
        return noptin()->mailer->prepare_then_send( $item );

    }

    /**
     * Returns whether or not the action can run (dependancies are installed).
     *
     * @since 1.3.3
     * @param Noptin_Subscriber $subscriber The subscriber.
     * @param Noptin_Automation_Rule $rule The automation rule used to trigger the action.
     * @param array $args Extra arguments passed to the action.
     * @return bool
     */
    public function can_run( $subscriber, $rule, $args ) {

        // Abort if we do not have a subject or an email.
        if ( empty( $rule->action_settings['email_content'] ) || empty( $rule->action_settings['email_subject'] ) ) {
            log_noptin_message(
                sprintf(
                    __( 'Email automation rule action not sent to %s because either the subject or the content has not been set', 'newsletter-optin-box' ),
                    $subscriber->email
                )
            );
            return false;
        }

        // We only want to send an email to active subscribers.
        if ( ! $subscriber->is_active() ) {
            log_noptin_message(
                sprintf(
                    __( 'Email automation rule action not sent to %s because the subscriber is not active', 'newsletter-optin-box' ),
                    $subscriber->email
                )
            );
			return false;
        }

        return true;
    }

}
