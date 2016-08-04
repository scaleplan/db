<?php
/**
 * Created by PhpStorm.
 * User: Александр
 * Date: 11.04.14
 * Time: 10:45
 */

require_once(DB_ERRORS_LANG . '.errors.php');

class _PDOException extends Exception { }

class _PDO
{
    private $dbh = false;
    private $dbdriver = false;
    private $tables = false;

    private static $dsn = false;
    private static $instance = false;

    /**
     * Singleton для объекта класса
     *
     * @param string $hostorsock - хост или сокет для подключения к БД, если порт не задан считается что используется сокет
     * @param int $port - порт для подключения к БД
     * @param string $dbdriver - используемый драйвер СУБД (поддерживаются СУБД MySQL и PostgreSQL)
     * @param string $login - логин для подключения к БД
     * @param string $password - пароль для подключения к БД
     * @param string $dbname - имя базы данных
     *
     * @return _PDO
     * @throws Exception
     */
    public static function create (string $dbdriver = DB_DRIVER, string $login = DB_LOGIN, string $password = DB_PASSWORD, string $dbname = DB_NAME, string $hostorsock = DB_SOCKET, int $port = DB_PORT)
    {
        if (!self::$instance)
        {
            if (extension_loaded('pdo_'.$dbdriver))
            {
                $dbh = false;
                switch ($dbdriver)
                {
                    case 'mysql':
                        if (!$port)
                        {
                            self::$dsn = "dbname=$dbname; unix_socket=$hostorsock";
                        }
                        else
                        {
                            self::$dsn = "dbname=$dbname; host=$hostorsock; port=$port";
                        }
                        $dbh = new PDO('mysql:' . self::$dsn, $login, $password);
                        break;

                    case 'pgsql':
                        self::$dsn = "user=$login host=$hostorsock port=$port dbname=$dbname password=$password";
                        $dbh = new PDO('pgsql:' . self::$dsn, null, null, [PDO::ATTR_PERSISTENT => true,
                                                                           PDO::ATTR_STRINGIFY_FETCHES => true,
                                                                           PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                                                           PDO::ATTR_EMULATE_PREPARES => DB_PERSISTENT,
                                                                           PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                                                                           PDO::ATTR_ORACLE_NULLS, PDO::NULL_TO_STRING]);
                        break;
                }
            }
            else
            {
                throw new _PDOException(DB_NO_DBDRIVERS . $dbdriver);
            }

            if ($dbh)
            {
                self::$instance = new _PDO ($dbh, $dbdriver, $dbname);
            }
            else
            {
                throw new _PDOException(DB_CONNECT_ERROR);
            }
        }
        return self::$instance;
    }

    /**
     * Конструктор класса
     *
     * @param $dbh - подключение к БД
     * @param $dbdriver - используемый драйвер СУБД ('pgsql'|'mysql')
     * @param $dbname - Имя БД
     *
     * @throws _PDOException
     */
    private function __construct (PDO & $dbh, string & $dbdriver, string & $dbname)
    {
        $this->dbh = $dbh;
        $this->dbdriver = $dbdriver;
        if (!isset($_SESSION['tables']))
        {
            if ($dbdriver == 'pgsql')
            {
                $this->tables = $this->query("SELECT 
                                               (CASE 
                                                  WHEN 
                                                    table_schema = 'public' 
                                                  THEN 
                                                    '' 
                                                  ELSE 
                                                    table_schema || '.' 
                                                END) || table_name AS table_name 
                                              FROM 
                                                information_schema.tables 
                                              WHERE 
                                                table_schema IN (" . DB_SCHEMAS . ")");
                $this->tables[]['table_name'] = 'pg_type';
                $this->tables[]['table_name'] = 'pg_enum';
            }
            else
            {
                $this->tables = $this->query(" show tables from $dbname");
            }
            if ($this->tables)
            {
                $_SESSION['tables'] = $this->tables;
            }
            else
            {
                throw new _PDOException(DB_NO_TABLES);
            }
        }
        else
        {
            $this->tables = $_SESSION['tables'];
        }
    }

    /**
     * Получить имя драйвера СУБД
     *
     * @return bool
     */
    public function getDBDriver ()
    {
        return $this->dbdriver;
    }

    /**
     * Вернет подключение к базе данных
     *
     * @return bool
     */
    public function getDBH ()
    {
        return $this->dbh;
    }

    /**
     * Сделать запрос к БД, поддерживает подготовленные выражения
     *
     * @param $query - запрос
     * @param array $params - параметры запроса
     *
     * @return int
     * @throws Exception
     * @throws PDOException
     */
    public function query($query, array $params = [])
    {
        $execQuery = function(array & $params, string & $query, int & $row_count)
        {
            if ($params)
            {
                $sth = $this->dbh->prepare($query);
                $sth->execute($params);
            }
            else
            {
                $sth = $this->dbh->query($query);
            }
            if ($sth)
            {
                $row_count += $sth->rowCount();
                return $sth;
            }
            else
            {
                throw new _PDOException('Не удалось выполнить запрос');
            }
        };
        $row_count = 0;
        if (is_array($query) && isset($params[0]) && count($query) == count($params))
        {
            $this->dbh->beginTransaction();
            foreach ($query as $key => & $value)
            {
                $execQuery($params[$key], $value, $row_count);
            }
            unset($value);
            $this->dbh->commit();
            return $row_count;
        }
        else
        {
            $sth = $execQuery($params, $query, $row_count);
            $result = $sth->fetchAll();
            if (count($result) == 0)
            {
                return $row_count;
            }
            else
            {
                return $result;
            }
        }
    }

    /**
     * Начать транзакцию
     */
    public function beginTransaction ()
    {
        try
        {
            return $this->dbh->beginTransaction();
        }
        catch (PDOException $e)
        {

        }
    }

    /**
     * Фиксировать транзакцию
     */
    public function commit ()
    {
        try
        {
            return $this->dbh->commit();
        }
        catch (PDOException $e)
        {

        }
    }

    /**
     * Откатить транцакцию
     */
    public function rollBack ()
    {
        try
        {
            return $this->dbh->rollBack();
        }
        catch (PDOException $e)
        {

        }
    }

    /**
     * Возвращаем имена таблиц использующихся в запросе
     *
     * @param bool $query - запрос
     *
     * @return array|string
     * @throws _PDOException
     */
    public function getTables (string & $query)
    {
        $tables = false;
        foreach ($this->tables as & $table)
        {
            if (strpos($query, $table['table_name']) !== false)
            {
                $tables[] = $table['table_name'];
            }
        }
        unset($table);
        return $tables;
    }

    /**
     * Возвращаем имена таблиц использующихся в запросе только для запросов на изменение
     *
     * @param $query - запрос
     *
     * @return array|bool|string
     */
    public function getEditTables (string & $query)
    {
        if (preg_match('/(UPDATE|INSERT\sINTO|DELETE)/', $query))
        {
            return $this->getTables($query);
        }
        else
        {
            return false;
        }
    }

    /**
     * Выполнить параллельно пакет транзикций. Актуально для PostgreSQL
     *
     * @param array $batch - массив транзакций
     *
     * @return array
     */
    public function parallelExecute(array $batch)
    {
        if (!count($this->query("select proname from pg_proc where proname = 'execute_multiple'")))
        {
            $sql = file_get_contents('execute_multiple.sql');
            $this->dbh->exec($sql);
        }
        $query = 'SELECT execute_multiple(ARRAY[';
        foreach ($batch as & $t)
        {
            $query .= "'" . implode(';', $t) . "',";
        }
        unset($t);
        $query = trim($query, ',');
        $count = count($batch);

        $count = DB_MAX_PARALLEL_CONNECTS <= $count ? DB_MAX_PARALLEL_CONNECTS : $count;
        $query .= "], $count, '" . self::$dsn . "') AS failed";

        $result = $this->query($query);

        $failed = explode(',', $result[0]['failed']);
        foreach ($batch as $key => & $value)
        {
            if (!in_array($key + 1, $failed))
            {
                unset($batch[$key]);
            }
        }
        unset($value);

        return $batch;
    }

    /**
     * Формирует строку для асинхронного выполнения методами asyncBatch и execBatch
     *
     * @param array $batch
     * @return string
     */
    private function createQStrFromBatch (array & $batch)
    {
        $query_str = '';
        foreach ($batch as & $t)
        {
            if (count($t) > 1)
            {
                $query_str .= 'BEGIN;' . implode(';', $t) . ';' . 'COMMIT;';
            }
        }
        unset($t);
        return $query_str;
    }

    /**
     * Отправить асинхронно пакет транзакций на сервер
     *
     * @param array $batch - массив транзакций. Актуально для PostgreSQL
     *
     * @return bool
     * @throws _PDOException
     */
    public function asyncBatch(array $batch)
    {

        if ($db = pg_connect(self::$dsn))
        {
            $result = pg_send_query($db, $this->createQStrFromBatch($batch));

            pg_close($db);

            if (!$result)
            {
                throw new _PDOException(DB_BATCH_SEND_ERROR);
            }
            return true;
        }
        else
        {
            throw new _PDOException(DB_NATIVE_CONNECT_ERROR);
        }
    }

    /**
     * Выполнить пакет транзакций с проверкой результата выполнения. Актуально для PostgreSQL
     *
     * @param array $batch - массив транзакций
     *
     * @return bool
     * @throws _PDOException
     */
    public function execBatch (array $batch)
    {
        $this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        try
        {
            $this->dbh->exec($this->createQStrFromBatch($batch));
        }
        catch (PDOException $e)
        {
            $this->dbh->exec('ROLLBACK');
            throw new _PDOException(DB_BATCH_ERROR . $e->getMessage());
        }
        finally
        {
            $this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, DB_EMULATE_PREPARES);
        }

        return true;
    }

}
