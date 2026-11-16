<?php

namespace Hizzle\Store;

defined( 'ABSPATH' ) || exit;

/** Read-only WordPress abilities for a collection with a datastore REST controller. */
class Abilities {
	/** @var array Collections already connected to registration hooks. */
	private static $registered = array();
	/** @var REST_Controller Collection REST controller. */
	private $controller;

	/**
	 * Bind one controller to the WordPress registration hooks.
	 *
	 * @param string          $store      Store namespace used for the ability category.
	 * @param string          $name       Collection name within the store.
	 * @param REST_Controller $controller Controller that serves the collection.
	 */
	public static function init( $store, $name, $controller ) {
		$key = $store . '/' . $name;
		if ( isset( self::$registered[ $key ] ) ) {
			return;
		}
		self::$registered[ $key ] = true;
		$abilities                = new self( $controller );
		add_action( 'wp_abilities_api_init', array( $abilities, 'register' ) );
		add_action(
			'wp_abilities_api_categories_init',
			static function () use ( $store ) {
				$slug = self::slug( $store );
				if ( ! wp_has_ability_category( $slug ) ) {
					wp_register_ability_category(
						$slug,
						array(
							'label'       => ucfirst( $store ),
							'description' => sprintf( 'Read records in the %s store.', $store ),
						)
					);
				}
			},
			20
		);
	}

	/**
	 * Retain the controller for schemas, permissions, and tabs.
	 *
	 * @param REST_Controller $controller Controller that serves the collection.
	 */
	private function __construct( $controller ) {
		$this->controller = $controller;
	}

	/**
	 * Convert datastore identifiers to WordPress ability slugs.
	 *
	 * @param string $name Store namespace or collection ability identifier.
	 * @return string Sanitized slug for the WordPress abilities registry.
	 */
	private static function slug( $name ) {
		return sanitize_title( str_replace( '_', '-', $name ) );
	}

	/**
	 * Filter enabled reads for a whole store or a single collection.
	 *
	 * @param Collection $collection Collection whose read operations are being registered.
	 * @return string[] Enabled operation names.
	 */
	private static function operations( $collection ) {
		return (array) apply_filters( $collection->get_namespace() . '_read_abilities', array( 'list', 'get', 'aggregate', 'tab', 'field-options' ), $collection );
	}

	/**
	 * Add discovery guidance to choice-based read or write inputs.
	 *
	 * @param Collection $collection  Collection that owns the field.
	 * @param Prop|null  $prop        Registered property, or null for an unknown field.
	 * @param string     $description Existing input description.
	 * @return string Input description with field-option guidance when available.
	 */
	public static function describe_field( $collection, $prop, $description ) {
		if ( ! $prop || ( ! $prop->is_tokens && null === $prop->enum ) || ! in_array( 'field-options', self::operations( $collection ), true ) ) {
			return $description;
		}
		$singular     = $collection->get_singular_name() ?: $collection->get_name();
		$description .= sprintf( ' Look up current values and labels with %s/get-%s-field-options using {"field":"%s"}.', self::slug( $collection->get_namespace() ), self::slug( $singular ), $prop->name );
		if ( $prop->is_tokens ) {
			$description .= ' New values are also accepted.';
		}
		return $description;
	}

	/** Register schemas when WordPress initializes the abilities registry. */
	public function register() {
		$collection = $this->controller->fetch_collection();
		if ( ! $collection ) {
			return;
		}
		$enabled = self::operations( $collection );
		if ( ! $enabled ) {
			return;
		}
		$name     = $collection->get_name();
		$single   = $collection->get_singular_name() ?: $name;
		$store    = $collection->get_namespace();
		$singular = $collection->get_label( 'abilities_singular', str_replace( array( '-', '_' ), ' ', "$store $single" ) );
		$plural   = $collection->get_label( 'abilities_plural', str_replace( array( '-', '_' ), ' ', "$store $name" ) );
		$query    = $collection->get_query_schema();
		$props    = $collection->get_rest_schema()['properties'];
		$choices  = array();
		foreach ( $collection->get_props() as $prop ) {
			if ( ! isset( $props[ $prop->name ] ) || ( ! $prop->is_tokens && null === $prop->enum ) ) {
				continue;
			}
			$choices[] = $prop->name;
			foreach ( array( $prop->name, $prop->name . '_not' ) as $filter ) {
				if ( isset( $query[ $filter ] ) ) {
					$query[ $filter ]['description'] = self::describe_field( $collection, $prop, $query[ $filter ]['description'] ?? '' );
				}
			}
		}
		$query += array(
			'page' => array(
				'type'    => 'integer',
				'minimum' => 1,
			),
		);
		$id     = array(
			'id' => array(
				'type'    => 'integer',
				'minimum' => 1,
			),
		);
		if ( in_array( 'list', $enabled, true ) ) {
			$this->add(
				'list',
				"list-$name",
				"List $plural",
				"List $plural using filters, sorting and pagination.",
				$query,
				array(),
				array(
					$name         => array(
						'type'  => 'array',
						'items' => $this->record_schema( $collection ),
					),
					'total'       => array(
						'type'        => 'integer',
						'description' => 'Number of matching records across all pages.',
					),
					'total_pages' => array(
						'type'        => 'integer',
						'description' => 'Number of result pages at the current page size.',
					),
				)
			);
		}
		if ( in_array( 'get', $enabled, true ) ) {
			$selectors = $id;
			foreach ( $collection->keys['unique'] ?? array() as $key ) {
				if ( ! isset( $props[ $key ] ) ) {
					continue;
				}
				$selectors[ $key ]         = array_intersect_key( $props[ $key ], array_flip( array( 'description', 'maxLength', 'format' ) ) );
				$selectors[ $key ]['type'] = in_array( 'integer', (array) $props[ $key ]['type'], true ) ? 'integer' : 'string';
				if ( 'email' === $key ) {
					$selectors[ $key ]['format'] = 'email';
				}
			}
			$this->add( 'get', "get-$single", "Get $singular", "Retrieve one $singular. Supply exactly one identifier.", $selectors );
		}
		if ( $choices && in_array( 'field-options', $enabled, true ) ) {
			$this->add(
				'field-options',
				"get-$single-field-options",
				"Get $singular field options",
				"Look up choices for $plural. Match the display label and pass its stored value.",
				array(
					'field' => array(
						'type' => 'string',
						'enum' => $choices,
					),
				),
				array( 'field' ),
				array(
					'options' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'value' => array( 'type' => 'string' ),
								'label' => array( 'type' => 'string' ),
							),
							'required'   => array( 'value', 'label' ),
						),
					),
				)
			);
		}
		if ( in_array( 'tab', $enabled, true ) ) {
			$tabs = array_filter(
				$this->controller->get_record_tabs(),
				static function ( $tab ) {
					return is_callable( $tab['callback'] ?? null );
				}
			);
			if ( $tabs ) {
				$this->add(
					'tab',
					"get-$single-tab",
					"Get $singular tab",
					"Read a registered tab for one $singular.",
					$id + array(
						'tab_id' => array(
							'type' => 'string',
							'enum' => array_keys( $tabs ),
						),
					),
					array( 'id', 'tab_id' )
				);
			}
		}
		if ( in_array( 'aggregate', $enabled, true ) ) {
			$columns = array_intersect( $collection->get_known_fields()['main'], array_keys( $props ) );
			$metrics = array();
			foreach ( $columns as $column ) {
				$prop      = $collection->get_prop( $column );
				$functions = array( 'COUNT', 'MIN', 'MAX' );
				if ( $prop && ( $prop->is_numeric() || $prop->is_float() ) ) {
					$functions = array_merge( $functions, array( 'SUM', 'AVG' ) );
				}
				$metrics[ $column ] = array(
					'type' => 'string',
					'enum' => $functions,
				);
			}
			$metrics['id'] = array(
				'type' => 'string',
				'enum' => array( 'COUNT', 'MIN', 'MAX' ),
			);
			$filters       = array_diff_key( $query, array_flip( array( 'page', 'paged', 'per_page', 'offset', 'order', 'orderby' ) ) );
			$this->add(
				'aggregate',
				"aggregate-$name",
				"Summarize $plural",
				"Calculate counts and summary statistics for $plural.",
				$filters + array(
					'aggregate' => array(
						'type'                 => 'object',
						'properties'           => $metrics,
						'minProperties'        => 1,
						'additionalProperties' => false,
					),
					'groupby'   => array(
						'type' => 'string',
						'enum' => array_keys( $metrics ),
					),
				),
				array( 'aggregate' )
			);
		}
	}

	/**
	 * Register one read operation using the collection's access policy.
	 *
	 * @param string   $operation   Read operation: list, get, tab, aggregate, or field-options.
	 * @param string   $slug        Ability identifier before the store namespace is added.
	 * @param string   $label       Display name shown to ability consumers.
	 * @param string   $description Description of what the ability reads.
	 * @param array    $properties  Input property schemas keyed by input name.
	 * @param string[] $required    Optional. Names of required input properties.
	 * @param array    $output      Optional. Output property schemas keyed by name. All supplied properties are required.
	 */
	private function add( $operation, $slug, $label, $description, $properties, $required = array(), $output = array() ) {
		$collection = $this->controller->fetch_collection();
		$store      = self::slug( $collection->get_namespace() );
		$route      = $this->controller->get_collection_route();
		$input      = array(
			'type'                 => 'object',
			'properties'           => $properties,
			'additionalProperties' => false,
		);
		if ( $required ) {
			$input['required'] = $required;
		} elseif ( 'get' === $operation ) {
			$input['minProperties'] = 1;
			$input['maxProperties'] = 1;
		} else {
			$input['default'] = array();
		}
		$output_schema = array( 'type' => 'object' );
		if ( $output ) {
			$output_schema['properties'] = $output;
			$output_schema['required']   = array_keys( $output );
		}
		if ( 'get' === $operation ) {
			$output_schema = $this->record_schema( $collection );
		} elseif ( 'tab' === $operation ) {
			$output_schema['properties'] = array(
				'results' => array(
					'type'        => array( 'array', 'object', 'string', 'number', 'integer', 'boolean', 'null' ),
					'description' => 'Content returned by the selected tab. Its structure depends on the registered tab callback.',
				),
			);
			$output_schema['required']   = array( 'results' );
		} elseif ( 'aggregate' === $operation ) {
			$output_schema['properties'] = array(
				'results' => array(
					'type'        => 'array',
					'description' => 'Summary rows. Columns use lowercase function_field aliases such as count_id. Grouped rows also include the groupby field.',
					'items'       => array( 'type' => 'object' ),
				),
			);
			$output_schema['required']   = array( 'results' );
		}
		wp_register_ability(
			$store . '/' . self::slug( $slug ),
			array(
				'label'               => $label,
				'description'         => $description,
				'category'            => $store,
				'input_schema'        => self::normalize_schema( $input ),
				'output_schema'       => self::normalize_schema( $output_schema ),
				'permission_callback' => function ( $args = array() ) use ( $operation, $route ) {
					$request = $this->request( $operation, $route, $args );
					if ( 'get' === $operation || 'tab' === $operation ) {
						return true === $this->controller->get_item_permissions_check( $request );
					}
					return true === $this->controller->get_items_permissions_check( $request );
				},
				'execute_callback'    => function ( $args = array() ) use ( $operation, $route, $collection ) {
					return $this->execute( $operation, $route, $collection, $args );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array( 'public' => true ),
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
					),
				),
			)
		);
	}

	/**
	 * Resolve choices after authorization, or dispatch to the registered endpoint.
	 *
	 * @param string     $operation  Registered read operation to execute.
	 * @param string     $route      Collection REST path before adding a record or operation suffix.
	 * @param Collection $collection Collection supplying the records and field definitions.
	 * @param array      $args       Validated ability inputs keyed by property name.
	 * @return array|\WP_Error Operation result or an error from the REST endpoint.
	 */
	private function execute( $operation, $route, $collection, $args ) {
		if ( 'field-options' === $operation ) {
			$prop    = $collection->get_prop( $args['field'] );
			$options = array();
			if ( $prop->is_tokens ) {
				if ( method_exists( $prop, 'get_token_suggestions' ) ) {
					$values = $prop->get_token_suggestions();
				} else {
					// A different plugin can load an older datastore Prop before this library.
					$values = apply_filters( $prop->hook_prefix( 'token_suggestions' ), null, $collection );
					$values = $values ?? $collection->get_all_meta( $prop->name );
				}
				foreach ( $values as $value ) {
					$options[] = array(
						'value' => (string) $value,
						'label' => (string) $value,
					);
				}
			} else {
				foreach ( $prop->get_choices() as $value => $label ) {
					$options[] = array(
						'value' => (string) $value,
						'label' => (string) $label,
					);
				}
			}
			return array( 'options' => $options );
		}
		$request = $this->request( $operation, $route, $args );
		if ( 'get' === $operation || 'list' === $operation ) {
			$request->set_param( '_fields', implode( ',', wp_list_pluck( $collection->get_props(), 'name' ) ) );
		}
		$response = rest_do_request( $request );
		if ( $response->is_error() ) {
			return $response->as_error();
		}
		if ( 'list' === $operation ) {
			$headers = $response->get_headers();
			return array(
				$collection->get_name() => $response->get_data(),
				'total'                 => (int) $headers['X-WP-Total'],
				'total_pages'           => (int) $headers['X-WP-TotalPages'],
			);
		}
		return 'get' === $operation ? $response->get_data() : array( 'results' => $response->get_data() );
	}

	/**
	 * Build the REST request used for permissions and execution.
	 *
	 * @param string $operation Read operation name.
	 * @param string $route     Collection REST path.
	 * @param array  $args      Validated ability input.
	 * @return \WP_REST_Request
	 */
	private function request( $operation, $route, $args ) {
		$url_params = array();
		if ( 'get' === $operation && $args ) {
			$key = key( $args );
			if ( 'id' === $key ) {
				$route     .= '/' . (int) $args['id'];
				$url_params = array( 'id' => (int) $args['id'] );
			} else {
				$route     .= '/' . $key . '/' . rawurlencode( $args[ $key ] );
				$url_params = array(
					'hizzle_get_by' => $key,
					'hizzle_value'  => rawurlencode( $args[ $key ] ),
				);
			}
			$args = array();
		} elseif ( 'tab' === $operation && isset( $args['id'], $args['tab_id'] ) ) {
			$route     .= '/' . (int) $args['id'] . '/' . rawurlencode( $args['tab_id'] );
			$url_params = array(
				'id'     => (int) $args['id'],
				'tab_id' => $args['tab_id'],
			);
			$args       = array();
		} elseif ( 'aggregate' === $operation ) {
			$route .= '/aggregate';
		}
		if ( 'get' === $operation || 'list' === $operation ) {
			$args['context'] = 'edit';
		}
		$request = new \WP_REST_Request( 'GET', $route );
		$request->set_url_params( $url_params );
		$request->set_query_params( $args );
		return $request;
	}

	/**
	 * Describe record fields without requiring fields omitted by REST context filters.
	 *
	 * @param Collection $collection Collection supplying the response properties.
	 * @return array Record output schema.
	 */
	private function record_schema( $collection ) {
		$properties = array();
		$schema     = $this->controller->get_item_schema();
		foreach ( $collection->get_props() as $prop ) {
			if ( isset( $schema['properties'][ $prop->name ] ) ) {
				$field    = $schema['properties'][ $prop->name ];
				$property = array_intersect_key( $field, array_flip( array( 'type', 'description', 'items', 'properties' ) ) );
				// Text storage can contain structured values returned by record getters.
				if ( in_array( $prop->type, array( 'TEXT', 'LONGTEXT', 'MEDIUMTEXT', 'TINYTEXT' ), true ) && ! isset( $field['items'] ) && ! isset( $field['properties'] ) ) {
					$property['type'] = array_values( array_unique( array_merge( (array) $property['type'], array( 'array', 'object' ) ) ) );
				}
				$properties[ $prop->name ] = $property;
			}
		}
		return array(
			'type'       => 'object',
			'properties' => $properties,
		);
	}

	/**
	 * Remove REST-only schema attributes while preserving property and definition names.
	 *
	 * @param array $schema JSON schema node.
	 * @return array Schema safe for ability discovery and validation.
	 */
	private static function normalize_schema( $schema ) {
		unset( $schema['validate_callback'], $schema['sanitize_callback'], $schema['arg_options'], $schema['context'], $schema['readonly'] );
		if ( isset( $schema['required'] ) && is_bool( $schema['required'] ) ) {
			unset( $schema['required'] );
		}
		if ( isset( $schema['type'] ) && is_array( $schema['type'] ) ) {
			$schema['type'] = array_values( array_unique( $schema['type'] ) );
		}
		foreach ( array( 'properties', 'patternProperties', 'definitions', '$defs' ) as $key ) {
			foreach ( $schema[ $key ] ?? array() as $name => $child ) {
				$schema[ $key ][ $name ] = self::normalize_schema( $child );
			}
		}
		foreach ( array( 'items', 'additionalProperties', 'additionalItems', 'not' ) as $key ) {
			if ( isset( $schema[ $key ] ) && is_array( $schema[ $key ] ) ) {
				if ( 'items' === $key && isset( $schema[ $key ][0] ) ) {
					$schema[ $key ] = array_map( array( __CLASS__, 'normalize_schema' ), $schema[ $key ] );
				} else {
					$schema[ $key ] = self::normalize_schema( $schema[ $key ] );
				}
			}
		}
		foreach ( array( 'oneOf', 'anyOf', 'allOf' ) as $key ) {
			foreach ( $schema[ $key ] ?? array() as $index => $child ) {
				$schema[ $key ][ $index ] = self::normalize_schema( $child );
			}
		}
		return $schema;
	}
}
