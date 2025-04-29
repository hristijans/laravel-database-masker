<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Tests\Integration;

use Hristijans\DatabaseMasker\Contracts\DatabaseMaskerInterface;
use Hristijans\DatabaseMasker\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DatabaseMaskerTest extends TestCase
{
    protected DatabaseMaskerInterface $databaseMasker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->databaseMasker = $this->app->make(DatabaseMaskerInterface::class);
    }

    protected function tearDown(): void
    {
        // Get the actual storage path in the test environment
        $storagePath = $this->app->storagePath();

        // Clean up any test files
        $files = [
            $storagePath . '/app/masked_testing_cmd.sql',
            $storagePath . '/app/masked_second_db_cmd.sql',
            $storagePath . '/app/custom_output.sql',
        ];

        foreach ($files as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        parent::tearDown();
    }

    /**
     * Test the service provider is loaded.
     */
    public function test_service_provider_is_loaded(): void
    {
        $this->assertTrue($this->app->bound('database-masker'));
        $this->assertTrue($this->app->bound(DatabaseMaskerInterface::class));
    }

    /**
     * Test creating a masked database dump with the legacy method.
     */
    public function test_create_masked_dump(): void
    {
        // Drop and recreate tables to ensure a clean state
        $schema = $this->app['db']->connection()->getSchemaBuilder();
        if ($schema->hasTable('test_users')) {
            $schema->drop('test_users');
        }
        if ($schema->hasTable('test_customers')) {
            $schema->drop('test_customers');
        }

        // Create fresh tables
        $this->setupTestTablesForConnection('testing');

        // Verify tables exist in the database
        $this->assertTrue(
            $schema->hasTable('test_users'),
            'test_users table not found in database'
        );
        $this->assertTrue(
            $schema->hasTable('test_customers'),
            'test_customers table not found in database'
        );

        // Configure tables for masking
        config(['database-masker.tables' => [
            'test_users' => [
                'columns' => [
                    'email' => ['type' => 'email'],
                    'name' => ['type' => 'name'],
                ],
            ],
            'test_customers' => [
                'columns' => [
                    'email' => ['type' => 'email'],
                    'first_name' => ['type' => 'firstName'],
                    'last_name' => ['type' => 'lastName'],
                ],
            ],
        ]]);

        // Clear exclude tables to make sure they don't affect our test
        config(['database-masker.exclude_tables' => []]);

        // Create a fresh instance of the DatabaseMasker to ensure it uses the updated config
        $databaseMasker = $this->app->make('database-masker');

        // Debug what tables the DatabaseMasker sees
        $defaultConnection = config('database.default');
        $driver = $this->app->make(\Hristijans\DatabaseMasker\Services\Factories\DatabaseDriverFactory::class)
            ->createDriver(DB::connection($defaultConnection), $defaultConnection);
        $allTables = $driver->getTables([]);


        // Get the config the DatabaseMasker will use
        $configuredTables = config('database-masker.tables');

        // Create the masked dump
        $outputFile = tempnam(sys_get_temp_dir(), 'db_mask_test_');
        $dumpFile = $databaseMasker->createMaskedDump($outputFile);

        $this->assertFileExists($dumpFile);

        // Read file content
        $content = file_get_contents($dumpFile);


        // Search for specific table markers in the content
        $hasUsersTable = strpos($content, 'test_users') !== false;
        $hasCustomersTable = strpos($content, 'test_customers') !== false;


        // Now run the actual assertions
        $this->assertStringContainsString('test_users', $content, 'test_users table not found in dump');
        $this->assertStringContainsString('test_customers', $content, 'test_customers table not found in dump');

        // Clean up
        @unlink($dumpFile);
    }

    /**
     * Test the multi-database functionality.
     */
    public function test_multi_database_support(): void
    {
        // Configure multi-db settings
        config(['database-masker.connections' => [
            'testing' => [
                'tables' => [
                    'test_users' => [
                        'columns' => [
                            'email' => ['type' => 'email'],
                            'name' => ['type' => 'name'],
                        ],
                    ],
                ],
                'exclude_tables' => [],
                'output_file' => 'masked_database_testing.sql',
            ],
            'second_db' => [
                'tables' => [
                    'test_customers' => [
                        'columns' => [
                            'email' => ['type' => 'email'],
                            'first_name' => ['type' => 'firstName'],
                            'last_name' => ['type' => 'lastName'],
                        ],
                    ],
                ],
                'exclude_tables' => [],
                'output_file' => 'masked_database_second_db.sql',
            ],
        ]]);

        // Create masked dumps for all connections
        $outputPath = $this->app->storagePath('app');
        $results = $this->databaseMasker->createMaskedDumps($outputPath);

        // Check results array structure
        $this->assertArrayHasKey('testing', $results);
        $this->assertArrayHasKey('second_db', $results);

        // Check each connection result
        foreach (['testing', 'second_db'] as $connection) {
            $result = $results[$connection];

            $this->assertEquals('success', $result['status']);
            $this->assertEquals($connection, $result['connection']);
            $this->assertTrue(isset($result['output_file']));
            $this->assertFileExists($result['output_file']);

            // Content checks
            $content = file_get_contents($result['output_file']);

            if ($connection === 'testing') {
                $this->assertStringContainsString('test_users', $content);
                $this->assertStringNotContainsString('user@testing.test', $content);
            } else {
                $this->assertStringContainsString('test_customers', $content);
                $this->assertStringNotContainsString('customer@second_db.test', $content);
            }
        }
    }

    /**
     * Test creating a masked dump for a specific connection.
     */
    public function test_create_masked_dump_for_connection(): void
    {
        $connectionName = 'testing';
        $connectionConfig = [
            'tables' => [
                'test_users' => [
                    'columns' => [
                        'email' => ['type' => 'email'],
                        'name' => ['type' => 'name'],
                    ],
                ],
            ],
            'exclude_tables' => [],
        ];

        $outputFile = $this->app->storagePath('app/masked_specific_connection.sql');

        $result = $this->databaseMasker->createMaskedDumpForConnection(
            $connectionName,
            $connectionConfig,
            $outputFile
        );

        $this->assertEquals('success', $result['status']);
        $this->assertEquals($connectionName, $result['connection']);
        $this->assertEquals($outputFile, $result['output_file']);
        $this->assertFileExists($result['output_file']);

        // Content checks
        $content = file_get_contents($result['output_file']);
        $this->assertStringContainsString('test_users', $content);
        $this->assertStringNotContainsString('user@testing.test', $content);

        // Clean up
        @unlink($outputFile);
    }

    /**
     * Test the getConfiguredConnections method.
     */
    public function test_get_configured_connections(): void
    {
        // First, explicitly set the configuration
        $this->app['config']->set('database-masker.connections', [
            'testing' => ['tables' => []],
            'second_db' => ['tables' => []],
        ]);

        // Double-check the config is set correctly
        $connections = config('database-masker.connections');
        $this->assertIsArray($connections);
        $this->assertArrayHasKey('testing', $connections);
        $this->assertArrayHasKey('second_db', $connections);


        // Now get the connections using the actual method
        $result = $this->invokeMethod($this->databaseMasker, 'getConfiguredConnections', []);


        // Assertions
        $this->assertArrayHasKey('testing', $result);
        $this->assertArrayHasKey('second_db', $result);
    }
}
