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
    - [PostgreSQL](#postgresql)
    - [SQLite](#sqlite)
    - [SQL Server](#sql-server)
    - [PDO](#pdo)
* [ORM](#orm)
    - [Active Record](#active-record)
    - [Encoded Record](#encoded-record)
    - [Table Gateway](#table-gateway)
    - [Options](#options)
    - [Execute Queries](#execute-queries)
    - [Relationships](#relationships)
    - [Shorthand Syntax](#shorthand-syntax)
* [Querying](#querying)
    - [Prepared Statements](#prepared-statements)
* [Query Builder](#query-builder)
* [Schema Builder](#schema-builder)
* [Migrator](#migrator)
* [Seeder](#seeder)
* [Profiler](#profiler)

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
    'database' => 'DATABASE',
    'username' => 'DB_USER',
    'password' => 'DB_PASS'
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
with interacting with objects in PHP and not writing SQL. The ORM does it for you.
An example of this is using a table class that represents the active record pattern
(which will be explored more in-depth below.)

```php
use Pop\Db\Db;
use Pop\Db\Record;

$db = Db::mysqlConnect([
    'database' => 'DATABASE',
    'username' => 'DB_USER',
    'password' => 'DB_PASS'
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

The basics of connecting to a database with an adapter was outlined in the [quickstart](#quickstart)
section. In this section, we'll go over the basics of each database adapter. Each of them
have slightly connection parameters, but once the different adapter objects are created, they all
share a common interface to interact with the database.

- `connect(array $options = [])`
- `beginTransaction()`
- `commit()`
- `rollback()`
- `query(mixed $sql)`
- `prepare(mixed $sql)`
- `bindParams(array $params)`
- `execute()`
- `fetch()`
- `fetchAll()`
- `disconnect()`
- `escape(?string $value = null)`
- `getLastId()`
- `getNumberOfRows()`
- `getVersion()`
- `getTables()`

[Top](#pop-db)

### MySQL

The supported options to create a MySQL database adapter and connect with a MySQL database are:

- `database` (required)
- `username` (required)
- `password` (required)
- `host` (optional, defaults to `localhost`)
- `port`
- `socket`

```php
$db = Db::mysqlConnect([
    'database' => 'DATABASE',
    'username' => 'DB_USER',
    'password' => 'DB_PASS'
]);
```

The `Pop\Db\Adapter\Mysql` object that is returned utilizes the `mysqli` class available with the `mysqli`
PHP extension.

[Top](#pop-db)

### PostgreSQL

The supported options to create a PostgreSQL database adapter and connect with a PostgreSQL database are:

- `database` (required)
- `username` (required)
- `password` (required)
- `host` (optional, defaults to `localhost`)
- `hostaddr`
- `port`
- `connect_timeout`
- `options`
- `sslmode`
- `persist`

```php
$db = Db::pgsqlConnect([
    'database' => 'DATABASE',
    'username' => 'DB_USER',
    'password' => 'DB_PASS'
]);
```

The `Pop\Db\Adapter\Pgsql` object that is returned utilizes the `pg_*` functions available with the `pgsql`
PHP extension.

[Top](#pop-db)

### SQLite

The supported options to create a SQLite database adapter and connect with a SQLite database are:

- `database` (required - path to database file on disk)
- `flags`
- `key`

```php
$db = Db::mysqlConnect([
    'database' => '/path/to/my_database.sqlite',
]);
```

The `Pop\Db\Adapter\Sqlite` object that is returned utilizes the `Sqlite3` class available with the `sqlite3`
PHP extension.

**NOTE:** It is important to make sure the database file has the appropriate permissions for the
database adapter to be able to access and modify it.

[Top](#pop-db)

### SQL Server

The supported options to create a SQL Server database adapter and connect with a SQL Server database are:

- `database` (required)
- `username` (required)
- `password` (required)
- `host` (optional, defaults to `localhost`)
- `info`
- `ReturnDatesAsStrings`

```php
$db = Db::sqlsrvConnect([
    'database' => 'DATABASE',
    'username' => 'DB_USER',
    'password' => 'DB_PASS'
]);
```

The `Pop\Db\Adapter\Sqlsrv` object that is returned utilizes the `sqlsrv_*` functions available with the
`sqlsrv` PHP extension.

[Top](#pop-db)

### PDO

The PDO adapter works with the popular PDO extension available with PHP. This encompasses multiple database
drivers that PDO supports. They provide an alternate to the other native adapters.

The supported options to create a PDO database adapter and connect with a PDO-supported database are:

- `type` (required - type of driver: `mysql`, `pgsql`, `sqlite`, `sqlsrv`, etc.)
- `database` (required)
- `username` (required for database drivers that require credentials)
- `password` (required for database drivers that require credentials)
- `host`

The `Pop\Db\Adapter\Pdo` object that is returned utilizes the classes and functions made available by the
PDO extension and its various available drivers.

[Top](#pop-db)

ORM
---

The main concept of the `pop-db` component is that of ORM - object relational mapping. This means
that all of complex things that make databases work - connections, SQL queries, etc. - are abstracted
away so the developer only has to worry about interacting with objects in PHP. The rest is handled
for you, under the hood, in a secure and efficient manner.

Of course, if you prefer to directly work with those concepts that have been abstracted away, you
can still do that with the `pop-db` component. It provides the flexibility for both styles of database
interaction.

[Top](#pop-db)

### Active Record

Central to the ORM-style of `pop-db` is its use of the active record pattern, which is built into
the `Pop\Db\Record` class. As hinted at in the [quickstart](#quickstart) section, the main concept
is to write "table" classes that represent tables in the database and that extend the `Pop\Db\Record`
class.

```php
use Pop\Db\Db;
use Pop\Db\Record;

$db = Db::mysqlConnect([
    'database' => 'DATABASE',
    'username' => 'DB_USER',
    'password' => 'DB_PASS'
]);

class Users extends Record {}

Record::setDb($db);
```

#### Registering the database

In the above example, a `users` table class has been created that inherits all of the functionality of
`Pop\Db\Record`. The database adapter has been registered with the `Pop\Db\Record` class, which means
any table class that extends it will have access to that database adapter.

If you need to add specific database adapters to specific table classes, you can do that as well:

```php
use Pop\Db\Db;
use Pop\Db\Record;

$db = Db::mysqlConnect([
    'database' => 'DATABASE',
    'username' => 'DB_USER',
    'password' => 'DB_PASS'
]);

$dbUsers = Db::mysqlConnect([
    'database' => 'DATABASE_FOR_USERS',
    'username' => 'DB_USER',
    'password' => 'DB_PASS'
]);

class Users extends Record {};

Users::setDb($dbUsers); // Only the users table class uses the $dbUsers connection
Record::setDb($db);     // All other table classes will use the $db connection
```

#### Table configuration

A few things are configured by default:

- The table name is automatically parsed from the class name
    + `Users` becomes `users`
    + `UserLogins` becomes `user_logins`
- The primary ID is set to `id`
- There is no table prefix

However, you can override that through table properties:

```php
class Users extends Record
{
    protected ?string $table       = 'users_table';
    protected ?string $prefix      = 'my_app_';
    protected array   $primaryKeys = ['user_id'];
}
```

Once a table class is configured, there is a basic set of static methods to get
the database adapter or other objects or info:

- `Users::getDb()` - Get the db adapter object
- `Users::db()` - Alias to getDb()
- `Users::getSql()` - Get the SQL builder object
- `Users::sql()` - Alias to getSql()
- `Users::table()` - Get the full table name, for example `my_app_users_table`
- `Users::getTableInfo()` - Get information about the table, like columns, etc.

#### Fetch a record

The basic way to use the table class is to fetch individual record objects from the database.
All of the following examples return an instance of `Users`.

```php
// Fetch a single user record by ID
$user = Users::findById(1);
```

```php
// Search for a single user record
$user = Users::findOne(['username' => 'testuser']);
```

```php
// Search for a single user record, or create one if it doesn't exist
$user = Users::findOneOrCreate(['username' => 'testuser']);
```

```php
// Search for the latest single user record
$user = Users::findLatest();
```

By default, `findLatest()` will use the primary key, like `id`. However, you can pass it another field
to sort by:

```php
// Search for the latest single user record by 'last_login'
$user = Users::findLatest('last_login');
```

#### Modify a record

Once a record has been fetched, you can then modify it and save it:

```php
$user->username = 'newusername';
$user->save();
```

or even delete it:

```php
$user->delete();
```

Other methods are available to modify an existing record:

```php
$user->increment('attempts'); // Increment column by one
$user->decrement('capacity'); // Decrement column by one
```

```php
// Make a new copy of the user record in the database
// The $replace parameter can be an array of new, overriding column values
$newUser = $user->copy($replace);
```

#### Dirty records

If a record has been modified, the changes are stored and you can get them like this:

```php
$user->username = 'newusername';
$user->email    = 'newemail@test.com';

if ($user->isDirty()) {
    print_r($user->getDirty);
}
```

```text
Array
(
    [old] => Array
        (
            [username] => testuser
            [email] => test@test.com
        )
    [new] => Array
        (
            [username] => newusername
            [email] => newemail@test.com
        )
)
```

This is useful for application components that track and log changes to data in the database.

[Top](#pop-db)

### Encoded Record

The `Pop\Db\Record\Encoded` class extends the `Pop\Db\Record` and provides the functionality
to manage fields in the database record that require encoding, serialization, encryption or
hashing of some kind. The supported types are:

- JSON
- PHP Serialization
- Base64
- 1-Way Hashing
- 2-Way Encryption

The benefit of this class is that it handles the encoding and decoding for you. To use it, you
would configure your class like this below, defining the fields that need to be encoded/decoded:

```php
use Pop\Db\Record\Encoded

class Users extends Encoded
{
    protected array $jsonFields   = ['metadata'];
    protected array $phpFields    = ['user_info'];
    protected array $base64Fields = ['user_image'];
} 
```

The above example means that any time you save to those fields, the proper encoding of the field
data will take place and the correct encoded data will be stored in the database. Then, when you
fetch the record and retrieve those fields, the proper decoding will take place, giving you the
original decoded data.

#### 1-Way Hashing

Using a password hash field would be an advanced example that would require more configuration:

```php
use Pop\Db\Record\Encoded

class Users extends Encoded
{

    protected array  $hashFields    = ['password'];
    protected string $hashAlgorithm = PASSWORD_BCRYPT;
    protected array  $hashOptions   = ['cost' => 10];
}
```

This configuration will use the defined algorithm and options to safely create and store the one-way
hash value in the database. Then, when needed, you can use the `verify()` method and check an attempted
password against that stored hash.

```php
$user = Users::findOne(['username' => 'testuser']);
if ($user->verify('password', $attemptPassword)) {
    // The user submitted the correct password.
}
```

#### 2-Way Encryption

An even more advanced example would be using an 2-way encrypted field, which uses the
Open SSL library extension. It requires a few more table properties to be configured:

- `$cipherMethod`
- `$key`
- `$iv`

You have to create an IV value and base64 encode it to set it as the `$iv` property.

```php
use Pop\Db\Record\Encoded

class Users extends Encoded
{
    protected array   $encryptedFields = ['sensitive_data'];
    protected ?string $cipherMethod    = 'aes-256-cbc';
    protected ?string $key             = 'YOUR_KEY';
    protected ?string $iv              = 'BASE64_ENCODED_IV_STRING';
}
```

This configuration will allow you to store the encrypted value in the database and
securely extract it when you fetch the record.

[Top](#pop-db)

### Table Gateway

The `Pop\Db\Record` class actually has functionality that allows you to fetch multiple records,
or rows, at a time, much like a table data gateway. The default value returned from most of these
calls is a `Pop\Db\Record\Collection`, which provides functionality to perform array-like
operations on the rows. By default, each object in the collection is an instance of the table
class that extends `Pop\Db\Record`, which allows you to work directly with those objects and
modify or delete them.

#### Find records

```php
// Find all users who have never logged in.
$users = Users::findBy(['logins' => 0]);
```

```php
// Find a group of users
$users = Users::findIn('username', ['testuser', 'someotheruser', 'anotheruser']);
```

```php
// Find all users
$users = Users::findAll();
```

You can use the `toArray()` method to convert the collection object into a plain array:

```php
// Returns an array
$users = Users::findBy(['logins' => 0])->toArray();
```

Or, in most methods, there is an `$asArray` parameter that will do the same:

```php
// 3rd param $asArray set to true; Returns an array
$users = Users::findBy(['logins' => 0], null, true);
```

#### Get count of records

If you just need to get a count of records, you can do that like this:

```php
// Get count of all users
$count = Users::getTotal();
```

```php
// Get count of all users who have never logged in.
$count = Users::getTotal(['logins' => 0]);
```

[Top](#pop-db)

### Options

In most of the methods described above, there is an available `$options` array
that allows you to really tailor the query. These are the supported options:

- `select`
- `offset`
- `limit`
- `order`
- `group`
- `join`

##### Select

Pass an array of the fields you want to select with the query. This can help
cut the amount of unwanted data that's return, or help define data to select
across multiple joined tables. If this option is not used, it will default to
`table_name.*`

##### Offset

The start offset of the returned set of data. Used typically with pagination

##### Limit

The value by which to limit the results

##### Order

The field or fields by which to order the results

##### Group

The field or fields by which to group the results

##### Join

This option allows you to define multiple tables to use in a JOIN query.

**Basic Options Example**

```php
$users = Users::findBy(['logins' => 0], [
    'select' => ['id', 'username'],
    'order'  => ['id DESC'],
    'offset' => 10
    'limit'  => 25
]);
```

**Options Example Using Join**

Assume there is another table called `Roles` and the users table contains a
`role_id` foreign key:

```php
$users = Users::findBy(['logins' => 0], [
    'select' => [
        Users::table() . '.*',
        Roles::table() . '.role',
    ],
    'join' => [
        'table'   => Roles::table(),
        'columns' => [
            Roles::table() . '.id' => Users::table() . '.role_id',
        ],
    ],
]);
```

The `join` option defines the table to join with as well as which columns to join by.
Notice that the `select` option was used to craft the required fields - in this case,
all of user fields and just the `role` field from the roles table.

The type of join defaults to a `LEFT JOIN`, but a `type` key can be added to define
alternate join types.

[Top](#pop-db)

### Execute Queries

If any of the available methods listed above don't provide what is needed,
you can execute direct queries through the table class.

#### Query (no parameters)

This will return a `Pop\Db\Record\Collection` object:

```php
$users = Users::query('SELECT * FROM ' . Users::table());
```

#### Prepared statement (w/ parameters)

This will return a `Pop\Db\Record\Collection` object:

```php
$sql   = 'SELECT * FROM ' . Users::table() . ' WHERE last_login >= :last_login';
$users = Users::execute($sql, ['last_login' => '2023-11-01 08:00:00']);
```

[Top](#pop-db)

### Relationships

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

Seeder
------

[Top](#pop-db)

Profiler
--------

[Top](#pop-db)
