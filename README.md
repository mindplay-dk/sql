mindplay/sql
============

Database framework and query builder.

[![PHP Version](https://img.shields.io/badge/php-5.5%2B-blue.svg)](https://packagist.org/packages/mindplay/sql)
[![Build Status](https://travis-ci.org/mindplay-dk/sql.svg?branch=master)](https://travis-ci.org/mindplay-dk/sql)
[![Code Coverage](https://scrutinizer-ci.com/g/mindplay-dk/sql/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/mindplay-dk/sql/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mindplay-dk/sql/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mindplay-dk/sql/?branch=master)

This library implements (yet another) database abstraction and (yet another) query builder.

It supports:

  * Query-builders for `INSERT`, `UPDATE`, `DELETE` and `SELECT` queries, with a chainable API.
  * Schema abstraction with bi-directional conversions between PHP data-types/objects and relational data.
  * An abstraction over `PDO` adding support for `array` values with PDO-style `:name` placeholders.
  * Streaming iteration of query results enabling you to process results in arbitrary-size batches.
  * On-the-fly mapping of functions against individual records, or batches of records.
  * **NOT** an object/relational-mapper.

An important non-goal of this project is the ability to switch from one database technology to another -
while we do support both MySQL and PostgreSQL, and while a lot of the implementations are shared, no attempt
is made at hiding or abstracting the differences between each technology. On the contrary, we try to make it
plain and obvious that there are differences, both in terms of capabilities and best patterns for each DBMS.

We favor simplicity over ease of use - this library minimally abstracts PDO and stays reasonably close to SQL
and the relational model, rather than attempting to hide it.

### Project Status

Per [SimVersion](https://simversion.github.io/), the `0.x` release series is *feature-incomplete*, *not* "unstable",
and will not transition to `1.x` until it is feature-complete.

The project has been widely used on many internal projects in our organization - it is "stable", but is still
subject to changes, and will remain so for the foreseeable future.

The public API has been largely stable for many releases - at this point, most breaking changes are changes to
the internal `protected` portion of the API; typically, a major `0.x+1` release has very little impact on client code.

### Contributions

Current target is php 7.0 and later, see `.travis.yml`.

Code adheres to [PSR-2](http://www.php-fig.org/psr/psr-2/) and [PSR-4](http://www.php-fig.org/psr/psr-4/).

To run the test-suite:

    php test/test.php

To run only the unit or integration suites:

    php test/test.php --unit
    php test/test.php --integration

## Overview

The concepts used in this library can be roughly divided into two main areas: the *framework* and the *model*.

The *framework* (the `mindplay\sql\framework` namespace) consists of database Connection, Statement and
Prepared Statement abstractions, and an implementation of these for PDO.

In addition, the framework includes an iterable Result model which includes support for a Mapper abstraction
and implementations providing support for custom operations on individual records, as well as processing of
large result sets in batches.

The *model* (the `mindplay\sql\model` namespace) includes a Driver abstraction, Query Builders for `INSERT`, `SELECT`,
`UPDATE`, `DELETE` and custom SQL queries, a Schema model and a Type abstraction, which includes a Mapper
implementation for Type conversions.

## Usage

Every project needs a `Schema` class and one `Table` class for every table in that schema.

This boostrapping process may seem a little verbose, but with IDE support, you will write these simple
classes in no-time - these classes in turn will provide support for static analysis tools and IDEs,
e.g. with auto-completion for table/column names, making your database work simple and safe.

It's worth it.

<a name="creating-table-models"></a>
### Creating Table Models

Define your table model by extending the `Table` class.

Your table classes act as factories for `Column` objects.

Add one method per column, each returning a `Column` object - the `Table` class provides several different
protected factory-methods to help with the creation of `Column` instances.

The method-name should match the column name, so you can use `__FUNCTION__` to avoid repetition.

The `Table` class implements `__get()` so you can skip the parens when referencing columns.
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

The following `protected` factory-methods are available to help create `Column` instances:

  * `requiredColumn()` for non-optional columns: the INSERT query-builder will throw an exception
    if you don't explicitly specify a value for these.
  
  * `optionalColumn()` for columns that have a default value and/or allow NULLs.
  
  * `autoColumn()` for columns that the database itself will populate, e.g. auto-incrementing or
    columns that are otherwise initialized by the database itself.

Refer to the [`Table`](src/model/schema/Table.php) API for arguments.

Note that "required" and "optional" do not necessarily correlate 1:1 with `IS NULL` in your schema.
For example, a column could be "required" but still allow SQL `NULL` values - in this case, "required"
means you must explicitly supply a null-value e.g. to the INSERT query-builder, which may be safer and
more explicit for some use-cases.

#### Column Types

Every `Column` references a `Type` by it's class-name. (e.g. `DateType::class`, etc.)

`Type` implementations are responsible for converting between SQL values and PHP values, in both directions.

`Type` implementations are auto-wired in the DI container internally - you don't need to explicitly
register a custom `Type` implementation.

Built-in types are available for the scalar PHP types (`string`, `int`, `float`, `bool` and `null`) as well
as a few other SQL types.

For available types and documentation, look in the `mindplay\sql\model\types` namespace.

### Creating Schema Models

Define your schema model by extending the `Schema` class.

Your schema class acts as a factory for `Table` objects.

Add one method per `Table` type, each returning a `Table` object - the `Schema` class provides a
protected factory-method `createTable()` to help with the creation of `Table` instances.

The method-name should match the table-name, so you can use `__FUNCTION__` to avoid repetition.

The `Schema` class implements `__get()` so you can skip the parens when referencing tables.
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

### Bootstrapping a Project

If you use a dependency injection container, you should perform this bootstrapping once and register
these objects as services in your container.

First, select a `Database` implementation - for example:

```php
$db = new MySQLDatabase();
```

Next, create (and register in your DI container) your `Schema` model:

```php
/** @var UserSchema $schema */
$schema = $db->getSchema(UserSchema::class);
```

Finally, create (and register) a matching `Connection` implementation - for example:

```php
$connection = $db->createConnection(new PDO("mysql:dbname=foo;host=127.0.0.1", "root", "root"));
```

Don't quibble about the fact that you need three different dependencies - it may seem complicated
or verbose, but it's actually very simple; each of these three components have a very distinct
purpose and scope:

  * The `Database` model acts as a factory for `Schema`-types and various query-types (insert, select, etc.)
  
  * Your `Schema`-type acts as a factory-class for your domain-specific `Table`-types.
  
  * The `Connection` object provides a thin wrapper over `PDO`, which doesn't have an interface of it's own.

Note that the `Database` model and `Schema`-types have *no dependency* on the `Connection` object - the
database model and query-builders operate entirely in the abstract with no dependency on any physical
database connection, which is great, as it enables you to write (and unit-test) complex query-builders
independently of any database connection.

### Building Queries

Creating a query begins with the `Database` model and your `Schema`-type.

Here is a basic example of building a SELECT query with a `SelectQuery` builder, which is created
by the `select()` factory-method:

```php
$user = $schema->user;

$query = $db->select($user)
    ->where("{$user->first_name} LIKE :name")
    ->bind("name", "%rasmus%")
    ->order("{$user->last_name} DESC, {$user->first_name} DESC")
    ->page(1, 20); // page 1, 20 records per page, e.g.: OFFSET 0 LIMIT 20
```

Note the use of `__toString()` magic, which is supported by `Table`-types and `Column` objects: these
properties/methods return quoted names - for example, `{$user->last_name}` expands to `"user"."last_name"`
if you're using a `PostgresConnection`.

Factory-methods are available for the following query-builders:

  * `select(Table $from)` creates a `SelectQuery` builder for `SELECT` queries
  * `insert(Table $into)` creates an `InsertQuery` builder for `INSERT` queries
  * `update(Table $table)` creates an `UpdateQuery` builder for `UPDATE` queries
  * `delete(Table $table)` creates `DeleteQuery` builder for `DELETE` queries
  * `sql(string $sql)` creates a `SQLQuery` builder for custom SQL queries

All of the query-builders support parameter binding via `bind()` and `apply()`.

Query-builders for `SELECT`, `UPDATE` and `DELETE` queries support conditions via the `where()` method.

In addition, some query-builders support a few features specific to those types of queries.

#### Binding Parameters

All types of query-builders extend the `Query` builder, which implements parameter binding - the one
feature that is common to all query-types, including the "raw" `SQLQuery` type.

> ⚠ *To avoid SQL injection, all values should be bound to placeholders* - we can't prevent you from
> inserting literal values directly into queries, but don't: you should *always* use parameter binding.

You can bind individual placeholders (such as `:name`) to values using the `bind()` method:

```php
$query->bind("name", $value);
``` 

For native scalar types (`string`, `int`, `float`, `bool`, `null` and arrays of those) the type is
automatically inferred from the value-type.

For other types, you must manually specify which type to use - for example, the built-in `DateType`
can be used to expand an integer timestamp value into a `DATE` SQL expression:

```php
$query->bind("created", $timestamp, DateType::class);
``` 

For convenience, you can also `apply()` a map of name/value-pairs to several placeholders:

```php
$query->apply([
    "first_name" => $first_name,
    "last_name" => $last_name,
]);
```

Note that `apply()` works for scalar types (and arrays of those) only - explicitly binding to
specific types requires multiple calls to `bind()`.

#### `SELECT` Queries

The SELECT query-builder supports by far the widest range of API methods:

  * Projections of columns and SQL expressions.
  * Conditions via the `where()` method.
  * Ordering via the `order()` method.
  * Joins via the `innerJoin()`, `leftJoin()` and `outerJoin()` methods.
  * Limits via the `limit()` and `page()` methods.

We'll cover all of these in the following sections.

##### Projections

To create a SELECT query-builder, you must specify the root of the projection - for example:

```php
$user = $schema->user;

$query = $db->select($user);
```

If you don't manually specify which columns should be selected, by default, this will build a
simple query like:

    SELECT * FROM user

You can explicitly designate the columns you wish to select:

```php
$user = $schema->user;

$query = $db
    ->select($user)
    ->columns([
        $user->first_name,
        $user->last_name,
    ]);
```

Note that the raw SQL values from the selected columns will be automatically converted to PHP types
using the type-information defined in your table/column-model.

Contrast this with the `value()` method, which lets you add any custom SQL expression to be selected:

```php
$user = $schema->user;

$query = $db
    ->select($user)
    ->columns([$user->id])
    ->value("CONCAT({$user->first_name}, ' ', {$user->last_name})", "full_name");
```

Note that, since we're building an SQL expression and passing that as a string, the type-information
in the columns can't automatically be used - in this example, the raw SQL value is a string, and that
happens to be the type we want back, so we don't need to specify a type.

In other cases, you may need to explicitly specify the type - for example, here we're calculating
an `age` value, designating the value for conversion with `IntType::class`:

```php
$user = $schema->user;

$query = $db
    ->select($user)
    ->table($user)
    ->value("DATEDIFF(hour, {$user->dob}, NOW()) / 8766", "age", IntType::class);
```

Note also the use of `table($user)` in this example - we're selecting the entire table (all of the
columns) as well as the custom `age` expression.

##### Having

Building on the above example, we can add an SQL `HAVING` clause to select users of legal drinking age:

```php
$query->having("age >= 21");
```

Repeated calls to `having()` will append to the list of `HAVING` expressions.

(Note that this particular example could be optimized by duplicating the `DATEDIFF` expression
and adding the `>= 21` condition to the `where()` clause instead.)

##### Grouping

We can build an aggregate query by adding an SQL `GROUP BY` clause - for example, here
we create a projection of the number of users grouped by country name:

```php
$user = $schema->user;

$query = $db
    ->select($user)
    ->columns([$user->country])
    ->groupBy($user->country)
    ->value("COUNT({$user})", "num_users");
```

Note that repeated calls to `groupBy()` will append to the list of `GROUP BY` terms.

<a name="conditions"></a>
##### Conditions (`WHERE`)

Note that the `where()` method is supported by the SELECT, UPDATE and DELETE query-builders.

When you add multiple conditions with `where()`, these are combined with the `AND` operator - so
your query has to match *all* of the conditions applied to it.

> ⚠ Literal SQL expressions in `where()` conditions must always use `:name` placeholders - resist
> the temptation to inject literal values, even when this seems perfectly safe: refactoring etc.
> could make a safe injection *become* unsafe, and there is no reason to take that risk, ever.

The `where()` method accepts either a single SQL condition, or an array of conditions - for example:

```php
$user = $schema->user;

$query = $db
    ->select($user)
    ->where([
        "{$user->first_name} LIKE :first_name",
        "{$user->last_name} LIKE :last_name",
    ])
    ->apply([
        "first_name" => "ras%",
        "last_name" => "sch%",
    ]);
```

This produces an SQL query like:

    SELECT * FROM user WHERE (first_name LIKE "ras%") AND (last_name LIKE "sch%")

Two simple helper-functions are available to help you build arbitrarily nested conditions with
any combination of `AND` and `OR` operators:

  * `expr::all()` combines conditions to match *all* given conditions. (by combining them with `AND`.)
  * `expr::any()` combines conditions to match *any* of the given conditions. (by combining with `OR`.)

For example:

  * `expr::all(["a = :a", "b = :b"])` combines to `"(a = :a) AND (b = :b)"`
  * `expr::any(["a = :a", "b = :b"])` combines to `"(a = :a) OR (b = :b)"` 

So, building on the first example above, if you wanted to search by `first_name` *or* `last_name`, you
can use `expr::any()` to combine the conditions before adding them to the query - that is:

```php
$user = $schema->user;

$query = $db
    ->select($user)
    ->where(
        expr::any([
            "{$user->first_name} LIKE :first_name",
            "{$user->last_name} LIKE :last_name",
        ])
    )
    ->apply([
        "first_name" => "ras%",
        "last_name" => "sch%",
    ]);
```

This produces an SQL query like:

    SELECT * FROM user WHERE (first_name LIKE "ras%") OR (last_name LIKE "sch%")

> ⚠ Note that both of these functions throw an `InvalidArgumentException` if you pass an empty array.
> This is very much *by design*, since we can't combine zero conditions into one meaningful condition -
> if some list of conditions in your domain is zero-or-more, you need to actively decide if this
> should generate no added condition, an `IS NULL` condition, or something else entirely.

<a name="join"></a>
##### Joins

Various JOIN-methods are supported by the SELECT, UPDATE and DELETE query-builders, including
`innerJoin()`, `leftJoin()` and `rightJoin()`.

All the JOIN-methods accept the same arguments, e.g. `leftJoin(Table $table, string $expr)`, and so on.

The `$table` argument designates the table to JOIN with, and the `$expr` argument specifies the `ON` clause.

Let's examine a typical use-case with `customer` and `order` tables - and let's say we want a list
of customer records, and the number of orders each customer has placed:

```php
$customer = $schema->customer;
$order = $schema->order;

$query = $db
    ->select($customer)
    ->table($customer)
    ->leftJoin($order, "{$order->customer_id} = {$customer->id}")
    ->value("COUNT({$order})", "num_orders")
    ->groupBy($customer->id);
```

This produces an SQL query like:

    SELECT
      customer.*,
      COUNT(order) AS num_orders
    FROM
      customer
    LEFT JOIN
      order ON order.customer_id = customer.id
    GROUP BY
      customer.id

Note the use of `groupBy()` and `value()`, which are specific to the SELECT query-builder.

Note that self-join is possible by naming the relational variables - for example, in the typical
use-case with an `employee` table, where a `supervisor_id` references another `employee`, we can
create a second alias, e.g. `employee AS supervisor` to get a list of employees including the
name of their direct supervisor:

```php
$employee = $schema->employee;
$supervisor = $schema->employee("supervisor"); // e.g. "employee AS supervisor"

$query = $db
    ->select($employee)
    ->table($employee)
    ->leftJoin($supervisor, "{$supervisor->id} = {$emplyoee->supervisor_id}")
    ->columns($supervisor->name);
```

#### `INSERT` Queries

This is probably the simplest of the available query-builders.

To create an INSERT query-builder, you must specify the destination table - and then call the `add()`
method to add one or more records - for example:

```php
$user = $schema->user;

$query = $db
    ->insert($user)
    ->add([
        "first_name" => "Rasmus",
        "last_name" => "Schultz",
        "dob" => 951030427,
    ]);
```

Note that the array keys must match column-names in the destination table - so that type-conversions for the
columns can be applied.

If you think this approach is too fragile, you can choose to get the column-names from the schema model instead:

```php
$user = $schema->user;

$query = $db
    ->insert($user)
    ->add([
        $user->first_name->getName() => "Rasmus",
        $user->last_name->getName() => "Schultz",
        $user->dob->getName() => 951030427,
    ]);
```

This is safer (in terms of static analysis) but a bit verbose.

Note that, if you add multiple records, when executed, these will be inserted with a single INSERT statement.

#### `UPDATE` Queries

To create an UPDATE query-builder, you must specify the table to be updated and the [conditions](#conditions),
and then designate the value to be applied - for example, here we update the `user` table where `user.id = 123`,
setting the value of the `first_name` column:

```php
$user = $schema->user;

$query = $db
    ->update($user)
    ->where("{$user->id} = :id")
    ->bind("id", 123)
    ->setValue($user->first_name, "Rasmus");
```

For convenience, you could also use `assign()` with a key/value array instead:

```php
$query->assign([
    "first_name" => "Rasmus"
]);
```

In either case, type-conversions will automatically be applied according to the column-type.

You can also use `setExpr()`, which lets you specify a custom SQL expression to compute a value - for example,
here we update the `last_logged_in` column using the SQL `NOW()` function to get the DB server's current date/time:

```php
$user = $schema->user;

$query = $db
    ->update($user)
    ->where("{$user->id} = :id")
    ->bind("id", 123)
    ->setExpr($user->last_logged_in, "NOW()");
```

In addition, PostgreSQL supports `returning()`, and MySQL supports `limit()` and `order()`.

Note that building [nested queries](#nested) is possible with the UPDATE query-builder.

#### `DELETE` Queries

To create a DELETE query-builder, you must specify the table from which to delete and the
[conditions](#conditions) - for example, here we delete from the `user` table where `user.id = 123`:

```php
$user = $schema->user;

$query = $db
    ->delete($user)
    ->where("{$user->id} = :id")
    ->bind("id", 123);
```

In addition, PostgreSQL supports `returning()`, and MySQL supports `limit()` and `order()`.

Note that building [nested queries](#nested) is possible with the DELETE query-builder.

#### Custom SQL Queries

The `SQLQuery` type lets you leverage all the framework features for "hand-written" SQL queries - e.g.
parameter binding (with array support), column references, types, mappers, result iteration, etc.

Don't think of custom SQL queries as a "last resort" - use query-builders for queries that are
dynamic in nature, but don't shy away from raw SQL because it "looks" or "feels" wrong: a static query
is often both simpler and easier to understand when written using plain SQL syntax.

For example, to create a simple SQL query counting new users created in the past month:

```php
$user = $schema->user;

$query = $db
    ->sql("SELECT COUNT({$user}) as num_users FROM {$user} WHERE {$user->created} > :first_date")
    ->bind("first_date",  time() + 30*24*60*60, TimestampType::class);
```

This approach has several benefits over raw SQL with PDO:

  1. The use of the table/column-model ensures that the referenced column exists in your schema, gets
     correctly qualified and quoted, enable static analysis (and safe renaming) in an IDE, etc.
  
  2. You can `bind()` values to placeholders with type-conversions, which enables you to write
     code with the same types you use in your application model. (in this example an integer timestamp.)
  
  3. Various convenience features like result iteration, batching and mapping are fully supported.

For static, one-off queries, this approach is definitely worth considering.

<a name="nested"></a>
#### Nested Queries

The SELECT query-builder supports `__toString()` magic, which allows you to build the full SQL query
and insert it into another query-builder instance.

This enables you to build nested SELECT queries - for example, you can use `value()` to inline a
sub-query and return the result, or you can use `expr()` to inline a sub-query and a condition on
the result of that sub-query.

Let's examine a typical use-case with `customer` and `order` tables - and let's say we want a list
of customer IDs and names, and the number of orders they've placed.

Also, let's say we only want to count `order` rows with a minimum `total` sale over $100.

We need to build the sub-query for the number of orders first:

```php
$customer = $schema->customer;
$order = $schema->order;

$num_orders = $db
    ->select($order)
    ->value("COUNT({$order})")
    ->where([
        "{$order->total} > :min_total",
        "{$order->customer_id} = {$customer->id}",
    ]);
```

Two important things to note about this sub-query:

  1. We've deliberately left the `:min_total` placeholder unbound - this placeholder will be bound
     in the parent query instead, which is the one we'll actually execute. We're just leveraging
     the first query-builder for it's ability to build an SQL statement.

  2. This query can't be executed in the first place, because the second condition references
     `{$customer->id}`, which will be established by the parent query.

Next, we build the parent query, using `value()` to insert and return the value from the sub-query:

```php
$query = $db
    ->select($customer)
    ->table([
        $customer->id,
        $customer->first_name,
        $customer->last_name,
    ])
    ->value($num_orders, "num_orders")
    ->bind("min_total", 100);
```

Again, note that the `:min_total` placeholder was bound to the parent query, not to the sub-query.

This produces an SQL query like:

    SELECT
      customer.id,
      customer.first_name,
      customer.last_name,
      (
        SELECT COUNT(order) FROM order
        WHERE (order.total > 100)
        AND (order.customer_id = customer.id)
      ) AS num_orders
    FROM
      customer

Note that, in simple cases like this, using multiple query-builders may be overly verbose: you
may need query-builders for queries that are dynamic in nature, but for a simple static sub-query,
you might also consider simply inserting the sub-query as literal SQL - like so:

```php
$query = $db
    ->select($customer)
    ->table([
        $customer->id,
        $customer->first_name,
        $customer->last_name,
    ])
    ->value(
        "SELECT COUNT({$order}) FROM {$order}"
        . " WHERE ({$order->total} > :min_total)"
        . " AND ({$order->customer_id} = {$customer->id})",
        "num_orders"
    )
    ->bind("min_total", 100);
```

One approach isn't "better" or "worse" than the other - building an inline SQL statement in this way
produces the exact same SQL query, so it is mostly a question of whether the sub-query is dynamic
or static in nature.

### Executing Queries 

To directly execute a query, simply pass it to `Connection::execute()`:

```php
$connection->execute(
    $db->sql("DELETE FROM order WHERE id = :id")->bind("id", 123)
);
```

The `execute()` method returns the `PreparedStatement` instance after running it, which makes
it possible to subsequently count the number of rows affected by an INSERT, UPDATE or DELETE.

You can use this to check if a DELETE was successful:

```php
$delete = $db->sql("DELETE FROM order WHERE id = :id")->bind("id", 123);

if ($connection->execute($delete)->getRowsAffected() !== 1) {
    // delete failed!
}
```

#### Fetching Results

The `Connection::fetch()` method produces an iterable `Result` instance.

This makes it easy to fetch a result and iterate over the rows:

```php
$query = $db->sql("SELECT * FROM user");

$result = $connection->fetch($query);

foreach ($result as $row) {
    var_dump($row["id"], $row["first_name"]);
}
```

Note that there is no built-in row-model: the `Result` instance yields simple `array` values
by default, with column-names mapping to the projected values. (See also [mappers](#mappers),
which let you map the rows to model objects, etc.)

For convenience, a couple of shortcuts are available to read the result set, e.g.:

  * `$result->all()` will read the entire result set into memory and returns an `array`.
  * `$result->firstRow()` to read the first row, e.g. for result sets that produce a single record,
    such as simple primary key queries, etc.
  * `$result->firstCol()` to read the first column of the first row, e.g. for result sets that produce
    a single record with a single column, such as `COUNT` queries, etc.

#### Type Conversions

To enable conversion of projected SQL values to PHP types, the SELECT query-builder internally maps the
projected values against `Type` implementations defined by your [table/column-models](#creating-table-models).

For example, if you have a `user` table with a `created` column of type `TimestampType`, fetching this
column internally maps the SQL `DATETIME` type to an `integer` timestamp:

```php
$user = $schema->user;

$query = $db
    ->select($user)
    ->where("{$user->id} = :id")
    ->bind("id", 123);

$row = $connection->fetch($query)->firstRow();

var_dump($row["created"]); // => (int) 1553177264
```

<a name="mappers"></a>
#### Mapping Results

While basic type-conversions are internally applied (by a built-in `Mapper` implementation) you also
have the option of manually mapping rows against a custom function.

For example, to perform a basic mapping of `user` rows to `User` model instances, you might apply
a simple mapper-function using `mapRecords()`, as follows:

```php
$user = $schema->user;

$query = $db
    ->select($user)
    ->mapRecords(function (array $row) {
        return new User($row["id"], $row["first_name"], $row["last_name"]);
    });

$results = $connection->fetch($query);

foreach ($results as $result) {
    var_dump($result); // class User#1 (0) { ... }
}
```

If you apply multiple mappers, these will be applied in the order they were added - applying
another mapper after the one in this example, the next mapper will receive the `User` instance.
So you can chain as many operations as you want to, as long as you make sure the next mapper
expects an input like the output produced by the previous one.

If a mapping operation is common, you can implement it in a reusable way, by implementing the
`Mapper` interface - for example, we can refactor the mapping function above to a `Mapper`, like so:

```php
class UserMapper implements Mapper
{
    public function map(array $rows)
    {
        foreach ($rows as $row) {
            yield new User($row["id"], $row["first_name"], $row["last_name"]);
        }
    }
}
```

To apply this mapper to a query, use `map()` instead of `mapRecords()`:

```php
$query = $db
    ->select($user)
    ->map(new UserMapper());
```

Note the fact that mappers process an entire [batch](#batches) of rows at a time - in this example, we used
the `yield` statement to create a [Generator](https://www.php.net/manual/en/language.generators.syntax.php),
which is more convenient than manually creating and appending to an array, and also enables you to customize
keys, e.g. using the `yield $key => $value` syntax.

<a name="batches"></a>
#### Batching Results

To avoid memory overhead when processing larger result sets, the `Result` model internally fetches records
(and applies mappers, etc.) in *batches*.

The default batch size is 1000 records, e.g. large enough to fetch the result of most normal queries in
a single round-trip.

If needed, you can specify a different batch size via `Connection::fetch()` - the batch processing is
internal, so when you loop over the `Result` with a `foreach` statement, the difference isn't directly
visible in your client code:

```php
$query = $db
    ->select($user)
    ->map(new UserMapper());

$result = $connection->fetch($query, 100); // batches of 100

foreach ($result as $row) {
    // ...
}
```

Because [mappers](#mappers) are applied to *batches*, the `UserMapper` in this example internally gets
invoked for every set of 100 records - assuming the records fall out of scope your client code, this
means that only 100 `User` instances will exist in-memory at a time.

#### Counting Results

The SELECT query-builder is able to rewrite itself into an SQL `COUNT(*)` query, removing the
`LIMIT`, `OFFSET` and `ORDER BY` clauses, and ignoring any applied mappers.

For example, if you're building a search-form that displays pages of 20 records, you can count the
total number of results (e.g. to be displayed somewhere) before executing the actual query:

```php
$query = $db
    ->select($user)
    ->where("{$user->name} LIKE :name")
    ->bind("name", "%rasmus%")
    ->page($page_no, 20); // $page_no is the base-1 page number

$count = $connection->count($query);  // total number of matching results (for display)

$num_pages = ceil($count / 20);       // total number of pages (for display)

$result = $connection->fetch($query); // 20 records of the requested page number
```

Note that any conditions and JOINs etc. will be preserved and applied as normal, only the root
projection of the query is changed into `COUNT(*)`, and the query is immediately executed and fetched.

#### Transactions

The `Connection` interfaces supports transactions in a safer, more atomic way than bare PDO.

Rather than disparate begin, commit and rollback-methods, a single `transact()` method accepts a
callback, and the transaction must explicitly either commit or roll back immediately.

In this abbreviated example, we update a `payment` and create a `subscription` atomically:

```php
$connection->transact(function () use ($connection, $db) {
    $connection->execute(
        $db->sql("UPDATE payment WHERE id = :payment_id SET paid = NOW()")->bind(...)
    );
    
    $connection->execute(
        $db->sql("INSERT INTO subscription (...) VALUES (...)")->bind(...);
    );
    
    return true; // COMMITS the transaction
});
```

The callback function *must* explicitly return either `true` to commit the transaction, or `false` to roll back -
returning anything other than a `bool` will roll back the transaction and generate an exception.

If an unhandled exception is thrown while invoking your callback, the transaction will be rolled back, and
the exception will be re-thrown.

Note that nested transactions are possible, e.g. by calling `transact()` from within a callback. The result of
doing so is a single SQL transaction around the top-level call to `transact()`, and therefore, *all* transaction
callbacks must return `true` to commit - if *any* of the callbacks in a net transaction return `false` (or
generate an exception, etc.) the transaction will be rolled back, and a `TransactionAbortedException` will be
thrown. In other words, any nested transactions must *agree* to either commit or rollback - this ensures that
the top-level transaction will either succeed or fail as a whole.

#### Prepared Statements

To efficiently execute the same query many times, you can manually `prepare()` a statement -
for example, to DELETE a list of `order` records:

```php
$delete = $connection->prepare($db->sql("DELETE FROM order WHERE id = :id"));

foreach ($ids as $id) {
    $delete->bind("id", $id);
    $delete->execute();
}
```

Note that the `prepare()` method *eagerly* expand arrays to multiple placeholders - while you can
`bind()` the placeholders of a `PreparedStatement` instance to scalar (`int`, `float`, `string`,
`bool` and `null`) values, binding `array` values to an already prepared statement is not possible,
because this changes the structure of the query itself. (If your use-case requires you to bind
placeholders to different `array` values, instead use the `bind()` method of the query-builder and
avoid re-binding the prepared statement.)

### Logging

Logging of queries is supported via the [`Logger`](src/framework/Logger.php) interface - and instance
can be injected into a `Connection` instance with the `addLogger()` method.

A [`BufferedPSRLogger`](src/framework/BufferedPSRLogger) implementation is available - this will buffer
executed queries, until you choose to flush them to a [PSR-3](http://www.php-fig.org/psr/psr-3/) logger,
for example:

```php
$buffer = new BufferedPSRLogger();

$connection->addLogger($buffer);

// ... execute queries ...

$buffer->flushTo($psr_logger);
```

Where `$psr_logger` is a `Psr\Log\LoggerInterface` implementation of your choosing.

You may want to check out [`kodus/chrome-logger`](https://github.com/kodus/chrome-logger), which can be
used to render an SQL query-log via [ChromeLogger](https://craig.is/writing/chrome-logger) in tabular format.

## Performance

Plenty fast.

A simple [benchmark](test/benchmark.php) of query-builder performance is included - a simple `SELECT` with
`ORDER` and `LIMIT` clauses builds in ~0.1 msec, and a more complex `SELECT` with two `JOIN` clauses and a
bunch of conditions and parameters builds in ~0.5 msec. (on my Windows 10 laptop running PHP 7)

## Architecture

This section contains notes for inquisitive minds.

The overall architecture consists of high-level `Query` models and a low-level `PreparedStatement` abstraction.

At the `Query` layer, values are managed as native PHP values. Simple values, such as `int`, `float`, `string`,
`bool`, `null`, are internally managed, and the use of arrays is managed by expanding PDO-style placeholders.

The `Query` models implement either `Executable` or `ReturningExecutable`, depending on whether the type of
query returns records (`SELECT`, `INSERT..RETURNING`, etc.) or not. (`INSERT`, `DELETE`, etc.)

The `Connection` abstraction prepares a `Statement` and generates a `PreparedStatement` instance - at this
layer, the abstraction is connection-dependent, and only scalar values are supported.

The idea of internally managing the creation of the `PDOStatement` instance was considered, but this would block
the consumer from making potential optimizations by repeatedly executing the same prepared statement. By hiding
the creation of `PDOStatement` from the consumer (e.g. by implicitly preparing the statement again if a non-scalar
type is used) the performance implications would have been hidden - in other words, the `PreparedStatement` model,
with it's inability to bind anything other than scalar values, accurately reflects the real-world limitations
and performance implications of prepared statements in PDO.
