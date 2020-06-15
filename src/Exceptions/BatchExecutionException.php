<?php

namespace Scaleplan\Db\Exceptions;

/**
 * Class BatchExecutionException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class BatchExecutionException extends DbException
{
    public const MESSAGE = 'Ошибка выполнения пакета запросов.';
    public const CODE = 500;
}
