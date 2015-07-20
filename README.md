pop-db
======

[![Build Status](https://travis-ci.org/popphp/pop-db.svg?branch=master)](https://travis-ci.org/popphp/pop-db)
[![Coverage Status](http://www.popphp.org/cc/coverage.php?comp=pop-db)](http://www.popphp.org/cc/pop-db/)

OVERVIEW
--------
`pop-db` is a robust database component that provides multiple sets of features and functionality
to easily interface with databases. Features include:

* Database Adapters
    + MySQL
    + PostgreSQL
    + Sqlite
    + PDO
    + SQL Server
    + Oracle
* Active Record Implementation
* SQL Query Builder

`pop-db`is a component of the [Pop PHP Framework](http://www.popphp.org/).

INSTALL
-------

Install `pop-db` using Composer.

    composer require popphp/pop-db

## BASIC USAGE

* [Connect to a database](#connect-to-a-database)
* [Using the SQL query builder](#using-the-sql-query-builder)
* [Using active record](#using-active-record)  

### Connect to a database

Connecting to a database is easy. You can use the factory:

```php
use Pop\Db\Db;

// Returns an instance of the Pop\Db\Adapter\Mysql
$mysql = Db::connect('mysql', [
    'database' => 'mysql_database',
    'username' => 'mysql_username',
    'password' => 'mysql_password',
    'host'     => 'localhost'
]);

// Returns an instance of the Pop\Db\Adapter\Mysql
$pgsql = Db::connect('pgsql', [
    'database' => 'pgsql_database',
    'username' => 'pgsql_username',
    'password' => 'pgsql_password',
    'host'     => 'localhost'
]);

// Returns an instance of the Pop\Db\Adapter\Pdo, with the DSN set to 'sqlite'
$pdo = Db::connect('pdo', [
    'database' => '/path/to/database.sqlite',
    'type'     => 'sqlite'
]);
```

Or, you can just directly create new database adapter objects:

```php
use Pop\Db\Adapter;

$mysql = new Adapter\Mysql([
    'database' => 'mysql_database',
    'username' => 'mysql_username',
    'password' => 'mysql_password',
    'host'     => 'localhost'
]);
```

[Top](#basic-usage)

### Using the SQL query builder

Once you have a database adapter object ready to go, you can utilize the
SQL query builder to help you build queries with the correct syntax for
that particular database.

```php
use Pop\Db\Db;
use Pop\Db\Sql;

$db = Db::connect('mysql', [
    'database' => 'mysql_database',
    'username' => 'mysql_username',
    'password' => 'mysql_password',
    'host'     => 'localhost'
]);

// Create the SQL object, passing it the DB object and a table name.
$sql = new Sql($db, 'users');

$sql->select();
echo $sql;  
```

The above example produces:

```sql
SELECT * FROM `users`
```

```php
$sql->select(['id', 'username'])->where('id < :id')->orderBy('id', 'DESC');;
echo $sql;
```

The above example produces:

```sql
SELECT `id`, `username` FROM `users` WHERE (`id` < ?) ORDER BY `id` DESC
```

[Top](#basic-usage)

### Using active record



[Top](#basic-usage)