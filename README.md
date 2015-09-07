_PDO
====
##Описание методов
###Класс class._pdo.php
#### create
`public static function create ($dbdriver = < pgsql| mysql >, $login = 'test', $password = 'test', $dbname = 'test', $hostorsock = '< /path/to/socket | db host >', $port = 6432)`   
######Singleton для объекта класса. Статический метод возвращающий объект класса _PDO  
**Параметры:**
* *$dbdriver* - драйвер доступа к СУБД. На данный момент поддерживаются СУБД MySQL и PostgreSQL, допустимые значения: pgsql, mysql;
* *$login* - логин пользователя для доступа к базе данных;
* *$password* - пароль доступа к базу данных;
* *$dbname* - имя базы данных, к которой мы подключаемся;
* *$hostorsock* - имя, ip-адрес хоста или UNIX-сокет для подключения к базе данных;
* *$port* - порт, на котором БД слушает подключения.    

**Пример использования:**    
`$dbconnect = _PDO::create($dbdriver);`

####getDBDriver
`public function getDBDriver()`   
######Возвращает текущий драйвер подключения к БД  
**Параметры:** нет.    

**Пример использования:**    
`$driver = $dbconnect->getDBDriver();`

####query
`public function query($query, array $params = [])`   
######Выполняет запрос к БД и возвращает результат    
**Параметры:**
* *$query* - текст запроса;
* *$params* - параметры запроса (для prepared-запросов).    

**Пример использования:**     
` $value = $this->dbconnect->query($query, $params);`

####beginTransaction
`public function beginTransaction ()`   
######Стартует транзакцию  

####commit
`public function commit ()`   
######Коммитит транзакцию  

####rollBack
`public function rollBack ()`   
######Откатывает транзакцию  

####getTables
`public function getTables ($query)`   
######Возвращает имена таблиц, использующихся в запросе  
**Параметры:**    
* *$query* - текст запроса.

####getEditTables
`public function getEditTables ($query)`   
######Если запрос является запросом на изменение, то возвращает учавствующие в запросе таблицы, иначе возвратит FALSE 
**Параметры:**    
* *$query* - текст запроса.

------------------------------------------------------------------------------------------------------------

##Как использовать
```
$dbconnect = _PDO::create($dbdriver);     
$params = [param1 => true, param2 = false];    
$query = "INSERT INTO    
            test    
            (param1, param2)      
         VALUES    
            (:param1, :param2)   
         RETURNING
            param1,   
            param2";  
$result = $dbconnect->query($query, $params);
```
где `$result` - результат выполнения запроса.
