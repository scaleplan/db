<?php

namespace Scaleplan\Db\Exceptions;

/**
 * Class InvalidIsolationLevelException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class InvalidIsolationLevelException extends DbException
{
    public const MESSAGE = 'Неверный уровень изоляции.';
    public const CODE = 406;
}
