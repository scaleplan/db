<?php

namespace Scaleplan\Db\Exceptions;

/**
 * Class DbException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class ConnectionStringException extends DbException
{
    public const MESSAGE = 'Ошибка строки подключения.';
    public const CODE = 406;
}
