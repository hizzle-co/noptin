# JOIN Queries Feature - Changelog

## Version 0.3.0 - JOIN Queries Support

### New Features

#### 1. Collection JOIN Configuration
- Added `joins` property to Collection class
- Allows defining relationships between collections
- Supports INNER, LEFT, and RIGHT join types
- Example configuration:
  ```php
  'joins' => array(
      'payments' => array(
          'collection'  => 'store_namespace_payments',
          'on'          => 'customer_id',
          'foreign_key' => 'id',
          'type'        => 'LEFT',
      ),
  )
  ```

#### 2. Query Class Enhancements
- Added `prepare_collection_joins()` method to process JOIN configurations
- Updated `prefix_field()` to support joined table fields (dot notation and double underscore)
- Added `join` parameter to query defaults
- Supports selective JOIN inclusion per query
- Properly handles join aliases as field prefixes

#### 3. REST API Support
- Added `join` parameter to aggregate endpoint
- Allows specifying which joins to include in API requests
- Example: `/wp-json/store/v1/customers/aggregate?join[]=payments&aggregate[payments.amount][]=SUM`

#### 4. Field Reference Formats
- **Dot notation**: `payments.amount` (standard SQL style)
- **Double underscore**: `payments__amount` (URL-friendly alternative)
- Both formats work in aggregate queries, GROUP BY, and extra fields

### Modified Files

1. **src/Collection.php**
   - Added `public $joins = array()` property
   - Updated `get_query_schema()` to include join parameter when joins are configured

2. **src/Query.php**
   - Added `prepare_collection_joins()` method
   - Updated `prefix_field()` to handle joined table fields
   - Added `join` to default query vars
   - Updated `prepare_known_fields()` to initialize joins array

3. **src/REST_Controller.php**
   - Added `join` parameter to aggregate endpoint registration

### Documentation

1. **JOINS.md** (New)
   - Comprehensive guide to JOIN queries
   - Configuration examples
   - Usage examples for queries and REST API
   - Best practices and troubleshooting

2. **example-joins.php** (New)
   - Working PHP examples
   - Sample data creation
   - Multiple use case demonstrations
   - Admin action hooks for testing

3. **README.md** (Updated)
   - Added JOIN queries to features list
   - Quick start example
   - Links to documentation

4. **test-joins.php** (New)
   - Verification script for implementation

### Use Cases

1. **Customer Revenue Analysis**
   ```php
   $query = $customers->query(array(
       'join' => array('payments'),
       'aggregate' => array(
           'payments.amount' => array('SUM', 'COUNT'),
       ),
       'groupby' => 'id',
   ));
   ```

2. **Monthly Revenue Reports**
   ```php
   $query = $customers->query(array(
       'join' => array('payments'),
       'aggregate' => array(
           'payments.amount' => array('SUM'),
       ),
       'groupby' => array(
           'id' => null,
           'payments.date' => 'month',
       ),
   ));
   ```

3. **Multiple Collections**
   ```php
   $query = $customers->query(array(
       'join' => array('payments', 'orders'),
       'aggregate' => array(
           'payments.amount' => array('SUM'),
           'orders.total' => array('SUM'),
       ),
       'groupby' => 'id',
   ));
   ```

### Technical Details

#### JOIN Type Validation
- Only INNER, LEFT, and RIGHT joins are supported
- Invalid join types default to INNER
- Join type is case-insensitive

#### Field Prefixing
- Fields are properly escaped with `esc_sql()` and `sanitize_key()`
- Joined table references use aliases for clarity
- Known fields array tracks joined collections

#### Query Flow
1. Collection defines joins in schema
2. Query receives `join` parameter (optional)
3. `prepare_collection_joins()` builds JOIN clauses
4. `prefix_field()` resolves field references
5. SQL query is constructed with proper JOINs
6. Results include data from joined tables

### Backward Compatibility
- Fully backward compatible
- Existing queries without joins continue to work
- JOIN is opt-in via query parameter
- No database schema changes required

### Security
- All field names are sanitized with `sanitize_key()`
- SQL values are escaped with `esc_sql()`
- JOIN types are validated against whitelist
- Collection names must be registered in Store

### Performance Considerations
- JOINs only applied when explicitly requested
- Supports indexed foreign keys for optimal performance
- LEFT JOINs used by default to avoid missing data
- Can be combined with WHERE clauses for filtering

### Known Limitations
1. No support for complex JOIN conditions (beyond simple equality)
2. No support for FULL OUTER JOIN
3. Cannot join to external databases or tables outside the Store
4. Subqueries in JOIN conditions not supported

### Future Enhancements (Potential)
- [ ] Support for multiple join conditions
- [ ] JOIN conditions with operators (>, <, !=)
- [ ] JOIN with subqueries
- [ ] Automatic JOIN detection based on foreign key constraints
- [ ] Visual query builder for joins
- [ ] JOIN performance profiling

### Breaking Changes
None. This is a new feature with no breaking changes.

### Migration Guide
To use JOIN queries in existing collections:

1. Add `joins` configuration to collection schema
2. Update queries to include `join` parameter when needed
3. Reference joined fields with dot notation or double underscore
4. Test aggregate queries with new joins

Example:
```php
// Before (without joins)
$query = $customers->query(array(
    'fields' => 'all',
));

// After (with joins)
$query = $customers->query(array(
    'join' => array('payments'),
    'aggregate' => array(
        'payments.amount' => array('SUM'),
    ),
    'groupby' => 'id',
));
```

### Testing
- Manual verification tests included
- All syntax validated
- Documentation examples tested
- REST API endpoints verified

### Credits
- Feature requested in GitHub issue: "Allow JOIN queries"
- Implementation by GitHub Copilot
- Code review and testing completed

### Release Notes Summary
This release adds comprehensive support for JOIN queries, enabling collections to be related together for complex data analysis and aggregation. Collections can define relationships in their schema, and queries can selectively include related data using the `join` parameter. Full REST API support is included for aggregate queries with joins.
