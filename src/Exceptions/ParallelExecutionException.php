<?php

namespace Scaleplan\Db\Exceptions;

/**
 * Class DbException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class ParallelExecutionException extends DbException
{
    public const MESSAGE = 'db.parallel-execution-error';
    public const CODE = 500;
}
