<?php

namespace TechOne\Database\Connectors;

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
}
