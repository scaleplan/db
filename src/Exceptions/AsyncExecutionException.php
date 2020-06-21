<?php

namespace Scaleplan\Db\Exceptions;

/**
 * Class AsyncExecutionException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class AsyncExecutionException extends DbException
{
    public const MESSAGE = 'db.async-execution-error';
    public const CODE = 500;
}
