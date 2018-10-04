<?php

namespace Scaleplan\CachePDO\Exceptions;

/**
 * Class CachePDOException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class ParallelExecutionException extends CachePDOException
{
    public const MESSAGE = 'Parallel execution error.';
}