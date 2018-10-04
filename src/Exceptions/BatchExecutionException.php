<?php

namespace Scaleplan\CachePDO\Exceptions;

/**
 * Class BatchExecutionException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class BatchExecutionException extends CachePDOException
{
    public const MESSAGE = 'Batch execution error.';
}