<?php

namespace Hizzle\Store;

// A store contains an array of collections, which contain an array of rows, which contains an array of props.
/**
 * Store API: Handles CRUD operations on a array of collections.
 *
 * @since   1.0.0
 * @package Hizzle\Store
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles CRUD operations on an array of collections.
 *
 * @since 1.0.0
 */
class Store {

	/**
	 * Namespace of this store's instance.
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * A list of collections
	 *
	 * @var Collection[]
	 */
	protected $collections;

	/**
	 * A list of class instances
	 *
	 * @var Store[]
	 */
	protected static $instances = array();

	/**
	 * Class constructor.
	 *
	 * @param string $namespace Namespace of this store's instance.
	 * @param array  $collections A list of collections.
	 */
	public function __construct( $namespace, $collections ) {

		// Init the store.
		$this->namespace   = $namespace;
		$this->collections = apply_filters( $this->hook_prefix( 'collections' ), $collections );

		// Prepare the collections.
		foreach ( $this->collections as $key => $collection ) {
            if ( ! $collection instanceof Collection ) {
                $collection['name']        = $key;
                $this->collections[ $key ] = new Collection( $this->namespace, $collection );
            }
        }

		// Register the store.
		self::$instances[ $namespace ] = $this;
	}

	/**
	 * Inits a new store or loads an existing store.
	 *
	 * @param string $namespace Namespace of the store.
	 * @param array  $collections A list of collections.
	 * @return Store
	 */
	public static function init( $namespace, $collections = array() ) {

		// Maybe create a new instance.
		if ( ! isset( self::$instances[ $namespace ] ) ) {
			return new self( $namespace, $collections );
		}

		// Update the existing instance.
		foreach ( $collections as $key => $collection ) {
			if ( ! isset( self::$instances[ $namespace ]->collections[ $key ] ) ) {
				if ( ! $collection instanceof Collection ) {
					$collection['name']  = $key;
					$collections[ $key ] = new Collection( $namespace, $collection );
				}

				self::$instances[ $namespace ]->collections[ $key ] = $collections[ $key ];
			}
        }

		return self::$instances[ $namespace ];
	}

	/**
	 * Retrieves a store by its namespace.
	 *
	 * @param string $namespace Namespace of the store.
	 * @return Store
	 * @throws Store_Exception
	 */
	public static function instance( $namespace ) {

		if ( ! isset( self::$instances[ $namespace ] ) ) {
			throw new Store_Exception( 'missing_store', wp_sprintf( 'Store %s not found.', $namespace ) );
		}

		return self::$instances[ $namespace ];
	}

	/**
	 * Retrieves the hook prefix.
	 *
	 * @param string $suffix Suffix to append to the hook prefix.
	 * @return string
	 */
	public function hook_prefix( $suffix = '' ) {
		return $this->get_namespace() . '_' . $suffix;
	}

	/**
	 * Retrieves the namespace.
	 *
	 * @return string
	 */
	public function get_namespace() {
		return $this->namespace;
	}

	/**
	 * Retrieves a single collection.
	 *
	 * @param string $key The collection key.
	 * @return null|Collection
	 */
	public function get( $key ) {
		return isset( $this->collections[ $key ] ) ? $this->collections[ $key ] : null;
	}

	/**
	 * Retrieves all collections.
	 *
	 * @return Collection[]
	 */
	public function get_collections() {
		return $this->collections;
	}

	/**
     * Returns the table definitions as an array.
     *
     * @return string[]
     */
    public function get_schema() {
		$schema = array();

		foreach ( $this->get_collections() as $collection ) {
			$schema[] = $collection->get_schema();
			$schema[] = $collection->get_meta_schema();
		}

		return array_filter( $schema );
	}

}
