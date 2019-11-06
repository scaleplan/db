<?php

namespace Scaleplan\Db\Interfaces;

/**
 * Class TableTags
 *
 * @package Scaleplan\Db
 */
interface TableTagsInterface
{
    /**
     * @param string[] $schemas - какие схемы будут использоваться
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
