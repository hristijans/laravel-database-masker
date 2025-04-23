<?php

namespace Hristijans\DatabaseMasker;

use Faker\Factory as FakerFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Exception;

class DatabaseMasker
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The Faker instance.
     *
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * The configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * The database connection.
     *
     * @var \Illuminate\Database\Connection
     */
    protected $connection;

    /**
     * The temporary SQL file path.
     *
     * @var string
     */
    protected $tempSqlFile;

    /**
     * Create a new DatabaseMasker instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
        $this->faker = FakerFactory::create();
        $this->config = config('database-masker');
        $this->connection = DB::connection();
        $this->tempSqlFile = storage_path('app/masked_database.sql');
    }

    /**
     * Create a masked database dump.
     *
     * @param  string|null  $outputFile
     * @return string
     */
    public function createMaskedDump($outputFile = null)
    {
        if (!$outputFile) {
            $outputFile = $this->tempSqlFile;
        }
        
        // Start the SQL file with drop and recreate statements
        $this->initSqlFile($outputFile);
        
        // Get all tables excluding the ones in the exclude list
        $tables = $this->getTables();
        
        foreach ($tables as $table) {
            $this->processMaskTable($table, $outputFile);
        }
        
        // Add foreign key checks back
        file_put_contents($outputFile, "\nSET FOREIGN_KEY_CHECKS=1;\n", FILE_APPEND);
        
        return $outputFile;
    }
    
    /**
     * Restore the masked database dump.
     *
     * @param  string|null  $inputFile
     * @return bool
     * @throws \Exception
     */
    public function restoreMaskedDump($inputFile = null)
    {
        if (!$inputFile) {
            $inputFile = $this->tempSqlFile;
        }
        
        if (!file_exists($inputFile)) {
            throw new Exception("Masked database dump file not found: {$inputFile}");
        }
        
        // Execute the SQL file
        $dbConfig = config('database.connections.' . config('database.default'));
        
        $command = sprintf(
            'mysql -h%s -u%s -p%s %s < %s',
            escapeshellarg($dbConfig['host']),
            escapeshellarg($dbConfig['username']),
            escapeshellarg($dbConfig['password']),
            escapeshellarg($dbConfig['database']),
            escapeshellarg($inputFile)
        );
        
        $output = null;
        $returnVar = null;
        
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            throw new Exception("Failed to restore database: " . implode("\n", $output));
        }
        
        return true;
    }
    
    /**
     * Process and mask a single table.
     *
     * @param  string  $table
     * @param  string  $outputFile
     * @return void
     */
    protected function processMaskTable($table, $outputFile)
    {
        // Skip if in exclude list
        if (in_array($table, $this->config['exclude_tables'] ?? [])) {
            return;
        }
        
        // Get table structure
        $createTableSql = $this->getCreateTableSql($table);
        file_put_contents($outputFile, $createTableSql . "\n\n", FILE_APPEND);
        
        // Process data in batches
        $batchSize = $this->config['batch_size'] ?? 1000;
        $totalRows = DB::table($table)->count();
        
        if ($totalRows === 0) {
            return;
        }
        
        $batches = ceil($totalRows / $batchSize);
        
        for ($i = 0; $i < $batches; $i++) {
            $offset = $i * $batchSize;
            $records = DB::table($table)->offset($offset)->limit($batchSize)->get();
            
            if ($records->isEmpty()) {
                continue;
            }
            
            $insertSql = $this->generateInsertSql($table, $records);
            file_put_contents($outputFile, $insertSql . "\n\n", FILE_APPEND);
        }
    }
    
    /**
     * Generate SQL INSERT statements with masked data.
     *
     * @param  string  $table
     * @param  \Illuminate\Support\Collection  $records
     * @return string
     */
    protected function generateInsertSql($table, $records)
    {
        $columns = Schema::getColumnListing($table);
        $tableConfig = $this->config['tables'][$table] ?? null;
        $maskColumns = $tableConfig['columns'] ?? [];
        
        $insertSql = "INSERT INTO `{$table}` (`" . implode("`, `", $columns) . "`) VALUES\n";
        $valuesSql = [];
        
        foreach ($records as $record) {
            $values = [];
            
            foreach ($columns as $column) {
                $value = $record->$column;
                
                // Determine if this column should be masked
                if (isset($maskColumns[$column])) {
                    $value = $this->maskValue($value, $maskColumns[$column]);
                }
                
                // Format value for SQL
                $values[] = $this->formatSqlValue($value);
            }
            
            $valuesSql[] = "(" . implode(", ", $values) . ")";
        }
        
        return $insertSql . implode(",\n", $valuesSql) . ";";
    }
    
    /**
     * Mask a value based on configuration.
     *
     * @param  mixed  $originalValue
     * @param  array  $columnConfig
     * @return mixed
     */
    protected function maskValue($originalValue, $columnConfig)
    {
        $type = $columnConfig['type'] ?? 'text';
        
        switch ($type) {
            case 'email':
                return $this->faker->safeEmail();
            
            case 'name':
                return $this->faker->name();
            
            case 'firstName':
                return $this->faker->firstName();
                
            case 'lastName':
                return $this->faker->lastName();
            
            case 'phone':
                return $this->faker->phoneNumber();
            
            case 'address':
                return $this->faker->address();

            case 'city':
                return $this->faker->city();
                
            case 'country':
                return $this->faker->country();
                
            case 'postcode':
                return $this->faker->postcode();
            
            case 'text':
                $length = $columnConfig['length'] ?? 100;
                return $this->faker->text($length);
            
            case 'number':
            case 'randomNumber':
                $min = $columnConfig['min'] ?? 1;
                $max = $columnConfig['max'] ?? 1000;
                return $this->faker->numberBetween($min, $max);
            
            case 'date':
                $format = $columnConfig['format'] ?? 'Y-m-d';
                return $this->faker->date($format);
            
            case 'datetime':
                $format = $columnConfig['format'] ?? 'Y-m-d H:i:s';
                return $this->faker->dateTime()->format($format);
            
            case 'numerify':
                $format = $columnConfig['format'] ?? '###';
                return $this->faker->numerify($format);
            
            case 'lexify':
                $format = $columnConfig['format'] ?? '????';
                return $this->faker->lexify($format);
            
            case 'bothify':
                $format = $columnConfig['format'] ?? '##??';
                return $this->faker->bothify($format);
            
            case 'regexify':
                $regex = $columnConfig['regex'] ?? '[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}';
                return $this->faker->regexify($regex);
                
            case 'creditCardNumber':
                return $this->faker->creditCardNumber();
                
            case 'company':
                return $this->faker->company();
                
            case 'url':
                return $this->faker->url();
                
            case 'ipv4':
                return $this->faker->ipv4();
                
            case 'ipv6':
                return $this->faker->ipv6();
                
            case 'uuid':
                return $this->faker->uuid();
                
            case 'password':
                // Returns a bcrypt hash of a random password
                return password_hash($this->faker->password(), PASSWORD_BCRYPT);
                
            default:
                return $this->faker->text(50);
        }
    }
    
    /**
     * Format a value for SQL.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function formatSqlValue($value)
    {
        if ($value === null) {
            return 'NULL';
        }
        
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        
        if (is_numeric($value)) {
            return $value;
        }
        
        // Escape single quotes and other special characters
        return "'" . addslashes($value) . "'";
    }
    
    /**
     * Get CREATE TABLE statement for a table.
     *
     * @param  string  $table
     * @return string
     */
    protected function getCreateTableSql($table)
    {
        $result = DB::select("SHOW CREATE TABLE `{$table}`");
        $createTableSql = $result[0]->{'Create Table'} ?? $result[0]->{'Create View'};
        
        return "DROP TABLE IF EXISTS `{$table}`;\n{$createTableSql};";
    }
    
    /**
     * Initialize the SQL file with header.
     *
     * @param  string  $outputFile
     * @return void
     */
    protected function initSqlFile($outputFile)
    {
        $header = "-- Database Masked Dump\n";
        $header .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
        $header .= "-- By: Laravel Database Masker\n\n";
        $header .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        file_put_contents($outputFile, $header);
    }
    
    /**
     * Get all database tables.
     *
     * @return array
     */
    protected function getTables()
    {
        $tables = [];
        $excludeTables = $this->config['exclude_tables'] ?? [];
        
        $dbName = $this->connection->getDatabaseName();
        $rawTables = $this->connection->select("SHOW TABLES");
        
        $tableNameKey = "Tables_in_{$dbName}";
        foreach ($rawTables as $tableObj) {
            $tableName = $tableObj->$tableNameKey;
            if (!in_array($tableName, $excludeTables)) {
                $tables[] = $tableName;
            }
        }
        
        return $tables;
    }
    
    /**
     * Get column type from database schema.
     * 
     * @param  string  $table
     * @param  string  $column
     * @return string
     */
    protected function getColumnType($table, $column)
    {
        $schema = DB::connection()->getDoctrineSchemaManager();
        $columns = $schema->listTableColumns($table);
        
        if (!isset($columns[$column])) {
            return 'string';
        }
        
        $doctrineType = $columns[$column]->getType()->getName();
        
        // Map Doctrine type to a simpler type
        $typeMap = [
            'string' => 'string',
            'text' => 'text',
            'integer' => 'integer',
            'smallint' => 'integer',
            'bigint' => 'integer',
            'boolean' => 'boolean',
            'decimal' => 'float',
            'float' => 'float',
            'date' => 'date',
            'datetime' => 'datetime',
            'datetimetz' => 'datetime',
            'time' => 'time',
            'array' => 'array',
            'json' => 'json',
        ];
        
        return $typeMap[$doctrineType] ?? 'string';
    }
}