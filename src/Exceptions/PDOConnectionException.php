<?php

namespace Scaleplan\CachePDO\Exceptions;

/**
 * Class PDOConnectionException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class PDOConnectionException extends CachePDOException
{
    public const MESSAGE = 'Connection by PDO error.';
}