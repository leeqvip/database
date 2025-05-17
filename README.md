# Database library for PHP

[![PHPUnit](https://github.com/leeqvip/database/actions/workflows/phpunit.yml/badge.svg)](https://github.com/leeqvip/database/actions/workflows/phpunit.yml)
[![Coverage Status](https://coveralls.io/repos/github/leeqvip/database/badge.svg)](https://coveralls.io/github/leeqvip/database)
[![Latest Stable Version](https://poser.pugx.org/leeqvip/database/v/stable)](https://packagist.org/packages/leeqvip/database)
[![Total Downloads](https://poser.pugx.org/leeqvip/database/downloads)](https://packagist.org/packages/leeqvip/database)
[![License](https://poser.pugx.org/leeqvip/database/license)](https://packagist.org/packages/leeqvip/database)

PDO database library for PHP.

the current supported databases are:

| type | database |
| ------ | ------ |
| mysql | MySQL |
| pgsql | PostgreSQL |
| sqlite | SQLite |
| sqlsrv | SqlServer |

### Installation

Use [Composer](https://getcomposer.org/)

```
composer require leeqvip/database
```

### Usage

```php
require_once './vendor/autoload.php';

use Leeqvip\Database\Manager;

$config = [
    'type'     => 'mysql', // mysql,pgsql,sqlite,sqlsrv
    'hostname' => '127.0.0.1',
    'database' => 'test',
    'username' => 'root',
    'password' => 'abc-123',
    'hostport' => '3306',
];

$manager = new Manager($config);
$connection = $manager->getConnection();

$connection->query('SELECT * FROM `users` WHERE `id` = :id', ['id' => 1]);

$connection->execute('UPDATE `users` SET `name` = "joker" where `id` = :id', ['id' => 1]);
```

### License

This project is licensed under the [Apache 2.0 license](LICENSE).