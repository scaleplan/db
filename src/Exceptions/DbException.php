<?php

namespace Scaleplan\Db\Exceptions;

/**
 * Class DbException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class DbException extends \Exception
{
    public const MESSAGE = 'Db error.';

    /**
     * DbException constructor.
     *
     * @param string|null $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(\string $message = '', \int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message ?: static::MESSAGE, $code, $previous);
    }
}
