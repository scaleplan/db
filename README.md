_PDO
====
###1.Описание методов
#####Класс class._pdo.php
`public static function create ($dbdriver = < pgsql| mysql >, $login = 'test', $password = 'test', $dbname = 'test', $hostorsock = '< /path/to/socket | db host >', $port = 6432)`   
**Singleton для объекта класса. Статический метод возвращающий объект класса _PDO.**
Параметры:
* *$dbdriver* - драйвер доступа к СУБД. На данный момент поддерживаются СУБД MySQL и PostgreSQL. 
Допустимые значения: pgsql, mysql
* *$login* - логин пользователя для доступа к базе данных.
* *$password* - пароль доступа к базу данных.
* *$dbname* - имя базы данных, к которой мы подключаемся.
* *$hostorsock* - имя, ip-адрес хоста или UNIX-сокет для подключения к базе данных.
* *$port* - порт, на котором БД слушает подключения
