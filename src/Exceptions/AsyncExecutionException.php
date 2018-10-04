<?php

namespace Scaleplan\CachePDO\Exceptions;

/**
 * Class AsyncExecutionException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class AsyncExecutionException extends CachePDOException
{
    public const MESSAGE = 'Async execution error.';
}