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
     * @return _PDO
     * @throws Exception
     */
    public static function create ($dbdriver = 'pgsql', $login = 'edu_user', $password = 'asEx8AmXk9', $dbname = 'edu', $hostorsock = '/var/run/postgresql', $port = 5432)
    {
        try
        {
            if (!self::$instance)
            {
                if (file_exists($_SERVER['DOCUMENT_ROOT'].'/confs/db.ini') && $init = parse_ini_file($_SERVER['DOCUMENT_ROOT'].'/confs/db.ini'))
                {
                    extract($init);
                }

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
                    self::$instance = new _PDO ($dbh, $dbdriver);
                }
                else
                {
                    throw new _PDOException('Не удалось подключение к базе данных');
                }
            }
            return self::$instance;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    private function __construct (&$dbh, &$dbdriver)
    {
        $this->dbh = $dbh;
        try
        {
            $this->dbdriver = $dbdriver;
            $this->dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->dbh->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_TO_STRING);
            //$this->dbh->setAttribute(PDO::ATTR_PERSISTENT , true);
            //$this->dbh->setAttribute(PDO::ATTR_STRINGIFY_FETCHES , true);
            if (!isset($_SESSION['tables']))
            {
                if ($this->tables = $this->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'"))
                {
                    $this->tables[]['table_name'] = 'pg_type';
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
        catch (Exception $e)
        {
            throw $e;
        }
    }

    public function getDBDriver()
    {
        return $this->dbdriver;
    }

    /**
     * Сделать запрос к БД, поддерживает подготовленные выражения
     *
     * @param $query
     * @param array $params
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
        try
        {
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
                    //var_dump($result);
                    return $result;
                }
            }
            else
            {
                return false;
            }
        }
        catch (PDOException $e)
        {
            throw $e;
        }
    }

    function beginTransaction ()
    {
        try
        {
            $this->dbh->beginTransaction();
        }
        catch (Exception $e)
        {

        }
    }

    function commit ()
    {
        try
        {
            $this->dbh->commit();
        }
        catch (Exception $e)
        {

        }
    }

    function rollBack ()
    {
        try
        {
            $this->dbh->rollBack();
        }
        catch (Exception $e)
        {

        }
    }

    /**
     * Возвращаем имена таблиц использующихся в запросе на изменение
     *
     * @param bool $query
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

    function getEditTables ($query)
    {
        if (preg_match('/(update|insert\sinto|delete)/i', $query))
        {
            return $this->getTables($query);
        }
        else
        {
            return false;
        }
    }

}
