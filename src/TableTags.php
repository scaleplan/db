<?php

namespace Scaleplan\Db;

use Scaleplan\Db\Exceptions\ConnectionStringException;
use Scaleplan\Db\Exceptions\PDOConnectionException;
use Scaleplan\Db\Interfaces\DbInterface;
use Scaleplan\Db\Interfaces\TableTagsInterface;

/**
 * Class TableTags
 *
 * @package Scaleplan\Db
 */
class TableTags implements TableTagsInterface
{
    /**
     * С какими схемами дополнительно будет рабоать объект при подключении к PosqlgreSQL
     */
    public const PGSQL_ADDITIONAL_TABLES = ['pg_type', 'pg_enum'];

    public const SYSTEM_SCHEMAS = ['pg_catalog', 'information_schema'];

    /**
     * С какими схемами дополнительно будет рабоать объект при подключении к MySQL
     */
    public const MYSQL_ADDITIONAL_TABLES = [];

    /**
     * @var DbInterface
     */
    protected $db;

    /**
     * @var array
     */
    protected $tables;

    /**
     * TableTags constructor.
     *
     * @param DbInterface $db
     */
    public function __construct(DbInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Добавить дополнительные таблицы к используемым
     */
    protected function addAdditionTables() : void
    {
        $dbms = strtoupper($this->db->getDBDriver());
        foreach (\constant("static::{$dbms}_ADDITIONAL_TABLES") as $table) {
            $this->tables[]['table_name'] = $table;
        }
    }

    /**
     * Инициализировать хранение имен таблиц в сессии
     *
     * @param string $dbName - имя базы данных
     */
    protected static function initSessionStorage(string $dbName) : void
    {
        if (!isset($_SESSION['databases']) || !\is_array($_SESSION['databases'])) {
            $_SESSION['databases'] = [];
        }

        if (!isset($_SESSION['databases'][$dbName]) || !\is_array($_SESSION['databases'][$dbName])) {
            $_SESSION['databases'][$dbName] = ['tables' => []];
        }
    }

    /**
     * @param string[]|null $schemas - какие схемы будут использоваться
     *
     * @throws ConnectionStringException
     * @throws Exceptions\QueryCountNotMatchParamsException
     * @throws Exceptions\QueryExecutionException
     * @throws PDOConnectionException
     */
    public function initTablesList(array $schemas = null) : void
    {
        if (!preg_match('/dbname=([^;]+)/i', $this->db->getDsn(), $matches)) {
            throw new ConnectionStringException('Не удалось выделить имя базы данных из строки подключения');
        }

        $dbName = $matches[1];

        if (!empty($_SESSION['databases'][$dbName]['tables'])) {
            $this->tables = $_SESSION['databases'][$dbName]['tables'];
            return;
        }

        static::initSessionStorage($dbName);

        $this->addAdditionTables();

        if ($this->db->getDBDriver() === 'pgsql') {
            $_SESSION['databases'][$dbName]['tables']
                = $this->tables
                = array_merge($this->tables, $this->getPostgresTables($schemas));
        } elseif ($this->db->getDBDriver() === 'mysql') {
            $_SESSION['databases'][$dbName]['tables']
                = $this->tables
                = array_merge($this->tables, $this->getMysqlTables($dbName));
        }

        if (!$this->tables) {
            throw new PDOConnectionException('Не удалось получить список таблиц');
        }
    }

    /**
     * @param array|null $schemas
     *
     * @return array
     *
     * @throws Exceptions\QueryCountNotMatchParamsException
     * @throws Exceptions\QueryExecutionException
     * @throws PDOConnectionException
     */
    protected function getPostgresTables(array $schemas = null) : array
    {
        if (null !== $schemas) {
            return $this->db->query(
                "SELECT 
                        (CASE WHEN table_schema = 'public' 
                              THEN '' 
                              ELSE table_schema || '.' 
                         END) || table_name AS table_name 
                    FROM information_schema.tables 
                    WHERE table_schema = ANY(string_to_array(:schemas, ','))",
                ['schemas' => implode(',', $schemas)]
            );
        }

        return $this->db->query(
            "SELECT 
                        (CASE WHEN table_schema = 'public' 
                              THEN '' 
                              ELSE table_schema || '.' 
                         END) || table_name AS table_name 
                    FROM information_schema.tables 
                    WHERE table_schema != ALL(string_to_array(:schemas, ','))",
            ['schemas' => implode(',', static::SYSTEM_SCHEMAS)]
        );
    }

    /**
     * @param string $dbName
     *
     * @return array
     *
     * @throws Exceptions\QueryCountNotMatchParamsException
     * @throws Exceptions\QueryExecutionException
     * @throws PDOConnectionException
     */
    protected function getMysqlTables(string $dbName) : array
    {
        return $this->db->query("SHOW TABLES FROM $dbName");
    }

    /**
     * Возвращаем имена таблиц использующихся в запросе только для запросов на изменение
     *
     * @param string $query - запрос
     *
     * @return array
     */
    public function getEditTables(string &$query) : array
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
    public function getTables(string &$query) : array
    {
        $tables = [];
        foreach ($this->tables as &$table) {
            if (strpos($query, $table['table_name']) !== false) {
                $tables[] = $table['table_name'];
            }
        }

        unset($table);
        return $tables;
    }
}
