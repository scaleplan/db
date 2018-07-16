<small>avtomon</small>

CachePDO
========

CachePDO представляет собой класс-обертку для взаимодествия PHP-приложения с СУБД PostgreSQL и MySQL.

Описание
-----------

Позволяет прозрачно взаимодействовать с любой из этих СУБД не вникая в различия взаимодейтвия PHP с этими системами –
для разработчика работа с обоими СУБД будет одинакова с точки зрения программирования.
Класс поддерживает подготовленные выражения. Кроме того есть дополнительная функциональность для реализации концепции
параллельного выполнения запросов внутри одного подключени к базе данных. А так же есть методы для реализации
асинхронного выполнения пакетов запросов.

Class CachePDO

Сигнатура
---------

- **class**.

Константы
---------

class устанавливает следующие константы:

- [`ALLOW_DRIVERS`](#ALLOW_DRIVERS) &mdash; Доступные драйвера СУБД
- [`PGSQL_ADDITIONAL_TABLES`](#PGSQL_ADDITIONAL_TABLES) &mdash; С какими схемами дополнительно будет рабоать объект при подключении к PosqlgreSQL
- [`MYSQL_ADDITIONAL_TABLES`](#MYSQL_ADDITIONAL_TABLES) &mdash; С какими схемами дополнительно будет рабоать объект при подключении к MySQL
- [`DUPLICATE_ERROR_CODE`](#DUPLICATE_ERROR_CODE) &mdash; Код указывающий на ошибку произошедшую при попытке добавить дубликат данных
- [`DB_MAX_PARALLEL_CONNECTS`](#DB_MAX_PARALLEL_CONNECTS) &mdash; Максимальное число параллельных транзакций
- [`EXECUTE_MULTIPLE_PATH`](#EXECUTE_MULTIPLE_PATH) &mdash; Путь к файлу с хранимой процедурой, обеспечивающей параллельное выполнение запросов

Свойства
----------

class устанавливает следующие свойства:

- [`$dns`](#$dns) &mdash; Строка подключения к БД
- [`$dbh`](#$dbh) &mdash; Хэндлер подключения к БД
- [`$dbdriver`](#$dbdriver) &mdash; Имя драйвера СУБД
- [`$tables`](#$tables) &mdash; Список таблиц БД
- [`$isArrayResults`](#$isArrayResults) &mdash; Возвращать ли пустой массив при отсутствии результата запроса
- [`$instances`](#$instances) &mdash; Сохраненные объекты CachePDO

### `$dns` <a name="dns"></a>

Строка подключения к БД

#### Сигнатура

- **protected** property.
- Значение `string`.

### `$dbh` <a name="dbh"></a>

Хэндлер подключения к БД

#### Сигнатура

- **protected** property.
- Может быть одного из следующих типов:
    - `null`
    - [`PDO`](http://php.net/class.PDO)

### `$dbdriver` <a name="dbdriver"></a>

Имя драйвера СУБД

#### Сигнатура

- **protected** property.
- Значение `string`.

### `$tables` <a name="tables"></a>

Список таблиц БД

#### Сигнатура

- **protected** property.
- Может быть одного из следующих типов:
    - `array`
    - `int`

### `$isArrayResults` <a name="isArrayResults"></a>

Возвращать ли пустой массив при отсутствии результата запроса

#### Сигнатура

- **protected** property.
- Значение `bool`.

### `$instances` <a name="instances"></a>

Сохраненные объекты CachePDO

#### Сигнатура

- **public static** property.
- Значение `array`.

Методы
-------

Методы класса class:

- [`getInstance()`](#getInstance) &mdash; Фабрика CachePDO
- [`__construct()`](#__construct) &mdash; Конструктор. Намеренно сделан открытым чтобы дать большую гибкость
- [`addAdditionTables()`](#addAdditionTables) &mdash; Добавить дополнительные таблицы используемым
- [`initSessionStorage()`](#initSessionStorage) &mdash; Инициализировать хранение имен таблиц в сессии
- [`query()`](#query) &mdash; Сделать запрос к БД, поддерживает подготовленные выражения
- [`getDBDriver()`](#getDBDriver) &mdash; Получить имя драйвера СУБД
- [`getDBH()`](#getDBH) &mdash; Вернет подключение к базе данных
- [`beginTransaction()`](#beginTransaction) &mdash; Начать транзакцию
- [`commit()`](#commit) &mdash; Фиксировать транзакцию
- [`rollBack()`](#rollBack) &mdash; Откатить транцакцию
- [`getEditTables()`](#getEditTables) &mdash; Возвращаем имена таблиц использующихся в запросе только для запросов на изменение
- [`getTables()`](#getTables) &mdash; Возвращаем имена таблиц использующихся в запросе
- [`parallelExecute()`](#parallelExecute) &mdash; Выполнить параллельно пакет запросов. Актуально для PostgreSQL
- [`async()`](#async) &mdash; Отправить асинхронно пакет транзакций на сервер (актуально для PostgreSQL)
- [`createQStrFromBatch()`](#createQStrFromBatch) &mdash; Формирует строку для асинхронного выполнения методами asyncBatch и execBatch
- [`execBatch()`](#execBatch) &mdash; Выполнить пакет транзакций с проверкой результата выполнения. Актуально для PostgreSQL

### `getInstance()` <a name="getInstance"></a>

Фабрика CachePDO

#### Сигнатура

- **public static** method.
- Может принимать следующий параметр(ы):
    - `$dns` (`string`) - строка подключения
    - `$login` (`string`) - пользователь БД
    - `$password` (`string`) - пароль
    - `$schemas` (`array`) - какие схемы будут использоваться
    - `$options` (`array`) - дополнительные опции
    - `$isArrayResults` (`bool`) - возвращать результат только в виде массива
- Возвращает [`CachePDO`](../avtomon/CachePDO.md) value.
- Выбрасывает одно из следующих исключений:
    - [`avtomon\CachePDOException`](../avtomon/CachePDOException.md)

### `__construct()` <a name="__construct"></a>

Конструктор. Намеренно сделан открытым чтобы дать большую гибкость

#### Сигнатура

- **public** method.
- Может принимать следующий параметр(ы):
    - `$dns` (`string`) - строка подключения
    - `$login` (`string`) - пользователь БД
    - `$password` (`string`) - пароль
    - `$schemas` (`array`) - какие схемы будут использоваться
    - `$options` (`array`) - дополнительные опции
    - `$isArrayResults` (`bool`) - возвращать результат только в виде массива
- Ничего не возвращает.
- Выбрасывает одно из следующих исключений:
    - [`avtomon\CachePDOException`](../avtomon/CachePDOException.md)

### `addAdditionTables()` <a name="addAdditionTables"></a>

Добавить дополнительные таблицы используемым

#### Сигнатура

- **protected** method.
- Ничего не возвращает.

### `initSessionStorage()` <a name="initSessionStorage"></a>

Инициализировать хранение имен таблиц в сессии

#### Сигнатура

- **protected static** method.
- Может принимать следующий параметр(ы):
    - `$dbName` (`string`) - имя базы данных
- Ничего не возвращает.

### `query()` <a name="query"></a>

Сделать запрос к БД, поддерживает подготовленные выражения

#### Сигнатура

- **public** method.
- Может принимать следующий параметр(ы):
    - `$query` (`string[]`|`string`) - запрос
    - `$params` (`array`) - параметры запроса
- Может возвращать одно из следующих значений:
    - `array`
    - `int`

### `getDBDriver()` <a name="getDBDriver"></a>

Получить имя драйвера СУБД

#### Сигнатура

- **public** method.
- Возвращает `string` value.

### `getDBH()` <a name="getDBH"></a>

Вернет подключение к базе данных

#### Сигнатура

- **public** method.
- Возвращает [`PDO`](http://php.net/class.PDO) value.

### `beginTransaction()` <a name="beginTransaction"></a>

Начать транзакцию

#### Сигнатура

- **public** method.
- Возвращает `bool` value.

### `commit()` <a name="commit"></a>

Фиксировать транзакцию

#### Сигнатура

- **public** method.
- Возвращает `bool` value.

### `rollBack()` <a name="rollBack"></a>

Откатить транцакцию

#### Сигнатура

- **public** method.
- Возвращает `bool` value.

### `getEditTables()` <a name="getEditTables"></a>

Возвращаем имена таблиц использующихся в запросе только для запросов на изменение

#### Сигнатура

- **public** method.
- Может принимать следующий параметр(ы):
    - `$query` (`string`) - запрос
- Возвращает `array` value.
- Выбрасывает одно из следующих исключений:
    - [`avtomon\CachePDOException`](../avtomon/CachePDOException.md)

### `getTables()` <a name="getTables"></a>

Возвращаем имена таблиц использующихся в запросе

#### Сигнатура

- **public** method.
- Может принимать следующий параметр(ы):
    - `$query` (`string`) - запрос
- Возвращает `array` value.
- Выбрасывает одно из следующих исключений:
    - [`avtomon\CachePDOException`](../avtomon/CachePDOException.md)

### `parallelExecute()` <a name="parallelExecute"></a>

Выполнить параллельно пакет запросов. Актуально для PostgreSQL

#### Сигнатура

- **public** method.
- Может принимать следующий параметр(ы):
    - `$batch` (`array`) - массив транзакций
- Возвращает `array` value.
- Выбрасывает одно из следующих исключений:
    - [`avtomon\CachePDOException`](../avtomon/CachePDOException.md)

### `async()` <a name="async"></a>

Отправить асинхронно пакет транзакций на сервер (актуально для PostgreSQL)

#### Сигнатура

- **public** method.
- Может принимать следующий параметр(ы):
    - `$query` (`string[]`|`string`) - запрос или массив запросов
    - `$data` (`array`) - параметры подготовленного запроса
- Возвращает `bool` value.
- Выбрасывает одно из следующих исключений:
    - [`avtomon\CachePDOException`](../avtomon/CachePDOException.md)

### `createQStrFromBatch()` <a name="createQStrFromBatch"></a>

Формирует строку для асинхронного выполнения методами asyncBatch и execBatch

#### Сигнатура

- **protected** method.
- Может принимать следующий параметр(ы):
    - `$batch` (`array`) - массив транзакций
- Возвращает `string` value.

### `execBatch()` <a name="execBatch"></a>

Выполнить пакет транзакций с проверкой результата выполнения. Актуально для PostgreSQL

#### Сигнатура

- **public** method.
- Может принимать следующий параметр(ы):
    - `$batch` (`array`) - массив транзакций
- Возвращает `bool` value.
- Выбрасывает одно из следующих исключений:
    - [`avtomon\CachePDOException`](../avtomon/CachePDOException.md)

