_PDO
====
#Описание методов
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

####setAttribute
`public function setAttribute ($attribute, $value)`   
######Установить атрибут на подключение  
**Параметры:**   
* *$attribute* - имя атрибута;
* *$value* - устанавливаемое значение.

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


##Класс class.service.php
####createPrepareFields
`public static function createPrepareFields (array &$data, $type = 'insert')`   
######Формирование строки плейсхолдеров для SQL-запросов
**Параметры:**    
* *$data* - массив данных запроса;
* *$type - тип запроса, поддерживаются следующие варианты: insert, inline, либо пустое значение.

####createSelectString
`public static function createSelectString (array $data)`   
######Формирование списка полей для SQL-запросов
**Параметры:**    
* *$data* - массив данных запроса;

####sql
`public static function sql ($request, &$data)`   
######Разбор SQL-шаблона
**Параметры:**    
* *$request* - текст шаблона запроса;
* *$data* - массив данных запроса;

------------------------------------------------------------------------------------------------------------

#Как использовать
На вход модуля приходит набор данных для формирования запроса SQL-запрос, который может содержать шаблонные элементы, которые затем будут обрабатываться SQL-шаблонизатором посредством методов класса Service.
Например шаблон вида     
`INSERT INTO    
   controls     
   ([fields])    
 VALUES    
   [expression]    
 RETURNING    
   id AS control_id,    
   [fields]`   

если на входе у нас данные [param1 => true, param2 = false]
превратиться в запрос
`INSERT INTO    
   controls     
   (param1, param2)    
 VALUES    
   (:param1, :param2)    
 RETURNING    
   id AS control_id,    
   param1,    
   param2`

что является prepared-запросом и может выполнится средствами метода _PDO::query (<текст запроса>, <массив параметров>).

Таким образом полный код исполнения выше приведенного запроса будет следующим:
`$dbconnect = _PDO::create($dbdriver);  
$params = [param1 => true, param2 = false];  
$query = "INSERT INTO    
           controls     
           ([fields])    
         VALUES    
           [expression]    
         RETURNING    
           id AS control_id,    
           [fields]";  
$result = $dbconnect->query(Service::sql($query, $params), $params);`
где `$result` - результат выполнения запроса.
Разумеется возможны и более простые варианты без параметров с создания подготовленных выражений, а так без подстановки значений шаблон.
