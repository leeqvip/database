<?php
namespace TechOne\Database;

use TechOne\Database;

/**
 * Database Manager
 * @author techlee@qq.com
 */
class Manager
{

    protected $connection;

    protected $config = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getConnection()
    {
        if (is_null($this->connection)) {
            $this->connection = new Connection($this->config);
        }
        return $this->connection;
    }
}
