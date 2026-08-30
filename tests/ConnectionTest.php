<?php

namespace Tests;

use InvalidArgumentException;
use Leeqvip\Database\Connection;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

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

    public function testCommitPersistsChanges()
    {
        $this->init();
        $conn = $this->getConnection();

        $conn->beginTransaction();
        $conn->execute("INSERT INTO users (id, name, nickname, created_at) VALUES (400, 'dave', 'Small Cloud', '2025-05-17 13:28:56')");
        $this->assertCount(1, $conn->query("SELECT * FROM users WHERE id = 400"));
        $conn->commit();

        $this->assertCount(1, $conn->query("SELECT * FROM users WHERE id = 400"));
    }

    public function testRollbackRevertsChanges()
    {
        $this->init();
        $conn = $this->getConnection();

        $conn->beginTransaction();
        $conn->execute("INSERT INTO users (id, name, nickname, created_at) VALUES (500, 'eve', 'Small Moon', '2025-05-17 13:28:56')");
        $conn->rollBack();

        $this->assertCount(0, $conn->query("SELECT * FROM users WHERE id = 500"));
    }

    public function testTransactionCallbackCommitsOnSuccess()
    {
        $this->init();
        $conn = $this->getConnection();

        $result = $conn->transaction(function (Connection $db) {
            $db->execute("INSERT INTO users (id, name, nickname, created_at) VALUES (600, 'frank', 'Small Sun', '2025-05-17 13:28:56')");
            return 'ok';
        });

        $this->assertEquals('ok', $result);
        $this->assertCount(1, $conn->query("SELECT * FROM users WHERE id = 600"));
    }

    public function testTransactionCallbackRollsBackOnException()
    {
        $this->init();
        $conn = $this->getConnection();

        try {
            $conn->transaction(function (Connection $db) {
                $db->execute("INSERT INTO users (id, name, nickname, created_at) VALUES (700, 'grace', 'Small Rain', '2025-05-17 13:28:56')");
                throw new RuntimeException('boom');
            });
            $this->fail('Expected RuntimeException to be thrown');
        } catch (RuntimeException $e) {
            $this->assertEquals('boom', $e->getMessage());
        }

        $this->assertCount(0, $conn->query("SELECT * FROM users WHERE id = 700"));
    }

    public function testNestedTransactionsRollBackInnerOnly()
    {
        $this->init();
        $conn = $this->getConnection();

        $conn->beginTransaction();
        $conn->execute("INSERT INTO users (id, name, nickname, created_at) VALUES (800, 'henry', 'Small Tree', '2025-05-17 13:28:56')");
        $conn->beginTransaction();
        $conn->execute("INSERT INTO users (id, name, nickname, created_at) VALUES (801, 'iris', 'Small Leaf', '2025-05-17 13:28:56')");
        $conn->rollBack();
        $conn->commit();

        $this->assertCount(1, $conn->query("SELECT * FROM users WHERE id = 800"));
        $this->assertCount(0, $conn->query("SELECT * FROM users WHERE id = 801"));
    }

    public function testNestedTransactionCallbackCommits()
    {
        $this->init();
        $conn = $this->getConnection();

        $conn->transaction(function (Connection $db) {
            $db->execute("INSERT INTO users (id, name, nickname, created_at) VALUES (900, 'jack', 'Small Lake', '2025-05-17 13:28:56')");
            $db->transaction(function (Connection $db2) {
                $db2->execute("INSERT INTO users (id, name, nickname, created_at) VALUES (901, 'kate', 'Small Hill', '2025-05-17 13:28:56')");
            });
        });

        $this->assertCount(1, $conn->query("SELECT * FROM users WHERE id = 900"));
        $this->assertCount(1, $conn->query("SELECT * FROM users WHERE id = 901"));
    }

    public function testNestedTransactionCallbackRollsBackInnerOnly()
    {
        $this->init();
        $conn = $this->getConnection();

        $conn->transaction(function (Connection $db) {
            $db->execute("INSERT INTO users (id, name, nickname, created_at) VALUES (910, 'leo', 'Small River', '2025-05-17 13:28:56')");
            try {
                $db->transaction(function (Connection $db2) {
                    $db2->execute("INSERT INTO users (id, name, nickname, created_at) VALUES (911, 'mia', 'Small Stone', '2025-05-17 13:28:56')");
                    throw new RuntimeException('inner boom');
                });
            } catch (RuntimeException $e) {
                // the inner transaction failed, the outer one keeps going
            }
        });

        $this->assertCount(1, $conn->query("SELECT * FROM users WHERE id = 910"));
        $this->assertCount(0, $conn->query("SELECT * FROM users WHERE id = 911"));
    }

    public function testCommitWithoutTransactionThrowsException()
    {
        $this->init();

        $this->expectException(LogicException::class);
        $this->getConnection()->commit();
    }

    public function testRollbackWithoutTransactionThrowsException()
    {
        $this->init();

        $this->expectException(LogicException::class);
        $this->getConnection()->rollBack();
    }

    /**
     * A connection whose bookkeeping claims an open transaction while the
     * underlying PDO has none, as after an implicit commit. Simulated
     * directly because PDO::inTransaction() does not report raw SQL
     * COMMIT/ROLLBACK reliably on every driver version.
     */
    protected function desyncedConnection()
    {
        $conn = new class ($this->config) extends Connection {
            public function simulateImplicitCommit(): void
            {
                $this->transLevel = 1;
            }
        };
        $conn->simulateImplicitCommit();

        return $conn;
    }

    public function testCommitAfterImplicitCommitThrowsAndStateRecovers()
    {
        $this->init();
        $conn = $this->desyncedConnection();

        try {
            $conn->commit();
            $this->fail('Expected LogicException to be thrown');
        } catch (LogicException $e) {
            $this->assertStringContainsString('implicitly committed', $e->getMessage());
        }

        $conn->beginTransaction();
        $conn->execute("INSERT INTO users (id, name, nickname, created_at) VALUES (920, 'nina', 'Small Sea', '2025-05-17 13:28:56')");
        $conn->commit();

        $this->assertCount(1, $conn->query("SELECT * FROM users WHERE id = 920"));
    }

    public function testBeginTransactionAfterImplicitCommitThrowsException()
    {
        $this->init();
        $conn = $this->desyncedConnection();

        $this->expectException(LogicException::class);
        $conn->beginTransaction();
    }
}
