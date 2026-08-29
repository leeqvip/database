# Database library for PHP

[![PHPUnit](https://github.com/leeqvip/database/actions/workflows/phpunit.yml/badge.svg)](https://github.com/leeqvip/database/actions/workflows/phpunit.yml)
[![Coverage Status](https://coveralls.io/repos/github/leeqvip/database/badge.svg)](https://coveralls.io/github/leeqvip/database)
[![Latest Stable Version](https://poser.pugx.org/leeqvip/database/v/stable)](https://packagist.org/packages/leeqvip/database)
[![Total Downloads](https://poser.pugx.org/leeqvip/database/downloads)](https://packagist.org/packages/leeqvip/database)
[![License](https://poser.pugx.org/leeqvip/database/license)](https://packagist.org/packages/leeqvip/database)

A lightweight PDO database library for PHP.

The currently supported databases are:

| type | database | PDO driver |
| ------ | ------ | ------ |
| mysql | MySQL | `pdo_mysql` |
| pgsql | PostgreSQL | `pdo_pgsql` |
| sqlite | SQLite | `pdo_sqlite` |
| sqlsrv | SQL Server | `pdo_sqlsrv` |

### Requirements

- PHP >= 8.0
- [ext-pdo](https://www.php.net/manual/en/book.pdo.php)
- The PDO driver for your database, e.g. `pdo_mysql`, `pdo_pgsql` or `pdo_sqlite`. For SQL Server, install the [pdo_sqlsrv](https://learn.microsoft.com/en-us/sql/connect/php/installing-the-microsoft-drivers-for-php-for-sql-server) extension (not bundled with PHP).

### Installation

Use [Composer](https://getcomposer.org/)

```
composer require leeqvip/database
```

### Usage

#### Quick start

```php
require_once './vendor/autoload.php';

use Leeqvip\Database\Manager;

$config = [
    'type'     => 'mysql', // mysql, pgsql, sqlite, sqlsrv
    'hostname' => '127.0.0.1',
    'database' => 'test',
    'username' => 'root',
    'password' => 'abc-123',
    'hostport' => '3306',
    'charset'  => 'utf8mb4',
];

$manager = new Manager($config);
$connection = $manager->getConnection(); // lazily creates and caches one Connection

// Returns all matching rows as an array of associative arrays
$rows = $connection->query('SELECT * FROM `users` WHERE `id` = :id', ['id' => 1]);

// Returns the number of affected rows
$count = $connection->execute(
    'UPDATE `users` SET `name` = :name WHERE `id` = :id',
    ['name' => 'joker', 'id' => 1]
);
```

Both named parameters and positional placeholders are supported. Numeric keys in the bind array are converted to 1-based positional parameters:

```php
$rows = $connection->query('SELECT * FROM `users` WHERE `id` = ?', [1]);
```

#### Configuration

Only `type` is required; everything else has a sensible default.

| key | description | default |
| ------ | ------ | ------ |
| `type` | Connector name: `mysql`, `pgsql`, `sqlite`, `sqlsrv` | (required) |
| `hostname` | Server host | `127.0.0.1` |
| `hostport` | Server port | `3306` |
| `database` | Database name. For SQLite, the path to the database file | `''` |
| `username` | Username | `''` |
| `password` | Password | `''` |
| `charset` | MySQL connection charset | — |
| `socket` | MySQL unix socket; takes precedence over `hostname`/`hostport` | — |
| `dsn` | Full DSN string; bypasses the auto-generated DSN | — |
| `params` | PDO attributes, merged over the defaults below | — |

The default PDO attributes (MySQL and SQLite) are:

```php
[
    PDO::ATTR_CASE              => PDO::CASE_NATURAL,
    PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_ORACLE_NULLS      => PDO::NULL_NATURAL,
    PDO::ATTR_STRINGIFY_FETCHES => false,
    PDO::ATTR_EMULATE_PREPARES  => false,
]
```

Note that `PDO::ATTR_EMULATE_PREPARES` is disabled by default, so queries use native prepared statements where the driver supports them. (The `pgsql` and `sqlsrv` connectors use the same defaults minus `PDO::ATTR_EMULATE_PREPARES`.)

#### SQLite

SQLite only needs the path to the database file:

```php
$connection = (new Manager([
    'type'     => 'sqlite',
    'database' => '/path/to/database.sqlite',
]))->getConnection();
```

#### Custom DSN

If the auto-generated DSN is not enough, pass a complete one and it will be used as-is:

```php
$config = [
    'type'     => 'mysql',
    'dsn'      => 'mysql:unix_socket=/tmp/mysql.sock;dbname=test;charset=utf8mb4',
    'username' => 'root',
    'password' => 'abc-123',
];
```

#### Raw PDO access

```php
$pdo = $connection->getPdo(); // lazily connects on first access
$connection->connect();       // or connect explicitly

$statement = $connection->getPdo()->prepare('SELECT VERSION()');
$statement->execute();
```

You may also skip the `Manager` entirely and use `Leeqvip\Database\Connection` directly with the same config array.

### Exceptions

- A missing `type` or an unknown connector throws `InvalidArgumentException` when the connection object is created (i.e. in `getConnection()`).
- Connection and query failures throw `PDOException`.

### Testing

```
composer install
vendor/bin/phpunit
```

### License

This project is licensed under the [Apache 2.0 license](LICENSE).
