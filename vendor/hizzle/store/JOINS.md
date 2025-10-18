# JOIN Queries Documentation

This document explains how to use JOIN queries in the Hizzle Datastore library to relate collections together.

## Overview

JOIN queries allow you to combine data from multiple collections based on a common field. This is particularly useful for:
- Aggregating data across related collections
- Fetching related data in a single query
- Performing complex data analysis

## Configuration

### Defining JOINs in Collection Schema

When creating a collection, define the `joins` property to specify relationships with other collections:

```php
use Hizzle\Store\Store;

Store::init(
    'my_store',
    array(
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
                ),
                'email'      => array(
                    'type'     => 'varchar',
                    'length'   => 255,
                    'nullable' => false,
                ),
            ),
            'keys'          => array(
                'primary' => 'id',
            ),
            // Define JOINs
            'joins'         => array(
                'payments' => array(
                    'collection'  => 'my_store_payments',  // Full collection name
                    'on'          => 'id',                 // Local field (customers.id)
                    'foreign_key' => 'customer_id',        // Foreign field (payments.customer_id)
                    'type'        => 'LEFT',               // Optional: INNER, LEFT, or RIGHT
                ),
            ),
        ),
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
                ),
                'amount'      => array(
                    'type'     => 'decimal',
                    'length'   => '10,2',
                    'nullable' => false,
                ),
                'date'        => array(
                    'type'     => 'datetime',
                    'nullable' => false,
                ),
            ),
            'keys'          => array(
                'primary'     => 'id',
                'customer_id' => 'customer_id',
            ),
        ),
    )
);
```

## Usage

### Basic JOIN Query

To use JOINs in a query, pass the `join` parameter with an array of join aliases:

```php
$collection = Store::instance('my_store')->get('customers');

// Query with JOIN
$query = $collection->query(array(
    'join' => array('payments'),  // Include the payments join
));
```

### Aggregate Queries with JOINs

JOINs are particularly powerful with aggregate queries:

```php
// Total payments per customer
$query = $collection->query(array(
    'join'      => array('payments'),
    'aggregate' => array(
        'payments.amount' => array('SUM'),  // Use dot notation for joined fields
    ),
    'groupby'   => 'id',
    'extra_fields' => array('name', 'email'),  // Include customer fields
));

$results = $query->get_aggregate();
// Returns: array of objects with customer info and total payment amount
```

### Multiple Aggregate Functions

```php
$query = $collection->query(array(
    'join'      => array('payments'),
    'aggregate' => array(
        'payments.amount' => array('SUM', 'AVG', 'COUNT'),
        'id'              => array('COUNT'),  // Count customers
    ),
    'groupby'   => 'id',
    'extra_fields' => array('name'),
));
```

### Filtering with Joined Data

You can filter results based on joined table data:

```php
// Customers with payments over $100
$query = $collection->query(array(
    'join'      => array('payments'),
    'aggregate' => array(
        'payments.amount' => array('SUM'),
    ),
    'groupby'   => 'id',
));

// Then filter the aggregate results as needed
```

### Using Multiple JOINs

If a collection has multiple JOIN configurations, you can specify which ones to use:

```php
'joins' => array(
    'payments' => array(
        'collection'  => 'my_store_payments',
        'on'          => 'id',
        'foreign_key' => 'customer_id',
    ),
    'orders' => array(
        'collection'  => 'my_store_orders',
        'on'          => 'id',
        'foreign_key' => 'customer_id',
    ),
)

// Use both joins
$query = $collection->query(array(
    'join' => array('payments', 'orders'),
    // ... rest of query
));

// Or use just one
$query = $collection->query(array(
    'join' => 'payments',  // Single join as string
    // ... rest of query
));
```

## REST API Usage

### Aggregate Endpoint with JOINs

The aggregate endpoint supports the `join` parameter:

```bash
GET /wp-json/my_store/v1/customers/aggregate?join[]=payments&aggregate[payments.amount][]=SUM&groupby=id
```

Example with curl:

```bash
curl -X GET "https://example.com/wp-json/my_store/v1/customers/aggregate" \
  -H "Content-Type: application/json" \
  -G \
  --data-urlencode "join[]=payments" \
  --data-urlencode "aggregate[payments.amount][]=SUM" \
  --data-urlencode "groupby=id" \
  --data-urlencode "extra_fields[]=name" \
  --data-urlencode "extra_fields[]=email"
```

## Field References

When working with joined tables, reference fields using either:

1. **Dot notation**: `payments.amount` (standard SQL style)
2. **Double underscore**: `payments__amount` (alternative for URL encoding)

Both formats are supported in:
- Aggregate field specifications
- GROUP BY clauses
- Extra fields lists

## JOIN Types

Three types of JOINs are supported:

- **INNER JOIN** (default): Returns only rows with matching values in both tables
- **LEFT JOIN**: Returns all rows from the left table, with NULL for non-matching right table rows
- **RIGHT JOIN**: Returns all rows from the right table, with NULL for non-matching left table rows

Specify the type in your JOIN configuration:

```php
'joins' => array(
    'payments' => array(
        'collection'  => 'my_store_payments',
        'on'          => 'id',
        'foreign_key' => 'customer_id',
        'type'        => 'LEFT',  // or 'INNER' or 'RIGHT'
    ),
)
```

## Examples

### Example 1: Customer Revenue Report

```php
$customers = Store::instance('my_store')->get('customers');

$query = $customers->query(array(
    'join'         => array('payments'),
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
    'extra_fields' => array('name', 'email'),
));

$results = $query->get_aggregate();

foreach ($results as $row) {
    echo "Customer: {$row->name}\n";
    echo "Total Revenue: \${$row->total_revenue}\n";
    echo "Number of Payments: {$row->payment_count}\n\n";
}
```

### Example 2: Monthly Revenue by Customer

```php
$query = $customers->query(array(
    'join'      => array('payments'),
    'aggregate' => array(
        'payments.amount' => array('SUM'),
    ),
    'groupby'   => array(
        'id'              => null,
        'payments.date'   => 'month',  // Group by month
    ),
    'extra_fields' => array('name'),
));
```

### Example 3: Top Customers

```php
// Find customers with highest total payments
$query = $customers->query(array(
    'join'      => array('payments'),
    'aggregate' => array(
        'payments.amount' => array('SUM'),
    ),
    'groupby'   => 'id',
    'extra_fields' => array('name', 'email'),
    'orderby'   => 'sum_amount',
    'order'     => 'DESC',
    'per_page'  => 10,
));
```

## Best Practices

1. **Only join when needed**: JOINs add complexity and can impact performance. Only include joins when you need to aggregate or filter data from related tables.

2. **Use specific join names**: When a collection has multiple joins defined, explicitly specify which ones to use rather than joining all tables.

3. **Index foreign keys**: Ensure foreign key fields are indexed in both tables for better performance.

4. **Use LEFT JOINs carefully**: LEFT JOINs can return NULL values. Make sure your aggregate functions handle NULLs appropriately.

5. **Test performance**: Always test query performance with realistic data volumes, especially with multiple JOINs.

## Troubleshooting

### JOIN not working

- Verify the collection names are correct (use full collection name: `namespace_name`)
- Check that foreign key and local key field names match exactly
- Ensure both collections are registered with the Store

### Invalid field errors

- Use the correct format for joined fields: `join_alias.field_name`
- Verify the field exists in the joined collection
- Check for typos in field names

### Performance issues

- Add indexes to JOIN key fields
- Limit the result set with WHERE conditions
- Consider caching aggregate results for frequently-accessed data
- Use INNER JOINs when possible (faster than LEFT JOINs)
