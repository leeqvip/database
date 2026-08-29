<?php

namespace Tests;

use Leeqvip\Database\Connection;
use Leeqvip\Database\Manager;
use PHPUnit\Framework\TestCase;

class ManagerTest extends TestCase
{
    public function testGetConnectionReturnsConnection()
    {
        $manager = new Manager([
            'type' => 'sqlite',
            'database' => ':memory:',
        ]);

        $this->assertInstanceOf(Connection::class, $manager->getConnection());
    }

    public function testGetConnectionReturnsSameInstance()
    {
        $manager = new Manager([
            'type' => 'sqlite',
            'database' => ':memory:',
        ]);

        $this->assertSame($manager->getConnection(), $manager->getConnection());
    }

    public function testGetConnectionUsesConfig()
    {
        $manager = new Manager([
            'type' => 'sqlite',
            'database' => ':memory:',
        ]);
        $connection = $manager->getConnection();

        $connection->execute('CREATE TABLE users (id int NOT NULL, name varchar(191) DEFAULT NULL)');
        $connection->execute(
            'INSERT INTO users (id, name) VALUES (:id, :name)',
            [
                'id' => 100,
                'name' => 'alice',
            ],
        );

        $users = $connection->query('SELECT * FROM users');
        $this->assertCount(1, $users);
        $this->assertEquals('100', $users[0]['id']);
        $this->assertEquals('alice', $users[0]['name']);
    }
}
