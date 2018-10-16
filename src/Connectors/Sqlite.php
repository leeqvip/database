<?php
namespace TechOne\Database\Connectors;

/**
 * Sqlite Connector
 * @author techlee@qq.com
 */
class Sqlite extends Connector
{

    protected function parseDsn(array $config)
    {
        $dsn = 'sqlite:' . $config['database'];

        return $dsn;
    }

}
