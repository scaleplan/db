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
                            $dsn = "mysql: dbname=$dbname; unix_socket=$hostorsock";
                        }
                        else
                        {
                            $dsn = "mysql:dbname=$dbname; host=$hostorsock; port=$port";
                        }
                        $dbh = new PDO($dsn, $login, $password);
                        break;

                    case 'pgsql':
                        $dsn = "pgsql:user=$login host=$hostorsock port=$port dbname=$dbname password=$password";
                        $dbh = new PDO($dsn, null, null, [PDO::ATTR_PERSISTENT => true, PDO::ATTR_STRINGIFY_FETCHES => true]);
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
        $this->dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->dbh->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_TO_STRING);
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
     * Сделать запрос к БД, поддерживает подготовленные выражения
     *
     * @param $query - запрос
     * @param array $params - параметры запроса
     *
     * @return int
     * @throws Exception
     * @throws PDOException
     */
    function query($query, array $params = array())
    {
        $execQuery = function(&$params, &$query, &$db, &$row_count)
        {
            try
            {
                if ($params)
                {
                    $sth = $db->prepare($query);
                    $sth->execute($params);
                }
                else
                {
                    $sth = $db->query($query);
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
            }
            catch (Exception $e)
            {
                throw $e;
            }
        };
        $row_count = 0;
        if (is_array($query) && isset($params[0]) && count($query) == count($params))
        {
            $this->dbh->beginTransaction();
            foreach ($query AS $key => $value)
            {
                $sth = $execQuery($params[$key], $value, $this->dbh, $row_count);
                if ($sth)
                {
                    $row_count += $sth->rowCount();
                }
            }
            $this->dbh->commit();
        }
        else
        {
            $sth = $execQuery($params, $query, $this->dbh, $row_count);
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
    function beginTransaction ()
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
    function commit ()
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
    function rollBack ()
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
    function getTables ($query)
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
    function getEditTables ($query)
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

}
