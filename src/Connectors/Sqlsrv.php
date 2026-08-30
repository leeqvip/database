<?php

namespace Leeqvip\Database\Connectors;

use PDO;

/**
 * Sqlsrv Connector.
 *
 * @author techlee@qq.com
 */
class Sqlsrv extends Connector
{
    protected $params = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ];

    protected function parseDsn(array $config)
    {
        $dsn = 'sqlsrv:Database='.$config['database'].';Server='.$config['hostname'];

        if (!empty($config['hostport'])) {
            $dsn .= ','.$config['hostport'];
        }

        return $dsn;
    }

    public function createSavepoint(PDO $pdo, string $name): void
    {
        $pdo->exec('SAVE TRANSACTION ' . $name);
    }

    public function releaseSavepoint(PDO $pdo, string $name): void
    {
        // SQL Server has no RELEASE SAVEPOINT; savepoint names can be reused
    }

    public function rollbackToSavepoint(PDO $pdo, string $name): void
    {
        $pdo->exec('ROLLBACK TRANSACTION ' . $name);
    }
}
