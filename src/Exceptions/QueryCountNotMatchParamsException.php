<?php

namespace Scaleplan\Db\Exceptions;

/**
 * Class QueryCountNotMatchParamsException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class QueryCountNotMatchParamsException extends DbException
{
    public const MESSAGE = 'db.requests-and-params-arrays-inconsistency';
}
