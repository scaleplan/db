<?php

namespace Scaleplan\Db\Exceptions;

/**
 * Class DbException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class ParallelExecutionException extends DbException
{
    public const MESSAGE = 'Parallel execution error.';
    public const CODE = 500;
}
