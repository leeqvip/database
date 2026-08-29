<?php

namespace Tests;

use InvalidArgumentException;
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

        try {
            $this->getPDO();
        } catch (\PDOException $e) {
            $this->markTestSkipped(sprintf('Database connection not available: %s', $e->getMessage()));
        }

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

    public function testQueryWithNumberedPlaceholders()
    {
        $this->init();
        $this->getPDO()->exec("INSERT INTO users (id, name, nickname, created_at) VALUES (100, 'alice', 'Small Flower', '2025-05-17 13:28:56')");

        $users = $this->getConnection()->query("SELECT * FROM users WHERE id = ? AND name = ?", [100, 'alice']);
        $this->assertCount(1, $users);
        $this->assertEquals('alice', $users[0]['name']);
    }

    public function testExecuteWithNumberedPlaceholders()
    {
        $this->init();
        $conn = $this->getConnection();

        $conn->execute(
            "INSERT INTO users (id, name, nickname, created_at) VALUES (?, ?, ?, '2025-05-17 13:28:56')",
            [300, 'carol', 'Small Star'],
        );

        $users = $conn->query("SELECT * FROM users WHERE id = ?", [300]);
        $this->assertCount(1, $users);
        $this->assertEquals('carol', $users[0]['name']);
    }

    public function testExecuteReturnsAffectedRowCount()
    {
        $this->init();
        $conn = $this->getConnection();

        $affected = $conn->execute("INSERT INTO users (id, name, nickname, created_at) VALUES (300, 'carol', 'Small Star', '2025-05-17 13:28:56')");
        $this->assertEquals(1, $affected);

        $affected = $conn->execute("UPDATE users SET name = 'carol2' WHERE id = 300");
        $this->assertEquals(1, $affected);

        $affected = $conn->execute("UPDATE users SET name = 'nobody' WHERE id = 999");
        $this->assertEquals(0, $affected);
    }

    public function testGetPdoReturnsSameInstance()
    {
        $this->init();

        $connection = $this->getConnection();
        $this->assertSame($connection->getPdo(), $connection->getPdo());
    }

    public function testQueryWithInvalidSqlThrowsException()
    {
        $this->init();

        $this->expectException(\PDOException::class);
        $this->getConnection()->query("SELECT * FROM table_not_exists");
    }

    public function testExecuteWithInvalidSqlThrowsException()
    {
        $this->init();

        $this->expectException(\PDOException::class);
        $this->getConnection()->execute("INSERT INTO table_not_exists (id) VALUES (1)");
    }

    public function testUnknownDriverThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        new Connection(['type' => 'foo_bar_unknown']);
    }

    public function testMissingDriverThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        new Connection([]);
    }
}
