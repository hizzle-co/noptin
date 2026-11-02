<?php
/**
 * Handles text inputs.
 *
 * @since 1.0.0
 *
 */

namespace Hizzle\Noptin\Fields\Types;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles text inputs.
 *
 * @since 1.5.5
 */
class Text extends Base {

	/**
	 * Retreives the input type.
	 *
	 * @since 1.5.5
	 * @return string
	 */
	public function get_input_type() {
		return 'text';
	}

	/**
	 * Store decoded plain text without markup, merge tags, or shortcodes.
	 *
	 * @param mixed $value Submitted value.
	 * @return string
	 */
	public function sanitize_value( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = html_entity_decode( (string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$value = sanitize_text_field( wp_strip_all_tags( $value ) );

		do {
			$previous = $value;
			// Remove complete merge tags, including tags nested in their attributes.
			$value = preg_replace( '/\[\[(?:[^\[\]]++|(?R)|\[(?!\[)|\](?!\]))*\]\]/s', '', $value );
			// The email parser also recognizes entity-encoded square brackets.
			$value = preg_replace( '/&#91;&#91;.*?&#93;&#93;/s', '', $value );
			$value = preg_replace( '/\{\{?\/?[a-zA-Z_][a-zA-Z0-9_.:-]*(?:\s+[^{}]*)?\/?\}\}?/s', '', $value );

			// Discover names from the input rather than the active shortcode registry.
			// Unknown shortcodes must not become executable when an add-on is enabled later.
			if ( preg_match_all( '@\[/?([^<>&/\[\]\x00-\x20=]++)@', $value, $matches ) ) {
				$value = preg_replace( '/' . get_shortcode_regex( array_unique( $matches[1] ) ) . '/s', '', $value );
			}
			// Removal can join fragments into another tag; strip those too.
		} while ( $value !== $previous );

		return sanitize_text_field( $value );
	}

	/**
	 * @inheritdoc
	 */
	public function output( $args ) {

		$placeholder        = empty( $args['placeholder'] ) ? $args['label'] : $args['placeholder'];
		$has_no_placeholder = empty( $args['placeholder'] ) || $placeholder === $args['label'];

		$attrs = array(
			'name'  => $args['name'],
			'id'    => $args['id'],
			'type'  => $this->get_input_type(),
			'value' => $args['value'],
			'class' => array(
				'noptin-text',
				'noptin-form-field',
				'noptin-form-field__' . $args['merge_tag'],
				$has_no_placeholder ? 'noptin-form-field__has-no-placeholder' : 'noptin-form-field__has-placeholder',
			),
		);

		if ( empty( $args['react'] ) ) {
			$attrs['placeholder'] = $placeholder;
		} else {
			$attrs[':placeholder'] = 'field.type.label';
		}

		if ( ! empty( $args['required'] ) ) {
			$attrs['required'] = true;
		}

		?>

			<label class="noptin-label" for="<?php echo esc_attr( $args['id'] ); ?>"><?php echo empty( $args['react'] ) ? wp_kses_post( $args['label'] ) : '{{field.type.label}}'; ?></label>
			<input <?php noptin_attr( 'custom_field_' . $attrs['type'], $attrs, $args ); ?>/>

		<?php
	}

	/**
	 * Filters the database schema.
	 *
	 * @since 2.0.0
	 * @param array $schema
	 * @param array $field
	 */
	public function filter_db_schema( $schema, $custom_field ) {
		$schema = parent::filter_db_schema( $schema, $custom_field );
		$column = $this->get_column_name( $custom_field );

		$schema[ $column ] = array_merge(
			$schema[ $column ],
			array(
				'type'   => 'VARCHAR',
				'length' => 255,
			)
		);

		if ( in_array( $custom_field['merge_tag'], array( 'first_name', 'last_name', 'name' ), true ) ) {
			$schema[ $column ]['length']   = 'name' === $custom_field['merge_tag'] ? 200 : 100;
			$schema[ $column ]['nullable'] = false;
			$schema[ $column ]['default']  = '';
		}

		$schema[ $column ]['sanitize_callback'] = array( $this, 'sanitize_value' );

		return $schema;
	}
}
