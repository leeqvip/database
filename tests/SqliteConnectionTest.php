<?php

namespace Tests;


class SqliteConnectionTest extends ConnectionTest
{

    protected $config = [];

    protected function initConfig()
    {
        $this->config = [
            'type' => 'sqlite', // mysql,pgsql,sqlite,sqlsrv
            'database' => __DIR__.'/test.db',
        ];
    }
}

