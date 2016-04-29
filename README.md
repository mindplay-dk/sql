mindplay/sql
============

Database framework and query builder.

[![PHP Version](https://img.shields.io/badge/php-5.5%2B-blue.svg)](https://packagist.org/packages/mindplay/sql)
[![Build Status](https://travis-ci.org/mindplay-dk/sql.svg?branch=master)](https://travis-ci.org/mindplay-dk/sql)
[![Code Coverage](https://scrutinizer-ci.com/g/mindplay-dk/sql/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/mindplay-dk/sql/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mindplay-dk/sql/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mindplay-dk/sql/?branch=master)

## Concepts

The concepts used in this library can be roughly divided into two main areas: the framework and the model.

The codebase is namespaced accordingly.

### Framework

TODO

### Model

TODO

## Architecture

This section contains notes for inquisitive minds.

Parameter binding and type management occurs in three different layers of the architecture - this is not
duplication, as these abstractions satisfy very different requirements.

Going from the highest to the lowest level of abstraction:

  1. At the `Query` layer, values are managed by `Type` implementations, allowing support for complex types
     such as `DATETIME`, the JSON-type specific to Postgres, or very high-level (domain-specific) types.

  2. At the `Statement` layer, scalar (`int`, `float`, `string`, `bool`, `null`) values, and arrays of scalar
     values, are managed as native PHP values. The use of arrays is handled by expanding PDO placeholders.

  3. At the `PreparedStatement` layer, a managed `PDOStatement` instance has been created, which means two
     things: (1) at this level, the abstraction is connection-dependent, and (2) only scalar values are supported. 

The idea of internally managing the creation of the `PDOStatement` instance was considered, but this would block
the consumer from making potential optimizations by repeatedly executing the same prepared statement. By hiding
the creation of `PDOStatement` from the consumer (e.g. by implicitly preparing the statement again if a non-scalar
type is used) the performance implications would also have been hidden - in other words, the `PreparedStatement`
model, with it's inability to bind anything other than scalar values, accurately reflects the real-world limitations
and performance implications of prepared statements in PDO.
