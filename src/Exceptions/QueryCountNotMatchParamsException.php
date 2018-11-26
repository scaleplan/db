<?php

namespace Scaleplan\Db\Exceptions;

/**
 * Class QueryCountNotMatchParamsException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class QueryCountNotMatchParamsException extends DbException
{
    public const MESSAGE = 'Query count not match parameter record count.';
}