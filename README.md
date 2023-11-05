pop-db
======

[![Build Status](https://github.com/popphp/pop-db/workflows/phpunit/badge.svg)](https://github.com/popphp/pop-db/actions)
[![Coverage Status](http://cc.popphp.org/coverage.php?comp=pop-db)](http://cc.popphp.org/pop-db/)

[![Join the chat at https://popphp.slack.com](https://media.popphp.org/img/slack.svg)](https://popphp.slack.com)
[![Join the chat at https://discord.gg/D9JBxPa5](https://media.popphp.org/img/discord.svg)](https://discord.gg/D9JBxPa5)

* [Overview](#overview)
* [Install](#install)
* [Quickstart](#quickstart)
    - [Connect to a Database](#connect-to-a-database)
    - [Query a Database](#query-a-database)
    - [Table Class](#table-class)
* [Adapters](#adapters)
    - [MySQL](#mysql)
    - [Postgresql](#postgresql)
    - [SQLite](#sqlite)
    - [PDO](#pdo)
    - [SQL Server](#sql-server)
* [ORM](#orm)
    - [Active Record](#active-record)
    - [Table Gateway](#table-gateway)
    - [Shorthand Syntax](#shorthand-syntax)
* [Querying](#querying)
    - [Prepared Statements](#prepared-statements)
* [Query Builder](#query-builder)
* [Schema Builder](#schema-builder)
* [Migrator](#migrator)

Overview
--------
`pop-db` is a robust database ORM-style component that provides a wide range of features
and functionality to easily interface with databases. Those features include:

* Database Adapters
  - MySQL
  - PostgreSQL
  - Sqlite
  - PDO
  - SQL Server
* ORM-style concepts
  - Active Record
  - Table Gateway
  - Relationship Associations
* SQL Query Builder
* SQL Schema Builder
* Migrator

`pop-db`is a component of the [Pop PHP Framework](http://www.popphp.org/).

[Top](#pop-db)

Install
-------

Install `pop-db` using Composer.

    composer require popphp/pop-db

Or, require it in your composer.json file

    "require": {
        "popphp/pop-db" : "^6.0.0"
    }

[Top](#pop-db)

Quickstart
----------

### Connect to a database

You can connect to a database using the `Pop\Db\Db::connect()` method:

```php
use Pop\Db\Db;

$db = Db::connect('mysql', [
    'database' => 'DATABASE',
    'username' => 'DB_USER',
    'password' => 'DB_PASS',
    'host'     => 'localhost'
]);
```

Or, alternatively, there are shorthand methods for each database connection type:

```php
use Pop\Db\Db;

$db = Db::mysqlConnect([
    'database' => 'DATABASE',
    'username' => 'DB_USER',
    'password' => 'DB_PASS',
    'host'     => 'localhost'
]);
```

- `mysqlConnect()`
- `pgsqlConnect()`
- `sqliteConnect()`
- `pdoConnect()`
- `sqlsrvConnect()`

If no `host` value is given, it will default to `localhost`.

[Top](#pop-db)

### Query a database

Once you have a database object that represents a database connection, you can
use it to query the database:

```php
use Pop\Db\Db;

$db = Db::mysqlConnect([
    'database' => 'popdb',
    'username' => 'popuser',
    'password' => '12pop34'
]);

$db->query('SELECT * FROM `users`');
$users = $db->fetchAll();
print_r($users);
```

If there are any user records in the `users` table, the result will be:

```text
Array
(
    [0] => Array
        (
            [id] => 1
            [username] => testuser
            [password] => password
            [email] => test@test.com
        )

)
```

[Top](#pop-db)

### Table Class

Part of the benefit of using an ORM-style database library like `pop-db` is to
abstract away the layer of SQL required so that you only have to concern yourself
with interacting with objects in PHP and not writing valid, secure SQL. The ORM
does it for you. An example of this is using a table class that represents the
active record pattern (which will be explored more in-depth below.)

```php
use Pop\Db\Db;
use Pop\Db\Record;

$db = Db::mysqlConnect([
    'database' => 'popdb',
    'username' => 'popuser',
    'password' => '12pop34'
]);

class Users extends Record {}

Record::setDb($db);
```

In the above example, a database object is created and passed to the `Pop\Db\Record`
class. This is so that any classes that extend `Pop\Db\Record` will be aware of and have
access to the database object.

Then, a table class that represents the `users` table in the database extends the
`Pop\Db\Record` class and inherits all of its built-in functionality. From there,
methods can be called to fetch data out of the `users` table or save new data to
the `users` table.

**Fetch users**

```php
$users = Users::findAll()->toArray();
print_r($users);
```

```text
Array
(
    [0] => Array
        (
            [id] => 1
            [username] => testuser
            [password] => 12test34
            [email] => test@test.com
        )

)
```

**Fetch user ID 1**

```php
$user = Users::findById(1)->toArray();
print_r($user);
```

```text
Array
(
    [id] => 1
    [username] => testuser
    [password] => 12test34
    [email] => test@test.com
)
```

**Edit user ID 1**

```php
$user = Users::findById(1);
$user->username = 'testuser2';
$user->email    = 'test2@test.com'; 
$user->save();
print_r($user->toArray());
```

```text
Array
(
    [id] => 1
    [username] => testuser2
    [password] => 12test34
    [email] => test2@test.com
)
```

**Create new user**

```php
$user = new Users([
    'username' => 'newuser',
    'password' => 'somepassword',
    'email'    => 'newuser@test.com'
]);
$user->save();
print_r($user->toArray());
```

```text
Array
(
    [username] => newuser
    [password] => somepassword
    [email] => newuser@test.com
    [id] => 2
)
```

[Top](#pop-db)

Adapters
--------

[Top](#pop-db)

### MySQL

[Top](#pop-db)

### Postgresql

[Top](#pop-db)

### SQLite

[Top](#pop-db)

### PDO

[Top](#pop-db)

### SQL Server

[Top](#pop-db)

ORM
---

[Top](#pop-db)

### Active Record

[Top](#pop-db)

### Table Gateway

[Top](#pop-db)

### Shorthand Syntax

[Top](#pop-db)

Querying
--------

[Top](#pop-db)

### Prepared Statements

[Top](#pop-db)

Query Builder
-------------

[Top](#pop-db)

Schema Builder
--------------

[Top](#pop-db)

Migrator
--------

[Top](#pop-db)
