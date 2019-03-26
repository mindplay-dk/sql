### Version 0.7.6

This bugfix release includes some minor breaking changes.

The `$default` argument has been removed from `Table::requiredColumn()` - when using the INSERT query-builder,
a value must be specified for a required column, so a default value would never actually get used.

This isn't *strictly* a breaking change, since extra arguments are allowed in PHP calls - however, if you were
using this argument, you may receive an inspection failure from your IDE or static analysis tool, and you
can safely remove this argument, since it was never used.
