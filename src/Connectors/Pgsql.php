<?php

namespace TechOne\Database\Connectors;

use PDO;

/**
 * Pgsql Connector.
 *
 * @author techlee@qq.com
 */
class Pgsql extends Connector
{
    protected $params = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ];

    protected function parseDsn(array $config)
    {
        $dsn = 'pgsql:dbname='.$config['database'].';host='.$config['hostname'];

        if (!empty($config['hostport'])) {
            $dsn .= ';port='.$config['hostport'];
        }

        return $dsn;
    }
}
