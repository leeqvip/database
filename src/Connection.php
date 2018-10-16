<?php
namespace TechOne\Database;

use InvalidArgumentException;
use PDO;

/**
 * Connection
 * @author techlee@qq.com
 */
class Connection
{
    protected $pdo;

    protected $connector;

    protected $config = [];

    public function __construct(array $config = [])
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }

        $connector = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
            return strtoupper($match[1]);
        }, $config['type']);

        $connector = ucfirst($connector);

        $connector = '\\TechOne\\Database\\Connectors\\' . $connector;

        if (!class_exists($connector)) {
            throw new InvalidArgumentException(sprintf('%s connector not found', $config['type']));
        }
        $this->connector = new $connector;
    }

    public function getPdo()
    {
        if (is_null($this->pdo)) {
            $this->connect();
        }
        return $this->pdo;
    }

    public function connect()
    {
        $this->pdo = $this->connector->createConnection($this->config);
        return $this->pdo;
    }

    public function query($sql, $bind = [])
    {

        try {

            $statement = $this->getPdo()->prepare($sql);

            foreach ($bind as $key => $val) {
                $param = is_numeric($key) ? $key + 1 : ':' . $key;
                $statement->bindValue($param, $val);
            }
            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC);

        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function execute($sql, $bind = [])
    {
        try {

            $statement = $this->getPdo()->prepare($sql);

            foreach ($bind as $key => $val) {
                $param = is_numeric($key) ? $key + 1 : ':' . $key;
                $statement->bindValue($param, $val);
            }

            $statement->execute();

            return $statement->rowCount();

        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
