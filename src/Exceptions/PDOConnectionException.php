<?php

namespace Scaleplan\Db\Exceptions;

/**
 * Class PDOConnectionException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class PDOConnectionException extends DbException
{
    public const MESSAGE = 'Ошибка подключения через PDO.';
    public const CODE = 523;
}
