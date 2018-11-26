<?php

namespace Scaleplan\Db;

use Scaleplan\Db\Exceptions\AsyncExecutionException;
use Scaleplan\Db\Exceptions\BatchExecutionException;
use Scaleplan\Db\Exceptions\DbException;
use Scaleplan\Db\Exceptions\ConnectionStringException;
use Scaleplan\Db\Exceptions\ParallelExecutionException;
use Scaleplan\Db\Exceptions\PDOConnectionException;
use Scaleplan\Db\Exceptions\QueryCountNotMatchParamsException;
use Scaleplan\Db\Exceptions\QueryExecutionException;

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
class Db
{
    /**
     * Доступные драйвера СУБД
     */
    public const ALLOW_DRIVERS = ['pgsql', 'mysql'];

    /**
     * С какими схемами дополнительно будет рабоать объект при подключении к PosqlgreSQL
     */
    public const PGSQL_ADDITIONAL_TABLES = ['pg_type', 'pg_enum'];

    /**
     * С какими схемами дополнительно будет рабоать объект при подключении к MySQL
     */
    public const MYSQL_ADDITIONAL_TABLES = [];

    /**
     * Код указывающий на ошибку произошедшую при попытке добавить дубликат данных
     */
    public const DUPLICATE_ERROR_CODE = '42P04';

    /**
     * Максимальное число параллельных транзакций
     *
     * @const int
     */
    protected const DB_MAX_PARALLEL_CONNECTS = 10;

    /**
     * Путь к файлу с хранимой процедурой, обеспечивающей параллельное выполнение запросов
     *
     * @const string
     */
    protected const EXECUTE_MULTIPLE_PATH = '';

    /**
     * Строка подключения к БД
     *
     * @var string
     */
    protected $dns = '';

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
     * @param string $dns - строка подключения
     * @param string $login - пользователь БД
     * @param string $password - пароль
     * @param string[] $schemas - какие схемы будут использоваться
     * @param array $options - дополнительные опции
     * @param bool $isArrayResults - возвращать результат только в виде массива
     *
     * @return Db
     *
     * @throws ConnectionStringException
     * @throws PDOConnectionException
     */
    public static function getInstance(
        string $dns,
        string $login,
        string $password,
        array $schemas = [],
        array $options = [],
        bool $isArrayResults = true
    ): Db {
        $key =
            $dns
            . $login
            . $password
            . json_encode($schemas, JSON_UNESCAPED_UNICODE)
            . json_encode($options, JSON_UNESCAPED_UNICODE)
            . (string) $isArrayResults;

        if (empty(static::$instances[$key])) {
            static::$instances[$key] = new Db($dns, $login, $password, $options, $isArrayResults);
        }

        return static::$instances[$key];
    }

    /**
     * Конструктор. Намеренно сделан открытым чтобы дать большую гибкость
     *
     * @param string $dns - строка подключения
     * @param string $login - пользователь БД
     * @param string $password - пароль
     * @param array $options - дополнительные опции
     * @param bool $isArrayResults - возвращать результат только в виде массива
     *
     * @throws ConnectionStringException
     * @throws PDOConnectionException
     */
    public function __construct(
        string $dns,
        string $login,
        string $password,
        array $options = [],
        bool $isArrayResults = true
    )
    {
        if (!preg_match('/^(.+?):/', $dns, $matches)) {
            throw new ConnectionStringException('Неверная строка подключения: Не задан драйвер');
        }

        if (!\in_array($matches[1], static::ALLOW_DRIVERS, true)) {
            throw new ConnectionStringException(
                "Подключение с использование драйвера {$matches[1]} недоступно"
            );
        }

        $this->dbDriver = $matches[1];

        if (empty($this->dbh = new \PDO($dns, $login, $password, $options))) {
            throw new PDOConnectionException();
        }

        $this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->dbh->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_TO_STRING);
        $this->dbh->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        $this->dns = $dns;

        $this->isArrayResults = $isArrayResults;
    }

    /**
     * @param string[] $schemas - какие схемы будут использоваться
     *
     * @throws ConnectionStringException
     * @throws PDOConnectionException
     * @throws QueryCountNotMatchParamsException
     */
    public function initTablesList(array $schemas)
    {
        if (!preg_match('/dbname=(.+)/i', $this->dns, $matches)) {
            throw new ConnectionStringException('Не удалось выделить имя базы данных из строки подключения');
        }

        $dbName = $matches[1];

        if (!empty($_SESSION['databases'][$dbName]['tables'])) {
            $this->tables = $_SESSION['databases'][$dbName]['tables'];
            return;
        }

        if (!isset($_SESSION['databases']) || !\is_array($_SESSION['databases'])) {
            $_SESSION['databases'] = [];
        }

        if (!isset($_SESSION['databases'][$dbName]) || !\is_array($_SESSION['databases'][$dbName])) {
            $_SESSION['databases'][$dbName] = ['tables' => []];
        }

        $this->addAdditionTables();

        if ($this->dbDriver === 'pgsql') {
            static::initSessionStorage($dbName);

            $_SESSION['databases'][$dbName]['tables']
                = $this->tables = array_merge($this->tables, $this->query(
                "SELECT 
                        (CASE WHEN table_schema = 'public' 
                              THEN '' 
                              ELSE table_schema || '.' 
                         END) || table_name AS table_name 
                    FROM information_schema.tables 
                    WHERE table_schema IN ('" . implode("', '", $schemas) . "')"));
        } elseif ($this->dbDriver === 'mysql') {
            if (!preg_match('/dbname=(.+?)/i', $this->dns, $matches)) {
                throw new ConnectionStringException(
                    'Не удалось выделить имя базы данных из строки подключения'
                );
            }

            $_SESSION['databases'][$dbName]['tables']
                = $this->tables
                = array_merge($this->tables, $this->query("SHOW TABLES FROM {$matches[1]}"));
        }

        if (!$this->tables) {
            throw new PDOConnectionException('Не удалось получить список таблиц');
        }
    }

    /**
     * Добавить дополнительные таблицы к используемым
     */
    protected function addAdditionTables(): void
    {
        $dbms = strtoupper($this->dbDriver);
        foreach (\constant("static::{$dbms}_ADDITIONAL_TABLES") as $table) {
            $this->tables[]['table_name'] = $table;
        }
    }

    /**
     * Инициализировать хранение имен таблиц в сессии
     *
     * @param string $dbName - имя базы данных
     */
    protected static function initSessionStorage(string $dbName): void
    {
        if (!isset($_SESSION['databases'])) {
            $_SESSION['databases'] = [];
        }

        if (!isset($_SESSION['databases'][$dbName])) {
            $_SESSION['databases'][$dbName] = [];
        }
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
     */
    public function query($query, array $params = [])
    {
        $execQuery = function (array &$params, string &$query, int &$rowCount) {
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
        };

        $rowCount = 0;
        if (\is_array($query)) {
            if (!isset($params[0]) && \count($query) !== \count($params)) {
                throw new QueryCountNotMatchParamsException();
            }

            $this->dbh->beginTransaction();
            foreach ($query as $key => & $value) {
                $execQuery($params[$key], $value, $rowCount);
            }

            unset($value);
            return $rowCount;
        }

        $sth = $execQuery($params, $query, $rowCount);
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
    public function getDBDriver(): string
    {
        return $this->dbDriver;
    }

    /**
     * Вернет подключение к базе данных
     *
     * @return \PDO
     */
    public function getDBH(): ?\PDO
    {
        return $this->dbh;
    }

    /**
     * Начать транзакцию
     *
     * @return bool
     */
    public function beginTransaction(): bool
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
    public function commit(): bool
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
    public function rollBack(): bool
    {
        try {
            return $this->dbh->rollBack();
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Возвращаем имена таблиц использующихся в запросе только для запросов на изменение
     *
     * @param string $query - запрос
     *
     * @return array
     */
    public function getEditTables(string &$query): array
    {
        if (preg_match('/(UPDATE\s|INSERT\sINTO\s|DELETE\s|ALTER\sTYPE)/', $query)) {
            return $this->getTables($query);
        }

        return [];
    }

    /**
     * Возвращаем имена таблиц использующихся в запросе
     *
     * @param string $query - запрос
     *
     * @return array
     */
    public function getTables(string &$query): array
    {
        $tables = [];
        foreach ($this->tables as & $table) {
            if (strpos($query, $table['table_name']) !== false) {
                $tables[] = $table['table_name'];
            }
        }

        unset($table);
        return $tables;
    }

    /**
     * Формирует строку для асинхронного выполнения методами asyncBatch и execBatch
     *
     * @param string[] $batch - массив транзакций
     *
     * @return string
     */
    protected function createQStrFromBatch(array &$batch): string
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
    public function execBatch(array $batch): bool
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