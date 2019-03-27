<?php

namespace Scaleplan\Db;

use Scaleplan\Db\Exceptions\BatchExecutionException;
use Scaleplan\Db\Exceptions\DbException;
use Scaleplan\Db\Exceptions\ConnectionStringException;
use Scaleplan\Db\Exceptions\PDOConnectionException;
use Scaleplan\Db\Exceptions\QueryCountNotMatchParamsException;
use Scaleplan\Db\Exceptions\QueryExecutionException;
use Scaleplan\Db\Interfaces\DbInterface;

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
class Db implements DbInterface
{
    /**
     * Доступные драйвера СУБД
     */
    public const ALLOW_DRIVERS = ['pgsql', 'mysql'];

    /**
     * Код указывающий на ошибку произошедшую при попытке добавить дубликат данных
     */
    public const DUPLICATE_ERROR_CODE = '42P04';

    /**
     * Максимальное число параллельных транзакций
     */
    protected const DB_MAX_PARALLEL_CONNECTS = 10;

    /**
     * Путь к файлу с хранимой процедурой, обеспечивающей параллельное выполнение запросов
     */
    protected const EXECUTE_MULTIPLE_PATH = '';

    /**
     * Строка подключения к БД
     *
     * @var string
     */
    protected $dsn = '';

    /**
     * Хэндлер подключения к БД
     *
     * @var null|\PDO
     */
    protected $dbh;

    /**
     * Имя драйвера СУБД
     *
     * @var string
     */
    protected $dbDriver = '';

    /**
     * Список таблиц БД
     *
     * @var array|int
     */
    protected $tables = [];

    /**
     * Возвращать ли пустой массив при отсутствии результата запроса
     *
     * @var bool
     */
    protected $isArrayResults = true;

    /**
     * Сохраненные объекты Db
     *
     * @var array
     */
    public static $instances = [];

    /**
     * Фабрика Db
     *
     * @param string $dsn - строка подключения
     * @param string $login - пользователь БД
     * @param string $password - пароль
     * @param array $options - дополнительные опции
     * @param bool $isArrayResults - возвращать результат только в виде массива
     *
     * @return Db
     *
     * @throws ConnectionStringException
     * @throws PDOConnectionException
     */
    public static function getInstance(
        string $dsn,
        string $login,
        string $password,
        array $options = [],
        bool $isArrayResults = true
    ) : Db
    {
        $key =
            $dsn
            . $login
            . $password
            . json_encode($options, JSON_UNESCAPED_UNICODE)
            . $isArrayResults;

        if (empty(static::$instances[$key])) {
            static::$instances[$key] = new Db($dsn, $login, $password, $options, $isArrayResults);
        }

        return static::$instances[$key];
    }

    /**
     * Конструктор. Намеренно сделан открытым чтобы дать большую гибкость
     *
     * @param string $dsn - строка подключения
     * @param string $login - пользователь БД
     * @param string $password - пароль
     * @param array $options - дополнительные опции
     * @param bool $isArrayResults - возвращать результат только в виде массива
     *
     * @throws ConnectionStringException
     * @throws PDOConnectionException
     */
    public function __construct(
        string $dsn,
        string $login,
        string $password,
        array $options = [],
        bool $isArrayResults = true
    )
    {
        if (!preg_match('/^(.+?):/', $dsn, $matches)) {
            throw new ConnectionStringException('Неверная строка подключения: Не задан драйвер');
        }

        if (!\in_array($matches[1], static::ALLOW_DRIVERS, true)) {
            throw new ConnectionStringException(
                "Подключение с использование драйвера {$matches[1]} недоступно"
            );
        }

        $this->dbDriver = $matches[1];

        if (empty($this->dbh = new \PDO($dsn, $login, $password, $options))) {
            throw new PDOConnectionException();
        }

        $this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->dbh->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_TO_STRING);
        $this->dbh->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        $this->dsn = $dsn;

        $this->isArrayResults = $isArrayResults;
    }

    /**
     * @return string
     */
    public function getDsn() : string
    {
        return $this->dsn;
    }

    /**
     * @param array $params
     * @param string $query
     * @param int $rowCount
     *
     * @return \PDOStatement
     *
     * @throws QueryExecutionException
     */
    protected function execQuery(array &$params, string &$query, int &$rowCount) : \PDOStatement
    {
        if (empty($params)) {
            $sth = $this->dbh->query($query);
        } else {
            $sth = $this->dbh->prepare($query);
            $sth->execute($params);
        }

        if (!$sth) {
            throw new QueryExecutionException();
        }

        $rowCount += $sth->rowCount();
        return $sth;
    }

    /**
     * Сделать запрос к БД, поддерживает подготовленные выражения
     *
     * @param string[]|string $query - запрос
     * @param array $params - параметры запроса
     *
     * @return array|int
     *
     * @throws QueryCountNotMatchParamsException
     * @throws QueryExecutionException
     */
    public function query($query, array $params = [])
    {
        $rowCount = 0;
        if (\is_array($query)) {
            if (!isset($params[0]) && \count($query) !== \count($params)) {
                throw new QueryCountNotMatchParamsException();
            }

            $this->dbh->beginTransaction();
            foreach ($query as $key => & $value) {
                $this->execQuery($params[$key], $value, $rowCount);
            }

            unset($value);
            return $rowCount;
        }

        $sth = $this->execQuery($params, $query, $rowCount);
        $result = $sth->fetchAll();
        if (!$result && !$this->isArrayResults) {
            return $rowCount;
        }

        $result = array_map(function ($record) {
            return array_map(function ($item) {
                return json_decode($item, true) ?? $item;
            }, $record);
        }, $result);

        return $result;
    }

    /**
     * Получить имя драйвера СУБД
     *
     * @return string
     */
    public function getDBDriver() : string
    {
        return $this->dbDriver;
    }

    /**
     * Вернет подключение к базе данных
     *
     * @return \PDO
     */
    public function getDBH() : ?\PDO
    {
        return $this->dbh;
    }

    /**
     * Начать транзакцию
     *
     * @return bool
     */
    public function beginTransaction() : bool
    {
        try {
            return $this->dbh->beginTransaction();
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Фиксировать транзакцию
     *
     * @return bool
     */
    public function commit() : bool
    {
        try {
            return $this->dbh->commit();
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Откатить транцакцию
     *
     * @return bool
     */
    public function rollBack() : bool
    {
        try {
            return $this->dbh->rollBack();
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Формирует строку для асинхронного выполнения методами asyncBatch и execBatch
     *
     * @param string[] $batch - массив транзакций
     *
     * @return string
     */
    protected function createQStrFromBatch(array &$batch) : string
    {
        $queryStr = '';
        foreach ($batch as & $t) {
            if (\count($t) > 1) {
                $queryStr .= 'BEGIN;' . implode(';', $t) . ';' . 'COMMIT;';
            }
        }

        unset($t);
        return $queryStr;
    }

    /**
     * Выполнить пакет транзакций с проверкой результата выполнения. Актуально для PostgreSQL
     *
     * @param string[] $batch - массив транзакций
     *
     * @return bool
     *
     * @throws DbException
     */
    public function execBatch(array $batch) : bool
    {
        $oldAttrEmulatePrepares = $this->dbh->getAttribute(\PDO::ATTR_EMULATE_PREPARES);
        $this->dbh->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
        try {
            $this->dbh->exec($this->createQStrFromBatch($batch));
        } catch (\PDOException $e) {
            $this->dbh->exec('ROLLBACK');
            throw new BatchExecutionException('Ошибка выполнения пакета транзакций: ' . $e->getMessage());
        } finally {
            $this->dbh->setAttribute(\PDO::ATTR_EMULATE_PREPARES, $oldAttrEmulatePrepares);
        }

        return true;
    }
}
