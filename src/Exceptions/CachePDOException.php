<?php

namespace Scaleplan\CachePDO\Exceptions;

/**
 * Class CachePDOException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class CachePDOException extends \Exception
{
    public const MESSAGE = 'CachePDO error.';

    /**
     * CachePDOException constructor.
     *
     * @param string|null $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = null, int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message ?? static::MESSAGE, $code, $previous);
    }
}