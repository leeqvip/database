<?php

namespace Tests;

use Leeqvip\Database\Connection;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{

    protected $config = [];

    protected function initConfig()
    {
        $this->config = [
            'type' => 'mysql', // mysql,pgsql,sqlite,sqlsrv
            'hostname' => getenv('DB_HOST'),
            'database' => getenv('DB_DATABASE'),
            'username' => getenv('DB_USERNAME'),
            'password' => getenv('DB_PASSWORD'),
            'hostport' => getenv('DB_PORT'),
        ];

        $pdo = $this->getPDO();
        $pdo->exec("DROP TABLE IF EXISTS users");
        $pdo->exec(<<<EOT
            CREATE TABLE users (
              `id` bigint unsigned NOT NULL,
              `name` varchar(191) DEFAULT NULL,
              `nickname` varchar(255) DEFAULT NULL,
              `created_at` timestamp NULL DEFAULT NULL
            );
            EOT);
    }

    protected function getConnection()
    {
        return new Connection($this->config);
    }

    protected function getPDO(): \PDO
    {
        return $this->getConnection()->getPdo();
    }

    public function testQuery()
    {
        $this->initConfig();
        $this->getPDO()->exec("INSERT INTO users (id, name, nickname, created_at) VALUES (100, 'alice', 'Small Flower', now())");

        $users = $this->getConnection()->query("SELECT * FROM users");
        $this->assertCount(1, $users);

        $users = $this->getConnection()->query("SELECT * FROM users WHERE id = :id", ['id' => 100]);
        $this->assertCount(1, $users);

        $this->assertEquals('100', $users[0]['id']);
        $this->assertEquals('alice', $users[0]['name']);
        $this->assertEquals('Small Flower', $users[0]['nickname']);
    }

    public function testExecute()
    {
        $this->initConfig();
        $conn = $this->getConnection();

        $users = $conn->query("SELECT * FROM users");
        $this->assertCount(0, $users);
        $conn->execute(
            "INSERT INTO users (id, name, nickname, created_at) VALUES (:id, :name, :nickname, :created_at)",
            [
                'id' => 200,
                'name' => 'bob',
                'nickname' => 'Small Angel',
                'created_at' => time(),
            ],
        );

        $users = $conn->query("SELECT * FROM users");
        $this->assertCount(1, $users);

        $this->assertEquals('200', $users[0]['id']);
        $this->assertEquals('bob', $users[0]['name']);
        $this->assertEquals('Small Angel', $users[0]['nickname']);
    }
}
