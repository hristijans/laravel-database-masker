<?php

namespace Hristijans\DatabaseMasker\Tests;

use Hristijans\DatabaseMasker\Facades\DatabaseMasker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseMaskerTest extends TestCase
{
    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestTables();
        $this->seedTestData();
    }

    /**
     * Create test tables.
     *
     * @return void
     */
    protected function createTestTables()
    {
        Schema::create('test_users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('test_customers', function ($table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone_number')->nullable();
            $table->text('address')->nullable();
            $table->string('credit_card_number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Seed test data.
     *
     * @return void
     */
    protected function seedTestData()
    {
        DB::table('test_users')->insert([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '123-456-7890',
            'address' => '123 Main St, Anytown, USA',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('test_customers')->insert([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'phone_number' => '098-765-4321',
            'address' => '456 Oak Dr, Somewhere, USA',
            'credit_card_number' => '4111111111111111',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Clean up test tables.
     */
    protected function tearDown(): void
    {
        Schema::dropIfExists('test_users');
        Schema::dropIfExists('test_customers');

        parent::tearDown();
    }

    /**
     * Test the service provider is loaded.
     *
     * @return void
     */
    public function test_service_provider_is_loaded()
    {
        $this->assertTrue($this->app->bound('database-masker'));
    }

    /**
     * Test the configuration is published.
     *
     * @return void
     */
    public function test_configuration_is_published()
    {
        $this->assertTrue(is_array(config('database-masker')));
    }

    /**
     * Test getting database tables.
     *
     * @return void
     */
    public function test_get_tables()
    {
        $tables = $this->invokeMethod(app('database-masker'), 'getTables', []);
        $this->assertContains('test_users', $tables);
        $this->assertContains('test_customers', $tables);
    }

    /**
     * Test masking specific values.
     *
     * @return void
     */
    public function test_mask_value()
    {
        $masker = app('database-masker');

        $email = $this->invokeMethod($masker, 'maskValue', ['test@example.com', ['type' => 'email']]);
        $this->assertNotEquals('test@example.com', $email);
        $this->assertStringContainsString('@', $email);

        $name = $this->invokeMethod($masker, 'maskValue', ['John Doe', ['type' => 'name']]);
        $this->assertNotEquals('John Doe', $name);
        $this->assertIsString($name);

        $number = $this->invokeMethod($masker, 'maskValue', [123, ['type' => 'randomNumber', 'min' => 1, 'max' => 1000]]);
        $this->assertIsNumeric($number);
        $this->assertGreaterThanOrEqual(1, $number);
        $this->assertLessThanOrEqual(1000, $number);
    }

    /**
     * Call protected/private method of a class.
     *
     * @param  object  $object
     * @param  string  $methodName
     * @return mixed
     */
    public function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Test creating a masked dump.
     *
     * @return void
     */
    public function test_create_masked_dump()
    {
        config(['database-masker.tables' => [
            'test_users' => [
                'columns' => [
                    'email' => ['type' => 'email'],
                    'name' => ['type' => 'name'],
                    'phone' => ['type' => 'phone'],
                    'address' => ['type' => 'address'],
                ],
            ],
            'test_customers' => [
                'columns' => [
                    'email' => ['type' => 'email'],
                    'first_name' => ['type' => 'firstName'],
                    'last_name' => ['type' => 'lastName'],
                    'phone_number' => ['type' => 'phone'],
                    'address' => ['type' => 'address'],
                    'credit_card_number' => ['type' => 'creditCardNumber'],
                ],
            ],
        ]]);

        $outputFile = tempnam(sys_get_temp_dir(), 'db_mask_test_');
        $dumpFile = DatabaseMasker::createMaskedDump($outputFile);

        $this->assertFileExists($dumpFile);
        $this->assertStringContainsString('test_users', file_get_contents($dumpFile));
        $this->assertStringContainsString('test_customers', file_get_contents($dumpFile));

        // Clean up
        @unlink($dumpFile);
    }
}
