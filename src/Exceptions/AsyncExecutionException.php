<?php

namespace Scaleplan\Db\Exceptions;

/**
 * Class AsyncExecutionException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class AsyncExecutionException extends DbException
{
    public const MESSAGE = 'Ошибка асинхронного выполнения запроса.';
    public const CODE = 500;
}
