<?php

namespace Scaleplan\Db\Exceptions;

/**
 * Class InvalidIsolationLevelException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class InvalidIsolationLevelException extends DbException
{
    public const MESSAGE = 'Invalid isolation level.';
}