<?php
namespace TechOne\Database\Connectors;

use PDO;
use PDOException;

/**
 * Connector
 * @author techlee@qq.com
 */
abstract class Connector
{

    protected $params = [
        PDO::ATTR_CASE              => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS      => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES  => false,
    ];

    protected $config = [
        'type'     => '',
        'hostname' => '127.0.0.1',
        'database' => '',
        'username' => '',
        'password' => '',
        'hostport' => '3306',
        'dsn'      => '',
    ];

    abstract protected function parseDsn(array $config);

    public function createConnection(array $config, $autoConnection = false)
    {
        if (!$config) {
            $config = $this->config;
        } else {
            $config = array_merge($this->config, $config);
        }

        if (isset($config['params']) && is_array($config['params'])) {
            $params = $config['params'] + $this->params;
        } else {
            $params = $this->params;
        }

        try {
            if (empty($config['dsn'])) {
                $config['dsn'] = $this->parseDsn($config);
            }

            return new PDO($config['dsn'], $config['username'], $config['password'], $params);

        } catch (PDOException $e) {
            if ($autoConnection) {
                return $this->createConnection($config, $autoConnection);
            } else {
                throw $e;
            }
        }
    }

}
