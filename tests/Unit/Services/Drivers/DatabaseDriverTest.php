<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Tests\Unit\Services\Drivers;

use Hristijans\DatabaseMasker\Services\Drivers\MySqlDriver;
use Hristijans\DatabaseMasker\Services\Drivers\SqliteDriver;
use Hristijans\DatabaseMasker\Services\Factories\MaskerStrategyFactory;
use Hristijans\DatabaseMasker\Tests\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Mockery;

class DatabaseDriverTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test the format SQL value method.
     */
    public function test_format_sql_value(): void
    {
        $maskerFactory = new MaskerStrategyFactory;
        $driver = new MySqlDriver($maskerFactory);

        // Test NULL value
        $this->assertEquals('NULL', $driver->formatSqlValue(null));

        // Test boolean values
        $this->assertEquals('1', $driver->formatSqlValue(true));
        $this->assertEquals('0', $driver->formatSqlValue(false));

        // Test numeric values
        $this->assertEquals('123', $driver->formatSqlValue(123));
        $this->assertEquals('123.45', $driver->formatSqlValue(123.45));

        // Test string values (should be quoted and escaped)
        $this->assertEquals("'test'", $driver->formatSqlValue('test'));
        $this->assertEquals("'test\\'s'", $driver->formatSqlValue("test's")); // Test escaping
    }

    /**
     * Test the generate SQL statements method.
     */
//    public function test_generate_insert_sql(): void
//    {
//        // Create mocks
//        $connection = Mockery::mock(Connection::class);
//        $connection->shouldReceive('getDriverName')->andReturn('mysql');
//
//        $maskerFactory = new MaskerStrategyFactory;
//        $driver = new MySqlDriver($maskerFactory);
//
//        // Initialize the driver with a mock connection
//        $driver->initialize($connection, 'mysql');
//
//        // Create test data
//        $records = new Collection([
//            (object) [
//                'id' => 1,
//                'name' => 'Test User',
//                'email' => 'test@example.com',
//            ],
//        ]);
//
//        // Define table configuration
//        $tableConfig = [
//            'columns' => [
//                'email' => ['type' => 'email'],
//                'name' => ['type' => 'name'],
//            ],
//        ];
//
//        // Create a schema mock that's specific to just this test
//        $schemaMock = Mockery::mock('alias:Illuminate\Support\Facades\Schema');
//        $schemaMock->shouldReceive('connection')
//            ->with('mysql')
//            ->andReturnSelf();
//        $schemaMock->shouldReceive('getColumnListing')
//            ->andReturn(['id', 'name', 'email']);
//
//        // Generate the SQL
//        $sql = $driver->generateInsertSql('users', $records, $tableConfig);
//
//        // Verify the SQL
//        $this->assertStringContainsString('INSERT INTO `users`', $sql);
//        $this->assertStringContainsString('(`id`, `name`, `email`)', $sql);
//        $this->assertStringContainsString('VALUES', $sql);
//
//        // The ID should be preserved (not masked)
//        $this->assertStringContainsString('(1,', $sql);
//
//        // The name and email should be masked (values will be dynamic)
//        $this->assertStringNotContainsString("'Test User'", $sql);
//        $this->assertStringNotContainsString("'test@example.com'", $sql);
//    }

    /**
     * Test SQLite driver's getTables method.
     */
    public function test_sqlite_driver_get_tables(): void
    {
        // Create a real SQLite connection for this test
        $this->setUpDatabase();
        $connection = $this->app['db']->connection('testing');

        $maskerFactory = new MaskerStrategyFactory;
        $driver = new SqliteDriver($maskerFactory);
        $driver->initialize($connection, 'testing');

        // Get all tables (excluding none)
        $tables = $driver->getTables([]);

        // Should contain our test tables
        $this->assertContains('test_users', $tables);
        $this->assertContains('test_customers', $tables);

        // Test with exclusions
        $tablesWithExclusion = $driver->getTables(['test_users']);
        $this->assertNotContains('test_users', $tablesWithExclusion);
        $this->assertContains('test_customers', $tablesWithExclusion);
    }

    /**
     * Test SQLite driver's getCreateTableSql method.
     */
    public function test_sqlite_driver_get_create_table_sql(): void
    {
        // Create a real SQLite connection for this test
        $this->setUpDatabase();
        $connection = $this->app['db']->connection('testing');

        $maskerFactory = new MaskerStrategyFactory;
        $driver = new SqliteDriver($maskerFactory);
        $driver->initialize($connection, 'testing');

        // Get CREATE TABLE SQL for test_users
        $sql = $driver->getCreateTableSql('test_users');

        // Verify the SQL
        $this->assertStringContainsString('DROP TABLE IF EXISTS "test_users"', $sql);
        $this->assertStringContainsString('CREATE TABLE "test_users"', $sql);
        $this->assertStringContainsString('name', $sql);
        $this->assertStringContainsString('email', $sql);
    }

    /**
     * Test file operations (init and finalize).
     */
    public function test_file_operations(): void
    {
        $maskerFactory = new MaskerStrategyFactory;
        $driver = new MySqlDriver($maskerFactory);

        $tempFile = tempnam(sys_get_temp_dir(), 'db_driver_test_');

        // Initialize the file
        $driver->initSqlFile($tempFile, 'mysql');

        // Verify file content
        $content = file_get_contents($tempFile);
        $this->assertStringContainsString('Database Masked Dump', $content);
        $this->assertStringContainsString('for connection \'mysql\'', $content);
        $this->assertStringContainsString('SET FOREIGN_KEY_CHECKS=0', $content);

        // Finalize the file
        $driver->finalizeSqlFile($tempFile);

        // Verify updated content
        $updatedContent = file_get_contents($tempFile);
        $this->assertStringContainsString('SET FOREIGN_KEY_CHECKS=1', $updatedContent);

        // Clean up
        unlink($tempFile);
    }
}
