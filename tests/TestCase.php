<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Tests;

use Hristijans\DatabaseMasker\DatabaseMaskerServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            DatabaseMaskerServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Add a second connection for multi-db testing
        $app['config']->set('database.connections.second_db', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set up the base config
        $app['config']->set('database-masker', [
            'tables' => [
                'test_users' => [
                    'columns' => [
                        'email' => ['type' => 'email'],
                        'name' => ['type' => 'name'],
                        'phone' => ['type' => 'phone'],
                    ],
                ],
            ],
            'exclude_tables' => [
                'migrations',
            ],
            'batch_size' => 100,
        ]);
    }

    /**
     * Set up the database schema for both connections.
     */
    protected function setUpDatabase(): void
    {
        // Drop existing tables first to avoid conflicts
        $this->dropTestTables('testing');
        $this->dropTestTables('second_db');

        // Setup schema for primary connection
        $this->setupTestTablesForConnection('testing');

        // Setup schema for secondary connection
        $this->setupTestTablesForConnection('second_db');

        // Debug to ensure connections are properly initialized
        $this->assertTrue($this->app['db']->connection('testing')->getSchemaBuilder()->hasTable('test_users'),
            'test_users table not found in testing connection');
        $this->assertTrue($this->app['db']->connection('second_db')->getSchemaBuilder()->hasTable('test_customers'),
            'test_customers table not found in second_db connection');
    }

    protected function dropTestTables(string $connection): void
    {
        $schema = $this->app['db']->connection($connection)->getSchemaBuilder();

        // Drop tables in reverse order to handle foreign key constraints
        if ($schema->hasTable('test_customers')) {
            $schema->drop('test_customers');
        }

        if ($schema->hasTable('test_users')) {
            $schema->drop('test_users');
        }
    }

    /**
     * Set up test tables for a specific connection.
     */
    protected function setupTestTablesForConnection(string $connection): void
    {
        $this->app['db']->connection($connection)->getSchemaBuilder()->create('test_users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('password');
            $table->timestamps();
        });

        $this->app['db']->connection($connection)->getSchemaBuilder()->create('test_customers', function ($table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone_number')->nullable();
            $table->text('address')->nullable();
            $table->string('credit_card_number')->nullable();
            $table->timestamps();
        });

        // Seed some test data
        $this->app['db']->connection($connection)->table('test_users')->insert([
            'name' => "Test User ({$connection})",
            'email' => "user@{$connection}.test",
            'phone' => '123-456-7890',
            'address' => '123 Test St',
            'password' => password_hash('password', PASSWORD_BCRYPT),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->app['db']->connection($connection)->table('test_customers')->insert([
            'first_name' => 'Jane',
            'last_name' => "Doe ({$connection})",
            'email' => "customer@{$connection}.test",
            'phone_number' => '098-765-4321',
            'address' => '456 Test Ave',
            'credit_card_number' => '4111111111111111',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Invoke a protected method on an object.
     *
     * @param  array<int, mixed>  $parameters
     */
    protected function invokeMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new \ReflectionClass($object::class);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Access a protected property on an object.
     */
    protected function getProtectedProperty(object $object, string $propertyName): mixed
    {
        $reflection = new \ReflectionClass($object::class);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    /**
     * Set a protected property on an object.
     */
    protected function setProtectedProperty(object $object, string $propertyName, mixed $value): void
    {
        $reflection = new \ReflectionClass($object::class);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
