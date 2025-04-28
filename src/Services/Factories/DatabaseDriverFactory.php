<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Services\Factories;

use Hristijans\DatabaseMasker\Contracts\DatabaseDriverInterface;
use Hristijans\DatabaseMasker\Exceptions\UnsupportedDatabaseDriverException;
use Hristijans\DatabaseMasker\Services\Drivers\MySqlDriver;
use Hristijans\DatabaseMasker\Services\Drivers\PostgresDriver;
use Hristijans\DatabaseMasker\Services\Drivers\SqliteDriver;
use Illuminate\Database\Connection;

final class DatabaseDriverFactory
{
    private MaskerStrategyFactory $maskerFactory;

    /**
     * Create a new instance.
     */
    public function __construct(MaskerStrategyFactory $maskerFactory)
    {
        $this->maskerFactory = $maskerFactory;
    }

    /**
     * Create a database driver based on the connection.
     *
     * @throws UnsupportedDatabaseDriverException
     */
    public function createDriver(Connection $connection, string $connectionName): DatabaseDriverInterface
    {
        $driver = match ($connection->getDriverName()) {
            'mysql' => new MySqlDriver($this->maskerFactory),
            'pgsql' => new PostgresDriver($this->maskerFactory),
            'sqlite' => new SqliteDriver($this->maskerFactory),
            default => throw new UnsupportedDatabaseDriverException("Unsupported database driver: {$connection->getDriverName()}")
        };

        $driver->initialize($connection, $connectionName);

        return $driver;
    }
}
