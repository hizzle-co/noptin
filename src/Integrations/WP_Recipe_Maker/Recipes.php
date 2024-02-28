<?php

namespace Hizzle\Noptin\Integrations\WP_Recipe_Maker;

defined( 'ABSPATH' ) || exit;

/**
 * Container for recipes.
 */
class Recipes extends \Hizzle\Noptin\Objects\Generic_Post_Type {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function __construct() {
		$this->record_class      = __NAMESPACE__ . '\Recipe';
		$this->integration       = 'wp-recipe-maker';
		$this->title_field       = 'name';
		$this->description_field = 'summary';
		$this->image_field       = 'image';
		$this->url_field         = 'url';
		$this->meta_field        = 'total_time';
		$this->icon              = array(
			'icon' => 'carrot',
			'fill' => '#0075c5',
		);
		parent::__construct( \WPRM_POST_TYPE, false );
	}

	protected function taxonomies() {
		return wp_list_pluck(
			\WPRM_Taxonomies::get_taxonomies(),
			'name'
		);
	}

	/**
	 * Retrieves available fields.
	 *
	 */
	public function get_fields() {

		$fields = array(
			'name'                 => array(
				'label' => __( 'Name', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => sprintf(
						/* translators: %s: Object type label. */
						__( '%s Name', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'Displays the %s name.', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'icon'        => 'heading',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'element'     => 'heading',
					'linksTo'     => $this->field_to_merge_tag( 'url' ),
				),
			),
			'slug'                 => array(
				'label' => __( 'Slug', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'image'                => array(
				'label' => __( 'Image', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => __( 'Featured Image', 'newsletter-optin-box' ),
					'description' => __( 'Displays the featured image.', 'newsletter-optin-box' ),
					'icon'        => 'camera',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'defaults'    => array(
						'alt'  => $this->field_to_merge_tag( 'name' ),
						'href' => $this->field_to_merge_tag( 'url' ),
					),
					'element'     => 'image',
					'settings'    => array(
						'size' => array(
							'label'       => __( 'Resolution', 'newsletter-optin-box' ),
							'el'          => 'image_size_select',
							'description' => __( 'Select the image size to display.', 'newsletter-optin-box' ),
							'placeholder' => __( 'Select image size', 'newsletter-optin-box' ),
							'default'     => 'woocommerce_thumbnail',
						),
					),
				),
			),
			'url'                  => array(
				'description' => __( 'URL', 'newsletter-optin-box' ),
				'type'        => 'string',
				'block'       => array(
					'title'       => __( 'Read More', 'newsletter-optin-box' ),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'Displays a button link to the %s.', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'icon'        => 'welcome-view-site',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'defaults'    => array(
						'text' => __( 'Read More', 'newsletter-optin-box' ),
						'url'  => $this->field_to_merge_tag( 'url' ),
					),
					'element'     => 'button',
				),
			),
			'author'               => array(
				'label' => __( 'Author', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'summary'              => array(
				'label' => __( 'Summary', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => sprintf(
						/* translators: %s: Object type label. */
						__( '%s Summary.', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'Displays the %s summary.', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'icon'        => 'editor-alignleft',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
				),
			),
			'prep_time'            => array(
				'description' => __( 'Preparation time', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'cook_time'            => array(
				'description' => __( 'Cook time', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'total_time'           => array(
				'description' => __( 'Total time', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'custom_time'          => array(
				'description' => __( 'Custom time', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'custom_time_label'    => array(
				'description' => __( 'Custom time label', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'post_status'          => array(
				'description' => __( 'Status', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'language'             => array(
				'description' => __( 'Language', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'type'                 => array(
				'description' => __( 'Type', 'newsletter-optin-box' ),
				'type'        => 'string',
				'options'     => array(
					'food'  => __( 'Food Recipe', 'newsletter-optin-box' ),
					'howto' => __( 'How-to Instructions', 'newsletter-optin-box' ),
					'other' => __( 'Other (no metadata)', 'newsletter-optin-box' ),
				),
			),
			'id'                   => array(
				'label' => __( 'ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'parent_id'            => array(
				'label' => __( 'Parent ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'date'                 => array(
				'description' => __( 'Date', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'date_formatted'       => array(
				'description' => __( 'Date formatted', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'cost'                 => array(
				'description' => __( 'Cost', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'servings'             => array(
				'description' => __( 'Servings', 'newsletter-optin-box' ),
				'type'        => 'boolean',
			),
			'servings_unit'        => array(
				'description' => __( 'Servings unit', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'equipment'            => array(
				'description' => __( 'Equipment', 'newsletter-optin-box' ),
				'type'        => 'string',
				'block'       => array(
					'title'       => sprintf(
						/* translators: %s: Object type label. */
						__( '%s Equipment.', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'Displays the %s equipment.', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'icon'        => 'admin-tools',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
				),
			),
			'ingredients'          => array(
				'description' => __( 'Ingredients', 'newsletter-optin-box' ),
				'type'        => 'string',
				'block'       => array(
					'title'       => sprintf(
						/* translators: %s: Object type label. */
						__( '%s Ingredients.', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'Displays the %s ingredients.', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'icon'        => 'plus',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
				),
			),
			'instructions'         => array(
				'description' => __( 'Instructions', 'newsletter-optin-box' ),
				'type'        => 'string',
				'block'       => array(
					'title'       => sprintf(
						/* translators: %s: Object type label. */
						__( '%s Instructions.', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'Displays the %s instructions.', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'icon'        => 'editor-help',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
				),
			),
			'notes'                => array(
				'description' => __( 'Notes', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'calories'            => array(
				'description' => __( 'Calories', 'newsletter-optin-box' ),
				'type'        => 'number',
			),
			'parent_post_id'       => array(
				'description' => __( 'Parent Post ID', 'newsletter-optin-box' ),
				'type'        => 'number',
			),
			'parent_post_url'      => array(
				'description' => __( 'Parent Post URL', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'parent_post_language' => array(
				'description' => __( 'Parent Post Language', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'meta'                 => $this->meta_key_tag_config(),
		);

		foreach ( $this->taxonomies() as $taxonomy => $label ) {
			$icon = 'marker';

			// Check if taxonomy contains the word category.
			if ( false !== strpos( $taxonomy, 'category' ) ) {
				$icon = 'category';
			}

			// Check if taxonomy contains the word tag.
			if ( false !== strpos( $taxonomy, 'tag' ) ) {
				$icon = 'tag';
			}

			$fields[ $taxonomy ] = $this->taxonomy_tag_config(
				$label,
				sprintf(
					/* translators: %s: Object type label. */
					__( 'Displays the %1$s %2$s.', 'newsletter-optin-box' ),
					strtolower( $this->singular_label ),
					strtolower( $label )
				),
				$icon
			);
		}

		return apply_filters( 'noptin_post_type_known_custom_fields', $fields, $this->type );
	}
}
