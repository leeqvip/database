<?php

namespace Tests;


class PostgresConnectionTest extends ConnectionTest
{

    protected $config = [];

    protected function initConfig()
    {
        $this->config = [
            'type' => 'pgsql', // mysql,pgsql,sqlite,sqlsrv
            'hostname' => getenv('PG_HOST'),
            'database' => getenv('PG_DATABASE'),
            'username' => getenv('PG_USERNAME'),
            'password' => getenv('PG_PASSWORD'),
            'hostport' => getenv('PG_PORT'),
        ];
    }
}
