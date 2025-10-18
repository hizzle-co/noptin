<?php
/**
 * Example: Using JOIN Queries with Hizzle Datastore
 * 
 * This example demonstrates how to use JOIN queries to relate
 * a customers collection with a payments collection.
 */

// This example assumes WordPress is loaded and the plugin is active.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'This file should be loaded in a WordPress environment.' );
}

use Hizzle\Store\Store;

/**
 * Step 1: Initialize the store with related collections
 */
function example_init_store_with_joins() {
	
	Store::init(
		'example_store',
		array(
			// Customers collection
			'customers' => array(
				'name'          => 'customers',
				'singular_name' => 'customer',
				'props'         => array(
					'id'         => array(
						'type'     => 'int',
						'length'   => 20,
						'nullable' => false,
					),
					'name'       => array(
						'type'     => 'varchar',
						'length'   => 255,
						'nullable' => false,
						'label'    => 'Customer Name',
					),
					'email'      => array(
						'type'     => 'varchar',
						'length'   => 255,
						'nullable' => false,
						'label'    => 'Email',
					),
					'date_created' => array(
						'type'     => 'datetime',
						'nullable' => false,
						'label'    => 'Date Created',
					),
				),
				'keys'          => array(
					'primary' => 'id',
					'unique'  => array('email'),
				),
				// Define the JOIN to payments collection
				'joins'         => array(
					'payments' => array(
						'collection'  => 'example_store_payments',  // Full collection name
						'on'          => 'id',                      // Local field (customers.id)
						'foreign_key' => 'customer_id',             // Foreign field (payments.customer_id)
						'type'        => 'LEFT',                    // Use LEFT JOIN to include customers without payments
					),
				),
			),
			
			// Payments collection
			'payments' => array(
				'name'          => 'payments',
				'singular_name' => 'payment',
				'props'         => array(
					'id'          => array(
						'type'     => 'int',
						'length'   => 20,
						'nullable' => false,
					),
					'customer_id' => array(
						'type'     => 'int',
						'length'   => 20,
						'nullable' => false,
						'label'    => 'Customer ID',
					),
					'amount'      => array(
						'type'     => 'decimal',
						'length'   => '10,2',
						'nullable' => false,
						'label'    => 'Amount',
					),
					'status'      => array(
						'type'     => 'varchar',
						'length'   => 50,
						'nullable' => false,
						'default'  => 'pending',
						'label'    => 'Status',
						'enum'     => array(
							'pending'   => 'Pending',
							'completed' => 'Completed',
							'failed'    => 'Failed',
						),
					),
					'date_created' => array(
						'type'     => 'datetime',
						'nullable' => false,
						'label'    => 'Date Created',
					),
				),
				'keys'          => array(
					'primary'     => 'id',
					'customer_id' => 'customer_id',
				),
			),
		)
	);
}

// Initialize the store
add_action( 'init', 'example_init_store_with_joins' );

/**
 * Step 2: Example queries using JOINs
 */

/**
 * Example 1: Get total revenue per customer
 */
function example_customer_revenue_report() {
	$store      = Store::instance( 'example_store' );
	$customers  = $store->get( 'customers' );
	
	// Query with JOIN to aggregate payment amounts
	$query = $customers->query(
		array(
			'join'         => array( 'payments' ),
			'aggregate'    => array(
				'payments.amount' => array(
					array(
						'function' => 'SUM',
						'as'       => 'total_revenue',
					),
					array(
						'function' => 'COUNT',
						'as'       => 'payment_count',
					),
				),
			),
			'groupby'      => 'id',
			'extra_fields' => array( 'name', 'email' ),
			'orderby'      => 'sum_amount',
			'order'        => 'DESC',
		)
	);
	
	$results = $query->get_aggregate();
	
	// Display results
	foreach ( $results as $row ) {
		echo sprintf(
			"Customer: %s (%s)\nTotal Revenue: $%s\nNumber of Payments: %d\n\n",
			$row->name,
			$row->email,
			number_format( $row->total_revenue, 2 ),
			$row->payment_count
		);
	}
	
	return $results;
}

/**
 * Example 2: Get customers with completed payments only
 */
function example_customers_with_completed_payments() {
	$store      = Store::instance( 'example_store' );
	$customers  = $store->get( 'customers' );
	
	// Query with JOIN and filter on payment status
	$query = $customers->query(
		array(
			'join'         => array( 'payments' ),
			'aggregate'    => array(
				'payments.amount' => array( 'SUM' ),
			),
			'groupby'      => 'id',
			'extra_fields' => array( 'name', 'email' ),
			// Note: Additional WHERE clause filtering on payments.status = 'completed'
			// would need to be added via filter hooks in a real implementation
		)
	);
	
	return $query->get_aggregate();
}

/**
 * Example 3: Monthly revenue breakdown
 */
function example_monthly_revenue() {
	$store      = Store::instance( 'example_store' );
	$customers  = $store->get( 'customers' );
	
	// Query with JOIN and date grouping
	$query = $customers->query(
		array(
			'join'      => array( 'payments' ),
			'aggregate' => array(
				'payments.amount' => array( 'SUM' ),
			),
			'groupby'   => array(
				'id'                    => null,
				'payments.date_created' => 'month',  // Group by month
			),
			'extra_fields' => array( 'name', 'email' ),
			'orderby'      => 'cast_payments.date_created',
			'order'        => 'DESC',
		)
	);
	
	return $query->get_aggregate();
}

/**
 * Example 4: Using JOINs via REST API
 * 
 * You can access the aggregate endpoint with JOINs:
 * 
 * GET /wp-json/example_store/v1/customers/aggregate?join[]=payments&aggregate[payments.amount][]=SUM&groupby=id
 */
function example_rest_api_documentation() {
	return array(
		'endpoint' => rest_url( 'example_store/v1/customers/aggregate' ),
		'params'   => array(
			'join'                        => array( 'payments' ),
			'aggregate[payments.amount][]' => 'SUM',
			'groupby'                     => 'id',
			'extra_fields[]'              => array( 'name', 'email' ),
		),
	);
}

/**
 * Example 5: Filter aggregate results in PHP
 */
function example_filter_aggregate_results() {
	$store      = Store::instance( 'example_store' );
	$customers  = $store->get( 'customers' );
	
	// Get all customer revenue
	$query = $customers->query(
		array(
			'join'         => array( 'payments' ),
			'aggregate'    => array(
				'payments.amount' => array( 'SUM' ),
			),
			'groupby'      => 'id',
			'extra_fields' => array( 'name', 'email' ),
		)
	);
	
	$all_results = $query->get_aggregate();
	
	// Filter for customers with revenue over $1000
	$high_value_customers = array_filter(
		$all_results,
		function( $customer ) {
			return isset( $customer->sum_amount ) && $customer->sum_amount > 1000;
		}
	);
	
	return $high_value_customers;
}

/**
 * Example 6: Create sample data for testing
 */
function example_create_sample_data() {
	$store = Store::instance( 'example_store' );
	
	// Create customers
	$customer_ids = array();
	$customers    = array(
		array( 'name' => 'John Doe', 'email' => 'john@example.com' ),
		array( 'name' => 'Jane Smith', 'email' => 'jane@example.com' ),
		array( 'name' => 'Bob Johnson', 'email' => 'bob@example.com' ),
	);
	
	foreach ( $customers as $customer_data ) {
		$customer_data['date_created'] = current_time( 'mysql', true );
		
		try {
			$customer = $store->get( 'customers' )->get( 0 );
			$customer->set_props( $customer_data );
			$customer->save();
			$customer_ids[] = $customer->get_id();
		} catch ( Exception $e ) {
			error_log( 'Error creating customer: ' . $e->getMessage() );
		}
	}
	
	// Create payments for customers
	$payments = array(
		array( 'customer_id' => $customer_ids[0], 'amount' => 100.50, 'status' => 'completed' ),
		array( 'customer_id' => $customer_ids[0], 'amount' => 250.75, 'status' => 'completed' ),
		array( 'customer_id' => $customer_ids[1], 'amount' => 500.00, 'status' => 'completed' ),
		array( 'customer_id' => $customer_ids[1], 'amount' => 150.25, 'status' => 'pending' ),
		array( 'customer_id' => $customer_ids[2], 'amount' => 75.00, 'status' => 'completed' ),
	);
	
	foreach ( $payments as $payment_data ) {
		$payment_data['date_created'] = current_time( 'mysql', true );
		
		try {
			$payment = $store->get( 'payments' )->get( 0 );
			$payment->set_props( $payment_data );
			$payment->save();
		} catch ( Exception $e ) {
			error_log( 'Error creating payment: ' . $e->getMessage() );
		}
	}
	
	return array(
		'customer_ids' => $customer_ids,
		'message'      => 'Sample data created successfully',
	);
}

/**
 * Register an admin action to create sample data
 */
add_action( 'admin_init', function() {
	if ( isset( $_GET['example_create_data'] ) && current_user_can( 'manage_options' ) ) {
		check_admin_referer( 'example_create_data' );
		$result = example_create_sample_data();
		wp_die( esc_html( $result['message'] ) );
	}
} );

/**
 * Register an admin action to run revenue report
 */
add_action( 'admin_init', function() {
	if ( isset( $_GET['example_run_report'] ) && current_user_can( 'manage_options' ) ) {
		check_admin_referer( 'example_run_report' );
		ob_start();
		example_customer_revenue_report();
		$output = ob_get_clean();
		wp_die( '<pre>' . esc_html( $output ) . '</pre>' );
	}
} );
