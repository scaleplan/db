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
    public static function create ($dbdriver = 'pgsql', $login = 'pgsql', $password = 'test', $dbname = 'test', $hostorsock = '/tmp/bouncer', $port = 6432)
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
                            $dbh = new PDO($dsn);
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

    /**
     * Конструктор класса
     *
     * @param $dbh
     * @param $dbdriver
     */
    private function __construct (&$dbh, &$dbdriver)
    {
        $this->dbh = $dbh;
        $this->dbdriver = $dbdriver;
        $this->dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        if (!isset($_SESSION['tables']))
        {
            if ($this->dbdriver == 'pgsql')
            {
                if ($this->tables = $this->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'"))
                {
                    $this->tables[]['table_name'] = 'pg_type';
                }
                else
                {
                    throw new _PDOException('Список таблиц пуст');
                }
            }
            elseif ($this->dbdriver == 'mysql')
            {
                if ($tables = $this->query("SHOW TABLES"))
                {
                    foreach ($tables AS $table)
                    {
                        $this->tables[]['table_name'] = $table[0];
                    }
                }
            }
            $_SESSION['tables'] = json_encode($this->tables, JSON_UNESCAPED_UNICODE);
        }
        else
        {
            $this->tables = json_decode($_SESSION['tables'], true);
        }
    }

    /**
     * Возвратит используемый драйвер подключения
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
     * @param $query
     * @param array $params
     * @return int
     * @throws Exception
     * @throws PDOException
     */
    public function query($query, array $params = [])
    {
        $execQuery = function(&$params, &$query, &$db, &$row_count)
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
     * Стартовать транзакцию (исключение подавляется для предотвращения прекращения вополнения скрипта вследствие попытки открытия транзакции внутри другой транзакции)
     *
     * @return mixed
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
     * Закоммитить транзакцию
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
     * Откатить транзакцию
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
     * Установить атрибут на подключение
     *
     * @param $attribute
     * @param $value
     * @return mixed
     */
    public function setAttribute ($attribute, $value)
    {
        return $this->dbh->setAttribute($attribute, $value);
    }

    /**
     * Возвращаем имена таблиц использующихся в запросе
     *
     * @param bool $query
     * @return array|string
     * @throws _PDOException
     */
    public function getTables ($query)
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
     * Если запрос является запросом на изменение, то возвращает учавствующие в запросе таблицы, иначе возвратит FALSE
     *
     * @param $query
     * @return array|bool|string
     */
    function getEditTables ($query)
    {
        if (preg_match('/^(update|insert\sinto|delete)/i', $query))
        {
            return $this->getTables($query);
        }
        else
        {
            return false;
        }
    }

}

?>