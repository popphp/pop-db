pop-db
======

[![Build Status](https://github.com/popphp/pop-db/workflows/phpunit/badge.svg)](https://github.com/popphp/pop-db/actions)
[![Coverage Status](https://cc.popphp.org/coverage.php?comp=pop-db)](https://cc.popphp.org/pop-db/)

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
    - [Auth Record](#auth-record)
    - [Table Gateway](#table-gateway)
    - [Data Model](#data-model)
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
    - [Subqueries](#subqueries)
    - [JSON Column Querying](#json-column-querying)
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
  - SQLite
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

`pop-db`is a component of the [Pop PHP Framework](https://www.popphp.org/).

[Top](#pop-db)

Install
-------

Install `pop-db` using Composer.

    composer require popphp/pop-db

Or, require it in your composer.json file

    "require": {
        "popphp/pop-db" : "^7.0.0"
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
$db = Db::sqliteConnect([
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
    'type'     => 'mysql',
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

#### Mass-assignment protection

By default, a table class will accept any array of column values passed to its constructor and set
every key as a column value, with no restrictions. If that array comes from untrusted input (e.g.
`new Users($request->all())`), an attacker can set any column just by adding an extra key to the
request body. To guard against this, a table class can declare `$fillable` or `$guarded`, following
the same naming/precedence convention as Laravel Eloquent:

```php
class Users extends Record
{
    protected ?string $table   = 'users';

    // Allowlist: only these columns can be mass-assigned
    protected array   $fillable = ['username', 'email'];
}
```

```php
class Users extends Record
{
    protected ?string $table  = 'users';

    // Denylist: everything except these columns can be mass-assigned
    protected array   $guarded = ['is_admin', 'role'];
}
```

- If `$fillable` is non-empty, it takes full precedence - only the listed columns are mass-assignable,
  and `$guarded` is ignored entirely.
- Otherwise, if `$guarded` is non-empty, every column *except* the listed ones is mass-assignable.
- If neither is declared (both stay as the default empty array), mass-assignment is unrestricted -
  today's existing behavior.

The filtering is enforced by `fill()`, not `setColumns()`. The constructor now routes array-like input
through `fill()` when a table class is instantiated with column data:

```php
$user = new Users($request->all()); // filtered through $fillable/$guarded
$user->save();
```

`fill()` is also callable directly, which is the recommended way to mass-assign untrusted data onto
an *existing* record that has already been fetched from the database:

```php
$user = Users::findById(1);
$user->fill($request->all()); // filtered through $fillable/$guarded
$user->save();                // updates the existing row
```

Note that `fill()` *replaces* the record's current column set with the filtered input rather than
merging into it. On a fetched record that is harmless for the update itself - the primary key value
is retained separately, only the filled columns end up in the `UPDATE`, and `save()` re-fetches the
full row afterwards. To create a *new* record from untrusted input, pass the array to the constructor
(shown above) rather than calling `fill()` on an empty instance - a record built with the no-argument
constructor is not flagged as new, so `save()` would attempt an update instead of an insert.

You can also check whether an individual column is mass-assignable:

```php
$user->isFillable('role'); // false, if 'role' is guarded or not in $fillable
```

**Scope note:** mass-assignment protection only applies to `fill()` and the constructor. It does not
guard single-property assignment (`$user->role = 'admin'` still works regardless of `$guarded`), nor
does it apply to `Gateway\Table`'s raw `insert()`/`update()` methods, nor to row hydration from the
database (`findById()`, `findOne()`, `findAll()`, etc. always populate every real column, since that
data is trusted and DB-sourced, not user-supplied). The same applies to `replicate()`/`copy()` and to
the new-record path of `findOneOrCreate()`/`findByOrCreate()` - all three build the new record from data
this codebase already fetched or was explicitly given (a copy of an existing row, or the very search
criteria you just called them with), not from raw external input, so `$fillable`/`$guarded` don't apply there
either.

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

Anywhere `$columns` is accepted (`findOne()`, `findBy()`, `findOneOrCreate()`, `findByOrCreate()`, etc.), a
`Sql\PredicateSet` built with `predicate()` works the same as a plain array. This is useful when the search
criteria need more than simple equality (e.g. a `LIKE`, a range, or an `OR`). When `findOneOrCreate()`/
`findByOrCreate()` don't find a match, the new record is built from the `PredicateSet`'s own equality
predicates - so a `PredicateSet` used for find-or-create should stick to `equalTo()` conditions, the same way
the array form only makes sense with plain `column => value` pairs:

```php
$criteria = Users::predicate()->equalTo('username', 'testuser')->equalTo('email', 'testuser@test.com');

$user = Users::findOneOrCreate($criteria); // creates ['username' => 'testuser', 'email' => 'testuser@test.com'] if not found
```

By default, `findLatest()` will use the primary key, like `id`. However, you can pass it another field
to sort by:

```php
// Search for the latest single user record by 'last_login'
$user = Users::findLatest('last_login');
```

#### Find API

These are available static methods to find a record or records in the database table. Every `$columns`
parameter below accepts either a plain array or a `Sql\PredicateSet` (see above):

- `findById($id, array $options = null, bool $asArray = false)`
- `findOne(array|PredicateSet $columns = null, array $options = null, bool $asArray = false)`
- `findOneOrCreate(array|PredicateSet $columns = null, array $options = null, bool $asArray = false)`
- `findLatest($by = null, array|PredicateSet $columns = null, array $options = null, bool $asArray = false)`
- `findBy(array|PredicateSet $columns = null, array $options = null, bool $asArray = false)`
- `findByOrCreate(array|PredicateSet $columns = null, array $options = null, bool $asArray = false)`
- `findIn($key, array $values, array|PredicateSet $columns = null, array $options = null, bool $asArray = false)`
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

These build structured shorthand internally, so none of them emit a deprecation notice.
`findWhereBetween()`/`findWhereNotBetween()` accept either the packed string form
(`'(1, 5)'`) or an unambiguous 2-element array (`[1, 5]`).

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
$user->reset('attempts'); // Reset column to null and save
$user->reset('attempts', 0); // Reset column to a given value (e.g. 0 or '') and save
```

```php
// Make a new copy of the user record in the database
// The $replace parameter can be an array of new, overriding column values
$newUser = $user->copy($replace);
```

#### Lifecycle hooks

`Pop\Db\Record` exposes eight protected, empty, overridable hook methods that a table class can
implement to run code around a single-record `save()`/`delete()`:

- `beforeSave()` / `afterSave()` - wrap the whole `save()` call, for both inserts and updates
- `beforeInsert()` / `afterInsert()` - fire only when the record is new (an INSERT)
- `beforeUpdate()` / `afterUpdate()` - fire only when the record already exists (an UPDATE)
- `beforeDelete()` / `afterDelete()` - wrap the whole `delete()` call

They're no-ops by default, so declaring none of them is zero behavior change. Override one in a
table class to hook in:

```php
class Users extends Pop\Db\Record
{
    protected function beforeSave(): void
    {
        $this->updated_at = date('Y-m-d H:i:s');
    }

    protected function afterDelete(): void
    {
        Logger::info('User deleted', ['id' => $this->id]);
    }
}
```

On `save()`, the firing order is `beforeSave()` → (`beforeInsert()` or `beforeUpdate()`) →
the actual INSERT/UPDATE → (`afterInsert()` or `afterUpdate()`) → `afterSave()`. `afterUpdate()`
fires after the record has been re-fetched from the database, so it sees the row's current
persisted state. On `delete()`, the order is `beforeDelete()` → the actual DELETE → `afterDelete()`,
and `afterDelete()` still has access to the deleted record's own column values (e.g. `$this->id`)
even though the record's in-memory state is cleared immediately afterward.

These hooks only fire on the single-record path - bulk operations (`$user->save($rows)`,
`$user->delete($columns)`) do not trigger any of them.

`increment()`, `decrement()`, `reset()`, `replicate()` and `copy()` all internally call `save()`, so a
`beforeSave()`/`afterSave()` override that itself calls any of those methods will recurse.

A hook can abort the operation by throwing: the exception propagates out of `save()`/`delete()`
to the caller like any other failure in those methods (triggering a transaction rollback if one is
active). Note that an `after*` hook throwing cannot retroactively undo the INSERT/UPDATE/DELETE
statement that already ran if it wasn't inside an active transaction - the throw still propagates
to the caller either way, but the database change stands.

#### Dirty records

If a record has been modified, the changes are stored and you can get them like this:

```php
$user->username = 'newusername';
$user->email    = 'newemail@test.com';

if ($user->isDirty()) {
    print_r($user->getDirty());
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
use Pop\Db\Record\Encoded;

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
use Pop\Db\Record\Encoded;

class Users extends Encoded
{

    protected array  $hashFields    = ['password'];
    protected string $hashAlgorithm = PASSWORD_BCRYPT;
    protected array  $hashOptions   = ['cost' => 12];
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

If `$hashOptions` is later tightened (e.g. bumping bcrypt's `cost`), `verify()` also records
whether the stored hash was made with the old, weaker settings. Check `needsRehash()` right
after a successful `verify()`, and use `rehash()` - with the plaintext value you just verified -
to transparently upgrade it in place:

```php
if ($user->verify('password', $attemptedPassword)) {
    if ($user->needsRehash()) {
        $user->rehash('password', $attemptedPassword); // re-hashes with current $hashOptions and saves
    }
    // proceed as authenticated
}
```

#### 2-Way Encryption

An even more advanced example would be using an 2-way encrypted field, which uses the
Open SSL library extension. It requires a few more table properties to be configured:

- `$cipherMethod` - available cipher method string from the `Pop\Crypt\Encryption\Encrypter` class
- `$key` - base64 encoded value generated from the `Pop\Crypt\Encryption\Encrypter` class
- `$previousKeys` - base64 encoded values generated from the `Pop\Crypt\Encryption\Encrypter` class 
  of previous keys to provide graceful key rotation

The encryption properties can be stored within the class directly:

```php
use Pop\Db\Record\Encoded;

class Users extends Encoded
{
    protected array   $encryptedFields = ['sensitive_data_field'];
    protected ?string $cipherMethod    = 'aes-256-cbc';
    protected ?string $key             = 'BASE64_ENCODED_KEY';
    protected array   $previousKeys    = ['BASE64_OLD_ENCODED_KEY1', 'BASE64_OLD_ENCODED_KEY2'];
}
```

Or, they can be autoloaded from the application's `.env` file as the following values:

- `APP_CIPHER_METHOD` - a string of the cipher to be used, e.g. `aes-256-cbc`
- `APP_KEY` - a base-64 encoded string of the current active key
- `APP_PREVIOUS_KEYS` - a comma-separated list of base-64 encoded strings of the previous keys

[Top](#pop-db)

### Auth Record

The `Pop\Db\Record\Auth` class extends `Pop\Db\Record\Encoded` and provides a ready-made
username/password authentication flow on top of a user table, including failed-attempt
lockout and optional MFA (multi-factor authentication) code issuance/verification.

```php
use Pop\Db\Record\Auth;

class Users extends Auth
{
    // No $hashFields needed - Auth::__construct() always adds $passwordField to it for you
}
```

The underlying table needs, at minimum, the fields referenced by `$usernameField` (default
`username`) and `$passwordField` (default `password`), plus `$attemptsField` (default `attempts`,
should default to `0`). If you plan to use MFA, it also needs the two nullable fields configured
in `$mfaConfig` (default `mfa_code`/`mfa_timestamp`).

#### Authenticating

```php
$user = new Users();

// $mfa = false: authenticate outright, no MFA step
if ($user->authenticate($username, $attemptedPassword, false)) {
    // Logged in
} else {
    echo $user->getAuthFailureMessage();
}
```

```php
// $mfa = true (the default): on success, a fresh MFA code + expiration are generated,
// saved to the user record, and the record itself is returned so the app can send the
// code however it likes (email, SMS, etc.)
$result = $user->authenticate($username, $attemptedPassword);

if ($result !== false) {
    // $result is the user record - send $result->mfa_code to the user
} else {
    echo $user->getAuthFailureMessage();
}
```

On a failed attempt (bad password, or a valid password submitted after the attempts limit has
been exceeded), `$attemptsField` is incremented and saved automatically. `getAuthFailure()`
returns the specific reason as a string constant, and `getAuthFailureMessage()` returns a
human-readable message for it:

- `Auth::USER_DOES_NOT_EXIST`
- `Auth::INVALID_CREDENTIALS`
- `Auth::ATTEMPTS_EXCEEDED`
- `Auth::INVALID_MFA_CODE`
- `Auth::MFA_CODE_EXPIRED`

Once `$attemptsField` reaches `$attemptsLimit` (default `3`), the account is locked out for
good - even a correct password will keep failing with `ATTEMPTS_EXCEEDED`. This is deliberate:
there's no automatic, time-based unlock, so unlocking is left to an application admin explicitly
calling `resetAttempts()`.

`$attemptsLimit` can be read/set at runtime with `getAttemptsLimit()`/`setAttemptsLimit()` (both
fluent), or overridden for a single `authenticate()` call via its optional fourth argument:

```php
// Give this login attempt a stricter limit than the class default
$user->authenticate($username, $attemptedPassword, false, 1);
```

Note that passing `$attemptsLimit` to `authenticate()` isn't a one-shot override - it calls
`setAttemptsLimit()` internally, so the new value sticks on the instance for any subsequent calls
too, until changed again.

Setting `$attemptsLimit` to `0` (or calling `setAttemptsLimit(0)`) disables attempts enforcement
entirely - `attemptsExceeded()` always returns `false` and `hasAttemptsLimit()` reports `false`,
regardless of how high `$attemptsField` climbs.

If a successful login's stored password hash was made under older `$hashOptions` (e.g. a bcrypt
`cost` that's since been raised), it's transparently rehashed and saved on the way in - see
[1-Way Hashing](#1-way-hashing) for the `needsRehash()`/`rehash()` mechanics this relies on.

#### MFA verification

Once the app has delivered the MFA code, fetch the user record and verify the code the user
submits back:

```php
$user = Users::findOne(['username' => $username]);

if ($user->authenticateMfa($attemptedCode)) {
    // MFA passed - the stored code is cleared, so it cannot be reused
} else {
    echo $user->getAuthFailureMessage(); // INVALID_MFA_CODE, MFA_CODE_EXPIRED, etc.
}
```

Wrong or expired MFA guesses increment and are gated by the same `$attemptsField`/`$attemptsLimit`
as login attempts, so a locked-out account is also locked out of guessing MFA codes.

#### Resending/regenerating an MFA code

`authenticate()` issues the initial MFA code by calling `generateMfaCode()` internally, but it's
also public, so a "resend code" affordance can call it directly on an already-loaded user record
without repeating the password check:

```php
$user = Users::findOne(['username' => $username]);
$user->generateMfaCode();
// send $user->mfa_code to the user again
```

`generateMfaCode()` is fluent and no-ops (leaving any existing code/timestamp untouched) in two
cases:

- the record isn't a loaded user (`userExists()` is false)
- attempts have already been exceeded (`attemptsExceeded()` is true)

The second case is deliberate: a locked-out account can't be handed a fresh, usable code via
resend, since MFA verification checks `attemptsExceeded()` before it ever looks at the code -
resetting attempts just to make a resent code work would turn "resend" into an unlimited-guessing
loophole. The only way out of lockout is an explicit `resetAttempts()` call.

`$mfaConfig` can be read/set at runtime with `getMfaConfig()`/`setMfaConfig()` (both fluent).
`setMfaConfig()` merges into the existing config, so you only need to pass the keys you want to
change - anything you omit keeps its current value. Only the five keys already present in
`$mfaConfig` (shown below) are ever honored; any other key passed in is silently ignored, since
the class only ever reads from those five:

```php
// Longer, alphanumeric codes, without having to repeat the other mfaConfig keys
$user->setMfaConfig(['length' => 8, 'alphanumeric' => true]);
```

#### Configuration

All of the following are plain property overrides on your table class:

```php
class Users extends Auth
{
    protected string $usernameField = 'username';
    protected string $passwordField = 'password';
    protected string $attemptsField = 'attempts';
    protected int    $attemptsLimit = 3;

    protected array $mfaConfig = [
        'length'              => 6,               // Code length
        'expires'             => 300,              // Seconds
        'alphanumeric'        => false,            // Numeric by default, can be alphanumeric
        'mfa_code_field'      => 'mfa_code',       // varchar database column, nullable
        'mfa_timestamp_field' => 'mfa_timestamp',  // integer database column, nullable
    ];
}
```

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

### Data Model

Going one level further, the abstract class `Pop\Db\Model\AbstractDataModel` is also available, which provides
a tightly integrated API for some common interactions with a database and its records. The basic requirements
are that there is a model class that extends the abstract data model and a subsequent related table class
(see the [Table Class](#table-class) section above for more info.) In the example below, the classes
`MyApp\Model\User` and `MyApp\Table\Users` are created, and by that naming convention, they are linked together.

```php
<?php

namespace MyApp\Table;

use Pop\Db\Record;

class Users extends Record
{

}
```

```php
<?php

namespace MyApp\Model;

use Pop\Db\Model\AbstractDataModel;

class User extends AbstractDataModel
{

}
```

The available API in the data model object is:

Each method that reads or writes a record takes a `$toArray` parameter (`bool|array`, default `false`). Leave it
`false` to get back `Record`/`Collection` objects, pass `true` to get plain arrays instead, or pass an array of
column names to get plain arrays limited to just those columns.

**Static Methods**

- `fetchAll(?string $sort = null, mixed $limit = null, mixed $page = null, bool|array $toArray = false): array|Collection`
- `fetch(mixed $id, bool $toArray = false): array|Record`
- `createNew(array $data, bool $toArray = false): array|Record`
- `filterBy(mixed $filters = null, mixed $select = null): static`

**Instance Methods**

- `getAll(?string $sort = null, mixed $limit = null, mixed $page = null, bool|array $toArray = false): array|Collection`
- `getById(mixed $id, bool $toArray = false): array|Record`
- `getOne(array $columns, bool $toArray = false): array|Record`
- `create(array $data, bool $toArray = false): array|Record`
- `copy(mixed $id, array $replace = [], bool $toArray = false): array|Record`
- `update(mixed $id, array $data, bool $toArray = false): array|Record`
- `replace(mixed $id, array $data, bool $toArray = false): array|Record`
- `delete(mixed $id): int`
- `remove(array $ids): int`
- `count(): int`
- `describe(bool $native = false, bool $full = false, bool $withAlias = false): array`
- `hasRequirements(): bool`
- `validate(array $data): bool|array`
- `filter(mixed $filters = null, mixed $select = null, ?array $options = null): AbstractDataModel`
- `select(mixed $select = null, ?array $options = null): AbstractDataModel`

`getOne()` fetches a single record by an arbitrary column/value array (rather than by primary key), and `copy()`
duplicates an existing record by ID, optionally overriding some columns via `$replace`.

**Create new**

```php
use MyApp\Model\User;

$user = User::createNew($userData);
```

If the model class has a `$requirements` property set (an array of required column names), `create()` and
`replace()` will validate `$data` against it before writing to the database. When a requirement is missing, an
array in the shape `['errors' => ['column' => "The column 'column' is required."]]` is returned instead of a
`Record`/array.

**Update**

```php
use MyApp\Model\User;

$userModel = new User();
$user = $userModel->update(1, $userData);
```

The `update()` method acts like a `PATCH` call and `replace()` acts like a `PUT` call and will replace and reset all model data.

**Delete**

```php
use MyApp\Model\User;

$userModel = new User();
$userModel->delete(1);
$userModel->remove([2, 3, 4]);
```

**Fetch**

```php
use MyApp\Model\User;

$users = User::fetchAll();
$user  = User::fetch(1);
```

**Filter and sort**

```php
use MyApp\Model\User;

$users = User::filterBy('username LIKE myuser%')->getAll('-id', '10', 2);
```

The above call filters the search by the filter string and sorts by `ID DESC` (`-id`). Also, it sets the limit to `10`
and starts the page offset on the second page.

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

The keys are case-sensitive. Any key outside of that list (plus `columns`, which the
relationship methods accept) is ignored by the query builder and triggers an
`E_USER_NOTICE` naming it, so a typo such as `limitt`, `Limit` or `orderBy` doesn't
silently return unfiltered results.

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

The field or fields by which to `order` the results. A direction may be appended
(`'id ASC'`, `'id DESC'`, `'id RAND()'`); a bare field with no direction defaults to
`ASC`. A leading `-` is shorthand for descending, so `'-id'` is equivalent to `'id DESC'`
— the same convention `Pop\Db\Model\AbstractDataModel` uses for its sort parameter.

##### Group

The field or fields by which to `group` the results

##### Join

The `join` option allows you to define multiple tables to use in a JOIN query.

**Basic Options Example**

```php
$users = Users::findBy(['logins' => 0], [
    'select' => ['id', 'username'],
    'order'  => ['id DESC'],
    'offset' => 10,
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

#### Structured Shorthand (Recommended)

As of v7, shorthand conditions can also be expressed with an explicit operator, avoiding any ambiguity between
column names and operator suffixes:

```php
$users = Users::findBy([
    'age'        => ['>=', 18],
    'status'     => ['!=', 'inactive'],
    'name'       => ['LIKE', '%smith%'],
    'created_at' => ['BETWEEN', '2024-01-01', '2024-12-31'],
    'role'       => ['IN', ['admin', 'editor']],
    'deleted_at' => ['IS NULL'],
]);
```

Plain equality (`'age' => 18`) is unchanged. `OR`/`AND` grouping is supported via reserved keys:

```php
$users = Users::findBy([
    'status' => 'active',
    'OR' => [
        ['role' => 'admin'],
        ['age'  => ['>=', 65]],
    ],
]);
// WHERE status = 'active' AND (role = 'admin' OR age >= 65)
```

An operator given the wrong number of values throws a `Pop\Db\Sql\Parser\Exception` immediately rather than
silently rendering something unintended — including `IN`/`NOT IN` given an empty array.

The older shorthand shapes shown above still work but are **deprecated** and will be removed in the next major
version — they trigger an `E_USER_DEPRECATED` notice. That covers the suffixed keys (`'age>=' => 18`,
`'%username' => 'test'`, `'username-' => null`, …), the array-valued IN form (`'id' => [2, 3]`) and the packed
BETWEEN form (`'id' => '(1, 5)'`). New code should use the structured format.

Plain equality (`'id' => 1`) and a bare key with a `null` value (`'id' => null`, meaning `id IS NULL`) are **not**
deprecated — they are first-class structured shorthand and can also be used inside `OR`/`AND` groups.

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
    - 1:many relationship where a foreign key in many child objects is a primary key in the parent object
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
    public function role(?array $options = null, bool $eager = false)
    {
        return $this->hasOneOf('Roles', 'role_id', $options, $eager);
    }

    // Define the 1:1 relationship of the info record this user owns
    public function info(?array $options = null, bool $eager = false)
    {
        return $this->hasOne('Info', 'user_id', $options, $eager);
    }

    // Define the 1:many relationship to all the orders this user owns
    public function orders(?array $options = null, bool $eager = false)
    {
        return $this->hasMany('Orders', 'user_id', $options, $eager);
    }

}
```

**Relationship method signature**

A relationship method must declare the two parameters shown above - `?array $options = null` and
`bool $eager = false` - and pass them straight through to the underlying `hasOneOf()`/`hasOne()`/
`hasMany()`/`belongsTo()` call. They are what `with()` uses to hand the method its per-relationship
options and to ask it for the relationship object rather than the already-loaded data. A relationship
method that omits them still lazy-loads correctly, but eager-loading it through `with()` fails at
runtime with `Call to undefined method ...::getEagerRelationships()`, because the method returns the
loaded record/collection instead of the relationship object `with()` needs.

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
    public function user(?array $options = null, bool $eager = false)
    {
        return $this->belongsTo('Users', 'user_id', $options, $eager);
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
    public function user(?array $options = null, bool $eager = false)
    {
        return $this->belongsTo('Users', 'user_id', $options, $eager);
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
$orders = $user->orders();

foreach ($orders as $order) {
    echo 'Order Total: $' . $order->order_total . PHP_EOL;
}
```

Chaining `->latest()` or `->oldest()` before a `hasMany()` relationship method collapses the result down to a
single record - the most (or least) recent one, ordered by a column you choose (defaults to `id`):

```php
$user = Users::findById(1);

$newestOrder = $user->latest()->orders();       // single Orders record, ordered by id DESC
$oldestOrder = $user->oldest('order_date')->orders(); // single Orders record, ordered by order_date ASC

echo 'Most recent order total: $' . $newestOrder->order_total;
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
$user = Users::with('orders')->getById(1);
foreach ($user->orders as $order) {
    echo 'Order Total: $' . $order->order_total . PHP_EOL;
}
```

Multiple relationships can be passed as well:

```php
$user = Users::with(['role', 'info', 'orders'])->getById(1);
```

And nested relationships are supported too. Assume there is a `Posts` class and a `Comments` class.
Also, let's assume a user object owns posts and a posts object owns comments, and the proper relationships
are set up in each table class. Then this call would be valid:

```php
$user = Users::with('posts.comments')->getById(1);
```

And would give you a user object with all of the user's `posts` and each of those post objects would have
their `comments` attached as well.

More than one nested relationship can hang off the same parent relationship. Assume the `Posts` class also
owns tags, then this call would be valid:

```php
$user = Users::with(['posts.comments', 'posts.tags'])->getById(1);
```

The `posts` relationship is only resolved once, and each of those post objects gets both its `comments` and
its `tags` attached. The nesting isn't limited to one level either — `with('posts.comments.author')` walks as
deep as the relationships are defined.

**Empty relationships**

When the records are fetched with `getOne()` or `getBy()`, the relationships are resolved in a single batched
query per relationship. If a relationship has no matching records, it still resolves — to a value that matches
the shape of the relationship, so the calling code doesn't have to special-case it:

```php
$user = Users::with(['info', 'orders'])->getOne(['id' => 1]);

var_dump($user->info);          // NULL -- a 1:1 relationship with no match
echo count($user->orders);      // 0    -- a 1:many relationship is an empty collection
```

A 1:1 relationship (`hasOne`, `hasOneOf` or `belongsTo`) with no match resolves to `null`, and a 1:many
relationship (`hasMany`) with no match resolves to an empty collection. Note that both of these previously
resolved to an empty `array` instead, so any code that checked an unmatched relationship from `getOne()` or
`getBy()` with `is_array()`, or passed it to `count()`, will need to be updated.

**Composite (multi-column) keys**

`$foreignKey` isn't limited to a single column name — it can also be given as an array of column names to
describe a composite key relationship, matched positionally against a primary key elsewhere, so order matters.
For `belongsTo()` (shown below), the array is paired against the *target* table's own declared primary key
columns: the first foreign key column matches its first primary key column, the second matches its second, and
so on.

```php
class Orders extends Pop\Db\Record
{
    /**
     * Mock Schema
     *    - id
     *    - user_id (FK to users.id)
     *    - org_id  (FK to users.org_id)
     *    - order_date
     *    - order_total
     *    - products
     */

    // Define the parent relationship up to the user that owns this order record,
    // matched on both `user_id` and `org_id`
    public function user(?array $options = null, bool $eager = false)
    {
        return $this->belongsTo('Users', ['user_id', 'org_id'], $options, $eager);
    }

}
```

This works the same way for `hasOne()`, `hasOneOf()` and `hasMany()` — pass an array of foreign key columns
instead of a single string. Which table's primary key it's paired against depends on the direction of the
relationship: for `hasOneOf()` and `belongsTo()`, the array is matched positionally against the *target*
table's own declared primary key columns; for `hasOne()` and `hasMany()`, it's matched positionally against the
*declaring* record's own primary key columns instead. Both lazy-loading and eager-loading via `with()`
(including nested `with()` calls) support composite keys.

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
* `$sql->innerJoin($foreignTable, array $columns);` -  Inner join
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

### Subqueries

The `IN`/`NOT IN` predicates and the scalar comparison predicates (`=`, `!=`, `>`, `>=`, `<`, `<=`) can take a
`Sql\Select` object as their value instead of a plain array or scalar, producing a `col IN (SELECT ...)` or
`col = (SELECT ...)` style subquery. There is also a dedicated `exists()`/`notExists()` API for standalone
`EXISTS (SELECT ...)` predicates that aren't tied to a column at all.

```php
$subquery = $db->createSql()->select('user_id')->from('orders');
$subquery->where->greaterThanOrEqualTo('total', 100);

$sql->select()
    ->from('users')
    ->where->in('id', $subquery);

echo $sql;
```

```sql
-- MySQL
SELECT * FROM `users` WHERE (`id` IN (SELECT `user_id` FROM `orders` WHERE (`total` >= 100)))
```

`notIn()` works the same way, producing `NOT IN (SELECT ...)`. The scalar comparison predicates accept a
`Select` the same way:

```php
$subquery = $db->createSql()->select('MAX(total)')->from('orders');

$sql->select()
    ->from('users')
    ->where->equalTo('total', $subquery);
```

```sql
-- MySQL
SELECT * FROM `users` WHERE (`total` = (SELECT MAX(total) FROM `orders`))
```

`exists()` and `notExists()` take a `Select` directly (there's no column argument, since `EXISTS` tests for the
presence of rows, not a value):

```php
$subquery = $db->createSql()->select('id')->from('orders');
$subquery->where->equalTo('user_id', 5);

$sql->select()->from('users')->where->exists($subquery);
```

```sql
-- MySQL
SELECT * FROM `users` WHERE (EXISTS (SELECT `id` FROM `orders` WHERE (`user_id` = 5)))
```

The shorthand array syntax supports the same forms: `['col' => ['IN', $select]]`, `['col' => ['NOT IN', $select]]`,
`['col' => ['=', $select]]`, and a reserved `'EXISTS'`/`'NOT EXISTS'` top-level key whose value is the `Select`:

```php
Users::findBy(['id' => ['IN', $subquery]]);
Users::findBy(['EXISTS' => $subquery]);
```

**Constraints:**

* A subquery's own conditions must be built with literal values, not bound placeholders — e.g.
  `$subquery->where->equalTo('total', 100)` rather than `'total = :total'`. Literal values are safe because they
  are escaped through the adapter's `quote()`/`escape()` methods, but they are not part of the outer query's
  prepared-statement parameter binding, since a subquery is rendered inline as a string before the outer query is
  prepared.
* `'EXISTS'` and `'NOT EXISTS'` are reserved top-level shorthand keys, the same way `'OR'` and `'AND'` are. A
  column literally named `EXISTS` cannot be addressed via the shorthand array syntax.
* A `Select` used as a subquery value cannot have an alias set on it (via `setAlias()`/`asAlias()`). An alias
  causes a `Select` to render itself as `(SELECT ...) AS alias`, which is only valid for a FROM/JOIN subquery;
  passing an aliased `Select` to a predicate throws an exception.

**BC note (v7):** to accept a `Select` as a value, `PredicateSet::equalTo()`, `notEqualTo()`, `greaterThan()`,
`greaterThanOrEqualTo()`, `lessThan()` and `lessThanOrEqualTo()` widened their `$value` parameter from `string`
to `mixed`. Because PHP enforces parameter contravariance, any downstream subclass of `PredicateSet` (or of
`Sql\Where`/`Sql\Having`) that overrides one of these methods with the old `string $value` signature will now
fail with an incompatible-signature error and must be updated to `mixed $value`.

[Top](#pop-db)

### JSON Column Querying

Columns that store JSON documents can be queried by path with `jsonExtract()`, and filtered with the
`jsonEqualTo()`/`jsonNotEqualTo()`/`jsonContains()` predicates.

`jsonExtract($column, $path)` returns a dialect-specific expression object that can be used as a SELECT column
(optionally aliased) or passed to `orderBy()`/`groupBy()` (either on its own or as an element of their array
form):

```php
$sql->select(['id', 'extracted_name' => $sql->jsonExtract('data', '$.name')])
    ->from('users');

echo $sql;
```

```sql
-- MySQL
SELECT `id`, JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.name')) AS `extracted_name` FROM `users`
```

```php
$sql->select()->from('users')
    ->orderBy($sql->jsonExtract('data', '$.name'));

$sql->select()->from('users')
    ->groupBy([$sql->jsonExtract('data', '$.name'), 'id']);
```

`jsonEqualTo()`/`jsonNotEqualTo()` compare the value extracted at a path against a scalar:

```php
$sql->select()
    ->from('users')
    ->where->jsonEqualTo('data', '$.role', 'admin');

echo $sql;
```

```sql
-- MySQL
SELECT * FROM `users` WHERE (JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.role')) = 'admin')
```

`jsonContains()` tests whether the JSON array/value at a path contains a given scalar candidate. It is only
supported on MySQL and PostgreSQL — neither SQLite nor SQL Server exposes a native JSON containment
operator/function, so `jsonContains()` throws an exception on those adapters:

```php
$sql->select()
    ->from('users')
    ->where->jsonContains('data', '$.roles', 'admin');

echo $sql;
```

```sql
-- MySQL
SELECT * FROM `users` WHERE (JSON_CONTAINS(`data`, '"admin"', '$.roles'))
-- PostgreSQL
SELECT * FROM "users" WHERE (("data" #> '{roles}') @> '"admin"'::jsonb)
```

The shorthand array syntax supports JSON path access via a `'column->$.path'` key, routed to
`jsonEqualTo()`/`jsonNotEqualTo()`/`jsonContains()` through the `=`/`!=`/`CONTAINS` operators (a bare value with
no operator tuple is treated as `=`, the same as any other shorthand column):

```php
Users::findBy(['data->$.role' => 'admin']);                    // jsonEqualTo()
Users::findBy(['data->$.role' => ['=', 'admin']]);              // jsonEqualTo()
Users::findBy(['data->$.role' => ['!=', 'admin']]);             // jsonNotEqualTo()
Users::findBy(['data->$.roles' => ['CONTAINS', 'admin']]);      // jsonContains()
```

**Constraints:**

* `jsonContains()` (and its `'CONTAINS'` shorthand operator) is only supported on the MySQL and PostgreSQL
  adapters — it throws an exception on SQLite and SQL Server, since neither has a native JSON containment
  operator/function to render it with.
* The `$path` argument uses MySQL-style JSONPath syntax (`'$.name'`, `'$.address.city'`, `'$.tags[0]'`) on every
  supported dialect *except* PostgreSQL, where `jsonExtract()`/`jsonContains()` parse it internally into
  PostgreSQL's own path-segment array (`{name}`, `{address,city}`, `{tags,0}`) — callers always write the same
  `'$.path'` string regardless of which database is connected.
* PostgreSQL's JSON extraction always yields `text`, and PostgreSQL will not implicitly compare `text` to a
  number, so `jsonEqualTo()`/`jsonNotEqualTo()` render their comparison value as a quoted text literal on that
  adapter (`"data"->>'n' = '5'`). Comparisons are therefore string comparisons on PostgreSQL — `5` and `5.0`
  at the same path are not equal there.
* The `jsonContains()` candidate is a raw PHP value that is JSON-encoded into the query verbatim (so `true`
  becomes `true`, `'admin'` becomes `'"admin"'`); it is never treated as a bound-parameter placeholder, and a
  value that cannot be encoded as JSON throws an exception.

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
