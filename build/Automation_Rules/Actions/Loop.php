<?php
/**
 * Loops over a selected data source.
 *
 * @since 3.4.6
 */

namespace Hizzle\Noptin\Automation_Rules\Actions;

defined( 'ABSPATH' ) || exit;

use Hizzle\Noptin\Objects\Store;

/**
 * Loops over a selected data source.
 */
class Loop extends Action {

	/**
	 * Maximum remote file size to download, in bytes.
	 *
	 * @var int
	 */
	private const DEFAULT_MAX_REMOTE_FILE_SIZE = 5242880; // 5 MB.

	/**
	 * Maximum JSON/XML file size to load fully into memory, in bytes.
	 *
	 * @var int
	 */
	private const DEFAULT_MAX_MEMORY_FILE_SIZE = 5242880; // 5 MB.

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'loop';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'Loop', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'Repeat steps for each item in a list, file, number range, or collection.', 'newsletter-optin-box' );
	}

	/**
	 * Retrieve the action's image.
	 *
	 * @return string
	 */
	public function get_image() {
		return 'controls-repeat';
	}

	/**
	 * @inheritdoc
	 */
	public function get_settings() {
		$options = array(
			'numbers' => __( 'Numbers', 'newsletter-optin-box' ),
			'csv'     => __( 'CSV', 'newsletter-optin-box' ),
			'xml'     => __( 'XML', 'newsletter-optin-box' ),
			'json'    => __( 'JSON', 'newsletter-optin-box' ),
		);

		$settings = array(
			'loop_over'      => array(
				'label'       => __( 'Loop over', 'newsletter-optin-box' ),
				'el'          => 'select',
				'default'     => 'numbers',
				'options'     => $options,
				'description' => __( 'Choose the type of data this loop should iterate over.', 'newsletter-optin-box' ),
				'can_map'     => false,
			),

			// Settings for number range loops.
			'number_start'   => array(
				'label'            => __( 'Start', 'newsletter-optin-box' ),
				'el'               => 'input',
				'type'             => 'number',
				'default'          => 1,
				'conditions'       => array(
					array(
						'key'   => 'action_settings.loop_over',
						'value' => 'numbers',
					),
				),
				'customAttributes' => array(
					'min' => 1,
				),
				'can_map'          => false,
			),
			'number_end'     => array(
				'label'            => __( 'End', 'newsletter-optin-box' ),
				'el'               => 'input',
				'type'             => 'number',
				'default'          => 10,
				'conditions'       => array(
					array(
						'key'   => 'action_settings.loop_over',
						'value' => 'numbers',
					),
				),
				'can_map'          => false,
				'customAttributes' => array(
					'min' => 1,
				),
			),
			'number_step'    => array(
				'label'            => __( 'Step', 'newsletter-optin-box' ),
				'el'               => 'input',
				'type'             => 'number',
				'default'          => 1,
				'conditions'       => array(
					array(
						'key'   => 'action_settings.loop_over',
						'value' => 'numbers',
					),
				),
				'can_map'          => false,
				'customAttributes' => array(
					'min' => 1,
				),
			),

			// csv/json/xml.
			'file'           => array(
				'label'       => __( 'File URL/Path', 'newsletter-optin-box' ),
				'el'          => 'input',
				'type'        => 'text',
				'description' => __( 'Enter the URL, server path, or merge tag that resolves to file contents.', 'newsletter-optin-box' ),
				'conditions'  => array(
					array(
						'key'      => 'action_settings.loop_over',
						'operator' => 'includes',
						'value'    => array( 'csv', 'xml', 'json' ),
					),
				),
			),

			// Maximum number of times to loop.
			'max_iterations' => array(
				'label'            => __( 'Max iterations', 'newsletter-optin-box' ),
				'el'               => 'input',
				'type'             => 'number',
				'default'          => 0,
				'description'      => __( 'Set a limit to prevent infinite loops. Set to 0 for unlimited.', 'newsletter-optin-box' ),
				'customAttributes' => array(
					'min' => 0,
				),
			),
		);

		foreach ( apply_filters( 'noptin_email_editor_objects', array() ) as $collection => $config ) {
			if ( empty( $config['can_list'] ) ) {
				continue;
			}

			$settings['loop_over']['options'][ $collection ] = $config['label'];

			// Add general filters.
			if ( ! empty( $config['filters'] ) ) {
				foreach ( (array) $config['filters'] as $key => $filter ) {
					// This only makes sense for emails.
					if ( 'since_last_send' === $key ) {
						continue;
					}

					$settings[ $collection . '.' . $key ] = array_merge(
						$filter,
						array(
							'conditions' => array_merge(
								$filter['conditions'] ?? array(),
								array(
									array(
										'key'   => 'action_settings.loop_over',
										'value' => $collection,
									),
								)
							),
						)
					);
				}
			}

			// Add orderOptions related collections.
			if ( ! empty( $config['orderOptions'] ) ) {
				$settings[ $collection . '.orderby/order' ] = array(
					'label'      => 'Order by',
					'el'         => 'select',
					'options'    => $config['orderOptions'],
					'conditions' => array(
						array(
							'key'   => 'action_settings.loop_over',
							'value' => $collection,
						),
					),
				);
			}
		}

		return $settings;
	}

	/**
	 * @inheritdoc
	 */
	public function run( $subject, $rule, $args ) {
		$children = $rule->get_children();
		$body     = null;
		$end      = null;

		// A loop will have at most two children: the body (first non-end child) and the end marker.
		foreach ( $children as $child ) {
			if ( 'loop_end' === $child->get_action_id() ) {
				$end = $child;
			} elseif ( ! $body ) {
				$body = $child;
			}
		}

		$trigger = $rule->get_trigger();
		$type    = $rule->get_action_setting( 'loop_over' );
		$return  = true;

		if ( $body && $body->get_status() ) {
			try {
				if ( 'numbers' === $type ) {
					$this->loop_numbers( $subject, $trigger, $body, $rule, $args );
				} elseif ( $this->get_collection( $type ) ) {
					$this->loop_collection( $subject, $trigger, $body, $rule, $args, $type );
				} elseif ( in_array( $type, array( 'csv', 'xml', 'json' ), true ) ) {
					$this->loop_files( $subject, $trigger, $body, $rule, $args, $type );
				} else {
					throw new \Exception( sprintf( 'Unsupported loop type: %s', esc_html( $type ) ) );
				}
			} catch ( \Throwable $e ) {
				$return = $e->getMessage();
			}
		}

		// If there's an end child, run it after the loop finishes.
		if ( ! empty( $end ) && $end->get_status() ) {
			$end_action = Main::get( 'loop_end' );
			$end->maybe_run( $subject, $trigger, $end_action, $args );
		}

		return $return;
	}

	/**
	 * @inheritdoc
	 */
	public function should_auto_run_child_rules() {
		return false;
	}

	/**
	 * Runs the loop body over a number range.
	 *
	 * @param mixed                                            $subject The subject.
	 * @param \Hizzle\Noptin\Automation_Rules\Triggers\Trigger $trigger The trigger.
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule  $body Body rule.
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule  $rule Loop rule.
	 * @param array                                            $args Trigger args.
	 */
	private function loop_numbers( $subject, $trigger, $body, $rule, $args ) {
		$max       = max( 0, (int) $rule->get_action_setting( 'max_iterations' ) );
		$start     = (int) $rule->get_action_setting( 'number_start' );
		$end       = (int) $rule->get_action_setting( 'number_end' );
		$step      = max( 1, absint( $rule->get_action_setting( 'number_step' ) ) );
		$direction = $start <= $end ? 1 : -1;
		$step      = $step * $direction;
		$action    = $body->get_action();
		$index     = 0;

		for ( $number = $start; $direction > 0 ? $number <= $end : $number >= $end; $number += $step ) {
			if ( $max > 0 && $index >= $max ) {
				break;
			}

			$body->maybe_run(
				$subject,
				$trigger,
				$action,
				array_merge(
					$args,
					array(
						'extra_args' => array_merge(
							$args['extra_args'] ?? array(),
							array(
								$this->prepend_merge_tag_prefix( $rule, 'current' ) => $number,
								$this->prepend_merge_tag_prefix( $rule, 'index' )   => $index,
							)
						),
					)
				)
			);

			++$index;
		}
	}

	/**
	 * Runs the loop body over a collection.
	 *
	 * @param mixed                                            $subject The subject.
	 * @param \Hizzle\Noptin\Automation_Rules\Triggers\Trigger $trigger The trigger.
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule  $body Body rule.
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule  $rule Loop rule.
	 * @param array                                            $args Trigger args.
	 * @param string                                           $type Collection type.
	 */
	private function loop_collection( $subject, $trigger, $body, $rule, $args, $type ) {
		$collection = $this->get_collection( $type );

		if ( ! $collection ) {
			throw new \Exception( sprintf( 'Collection not found for type: %s', esc_html( $type ) ) );
		}

		$max     = max( 0, (int) $rule->get_action_setting( 'max_iterations' ) );
		$filters = $this->prepare_collection_filters( $rule->get_action_setting( $collection->type ), $args );

		// Each collection can have its own pagination/limit query key,
		// so we set the common ones.
		if ( $max > 0 ) {
			foreach ( array( 'number', 'per_page', 'limit' ) as $filter ) {
				if ( empty( $filters[ $filter ] ) ) {
					$filters[ $filter ] = $max;
				}
			}
		}

		// orderby/order.
		if ( ! empty( $filters['orderby/order'] ) ) {
			$order = explode( '/', (string) $filters['orderby/order'] );

			if ( ! empty( $order[0] ) ) {
				$filters['orderby'] = $order[0];
			}

			if ( ! empty( $order[1] ) ) {
				$filters['order'] = $order[1];
			}

			unset( $filters['orderby/order'] );
		}

		$items = $collection->get_all( $filters );

		if ( ! is_array( $items ) && ! $items instanceof \Traversable ) {
			throw new \Exception( sprintf( 'Failed to retrieve items for collection type: %s', esc_html( $type ) ) );
		}

		$prefix    = $this->prepend_merge_tag_prefix( $rule, $collection->type );
		$action    = $body->get_action();
		$processed = 0;

		foreach ( $items as $item ) {
			// In case the limit we set in filters did not work.
			if ( $max > 0 && $processed >= $max ) {
				break;
			}

			$body->maybe_run(
				$subject,
				$trigger,
				$action,
				array_merge(
					$args,
					array(
						'provided_collections' => array_merge(
							$args['provided_collections'] ?? array(),
							array(
								$prefix => $item,
							)
						),
						'extra_args'           => array_merge(
							$args['extra_args'] ?? array(),
							array(
								$this->prepend_merge_tag_prefix( $rule, 'index' ) => $processed,
							)
						),
					)
				)
			);

			++$processed;
		}
	}

	/**
	 * Runs the loop body over a parsed file or merge-tag payload.
	 *
	 * @param mixed                                            $subject The subject.
	 * @param \Hizzle\Noptin\Automation_Rules\Triggers\Trigger $trigger The trigger.
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule  $body Body rule.
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule  $rule Loop rule.
	 * @param array                                            $args Trigger args.
	 * @param string                                           $type File type.
	 * @return true
	 */
	private function loop_files( $subject, $trigger, $body, $rule, $args, $type ) {
		if ( ! in_array( $type, array( 'csv', 'xml', 'json' ), true ) ) {
			throw new \Exception( sprintf( 'Unsupported loop type for file data retrieval: %s', esc_html( $type ) ) );
		}

		$file_path = (string) $this->replace_smart_tags(
			$rule->get_action_setting( 'file' ),
			$args['smart_tags'] ?? null
		);

		if ( '' === trim( $file_path ) ) {
			throw new \Exception( 'File URL/Path cannot be empty.' );
		}

		$source = $this->prepare_loop_file_source( $file_path, $type );

		try {
			switch ( $type ) {
				case 'csv':
					return $this->loop_csv( $subject, $trigger, $body, $rule, $args, $source['file_path'] );

				case 'xml':
					return $this->loop_xml( $subject, $trigger, $body, $rule, $args, $source['file_path'] );

				case 'json':
					return $this->loop_json( $subject, $trigger, $body, $rule, $args, $source['file_path'] );
			}
		} finally {
			if ( ! empty( $source['temp_file'] ) && file_exists( $source['temp_file'] ) ) {
				wp_delete_file( $source['temp_file'] );
			}
		}

		return true;
	}

	/**
	 * Fetches a collection.
	 *
	 * @param string $type Collection type.
	 * @return \Hizzle\Noptin\Objects\Collection|null
	 */
	private function get_collection( $type ) {
		if ( ! class_exists( Store::class ) ) {
			return null;
		}

		$collection = Store::get( $type );
		return $collection && ! empty( $collection->can_list ) ? $collection : null;
	}

	/**
	 * Prepares collection filters from flattened action settings.
	 *
	 * @param array $settings Action settings.
	 * @param array $args Trigger args.
	 * @return array
	 */
	private function prepare_collection_filters( $settings, $args ) {

		if ( ! is_array( $settings ) ) {
			return array();
		}

		$filters = array();

		foreach ( $settings as $key => $value ) {
			$filters[ $key ] = $this->replace_smart_tags( $value, $args['smart_tags'] ?? null );
		}

		return $filters;
	}

	/**
	 * Replaces smart tags in a value.
	 *
	 * @param mixed $value Value.
	 * @param \Hizzle\Noptin\Automation_Rules\Smart_Tags|null $smart_tags
	 * @return mixed
	 */
	private function replace_smart_tags( $value, $smart_tags ) {
		if ( isset( $smart_tags ) && is_callable( array( $smart_tags, 'replace_in_content' ) ) ) {
			return $smart_tags->replace_in_content( $value );
		}

		return $value;
	}

	/**
	 * Loops over a CSV file.
	 *
	 * @param mixed                                            $subject The subject.
	 * @param \Hizzle\Noptin\Automation_Rules\Triggers\Trigger $trigger The trigger.
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule  $body Body rule.
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule  $rule Loop rule.
	 * @param array                                            $args Trigger args.
	 * @param string                                           $file_path File path.
	 * @return true
	 */
	private function loop_csv( $subject, $trigger, $body, $rule, $args, $file_path ) {
		$handle = fopen( $file_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		if ( ! $handle ) {
			throw new \Exception( sprintf( 'Failed to open file for reading: %s', esc_html( $file_path ) ) );
		}

		try {
			$headers = fgetcsv( $handle, 0, ',', '"', '' );

			if ( false === $headers ) {
				return true;
			}

			$columns = $this->prepare_csv_columns( $headers, $rule );

			if ( empty( $columns ) ) {
				throw new \Exception( 'CSV header row cannot be empty.' );
			}

			$max       = max( 0, (int) $rule->get_action_setting( 'max_iterations' ) );
			$action    = $body->get_action();
			$processed = 0;

			while ( false !== ( $row = fgetcsv( $handle, 0, ',', '"', '' ) ) ) {
				if ( $max > 0 && $processed >= $max ) {
					break;
				}

				if ( $this->is_empty_csv_row( $row ) ) {
					continue;
				}

				$row  = array_slice( array_pad( $row, count( $columns ), '' ), 0, count( $columns ) );
				$item = array_combine( $columns, $row );

				if ( false === $item ) {
					continue;
				}

				$item[ $this->prepend_merge_tag_prefix( $rule, 'index' ) ] = $processed;

				$body->maybe_run(
					$subject,
					$trigger,
					$action,
					array_merge(
						$args,
						array(
							'extra_args' => array_merge(
								$args['extra_args'] ?? array(),
								$item
							),
						)
					)
				);

				++$processed;
			}
		} finally {
			fclose( $handle );
		}

		return true;
	}

	/**
	 * Loops over a JSON file.
	 *
	 * Normal JSON files are decoded in one pass after checking the file size.
	 * For very large JSON datasets, use newline-delimited JSON files with a .jsonl or .ndjson extension.
	 *
	 * @param mixed                                            $subject The subject.
	 * @param \Hizzle\Noptin\Automation_Rules\Triggers\Trigger $trigger The trigger.
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule  $body Body rule.
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule  $rule Loop rule.
	 * @param array                                            $args Trigger args.
	 * @param string                                           $file_path File path.
	 * @return true
	 */
	private function loop_json( $subject, $trigger, $body, $rule, $args, $file_path ) {
		if ( $this->is_json_lines_file( $file_path ) ) {
			return $this->loop_json_lines( $subject, $trigger, $body, $rule, $args, $file_path );
		}

		$this->assert_file_size_under_limit( $file_path, $this->get_max_memory_file_size(), 'JSON' );

		$data = wp_json_file_decode( $file_path, array( 'associative' => true ) );

		if ( ! is_array( $data ) ) {
			throw new \Exception( sprintf( 'Failed to read JSON file or JSON is not an array: %s', esc_html( $file_path ) ) );
		}

		if ( ! wp_is_numeric_array( $data ) ) {
			throw new \Exception( 'JSON data must be an array of items to loop over.' );
		}

		return $this->loop_file_items( $subject, $trigger, $body, $rule, $args, $data );
	}

	/**
	 * Loops over a newline-delimited JSON file.
	 *
	 * @param mixed                                            $subject The subject.
	 * @param \Hizzle\Noptin\Automation_Rules\Triggers\Trigger $trigger The trigger.
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule  $body Body rule.
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule  $rule Loop rule.
	 * @param array                                            $args Trigger args.
	 * @param string                                           $file_path File path.
	 * @return true
	 */
	private function loop_json_lines( $subject, $trigger, $body, $rule, $args, $file_path ) {
		$handle = fopen( $file_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		if ( ! $handle ) {
			throw new \Exception( sprintf( 'Failed to open JSON lines file for reading: %s', esc_html( $file_path ) ) );
		}

		$max       = max( 0, (int) $rule->get_action_setting( 'max_iterations' ) );
		$action    = $body->get_action();
		$processed = 0;
		$line_no   = 0;

		try {
			while ( false !== ( $line = fgets( $handle ) ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fgets
				++$line_no;

				$line = trim( $line );

				if ( '' === $line ) {
					continue;
				}

				if ( $max > 0 && $processed >= $max ) {
					break;
				}

				$item = json_decode( $line, true );

				if ( JSON_ERROR_NONE !== json_last_error() ) {
					throw new \Exception(
						sprintf(
							'Invalid JSON on line %1$d: %2$s',
							$line_no,
							json_last_error_msg()
						)
					);
				}

				$extra_args = $this->prepare_file_extra_args( $item, $rule );
				$extra_args[ $this->prepend_merge_tag_prefix( $rule, 'index' ) ] = $processed;

				$body->maybe_run(
					$subject,
					$trigger,
					$action,
					array_merge(
						$args,
						array(
							'extra_args' => array_merge(
								$args['extra_args'] ?? array(),
								$extra_args
							),
						)
					)
				);

				++$processed;
			}
		} finally {
			fclose( $handle );
		}

		return true;
	}

	/**
	 * Loops over an XML file.
	 *
	 * Uses XMLReader for <item> nodes so RSS-like feeds and large item lists do not require
	 * loading the whole XML document into memory. If no <item> nodes are found, the method
	 * falls back to parsing the full document after enforcing the memory file-size limit.
	 *
	 * @param mixed                                            $subject The subject.
	 * @param \Hizzle\Noptin\Automation_Rules\Triggers\Trigger $trigger The trigger.
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule  $body Body rule.
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule  $rule Loop rule.
	 * @param array                                            $args Trigger args.
	 * @param string                                           $file_path File path.
	 * @return true
	 */
	private function loop_xml( $subject, $trigger, $body, $rule, $args, $file_path ) {
		if ( class_exists( '\XMLReader' ) ) {
			$streamed = $this->loop_xml_items( $subject, $trigger, $body, $rule, $args, $file_path );

			if ( $streamed ) {
				return true;
			}
		}

		$this->assert_file_size_under_limit( $file_path, $this->get_max_memory_file_size(), 'XML' );

		$data = file_get_contents( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( false === $data ) {
			throw new \Exception( sprintf( 'Failed to read XML file: %s', esc_html( $file_path ) ) );
		}

		return $this->loop_file_items( $subject, $trigger, $body, $rule, $args, $this->parse_xml( $data ) );
	}

	/**
	 * Streams XML <item> nodes.
	 *
	 * @param mixed                                            $subject The subject.
	 * @param \Hizzle\Noptin\Automation_Rules\Triggers\Trigger $trigger The trigger.
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule  $body Body rule.
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule  $rule Loop rule.
	 * @param array                                            $args Trigger args.
	 * @param string                                           $file_path File path.
	 * @return bool True when at least one item node was found and processed.
	 */
	private function loop_xml_items( $subject, $trigger, $body, $rule, $args, $file_path ) {
		$reader = new \XMLReader();

		if ( ! $reader->open( $file_path, null, LIBXML_NOCDATA | LIBXML_NONET ) ) {
			throw new \Exception( sprintf( 'Failed to open XML file: %s', esc_html( $file_path ) ) );
		}

		$max       = max( 0, (int) $rule->get_action_setting( 'max_iterations' ) );
		$action    = $body->get_action();
		$processed = 0;
		$found     = false;

		try {
			while ( $reader->read() ) {
				if ( \XMLReader::ELEMENT !== $reader->nodeType || 'item' !== $reader->localName ) {
					continue;
				}

				$found = true;

				if ( $max > 0 && $processed >= $max ) {
					break;
				}

				$item = $this->xml_string_to_array( $reader->readOuterXML() );

				if ( array() === $item ) {
					continue;
				}

				$extra_args = $this->prepare_file_extra_args( $item, $rule );
				$extra_args[ $this->prepend_merge_tag_prefix( $rule, 'index' ) ] = $processed;

				$body->maybe_run(
					$subject,
					$trigger,
					$action,
					array_merge(
						$args,
						array(
							'extra_args' => array_merge(
								$args['extra_args'] ?? array(),
								$extra_args
							),
						)
					)
				);

				++$processed;
			}
		} finally {
			$reader->close();
		}

		return $found;
	}

	/**
	 * Loops over parsed file items.
	 *
	 * @param mixed                                            $subject The subject.
	 * @param \Hizzle\Noptin\Automation_Rules\Triggers\Trigger $trigger The trigger.
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule  $body Body rule.
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule  $rule Loop rule.
	 * @param array                                            $args Trigger args.
	 * @param array|\Traversable                               $items Parsed file items.
	 * @return true
	 */
	private function loop_file_items( $subject, $trigger, $body, $rule, $args, $items ) {
		if ( ! is_array( $items ) && ! $items instanceof \Traversable ) {
			throw new \Exception( 'File data must be an array of items to loop over.' );
		}

		$max       = max( 0, (int) $rule->get_action_setting( 'max_iterations' ) );
		$action    = $body->get_action();
		$processed = 0;

		foreach ( $items as $item ) {
			if ( $max > 0 && $processed >= $max ) {
				break;
			}

			$extra_args = $this->prepare_file_extra_args( $item, $rule );
			$extra_args[ $this->prepend_merge_tag_prefix( $rule, 'index' ) ] = $processed;

			$body->maybe_run(
				$subject,
				$trigger,
				$action,
				array_merge(
					$args,
					array(
						'extra_args' => array_merge(
							$args['extra_args'] ?? array(),
							$extra_args
						),
					)
				)
			);

			++$processed;
		}

		return true;
	}

	/**
	 * Prepares a file source for looping.
	 *
	 * @param string $path_or_url File path, URL, or raw payload.
	 * @param string $type File type.
	 * @return array{file_path:string,temp_file:string}
	 */
	private function prepare_loop_file_source( $path_or_url, $type ) {
		$path_or_url = trim( (string) $path_or_url );
		$parsed      = $this->parse_file_path( $path_or_url );

		if ( ! empty( $parsed['remote_file'] ) ) {
			$temp_file = $this->download_remote_file_to_temp( $parsed['file_path'] );

			return array(
				'file_path' => $temp_file,
				'temp_file' => $temp_file,
			);
		}

		if ( ! empty( $parsed['file_path'] ) ) {
			if ( ! is_readable( $parsed['file_path'] ) || ! is_file( $parsed['file_path'] ) ) {
				throw new \Exception( sprintf( 'File is not readable: %s', esc_html( $parsed['file_path'] ) ) );
			}

			return array(
				'file_path' => $parsed['file_path'],
				'temp_file' => '',
			);
		}

		$temp_file = $this->create_temp_file_from_string( $path_or_url, $type );

		return array(
			'file_path' => $temp_file,
			'temp_file' => $temp_file,
		);
	}

	/**
	 * Downloads a remote file to a temporary local file.
	 *
	 * @param string $url Remote URL.
	 * @return string Temporary file path.
	 */
	private function download_remote_file_to_temp( $url ) {
		$this->load_file_functions();

		$url = esc_url_raw( trim( (string) $url ) );

		if ( ! wp_http_validate_url( $url ) ) {
			throw new \Exception( 'The remote file URL is not valid or is not allowed.' );
		}

		$max_size = $this->get_max_remote_file_size();
		$path     = wp_parse_url( $url, PHP_URL_PATH );
		$filename = $path ? basename( $path ) : 'noptin-loop-file';
		$tmp_file = wp_tempnam( sanitize_file_name( $filename ) );

		if ( ! $tmp_file ) {
			throw new \Exception( 'Could not create a temporary file.' );
		}

		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 15,
				'redirection'         => 3,
				'stream'              => true,
				'filename'            => $tmp_file,
				'limit_response_size' => $max_size,
				'headers'             => array(
					'Accept' => 'text/csv,text/plain,application/json,application/xml,text/xml,*/*;q=0.1',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_delete_file( $tmp_file );
			throw new \Exception( esc_html( $response->get_error_message() ) );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			wp_delete_file( $tmp_file );

			throw new \Exception(
				sprintf(
					'The remote file could not be downloaded. HTTP status: %d.',
					$code
				)
			);
		}

		$file_size = file_exists( $tmp_file ) ? (int) filesize( $tmp_file ) : 0; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_filesize

		if ( $file_size <= 0 ) {
			wp_delete_file( $tmp_file );
			throw new \Exception( 'The remote file is empty.' );
		}

		if ( $max_size > 0 && $file_size >= $max_size ) {
			wp_delete_file( $tmp_file );
			throw new \Exception( 'The remote file is too large.' );
		}

		return $tmp_file;
	}

	/**
	 * Creates a temporary file from raw file contents.
	 *
	 * @param string $contents Raw file contents.
	 * @param string $extension Preferred extension.
	 * @return string Temporary file path.
	 */
	private function create_temp_file_from_string( $contents, $extension = 'txt' ) {
		$this->load_file_functions();

		$tmp_file = wp_tempnam( 'noptin-loop.' . sanitize_key( $extension ) );

		if ( ! $tmp_file ) {
			throw new \Exception( 'Could not create a temporary file.' );
		}

		$written = file_put_contents( $tmp_file, $contents ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		if ( false === $written ) {
			wp_delete_file( $tmp_file );
			throw new \Exception( 'Could not write raw data to a temporary file.' );
		}

		return $tmp_file;
	}

	/**
	 * Parse file path/url and see if it is remote or local.
	 *
	 * @param string $path_or_url File path or URL.
	 * @return array{remote_file:bool,file_path:string}
	 */
	private function parse_file_path( $path_or_url ) {
		$path_or_url = trim( (string) $path_or_url );

		if ( '' === $path_or_url ) {
			return array(
				'remote_file' => false,
				'file_path'   => '',
			);
		}

		if ( '//' === substr( $path_or_url, 0, 2 ) ) {
			$path_or_url = ( is_ssl() ? 'https:' : 'http:' ) . $path_or_url;
		}

		if ( preg_match( '#^https?://#i', $path_or_url ) ) {
			$local_file = $this->resolve_local_url_to_path( $path_or_url );

			if ( '' !== $local_file ) {
				return array(
					'remote_file' => false,
					'file_path'   => $local_file,
				);
			}

			return array(
				'remote_file' => true,
				'file_path'   => $path_or_url,
			);
		}

		$local_file = $this->resolve_local_path( $path_or_url );

		return array(
			'remote_file' => false,
			'file_path'   => $local_file,
		);
	}

	/**
	 * Resolves a local URL to a readable local file path.
	 *
	 * @param string $url File URL.
	 * @return string Empty string if the URL does not map to a local readable file.
	 */
	private function resolve_local_url_to_path( $url ) {
		$uploads = wp_upload_dir();

		$candidates = array();

		if ( empty( $uploads['error'] ) && ! empty( $uploads['baseurl'] ) && ! empty( $uploads['basedir'] ) ) {
			$candidates[ $uploads['baseurl'] ] = $uploads['basedir'];
		}

		$candidates[ content_url( '/' ) ]           = WP_CONTENT_DIR;
		$candidates[ network_site_url( '/', 'https' ) ] = ABSPATH;
		$candidates[ network_site_url( '/', 'http' ) ]  = ABSPATH;
		$candidates[ site_url( '/', 'https' ) ]     = ABSPATH;
		$candidates[ site_url( '/', 'http' ) ]      = ABSPATH;

		foreach ( $candidates as $base_url => $base_dir ) {
			$file_path = $this->url_to_local_path( $url, $base_url, $base_dir );

			if ( '' !== $file_path ) {
				return $file_path;
			}
		}

		return '';
	}

	/**
	 * Converts a URL under a given local base URL to a filesystem path.
	 *
	 * @param string $url File URL.
	 * @param string $base_url Local base URL.
	 * @param string $base_dir Local base directory.
	 * @return string Empty string if the URL does not resolve to a local file.
	 */
	private function url_to_local_path( $url, $base_url, $base_dir ) {
		$url_parts  = wp_parse_url( $url );
		$base_parts = wp_parse_url( $base_url );

		if ( empty( $url_parts['host'] ) || empty( $base_parts['host'] ) ) {
			return '';
		}

		if ( strtolower( $url_parts['host'] ) !== strtolower( $base_parts['host'] ) ) {
			return '';
		}

		if ( (int) ( $url_parts['port'] ?? 0 ) !== (int) ( $base_parts['port'] ?? 0 ) ) {
			return '';
		}

		$url_path  = '/' . ltrim( rawurldecode( $url_parts['path'] ?? '' ), '/' );
		$base_path = '/' . trim( rawurldecode( $base_parts['path'] ?? '' ), '/' );
		$base_path = '/' === $base_path ? '' : $base_path;

		if ( '' !== $base_path && 0 !== strpos( trailingslashit( $url_path ), trailingslashit( $base_path ) ) ) {
			return '';
		}

		$relative_path = '' === $base_path ? ltrim( $url_path, '/' ) : ltrim( substr( $url_path, strlen( $base_path ) ), '/' );

		if ( '' === $relative_path ) {
			return '';
		}

		return $this->resolve_local_path( trailingslashit( $base_dir ) . $relative_path, $base_dir );
	}

	/**
	 * Resolves a local path to a readable real path.
	 *
	 * @param string      $path File path.
	 * @param string|null $base_dir Optional base dir the file must be inside.
	 * @return string Empty string if the path is not a readable file.
	 */
	private function resolve_local_path( $path, $base_dir = null ) {
		$path = rawurldecode( strtok( trim( (string) $path ), '?#' ) );

		if ( '' === $path ) {
			return '';
		}

		$candidates = array( $path );

		if ( 0 === strpos( $path, '/wp-content/' ) ) {
			$candidates[] = WP_CONTENT_DIR . substr( $path, 11 );
		}

		$candidates[] = trailingslashit( ABSPATH ) . ltrim( $path, '/' );

		foreach ( array_unique( $candidates ) as $candidate ) {
			$real_path = realpath( $candidate );

			if ( ! $real_path || ! is_file( $real_path ) || ! is_readable( $real_path ) ) {
				continue;
			}

			if ( null !== $base_dir && ! $this->is_path_inside_directory( $real_path, $base_dir ) ) {
				continue;
			}

			return wp_normalize_path( $real_path );
		}

		return '';
	}

	/**
	 * Checks whether a file is inside a directory.
	 *
	 * @param string $file File path.
	 * @param string $directory Directory path.
	 * @return bool
	 */
	private function is_path_inside_directory( $file, $directory ) {
		$real_file      = realpath( $file );
		$real_directory = realpath( $directory );

		if ( ! $real_file || ! $real_directory ) {
			return false;
		}

		$real_file      = wp_normalize_path( $real_file );
		$real_directory = trailingslashit( wp_normalize_path( $real_directory ) );

		return 0 === strpos( $real_file, $real_directory );
	}

	/**
	 * Parses XML data.
	 *
	 * @param string $data Raw XML.
	 * @return array
	 */
	private function parse_xml( $data ) {
		if ( '' === trim( (string) $data ) ) {
			return array();
		}

		$data = $this->xml_string_to_array( $data );

		if ( isset( $data['item'] ) ) {
			return wp_is_numeric_array( $data['item'] ) ? $data['item'] : array( $data['item'] );
		}

		return wp_is_numeric_array( $data ) ? $data : array( $data );
	}

	/**
	 * Converts XML string to an array.
	 *
	 * @param string $data Raw XML.
	 * @return array
	 */
	private function xml_string_to_array( $data ) {
		if ( ! function_exists( 'simplexml_load_string' ) || '' === trim( (string) $data ) ) {
			return array();
		}

		$previous = libxml_use_internal_errors( true );
		$xml      = simplexml_load_string( $data, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( false === $xml ) {
			return array();
		}

		$json = wp_json_encode( $xml );
		$data = json_decode( $json, true );

		return is_array( $data ) ? $data : array();
	}

	/**
	 * Prepares extra args for file rows.
	 *
	 * @param mixed                                           $item Current row/item.
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule Rule.
	 * @return array
	 */
	private function prepare_file_extra_args( $item, $rule ) {
		if ( ! is_array( $item ) ) {
			return array(
				$this->prepend_merge_tag_prefix( $rule, 'current' ) => $item,
			);
		}

		$extra_args = array();

		foreach ( $item as $header => $value ) {
			if ( is_int( $header ) ) {
				$key = 'column_' . ( $header + 1 );
				$extra_args[ $this->prepend_merge_tag_prefix( $rule, $key ) ] = $value;
				continue;
			}

			$tag = $this->normalize_extra_arg_key( $header );

			if ( '' !== $tag ) {
				$extra_args[ $this->prepend_merge_tag_prefix( $rule, $tag ) ] = $value;
			}
		}

		return $extra_args;
	}

	/**
	 * Prepares normalized CSV column merge tags.
	 *
	 * @param array                                           $headers CSV headers.
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule Rule.
	 * @return array
	 */
	private function prepare_csv_columns( $headers, $rule ) {
		$columns = array();
		$seen    = array();

		foreach ( array_values( $headers ) as $index => $header ) {
			$key = $this->normalize_extra_arg_key( $header );

			if ( '' === $key ) {
				$key = 'column_' . ( $index + 1 );
			}

			$base_key = $key;
			$suffix   = 2;

			while ( isset( $seen[ $key ] ) ) {
				$key = $base_key . '_' . $suffix;
				++$suffix;
			}

			$seen[ $key ] = true;
			$columns[]    = $this->prepend_merge_tag_prefix( $rule, $key );
		}

		return $columns;
	}

	/**
	 * Checks whether a CSV row is empty.
	 *
	 * @param array $row CSV row.
	 * @return bool
	 */
	private function is_empty_csv_row( $row ) {
		foreach ( $row as $value ) {
			if ( null !== $value && '' !== trim( (string) $value ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Normalizes a file header into a merge-tag key.
	 *
	 * @param string $key Header.
	 * @return string
	 */
	private function normalize_extra_arg_key( $key ) {
		$key = strtolower( (string) $key );
		$key = preg_replace( '/[^a-z0-9]+/', '_', $key );
		return trim( (string) $key, '_' );
	}

	/**
	 * Checks if a file is newline-delimited JSON.
	 *
	 * @param string $file_path File path.
	 * @return bool
	 */
	private function is_json_lines_file( $file_path ) {
		$extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );

		return in_array( $extension, array( 'jsonl', 'ndjson' ), true );
	}

	/**
	 * Ensures a file is small enough to load fully into memory.
	 *
	 * @param string $file_path File path.
	 * @param int    $max_size Maximum allowed size in bytes.
	 * @param string $label Human-readable file type.
	 */
	private function assert_file_size_under_limit( $file_path, $max_size, $label ) {
		if ( $max_size <= 0 || ! file_exists( $file_path ) ) {
			return;
		}

		$file_size = (int) filesize( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_filesize

		if ( $file_size > $max_size ) {
			throw new \Exception(
				sprintf(
					'%1$s file is too large to load into memory. Use a smaller file or a streaming-friendly format.',
					esc_html( $label )
				)
			);
		}
	}

	/**
	 * Loads WordPress file helpers when running outside wp-admin.
	 */
	private function load_file_functions() {
		if ( ! function_exists( 'wp_tempnam' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
	}

	/**
	 * Retrieves the max remote file size.
	 *
	 * @return int
	 */
	private function get_max_remote_file_size() {
		return max(
			0,
			(int) apply_filters( 'noptin_loop_max_remote_file_size', self::DEFAULT_MAX_REMOTE_FILE_SIZE )
		);
	}

	/**
	 * Retrieves the max file size that can be loaded into memory.
	 *
	 * @return int
	 */
	private function get_max_memory_file_size() {
		return max(
			0,
			(int) apply_filters( 'noptin_loop_max_memory_file_size', self::DEFAULT_MAX_MEMORY_FILE_SIZE )
		);
	}

	/**
	 * Retrieves the provided collection smart-tag prefix.
	 *
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule Rule.
	 * @param string                                          $merge_tag
	 * @return string
	 */
	private function prepend_merge_tag_prefix( $rule, $merge_tag ) {
		return $this->get_merge_tag_prefix( $rule ) . '.' . $merge_tag;
	}

	/**
	 * Returns merge tag prefix.
	 *
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule Rule.
	 * @return string
	 */
	private function get_merge_tag_prefix( $rule ) {
		$uuid = $rule->get_meta( 'uuid' );
		return $uuid ? $uuid : $rule->get_id();
	}
}
