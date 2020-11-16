<?php

namespace Scaleplan\Db\Interfaces;

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
     */
    public function parallelExecute(array $batch) : array;

    /**
     * Отправить асинхронно пакет транзакций на сервер (актуально для PostgreSQL)
     *
     * @param string[]|string $query - запрос или массив запросов
     * @param array $data - параметры подготовленного запроса
     */
    public function async($query, array $data = null) : void;

    /**
     * @param string $level
     */
    public function setNextQueryIsolationLevel(string $level) : void;

    /**
     * @param string $level
     *
     * @return array
     */
    public function setIsolationLevel(string $level) : array;
}
