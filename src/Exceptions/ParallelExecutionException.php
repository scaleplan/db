<?php

namespace Scaleplan\Db\Exceptions;

/**
 * Class DbException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class ParallelExecutionException extends DbException
{
    public const MESSAGE = 'Ошибка параллельного выполнения запросов.';
    public const CODE = 500;
}
