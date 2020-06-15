<?php

namespace Scaleplan\Db\Exceptions;

/**
 * Class QueryCountNotMatchParamsException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class QueryCountNotMatchParamsException extends DbException
{
    public const MESSAGE = 'Количество запросов не соответсвует количесву наборов параметров.';
}
