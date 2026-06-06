<?php
/**
 * Automation Rules API: Smart tags.
 *
 * Allows users to use smart tags in automation rule actions.
 *
 * @since   1.9.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Automation_Rules;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Hizzle\Noptin\Objects\Store;

/**
 * Allows users to use smart tags in automation rule actions.
 *
 * @internal
 * @access private
 * @since 1.9.0
 * @ignore
 */
class Smart_Tags extends \Noptin_Dynamic_Content_Tags {

	/**
	 * Called when a trigger is fired.
	 *
	 * @param \Noptin_Abstract_Trigger $trigger
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

		$this->register_extra_args( $extra_args['extra_args'] ?? array() );
		$this->register_provided_collections( $extra_args['provided_collections'] ?? array() );

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

	/**
	 * Registers runtime extra tags as smart tags.
	 *
	 * @param array $extra_tags Extra tags.
	 */
	private function register_extra_args( $extra_tags ) {
		if ( empty( $extra_tags ) || ! is_array( $extra_tags ) ) {
			return;
		}

		foreach ( $extra_tags as $merge_tag => $value ) {
			if ( isset( $this->tags[ $merge_tag ] ) ) {
				if ( ! isset( $this->tags[ $merge_tag ]['replacement'] ) ) {
					$this->tags[ $merge_tag ]['replacement'] = $value;
				}

				continue;
			}

			$this->tags[ $merge_tag ] = array(
				'description' => $merge_tag,
				'replacement' => $value,
				'partial'     => true,
			);
		}
	}

	/**
	 * Registers collections provided by the current rule args.
	 *
	 * @param array $provided_collections Provided collections.
	 */
	private function register_provided_collections( $provided_collections ) {
		if ( empty( $provided_collections ) || ! is_array( $provided_collections ) || ! class_exists( Store::class ) ) {
			return;
		}

		global $noptin_current_objects;

		if ( ! is_array( $noptin_current_objects ) ) {
			$noptin_current_objects = array();
		}

		foreach ( $provided_collections as $prefix => $provided ) {
			$type = is_array( $provided ) ? ( $provided['type'] ?? '' ) : $provided;

			if ( empty( $type ) ) {
				continue;
			}

			$collection = Store::get( $type );

			if ( empty( $collection ) ) {
				continue;
			}

			if ( is_numeric( $prefix ) ) {
				$prefix = true;
			} else {
				$prefix = $prefix . '.' . $collection->smart_tags_prefix;
			}

			if ( is_array( $provided ) && array_key_exists( 'item', $provided ) ) {
				$noptin_current_objects[ $prefix ] = $collection->get( $provided['item'] );
			}

			foreach ( Store::smart_tags( $type, true, $prefix ) as $merge_tag => $tag ) {
				if ( ! isset( $tag['partial'] ) ) {
					$tag['partial'] = true;
				}

				$this->tags[ $merge_tag ] = $tag;
			}
		}
	}
}
