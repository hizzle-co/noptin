<?php
/**
 * Noptin.com License.
 *
 * @package Noptin\noptin.com
 * @since   1.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Noptin_COM_License Class
 *
 * Represents a single license key.
 * @ignore
 */
class Noptin_COM_License implements ArrayAccess, JsonSerializable {

	/**
	 * Contains the main license error.
	 *
	 * @var WP_Error
	 */
	public $license_error;

	/**
	 * Contains the main license data.
	 *
	 * @var object
	 */
	private $license_obj;

	/**
	 * Contains the misc license data.
	 *
	 * @var array
	 */
	public $extra_data = array();

	/**
	 * Class constructor.
	 *
	 * @param object|string $license_obj
	 * @since 1.5.0
	 */
	public function __construct( $license_obj ) {

		if ( empty( $license_obj ) ) {
			return;
		}

		if ( is_string( $license_obj ) ) {
			$license_obj = Noptin_COM::get_license_details( $license_obj );
		}

		if ( is_wp_error( $license_obj ) ) {

			$this->license_error = $license_obj;

			if ( is_admin() ) {
				noptin()->admin->show_error( $license_obj->get_error_message() );
			}

		} else {
			$this->license_obj = $license_obj;
		}

	}

	/**
	 * Returns the json serializable data.
	 *
	 * @return object
	 * @since 1.5.0
	 */
	public function jsonSerialize() {
		return $this->license_obj;
    }

	/**
	 * Retrieves a value.
	 *
	 * @param string $offset
	 * @param mixed $value
	 * @since 1.5.0
	 */
	public function offsetSet( $offset, $value ) {

		if ( is_callable( array( $this, $offset ) ) || is_callable( array( $this, 'get_' . $offset ) ) ) {
			$this->license_obj->$offset = $value;
		} else if ( is_null( $offset ) ) {
            $this->extra_data[] = $value;
        } else {
            $this->extra_data[$offset] = $value;
        }

    }

	/**
	 * Checks if a value exists.
	 *
	 * @param string $offset
	 * @since 1.5.0
	 */
    public function offsetExists( $offset ) {
		return is_callable( array( $this, $offset ) ) || is_callable( array( $this, 'get_' . $offset ) ) || isset( $this->extra_data[ $offset ] );
    }

	/**
	 * Unsets a value.
	 *
	 * @param string $offset
	 * @since 1.5.0
	 */
    public function offsetUnset( $offset ) {

		if ( isset( $this->extra_data[ $offset ] ) ) {
			unset( $this->extra_data[ $offset ] );
		}

    }

	/**
	 * Reads a value.
	 *
	 * @param string $offset
	 * @since 1.5.0
	 */
    public function offsetGet( $offset ) {

		if ( is_callable( array( $this, $offset ) ) ) {
			return call_user_func( array( $this, $offset ) );
		}

		if ( is_callable( array( $this, 'get_' . $offset ) ) ) {
			return call_user_func( array( $this, 'get_' . $offset ) );
		}

		if ( isset( $this->extra_data[ $offset ] ) ) {
			return $this->extra_data[ $offset ];
		}

        return null;
    }

	/**
	 * Check whether or not this is a membership license key.
	 *
	 * @since 1.5.0
	 * @return bool
	 */
	public function is_membership() {
		return ! empty( $this->license_obj->is_membership );
	}

	/**
	 * Check whether or not this is an unlimited license key.
	 *
	 * @since 1.5.0
	 * @return bool
	 */
	public function is_unlimited() {
		return ! empty( $this->license_obj->is_unlimited );
	}

	/**
	 * Check whether or not this is a lifetime license key.
	 *
	 * @since 1.5.0
	 * @return bool
	 */
	public function is_lifetime() {
		return ! empty( $this->license_obj->is_lifetime );
	}

	/**
	 * Check whether or not this is an expired license key.
	 *
	 * @since 1.5.0
	 * @return bool
	 */
	public function is_expired() {
		return ! $this->is_lifetime() && ! empty( $this->license_obj->is_expired );
	}

	/**
	 * Check whether or not this is an active license key.
	 *
	 * @since 1.5.0
	 * @return bool
	 */
	public function is_active() {
		return $this->license_obj->status === 1 && ! $this->is_expired();
	}

	/**
	 * Check whether or not the license is activated on this site.
	 *
	 * @since 1.5.0
	 * @return bool
	 */
	public function is_activated_on_site() {
		return array_key_exists( home_url(), $this->get_activated_on() );
	}

	/**
	 * Returns the id of the order used to buy the license key.
	 *
	 * @since 1.5.0
	 * @return int
	 */
	public function get_order_id() {
		return (int) $this->license_obj->order_id;
	}

	/**
	 * Returns the name of the product used to buy the license key.
	 *
	 * @since 1.5.0
	 * @return string
	 */
	public function get_product_name() {
		return sanitize_text_field( $this->license_obj->product_name );
	}

	/**
	 * Returns the url to the product used to buy the license key.
	 *
	 * @param bool $renewal_args
	 * @since 1.5.0
	 * @return string
	 */
	public function get_product_url( $renewal_args = false ) {

		$product_url = $this->license_obj->product_url;

		if ( $renewal_args ) {

			$product_url = add_query_arg(
				array(
					'order_id'   => $this->get_order_id(),
					'product_id' => $this->get_product_id(),
					'action'     => 'renew_license'
				),
				$product_url
			);

		}

		return Noptin_Addons::add_in_app_purchase_url_params( $product_url );
	}

	/**
	 * Returns the id of the product used to buy the license key.
	 *
	 * @since 1.5.0
	 * @return int
	 */
	public function get_product_id() {
		return (int) $this->license_obj->product_id;
	}

	/**
	 * Returns the license key.
	 *
	 * @since 1.5.0
	 * @return string
	 */
	public function get_license_key() {
		return sanitize_text_field( $this->license_obj->license_key );
	}

	/**
	 * Returns the expiry date of the license key.
	 *
	 * @since 1.5.0
	 * @return string
	 */
	public function get_expiration() {
		return sanitize_text_field( $this->license_obj->expiration );
	}

	/**
	 * Returns the creation date of the license key.
	 *
	 * @since 1.5.0
	 * @return string
	 */
	public function get_date_created() {
		return sanitize_text_field( $this->license_obj->date_created );
	}

	/**
	 * Returns the max activations of the license key.
	 *
	 * @since 1.5.0
	 * @return int
	 */
	public function get_max_activations() {
		return absint( $this->license_obj->max_activations );
	}

	/**
	 * Returns the activations count of the license key.
	 *
	 * @since 1.5.0
	 * @return int
	 */
	public function get_activations() {
		return count( $this->get_activated_on() );
	}

	/**
	 * Returns an array of sites that the license key is activated on.
	 *
	 * @since 1.5.0
	 * @return array
	 */
	public function get_activated_on() {
		return noptin_clean( (array) $this->license_obj->activated_on );
	}

	/**
	 * Checks if the license is maxed.
	 *
	 * @since 1.5.0
	 * @return bool
	 */
	public function is_maxed() {
		return ! $this->is_unlimited() && $this->get_activations() >= $this->get_max_activations();
	}

	/**
	 * Returns the download URL for a product.
	 *
	 * @since 1.5.0
	 * @return string
	 */
	public function get_download_url( $product_id = false ) {

		if ( empty( $this->license_obj->download_url ) ) {
			return 'https://noptin.com/my-account/downloads/';
		}

		if ( empty( $product_id ) ) {
			return $this->license_obj->download_url;
		}

		$download_url = remove_query_arg( 'download_addon', $this->license_obj->download_url );

		return add_query_arg( 'download_addon', (int) $product_id, $download_url );

	}

	/**
	 * Returns the activation URL for a license key.
	 *
	 * @since 1.5.0
	 * @return string
	 */
	public function get_activation_url() {

		return add_query_arg(
			array(
				'page'                      => 'noptin-addons',
				'section'                   => 'helper',
				'noptin-helper-activate'    => 1,
				'noptin-helper-product-key' => $this->get_license_key(),
				'noptin-helper-product-id'  => $this->get_product_id(),
				'noptin-helper-nonce'       => wp_create_nonce( 'activate:' . $this->get_license_key() ),
			),
			admin_url( 'admin.php' )
		);

	}

	/**
	 * Returns the deactivation URL for a license key.
	 *
	 * @since 1.5.0
	 * @return string
	 */
	public function get_deactivation_url() {

		return add_query_arg(
			array(
				'page'                      => 'noptin-addons',
				'section'                   => 'helper',
				'noptin-helper-deactivate'  => 1,
				'noptin-helper-product-key' => $this->get_license_key(),
				'noptin-helper-product-id'  => $this->get_product_id(),
				'noptin-helper-nonce'       => wp_create_nonce( 'deactivate:' . $this->get_license_key() ),
			),
			admin_url( 'admin.php' )
		);

	}

	/**
	 * Checks if the license exists or not.
	 *
	 * @since 1.5.0
	 * @return bool
	 */
	public function exists() {
		return ! empty( $this->license_obj );
	}

}
