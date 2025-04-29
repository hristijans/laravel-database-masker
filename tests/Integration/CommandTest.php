<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Tests\Integration;

use Hristijans\DatabaseMasker\Tests\TestCase;
use Illuminate\Support\Facades\File;

class CommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();

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
                'output_file' => 'masked_testing_cmd.sql',
            ],
            'second_db' => [
                'tables' => [
                    'test_customers' => [
                        'columns' => [
                            'email' => ['type' => 'email'],
                            'first_name' => ['type' => 'firstName'],
                        ],
                    ],
                ],
                'output_file' => 'masked_second_db_cmd.sql',
            ],
        ]]);

        // Ensure the storage directory exists
        $storageDir = $this->app->storagePath('app');
        if (!File::exists($storageDir)) {
            File::makeDirectory($storageDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Get the correct storage path
        $storagePath = $this->app->storagePath('app');

        // Clean up any test files
        $files = [
            "{$storagePath}/masked_testing_cmd.sql",
            "{$storagePath}/masked_second_db_cmd.sql",
            "{$storagePath}/custom_output.sql",
        ];

        foreach ($files as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        parent::tearDown();
    }


    /**
     * Test the db:mask-dump command with all connections.
     */
    public function testMaskDumpCommandForAllConnections(): void
    {
        // Correct storage path
        $storagePath = $this->app->storagePath('app');

        // Run the command
        $this->artisan('db:mask-dump')
            ->expectsOutput('Creating masked database dumps for all configured connections...')
            ->assertExitCode(0);

        // Check that both files were created
        $this->assertFileExists("{$storagePath}/masked_testing_cmd.sql");
        $this->assertFileExists("{$storagePath}/masked_second_db_cmd.sql");

        // Check file contents
        $testingContent = file_get_contents("{$storagePath}/masked_testing_cmd.sql");
        $this->assertStringContainsString('test_users', $testingContent);
        $this->assertStringNotContainsString('user@testing.test', $testingContent);

        $secondDbContent = file_get_contents("{$storagePath}/masked_second_db_cmd.sql");
        $this->assertStringContainsString('test_customers', $secondDbContent);
        $this->assertStringNotContainsString('customer@second_db.test', $secondDbContent);
    }

    /**
     * Test the db:mask-dump command with a specific connection.
     */
    public function testMaskDumpCommandForSingleConnection(): void
    {
        // Ensure the output directory exists
        $storagePath = $this->app->storagePath('app');
        if (!File::exists($storagePath)) {
            File::makeDirectory($storagePath, 0755, true);
        }

        // Create an absolute path for the output file
        $outputFile = $storagePath . '/masked_second_db_test.sql';

        // Run the command with explicit output path
        $this->artisan('db:mask-dump', [
            '--connection' => 'second_db',
            '--output' => $outputFile
        ])->assertExitCode(0);



        // Look in parent directories too
        $parentPath = dirname($storagePath);

        $parentFiles = File::files($parentPath);


        // Check if the file exists
        $this->assertFileExists($outputFile);

        // If file exists, check content
        if (File::exists($outputFile)) {
            $content = file_get_contents($outputFile);
            $this->assertStringContainsString('test_customers', $content);
        }
    }

    /**
     * Test the db:mask-dump command with a custom output path.
     */
    public function testMaskDumpCommandWithCustomOutput(): void
    {
        // Correct storage path
        $storagePath = $this->app->storagePath('app');
        $customOutput = "{$storagePath}/custom_output.sql";

        // Run the command with custom output
        $this->artisan('db:mask-dump', [
            '--connection' => 'testing',
            '--output' => $customOutput
        ])
            ->assertExitCode(0);

        // Check that the file was created in the custom location
        $this->assertFileExists($customOutput);
    }

    /**
     * Test the db:mask-dump command with a non-existent connection.
     */
    public function test_mask_dump_command_with_non_existent_connection(): void
    {
        $this->artisan('db:mask-dump', ['--connection' => 'non_existent'])
            ->expectsOutput("Connection 'non_existent' not found in configuration.")
            ->assertExitCode(1);
    }

    /**
     * Test the db:mask-dump command with a non-existent config file.
     */
    public function test_mask_dump_command_with_non_existent_config(): void
    {
        $this->artisan('db:mask-dump', ['--config' => 'non_existent.php'])
            ->expectsOutput('Config file not found: non_existent.php')
            ->assertExitCode(1);
    }

    /**
     * Test the db:mask-restore command with force flag.
     */
    public function test_mask_restore_command_with_force(): void
    {
        // Create a masked dump file first
        $this->artisan('db:mask-dump', ['--connection' => 'testing'])
            ->assertExitCode(0);

        // SQLite can't easily be restored in-memory, so we're just testing that the command runs
        // without error up to the point where it would try to restore the database
        $this->artisan('db:mask-restore', [
            '--connection' => 'testing',
            '--no-dump' => true,
            '--force' => true,
        ])
            ->expectsOutput('Restoring masked database to connection: testing...')
            // We expect this to fail because SQLite in-memory can't be restored
            ->assertExitCode(1);
    }

    /**
     * Test the db:mask-restore command with non-existent input file.
     */
    public function test_mask_restore_command_with_non_existent_file(): void
    {
        $this->artisan('db:mask-restore', [
            '--input' => 'non_existent_file.sql',
            '--no-dump' => true,
        ])
            ->expectsOutput('Input file not found: non_existent_file.sql')
            ->assertExitCode(1);
    }
}
