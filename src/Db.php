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
class Db implements \Serializable, DbInterface
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
     * Логин пользователя подключения
     *
     * @var string
     */
    protected $login = '';

    /**
     * Пароль пользователя подключения
     *
     * @var string
     */
    protected $password = '';

    /**
     * Опции подключения
     *
     * @var array
     */
    protected $options = [];

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
     * @var int
     */
    protected $userId;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var string
     */
    protected $timeZone;

    /**
     * @var \PDO
     */
    protected $connection;

    /**
     * @var bool
     */
    protected $isTransactional = true;

    /**
     * @var string
     */
    protected $dbName;

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
                "Подключение с использованием драйвера {$matches[1]} недоступно"
            );
        }

        $this->dbDriver = $matches[1];

        if (!preg_match('/dbname=([^;]+)/i', $dsn, $matches)) {
            throw new ConnectionStringException('Не удалось выделить имя базы данных из строки подключения');
        }

        $this->dbName = $matches[1];

        $this->login = $login;
        $this->password = $password;
        $this->options = $options;
        $this->dsn = $dsn;
        $this->isArrayResults = $isArrayResults;
    }

    /**
     * @return string
     */
    public function getLogin() : string
    {
        return $this->login;
    }

    /**
     * @return string
     */
    public function getPassword() : string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getDbName() : string
    {
        return $this->dbName;
    }

    /**
     * @return bool
     */
    public function isTransactional() : bool
    {
        return $this->isTransactional;
    }

    /**
     * @param bool $isTransactional
     *
     * @throws PDOConnectionException
     */
    public function setIsTransactional(bool $isTransactional) : void
    {
        if ($this->isConnected()) {
            if (!$isTransactional && $this->isTransactional) {
                $this->connection->commit();
            } elseif ($isTransactional && !$this->isTransactional) {
                $this->beginTransaction();
            }
        }

        $this->isTransactional = $isTransactional;
    }

    /**
     * @param int $userId
     */
    public function setUserId(int $userId) : void
    {
        if ($this->connection && $userId !== $this->userId) {
            $this->connection->prepare("SELECT set_config('user.id', :user_id, false)")
                ->execute(['user_id' => $this->userId]);
        }

        $this->userId = $userId;
    }

    /**
     * @param string $locale
     */
    public function setLocale(string $locale) : void
    {
        if ($this->connection && $locale !== $this->locale) {
            $this->connection->prepare("SELECT set_config('lc_messages', :locale, false)")
                ->execute(['locale' => $this->locale]);
        }

        $this->locale = $locale;
    }

    /**
     * @param \DateTimeZone $timeZone
     */
    public function setTimeZone(\DateTimeZone $timeZone) : void
    {
        if ($this->connection && $timeZone->getName() !== $this->timeZone) {
            $this->connection->prepare("SELECT set_config('lc_messages', :locale, false)")
                ->execute(['locale' => $this->locale]);
        }

        $this->timeZone = $timeZone->getName();
    }

    /**
     * @return bool
     */
    public function isConnected() : bool
    {
        return (bool)$this->connection;
    }

    /**
     * Вернет подключение к базе данных
     *
     * @return \PDO
     *
     * @throws PDOConnectionException
     * @throws \PDOException
     */
    public function getConnection() : \PDO
    {
        if (!$this->connection) {
            if (!$this->connection = new \PDO($this->dsn, $this->login, $this->password, $this->options)) {
                throw new PDOConnectionException();
            }

            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_NATURAL);
            $this->connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            $this->connection->setAttribute(\PDO::ATTR_PERSISTENT, true);
            $this->connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

            $this->userId && $this->connection->prepare("SELECT set_config('user.id', :user_id, false)")
                ->execute(['user_id' => $this->userId]);
            $this->timeZone && $this->connection->prepare("SELECT set_config('timezone', :timezone, false)")
                ->execute(['timezone' => $this->timeZone]);
            $this->locale && $this->connection->prepare("SELECT set_config('lc_messages', :locale, false)")
                ->execute(['locale' => $this->locale]);
            $this->isTransactional && $this->beginTransaction();
        }

        return $this->connection;
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
     * @throws PDOConnectionException
     * @throws QueryExecutionException
     */
    protected function execQuery(array &$params, string &$query, int &$rowCount) : \PDOStatement
    {
        if (empty($params)) {
            $sth = $this->getConnection()->query($query);
        } else {
            $sth = $this->getConnection()->prepare($query);
            static::bindParams($sth, $params);
            $sth->execute();
        }

        if (!$sth) {
            throw new QueryExecutionException();
        }

        $rowCount += $sth->rowCount();
        return $sth;
    }

    /**
     * @param \PDOStatement $sth
     * @param array $params
     *
     * @return \PDOStatement
     */
    protected static function bindParams(\PDOStatement $sth, array $params) : \PDOStatement
    {
        foreach ($params as $name => $value) {
            $type = null;
            switch (gettype($value)) {
                case 'boolean':
                    $type = \PDO::PARAM_BOOL;
                    break;

                case 'integer':
                    $type = \PDO::PARAM_INT;
                    break;

                case 'string':
                    $type = \PDO::PARAM_STR;
                    break;
            }

            $sth->bindValue($name, $value, $type);
        }

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
     * @throws PDOConnectionException
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

        $result = array_map(static function ($record) {
            return array_map(static function ($item) {
                return json_decode($item, true) ?? $item;
            }, $record);
        }, $result);

        return $result;
    }

    /**
     * @param string $query
     *
     * @return int
     *
     * @throws PDOConnectionException
     */
    public function exec(string $query) : int
    {
        return $this->getConnection()->exec($query);
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
     * Начать транзакцию
     *
     * @return bool
     *
     * @throws PDOConnectionException
     */
    public function beginTransaction() : bool
    {
        return !$this->getConnection()->inTransaction() && $this->getConnection()->beginTransaction();
    }

    /**
     * Фиксировать транзакцию
     *
     * @return bool
     *
     * @throws PDOConnectionException
     */
    public function commit() : bool
    {
        return $this->isConnected() && $this->getConnection()->inTransaction() && $this->getConnection()->commit();
    }

    /**
     * Откатить транцакцию
     *
     * @return bool
     *
     * @throws PDOConnectionException
     */
    public function rollBack() : bool
    {
        return $this->isConnected() && $this->getConnection()->inTransaction() && $this->getConnection()->rollBack();
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
        $oldAttrEmulatePrepares = $this->getConnection()->getAttribute(\PDO::ATTR_EMULATE_PREPARES);
        $this->getConnection()->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
        try {
            $this->getConnection()->exec($this->createQStrFromBatch($batch));
        } catch (\PDOException $e) {
            $this->getConnection()->exec('ROLLBACK');
            throw new BatchExecutionException('Ошибка выполнения пакета транзакций: ' . $e->getMessage());
        } finally {
            $this->getConnection()->setAttribute(\PDO::ATTR_EMULATE_PREPARES, $oldAttrEmulatePrepares);
        }

        return true;
    }

    /**
     * @return string
     */
    public function serialize() : string
    {
        return $this->getDsn();
    }

    /**
     * @param string $serialized
     *
     * @throws DbException
     */
    public function unserialize($serialized) : void
    {
        throw new DbException('Unserialize not supporting.');
    }
}
