mindplay/sql
============

Database framework and query builder.

[![PHP Version](https://img.shields.io/badge/php-5.5%2B-blue.svg)](https://packagist.org/packages/mindplay/sql)
[![Build Status](https://travis-ci.org/mindplay-dk/sql.svg?branch=master)](https://travis-ci.org/mindplay-dk/sql)
[![Code Coverage](https://scrutinizer-ci.com/g/mindplay-dk/sql/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/mindplay-dk/sql/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mindplay-dk/sql/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mindplay-dk/sql/?branch=master)

This library implements (yet another) database abstraction and (yet another) query builder.

It is:

  * Query-builders for `INSERT`, `UPDATE`, `DELETE` and `SELECT` queries, with a chainable API.
  * Schema abstraction with two-way support for conversion between PHP data-types/objects and relational data.
  * An abstraction over PDO adding support for `array` values with PDO-style `:name` placeholders.
  * Streaming iteration of query results enabling you to process results in arbitrary-size batches.
  * On-fly-fly mapping of functions against individual records, or batches of records.
  * **NOT** an object/relational-mapper.

I favor simplicity over ease of use - this library minimally abstracts PDO and stays reasonably close to SQL
and the relational model, rather than attempting to hide it.

## Quick Start

Every project needs a `Schema` class and one `Table` class for every table in that schema.

This boostrapping process may seem a little verbose, but with IDE support, you will write these simple
classes in no-time - these classes in turn will provide support for static analysis tools and IDEs,
e.g. with auto-completion for table/column names, making your database work simple and safe.

It's worth it.

#### 1. Create Table Models

Define your table model by extending the `Table` class.

Your table classes act as factories for `Column` objects.

Add one method per column, each returning a `Column` object - the `Table` class provides several different
protected factory-methods to help with the creation of `Column` instances.

The method-name should match the column name.

You should add a `@property-read` annotation for each column for optimal static analysis.

The table model pattern looks like this:

```php
/**
 * @property-read Column $id
 * @property-read Column $first_name
 * @property-read Column $last_name
 */
class UserTable extends Table
{
    public function id($alias)
    {
        return $this->autoColumn(__FUNCTION__, IntType::class, $alias);
    }
    
    public function first_name($alias)
    {
        return $this->requiredColumn(__FUNCTION__, StringType::class, $alias);
    }

    public function last_name($alias)
    {
        return $this->requiredColumn(__FUNCTION__, StringType::class, $alias);
    }
}
```

#### 2. Create Schema Model

Define your schema model by extending the `Schema` class.

Your schema class acts as a factory for `Table` objects.

Add one method per `Table` type, each returning a `Table` object - the `Schema` class provides a
protected factory-method `createTable()` to help with the creation of `Table` instances.

The method-name should match the table-name.

You should add a `@property-read` annotation for each table for optimal static analysis.

The schema model pattern looks like this:

```php
/**
 * @property-read UserTable    $user
 * @property-read AddressTable $address
 */
class UserSchema extends Schema
{
    /**
     * @param string $alias
     * 
     * @return UserTable
     */
    public function user($alias)
    {
        return $this->createTable(UserTable::class, __FUNCTION__, $alias);
    }

    /**
     * @param string $alias
     * 
     * @return AddressTable
     */
    public function address($alias)
    {
        return $this->createTable(AddressTable::class, __FUNCTION__, $alias);
    }
}
```

#### 3. Bootstrap Your Project

If you use a dependency injection container, you should perform this bootstrapping once and register
these objects as services in your container.

First, select a `Database` implementation - for example:

```php
$db = new MySQLDatabase();
```

Next, create (and register in your DI container) your `Schema` model:

```php
/** @var UserSchema $schema */
$schema = $db->createSchema(UserSchema::class);
```

Finally, create (and register) a `Connection` instance - for example:

```php
$connection = new PDOConnection(new PDO("mysql:dbname=foo;host=127.0.0.1", "root", "root"));
```

Don't quibble about the fact that you need three different dependencies - it may seem complicated
or verbose, but it's actually very simple; each of these three components have a very distinct
purpose and scope:

  * The `Database` model acts as a factory for `Schema` types and various query-types (insert, select, etc.)
  
  * Your `Schema`-type acts as a factory-class for your domain-specific `Table`-types.
  
  * The `Connection` object provides a thin wrapper over `PDO`, which doesn't have an interface of it's own.

Note that the `Database` model and `Schema`-types have *no dependency* on the `Connection` object - the
database model and query-builders operate entirely in the abstract with no dependency on any physical
database connection, which is great, as it enables you to write (and unit-test) complex query-builders
independently of any database connection.

#### 4. Create Queries

Creating a query begins with the `Database` model and your `Schema`-type:

```php
$user = $schema->user;

$query = $db->select($user)
    ->where("{$user->first_name} LIKE :name")
    ->bind("name", "%rasmus%")
    ->order("{$user->last_name} DESC, {$user->first_name} DESC")
    ->page(1, 20); // page 1, 20 records per page, e.g.: OFFSET 0 LIMIT 20
```

Note the use of `__toString()` magic, which is supported by `Table`-types and `Column` objects.

You're now ready to `fetch()` from the `Connection` and iterate over the results:

```php
$result = $connection->fetch($query);

foreach ($result as $record) {
    // ...
}
```

To learn how to build nested queries, joins and other query-types, refer to inline documentation
in the codebase, and peep at the [unit tests](test/test.php).

## Concepts

The concepts used in this library can be roughly divided into two main areas: the framework and the model.

The codebase is namespaced accordingly.

### Framework

TODO

### Model

TODO

## Architecture

This section contains notes for inquisitive minds.

The overall architecture consists of high-level `Query` models and a low-level `PreparedStatement` abstraction.

At the `Query` layer, values are managed as native PHP values. Simple values, such as `int`, `float`, `string`,
`bool`, `null`, are internally managed, and the use of arrays is managed by expanding PDO-style placeholders.

The `Query` models implement either `Executable` or `ReturningExecutable`, depending on whether the type of
query returns records (`SELECT`) or not. (`INSERT`, `DELETE`, etc.)

The `Connection` abstraction prepares an `Executable` by generating a `PreparedStatement` instance - at this
layer, the abstraction is connection-dependent, and only scalar values are supported.

The idea of internally managing the creation of the `PDOStatement` instance was considered, but this would block
the consumer from making potential optimizations by repeatedly executing the same prepared statement. By hiding
the creation of `PDOStatement` from the consumer (e.g. by implicitly preparing the statement again if a non-scalar
type is used) the performance implications would have been hidden - in other words, the `PreparedStatement` model,
with it's inability to bind anything other than scalar values, accurately reflects the real-world limitations
and performance implications of prepared statements in PDO.
