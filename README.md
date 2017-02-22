pop-db
======

[![Build Status](https://travis-ci.org/popphp/pop-db.svg?branch=master)](https://travis-ci.org/popphp/pop-db)
[![Coverage Status](http://cc.popphp.org/coverage.php?comp=pop-db)](http://cc.popphp.org/pop-db/)

OVERVIEW
--------
`pop-db` is a robust database component that provides a variety of features and functionality
to easily interface with databases. Those features include:

* Database Adapters
    + MySQL
    + PostgreSQL
    + Sqlite
    + PDO
    + SQL Server
* SQL Query Builder
* SQL Schema Migrator
* An Active Record Implementation
    + Relationship association support

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
that particular database. The beauty of this is that it takes the burden
off of you, the user, from remembering all of the slight differences
between the different database platforms.

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
$sql = $db->createSql();

$sql->select()->from('users');
echo $sql;
```

```sql
SELECT * FROM `users`
```

##### INSERT example:

```php
$sql->insert('users')->values([
    'username' => ':username',
    'password' => ':password'
]);
echo $sql;
```

```sql
INSERT INTO `users` (`username`, `password`) VALUES (?, ?)
```

If the database adapter was PostgreSQL instead of MySQL, it would have produced:

```sql
INSERT INTO "users" ("username", "password") VALUES ($1, $2)
```

##### DELETE example:

```php
$sql->delete('users')->where('id = :id');
echo $sql;
```

```sql
DELETE FROM `users` WHERE (`id` = ?)
```

##### UPDATE example:

```php
$sql->update('users')->values([
    'username' => ':username',
    'password' => ':password'
])->where('id = :id');
echo $sql;
```

```sql
UPDATE `users` SET `username` = ?, `password` = ? WHERE (`id` = ?)
```

##### A more complex SELECT example, using JOIN:

```php
$sql->select(['id', 'username', 'email'])->from('users')
    ->leftJoin('user_info', ['users.id' => 'user_info.user_id'])
    ->where('id < :id')
    ->orderBy('id', 'DESC');
echo $sql;
```

```sql
SELECT `id`, `username`, `email` FROM `users`
LEFT JOIN `user_info` ON (`users`.`id` = `user_info`.`user_id`)
WHERE (`id` < ?) ORDER BY `id` DESC
```

##### Executing the query with the adapter

For the simple example above (standard query without a parameter), you can use the `query()` method:

```php
$db->query((string)$sql);

while (($row = $db->fetch())) {
    print_r($row);
}
```

For the prepared statement example, you would use the `execute()` method:

```php
$db->prepare((string)$sql)
   ->bindParams(['id' => 1000])
   ->execute();

$rows = $db->fetchAll();

foreach ($rows as $row) {
    print_r($row);
}
```

[Top](#basic-usage)

### Using active record

The implementation of the Active Record pattern is actually a bit of a hybrid between
a Row Gateway and a Table Gateway pattern. It does follow the concept of selecting and
modifying a single row entry, but also expands to allow you to select multiple rows
at a time. There are a few helper methods to allow you a quick way to execute some
common queries.

The main idea behind this particular implementation of Active Record is that you would have a
class that represents a table, and the class name is the table name (unless otherwise specified.)
The table class would extend the `Pop\Db\Record` class. By default, the primary key is set to 'id',
but that can be changed as well.

```php
namespace MyApp\Table;

use Pop\Db\Record;

class Users extends Record {

}
```

At some point at the beginning of the life-cycle of your application, you would need to set the
database adapter object for the application to use:

```php
Pop\Db\Record::setDb(Db::connect('mysql', [
    'database' => 'mysql_database',
    'username' => 'mysql_username',
    'password' => 'mysql_password',
    'host'     => 'localhost'
]));
```

Then from there simple queries are possible with the helper methods:

```php
use MyApp\Table\Users;

// Find the user with id = 1001
$user = Users::findById(1001);
if (isset($user->id)) {
    echo $user->username;
}
```

```php
use MyApp\Table\Users;

// Find all active users
$users = Users::findBy(['active' => 1]);
if ($users->hasRows()) {
    foreach ($users->rows() as $user) {
        echo $user->username;
    }
}
```

```php
use MyApp\Table\Users;

// Find all users
$users = Users::findAll();
if ($users->hasRows()) {
    foreach ($users->rows() as $user) {
        echo $user->username;
    }
}
```

The examples above are basic SELECT examples. Let's look at some INSERT and UPDATE examples:

```php
use MyApp\Table\Users;

$user = new Users([
    'username' => 'testuser',
    'password' => '12test34',
    'email'    => 'test@test.com',
    'active'   => 1
]);

// Perform an INSERT statement to save the new user record
$user->save();

// Echoes the newly assigned ID of that newly inserted user record
echo $user->id;
```

```php
use MyApp\Table\Users;

$user = Users::findById(1001);
if (isset($user->id)) {
    $user->email = 'new_email@test.com';

    // Perform an UPDATE statement to modify the existing user record
    $user->save();
}
```

And here's an example of deleting a record:

```php
use MyApp\Table\Users;

$user = Users::findById(1001);
if (isset($user->id)) {
    $user->delete();  // Performs a DELETE statement to delete the user record
}
```

[Top](#basic-usage)