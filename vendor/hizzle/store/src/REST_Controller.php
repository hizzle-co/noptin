<?php

namespace Hizzle\Store;

/**
 * The rest controller for a single collection.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST_Controller API.
 */
class REST_Controller extends \WP_REST_Controller {

	/**
	 * Loads the class.
	 *
	 * @param string $namespace The store's namespace.
	 * @param string $collection The current collection.
	 */
	public function __construct( $namespace, $collection ) {
		$this->namespace = $namespace . '/v1';
		$this->rest_base = $collection;

		// Register rest routes.
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Retrieves the current store.
	 *
	 * @return Store|null The store, or null if not registered.
	 * @since 1.0.0
	 */
	public function fetch_store() {
		return Store::instance( trim( $this->namespace, '/v1' ) );
	}

	/**
	 * Retrieves the current collection.
	 *
	 * @return Collection|null The collection, or null if not registered.
	 * @since 1.0.0
	 */
	public function fetch_collection() {
		$store = $this->fetch_store();
		return $store ? $store->get( $this->rest_base ) : null;
	}

	/**
	 * Registers REST routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {

		// Fetch database table.
		$collection = $this->fetch_collection();

		if ( empty( $collection ) ) {
			return;
		}

		// METHODS to CREATE new records and READ the entire collection.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		// METHODS to READ, UPDATE and DELETE a single record.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the object.', 'hizzle-store' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => $collection->is_cpt() ? array(
						'force' => array(
							'default'     => false,
							'type'        => 'boolean',
							'description' => __( 'Whether to bypass trash and force deletion.', 'hizzle-store' ),
						),
					) : array(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/batch',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'batch_items' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_public_batch_schema' ),
			)
		);
	}

	/**
	 * Retrieves an object.
	 *
	 * @param  int $id Object ID.
	 * @return Record|null Data object or null.
	 */
	protected function get_object( $id ) {
		$collection = $this->fetch_collection();

		// Abort if the collection is non-existent.
		if ( empty( $collection ) ) {
			return null;
		}

		// Fetch the object.
		try {
			return $collection->get( $id );
		} catch ( Store_Exception $e ) {
			return null;
		}

	}

	/**
	 * Save an object data.
	 *
	 * @since  1.0.0
	 * @param  \WP_REST_Request $request  Full details about the request.
	 * @param  bool            $creating If is creating a new object.
	 * @return Record|WP_Error
	 */
	protected function save_object( $request, $creating = false ) {

		try {
			$object = $this->prepare_item_for_database( $request, $creating );

			if ( is_wp_error( $object ) ) {
				return $object;
			}

			$object->save();

			return $this->get_object( $object->get_id() );
		} catch ( Store_Exception $e ) {
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		}

	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! $this->check_record_permissions( 'read' ) ) {
			return new \WP_Error( 'hizzle_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'hizzle-store' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to create an item.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! $this->check_record_permissions( 'create' ) ) {
			return new \WP_Error( 'hizzle_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'hizzle-store' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to read an item.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( $object && $object->exists() && ! $this->check_record_permissions( 'read', $object->get_id() ) ) {
			return new \WP_Error( 'hizzle_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'hizzle-store' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to update an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( $object && $object->exists() && ! $this->check_record_permissions( 'edit', $object->get_id() ) ) {
			return new \WP_Error( 'hizzle_rest_cannot_edit', __( 'Sorry, you are not allowed to edit this resource.', 'hizzle-store' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to delete an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( $object && $object->exists() && ! $this->check_record_permissions( 'delete', $object->get_id() ) ) {
			return new \WP_Error( 'hizzle_rest_cannot_delete', __( 'Sorry, you are not allowed to delete this resource.', 'hizzle-store' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check permissions of posts on REST API.
	 *
	 * @since 1.0.0
	 * @param string $context   Request context.
	 * @param int    $object_id Post ID.
	 * @return bool
	 */
	public function check_record_permissions( $context = 'read', $object_id = 0 ) {
		$collection = $this->fetch_collection();

		// Only admins can query non-post type collections.
		if ( empty( $collection ) || empty( $collection->post_type ) ) {
			return current_user_can( 'manage_options' );
		}

		$contexts = array(
			'read'   => 'read_private_posts',
			'create' => 'publish_posts',
			'edit'   => 'edit_post',
			'delete' => 'delete_post',
			'batch'  => 'edit_others_posts',
		);

		if ( 'revision' === $collection->post_type ) {
			$permission = false;
		} else {
			$cap              = $contexts[ $context ];
			$post_type_object = get_post_type_object( $collection->post_type );
			$permission       = current_user_can( $post_type_object->cap->$cap, $object_id );
		}

		return apply_filters( 'hizzle_store_rest_check_permissions', $permission, $context, $object_id, $collection->post_type, $this );
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$collection = $this->fetch_collection();

		try {
			$query = $collection->query( $request->get_params() );
		} catch ( Store_Exception $e ) {
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		}

		$items = array();

		foreach ( $query->get_results() as $item ) {
			$data    = $this->prepare_item_for_response( $item, $request );
			$items[] = $this->prepare_response_for_collection( $data );
		}

		$response = rest_ensure_response( $items );

		$response->header( 'X-WP-Total', (int) $query->get_total() );

		return $response;
	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( ! $object || ! $object->exists() ) {
			return new \WP_Error( "hizzle_rest_{$this->rest_base}_invalid_id", __( 'Invalid ID.', 'hizzle-store' ), array( 'status' => 404 ) );
		}

		$data = $this->prepare_item_for_response( $object, $request );

		return rest_ensure_response( $data );

	}

	/**
	 * Creates one item from the collection.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {

		if ( ! empty( $request['id'] ) ) {
			/* translators: %s: rest base */
			return new \WP_Error( "hizzle_rest_{$this->rest_base}_exists", sprintf( __( 'Cannot create existing %s.', 'hizzle-store' ), $this->rest_base ), array( 'status' => 400 ) );
		}

		$object = $this->save_object( $request, true );

		if ( is_wp_error( $object ) ) {
			return $object;
		}

		if ( ! $object || ! $object->exists() ) {
			return new \WP_Error( "hizzle_rest_{$this->rest_base}_create_failed", __( 'Creating resource failed.', 'hizzle-store' ), array( 'status' => 500 ) );
		}

		try {
			$this->update_additional_fields_for_object( $object, $request );

			// Fires after a single object is created or updated via the REST API.
			do_action( 'hizzle_rest_insert_object_' . $this->get_normalized_rest_base(), $object, $request, true );
		} catch ( Store_Exception $e ) {
			$object->delete();
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $object, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $object->get_id() ) ) );

		return $response;

	}

	/**
	 * Updates one item from the collection.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {

		$object = $this->get_object( (int) $request['id'] );

		if ( ! $object || ! $object->exists() ) {
			return new \WP_Error( "hizzle_rest_{$this->rest_base}_invalid_id", __( 'Invalid ID.', 'hizzle-store' ), array( 'status' => 400 ) );
		}

		$object = $this->save_object( $request, false );

		if ( is_wp_error( $object ) ) {
			return $object;
		}

		try {
			$this->update_additional_fields_for_object( $object, $request );

			// Fires after a single object is created or updated via the REST API.
			do_action( 'hizzle_rest_insert_object_' . $this->get_normalized_rest_base(), $object, $request, false );
		} catch ( Store_Exception $e ) {
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $object, $request );
		return rest_ensure_response( $response );

	}

	/**
	 * Deletes one item from the collection.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		// TODO:
	}

	/**
	 * Prepares one item for create or update operation.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request $request Request object.
	 * @param  bool            $creating If is creating a new object.
	 * @return Record|\WP_Error The prepared item, or WP_Error object on failure.
	 */
	protected function prepare_item_for_database( $request, $creating = false ) {
		$record = $this->get_object( $creating ? 0 : (int) $request['id'] );

		if ( is_wp_error( $record ) ) {
			return $record;
		}

		foreach ( array_keys( $this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ) ) as $arg ) {
			if ( isset( $request[ $arg ] ) ) {

				// Special handling for metadata.
				if ( 'metadata' === $arg ) {

					$metadata = is_array( $request[ $arg ] ) ? $request[ $arg ] : array();

					foreach ( $metadata as $key => $value ) {

						if ( '' === $value ) {
							$record->remove_meta( $key );
						} else {
							$record->update_meta( $key, $value );
						}
					}

					continue;
				}

				$record->set( $arg, $request[ $arg ] );
			}
		}

		return $record;
	}

	/**
	 * Prepares the item for the REST response.
	 *
	 * @since 1.0.0
	 *
	 * @param Record           $item    WordPress representation of the item.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$fields = $this->get_fields_for_response( $request );
		$data   = array();

		foreach ( $item->get_data() as $key => $value ) {
			if ( rest_is_field_included( $key, $fields ) ) {
				$data[ $key ] = $value;
			}
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		if ( rest_is_field_included( '_links', $fields ) ) {
			$links = $this->prepare_links( $item, $request );
			$response->add_links( $links );
		}

		/**
		 * Filters the data for a REST API response.
		 *
		 */
		return apply_filters( "hizzle_store_rest_prepare_{$this->namespace}_{$this->rest_base}", $response, $item, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param Record           $record  Record data.
	 * @param \WP_REST_Request $request Request object.
	 * @return array                    Links for the given post.
	 */
	protected function prepare_links( $record, $request ) {
		$links = array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $record->get_id() ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		// TODO: Add links to aggregate data.
		// TODO: Add links to related objects.
		return $links;
	}

	/**
	 * Retrieves the query params for the collections.
	 *
	 * @since 1.0.0
	 *
	 * @return array Query parameters for the collection.
	 */
	public function get_collection_params() {

		$params     = parent::get_collection_params();
		$collection = $this->fetch_collection();

		if ( $collection ) {
			$params = array_merge( $params, $collection->get_query_schema() );
		}

		// Filter collection parameters.
		return apply_filters( "hizzle_rest_{$this->rest_base}_collection_params", $params, $this );
	}

	/**
	 * Retrieves the item's schema, conforming to JSON Schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$collection = $this->fetch_collection();
		$schema     = $collection ? $collection->get_rest_schema() : array();
		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Check batch limit.
	 *
	 * @param array $items Request items.
	 * @return bool|WP_Error
	 */
	protected function check_batch_limit( $items ) {
		$limit = apply_filters( 'hizzle_rest_batch_items_limit', 100, $this->get_normalized_rest_base() );
		$total = 0;

		if ( ! empty( $items['create'] ) ) {
			$total += count( $items['create'] );
		}

		if ( ! empty( $items['update'] ) ) {
			$total += count( $items['update'] );
		}

		if ( ! empty( $items['delete'] ) ) {
			$total += count( $items['delete'] );
		}

		if ( $total > $limit ) {
			/* translators: %s: items limit */
			return new \WP_Error( 'hizzle_rest_request_entity_too_large', sprintf( __( 'Unable to accept more than %s items for this request.', 'hizzle-pay' ), $limit ), array( 'status' => 413 ) );
		}

		return true;
	}

	/**
	 * Bulk create, update and delete items.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return array Of WP_Error or WP_REST_Response.
	 */
	public function batch_items( $request ) {
		/**
		 * REST Server
		 *
		 * @var \WP_REST_Server $wp_rest_server
		 */
		global $wp_rest_server;

		// Get the request params.
		$items    = array_filter( $request->get_params() );
		$query    = $request->get_query_params();
		$response = array();

		// Check batch limit.
		$limit = $this->check_batch_limit( $items );
		if ( is_wp_error( $limit ) ) {
			return $limit;
		}

		if ( ! empty( $items['create'] ) ) {
			foreach ( $items['create'] as $item ) {
				$_item = new \WP_REST_Request( 'POST', $request->get_route() );

				// Default parameters.
				$defaults = array();
				$schema   = $this->get_public_item_schema();
				foreach ( $schema['properties'] as $arg => $options ) {
					if ( isset( $options['default'] ) ) {
						$defaults[ $arg ] = $options['default'];
					}
				}
				$_item->set_default_params( $defaults );

				// Set request parameters.
				$_item->set_body_params( $item );

				// Set query (GET) parameters.
				$_item->set_query_params( $query );

				$_response = $this->create_item( $_item );

				if ( ! is_wp_error( $_response ) ) {
					$response[] = $wp_rest_server->response_to_data( $_response, '' );
				}
			}
		}

		if ( ! empty( $items['update'] ) ) {
			foreach ( $items['update'] as $item ) {
				$_item = new \WP_REST_Request( 'PUT', $request->get_route() );
				$_item->set_body_params( $item );
				$_response = $this->update_item( $_item );

				if ( ! is_wp_error( $_response ) ) {
					$response[] = $wp_rest_server->response_to_data( $_response, '' );
				}
			}
		}

		if ( ! empty( $items['delete'] ) ) {
			foreach ( $items['delete'] as $id ) {
				$id = (int) $id;

				if ( 0 === $id ) {
					continue;
				}

				$_item = new \WP_REST_Request( 'DELETE', $request->get_route() );
				$_item->set_query_params(
					array(
						'id'    => $id,
						'force' => true,
					)
				);
				$this->delete_item( $_item );
			}
		}

		do_action( 'hizzle_rest_batch_items_' . $this->get_normalized_rest_base(), $request, $response );

		return rest_ensure_response( $response );
	}

	/**
	 * Get the batch schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_public_batch_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'batch',
			'type'       => 'object',
			'properties' => array(
				'create' => array(
					'description' => __( 'List of created resources.', 'hizzle-store' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'    => 'object',
					),
				),
				'update' => array(
					'description' => __( 'List of updated resources.', 'hizzle-store' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'    => 'object',
					),
				),
				'delete' => array(
					'description' => __( 'List of delete resources.', 'hizzle-store' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'    => 'integer',
					),
				),
			),
		);

		return $schema;
	}

	/**
	 * Get normalized rest base.
	 *
	 * @return string
	 */
	protected function get_normalized_rest_base() {
		return preg_replace( '/\(.*\)\//i', '', trim( $this->namespace, '/v1' ) . '_' . $this->rest_base );
	}
}
