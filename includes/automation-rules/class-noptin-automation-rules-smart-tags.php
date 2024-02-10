<?php
/**
 * Automation Rules API: Smart tags.
 *
 * Allows users to use smart tags in automation rule actions.
 *
 * @since   1.9.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Allows users to use smart tags in automation rule actions.
 *
 * @internal
 * @access private
 * @since 1.9.0
 * @ignore
 */
class Noptin_Automation_Rules_Smart_Tags extends Noptin_Dynamic_Content_Tags {

	/**
	 * Called when a trigger is fired.
	 *
	 * @param Noptin_Abstract_Trigger $trigger
	 * @param mixed $subject
	 * @param array $extra_args
	 */
	public function __construct( $trigger, $subject, $extra_args = array() ) {

		if ( empty( $trigger ) ) {
			return;
		}

		$values = array_merge( $extra_args, $trigger->prepare_known_smart_tags( $subject ) );

		if ( ! empty( $extra_args['extra_args'] ) ) {
			$values = array_merge( $values, $extra_args['extra_args'] );
		}

		foreach ( $trigger->get_known_smart_tags() as $merge_tag => $tag ) {

			if ( isset( $values[ $merge_tag ] ) && 'subject' !== $merge_tag ) {
				$tag['replacement'] = $values[ $merge_tag ];
			}

			if ( 'subject' === $merge_tag && isset( $values['subject_orig'] ) ) {
				$tag['replacement'] = $values['subject_orig'];
			}

			if ( ! isset( $tag['partial'] ) ) {
				$tag['partial'] = true;
			}

			$this->tags[ $merge_tag ] = $tag;
		}

		// Ensure we have a replacement for [[email]].
		if ( ! isset( $this->tags['email'] ) ) {
			$this->tags['email'] = array(
				'description' => __( 'The email address of the current visitor (if known).', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_email' ),
			);
		}
	}

	/**
	 * @param string $content The string containing dynamic content tags.
	 * @param string $escape_function Escape mode for the replacement value. Leave empty for no escaping.
	 * @return string
	 */
	protected function replace( $content, $escape_function = '' ) {
		return $this->replace_with_brackets( $content, $escape_function );
	}
}
