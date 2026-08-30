<?php

namespace Leeqvip\Database;

use InvalidArgumentException;
use LogicException;
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

    protected $transLevel = 0;

    public function __construct(array $config = [])
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }

        if (empty($config['type'])) {
            throw new InvalidArgumentException('database type is required');
        }

        $connector = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
            return strtoupper($match[1]);
        }, $config['type']);

        $connector = ucfirst($connector);

        $connector = '\\Leeqvip\\Database\\Connectors\\' . $connector;

        if (!class_exists($connector)) {
            throw new InvalidArgumentException(sprintf('%s connector not found', $config['type']));
        }
        $this->connector = new $connector();
    }

    public function getPdo(): PDO
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

    public function beginTransaction(): void
    {
        if ($this->transLevel === 0) {
            $this->getPdo()->beginTransaction();
        } else {
            $this->assertTransactionActive();
            $this->connector->createSavepoint($this->getPdo(), 'trans_' . ($this->transLevel + 1));
        }
        $this->transLevel++;
    }

    public function commit(): void
    {
        if ($this->transLevel === 0) {
            throw new LogicException('there is no active transaction');
        }

        $this->assertTransactionActive();

        if ($this->transLevel === 1) {
            $this->getPdo()->commit();
        } else {
            $this->connector->releaseSavepoint($this->getPdo(), 'trans_' . $this->transLevel);
        }
        $this->transLevel--;
    }

    public function rollBack(): void
    {
        if ($this->transLevel === 0) {
            throw new LogicException('there is no active transaction');
        }

        $this->assertTransactionActive();

        if ($this->transLevel === 1) {
            $this->getPdo()->rollBack();
        } else {
            $this->connector->rollbackToSavepoint($this->getPdo(), 'trans_' . $this->transLevel);
        }
        $this->transLevel--;
    }

    /**
     * Runs the callback inside a transaction. Commits on success,
     * rolls back and re-throws when the callback throws.
     *
     * @template T
     * @param callable(self):T $callback
     * @return T
     */
    public function transaction(callable $callback)
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->transLevel > 0) {
                $this->rollBack();
            }
            throw $e;
        }
    }

    /**
     * A transaction can end without commit()/rollBack() being called,
     * e.g. a DDL statement implicitly commits on MySQL. Reset the level
     * so the connection stays usable.
     */
    protected function assertTransactionActive(): void
    {
        if (!$this->getPdo()->inTransaction()) {
            $this->transLevel = 0;
            throw new LogicException('the transaction was implicitly committed, e.g. by a DDL statement');
        }
    }
}
