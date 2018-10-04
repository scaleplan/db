<?php

namespace Scaleplan\CachePDO\Exceptions;

/**
 * Class QueryExecutionException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class QueryExecutionException extends CachePDOException
{
    public const MESSAGE = 'Query execution error.';
}