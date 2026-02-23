<?php

namespace Hizzle\Store;

defined( 'ABSPATH' ) || exit;

/**
 * Handles background exports in batches via WP-Cron.
 */
class Export {

	const CRON_HOOK          = 'hizzle_store_background_export';
	const CLEANUP_HOOK       = 'hizzle_store_background_export_cleanup';
	const OPTION_PREFIX      = 'hizzle_store_background_export_';
	const DEFAULT_BATCH_SIZE = 50;
	const LOCK_TTL           = 80;
	const MAX_RUNTIME        = 20;
	const MEMORY_THRESHOLD   = 0.8;

	private static $records = array();

	/**
	 * Registers cron handlers.
	 */
	public static function init() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'run' ) );
		add_action( self::CLEANUP_HOOK, array( __CLASS__, 'cleanup_job' ), 10, 1 );
		add_action( 'wp_ajax_' . self::CRON_HOOK, array( __CLASS__, 'maybe_handle_via_ajax' ) );
		add_action( 'wp_ajax_nopriv_' . self::CRON_HOOK, array( __CLASS__, 'maybe_handle_via_ajax' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_handle_download' ) );
	}

	/**
	 * Queues a new background export job.
	 *
	 * @param string $store_namespace Store namespace without the /v1 suffix.
	 * @param string $collection Collection name.
	 * @param array $query_args Query arguments.
	 * @param string $email The email to notify when the export is complete.
	 * @return string|false Job ID on success, false on failure.
	 */
	public static function queue( $store_namespace, $collection, $query_args, $email = '' ) {
		$job_id     = uniqid( time() );
		$query_args = self::sanitize_query_args( (array) $query_args );
		$uploads    = wp_upload_dir( null, false );
		$path       = trailingslashit( $uploads['basedir'] ) . trailingslashit( sanitize_key( $store_namespace ) ) . sanitize_key( $collection );

		if ( ! wp_mkdir_p( $path ) ) {
			return false;
		}

		$job = array(
			'id'              => $job_id,
			'store_namespace' => $store_namespace,
			'collection'      => $collection,
			'query_args'      => self::sanitize_query_args( (array) $query_args ),
			'email'           => $email,
			'created_at'      => time(),
			'fields'          => wp_parse_list( $query_args['__fields'] ?? '' ),
			'file_path'       => trailingslashit( $path ) . sanitize_key( $store_namespace ) . '-' . sanitize_key( $collection ) . '-' . sanitize_key( $job_id ) . '.csv',
		);

		// Maybe protect the export directory with an .htaccess file.
		if ( ! file_exists( $path . '/.htaccess' ) ) {
			self::htaccess_protect( $path );
		}

		update_option( self::get_job_option_name( $job_id ), $job, false );
		self::schedule_next( $job_id );

		do_action( 'hizzle_store_background_export_queued', $job_id, $job );

		return $job_id;
	}

	/**
	 * Schedules the next batch for a job.
	 *
	 * @param string $job_id Job ID.
	 */
	private static function schedule_next( $job_id ) {
		if ( ! wp_next_scheduled( self::CRON_HOOK, array( $job_id ) ) ) {
			wp_schedule_single_event( time(), self::CRON_HOOK, array( $job_id ) );
		}

		wp_remote_get(
			add_query_arg(
				array(
					'action'      => self::CRON_HOOK,
					'job_id'      => $job_id,
					'_ajax_nonce' => wp_create_nonce( self::CRON_HOOK . '_' . $job_id ),
				),
				admin_url( 'admin-ajax.php' )
			),
			array(
				'timeout'   => 0.01,
				'blocking'  => false,
				'sslverify' => false,
				'cookies'   => $_COOKIE,
			)
		);
	}

	/**
	 * Runs a rescheduled batch process.
	 *
	 */
	public static function maybe_handle_via_ajax() {

		// Don't lock up other requests while processing.
		session_write_close();

		$job_id = $_GET['job_id'] ?? '';

		if ( empty( $job_id ) ) {
			wp_die();
		}

		check_ajax_referer( self::CRON_HOOK . '_' . $job_id );
		self::run( $job_id );

		wp_die();
	}

	/**
	 * Runs the queue.
	 *
	 * Pass each queue item to the task handler, while remaining
	 * within server memory and time limit constraints.
	 */
	public static function run( $job_id ) {
		// If already running, bail.
		if ( ! self::acquire_lock( $job_id ) ) {
			// Set a backup CRON event in case the lock is stale and wasn't cleared.
			if ( wp_next_scheduled( self::CRON_HOOK, array( $job_id ) ) ) {
				wp_clear_scheduled_hook( self::CRON_HOOK, array( $job_id ) );
			}

			wp_schedule_single_event( time() + self::LOCK_TTL, self::CRON_HOOK, array( $job_id ) );
			return;
		}

		$job = get_option( self::get_job_option_name( $job_id ) );

		if ( empty( $job ) || empty( $job['store_namespace'] ) || empty( $job['collection'] ) ) {
			return;
		}

		try {
			$store = Store::instance( $job['store_namespace'] );
		} catch ( Store_Exception $e ) {
			self::fail_job( $job_id, $job, 'We could not find the store associated with this export job.' );
			return;
		}

		$collection = $store->get( $job['collection'] );
		if ( empty( $collection ) ) {
			self::fail_job( $job_id, $job, 'We could not find the collection associated with this export job.' );
			return;
		}

		$start_time = microtime( true );
		$fields     = empty( $job['fields'] ) ? array_keys( $collection->get_props() ) : $job['fields'];

		// Raise the memory limit.
		wp_raise_memory_limit();

		// Raise the time limit.
		self::raise_time_limit( self::MAX_RUNTIME + 10 );

		// Run the queue.
		do {

			$record = self::get_next_record( $collection, $job_id, $job['query_args'] );

			if ( empty( $record ) ) {
				self::finish_job( $job_id, $job, 'complete' );
				return;
			}

			$to_save = array();

			foreach ( $fields as $field ) {
				$value = $record->get( $field, 'edit' );

				// If value is a date, convert it to the ISO8601 format.
				if ( $value instanceof \DateTime ) {
					$value = $value->format( 'Y-m-d\TH:i:sP' );

					// If value contains 00:00:00, remove the time.
					if ( false !== strpos( $value, '00:00:00' ) ) {
						$value = substr( $value, 0, 10 );
					}
				}

				if ( is_bool( $value ) ) {
					$value = (int) $value;
				}

				// Check if this is an array of scalars.
				if ( is_array( $value ) && ! is_array( current( $value ) ) ) {
					$value = implode( ',', $value );
				}

				// Convert non-scalar values to JSON.
				if ( ! is_scalar( $value ) ) {
					$value = maybe_serialize( $value );
				}

				$to_save[ $field ] = $value;
			}

			self::save_record( $job['file_path'], $to_save, $fields );
			++$job['query_args']['offset'];
		} while ( microtime( true ) - $start_time < self::MAX_RUNTIME && ! self::is_memory_near_limit() );

		// Release the lock.
		self::release_lock( $job_id );

		// Continue with the next batch.
		update_option( self::get_job_option_name( $job_id ), $job, false );
		self::schedule_next( $job_id );
	}

	/**
	 * Returns the next record.
	 *
	 * @param Collection $collection Collection instance.
	 * @return Record|null
	 */
	private static function get_next_record( $collection, $job_id, $params ) {

		// If this is the first run, or if there are no more records to process, fetch the next batch.
		if ( empty( self::$records[ $job_id ] ) ) {
			$query                    = $collection->query( $params );
			self::$records[ $job_id ] = $query->get_results();
		}

		if ( empty( self::$records[ $job_id ] ) ) {
			return null;
		}

		return array_shift( self::$records[ $job_id ] );
	}

	/**
	 * Removes query args that should not persist across batches.
	 *
	 * @param array $query_args Query args.
	 * @return array
	 */
	private static function sanitize_query_args( $query_args ) {
		$to_unset = array( 'background_export', 'paged', 'page' );
		foreach ( $to_unset as $key ) {
			if ( array_key_exists( $key, $query_args ) ) {
				unset( $query_args[ $key ] );
			}
		}

		// Set per page.
		$query_args['per_page'] = (int) apply_filters(
			'hizzle_store_background_export_batch_size',
			self::DEFAULT_BATCH_SIZE,
			$query_args
		);

		// Start with offset 1.
		$query_args['offset'] = 1;

		// Ensure full objects are fetched so response normalization matches foreground exports.
		$query_args['fields'] = 'all';

		return $query_args;
	}

	/**
	 * Writes a record to the export CSV file.
	 *
	 * @param string $file_path File path.
	 * @param array $to_save Record data.
	 * @param array $fields Field order.
	 */
	private static function save_record( $file_path, $to_save, $fields ) {

		$is_new_file = ! file_exists( $file_path ) || 0 === filesize( $file_path );
		$handle      = fopen( $file_path, 'ab' );
		if ( false === $handle ) {
			return;
		}

		if ( function_exists( 'flock' ) ) {
			flock( $handle, LOCK_EX );
		}

		if ( $is_new_file ) {
			fputcsv( $handle, array_map( array( __CLASS__, 'escape_csv_value' ), $fields ) );
		}

		$row = array();
		foreach ( $fields as $field ) {
			$row[] = self::escape_csv_value( $to_save[ $field ] ?? '' );
		}

		fputcsv( $handle, $row );
		fflush( $handle );

		if ( function_exists( 'flock' ) ) {
			flock( $handle, LOCK_UN );
		}

		fclose( $handle );
	}

	/**
	 * Writes an .htaccess file to prevent direct access to export files.
	 *
	 * @param string $path Directory path.
	 */
	private static function htaccess_protect( $path ) {
		$htaccess_path = trailingslashit( $path ) . '.htaccess';
		$rules = array(
			'# Block direct access to exports.',
			'Options -Indexes',
			'<IfModule mod_authz_core.c>',
			'Require all denied',
			'</IfModule>',
			'<IfModule !mod_authz_core.c>',
			'Deny from all',
			'</IfModule>',
			'',
		);

		file_put_contents( $htaccess_path, implode( "\n", $rules ), LOCK_EX );
	}

	/**
	 * Sanitizes a CSV value for spreadsheet formula injection.
	 *
	 * @param mixed $value Value to sanitize.
	 * @return string
	 */
	private static function escape_csv_value( $value ) {
		if ( null === $value ) {
			$value = '';
		}

		if ( is_bool( $value ) ) {
			$value = $value ? '1' : '0';
		}

		$value   = (string) $value;
		$trimmed = ltrim( $value );

		if ( '' !== $trimmed && ( preg_match( '/^[=+\-@]/', $trimmed ) || 0 === strpos( $trimmed, "\t" ) ) ) {
			$value = "'" . $value;
		}

		return $value;
	}

	/**
	 * Finishes a job and removes its stored state.
	 *
	 * @param string $job_id Job ID.
	 * @param array $job Job data.
	 */
	private static function finish_job( $job_id, $job ) {
		wp_clear_scheduled_hook( self::CRON_HOOK, array( $job_id ) );
		self::release_lock( $job_id );

		self::schedule_cleanup( $job_id );

		$email = isset( $job['email'] ) ? sanitize_email( $job['email'] ) : '';
		if ( $email && ! empty( $job['file_path'] ) ) {
			$file_path = $job['file_path'];
			$download  = add_query_arg( array( 'hizzle_export' => $job_id ), home_url( '/' ) );

			$subject = 'Your export is ready';
			$body    = 'Your export is complete.';
			$body   .= "\n\n" . sprintf( 'Download: %s', esc_url_raw( $download ) );

			$attachments = array();
			if ( file_exists( $file_path ) ) {
				$size = filesize( $file_path );
				if ( false !== $size && $size <= 10 * 1024 * 1024 ) {
					$attachments[] = $file_path;
				}
			}

			wp_mail( $email, $subject, $body, array(), $attachments );
		}

		do_action( 'hizzle_store_background_export_finished', $job_id, $job );
	}

	/**
	 * Handles download requests for completed exports.
	 */
	public static function maybe_handle_download() {
		$job_id = isset( $_GET['hizzle_export'] ) ? sanitize_text_field( wp_unslash( $_GET['hizzle_export'] ) ) : '';
		if ( empty( $job_id ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		$job = get_option( self::get_job_option_name( $job_id ) );
		if ( empty( $job ) || empty( $job['file_path'] ) ) {
			wp_die( 'Export not found.', 404 );
		}

		$file_path = $job['file_path'];
		if ( ! file_exists( $file_path ) ) {
			wp_die( 'Export file missing.', 404 );
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . basename( $file_path ) . '"' );
		header( 'Content-Length: ' . filesize( $file_path ) );

		readfile( $file_path );
		exit;
	}

	/**
	 * Schedules a cleanup for a completed export.
	 *
	 * @param string $job_id Job ID.
	 */
	private static function schedule_cleanup( $job_id ) {
		if ( ! wp_next_scheduled( self::CLEANUP_HOOK, array( $job_id ) ) ) {
			wp_schedule_single_event( time() + MONTH_IN_SECONDS, self::CLEANUP_HOOK, array( $job_id ) );
		}
	}

	/**
	 * Deletes export file and job option after the retention period.
	 *
	 * @param string $job_id Job ID.
	 */
	public static function cleanup_job( $job_id ) {
		$job = get_option( self::get_job_option_name( $job_id ) );
		if ( ! empty( $job['file_path'] ) && file_exists( $job['file_path'] ) ) {
			@unlink( $job['file_path'] );
		}

		delete_option( self::get_job_option_name( $job_id ) );
	}

	/**
	 * Fails a job, cleans up, and notifies the requester.
	 *
	 * @param string $job_id Job ID.
	 * @param array $job Job data.
	 * @param string $message Failure message.
	 */
	private static function fail_job( $job_id, $job, $message ) {
		delete_option( self::get_job_option_name( $job_id ) );
		wp_clear_scheduled_hook( self::CRON_HOOK, array( $job_id ) );
		self::release_lock( $job_id );

		$email = isset( $job['email'] ) ? sanitize_email( $job['email'] ) : '';
		if ( $email ) {
			wp_mail( $email, 'Export failed', wp_kses_post( $message ) );
		}

		do_action( 'hizzle_store_background_export_failed', $job_id, $job, $message );
	}

	/**
	 * Attempts to raise the PHP timeout for time intensive processes.
	 *
	 * Only allows raising the existing limit and prevents lowering it.
	 *
	 * @param int $limit The time limit in seconds.
	 */
	private static function raise_time_limit( $limit = 0 ) {
		$limit              = (int) $limit;
		$max_execution_time = (int) ini_get( 'max_execution_time' );

		/*
		 * If the max execution time is already unlimited (zero), or if it exceeds or is equal to the proposed
		 * limit, there is no reason for us to make further changes (we never want to lower it).
		 */
		if ( 0 === $max_execution_time || ( $max_execution_time >= $limit && 0 !== $limit ) ) {
			return;
		}

		if ( function_exists( 'set_time_limit' ) && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) { // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
			@set_time_limit( $limit ); // @codingStandardsIgnoreLine
		}
	}

	/**
	 * Checks if memory is exceeded (more than 90% used)
	 *
	 * @return bool
	 */
	private static function is_memory_near_limit() {

		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			// Sensible default.
			$memory_limit = '128M';
		}

		if ( ! $memory_limit || -1 === intval( $memory_limit ) ) {
			// Unlimited, set to 32GB.
			$memory_limit = '32G';
		}

		$memory_limit   = wp_convert_hr_to_bytes( $memory_limit ) * self::MEMORY_THRESHOLD;
		$current_memory = memory_get_usage( true );

		return $current_memory >= $memory_limit;
	}

	/**
	 * Builds the option name for a job.
	 *
	 * @param string $job_id Job ID.
	 * @return string
	 */
	private static function get_job_option_name( $job_id ) {
		return self::OPTION_PREFIX . $job_id;
	}

	/**
	 * Lock process
	 *
	 * Lock the process so that multiple instances can't run simultaneously.
	 */
	private static function acquire_lock( $job_id ) {
		$lock_key = self::get_job_option_name( $job_id ) . '_lock';
		$lock     = get_option( $lock_key );

		// Delete stale lock.
		if ( $lock && ( time() - $lock ) >= self::MAX_RUNTIME + MINUTE_IN_SECONDS ) {
			self::release_lock( $job_id );
		}

		return add_option( $lock_key, time(), '', 'no' );
	}

	/**
	 * Unlock process
	 *
	 * Unlock the process so that other instances can spawn.
	 */
	private static function release_lock( $job_id ) {
		delete_option( self::get_job_option_name( $job_id ) . '_lock' );
	}
}
