### Version 0.8.0

This release upgrades everything to PHP 8.1.

The addition of static type-hints everywhere may be a breaking change - to upgrade, simply copy the added
type-hints from the library into your own methods.

### Version 0.7.6

This bugfix release includes some minor breaking changes.

##### Removed default value for required Columns

The `$default` argument has been removed from `Table::requiredColumn()` - when using the INSERT query-builder,
a value must be specified for a required column, so a default value would never actually get used.

This isn't *strictly* a breaking change, since extra arguments are allowed in PHP calls - however, if you were
using this argument, you may receive an inspection failure from your IDE or static analysis tool, and you
can safely remove this argument, since it was never used.

##### Removed Projection Queries

Due to a [design issue](https://github.com/mindplay-dk/sql/issues/45), the `ProjectionQuery` class has been
removed as part of a refactoring to remove several features from the UPDATE and DELETE query-builders,
which weren't actually supported by any DBMS.

This is obviously a breaking change - however, if you were using any of these API points, you wouldn't be
creating a query that can actually be executed; hence, presumably, removal of these methods won't affect
anyone and their removal is regarded as a bugfix for the purposes of versioning this release.
