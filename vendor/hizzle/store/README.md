# datastore

This is currently in Beta so expect the API to change alot.

## Features

- **CRUD Operations**: Create, read, update, and delete records
- **Query Builder**: Powerful query builder with filtering, sorting, and pagination
- **Aggregate Functions**: Support for SUM, AVG, COUNT, MIN, MAX with grouping
- **JOIN Queries**: Relate collections together for complex data analysis
- **REST API**: Automatic REST API endpoints for all collections
- **Meta Fields**: Support for custom meta fields with multiple values
- **Custom Post Types**: Integrate with WordPress custom post types

## Quick Start

### Basic Usage

```php
use Hizzle\Store\Store;

// Initialize a store
Store::init('my_store', array(
    'customers' => array(
        'name' => 'customers',
        'singular_name' => 'customer',
        'props' => array(
            'id' => array(
                'type' => 'int',
                'length' => 20,
                'nullable' => false,
            ),
            'name' => array(
                'type' => 'varchar',
                'length' => 255,
                'nullable' => false,
            ),
            'email' => array(
                'type' => 'varchar',
                'length' => 255,
                'nullable' => false,
            ),
        ),
        'keys' => array(
            'primary' => 'id',
        ),
    ),
));
```

### JOIN Queries (New!)

Define relationships between collections:

```php
'customers' => array(
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

- [JOIN Queries Guide](JOINS.md) - Comprehensive guide to using JOINs
- [Example Code](example-joins.php) - Working examples with JOINs

## Requirements

- PHP >= 5.3.0
- WordPress (for REST API and database integration)
