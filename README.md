# Database library for PHP

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
composer require techone/database
```

### Usage

```php
require_once './vendor/autoload.php';

use TechOne\Database\Manager;

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