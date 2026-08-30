<?php

namespace Tests;

use Leeqvip\Database\Connectors\Mysql;
use Leeqvip\Database\Connectors\Pgsql;
use Leeqvip\Database\Connectors\Sqlite;
use Leeqvip\Database\Connectors\Sqlsrv;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

class ConnectorTest extends TestCase
{
    protected function mysqlConnector()
    {
        return new class extends Mysql {
            public function dsn(array $config)
            {
                return $this->parseDsn($config);
            }
        };
    }

    protected function pgsqlConnector()
    {
        return new class extends Pgsql {
            public function dsn(array $config)
            {
                return $this->parseDsn($config);
            }
        };
    }

    protected function sqlsrvConnector()
    {
        return new class extends Sqlsrv {
            public function dsn(array $config)
            {
                return $this->parseDsn($config);
            }
        };
    }

    protected function sqliteConnector()
    {
        return new class extends Sqlite {
            public function dsn(array $config)
            {
                return $this->parseDsn($config);
            }
        };
    }

    public function testMysqlDsnWithSocket()
    {
        $dsn = $this->mysqlConnector()->dsn([
            'socket' => '/tmp/mysql.sock',
            'database' => 'test',
        ]);

        $this->assertSame('mysql:unix_socket=/tmp/mysql.sock;dbname=test', $dsn);
    }

    public function testMysqlDsnWithHostport()
    {
        $dsn = $this->mysqlConnector()->dsn([
            'hostname' => '127.0.0.1',
            'hostport' => '3307',
            'database' => 'test',
        ]);

        $this->assertSame('mysql:host=127.0.0.1;port=3307;dbname=test', $dsn);
    }

    public function testMysqlDsnWithoutHostport()
    {
        $dsn = $this->mysqlConnector()->dsn([
            'hostname' => '127.0.0.1',
            'database' => 'test',
        ]);

        $this->assertSame('mysql:host=127.0.0.1;dbname=test', $dsn);
    }

    public function testMysqlDsnWithCharset()
    {
        $dsn = $this->mysqlConnector()->dsn([
            'hostname' => '127.0.0.1',
            'hostport' => '3306',
            'database' => 'test',
            'charset' => 'utf8mb4',
        ]);

        $this->assertSame('mysql:host=127.0.0.1;port=3306;dbname=test;charset=utf8mb4', $dsn);
    }

    public function testPgsqlDsnWithHostport()
    {
        $dsn = $this->pgsqlConnector()->dsn([
            'hostname' => '127.0.0.1',
            'hostport' => '5432',
            'database' => 'test',
        ]);

        $this->assertSame('pgsql:dbname=test;host=127.0.0.1;port=5432', $dsn);
    }

    public function testPgsqlDsnWithoutHostport()
    {
        $dsn = $this->pgsqlConnector()->dsn([
            'hostname' => '127.0.0.1',
            'database' => 'test',
        ]);

        $this->assertSame('pgsql:dbname=test;host=127.0.0.1', $dsn);
    }

    public function testSqlsrvDsnWithHostport()
    {
        $dsn = $this->sqlsrvConnector()->dsn([
            'hostname' => '127.0.0.1',
            'hostport' => '1433',
            'database' => 'test',
        ]);

        $this->assertSame('sqlsrv:Database=test;Server=127.0.0.1,1433', $dsn);
    }

    public function testSqlsrvDsnWithoutHostport()
    {
        $dsn = $this->sqlsrvConnector()->dsn([
            'hostname' => '127.0.0.1',
            'database' => 'test',
        ]);

        $this->assertSame('sqlsrv:Database=test;Server=127.0.0.1', $dsn);
    }

    public function testSqliteDsn()
    {
        $dsn = $this->sqliteConnector()->dsn([
            'database' => __DIR__.'/test.db',
        ]);

        $this->assertSame('sqlite:'.__DIR__.'/test.db', $dsn);
    }

    public function testStandardSavepointStatements()
    {
        $connector = new Sqlite();

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('exec')->with('SAVEPOINT trans_2');
        $connector->createSavepoint($pdo, 'trans_2');

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('exec')->with('RELEASE SAVEPOINT trans_2');
        $connector->releaseSavepoint($pdo, 'trans_2');

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('exec')->with('ROLLBACK TO SAVEPOINT trans_2');
        $connector->rollbackToSavepoint($pdo, 'trans_2');
    }

    public function testSqlsrvSavepointStatementsUseSqlServerSyntax()
    {
        $connector = new Sqlsrv();

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('exec')->with('SAVE TRANSACTION trans_2');
        $connector->createSavepoint($pdo, 'trans_2');

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('exec')->with('ROLLBACK TRANSACTION trans_2');
        $connector->rollbackToSavepoint($pdo, 'trans_2');
    }

    public function testSqlsrvDoesNotReleaseSavepoints()
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->never())->method('exec');

        (new Sqlsrv())->releaseSavepoint($pdo, 'trans_2');
    }

    public function testCreateConnectionWithDefaultConfig()
    {
        $connector = new class extends Sqlite {
            protected $config = [
                'type' => 'sqlite',
                'database' => ':memory:',
                'username' => '',
                'password' => '',
            ];
        };

        $this->assertInstanceOf(PDO::class, $connector->createConnection([]));
    }

    public function testCreateConnectionWithExplicitDsn()
    {
        $pdo = (new Sqlite())->createConnection(['dsn' => 'sqlite::memory:']);

        $this->assertInstanceOf(PDO::class, $pdo);
    }

    public function testCreateConnectionWithCustomParams()
    {
        $pdo = (new Sqlite())->createConnection([
            'dsn' => 'sqlite::memory:',
            'params' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
            ],
        ]);

        $this->assertSame(PDO::ERRMODE_SILENT, $pdo->getAttribute(PDO::ATTR_ERRMODE));
    }

    public function testCreateConnectionFailsWhenDatabaseNotAvailable()
    {
        $this->expectException(PDOException::class);

        (new Sqlite())->createConnection([
            'database' => __DIR__.'/not_exists_dir/test.db',
        ]);
    }

    public function testCreateConnectionWithAutoConnectionRetriesOnce()
    {
        $this->expectException(PDOException::class);

        (new Sqlite())->createConnection([
            'database' => __DIR__.'/not_exists_dir/test.db',
        ], true);
    }
}
