<?php

namespace Scaleplan\Db\Interfaces;

use Scaleplan\Db\Exceptions\ConnectionStringException;
use Scaleplan\Db\Exceptions\DbException;
use Scaleplan\Db\Exceptions\PDOConnectionException;
use Scaleplan\Db\Exceptions\QueryCountNotMatchParamsException;

/**
 * Interface DbInterface
 *
 * @package Scaleplan\Db\Interfaces
 */
interface DbInterface
{
    /**
     * @param string[] $schemas - какие схемы будут использоваться
     *
     * @throws ConnectionStringException
     * @throws PDOConnectionException
     * @throws QueryCountNotMatchParamsException
     */
    public function initTablesList(array $schemas);

    /**
     * Сделать запрос к БД, поддерживает подготовленные выражения
     *
     * @param string[]|string $query - запрос
     * @param array $params - параметры запроса
     *
     * @return array|int
     *
     * @throws QueryCountNotMatchParamsException
     */
    public function query($query, array $params = []);

    /**
     * Получить имя драйвера СУБД
     *
     * @return string
     */
    public function getDBDriver() : string;

    /**
     * Вернет подключение к базе данных
     *
     * @return \PDO
     */
    public function getDBH() : ?\PDO;

    /**
     * Начать транзакцию
     *
     * @return bool
     */
    public function beginTransaction() : bool;

    /**
     * Фиксировать транзакцию
     *
     * @return bool
     */
    public function commit() : bool;

    /**
     * Откатить транцакцию
     *
     * @return bool
     */
    public function rollBack() : bool;

    /**
     * Возвращаем имена таблиц использующихся в запросе только для запросов на изменение
     *
     * @param string $query - запрос
     *
     * @return array
     */
    public function getEditTables(string &$query) : array;

    /**
     * Возвращаем имена таблиц использующихся в запросе
     *
     * @param string $query - запрос
     *
     * @return array
     */
    public function getTables(string &$query) : array;

    /**
     * Выполнить пакет транзакций с проверкой результата выполнения. Актуально для PostgreSQL
     *
     * @param string[] $batch - массив транзакций
     *
     * @return bool
     *
     * @throws DbException
     */
    public function execBatch(array $batch) : bool;
}
