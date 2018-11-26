<?php

namespace Scaleplan\Db;

use Scaleplan\Db\Exceptions\AsyncExecutionException;
use Scaleplan\Db\Exceptions\DbException;
use Scaleplan\Db\Exceptions\InvalidIsolationLevelException;
use Scaleplan\Db\Exceptions\ParallelExecutionException;

/**
 * Class pgDb
 *
 * @package Scaleplan\Db
 */
class pgDb extends Db
{
    public const SERIALIZABLE = 'SERIALIZABLE';
    public const REPEATABLE_READ = 'REPEATABLE READ';
    public const READ_COMMITTED = 'READ COMMITTED';

    public const ISOLATION_LEVELS = [
        self::READ_COMMITTED,
        self::REPEATABLE_READ,
        self::SERIALIZABLE,
    ];

    public const RETRY_TIMEOUT = 100000;
    public const LITE_RETRY_COUNT = 1;
    public const MAIN_RETRY_COUNT = 2;

    /**
     * @var string
     */
    protected $nextQueryIsolationLevel;

    /**
     * @var bool
     */
    protected $isRetry;

    /**
     * Выполнить параллельно пакет запросов. Актуально для PostgreSQL
     *
     * @param string[] $batch - массив транзакций
     *
     * @return array
     *
     * @throws DbException
     */
    public function parallelExecute(array $batch): array
    {
        if ($this->dbDriver !== 'pgsql') {
            throw new ParallelExecutionException('Поддерживается только PostgreSQL');
        }

        if (!\count($this->query("SELECT proname FROM pg_proc WHERE proname = 'execute_multiple'"))) {
            $sql = file_get_contents(static::EXECUTE_MULTIPLE_PATH);
            $this->dbh->exec($sql);
        }

        $query = 'SELECT execute_multiple(ARRAY[';
        foreach ($batch as & $t) {
            $query .= "'" . implode(';', $t) . "',";
        }

        unset($t);
        $query = trim($query, ',');
        $count = \count($batch);

        $count = static::DB_MAX_PARALLEL_CONNECTS <= $count ? static::DB_MAX_PARALLEL_CONNECTS : $count;
        $query .= "], $count, '" . $this->dns . "') AS failed";

        $result = $this->query($query);

        $failed = explode(',', $result[0]['failed']);
        foreach ($batch as $key => &$value) {
            if (!\in_array($key + 1, $failed, true)) {
                unset($batch[$key]);
            }
        }

        unset($value);

        return $batch;
    }

    /**
     * Отправить асинхронно пакет транзакций на сервер (актуально для PostgreSQL)
     *
     * @param string[]|string $query - запрос или массив запросов
     * @param array $data - параметры подготовленного запроса
     *
     * @return bool
     *
     * @throws DbException
     */
    public function async($query, array $data = null): bool
    {
        if ($this->dbDriver !== 'pgsql') {
            throw new AsyncExecutionException('Поддерживается только PostgreSQL');
        }

        if (!\extension_loaded('pgsql')) {
            throw new AsyncExecutionException(
                'Для асинхронного выполнения запросов требуется расширение pgsql'
            );
        }

        if (!$db = pg_connect($this->dns)) {
            throw new AsyncExecutionException('Не удалось подключиться к БД через нативный драйвер');
        }

        if (!\is_array($query) && !\is_string($query)) {
            throw new AsyncExecutionException(
                'Первый параметр должен быть строкой запроса или массивом запросов'
            );
        }

        if (!$data) {
            $queryStr = $query;
            if (\is_array($query)) {
                $queryStr = $this->createQStrFromBatch($query);
            }
            $result = pg_send_query($db, $queryStr);
            if (!$result) {
                if (\is_array($query)) {
                    throw new AsyncExecutionException('Не удалось выполнить пакет запросов');
                }

                throw new AsyncExecutionException('Не удалось выполнить запрос');
            }

            pg_close($db);

            return true;
        }

        if (\is_array($query)) {
            throw new AsyncExecutionException(
                'Для асинхронного выполнения подготовленных запросов недоступно использование пакета запросов'
            );
        }

        if (!pg_send_query_params($db, $query, $data)) {
            throw new DbException(pg_last_error($db));
        }

        return true;
    }

    /**
     * @param string $level
     *
     * @throws InvalidIsolationLevelException
     */
    public function setNextQueryIsolationLevel(string $level) : void
    {
        if (!\in_array($level, static::ISOLATION_LEVELS)) {
            throw new InvalidIsolationLevelException();
        }

        $this->nextQueryIsolationLevel = $level;
    }

    /**
     * @param $query
     * @param array $params
     *
     * @return array|int
     *
     * @throws Exceptions\QueryCountNotMatchParamsException
     */
    protected function retryQuery($query, array $params = [])
    {
        $liteAttempts = $mainAttempts = 0;
        do {
            try {
                return parent::query($query, $params);
            } catch (\PDOException $e) {
                switch (substr($e->getCode(), 0, 2)) {
                    case '0Z':
                    case '2D':
                    case '3B':
                        $liteAttempts++;
                        if ($liteAttempts <= static::LITE_RETRY_COUNT) {
                            usleep(static::RETRY_TIMEOUT);
                            continue;
                        }
                        break;
                    case '40':
                    case '55':
                        $mainAttempts++;
                        if ($mainAttempts <= static::MAIN_RETRY_COUNT) {
                            usleep(static::RETRY_TIMEOUT);
                            continue;
                        }
                }
            }

            throw $e;
        } while(true);
    }

    /**
     * @param string|string[] $query
     * @param array $params
     *
     * @return array|int
     *
     * @throws Exceptions\QueryCountNotMatchParamsException
     * @throws InvalidIsolationLevelException
     */
    public function query($query, array $params = [])
    {
        if (!$this->nextQueryIsolationLevel) {
            return $this->retryQuery($query, $params);
        }

        [$oldLevel] = $this->setIsolationLevel($this->nextQueryIsolationLevel);
        $result = $this->retryQuery($query, $params);
        $this->setIsolationLevel($oldLevel);

        return $result;
    }

    /**
     * @param string $level
     *
     * @return array
     *
     * @throws Exceptions\QueryCountNotMatchParamsException
     * @throws InvalidIsolationLevelException
     */
    public function setIsolationLevel(string $level) : array
    {
        if (!\in_array($level, static::ISOLATION_LEVELS)) {
            throw new InvalidIsolationLevelException();
        }

        static $oldIsolationLevel;
        if (!$oldIsolationLevel) {
            $oldIsolationLevel = $this->query('SHOW transaction_isolation')[0]['transaction_isolation'];
        }

        $this->query("SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL $level");
        $oldIsolationLevel = $level;

        return [$oldIsolationLevel, $level];
    }
}