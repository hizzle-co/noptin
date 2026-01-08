# datastore

This is currently in Beta so expect the API to change alot.

## Installation

Install via Composer:

```bash
composer require hizzle/store
```

## Features

- **CRUD Operations**: Create, read, update, and delete records
- **Query Builder**: Powerful query builder with filtering, sorting, and pagination
- **Aggregate Functions**: Support for SUM, AVG, COUNT, MIN, MAX with grouping
- **JOIN Queries**: Relate collections together for complex data analysis
- **REST API**: Automatic REST API endpoints for all collections
- **Meta Fields**: Support for custom meta fields with multiple values
- **Custom Post Types**: Integrate with WordPress custom post types

## Quick Start

### Recommended: Using Main Class

The `Main` class provides a simplified API for interacting with your store:

```php
use Hizzle\Store\Main;

// Get or create store instance
$db = Main::instance('my_store');

// Initialize store with collections
$db->init_store(
    array(
        'orders' => array(
            'object'        => 'Order',
            'singular_name' => 'order',
            'props'         => array(
                'id'          => array(
                    'type'        => 'BIGINT',
                    'length'      => 20,
                    'nullable'    => false,
                    'extra'       => 'AUTO_INCREMENT',
                    'description' => 'Order ID',
                ),
                'customer_id' => array(
                    'type'        => 'BIGINT',
                    'length'      => 20,
                    'nullable'    => false,
                    'description' => 'Customer ID',
                ),
                'total'       => array(
                    'type'        => 'DECIMAL',
                    'length'      => '10,2',
                    'nullable'    => false,
                    'description' => 'Order total',
                ),
                'status'      => array(
                    'type'        => 'VARCHAR',
                    'length'      => 20,
                    'default'     => 'pending',
                    'description' => 'Order status',
                ),
            ),
            'keys'          => array(
                'primary'     => array( 'id' ),
                'customer_id' => array( 'customer_id' ),
                'status'      => array( 'status' ),
            ),
            'labels'        => array(
                'name'          => __( 'Orders', 'textdomain' ),
                'singular_name' => __( 'Order', 'textdomain' ),
            ),
        ),
    )
);

// Work with records
$order = $db->get('orders', 123);
$orders = $db->query('orders', array('status' => 'completed'));
```

### Alternative: Using Store Class Directly

```php
use Hizzle\Store\Store;

// Initialize a store
$store = new Store(
    'my_store',
    array(
        'payments' => array(
            // This object must extend Hizzle\Store\Record
            'object'        => 'Payment',
            'singular_name' => 'payment',
            'props'         => array(
                'id'                  => array(
                    'type'        => 'BIGINT',
                    'length'      => 20,
                    'nullable'    => false,
                    'extra'       => 'AUTO_INCREMENT',
                    'description' => 'Payment ID',
                ),
                'customer_id'       => array(
                    'type'        => 'BIGINT',
                    'length'      => 20,
                    'nullable'    => false,
                    'description' => 'Customer ID',
                ),
                /* ... */
            ),
            'joins'         => array(
                'customers' => array(
                    'collection' => 'my_store_customers',
                    'on'         => 'customer_id',
                    'type'       => 'LEFT',
                ),
                'plans'     => array(
                    'collection' => 'my_store_plans',
                    'on'         => 'plan_id',
                    'type'       => 'LEFT',
                ),
                'products'  => array(
                    'collection' => 'my_store_products',
                    // We are assuming that the above payments schema has
                    // No 'plan_id' property, so we join via plans table
                    // Which is already joined above
                    'on'         => 'plans.product_id',
                    'type'       => 'LEFT',
                ),
            ),
            'keys'          => array(
                'primary'             => array( 'id' ),
                'customer_id'         => array( 'customer_id' ),
                'subscription_id'     => array( 'subscription_id' ),
                'status'              => array( 'status' ),
                'date_created_status' => array( 'date_created', 'status' ),
                'unique'              => array( 'uuid', 'transaction_id' ),
            ),
            'labels'        => array(
                'name'          => __( 'Payments', 'textdomain' ),
                'singular_name' => __( 'Payment', 'textdomain' ),
                'add_new'       => __( 'Add New', 'textdomain' ),
                'add_new_item'  => __( 'Add New Payment', 'textdomain' ),
                'edit_item'     => __( 'Overview', 'textdomain' ),
                'new_item'      => __( 'Add Payment', 'textdomain' ),
                'view_item'     => __( 'View Payment', 'textdomain' ),
                'view_items'    => __( 'View Payments', 'textdomain' ),
                'search_items'  => __( 'Search payments', 'textdomain' ),
                'not_found'     => __( 'No payments found.', 'textdomain' ),
                'import'        => __( 'Import Payments', 'textdomain' ),
            ),
        ),
        'customers' => array( /* ... */ ),
        /** Other collections **/
    )
);
```

### Working with Records

#### Create Records

```php
// Using Main class (recommended)
$db = Main::instance('my_store');

// Get the collection first
$collection = Store::instance('my_store')->get('payments');

// Create a new payment
$payment = $collection->create(array(
    'customer_id' => 123,
    'amount' => 99.99,
    'status' => 'completed',
));

// Get the payment ID
$payment_id = $payment->get_id();
```

#### Read Records

```php
// Using Main class (recommended)
$db = Main::instance('my_store');

// Get a single record by ID
$payment = $db->get('payments', $payment_id);

if ($payment && !is_wp_error($payment)) {
    echo $payment->get('amount'); // 99.99
    echo $payment->get('status'); // completed
}

// Get ID by a specific property
$payment_id = $db->get_id_by_prop('transaction_id', 'txn_abc123', 'payments');

// Using Collection directly
// Throws \Hizzle\Store\Store_Exception on failure
$collection = Store::instance('my_store')->get('payments');
$payment = $collection->get($payment_id);

// Check if a record exists
if ($collection->exists($payment_id)) {
    // Record exists
}
```

#### Update Records

```php
// Get the record using Main class
$db = Main::instance('my_store');
$payment = $db->get('payments', $payment_id);

if ($payment && !is_wp_error($payment)) {
    $payment->set('status', 'refunded');
    $payment->set('refund_date', current_time('mysql'));
    $payment->save();
}

// Or update via collection
// Throws \Hizzle\Store\Store_Exception on failure
$collection = Store::instance('my_store')->get('payments');
$collection->update($payment_id, array(
    'status' => 'refunded',
    'refund_date' => current_time('mysql'),
));
```

#### Delete Records

```php
// Using Main class (recommended)
$db = Main::instance('my_store');

// Delete records matching criteria
$deleted = $db->delete_where(
    array(
        'status' => 'pending',
        'customer_id' => 123,
    ),
    'payments'
);

// Delete all records (use with caution!)
$db->delete_all('payments');

// Or delete via record object
$payment = $db->get('payments', $payment_id);
if ($payment && !is_wp_error($payment)) {
    $payment->delete();
}
```

### Querying Records

```php
// Using Main class (recommended)
$db = Main::instance('my_store');

// Basic query - returns results
$payments = $db->query('payments', array(
    'status' => 'completed',
    'customer_id' => 123,
    'per_page' => 10,
    'page' => 1,
));

// Count records
$count = $db->query('payments', array(
    'status' => 'completed',
), 'count');

// Aggregate query
$results = $db->query('payments', array(
    'aggregate' => array(
        'amount' => array('SUM', 'AVG', 'COUNT'),
    ),
    'groupby' => 'status',
), 'aggregate');

// Get Query object for more control
$query = $db->query('payments', array(
    'status' => 'completed',
), 'query');

$payments = $query->get_results();
$total = $query->get_total();

// Using Collection directly
$collection = Store::instance('my_store')->get('payments');
$query = $collection->query(array(
    'status' => 'completed',
    'customer_id' => 123,
    'per_page' => 10,
    'page' => 1,
));

$payments = $query->get_results();
$total = $query->get_total();

// Complex query with date filters
$payments = $db->query('payments', array(
    'status' => array('completed', 'pending'),
    'amount_min' => 50,
    'date_created_after' => '2026-01-01',
    'orderby' => 'date_created',
    'order' => 'DESC',
));
```

### Working with Metadata

```php
// Using Main class (recommended)
$db = Main::instance('my_store');

// Add meta data
$db->add_record_meta($payment_id, 'gateway', 'stripe', false, 'payments');

// Get meta data
$gateway = $db->get_record_meta($payment_id, 'gateway', true, 'payments');

// Update meta data
$db->update_record_meta($payment_id, 'gateway', 'paypal', '', 'payments');

// Delete meta data
$db->delete_record_meta($payment_id, 'gateway', '', 'payments');

// Delete all metadata for a record
$db->delete_all_record_meta($payment_id, 'payments');

// Delete all metadata by key across all records
$db->delete_all_meta_by_key('old_field', 'payments');

// Check if meta exists
if ($db->record_meta_exists($payment_id, 'gateway', 'payments')) {
    // Meta exists
}

// Using Collection directly
$collection = Store::instance('my_store')->get('payments');
$collection->add_record_meta($payment_id, 'gateway', 'stripe');
$gateway = $collection->get_record_meta($payment_id, 'gateway', true);
$collection->update_record_meta($payment_id, 'gateway', 'paypal');
$collection->delete_record_meta($payment_id, 'gateway');
```

### Error Handling

```php
use Hizzle\Store\Main;

// Main class automatically converts exceptions to WP_Error
$db = Main::instance('my_store');
$payment = $db->get('payments', $payment_id);

if (is_wp_error($payment)) {
    error_log($payment->get_error_message());
} else {
    // Work with the payment
    echo $payment->get('amount');
}

// When using Store/Collection directly, use try/catch
try {
    $collection = Store::instance('my_store')->get('payments');
    $payment = $collection->get($payment_id);
    
    // Do something with the payment
    
} catch (\Hizzle\Store\Store_Exception $e) {
    error_log($e->getMessage());
    
    // Or convert to WP_Error
    $error = new WP_Error(
        $e->getErrorCode(),
        $e->getMessage(),
        $e->getErrorData()
    );
}
```

### JOIN Queries

Define relationships between collections:

```php
'customers' => array(
    'status' => 'complete',
    // ... other config
    'joins' => array(
        'payments' => array(
            'collection' => 'my_store_payments',
            'on' => 'id',
            'foreign_key' => 'customer_id',
            'type' => 'LEFT',
        ),
    ),
)
```

Use JOINs in aggregate queries:

```php
$query = $collection->query(array(
    'join' => array('payments'),
    'aggregate' => array(
        'payments.amount' => array('SUM', 'COUNT'),
    ),
    'groupby' => 'id',
));
```

## Documentation

### API Reference

Complete documentation for all components is available in the [docs](docs/) folder:

- **Core Classes**
  - [Store](docs/store.md) - Main store management
  - [Collection](docs/collection.md) - Collection CRUD operations
  - [Record](docs/record.md) - Individual record operations
  - [Query](docs/query.md) - Query builder and filtering

- **Supporting Classes**
  - [Prop](docs/prop.md) - Property definitions
  - [REST_Controller](docs/rest-controller.md) - REST API endpoints
  - [List_Table](docs/list-table.md) - WordPress admin tables
  - [Webhooks](docs/webhooks.md) - Event-driven webhooks

- **Utilities**
  - [Date_Time](docs/date-time.md) - Date/time handling
  - [Store_Exception](docs/store-exception.md) - Exception handling

### Guides

- [JOIN Queries Guide](docs/joins.md) - Comprehensive guide to using JOINs
- [Example Code](example-joins.php) - Working examples with JOINs

## Requirements

- PHP >= 5.3.0
- WordPress >= 4.7.0
