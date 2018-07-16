<small> avtomon </small>

CachePDO
========

CachePDO is a wrapper class for the interoperability of a PHP application with PostgreSQL and MySQL.

Description
-----------

Allows you to transparently interact with any of these databases without going into the differences between PHP and these systems -
for the developer, work with both DBMS will be the same from the point of view of programming.
The class supports prepared expressions. In addition, there is additional functionality for the implementation of the concept
parallel execution of requests within one connection to the database. And there are methods for implementing
asynchronous execution of request packets.

Class CachePDO

Signature
---------

- **class**.

Constants
---------

class sets the following constants:

  - [`ALLOW_DRIVERS`](#ALLOW_DRIVERS) &mdash; Available DBMS drivers
  - [`PGSQL_ADDITIONAL_TABLES`](#PGSQL_ADDITIONAL_TABLES) &mdash; What schemas will the object work on when connecting to PosqlgreSQL
  - [`MYSQL_ADDITIONAL_TABLES`](#MYSQL_ADDITIONAL_TABLES) &mdash; With what schemes will the object work when connecting to MySQL
- [DUPLICATE_ERROR_CODE`](#DUPLICATE_ERROR_CODE) &mdash; The code indicating the error that occurred when trying to add a duplicate of data
  - [`DB_MAX_PARALLEL_CONNECTS`](#DB_MAX_PARALLEL_CONNECTS) &mdash; Maximum number of concurrent transactions
- [EXECUTE_MULTIPLE_PATH`(#EXECUTE_MULTIPLE_PATH) &mdash; The path to a file with a stored procedure that provides parallel execution of queries

Properties
----------

class sets the following properties:

  - [`$dns`](#$dns) &mdash; The connection string to the database
  - [`$dbh`](#$dbh) &mdash; Handler connecting to the database
  - [`$dbdriver`](#$dbdriver) &mdash; DBMS driver name
  - [`$tables`](#$tables) &mdash; List of database tables
  - [`$isArrayResults`](#$isArrayResults) &mdash; Return an empty array if there is no query result
  - [`$instances`](#$instances) &mdash; Saved CachePDO objects

### `$dns`<a name="dns"> </a>

The connection string to the database

#### Signature

- **protected** property.
- The value of `string`.

### `$dbh`<a name="dbh"> </a>

Handler connecting to the database

#### Signature

- **protected** property.
- Can be one of the following types:
  - `null`
  - [`PDO`](http://php.net/class.PDO)

### `$dbdriver`<a name="dbdriver"> </a>

DBMS driver name

#### Signature

- **protected** property.
- The value of `string`.

### `$tables`<a name="tables"> </a>

List of database tables

#### Signature

- **protected** property.
- Can be one of the following types:
- array
  - `int`

### `$isArrayResults`<a name="isArrayResults"> </a>

Return an empty array if there is no query result

#### Signature

- **protected** property.
- The value of `bool`.

### `$instances`<a name="instances"> </a>

Saved CachePDO objects

#### Signature

- **public static** property.
- The value of `array`.

Methods
-------

Class methods class:

  - [`getInstance()`](#getInstance) &mdash; CachePDO Factory
  - [`__construct()`](#__construct) &mdash; Constructor. Intentionally made open to give more flexibility
  - [`addAdditionTables()`](#addAdditionTables) &mdash; Add additional tables to use
- [initSessionStorage() `](#initSessionStorage) &mdash; Initialize storage of table names in a session
  - [`query()`](#query) &mdash; Make a query to the database, supports the prepared expressions
  - [`getDBDriver()`](#getDBDriver) &mdash; Get the name of the DBMS driver
  - [`getDBH()`](#getDBH) &mdash; Will return connection to the database
  - [`beginTransaction()`](#beginTransaction) &mdash; Start transaction
  - [`commit()`](#commit) &mdash; Commit transaction
  - [`rollBack()`](#rollBack) &mdash; Roll back transactional
  - [`getEditTables()`](#getEditTables) &mdash; Return the table names used in the query only for change requests
  - [`getTables()`](#getTables) &mdash; Return the names of the tables used in the query
  - [`parallelExecute()`](#parallelExecute) &mdash; Run a package of requests in parallel. Actual for PostgreSQL
  - [`async()`](#async) &mdash; Send asynchronously the transaction package to the server (actual for PostgreSQL)
  - [`createQStrFromBatch()`](#createQStrFromBatch) &mdash; Forms a string for asynchronous execution using asyncBatch and execBatch
  - [`execBatch()`](#execBatch) &mdash; Run a transaction package that checks the result. Actual for PostgreSQL

### `getInstance()`<a name="getInstance"> </a>

CachePDO Factory

#### Signature

- **public static** method.
- It can take the following parameter (s):
  - `$dns`(`string`) - connection string
  - `$login`(`string`) - database user
  - `$password`(`string`) - password
  - `$schemas`(`array`) - which schemes will be used
  - `$options`(`array`) - additional options
  - `$isArrayResults`(`bool`) - return the result only in the form of an array
- Returns [`CachePDO`](../avtomon/CachePDO.md) value.
- Throws one of the following exceptions:
  - [`avtomon\CachePDOException`](../avtomon/CachePDOException.md)

### `__construct()`<a name="__construct"> </a>

Constructor. Intentionally made open to give more flexibility

#### Signature

- **public** method.
- It can take the following parameter (s):
  - `$dns`(`string`) - connection string
  - `$login`(`string`) - database user
  - `$password`(`string`) - password
  - `$schemas`(`array`) - which schemes will be used
  - `$options`(`array`) - additional options
  - `$isArrayResults`(`bool`) - return the result only in the form of an array
- Returns nothing.
- Throws one of the following exceptions:
  - [`avtomon\CachePDOException`](../avtomon/CachePDOException.md)

### `addAdditionTables()`<a name="addAdditionTables"> </a>

Add additional tables to use

#### Signature

- **protected** method.
- Returns nothing.

### `initSessionStorage()`<a name="initSessionStorage"> </a>

Initialize storage of table names in a session

#### Signature

- **protected static** method.
- It can take the following parameter (s):
  - `$dbName`(`string`) - the name of the database
- Returns nothing.

### `query()`<a name="query"> </a>

Make a query to the database, supports the prepared expressions

#### Signature

- **public** method.
- It can take the following parameter (s):
  - `$query`(`string [] `|`string`) - query
  - `$params`(`array`) - query parameters
- Can return one of the following values:
- array
  - `int`

### `getDBDriver()`<a name="getDBDriver"> </a>

Get the name of the DBMS driver

#### Signature

- **public** method.
Returns `string`value.

### `getDBH()`<a name="getDBH"> </a>

Will return connection to the database

#### Signature

- **public** method.
- Returns [`PDO`](http://php.net/class.PDO) value.

### `beginTransaction()`<a name="beginTransaction"> </a>

Start transaction

#### Signature

- **public** method.
- Returns the `bool`value.

### `commit()`<a name="commit"> </a>

Commit transaction

#### Signature

- **public** method.
- Returns the `bool`value.

### `rollBack()`<a name="rollBack"> </a>

Roll back transactional

#### Signature

- **public** method.
- Returns the `bool`value.

### `getEditTables()`<a name="getEditTables"> </a>

Return the table names used in the query only for change requests

#### Signature

- **public** method.
- It can take the following parameter (s):
  - `$query`(`string`) - query
Returns the `array`value.
- Throws one of the following exceptions:
  - [`avtomon\CachePDOException`](../avtomon/CachePDOException.md)

### `getTables()`<a name="getTables"> </a>

Return the names of the tables used in the query

#### Signature

- **public** method.
- It can take the following parameter (s):
  - `$query`(`string`) - query
Returns the `array`value.
- Throws one of the following exceptions:
  - [`avtomon\CachePDOException`](../avtomon/CachePDOException.md)

### `parallelExecute()`<a name="parallelExecute"> </a>

Run a package of requests in parallel. Actual for PostgreSQL

#### Signature

- **public** method.
- It can take the following parameter (s):
  - `$batch`(`array`) - transaction array
Returns the `array`value.
- Throws one of the following exceptions:
  - [`avtomon\CachePDOException`](../avtomon/CachePDOException.md)

### `async()`<a name="async"> </a>

Send asynchronously the transaction package to the server (actual for PostgreSQL)

#### Signature

- **public** method.
- It can take the following parameter (s):
  - `$query`(`string [] `|`string`) - query or array of queries
  - `$data`(`array`) - parameters of the prepared query
- Returns the `bool`value.
- Throws one of the following exceptions:
  - [`avtomon\CachePDOException`](../avtomon/CachePDOException.md)

### `createQStrFromBatch()`<a name="createQStrFromBatch"> </a>

Forms a string for asynchronous execution using asyncBatch and execBatch

#### Signature

- **protected** method.
- It can take the following parameter (s):
  - `$batch`(`array`) - transaction array
Returns `string`value.

### `execBatch()`<a name="execBatch"> </a>

Run a transaction package that checks the result. Actual for PostgreSQL

#### Signature

- **public** method.
- It can take the following parameter (s):
  - `$batch`(`array`) - transaction array
- Returns the `bool`value.
- Throws one of the following exceptions:
  - [`avtomon\CachePDOException`](../avtomon/CachePDOException.md)

