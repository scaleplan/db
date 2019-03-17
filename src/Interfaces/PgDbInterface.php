<?php

namespace Scaleplan\Db\Interfaces;

use Scaleplan\Db\Exceptions;
use Scaleplan\Db\Exceptions\DbException;
use Scaleplan\Db\Exceptions\InvalidIsolationLevelException;

/**
 * Class pgDb
 *
 * @package Scaleplan\Db
 */
interface PgDbInterface extends DbInterface
{
    /**
     * Выполнить параллельно пакет запросов. Актуально для PostgreSQL
     *
     * @param string[] $batch - массив транзакций
     *
     * @return array
     *
     * @throws DbException
     */
    public function parallelExecute(array $batch) : array;

    /**
     * Отправить асинхронно пакет транзакций на сервер (актуально для PostgreSQL)
     *
     * @param string[]|string $query - запрос или массив запросов
     * @param array $data - параметры подготовленного запроса
     *
     * @return bool
     *
     * @throws DbException
     */
    public function async($query, array $data = null) : bool;

    /**
     * @param string $level
     *
     * @throws InvalidIsolationLevelException
     */
    public function setNextQueryIsolationLevel(string $level) : void;

    /**
     * @param string $level
     *
     * @return array
     *
     * @throws Exceptions\QueryCountNotMatchParamsException
     * @throws InvalidIsolationLevelException
     */
    public function setIsolationLevel(string $level) : array;
}
