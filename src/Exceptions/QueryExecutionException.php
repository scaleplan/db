<?php

namespace Scaleplan\Db\Exceptions;

/**
 * Class QueryExecutionException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class QueryExecutionException extends DbException
{
    public const MESSAGE = 'Ошибка выполнения запроса.';
    public const CODE = 400;
}
