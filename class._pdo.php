<?php
/**
 * Created by PhpStorm.
 * User: Александр
 * Date: 11.04.14
 * Time: 10:45
 */

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
    public static function create ($dbdriver = DB_DRIVER, $login = DB_LOGIN, $password = DB_PASSWORD, $dbname = DB_NAME, $hostorsock = DB_SOCKET, $port = DB_PORT)
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
                                                                           PDO::ATTR_EMULATE_PREPARES => DB_EMULATE_PREPARES,
                                                                           PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                                                                           PDO::ATTR_ORACLE_NULLS, PDO::NULL_TO_STRING]);
                        break;
                }
            }
            else
            {
                throw new _PDOException("Не найдены подходящие расширения для работы с $dbdriver");
            }

            if ($dbh)
            {
                self::$instance = new _PDO ($dbh, $dbdriver, $dbname);
            }
            else
            {
                throw new _PDOException('Не удалось подключение к базе данных');
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
     */
    private function __construct ($dbh, $dbdriver, $dbname)
    {
        $this->dbh = $dbh;
        $this->dbdriver = $dbdriver;
        //$this->dbh->setAttribute(PDO::ATTR_PERSISTENT , true);
        //$this->dbh->setAttribute(PDO::ATTR_STRINGIFY_FETCHES , true);
        if (!isset($_SESSION['tables']))
        {
            if ($dbdriver == 'pgsql')
            {
                $this->tables = $this->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
            }
            else
            {
                $this->tables = $this->query("SELECT table_name FROM information_schema.tables WHERE table_schema = '$dbname'");
            }
            if ($this->tables)
            {
                $_SESSION['tables'] = json_encode($this->tables, JSON_UNESCAPED_UNICODE);
            }
            else
            {
                throw new _PDOException('Список таблиц пуст');
            }
        }
        else
        {
            $this->tables = json_decode($_SESSION['tables'], true);
        }
    }

    /**
     * Получить имя драйвера СУБД
     *
     * @return bool
     */
    public function getDBDriver()
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
        $execQuery = function() use (&$params, &$query, &$row_count)
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
                return false;
            }
        };
        $row_count = 0;
        if (is_array($query) && isset($params[0]) && count($query) == count($params))
        {
            $this->dbh->beginTransaction();
            foreach ($query AS $key => $value)
            {
                $sth = $execQuery();
                if ($sth)
                {
                    $row_count += $sth->rowCount();
                }
            }
            $this->dbh->commit();
        }
        else
        {
            $sth = $execQuery();
        }

        if ($sth)
        {
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
        else
        {
            return false;
        }
    }

    /**
     * Начать транзакцию
     */
    public function beginTransaction ()
    {
        try
        {
            $this->dbh->beginTransaction();
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
            $this->dbh->commit();
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
            $this->dbh->rollBack();
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
    private function getTables ($query)
    {
        $tables = false;
        foreach ($this->tables AS $table)
        {
            if (strpos($query, $table['table_name']) !== false)
                $tables[] = $table['table_name'];
        }
        return $tables;
    }

    /**
     * Возвращаем имена таблиц использующихся в запросе только для запросов на изменение
     *
     * @param $query - запрос
     *
     * @return array|bool|string
     */
    public function getEditTables ($query)
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

    public function parallelExecute(array $batch)
    {
        if (!count($this->query("select proname from pg_proc where proname = 'execute_multiple'")))
        {
            $sql = file_get_contents('execute_multiple.sql');
            $this->dbh->exec($sql);
        }
        $query = 'SELECT execute_multiple(ARRAY[';
        foreach ($batch AS $t)
        {
            $query .= "'" . implode(';', $t) . "',";
        }
        $query = trim($query, ',');
        $count = count($batch);

        $count = DB_MAX_PARALLEL_CONNECTS <= $count ? DB_MAX_PARALLEL_CONNECTS : $count;
        $query .= "], $count, '" . self::$dsn . "') AS failed";

        $result = $this->query($query);

        $failed = explode(',', $result[0]['failed']);
        foreach ($batch AS $key => $value)
        {
            if (!in_array($key + 1, $failed))
            {
                unset($batch[$key]);
            }
        }

        return $batch;
    }

    private function createQStrFromBatch (array $batch)
    {
        $query_str = '';
        foreach ($batch AS $t)
        {
            if (count($t) > 1)
            {
                $query_str .= 'BEGIN;' . implode(';', $t) . ';' . 'COMMIT;';
            }
        }
        return $query_str;
    }

    public function asyncBatch(array $batch)
    {

        if ($db = pg_connect(self::$dsn))
        {
            $result = pg_send_query($db, $this->createQStrFromBatch($batch));

            pg_close($db);

            if (!$result)
            {
                throw new _PDOException('Не удалось отправить пакет запросов на выполнение');
            }
            return true;
        }
        else
        {
            throw new _PDOException('Не удалось создать pg_-подключение');
        }
    }

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
            throw new _PDOException('Пакет запросов выполнен с ошибкой: ' . $e->getMessage());
        }
        finally
        {
            $this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, DB_EMULATE_PREPARES);
        }

        return true;
    }

}
