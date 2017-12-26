<?php

namespace avtomon;

class _PDOException extends \Exception
{
}

class _PDO
{
    const EXECUTE_MULTIPLE_PATH = '';

    private $dns = ''; // Строка подключения к БД
    private $dbh = null; // Хэндлер подключения к БД
    private $dbdriver = ''; // Имя драйвера СУБД
    private $tables = []; // Список таблиц БД

    /**
     * _PDO constructor.
     *
     * @param string $dns - строка подключения
     * @param string $login - пользователь БД
     * @param string $password - пароль
     * @param array $schemas - какие схемы будут использоваться
     * @param array $options - дополнительные опции
     *
     * @throws _PDOException
     */
    public function __construct(
            string $dns,
            string $login,
            string $password,
            array $schemas = [],
            array $options = []
    ) {
        if (!preg_match('/^(.+?):/i', $dns, $matches)) {
            throw new _PDOException('Неверная строка подключения: Не задан драйвер');
        }

        $this->dbdriver = $matches[1];

        if (empty($this->dbh = new \PDO($dns, $login, $password, $options))) {
            throw new _PDOException('Не удалось создать объект PDO');
        }

        $this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->dbh->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_TO_STRING);
        $this->dbh->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        $this->dns = $dns;

        if ($this->dbdriver === 'pgsql') {
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
                                                table_schema IN ('" . implode("', '", $schemas) . "')");
            $this->tables[]['table_name'] = 'pg_type';
            $this->tables[]['table_name'] = 'pg_enum';
        } elseif ($this->dbdriver === 'mysql') {
            if (!preg_match('/dbname=(.+?)/i', $dns, $matches)) {
                throw new _PDOException('Не удалось выделить имя базы данных из строки подключения');
            }

            $this->tables = $this->query("SHOW TABLES FROM {$matches[1]}");
        }

        if (!$this->tables) {
            throw new _PDOException('Не удалось получить список таблиц');
        }
    }

    /**
     * Сделать запрос к БД, поддерживает подготовленные выражения
     *
     * @param $query - запрос
     * @param array $params - параметры запроса
     *
     * @return int|array
     *
     * @throws _PDOException
     */
    public function query($query, array $params = [])
    {
        $execQuery = function (array &$params, string &$query, int &$rowСount) {
            if (empty($params)) {
                $sth = $this->dbh->query($query);
            }

            $sth = $this->dbh->prepare($query);
            $sth->execute($params);

            if (!$sth) {
                throw new _PDOException('Не удалось выполнить запрос');
            }

            $rowСount += $sth->rowCount();
            return $sth;
        };

        $rowСount = 0;
        if (is_array($query) && isset($params[0]) && count($query) == count($params)) {
            $this->dbh->beginTransaction();
            foreach ($query as $key => & $value) {
                $execQuery($params[$key], $value, $rowСount);
            }

            unset($value);
            return $rowСount;
        }

        $sth = $execQuery($params, $query, $rowСount);
        $result = $sth->fetchAll();
        if (count($result) == 0) {
            return $rowСount;
        }

        return $result;
    }

    /**
     * Получить имя драйвера СУБД
     *
     * @return string
     */
    public function getDBDriver(): string
    {
        return $this->dbdriver;
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
     * @param $query - запрос
     *
     * @return array
     */
    public function getEditTables(string &$query): array
    {
        if (preg_match('/(UPDATE|INSERT\sINTO|DELETE|CREATE\sTYPE)/', $query)) {
            return $this->getTables($query);
        }

        return [];
    }

    /**
     * Возвращаем имена таблиц использующихся в запросе
     *
     * @param bool $query - запрос
     *
     * @return array
     *
     * @throws _PDOException
     */
    public function getTables(string & $query)
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
     * Выполнить параллельно пакет транзикций. Актуально для PostgreSQL
     *
     * @param array $batch - массив транзакций
     *
     * @return array
     *
     * @throws _PDOException
     */
    public function parallelExecute(array $batch): array
    {
        if ($this->dbdriver != 'pgsql') {
            throw new _PDOException('Поддерживается только PostgreSQL');
        }

        if (!count($this->query("SELECT proname FROM pg_proc WHERE proname = 'execute_multiple'"))) {
            $sql = file_get_contents(self::EXECUTE_MULTIPLE_PATH);
            $this->dbh->exec($sql);
        }

        $query = 'SELECT execute_multiple(ARRAY[';
        foreach ($batch as & $t) {
            $query .= "'" . implode(';', $t) . "',";
        }

        unset($t);
        $query = trim($query, ',');
        $count = count($batch);

        $count = DB_MAX_PARALLEL_CONNECTS <= $count ? DB_MAX_PARALLEL_CONNECTS : $count;
        $query .= "], $count, '" . self::$dsn . "') AS failed";

        $result = $this->query($query);

        $failed = explode(',', $result[0]['failed']);
        foreach ($batch as $key => & $value) {
            if (!in_array($key + 1, $failed)) {
                unset($batch[$key]);
            }
        }

        unset($value);

        return $batch;
    }

    /**
     * Отправить асинхронно пакет транзакций на сервер
     *
     * @param array $batch - массив транзакций. Актуально для PostgreSQL
     *
     * @return bool
     *
     * @throws _PDOException
     */
    public function asyncBatch(array $batch): bool
    {
        if ($this->dbdriver != 'pgsql') {
            throw new _PDOException('Поддерживается только PostgreSQL');
        }

        if (!$db = pg_connect($this->dns)) {
            throw new _PDOException('Не удалось подключиться к БД через нативный драйвер');
        }

        $result = pg_send_query($db, $this->createQStrFromBatch($batch));
        if (!$result) {
            throw new _PDOException('Не удалось выполнить пакет транзакций');
        }

        pg_close($db);

        return true;
    }

    /**
     * Формирует строку для асинхронного выполнения методами asyncBatch и execBatch
     *
     * @param array $batch - массив транзакций
     *
     * @return string
     */
    private function createQStrFromBatch(array & $batch): string
    {
        $queryStr = '';
        foreach ($batch as & $t) {
            if (count($t) > 1) {
                $queryStr .= 'BEGIN;' . implode(';', $t) . ';' . 'COMMIT;';
            }
        }

        unset($t);
        return $queryStr;
    }

    /**
     * Выполнить пакет транзакций с проверкой результата выполнения. Актуально для PostgreSQL
     *
     * @param array $batch - массив транзакций
     *
     * @return bool
     *
     * @throws _PDOException
     */
    public function execBatch(array $batch): bool
    {
        $oldAttrEmulatePrepares = $this->dbh->getAttribute(PDO::ATTR_EMULATE_PREPARES);
        $this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        try {
            $this->dbh->exec($this->createQStrFromBatch($batch));
        } catch (PDOException $e) {
            $this->dbh->exec('ROLLBACK');
            throw new _PDOException('Ошибка выполнения пакета транзакций: ' . $e->getMessage());
        } finally {
            $this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, $oldAttrEmulatePrepares);
        }

        return true;
    }

}
