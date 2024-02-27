<?php

namespace Hizzle\Noptin\Objects;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Collection digests.
 *
 * @since 3.0.0
 */
class Digest extends \Hizzle\Noptin\Emails\Types\Recurring {

	/**
	 * @var string The collection.
	 */
	public $collection;

	/**
	 * @var string The collection label.
	 */
	public $collection_label;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param Collection $collection The collection.
	 * @return string
	 */
	public function __construct( $collection ) {
		parent::__construct();

		$this->collection_label  = $collection->label;
		$this->collection        = $collection->type;
		$this->type              = 'latest_' . $collection->plural_type() . '_digest';
		$this->notification_hook = 'noptin_send_' . $this->type;

		$this->add_hooks();
	}

	/**
	 * Retrieves the automated email type name.
	 *
	 */
	public function get_name() {
		return sprintf(
			// Translators: %s is the collection label.
			__( 'Latest %s', 'newsletter-optin-box' ),
			$this->collection_label
		);
	}

	/**
	 * Retrieves the automated email type description.
	 *
	 */
	public function get_description() {

		return sprintf(
			// Translators: %s is the collection label.
			__( 'Automatically send a daily, weekly, monthly or yearly email highlighting your latest %s.', 'newsletter-optin-box' ),
			strtolower( $this->collection_label )
		);
	}

	/**
	 * Returns the default subject.
	 *
	 */
	public function default_subject() {
		return sprintf(
			// Translators: %s is the collection label.
			__( 'Check out our latest %s', 'newsletter-optin-box' ),
			strtolower( $this->collection_label )
		);
	}

	/**
	 * Returns the default heading.
	 *
	 */
	public function default_heading() {
		return $this->default_subject();
	}

	/**
	 * Returns the default content.
	 *
	 */
	public function default_content_normal() {
		$collection = Store::get( $this->collection );

		if ( empty( $collection ) || 'post_type' !== $collection->object_type ) {
			return parent::default_content_normal();
		}

		return '<div>[[posts post_type="' . $collection->type . '" skiponempty=yes since_last_send=yes style=list]]</div>';
	}

	/**
	 * Prepares the default blocks.
	 *
	 * @return string
	 */
	protected function prepare_default_blocks() {
		$collection = Store::get( $this->collection );

		if ( empty( $collection ) ) {
			return '';
		}

		$name = $collection->plural_type();
		ob_start();
		?>
<!-- wp:noptin/<?php echo esc_attr( $name ); ?> -->
<div class="wp-block-noptin-<?php echo esc_attr( $name ); ?>">
[noptin_<?php echo esc_html( $name ); ?>_list query="number=10&amp;order=desc&amp;orderby=date" columns=1 responsive=yes skiponempty=no][/noptin_<?php echo esc_html( $name ); ?>_list]
</div>
<!-- /wp:noptin/<?php echo esc_attr( $name ); ?> -->
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns the default frequency.
	 *
	 */
	public function default_frequency() {
		return 'monthly';
	}

	/**
	 * Registers the email sub types.
	 *
	 * @param array $types
	 * @return array
	 */
	public function register_automation_type( $types ) {
		$types = parent::register_automation_type( $types );

		$types[ $this->type ]['alt_category'] = $this->collection_label;
		return $types;
	}
}
