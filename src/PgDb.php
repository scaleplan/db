<?php
declare(strict_types=1);

namespace Scaleplan\Db;

use Scaleplan\Db\Exceptions\AsyncExecutionException;
use Scaleplan\Db\Exceptions\DbException;
use Scaleplan\Db\Exceptions\InvalidIsolationLevelException;
use Scaleplan\Db\Exceptions\ParallelExecutionException;
use Scaleplan\Db\Interfaces\PgDbInterface;

/**
 * Class pgDb
 *
 * @package Scaleplan\Db
 */
class PgDb extends Db implements PgDbInterface
{
    public const SERIALIZABLE    = 'SERIALIZABLE';
    public const REPEATABLE_READ = 'REPEATABLE READ';
    public const READ_COMMITTED  = 'READ COMMITTED';

    public const ISOLATION_LEVELS = [
        self::READ_COMMITTED,
        self::REPEATABLE_READ,
        self::SERIALIZABLE,
    ];

    public const RETRY_TIMEOUT    = 100000;
    public const LITE_RETRY_COUNT = 1;
    public const MAIN_RETRY_COUNT = 2;

    /**
     * Код указывающий на ошибку произошедшую при попытке добавить дубликат данных
     */
    public const DUPLICATE_ERROR_CODES = ['42P04', 23505];

    /**
     * @var string
     */
    protected $nextQueryIsolationLevel;

    /**
     * @var bool
     */
    protected $isRetry;

    /**
     * @var bool
     */
    protected $isDeferred;

    /**
     * Выполнить параллельно пакет запросов. Актуально для PostgreSQL
     *
     * @param string[] $batch - массив транзакций
     *
     * @return array
     *
     * @throws DbException
     */
    public function parallelExecute(array $batch) : array
    {
        if ($this->dbDriver !== 'pgsql') {
            throw new ParallelExecutionException('Поддерживается только PostgreSQL');
        }

        if (!\count($this->query("SELECT proname FROM pg_proc WHERE proname = 'execute_multiple'"))) {
            $sql = file_get_contents(static::EXECUTE_MULTIPLE_PATH);
            $this->getConnection()->exec($sql);
        }

        $query = 'SELECT execute_multiple(ARRAY[';
        foreach ($batch as & $t) {
            $query .= "'" . implode(';', $t) . "',";
        }

        unset($t);
        $query = trim($query, ',');
        $count = \count($batch);

        $count = static::DB_MAX_PARALLEL_CONNECTS <= $count ? static::DB_MAX_PARALLEL_CONNECTS : $count;
        $query .= "], $count, '" . $this->getDsn() . "') AS failed";

        $result = $this->query($query);

        $failed = array_map('trim', explode(',', $result[0]['failed']));
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
     * @throws DbException
     */
    public function async($query, array $data = null) : void
    {
        if ($this->dbDriver !== 'pgsql') {
            throw new AsyncExecutionException('Поддерживается только PostgreSQL');
        }

        if (!\extension_loaded('pgsql')) {
            throw new AsyncExecutionException(
                'Для асинхронного выполнения запросов требуется расширение pgsql'
            );
        }

        if (!$db = pg_connect($this->getDsn())) {
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

            return;
        }

        if (\is_array($query)) {
            throw new AsyncExecutionException(
                'Для асинхронного выполнения недоступно использование пакета подготовленных запросов'
            );
        }

        if (!pg_send_query_params($db, $query, $data)) {
            throw new DbException(pg_last_error($db));
        }
    }

    /**
     * @param string $level
     *
     * @throws InvalidIsolationLevelException
     */
    public function setNextQueryIsolationLevel(string $level) : void
    {
        if (!\in_array($level, static::ISOLATION_LEVELS, true)) {
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
     * @throws Exceptions\PDOConnectionException
     * @throws Exceptions\QueryCountNotMatchParamsException
     * @throws Exceptions\QueryExecutionException
     */
    protected function retryQuery($query, array $params = [])
    {
        $liteAttempts = $mainAttempts = 0;
        $liteRetryCount = (int)(getenv('DB_LITE_RETRY_COUNT') ?: static::LITE_RETRY_COUNT);
        $retryTimeout = (int)(getenv('DB_RETRY_TIMEOUT') ?: static::RETRY_TIMEOUT);
        $mainRetryCount = (int)(getenv('DB_MAIN_RETRY_COUNT') ?: static::MAIN_RETRY_COUNT);
        do {
            try {
                return parent::query($query, $params);
            } catch (\PDOException $e) {
                switch (substr($e->getCode(), 0, 2)) {
                    case '0Z':
                    case '2D':
                    case '3B':
                        $liteAttempts++;
                        if ($liteAttempts <= $liteRetryCount) {
                            usleep($retryTimeout);
                            continue 2;
                        }
                        break;
                    case '40':
                    case '55':
                        $mainAttempts++;
                        if ($mainAttempts <= $mainRetryCount) {
                            usleep($retryTimeout);
                            continue 2;
                        }
                }
            }

            throw $e;
        } while (true);
    }

    /**
     * @param string|string[] $query
     * @param array $params
     *
     * @return array|int
     *
     * @throws Exceptions\PDOConnectionException
     * @throws Exceptions\QueryCountNotMatchParamsException
     * @throws Exceptions\QueryExecutionException
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
     * @throws Exceptions\PDOConnectionException
     * @throws Exceptions\QueryCountNotMatchParamsException
     * @throws Exceptions\QueryExecutionException
     * @throws InvalidIsolationLevelException
     */
    public function setIsolationLevel(string $level) : array
    {
        if (!\in_array($level, static::ISOLATION_LEVELS, true)) {
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

    /**
     * @param bool $isDeferred
     */
    public function setTransactionDeferred(bool $isDeferred) : void
    {
        $this->isDeferred = $isDeferred;
    }

    /**
     * @return \PDO
     *
     * @throws Exceptions\PDOConnectionException
     */
    public function getConnection() : \PDO
    {
        parent::getConnection();
        $this->isDeferred && $this->connection->query('SET CONSTRAINTS ALL DEFERRED');

        return $this->connection;
    }
}
