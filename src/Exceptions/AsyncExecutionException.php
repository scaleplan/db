<?php

namespace Scaleplan\Db\Exceptions;

/**
 * Class AsyncExecutionException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class AsyncExecutionException extends DbException
{
    public const MESSAGE = 'Async execution error.';
    public const CODE = 500;
}
