##Описание методов
<<<<<<< .mine
###Класс class._pdo.php
#### create
`public static function create ($dbdriver = < pgsql| mysql >, $login = 'test', $password = 'test', $dbname = 'test', $hostorsock = '< /path/to/socket | db host >', $port = 6432)`   
#####Singleton для объекта класса. Статический метод возвращающий объект класса _PDO  
=======

####Класс class.\_pdo.php

#####create

    public static function create ($dbdriver = *DB\_DRIVER*, $login = *DB\_LOGIN*, $password = *DB\_PASSWORD*, $dbname = *DB\_NAME*, $hostorsock = *DB\_SOCKET*, $port = *DB\_PORT*)

**Описание:**

Singleton для объекта класса - статический метод, возвращающий объект класса \_PDO. По умолчанию параметры берут из соответствующих констант. Объявление которых может содержаться, например, в конфигурационном файле.

>>>>>>> .r37
**Параметры:**

-   *$dbdriver* - драйвер доступа к СУБД. На данный момент поддерживаются СУБД MySQL и PostgreSQL, допустимые значения: pgsql, mysql;

<<<<<<< .mine
####getDBDriver
`public function getDBDriver()`   
#####Возвращает текущий драйвер подключения к БД  
**Параметры:** нет.    
=======
-   *$login* - логин пользователя для доступа к базе данных;
>>>>>>> .r37

-   *$password* - пароль доступа к базу данных;

<<<<<<< .mine
####query
`public function query($query, array $params = [])`   
#####Выполняет запрос к БД и возвращает результат    
=======
-   *$dbname* - имя базы данных, к которой мы подключаемся;

-   *$hostorsock* - имя, ip-адрес хоста или UNIX-сокет для подключения к базе данных;

-   *$port* - порт, на котором БД слушает подключения.

**Пример использования:**

    $dbconnect = \_PDO::create($dbdriver);
    
<br>
#####getDBDriver

    public function getDBDriver ()

**Описание:**

Возвращает имя текущего драйвер подключения к БД.

**Параметры:** нет.

**Пример использования:**

    $driver = $dbconnect->getDBDriver();

<br>
#####getDBH

    public function getDBDriver ()

**Описание:**

Вернет объект подключения к базе данных.

**Параметры:** нет.

**Пример использования:**

    $dbh = $dbconnect->getDBH();

<br>
#####query

    public function query($query, array $params = \[\])

**Описание:**

Выполняет запрос к БД и возвращает результат. Поддерживает регулярные выражения.

>>>>>>> .r37
**Параметры:**

-   *$query* - текст запроса;

<<<<<<< .mine
####beginTransaction
`public function beginTransaction ()`   
#####Стартует транзакцию  
=======
-   *$params* - параметры запроса (для prepared-запросов).
>>>>>>> .r37

<<<<<<< .mine
####commit
`public function commit ()`   
#####Коммитит транзакцию  
=======
**Пример использования:**
>>>>>>> .r37

<<<<<<< .mine
####rollBack
`public function rollBack ()`   
#####Откатывает транзакцию  
=======
    $dbconnect->query("SELECT table\_name FROM information\_schema.tables WHERE table\_schema = 'public'");
>>>>>>> .r37

<<<<<<< .mine
####getTables
`public function getTables ($query)`   
#####Возвращает имена таблиц, использующихся в запросе  
**Параметры:**    
* *$query* - текст запроса.
=======
<br>
#####beginTransaction
>>>>>>> .r37

<<<<<<< .mine
####getEditTables
`public function getEditTables ($query)`   
#####Если запрос является запросом на изменение, то возвращает учавствующие в запросе таблицы, иначе возвратит FALSE 
**Параметры:**    
* *$query* - текст запроса.
=======
    public function beginTransaction ()
>>>>>>> .r37

**Описание:**

Стартует транзакцию. Не выбрасывает исключение если транзакция стартуется повторно.

**Параметры:** нет.

**Пример использования:**

    $dbconnect->beginTransaction();

<br>
#####commit

    public function commit ()

**Описание:**

Коммитит транзакцию. Не выбрасывает исключение если открытой транзакции нет.

**Параметры:** нет.

**Пример использования:**

    $dbconnect->commit();

<br>
#####rollBack

    public function rollBack ()

**Описание:**

Откатывает транзакцию. Не выбрасывает исключение если нет открытой транзакции.

**Параметры:** нет.

**Пример использования:**

    $dbconnect->rollBack();

<br>
#####getTables

    private function getTables ($query)

**Описание:**

Возвращает имена таблиц, использующихся в запросе в виде массива.

**Параметры:**

-   *$query* - текст запроса.

**Пример использования:**

    $tables = $dbconnect->getTables(“SELECT \* FROM table”);

<br>
#####getEditTables

    public function getEditTables ($query)

**Описание:**

Если запрос является запросом на изменение, то возвращает участвующие в запросе таблицы, иначе возвратит FALSE.

**Параметры:**

-   *$query* - текст запроса.

**Пример использования:**

    $tables = $dbconnect->getEditTables(“INSERT INTO table VALUES (1, 2, 3)”);

    // table

<br>
#####parallelExecute

    public function parallelExecute(array $batch)

**Описание:**

Параллельно выполняет запросы из массива запросов, переданного единственным параметром. Возвращает Массив некорректно отработавших запросов.

**Параметры:**

-   *$batch* - массив запросов.

**Пример использования:**

    $failed = $dbconnect->parallelExecute(\[“INSERT INTO table VALUES (1, 2, 3)”, “UPDATE table2 SET field1 = ‘value1’, field2 = ‘value2’\]);

<br>
#####createQStrFromBatch

    private function createQStrFromBatch (array $batch)

**Описание:**

Формирует строку для асинхронного выполнения методами asyncBatch и execBatch.

**Параметры:**

-   *$batch* - массив запросов.

**Пример использования:**

    $str = $dbconnect->dbh->exec($this->createQStrFromBatch($batch));

<br>
#####asyncBatch

    public function asyncBatch(array $batch)

**Описание:**

Отправляет асинхронно пакет запросов на сервер. Использует php-расширение PGSQL.

**Параметры:**

-   *$batch* - массив запросов.

**Пример использования:**

    $dbconnect->asyncBatch(\[‘INSERT INTO test VALUES (1, 21)’,

    > ‘INSERT INTO test VALUES (1, 22)’,
    >
    > ‘INSERT INTO test VALUES (1, 23)’,
    >
    > ‘INSERT INTO test VALUES (3, 71)’\]);

<br>
#####execBatch

    public function execBatch (array $batch)

**Описание:**

Выполнить пакет транзакций с проверкой результата выполнения. Если во время выполнения пакета запросов произошла ошибка метод выкинет исключение.

**Параметры:**

-   *$batch* - массив запросов.

**Пример использования:**

    $dbconnect->execBatch(\[‘INSERT INTO test VALUES (1, 21)’,

    > ‘INSERT INTO test VALUES (1, 22)’,
    >
    > ‘INSERT INTO test VALUES (1, 23)’,
    >
    > ‘INSERT INTO test VALUES (3, 71)’\]);

<br>
***
####Как использовать

    $dbconnect = \_PDO::create();
    
    $params = \[param1 => true, param2 = false\];
    
    $query = "INSERT INTO
    
    test
    
    (param1, param2)
    
    VALUES
    
    (:param1, :param2)
    
    RETURNING
    
    param1,
    
    param2";
    
    $result = $dbconnect->query($query, $params);

где $result - результат выполнения запроса.
