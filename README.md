_PDO
====
Описание методов
#####Класс class._pdo.php
`public static function create ($dbdriver = < pgsql| mysql >, $login = 'test', $password = 'test', $dbname = 'test', $hostorsock = '< /path/to/socket | db host >', $port = 6432)`   
######Singleton для объекта класса. Статический метод возвращающий объект класса _PDO  
Параметры:
* *$dbdriver* - драйвер доступа к СУБД. На данный момент поддерживаются СУБД MySQL и PostgreSQL, допустимые значения: pgsql, mysql;
* *$login* - логин пользователя для доступа к базе данных;
* *$password* - пароль доступа к базу данных;
* *$dbname* - имя базы данных, к которой мы подключаемся;
* *$hostorsock* - имя, ip-адрес хоста или UNIX-сокет для подключения к базе данных;
* *$port* - порт, на котором БД слушает подключения.

`public function getDBDriver()`   
######Возвращает текущий драйвер подключения к БД  
Параметры: нет

`public function query($query, array $params = [])`   
######Выполняет запрос к БД и возвращает результат    
Параметры:
* *$query* - текст запроса;
* *$params* - параметры запроса (для prepared-запросов).

`public function beginTransaction ()`   
######Стартует транзакцию  

`public function commit ()`   
######Коммитит транзакцию  

`public function rollBack ()`   
######Откатывает транзакцию  

`public function setAttribute ($attribute, $value)`   
######Установить атрибут на подключение  
Параметры:
* *$attribute* - имя атрибута;
* *$value* - устанавливаемое значение.

`public function getTables ($query)`   
######Возвращает имена таблиц, использующихся в запросе  
Параметры:
* *$query* - текст запроса.

`public function getEditTables ($query)`   
######Если запрос является запросом на изменение, то возвращает учавствующие в запросе таблицы, иначе возвратит FALSE 
Параметры:
* *$query* - текст запроса.
