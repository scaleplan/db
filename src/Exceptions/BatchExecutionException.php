<?php

namespace Scaleplan\Db\Exceptions;

/**
 * Class BatchExecutionException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class BatchExecutionException extends DbException
{
    public const MESSAGE = 'Batch execution error.';
    public const CODE = 500;
}
