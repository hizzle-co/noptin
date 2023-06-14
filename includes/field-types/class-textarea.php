<?php
/**
 * Handles textarea inputs.
 *
 * @since 1.0.0
 *
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles textarea inputs.
 *
 * @since 1.5.5
 */
class Noptin_Custom_Field_Textarea extends Noptin_Custom_Field_Type {

	/**
	 * Whether or not it supports storing values in subscribers table.
	 *
	 * @var bool
	 */
	public $store_in_subscribers_table = true;

	/**
	 * Displays the actual markup for this field.
	 *
	 * @since 1.5.5
	 * @param array $args Field args
	 * @param false|Noptin_Subscriber $subscriber
	 */
	public function output( $args, $subscriber ) {

		$placeholder = empty( $args['placeholder'] ) ? $args['label'] : $args['placeholder'];
		?>

			<label class="noptin-label" for="<?php echo esc_attr( $args['id'] ); ?>"><?php echo empty( $args['vue'] ) ? wp_kses_post( $args['label'] ) : '{{field.type.label}}'; ?></label>
			<textarea
				name="<?php echo esc_attr( $args['name'] ); ?>"
				id="<?php echo esc_attr( $args['id'] ); ?>"
				class="noptin-text noptin-form-field <?php echo empty( $args['placeholder'] ) ? 'noptin-form-field__has-no-placeholder' : 'noptin-form-field__has-placeholder'; ?>"
				rows="4"
				<?php if ( empty( $args['vue'] ) ) : ?>
					placeholder="<?php echo esc_attr( $placeholder ); ?>"
				<?php else : ?>
					:placeholder="field.type.label"
				<?php endif; ?>
			><?php echo esc_textarea( $args['value'] ); ?></textarea>
		<?php

	}

	/**
	 * Filters the database schema.
	 *
	 * @since 1.13.0
	 * @param array $schema
	 * @param array $field
	 */
	public function filter_db_schema( $schema, $custom_field ) {
		$schema[ $this->get_column_name( $custom_field ) ] = array(
			'type'        => 'TEXT',
			'label'        => wp_strip_all_tags( $custom_field['label'] ),
			'description' => wp_strip_all_tags( $custom_field['label'] ),
		);

		return $schema;
	}
}
