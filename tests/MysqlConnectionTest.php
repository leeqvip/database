<?php

namespace Tests;


class MysqlConnectionTest extends ConnectionTest
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
    }
}
