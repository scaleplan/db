<?php

namespace Scaleplan\Db\Interfaces;


use Scaleplan\Db\Exceptions;
use Scaleplan\Db\Exceptions\ConnectionStringException;
use Scaleplan\Db\Exceptions\PDOConnectionException;

/**
 * Class TableTags
 *
 * @package Scaleplan\Db
 */
interface TableTagsInterface
{
    /**
     * @param string[] $schemas - какие схемы будут использоваться
     *
     * @throws ConnectionStringException
     * @throws Exceptions\QueryCountNotMatchParamsException
     * @throws PDOConnectionException
     */
    public function initTablesList(array $schemas) : void;

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
}
