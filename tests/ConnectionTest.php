<?php

namespace Tests;

use Leeqvip\Database\Connection;
use PHPUnit\Framework\TestCase;

abstract class ConnectionTest extends TestCase
{

    protected $config = [];

    abstract protected function initConfig();

    protected function initDatabase()
    {
        $pdo = $this->getPDO();
        $pdo->exec("DROP TABLE IF EXISTS users");
        $pdo->exec(<<<EOT
            CREATE TABLE users (
              id int NOT NULL,
              name varchar(191) DEFAULT NULL,
              nickname varchar(255) DEFAULT NULL,
              created_at timestamp NULL DEFAULT NULL
            );
            EOT
        );
    }

    protected function init()
    {
        $this->initConfig();
        $this->initDatabase();
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
        $this->init();
        $this->getPDO()->exec("INSERT INTO users (id, name, nickname, created_at) VALUES (100, 'alice', 'Small Flower', '2025-05-17 13:28:56')");

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
        $this->init();
        $conn = $this->getConnection();

        $users = $conn->query("SELECT * FROM users");
        $this->assertCount(0, $users);
        $conn->execute(
            "INSERT INTO users (id, name, nickname, created_at) VALUES (:id, :name, :nickname, '2025-05-17 13:28:56')",
            [
                'id' => 200,
                'name' => 'bob',
                'nickname' => 'Small Angel',
            ],
        );

        $users = $conn->query("SELECT * FROM users");
        $this->assertCount(1, $users);

        $this->assertEquals('200', $users[0]['id']);
        $this->assertEquals('bob', $users[0]['name']);
        $this->assertEquals('Small Angel', $users[0]['nickname']);
    }
}
