pop-db
======

[![Build Status](https://github.com/popphp/pop-db/workflows/phpunit/badge.svg)](https://github.com/popphp/pop-db/actions)
[![Coverage Status](http://cc.popphp.org/coverage.php?comp=pop-db)](http://cc.popphp.org/pop-db/)

[![Join the chat at https://popphp.slack.com](https://media.popphp.org/img/slack.svg)](https://popphp.slack.com)
[![Join the chat at https://discord.gg/TZjgT74U7E](https://media.popphp.org/img/discord.svg)](https://discord.gg/TZjgT74U7E)

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
    - [Shorthand Syntax](#shorthand-syntax)
    - [Execute Queries](#execute-queries)
    - [Transactions](#active-record-transactions)
* [Relationships](#relationships)
    - [Eager-Loading](#eager-loading)
* [Querying](#querying)
    - [Prepared Statements](#prepared-statements)
    - [Transactions](#query-transactions)
* [Query Builder](#query-builder)
    - [Select](#select)
    - [Insert](#insert)
    - [Update](#update)
    - [Delete](#delete)
    - [Joins](#joins)
    - [Predicates](#predicates)
    - [Nested Predicates](#nested-predicates)
    - [Sorting, Order, Limits](#sorting-order-limits)
* [Schema Builder](#schema-builder)
    - [Create Table](#create-table)
    - [Alter Table](#alter-table)
    - [Drop Table](#drop-table)
    - [Execute Schema](#execute-schema)
    - [Schema Builder API](#schema-builder-api)
* [Migrator](#migrator)
* [Seeder](#seeder)
* [SQL Data](#sql-data)
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
* Relationships
* SQL Query Builder
* SQL Schema Builder
* Migrator
* Profiler

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
use it to query the database. There is an API to support making a query and
returning the result:

- `$db->select($sql, array $params = [])`
- `$db->insert($sql, array $params = [])`
- `$db->update($sql, array $params = [])`
- `$db->delete($sql, array $params = [])`

The above methods supports SQL queries as well as prepared statements with parameters.

```php
use Pop\Db\Db;

$db = Db::mysqlConnect([
    'database' => 'DATABASE',
    'username' => 'DB_USER',
    'password' => 'DB_PASS'
]);

$users = $db->select('SELECT * FROM `users`');
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

**An INSERT Example**

```php
use Pop\Db\Db;

$db = Db::mysqlConnect([
    'database' => 'DATABASE',
    'username' => 'DB_USER',
    'password' => 'DB_PASS'
]);

$db->insert(
    'INSERT INTO `users` (`username`, `password`, `email`) VALUES (?, ?, ?)',
    ['testuser1', 'password1', 'testuser1@test.com']
);
```

The more verbose way to make a query would be:

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
$users = Users::findAll();
print_r($users->toArray());
```

```text
Array
(
    [0] => Array
        (
            [id] => 1
            [username] => testuser
            [password] => 12345678
            [email] => test@test.com
        )

)
```

**Fetch user ID 1**

```php
$user = Users::findById(1);
print_r($user->toArray());
```

```text
Array
(
    [id] => 1
    [username] => testuser
    [password] => 12345678
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
    [password] => 12345678
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

**Delete user ID 1**

```php
$user = Users::findById(1);
$user->delete();
```

[Top](#pop-db)

Adapters
--------

The basics of connecting to a database with an adapter was outlined in the [quickstart](#quickstart)
section. In this section, we'll go over the basics of each database adapter. Each of them
have slightly different connection parameters, but once the different adapter objects are
created, they all share a common interface to interact with the database.

- `connect(array $options = [])`
- `beginTransaction()`
- `commit()`
- `rollback()`
- `isTransaction()`
- `getTransactionDepth()`
- `transaction($callable, array $params = [])`
- `isSuccess()`
- `select(string|Sql $sql, array $params = [])`
- `insert(string|Sql $sql, array $params = [])`
- `update(string|Sql $sql, array $params = [])`
- `delete(string|Sql $sql, array $params = [])`
- `executeSql(string|Sql $sql, array $params = [])`
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
- `getNumberOfAffectedRows()`
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
drivers that PDO supports. They provide an alternate to the other native drivers.

The supported options to create a PDO database adapter and connect with a PDO-supported database are:

- `type` (required - type of driver: `mysql`, `pgsql`, `sqlite`, `sqlsrv`, etc.)
- `database` (required)
- `username` (required for database drivers that require credentials)
- `password` (required for database drivers that require credentials)
- `host` (optional, defaults to `localhost`)

```php
$db = Db::pdoConnect([
    'type'     => 'mysql'
    'database' => 'DATABASE',
    'username' => 'DB_USER',
    'password' => 'DB_PASS'
]);
```

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

#### Find API

These are available static methods to find a record or records in the database table:

- `findById($id, array $options = null, bool $asArray = false)`
- `findOne(array $columns = null, array $options = null, bool $asArray = false)`
- `findOneOrCreate(array $columns = null, array $options = null, bool $asArray = false)`
- `findLatest($by = null, array $columns = null, array $options = null, bool $asArray = false)`
- `findBy(array $columns = null, array $options = null, bool $asArray = false)`
- `findByOrCreate(array $columns = null, array $options = null, bool $asArray = false)`
- `findIn($key, array $values, array $columns = null, array $options = null, bool $asArray = false)`
- `findAll(array $options = null, bool $asArray = false)`

These are available static magic helper methods to find a record or records in the database table,
based on certain conditions:

- `findWhereEquals($column, $value, array $options = null, bool $asArray = false)`
- `findWhereNotEquals($column, $value, array $options = null, bool $asArray = false)`
- `findWhereGreaterThan($column, $value, array $options = null, bool $asArray = false)`
- `findWhereGreaterThanOrEqual($column, $value, array $options = null, bool $asArray = false)`
- `findWhereLessThan($column, $value, array $options = null, bool $asArray = false)`
- `findWhereLessThanOrEqual($column, $value, array $options = null, bool $asArray = false)`
- `findWhereLike($column, $value, array $options = null, bool $asArray = false)`
- `findWhereNotLike($column, $value, array $options = null, bool $asArray = false)`
- `findWhereIn($column, $values, array $options = null, bool $asArray = false)`
- `findWhereNotIn($column, $values, array $options = null, bool $asArray = false)`
- `findWhereBetween($column, $values, array $options = null, bool $asArray = false)`
- `findWhereNotBetween($column, $values, array $options = null, bool $asArray = false)`
- `findWhereNull($column, array $options = null, bool $asArray = false)`
- `findWhereNotNull($column, array $options = null, bool $asArray = false)`

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
$user->increment('attempts'); // Increment column by one and save
$user->decrement('capacity', 5); // Decrement column by 5 and save
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
if ($user->verify('password', $attemptedPassword)) {
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
operations on the rows or data. By default, each object in the collection is an instance of the
table class that extends `Pop\Db\Record`, which allows you to work directly with those objects and
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
// 3rd parameter $asArray set to true; Returns an array
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

##### Select Columns

Pass an array of the fields you want to select with the query with the `select` key.
This can help cut the amount of unwanted data that's returned, or help define data to
select across multiple joined tables. If this option is not used, it will default to
`table_name.*`

##### Offset

The start `offset` of the returned set of data. Used typically with pagination

##### Limit

The value by which to `limit` the results

##### Order

The field or fields by which to `order` the results

##### Group

The field or fields by which to `group` the results

##### Join

The `join` option allows you to define multiple tables to use in a JOIN query.

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
alternate join types. You can also define multiple joins at a time in a nested array.

[Top](#pop-db)

### Shorthand Syntax

There is shorthand SQL syntax that is available and supported by the ``Pop\Db\Record`` class to help
give even a more granular control over your queries without having write your own or use the query builder.
Here's a list of what is supported and what it translates into:

**Basic operators**

```php
$users = Users::findBy(['id' => 1]);   // WHERE id = 1
$users = Users::findBy(['id!=' => 1]); // WHERE id != 1
$users = Users::findBy(['id>' => 1]);  // WHERE id > 1
$users = Users::findBy(['id>=' => 1]); // WHERE id >= 1
$users = Users::findBy(['id<' => 1]);  // WHERE id < 1
$users = Users::findBy(['id<=' => 1]); // WHERE id <= 1
```

**LIKE and NOT LIKE**

```php
$users = Users::findBy(['%username%'   => 'test']); // WHERE username LIKE '%test%'
$users = Users::findBy(['username%'    => 'test']); // WHERE username LIKE 'test%'
$users = Users::findBy(['%username'    => 'test']); // WHERE username LIKE '%test'
$users = Users::findBy(['-%username'   => 'test']); // WHERE username NOT LIKE '%test'
$users = Users::findBy(['username%-'   => 'test']); // WHERE username NOT LIKE 'test%'
$users = Users::findBy(['-%username%-' => 'test']); // WHERE username NOT LIKE '%test%'
```

**NULL and NOT NULL**

```php
$users = Users::findBy(['username' => null]);  // WHERE username IS NULL
$users = Users::findBy(['username-' => null]); // WHERE username IS NOT NULL
```

**IN and NOT IN**

```php
$users = Users::findBy(['id' => [2, 3]]);  // WHERE id IN (2, 3)
$users = Users::findBy(['id-' => [2, 3]]); // WHERE id NOT IN (2, 3)
```

**BETWEEN and NOT BETWEEN**

```php
$users = Users::findBy(['id' => '(1, 5)']);  // WHERE id BETWEEN (1, 5)
$users = Users::findBy(['id-' => '(1, 5)']); // WHERE id NOT BETWEEN (1, 5)
```

Additionally, if you need use multiple conditions for your query, you can and they will be
stitched together with AND:

```php
$users = Users::findBy([
    'id>'       => 1,
    '%username' => 'user1'
]);
```
which will be translated into:

```sql
WHERE (id > 1) AND (username LIKE '%test')
```

If you need to use OR instead, you can specify it like this:

```php
$users = Users::findBy([
    'id>'       => 1,
    '%username' => 'user1 OR'
]);
```

Notice the ` OR` added as a suffix to the second condition's value. That will apply the OR
to that part of the predicate like this:

```sql
WHERE (id > 1) OR (username LIKE '%test')
```

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

### Active Record Transactions

Transactions are available through the ORM active record class. There are a few ways to
execute a transaction with the main record class. In the below example, the transaction
is started by calling the `startTransaction()` method. Once that has been called, the
subsequent `save()` will automatically call `commitTransaction()` on successful save or
the `rollback` method will be called upon an exception being thrown.

```php
$user = new Users([
    'username' => 'testuser',
    'password' => 'password',
    'email'    => 'test@test.com'
]);
$user->startTransaction();
$user->save();
```

A shorthand way of doing the same would be to call the static `start()` method, which combines the
constructor and `startTransaction` calls:

```php
$user = Users::start([
    'username' => 'testuser',
    'password' => 'password',
    'email'    => 'test@test.com'
]);
$user->save();
```

If you need to execute a transaction consisting of multiple queries across multiple
active record objects, you can do that as well:

```php
try {
    Record::start();

    $user = new Users([
        'username' => 'testuser',
        'password' => 'password',
        'email'    => 'test@test.com'
    ]);
    $user->save();

    $role = new Roles([
        'role' => 'Admin'
    ]);
    $role->save();

    Record::commit();
} catch (\Exception $e) {
    Record::rollback();
    echo $e->getMessage();
}
```

A shorthand method to achieve the same thing would be to use the `transaction` method with a callable:

```php
try {
    Record::transaction(function() {
        $user = new Users([
            'username' => 'testuser',
            'password' => 'password',
            'email'    => 'test@test.com'
        ]);
        $user->save();
    
        $role = new Roles([
            'role' => 'Admin'
        ]);
        $role->save();
    });
} catch (\Exception $e) {
    echo $e->getMessage();
}
```

Nested transactions are supported as well:

```php
try {
    Record::transaction(function() {
        $user = new Users([
            'username' => 'testuser',
            'password' => 'password',
            'email'    => 'test@test.com'
        ]);
        $user->save();
        
        Record::transaction(function(){
            $role = new Roles([
                'role' => 'Admin'
            ]);
            $role->save();
        });
    });
} catch (\Exception $e) {
    echo $e->getMessage();
}
```

[Top](#pop-db)

Relationships
-------------

Relationships allow for a simple way to select related data within the database. These relationships
can be 1:1 or 1:many, and you can define them as methods in your table class. The primary methods
being leveraged here from within the `Pop\Db\Record` class are:

* `hasOneOf()`
    - 1:1 relationship where a foreign key in the sibling object is a primary key in different sibling object 
* `hasOne()`
    - 1:1 relationship where a foreign key in the child object is a primary key in the parent object
* `hasMany()`
    - 1:1 relationship where a foreign key in many child objects is a primary key in the parent object
* `belongsTo()`
    - 1:1 relationship where a foreign key in the child object is a primary key in the parent object (inverse "hasOne")

Let's consider the following tables classes that represent tables in the database:

```php
class Users extends Pop\Db\Record
{

    /**
     * Mock Schema
     *    - id
     *    - role_id (FK to roles.id)
     *    - username
     *    - password
     *    - email
     */

    // Define the 1:1 relationship of the user's role
    public function role()
    {
        return $this->hasOneOf('Roles', 'role_id');
    }

    // Define the 1:1 relationship of the info record this user owns
    public function info()
    {
        return $this->hasOne('Info', 'user_id')
    }

    // Define the 1:many relationship to all the orders this user owns
    public function orders()
    {
        return $this->hasMany('Orders', 'user_id');
    }

}
```

```php
class Roles extends Pop\Db\Record
{
    /**
     * Mock Schema
     *    - id (FK to users.role_id)
     *    - role
     */
}
```

```php
class Info extends Pop\Db\Record
{
    /**
     * Mock Schema
     *    - user_id (FK to users.id)
     *    - address
     *    - phone
     */
    // Define the parent relationship up to the user that owns this info record
    public function user()
    {
        return $this->belongsTo('Users', 'user_id');
    }

}
```

```php
class Orders extends Pop\Db\Record
{
    /**
     * Mock Schema
     *    - id
     *    - user_id (FK to users.id)
     *    - order_date
     *    - order_total
     *    - products
     */

    // Define the parent relationship up to the user that owns this order record
    public function user()
    {
        return $this->belongsTo('Users', 'user_id');
    }

}
```

With those relationships defined, you can now call the related data like this:

```php
// The two 1:1 relationships
$user = Users::findById(1);
print_r($user->role()->toArray());
print_r($user->info()->toArray());
```

```text
Array
(
    [id] => 1
    [role] => Admin
)
Array
(
    [user_id] => 1
    [address] => 123 Main St
    [phone] => 504-555-5555
)
```

```php
// The 1:many relationship
$user   = Users::findById(1);
$orders = $users->orders();

foreach ($orders as $order) {
    echo 'Order Total: $' . $order->order_total . PHP_EOL;
}
```

```php
// The inverse 1:1 relationship
$userInfo = UserInfo::findOne(['user_id' => 1]);
print_r($userInfo->user()->toArray());
```

```text
Array
(
    [id] => 1
    [role_id] => 1
    [username] => testuser
    [password] => 12345678
    [email] => test@test.com
)
```

### Eager-Loading

In the above examples, the related data is "lazy-loaded", meaning the related data isn't available until those
relationship methods are called. However, you can call those relationship methods at the same time as you call
the primary record using the static `with()` method:

```php
$users = Users::with('orders')->getById(1);
foreach ($user->orders as $order) {
    echo 'Order Total: $' . $order->order_total . PHP_EOL;
}
```

Multiple relationships can be passed as well:

```php
$users = Users::with(['role', 'info', 'orders'])->getById(1);
```

And nested relationships are supported too. Assume there is a `Posts` class and a `Comments` class.
Also, let's assume a user object owns posts and a posts object owns comments, and the proper relationships
are set up in each table class. Then this call would be valid:

```php
$user = Users::with('posts.comments')->getById(1);
```

And would give you a user object with all of the user's `posts` and each of those post objects would have
their `comments` attached as well.

[Top](#pop-db)

Querying
--------

Instead of using the ORM-based components, you can directly query the database from the database adapter.
The API helps make specific queries or execute prepared statements, while returning the results:

- `$db->select(string|Sql $sql, array $params = []): array`
- `$db->insert(string|Sql $sql, array $params = []): int`
- `$db->update(string|Sql $sql, array $params = []): int`
- `$db->delete(string|Sql $sql, array $params = []): int`

In the case of `select()`, it will return an array of any found results. In the case of the others, it will
return the number of affected rows.

**Using a query**

```php
use Pop\Db\Db;

$db = Db::mysqlConnect([
    'database' => 'DATABASE',
    'username' => 'DB_USER',
    'password' => 'DB_PASS'
]);

$users = $db->select('SELECT * FROM `users`');
print_r($users);
```

**Using a prepared statements with parameters**

```php
use Pop\Db\Db;

$db = Db::mysqlConnect([
    'database' => 'DATABASE',
    'username' => 'DB_USER',
    'password' => 'DB_PASS'
]);

$users = $db->select('SELECT * FROM `users` WHERE `id` < ?', [10]);
print_r($users);
```

The more verbose way to query the database would be:

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

[Top](#pop-db)

### Prepared Statements

Taking it a step further, you can execute prepared statements as well:

```php
use Pop\Db\Db;

$db = Db::mysqlConnect([
    'database' => 'DATABASE',
    'username' => 'DB_USER',
    'password' => 'DB_PASS'
]);

$db->prepare('SELECT * FROM `users` WHERE `id` = ?');
$db->bindParams(['id' => 1]);
$db->execute();

$users = $db->fetchAll();
print_r($users);
```

```text
Array
(
    [0] => Array
        (
            [id] => 1
            [role_id] => 1
            [username] => testuser
            [password] => 12test34
            [email] => test@test.com
        )

)
```

[Top](#pop-db)

### Query Transactions

When using a database adapter directly, you can utilize transactions with it, like these examples below:

```php
try {
    $db->beginTransaction();
    $db->query("INSERT INTO `users` (`username`, `email`) VALUES ('testuser', 'test@test.com')");
    $db->commit();
} catch (\Exception $e) {
    $db->rollback();
}
```

```php
try {
    $db->beginTransaction();
    $db->prepare("INSERT INTO `users` (`username`, `email`) VALUES (?, ?)")
        ->bindParam([
            'username' => 'testuser',
            'email'    => 'test@test.com'
        ]);
    $db->execute();
    $db->commit();
} catch (\Exception $e) {
    $db->rollback();
}
```

You can also call a set of queries in one transaction like this:

```php
try {
    $db->transaction(function() use ($db) {
        $db->query(
            "INSERT INTO `users` (`username`, `email`) VALUES ('testuser', 'test@test.com')"
        );
    });
} catch (\Exception $e) {
    echo $e->getMessage();
}
```

Nested transactions are supported as well:

```php
try {
    $db->transaction(function() use ($db) {
        $db->query(
            "INSERT INTO `users` (`username`, `email`) VALUES ('testuser1', 'test1@test.com')"
        );
        $db->transaction(function() use ($db) {
            $db->query(
                "INSERT INTO `users` (`username`, `email`) VALUES ('testuser2', 'test2@test.com')"
            );
        });
    });
} catch (\Exception $e) {
    echo $e->getMessage();
}
```

[Top](#pop-db)

Query Builder
-------------

The query builder is available to build valid SQL queries that will work across the different database
adapters. This is useful if the application being built may deploy to different environments with
different database servers. When a prepared query statement requires placeholders for binding parameters,
use the named parameter format (e.g., `'id = :id'`). It will be translated to the correct placeholder
value for the database adapter.

### Select

```php
use Pop\Db\Db;

$db = Db::mysqlConnect([
    'database' => 'DATABASE',
    'username' => 'DB_USER',
    'password' => 'DB_PASS'
]);

$sql = $db->createSql();
$sql->select(['id', 'username'])
    ->from('users')
    ->where('id = :id');

echo $sql;
```

The following SQL query is produced for the MySQL adapter:

```sql
-- MySQL
SELECT `id`, `username` FROM `users` WHERE (`id` = ?)
```

Switching to the PostgeSQL adapter, the same code will produce:

```sql
-- PostgreSQL
SELECT "id", "username" FROM "users" WHERE ("id" = $1)
```

And switching to the SQLite adapter, and the same code will produce:

```sql
-- SQLite
SELECT "id", "username" FROM "users" WHERE ("id" = :id)
```

And of course, the `$sql` builder object can be passed directly to the database adapter
to directly execute the SQL query that has been created:

```php
use Pop\Db\Db;

$db = Db::mysqlConnect([
    'database' => 'DATABASE',
    'username' => 'DB_USER',
    'password' => 'DB_PASS'
]);

$sql = $db->createSql();
$sql->select(['id', 'username'])
    ->from('users')
    ->where('id = :id');

$db->prepare($sql);
$db->bindParams(['id' => 1]);
$db->execute();

$users = $db->fetchAll();
print_r($users);
```

[Top](#pop-db)

### Insert

```php
$sql->insert('users')->values([
    'username' => ':username',
    'password' => ':password'
]);

echo $sql;
```

```sql
-- MySQL
INSERT INTO `users` (`username`, `password`) VALUES (?, ?)
```

```sql
-- PostgreSQL
INSERT INTO "users" ("username", "password") VALUES ($1, $2)
```

```sql
-- SQLite
INSERT INTO "users" ("username", "password") VALUES (:username, :password)
```

[Top](#pop-db)

### Update

```php
$sql->update('users')->values([
    'username' => ':username',
    'password' => ':password'
])->where('id = :id');

echo $sql;
```

```sql
-- MySQL
UPDATE `users` SET `username` = ?, `password` = ? WHERE (`id` = ?)
```

```sql
-- PostgreSQL
UPDATE "users" SET "username" = $1, "password" = $2 WHERE ("id" = $3)
```

```sql
-- SQLite
UPDATE "users" SET "username" = :username, "password" = :password WHERE ("id" = :id)
```

[Top](#pop-db)

### Delete

```php
$sql->delete('users')
    ->where('id = :id');

echo $sql;
```

```sql
-- MySQL
DELETE FROM `users` WHERE (`id` = ?)
```

```sql
-- PostgreSQL
DELETE FROM "users" WHERE ("id" = $1)
```

```sql
-- SQLite
DELETE FROM "users" WHERE ("id" = :id)
```

[Top](#pop-db)

### Joins

The SQL Builder has an API to assist you in constructing complex SQL statements that use joins. Typically,
the join methods take two parameters: the foreign table and an array with a 'key => value' of the two related
columns across the two tables. Here's a SQL builder example using a LEFT JOIN:

```php
$sql->select(['id', 'username', 'email'])->from('users')
    ->leftJoin('user_info', ['users.id' => 'user_info.user_id'])
    ->where('id < :id')
    ->orderBy('id', 'DESC');

echo $sql;
```

```sql
-- MySQL
SELECT `id`, `username`, `email` FROM `users`
    LEFT JOIN `user_info` ON (`users`.`id` = `user_info`.`user_id`)
    WHERE (`id` < ?) ORDER BY `id` DESC
```

```sql
-- PostgreSQL
SELECT "id", "username", "email" FROM "users"
    LEFT JOIN "user_info" ON ("users"."id" = "user_info"."user_id")
    WHERE ("id" < $1) ORDER BY "id" DESC
```

```sql
-- SQLite
SELECT "id", "username", "email" FROM "users"
    LEFT JOIN "user_info" ON ("users"."id" = "user_info"."user_id")
    WHERE ("id" < :id) ORDER BY "id" DESC
```

Here's the available API for joins:

* `$sql->join($foreignTable, array $columns, $join = 'JOIN');` - Basic join
* `$sql->leftJoin($foreignTable, array $columns);` - Left join
* `$sql->rightJoin($foreignTable, array $columns);` - Right join
* `$sql->fullJoin($foreignTable, array $columns);` -  Full join
* `$sql->outerJoin($foreignTable, array $columns);` -  Outer join
* `$sql->leftOuterJoin($foreignTable, array $columns);` -  Left outer join
* `$sql->rightOuterJoin($foreignTable, array $columns);` -  Right outer join
* `$sql->fullOuterJoin($foreignTable, array $columns);` -  Full outer join
* `$sql->innerJoin($foreignTable, array $columns);` -  Outer join
* `$sql->leftInnerJoin($foreignTable, array $columns);` -  Left inner join
* `$sql->rightInnerJoin($foreignTable, array $columns);` -  Right inner join
* `$sql->fullInnerJoin($foreignTable, array $columns);` -  Full inner join

[Top](#pop-db)

### Predicates

The SQL Builder also has an extensive API to assist you in constructing predicates with which to filter your
SQL statements. Here's a list of some of the available methods to help you construct your predicate clauses:

* `$sql->where($where);` - Add a WHERE predicate
* `$sql->andWhere($where);` - Add another WHERE predicate using the AND conjunction
* `$sql->orWhere($where);` - Add another WHERE predicate using the OR conjunction
* `$sql->having($having);` - Add a HAVING predicate
* `$sql->andHaving($having);` - Add another HAVING predicate using the AND conjunction
* `$sql->orHaving($having);` - Add another HAVING predicate using the OR conjunction

**AND WHERE**

```php
$sql->select()
    ->from('users')
    ->where('id > :id')->andWhere('email LIKE :email');

echo $sql;
```

```sql
-- MySQL
SELECT * FROM `users` WHERE ((`id` > ?) AND (`email` LIKE ?))
```

**OR WHERE**

```php
$sql->select()
    ->from('users')
    ->where('id > :id')->orWhere('email LIKE :email');

echo $sql;
```

```sql
-- MySQL
SELECT * FROM `users` WHERE ((`id` > ?) OR (`email` LIKE ?))
```

There is even a more detailed and granular API that comes with the predicate objects.

```php
$sql->select()
    ->from('users')
    ->where->greaterThan('id', ':id')->and()->equalTo('email', ':email');

echo $sql;
```

```sql
-- MySQL
SELECT * FROM `users` WHERE ((`id` > ?) AND (`email` = ?))
```

[Top](#pop-db)

### Nested Predicates

If you need to nest a predicate, there are API methods to allow you to do that as well:

* `$sql->nest($conjunction = 'AND');` - Create a nested predicate set
* `$sql->andNest();` - Create a nested predicate set using the AND conjunction
* `$sql->orNest();` - Create a nested predicate set using the OR conjunction

```php
$sql->select()
    ->from('users')
    ->where->greaterThan('id', ':id')
        ->nest()->greaterThan('logins', ':logins')
            ->or()->lessThanOrEqualTo('failed', ':failed');

echo $sql;
```

The output below shows the predicates for `logins` and `failed` are nested together:

```sql
-- MySQL
SELECT * FROM `users` WHERE ((`id` > ?) AND ((`logins` > ?) OR (`failed` <= ?)))
```

[Top](#pop-db)

### Sorting, Order, Limits

The SQL Builder also has methods to allow to further control your SQL statement's result set:

* `$sql->groupBy($by);` - Add a GROUP BY
* `$sql->orderBy($by, $order = 'ASC');` - Add an ORDER BY
* `$sql->limit($limit);` - Add a LIMIT
* `$sql->offset($offset);` - Add an OFFSET

[Top](#pop-db)

Schema Builder
--------------

In addition to the query builder, there is also a schema builder to assist with database table
structures and their management. In a similar fashion to the query builder, the schema builder
has an API that mirrors the SQL that would be used to create, alter and drop tables in a database.
It is also built to be portable and work across different environments that may have different chosen
database adapters with which to work. And like the query builder, in order for it to function correctly,
you need to pass it the database adapter your application is currently using so that it can properly
build the SQL. The easiest way to do this is to just call the `createSchema()` method from the
database adapter. It will inject itself into the Schema builder object being created.

The examples below show separate schema statements, but a single schema builder object can have multiple
schema statements within one schema builder object's life cycle.

[Top](#pop-db)

### Create Table

```php
$db = Pop\Db\Db::mysqlConnect($options);

$schema = $db->createSchema();
$schema->create('users')
    ->int('id', 16)->increment()
    ->varchar('username', 255)
    ->varchar('password', 255)
    ->primary('id');

echo $schema;
```

The above code would produce the following SQL:

```sql
-- MySQL
CREATE TABLE `users` (
  `id` INT(16) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(255),
  `password` VARCHAR(255),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

```

**Foreign Key Example**

Here is an example of creating an additional `user_info` table that references the above `users` table
with a foreign key:

```php
$schema->create('user_info')
    ->int('user_id', 16)
    ->varchar('email', 255)
    ->varchar('phone', 255)
    ->foreignKey('user_id')->references('users')->on('id')->onDelete('CASCADE');
```

The above code would produce the following SQL:

```sql
-- MySQL
CREATE TABLE `user_info` (
  `user_id` INT(16),
  `email` VARCHAR(255),
  `phone` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `user_info` ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`)
  REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
```

[Top](#pop-db)

### Alter Table

```php
$schema->alter('users')
    ->varchar('email', 255)
    ->after('password');

echo $schema;
```

which is the same as:

```php
$schema->alter('users')
    ->addColumn('email', 'VARCHAR', 255)
    ->after('password');

echo $schema;
```

And would produce the following SQL:

```sql
-- MySQL
ALTER TABLE `users` ADD `email` VARCHAR(255) AFTER `password`;
```

[Top](#pop-db)

### Drop Table

```php
$schema->drop('users');

echo $schema;
```

The above code would produce the following SQL:

```sql
-- MySQL
DROP TABLE `users`;
```

[Top](#pop-db)

### Execute Schema

You can execute the schema by using the `execute()` method within the schema builder object:

```php
$schema->execute();
```

[Top](#pop-db)

### Schema Builder API

In the above code samples, if you want to access the table object directly, you can like this:

```php
$createTable   = $schema->create('users');
$alterTable    = $schema->alter('users');
$truncateTable = $schema->truncate('users');
$renameTable   = $schema->rename('users');
$dropTable     = $schema->drop('users');
```

Here's a list of common methods available with which to build your schema:

* `$createTable->ifNotExists();` - Add a IF NOT EXISTS flag
* `$createTable->addColumn($name, $type, $size = null, $precision = null, array $attributes = []);` - Add a column
* `$createTable->increment($start = 1);` - Set an increment value
* `$createTable->defaultIs($value);` - Set the default value for the current column
* `$createTable->nullable();` - Make the current column nullable
* `$createTable->notNullable();` - Make the current column not nullable
* `$createTable->index($column, $name = null, $type = 'index');` - Create an index on the column
* `$createTable->unique($column, $name = null);` - Create a unique index on the column
* `$createTable->primary($column, $name = null);` - Create a primary index on the column

The following methods are shorthand methods for adding columns of various common types. Please note, if the
selected column type isn't supported by the current database adapter, the column type is normalized to
the closest type.

* `$createTable->integer($name, $size = null, array $attributes = []);`
* `$createTable->int($name, $size = null, array $attributes = []);`
* `$createTable->bigInt($name, $size = null, array $attributes = []);`
* `$createTable->mediumInt($name, $size = null, array $attributes = []);`
* `$createTable->smallInt($name, $size = null, array $attributes = []);`
* `$createTable->tinyInt($name, $size = null, array $attributes = []);`
* `$createTable->float($name, $size = null, $precision = null, array $attributes = []);`
* `$createTable->real($name, $size = null, $precision = null, array $attributes = [])`
* `$createTable->double($name, $size = null, $precision = null, array $attributes = []);`
* `$createTable->decimal($name, $size = null, $precision = null, array $attributes = []);`
* `$createTable->numeric($name, $size = null, $precision = null, array $attributes = []);`
* `$createTable->date($name, array $attributes = []);`
* `$createTable->time($name, array $attributes = []);`
* `$createTable->datetime($name, array $attributes = []);`
* `$createTable->timestamp($name, array $attributes = []);`
* `$createTable->year($name, $size = null, array $attributes = []);`
* `$createTable->text($name, array $attributes = []);`
* `$createTable->tinyText($name, array $attributes = []);`
* `$createTable->mediumText($name, array $attributes = []));`
* `$createTable->longText($name, array $attributes = []);`
* `$createTable->blob($name, array $attributes = []);`
* `$createTable->mediumBlob($name, array $attributes = []);`
* `$createTable->longBlob($name, array $attributes = []);`
* `$createTable->char($name, $size = null, array $attributes = []);`
* `$createTable->varchar($name, $size = null, array $attributes = []);`

The following methods are all related to the creation of foreign key constraints and their relationships:

* `$createTable->foreignKey(string $column, ?string $name = null)` - Create a foreign key on the column
* `$createTable->references($foreignTable);` - Create a reference to a table for the current foreign key constraint
* `$createTable->on($foreignColumn);` - Used in conjunction with `references()` to designate the foreign column
* `$createTable->onDelete($action = null)` - Set the ON DELETE parameter for a foreign key constraint

[Top](#pop-db)

Migrator
--------

Database migrations are scripts that assist in implementing new changes to the database, as well
rolling back any changes to a previous state. It works by storing a directory of migration class
files and keeping track of the current state, or the last one that was processed. From that, you
can write scripts to run the next migration state or rollback to the previous one. The state can
be stored locally in the migration folder, or can be stored in its own table in the database.
The [pop-kettle](https://github.com/popphp/pop-kettle) component has this functionality built in to assist with managing database
migrations for your application.

You can create a blank template migration class like this:

```php
use Pop\Db\Sql\Migrator;

Migrator::create('MyNewMigration', __DIR__ . 'migrations');
```

The code above will create a file that looks like `migrations/20170225100742_my_new_migration.php`
and it will contain a blank class template:

```php
<?php

use Pop\Db\Sql\Migration\AbstractMigration;

class MyNewMigration extends AbstractMigration
{

    public function up()
    {

    }

    public function down()
    {

    }

}
```

From there, you can write your forward migration steps in the `up()` method, or your rollback steps
in the `down()` method. Here's an example that creates a table when stepped forward, and drops
that table when rolled back:

```php
<?php

use Pop\Db\Sql\Migration\AbstractMigration;

class MyNewMigration extends AbstractMigration
{

    public function up()
    {
        $schema = $this->db->createSchema();
        $schema->create('users')
            ->int('id', 16)->increment()
            ->varchar('username', 255)
            ->varchar('password', 255)
            ->primary('id');

        $schema->execute();
    }

    public function down()
    {
        $schema = $this->db->createSchema();
        $schema->drop('users');
        $schema->execute();
    }

}
```

To step forward, you would call the migrator like this:

```php
use Pop\Db\Db;
use Pop\Db\Sql\Migrator;

$db = Pop\Db\Db::connect('mysql', [
    'database' => 'my_database',
    'username' => 'my_db_user',
    'password' => 'my_db_password',
    'host'     => 'mydb.server.com'
]);

$migrator = new Migrator($db, 'migrations');
$migrator->run();
```

The above code would have created the table `users` with the defined columns.
To roll back the migration, you would call the migrator like this:

```php
use Pop\Db\Db;
use Pop\Db\Sql\Migrator;

$db = Pop\Db\Db::connect('mysql', [
    'database' => 'my_database',
    'username' => 'my_db_user',
    'password' => 'my_db_password',
    'host'     => 'mydb.server.com'
]);

$migrator = new Migrator($db, 'migrations');
$migrator->rollback();
```

And the above code here would have dropped the table `users` from the database.

[Top](#pop-db)

Seeder
------

Similar to migrations, you can create a database seed class to assist with populating some
initial data into your database. This functionality is built into the `pop-kettle` component
as well.

```php
use Pop\Db\Sql\Seeder;

Seeder::create('MyFirstSeeder', __DIR__ . '/seeds');
```

The code above will create a file that looks like `seeds/20231105215257_my_first_seeder.php`
and it will contain a blank class template:

```php
<?php

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Sql\Seeder\AbstractSeeder;

class MyFirstSeeder extends AbstractSeeder
{

    public function run(AbstractAdapter $db): void
    {

    }

}
```

From there, you can write your seed queries steps in the `run()` method. You can interact
with both the schema builder and the query builder.

```php
<?php

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Sql\Seeder\AbstractSeeder;

class MyFirstSeeder extends AbstractSeeder
{

    public function run(AbstractAdapter $db): void
    {
        $schema = $db->createSchema();
        $schema->create('users')
            ->int('id', 16)->notNullable()->increment()
            ->varchar('username', 255)->notNullable()
            ->varchar('password', 255)->notNullable()
            ->varchar('email', 255)->nullable()
            ->primary('id');

        $db->query($schema);

        $sql = $db->createSql();
        $sql->insert('users')->values([
            'username' => 'testuser1',
            'password' => '12345678',
            'email'    => 'testuser1@test.com'
        ]);
        $db->query($sql);

        $sql->insert('users')->values([
            'username' => 'testuser2',
            'password' => '87654321',
            'email'    => 'testuser2@test.com'
        ]);
        $db->query($sql);

        $sql->insert('users')->values([
            'username' => 'testuser3',
            'password' => '74185296',
            'email'    => 'testuser3@test.com'
        ]);
        $db->query($sql);
    }

}
```

Alternatively, you can use a plain SQL file as well and the seeder will parse it and execute
the queries inside:

```sql
CREATE TABLE `users` (
  `id` INT(16) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO `users` (`id`, `username`, `password`, `email`) VALUES
(1, 'testuser1', '12345678', 'test1@test.com'),
(2, 'testuser2', '87654321', 'test2@test.com'),
(3, 'testuser3', '74185296', 'test3@test.com');
```

Either way, when you call the `run()` method on the seeder class, it will scan the folder for
either seeder classes or SQL files and execute them:

```php
$db = Db::mysqlConnect([
    'database' => 'DATABASE',
    'username' => 'DB_USER',
    'password' => 'DB_PASS'
]);

Seeder::run($db, __DIR__ . '/seeds');
```

[Top](#pop-db)

SQL Data
--------

You can use the SQL data class to output large sets of data to a valid a SQL file.

```php
use Pop\Db\Db;
use Pop\Db\Sql\Data;

$db = Db::mysqlConnect([
    'database' => 'DATABASE',
    'username' => 'DB_USER',
    'password' => 'DB_PASS',
]);

$users = [
    [
        'id'       => 1,
        'username' => 'testuser1',
        'password' => 'mypassword1',
        'email'    => 'testuser1@test.com'
    ],
    [
        'id'       => 2,
        'username' => 'testuser2',
        'password' => 'mypassword2',
        'email'    => 'testuser2@test.com'
    ],
    [
        'id'       => 3,
        'username' => 'testuser3',
        'password' => 'mypassword3',
        'email'    => 'testuser3@test.com'
    ]
];

$data = new Data($db, 'users');
$data->streamToFile($users, __DIR__ . '/seeds/users.sql');
```

The above example code would produce a `users.sql` file that contains:

```sql
INSERT INTO `users` (`id`, `username`, `password`, `email`) VALUES
(1, 'testuser1', 'mypassword1', 'testuser1@test.com');
INSERT INTO `users` (`id`, `username`, `password`, `email`) VALUES
(2, 'testuser2', 'mypassword2', 'testuser2@test.com');
INSERT INTO `users` (`id`, `username`, `password`, `email`) VALUES
(3, 'testuser3', 'mypassword3', 'testuser3@test.com');
```

If you have a larger set that you'd like divide out over fewer `INSERT` queries, you can set
the `divide` parameter:


```php
use Pop\Db\Db;
use Pop\Db\Sql\Data;

$db = Db::mysqlConnect([
    'database' => 'DATABASE',
    'username' => 'DB_USER',
    'password' => 'DB_PASS',
]);

$users = [
    [
        'id'       => 1,
        'username' => 'testuser1',
        'password' => 'mypassword1',
        'email'    => 'testuser1@test.com'
    ],
    // ... large array of data ...
    [
        'id'       => 18,
        'username' => 'testuser3',
        'password' => 'mypassword3',
        'email'    => 'testuser3@test.com'
    ]
];

$data = new Data($db, 'users', 10); // Set the divide to 10
$data->streamToFile($users, __DIR__ . '/seeds/users.sql');
```

which would produce:

```sql
INSERT INTO `users` (`id`, `username`, `password`, `email`) VALUES
(1, 'testuser1', 'mypassword1', 'testuser1@test.com'),
(2, 'testuser2', 'mypassword2', 'testuser2@test.com'),
(3, 'testuser3', 'mypassword3', 'testuser3@test.com'),
(4, 'testuser4', 'mypassword4', 'testuser4@test.com'),
(5, 'testuser5', 'mypassword5', 'testuser5@test.com'),
(6, 'testuser6', 'mypassword6', 'testuser6@test.com'),
(7, 'testuser7', 'mypassword7', 'testuser7@test.com'),
(8, 'testuser8', 'mypassword8', 'testuser8@test.com'),
(9, 'testuser9', 'mypassword9', 'testuser9@test.com'),
(10, 'testuser10', 'mypassword10', 'testuser10@test.com');
INSERT INTO `users` (`id`, `username`, `password`, `email`) VALUES
(11, 'testuser11', 'mypassword11', 'testuser11@test.com'),
(12, 'testuser12', 'mypassword12', 'testuser12@test.com'),
(13, 'testuser13', 'mypassword13', 'testuser13@test.com'),
(14, 'testuser14', 'mypassword14', 'testuser14@test.com'),
(15, 'testuser15', 'mypassword15', 'testuser15@test.com'),
(16, 'testuser16', 'mypassword16', 'testuser16@test.com'),
(17, 'testuser17', 'mypassword17', 'testuser17@test.com'),
(18, 'testuser18', 'mypassword18', 'testuser18@test.com');
```

[Top](#pop-db)

Profiler
--------

The profiler object works in conjunction with the `pop-debug` component to set up a
query listener to monitor performance and record any potential issues.

```php
use Pop\Db\Db;
use Pop\Db\Record;
use Pop\Debug\Debugger;
use Pop\Debug\Storage\File;
use Pop\Db\Adapter\Profiler\Profiler;

$db = Db::mysqlConnect([
    'database' => 'DATABASE',
    'username' => 'DB_USER',
    'password' => 'DB_PASS',
]);

class Users extends Record {}

Record::setDb($db);

// Register the debugger and query handler with the DB adapter
$debugger = new Debugger(new File(__DIR__ . '/log'));
$db->listen('Pop\Debug\Handler\QueryHandler', null, new Profiler($debugger));

// Save a user to the database
$user = new Users([
    'username' => 'admin',
    'password' => 'password',
    'email'    => 'admin@test.com'
]);

$user->save();
```

With the debugger and query handler registered with the database profiler, any queries
that are executed will get automatically logged with the debugger. The debugger log output
from the above example might look like this:

```text
Start:			1699246221.25475
Finish:			0.00000
Elapsed:		0.00997 seconds

Queries:
--------
INSERT INTO `users` (`username`, `password`, `email`) VALUES (?, ?, ?) [0.00674]
Start:			1699246221.25796
Finish:			1699246221.26470
Params:
	username => admin
	password => password
	email => admin@test.com
```

If you'd like more control over when the debugger fires, you can manually save it as well:

```php
// Register the query handler with the DB adapter
$queryHandler = $db->listen('Pop\Debug\Handler\QueryHandler');

$debugger = new Debugger();
$debugger->addHandler($queryHandler);
$debugger->setStorage(new File(__DIR__ . '/log'));

// Save a user to the database
$user = new Users([
    'username' => 'admin',
    'password' => 'password',
    'email'    => 'admin@test.com'
]);

$user->save();
$debugger->save();
```

In the above example, the query handler is returned from the `listen()` method call, which 
in turn can be registered with the stand-alone debugger. Once the final query runs on the user
`save()` method, you can trigger the debugger `save()` method. 

[Top](#pop-db)
