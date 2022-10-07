<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sends a an email to subjects.
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
			esc_html__( 'Email Body', 'newsletter-optin-box' )
		);
		echo '<div class="noptin-content">';

		$content = '';
		if ( ! empty( $_GET['noptin_edit_automation_rule'] ) && is_numeric( $_GET['noptin_edit_automation_rule'] ) ) {
			$rule     = new Noptin_Automation_Rule( $_GET['noptin_edit_automation_rule'] );
			$settings = $rule->action_settings;

			if ( ! empty( $settings['email_content'] ) ) {
				$content = wp_unslash( $settings['email_content'] );
			}
		}

		// Vue does not work well with actions that insert <script> and <style> tags.
		remove_all_actions( 'media_buttons' );
		add_action( 'media_buttons', 'media_buttons' );

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

		printf(
			'<p class="description" v-show="availableSmartTags">%s</p>',
			sprintf(
				/* translators: %1: Opening link, %2 closing link tag. */
				esc_html__( 'You can use shortcodes and %1$s smart tags %2$s to personalize this email.', 'newsletter-optin-box' ),
				'<a href="#TB_inline?width=0&height=550&inlineId=noptin-automation-rule-smart-tags" class="thickbox">',
				'</a>'
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
		return __( 'Send an email', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_rule_description( $rule ) {

		$settings = $rule->action_settings;

		if ( empty( $settings['email_subject'] ) ) {
			$rule_description = esc_html__( 'send them an email', 'newsletter-optin-box' );
		} else {
			$email_subject    = esc_html( $settings['email_subject'] );
			$rule_description = sprintf(
				// translators: %s is the email subject
				esc_html__( 'send them an email with the subject %s', 'newsletter-optin-box' ),
				"<code>$email_subject</code>"
			);
		}

		return apply_filters( 'noptin_email_action_rule_description', $rule_description, $rule );

	}

	/**
	 * @inheritdoc
	 */
	public function get_keywords() {
		return array(
			'noptin',
			'email',
			'send email',
		);
	}

	/**
	 * @inheritdoc
	 */
	public function get_settings() {
		return array(
			'email_subject'       => array(
				'el'          => 'input',
				'label'       => __( 'Email Subject', 'newsletter-optin-box' ),
				'description' => sprintf(
					'<p class="description" v-show="availableSmartTags">%s</p>',
					sprintf(
						/* translators: %1: Opening link, %2 closing link tag. */
						esc_html__( 'You can use %1$s smart tags %2$s to personalize this field.', 'newsletter-optin-box' ),
						'<a href="#TB_inline?width=0&height=550&inlineId=noptin-automation-rule-smart-tags" class="thickbox">',
						'</a>'
					)
				),
			),
			'email_preview'       => array(
				'el'          => 'input',
				'label'       => __( 'Preview Text', 'newsletter-optin-box' ),
				'description' => sprintf(
					'<p class="description" v-show="availableSmartTags">%s</p>',
					sprintf(
						/* translators: %1: Opening link, %2 closing link tag. */
						esc_html__( 'You can use %1$s smart tags %2$s to personalize this field.', 'newsletter-optin-box' ),
						'<a href="#TB_inline?width=0&height=550&inlineId=noptin-automation-rule-smart-tags" class="thickbox">',
						'</a>'
					)
				),
			),
			'email_heading'       => array(
				'el'          => 'input',
				'label'       => __( 'Email Heading', 'newsletter-optin-box' ),
				'description' => sprintf(
					'<p class="description" v-show="availableSmartTags">%s</p>',
					sprintf(
						/* translators: %1: Opening link, %2 closing link tag. */
						esc_html__( 'You can use %1$s smart tags %2$s to personalize this field.', 'newsletter-optin-box' ),
						'<a href="#TB_inline?width=0&height=550&inlineId=noptin-automation-rule-smart-tags" class="thickbox">',
						'</a>'
					)
				),
			),
			'email_content'       => array(
				'el'          => 'email_action_content',
				'label'       => __( 'Email Content', 'newsletter-optin-box' ),
				'description' => __( 'Enter the email content. Shortcodes and merge tags are allowed.', 'newsletter-optin-box' ),
			),
			'email_footer'        => array(
				'el'          => 'textarea',
				'label'       => __( 'Footer Text', 'newsletter-optin-box' ),
				'description' => sprintf(
					'<p class="description" v-show="availableSmartTags">%s</p>',
					sprintf(
						/* translators: %1: Opening link, %2 closing link tag. */
						esc_html__( 'You can use shortcodes and %1$s smart tags %2$s to personalize the email footer.', 'newsletter-optin-box' ),
						'<a href="#TB_inline?width=0&height=550&inlineId=noptin-automation-rule-smart-tags" class="thickbox">',
						'</a>'
					)
				),
				'default'     => get_noptin_footer_text(),
			),
			'email_template'      => array(
				'el'          => 'select',
				'label'       => __( 'Email Template', 'newsletter-optin-box' ),
				'description' => __( 'Select the email template to use.', 'newsletter-optin-box' ),
				'default'     => get_noptin_option( 'email_template', 'paste' ),
				'options'     => get_noptin_email_templates(),
			),
			'install_addons_pack' => array(
				'el' 		  => 'upsell',
				'label' 	  => __( 'Delay this email', 'newsletter-optin-box' ),
				'description' => __( 'The add-ons pack allows you to delay this email for a given number of minutes, hours, or days.', 'newsletter-optin-box' ),
				'url' 		  => 'https://noptin.com/pricing/?utm_source=plugin&utm_medium=upsell&utm_campaign=automation-rules',
			),
		);
	}

	/**
	 * Sends an email to the subject.
	 *
	 * @since 1.3.0
	 * @param mixed $subject The subject.
	 * @param Noptin_Automation_Rule $rule The automation rule that triggered the action.
	 * @param array $args Extra arguments passed to the action.
	 * @return void
	 */
	public function run( $subject, $rule, $_args ) {

		// Fetch the email recipient.
		$recipient = $this->get_subject_email( $subject, $rule, $_args );

		if ( ! apply_filters( 'noptin_should_send_automation_rule_email', true, $recipient, $rule, $_args ) ) {
			return;
		}

		$settings = wp_unslash( $rule->action_settings );

		/** @var Noptin_Automation_Rules_Smart_Tags $smart_tags */
		$smart_tags = $_args['smart_tags'];

		$args = array(
			'footer_text'    => isset( $settings['email_footer'] ) ? $smart_tags->replace_in_content( $settings['email_footer'] ) : '',
			'heading'        => isset( $settings['email_heading'] ) ? $smart_tags->replace_in_text_field( $settings['email_heading'] ) : '',
			'preview_text'   => isset( $settings['email_preview'] ) ? $smart_tags->replace_in_text_field( $settings['email_preview'] ) : '',
			'subject'        => $smart_tags->replace_in_text_field( $settings['email_subject'] ),
			'content_normal' => $smart_tags->replace_in_content( $settings['email_content'] ),
			'recepient'      => $recipient,
			'email_type'     => 'normal',
			'template'       => isset( $settings['email_template'] ) ? sanitize_text_field( $settings['email_template'] ) : '',
			'merge_tags'     => array(),
		);

		$email = new Noptin_One_Time_Email( $args );

		noptin()->emails->one_time->maybe_send_campaign( $email );
	}

	/**
	 * Returns whether or not the action can run (dependancies are installed).
	 *
	 * @since 1.3.3
	 * @param mixed $subject The subject.
	 * @param Noptin_Automation_Rule $rule The automation rule used to trigger the action.
	 * @param array $args Extra arguments passed to the action.
	 * @return bool
	 */
	public function can_run( $subject, $rule, $args ) {

		// Abort if we do not have a subject or an email.
		if ( empty( $rule->action_settings['email_content'] ) || empty( $rule->action_settings['email_subject'] ) ) {
			return false;
		}

		// We only want to send an email to active recipients.
		$recipient = $this->get_subject_email( $subject, $rule, $args );

		return is_email( $recipient ) && ! noptin_is_email_unsubscribed( $recipient );
	}

}
