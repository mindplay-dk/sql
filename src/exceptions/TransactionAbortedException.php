<?php

namespace mindplay\sql\exceptions;

use RuntimeException;

/**
 * This exception represents an implicitly aborted transaction.
 * 
 * @see Connection::transact()
 */
class TransactionAbortedException extends RuntimeException
{
}
