<?php

namespace Scaleplan\Db\Exceptions;

/**
 * Class DbException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class ConnectionStringException extends DbException
{
    public const MESSAGE = 'db.connection-string-error';
    public const CODE = 406;
}
