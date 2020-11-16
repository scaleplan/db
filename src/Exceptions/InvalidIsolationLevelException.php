<?php

namespace Scaleplan\Db\Exceptions;

/**
 * Class InvalidIsolationLevelException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class InvalidIsolationLevelException extends DbException
{
    public const MESSAGE = 'db.wrong-isolation-level';
    public const CODE = 406;
}
