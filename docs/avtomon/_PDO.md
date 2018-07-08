<small>avtomon</small>

_PDO
====

Обертка для PDO

Описание
-----------

Class _PDO

Сигнатура
---------

- **class**.

Константы
---------

class устанавливает следующие константы:

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

Методы
-------

Методы класса class:

- [`__construct()`](#__construct) &mdash; _PDO constructor
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

### `__construct()` <a name="__construct"></a>

_PDO constructor

#### Сигнатура

- **public** method.
- Может принимать следующий параметр(ы):
    - `$dns` (`string`) &mdash; - строка подключения
    - `$login` (`string`) &mdash; - пользователь БД
    - `$password` (`string`) &mdash; - пароль
    - `$schemas` (`array`) &mdash; - какие схемы будут использоваться
    - `$options` (`array`) &mdash; - дополнительные опции
    - `$isArrayResults` (`bool`) &mdash; - возвращать результат только в виде массива
- Ничего не возвращает.

### `initSessionStorage()` <a name="initSessionStorage"></a>

Инициализировать хранение имен таблиц в сессии

#### Сигнатура

- **protected static** method.
- Может принимать следующий параметр(ы):
    - `$dbName` (`string`) &mdash; - имя базы данных
- Ничего не возвращает.

### `query()` <a name="query"></a>

Сделать запрос к БД, поддерживает подготовленные выражения

#### Сигнатура

- **public** method.
- Может принимать следующий параметр(ы):
    - `$query` (`string[]`|`string`) &mdash; - запрос
    - `$params` (`array`) &mdash; - параметры запроса
- Может возвращать одно из следующих значений:
    - `int`
    - `array`
- Выбрасывает одно из следующих исключений:
    - [`avtomon\_PDOException`](../avtomon/_PDOException.md)

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
    - `$query` (`string`) &mdash; - запрос
- Возвращает `array` value.

### `getTables()` <a name="getTables"></a>

Возвращаем имена таблиц использующихся в запросе

#### Сигнатура

- **public** method.
- Может принимать следующий параметр(ы):
    - `$query` (`string`) &mdash; - запрос
- Возвращает `array` value.
- Выбрасывает одно из следующих исключений:
    - [`avtomon\_PDOException`](../avtomon/_PDOException.md)

### `parallelExecute()` <a name="parallelExecute"></a>

Выполнить параллельно пакет запросов. Актуально для PostgreSQL

#### Сигнатура

- **public** method.
- Может принимать следующий параметр(ы):
    - `$batch` (`array`) &mdash; - массив транзакций
- Возвращает `array` value.
- Выбрасывает одно из следующих исключений:
    - [`avtomon\_PDOException`](../avtomon/_PDOException.md)

### `async()` <a name="async"></a>

Отправить асинхронно пакет транзакций на сервер (актуально для PostgreSQL)

#### Сигнатура

- **public** method.
- Может принимать следующий параметр(ы):
    - `$query` (`string[]`|`string`) &mdash; - запрос или массив запросов
    - `$data` (`array`) &mdash; - параметры подготовленного запроса
- Возвращает `bool` value.
- Выбрасывает одно из следующих исключений:
    - [`avtomon\_PDOException`](../avtomon/_PDOException.md)

### `createQStrFromBatch()` <a name="createQStrFromBatch"></a>

Формирует строку для асинхронного выполнения методами asyncBatch и execBatch

#### Сигнатура

- **protected** method.
- Может принимать следующий параметр(ы):
    - `$batch` (`array`) &mdash; - массив транзакций
- Возвращает `string` value.

### `execBatch()` <a name="execBatch"></a>

Выполнить пакет транзакций с проверкой результата выполнения. Актуально для PostgreSQL

#### Сигнатура

- **public** method.
- Может принимать следующий параметр(ы):
    - `$batch` (`array`) &mdash; - массив транзакций
- Возвращает `bool` value.
- Выбрасывает одно из следующих исключений:
    - [`avtomon\_PDOException`](../avtomon/_PDOException.md)

