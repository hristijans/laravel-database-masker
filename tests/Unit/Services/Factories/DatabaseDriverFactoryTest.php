<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Tests\Unit\Services\Factories;

use Hristijans\DatabaseMasker\Contracts\DatabaseDriverInterface;
use Hristijans\DatabaseMasker\Exceptions\UnsupportedDatabaseDriverException;
use Hristijans\DatabaseMasker\Services\Drivers\MySqlDriver;
use Hristijans\DatabaseMasker\Services\Drivers\PostgresDriver;
use Hristijans\DatabaseMasker\Services\Drivers\SqliteDriver;
use Hristijans\DatabaseMasker\Services\Factories\DatabaseDriverFactory;
use Hristijans\DatabaseMasker\Services\Factories\MaskerStrategyFactory;
use Hristijans\DatabaseMasker\Tests\TestCase;
use Illuminate\Database\Connection;
use Mockery;

class DatabaseDriverFactoryTest extends TestCase
{
    private DatabaseDriverFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $maskerFactory = new MaskerStrategyFactory;
        $this->factory = new DatabaseDriverFactory($maskerFactory);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test creating drivers for different database types.
     */
    public function test_create_driver(): void
    {
        // Test MySQL driver
        $mysqlConnection = Mockery::mock(Connection::class);
        $mysqlConnection->shouldReceive('getDriverName')->andReturn('mysql');

        $mysqlDriver = $this->factory->createDriver($mysqlConnection, 'mysql');
        $this->assertInstanceOf(DatabaseDriverInterface::class, $mysqlDriver);
        $this->assertInstanceOf(MySqlDriver::class, $mysqlDriver);

        // Test PostgreSQL driver
        $pgsqlConnection = Mockery::mock(Connection::class);
        $pgsqlConnection->shouldReceive('getDriverName')->andReturn('pgsql');

        $pgsqlDriver = $this->factory->createDriver($pgsqlConnection, 'pgsql');
        $this->assertInstanceOf(DatabaseDriverInterface::class, $pgsqlDriver);
        $this->assertInstanceOf(PostgresDriver::class, $pgsqlDriver);

        // Test SQLite driver
        $sqliteConnection = Mockery::mock(Connection::class);
        $sqliteConnection->shouldReceive('getDriverName')->andReturn('sqlite');

        $sqliteDriver = $this->factory->createDriver($sqliteConnection, 'sqlite');
        $this->assertInstanceOf(DatabaseDriverInterface::class, $sqliteDriver);
        $this->assertInstanceOf(SqliteDriver::class, $sqliteDriver);
    }

    /**
     * Test exception when creating a driver for an unsupported database.
     */
    public function test_unsupported_database_driver(): void
    {
        $this->expectException(UnsupportedDatabaseDriverException::class);

        $unsupportedConnection = Mockery::mock(Connection::class);
        $unsupportedConnection->shouldReceive('getDriverName')->andReturn('unsupported_db');

        $this->factory->createDriver($unsupportedConnection, 'unsupported');
    }
}
