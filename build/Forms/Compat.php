<?php
/**
 * Forms API: Form Compat.
 *
 * Converts classic forms to the new form editor.
 *
 * @since             3.8.7
 * @package           Noptin
 */

namespace Hizzle\Noptin\Forms;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Converts classic forms to the new form editor.
 *
 * @since 3.8.7
 */
class Compat {

	/**
	 * Converts classic forms to the new form editor.
	 */
	public static function convert( $form_id ) {
		$post       = get_post( $form_id );
		$new_params = array_merge(
			array(
				'optinType'        => 'inpost',
				'optinName'        => $post->post_title,
				'optinStatus'      => ( 'publish' === $post->post_status ),
				'id'               => $post->ID,
				'optinHTML'        => 'Classic Form',
				'noptinFormBg'     => '',
				'titleColor'       => '',
				'descriptionColor' => '',
				'buttonPosition'   => 'left',
				'formBorder'       => array(
					'style'         => 'none',
					'border_radius' => 0,
					'border_width'  => 0,
					'border_color'  => '',
					'generated'     => 'border-width: 0; border-style: none;',
				),
				'hideTitle'        => true,
				'hideDescription'  => true,
				'hideNote'         => true,
				'formHeight'       => '',
				'CSS'              => ".noptin-optin-form-wrapper .noptin-form-footer{ padding: 0; }\n.noptin-optin-form-wrapper form{ text-align: left; }",
			),
			self::get_messages( $form_id ),
			self::get_form_settings( $form_id ),
			self::get_form_texts( $form_id ),
			self::get_form_fields( $form_id ),
			self::get_page_targeting( $form_id ),
			self::get_integration_settings( $form_id )
		);

		return $new_params;
	}

	/**
	 * Adds messages to the new form.
	 */
	public static function get_messages( $form_id ) {

		$messages = get_post_meta( $form_id, 'form_messages', true );

		if ( empty( $messages ) ) {
			return array();
		}

		$prepared = array();

		foreach ( $messages as $message_id => $message ) {
			if ( ! empty( $message ) ) {
				$prepared[ "{$message_id}Message" ] = $message;
			}
		}

		return $prepared;
	}

	/**
	 * Page targeting settings.
	 */
	public static function get_page_targeting( $form_id ) {
		$settings = get_post_meta( $form_id, 'form_settings', true );

		if ( empty( $settings ) ) {
			return array();
		}

		$prepared = array();

		// hide
		if ( ! empty( $settings['hide'] ) ) {
			$prepared['neverShowOn'] = implode( ', ', $settings['hide'] );
		}

		// only_show
		if ( ! empty( $settings['only_show'] ) ) {
			$prepared['onlyShowOn'] = implode( ', ', $settings['only_show'] );
		}

		return $prepared;
	}

	/**
	 * Form texts.
	 */
	public static function get_form_texts( $form_id ) {
		$settings = get_post_meta( $form_id, 'form_settings', true );

		if ( empty( $settings ) ) {
			return array();
		}

		$prepared = array();

		// Button text.
		if ( ! empty( $settings['submit'] ) ) {
			$prepared['noptinButtonLabel'] = $settings['submit'];
		}

		// Acceptance text.
		if ( ! empty( $settings['acceptance'] ) ) {
			$prepared['gdprCheckbox']    = true;
			$prepared['gdprConsentText'] = $settings['acceptance'];
		}

		// Header text.
		if ( ! empty( $settings['before_fields'] ) ) {
			$prepared['title']     = $settings['before_fields'];
			$prepared['hideTitle'] = false;
		}

		// Footer text.
		if ( ! empty( $settings['after_fields'] ) ) {
			$prepared['note']     = $settings['after_fields'];
			$prepared['hideNote'] = false;
		}

		return $prepared;
	}

	/**
	 * Form fields.
	 */
	public static function get_form_fields( $form_id ) {
		$settings = get_post_meta( $form_id, 'form_settings', true );

		if ( ! is_array( $settings ) || empty( $settings['fields'] ) ) {
			return array();
		}

		$prepared = array();

		foreach ( noptin_parse_list( $settings['fields'] ) as $field ) {
			$prepared[] = array(
				'type'    => array(
					'name' => $field,
					'type' => $field,
				),
				'require' => 'email' === $field,
				'key'     => $field,
			);
		}

		return array( 'fields' => $prepared );
	}

	/**
	 * Form settings.
	 */
	public static function get_form_settings( $form_id ) {
		$settings = get_post_meta( $form_id, 'form_settings', true );

		if ( ! is_array( $settings ) ) {
			return array();
		}

		$prepared = array();

		// Redirect URL.
		if ( ! empty( $settings['redirect'] ) ) {
			$prepared['subscribeAction'] = 'redirect';
			$prepared['redirectUrl']     = $settings['redirect'];
		}

		// Tags.
		if ( ! empty( $settings['tags'] ) ) {
			$prepared['tags'] = $settings['tags'];
		}

		// Custom fields.
		foreach ( get_noptin_multicheck_custom_fields() as $field ) {
			if ( empty( $field['merge_tag'] ) ) {
				continue;
			}
			$merge_tag = $field['merge_tag'];
			if ( isset( $settings[ $merge_tag ] ) ) {
				$prepared[ $merge_tag ] = noptin_parse_list( $settings[ $merge_tag ], true );
			}
		}

		// Inject.
		if ( 'after' === ( $settings['inject'] ?? '' ) ) {
			$prepared['inject'] = $settings['inject'];
		}

		// Update update_existing
		if ( ! empty( $settings['update_existing'] ) ) {
			$prepared['update_existing'] = true;
		}

		// Labels.
		$prepared['showLabels'] = 'hide' !== ( $settings['labels'] ?? 'show' );

		// Single line.
		$prepared['singleLine'] = 'condensed' === ( $settings['template'] ?? 'normal' );
		$prepared['unstyled']   = 'none' === ( $settings['styles'] ?? 'inherit' );

		if ( ! $prepared['singleLine'] ) {
			$prepared['formWidth'] = '480px';
		}

		return $prepared;
	}

	/**
	 * Integration settings.
	 */
	public static function get_integration_settings( $form_id ) {
		$settings = get_post_meta( $form_id, 'form_settings', true );

		if ( empty( $settings ) ) {
			return array();
		}

		$prepared = array();
		foreach ( \Noptin_COM::get_connections() as $connection ) {
			foreach ( $settings as $key => $value ) {
				if ( strpos( $key, esc_attr( $connection->slug ) . '_' ) === 0 ) {
					$prepared[ $key ] = $value;
				}
			}
		}

		return $prepared;
	}
}
