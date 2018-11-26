<?php

namespace Scaleplan\Db\Exceptions;

/**
 * Class PDOConnectionException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class PDOConnectionException extends DbException
{
    public const MESSAGE = 'Connection by PDO error.';
}