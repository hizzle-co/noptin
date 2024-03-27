<?php

namespace Hizzle\Noptin\Integrations;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Base template integration
 *
 * @since 3.0.0
 */
abstract class Template_Integration {

	/**
	 * @var string The template slug.
	 * @since 2.0.0
	 */
	public $slug;

	/**
	 * @var string The template name.
	 * @since 2.0.0
	 */
	public $name;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'noptin_email_templates', array( $this, 'register_email_template' ) );
		add_filter( 'noptin_email_after_apply_template', array( $this, 'maybe_process_template' ), 10, 2 );
		add_action( 'noptin_email_styles', array( $this, 'maybe_add_email_styles' ) );
	}

	/**
	 * Registers the email template.
	 *
	 * @since 1.7.0
	 * @param array $templates Available templates.
	 * @return array
	 */
	public function register_email_template( $templates ) {
		$templates[ $this->slug ] = $this->name;
		return $templates;
	}

	/**
	 * Processes custom email templates.
	 *
	 * @since 1.7.0
	 * @param string $email.
	 * @param \Noptin_Email_Generator $generator
	 * @return string
	 */
	public function maybe_process_template( $email, $generator ) {

		if ( $this->slug === $generator->template ) {
			$email = $this->process_template( $generator->heading, $generator->content, $generator->footer_text );
		}

		return $email;
	}

	/**
	 * Retrieves all forms.
	 *
	 * @param string $heading
	 * @param string $content
	 * @param string $footer
	 * @return string
	 */
	abstract protected function process_template( $heading, $content, $footer );

	/**
	 * Applies custom email styles to Noptin templates.
	 *
	 * @param \Noptin_Email_Generator $generator
	 */
	public function maybe_add_email_styles( $generator ) {

		if ( 'normal' === $generator->type && $this->slug === $generator->template ) {
			$this->render_email_styles();
		}
	}

	/**
	 * Retrieves custom email styles.
	 *
	 * @return string
	 */
	protected function render_email_styles() {}
}
