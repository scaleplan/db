<?php

namespace Scaleplan\Db\Interfaces;

/**
 * Db представляет собой класс-обертку для взаимодествия PHP-приложения с СУБД PostgreSQL и MySQL.
 * Позволяет прозрачно взаимодействовать с любой из этих СУБД не вникая в различия взаимодейтвия PHP с этими системами –
 * для разработчика работа с обоими СУБД будет одинакова с точки зрения программирования.
 * Класс поддерживает подготовленные выражения. Кроме того есть дополнительная функциональность для реализации концепции
 * параллельного выполнения запросов внутри одного подключени к базе данных. А так же есть методы для реализации
 * асинхронного выполнения пакетов запросов.
 *
 * Class Db
 *
 * @package Scaleplan\Db
 */
interface DbInterface
{
    /**
     * @return string
     */
    public function getDbName() : string;

    /**
     * @return string
     */
    public function getLogin() : string;

    /**
     * @return string
     */
    public function getPassword() : string;

    /**
     * @return bool
     */
    public function isTransactional() : bool;

    /**
     * @param bool $isTransactional
     */
    public function setIsTransactional(bool $isTransactional) : void;

    /**
     * @param int $userId
     */
    public function setUserId(int $userId) : void;

    /**
     * @param string $locale
     */
    public function setLocale(string $locale) : void;

    /**
     * @param \DateTimeZone $timeZone
     */
    public function setTimeZone(\DateTimeZone $timeZone) : void;

    /**
     * @return bool
     */
    public function isConnected() : bool;

    /**
     * Вернет подключение к базе данных
     *
     * @return \PDO
     */
    public function getConnection() : \PDO;

    /**
     * @return string
     */
    public function getDsn() : string;

    /**
     * Сделать запрос к БД, поддерживает подготовленные выражения
     *
     * @param string[]|string $query - запрос
     * @param array $params - параметры запроса
     *
     * @return array|int
     */
    public function query($query, array $params = []);

    /**
     * Получить имя драйвера СУБД
     *
     * @return string
     */
    public function getDBDriver() : string;

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
     * Выполнить пакет транзакций с проверкой результата выполнения. Актуально для PostgreSQL
     *
     * @param string[] $batch - массив транзакций
     *
     * @return bool
     */
    public function execBatch(array $batch) : bool;

    /**
     * @return string
     */
    public function serialize() : string;

    /**
     * @param string $serialized
     */
    public function unserialize($serialized) : void;
}
