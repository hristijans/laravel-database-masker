# Laravel Database Masker

A Laravel package for creating masked database dumps with sensitive data obfuscated, following modern PHP 8.3 best practices. Perfect for providing realistic data to developers without exposing confidential information.

## Features

- **Selective Column Masking**: Configure which columns in which tables contain sensitive data
- **Type-Aware Masking**: Automatically generates appropriate fake data based on column types
- **Multiple Database Support**: Process multiple database connections at once
- **Modern Architecture**: Uses design patterns (Strategy, Factory, Template) for maintainable, extensible code
- **PHP 8.3 Ready**: Leverages the latest PHP features with strict typing
- **Database Agnostic**: Supports MySQL, PostgreSQL, and SQLite
- **Relationship Preservation**: Maintains database relationships and integrity
- **Performance Optimized**: Processes large databases efficiently with batching
- **Developer-Friendly**: Easy to use Artisan commands for creating and restoring masked dumps

## Requirements

- PHP 8.3+
- Laravel 10.0+

## Installation

You can install the package via composer:

```bash
composer require hristijans/laravel-database-masker
```

After installing the package, publish the configuration file:

```bash
php artisan vendor:publish --provider="Hristijans\DatabaseMasker\DatabaseMaskerServiceProvider" --tag="config"
```

## Configuration

Edit the published configuration file at `config/database-masker.php` to define which tables and columns contain sensitive data that should be masked.

### Single Database Configuration

For simple projects with a single database:

```php
return [
    'tables' => [
        'users' => [
            'columns' => [
                'email' => ['type' => 'email'],
                'name' => ['type' => 'name'],
                'phone' => ['type' => 'phone'],
                // Add more columns as needed
            ],
        ],
        // Add more tables as needed
    ],
    
    'exclude_tables' => [
        'migrations',
        'failed_jobs',
        // Add more tables to exclude
    ],
    
    // Global configuration
    'preserve_primary_keys' => true,
    'preserve_foreign_keys' => true,
    'batch_size' => 1000,
];
```

### Multiple Database Configuration

For projects with multiple database connections:

```php
return [
    'connections' => [
        'mysql' => [
            'tables' => [
                'users' => [
                    'columns' => [
                        'email' => ['type' => 'email'],
                        'name' => ['type' => 'name'],
                    ],
                ],
            ],
            'exclude_tables' => ['migrations', 'failed_jobs'],
            'output_file' => 'masked_mysql.sql',
        ],
        'customer_db' => [
            'tables' => [
                'customers' => [
                    'columns' => [
                        'email' => ['type' => 'email'],
                        'first_name' => ['type' => 'firstName'],
                        'last_name' => ['type' => 'lastName'],
                    ],
                ],
            ],
            'exclude_tables' => ['migrations'],
            'output_file' => 'masked_customer_db.sql',
        ],
    ],
    
    // Global configuration
    'preserve_primary_keys' => true,
    'preserve_foreign_keys' => true,
    'batch_size' => 1000,
    'output_path' => storage_path('app'),
];
```

## Available Mask Types

The package supports various mask types for different kinds of data:

| Mask Type | Description | Additional Options |
|-----------|-------------|-------------------|
| `email` | Replaces with a fake email address | - |
| `name` | Replaces with a fake full name | - |
| `firstName` | Replaces with a fake first name | - |
| `lastName` | Replaces with a fake last name | - |
| `phone` | Replaces with a fake phone number | `format`: e.g., '###-###-####' |
| `address` | Replaces with a fake address | - |
| `city` | Replaces with a fake city name | - |
| `country` | Replaces with a fake country name | - |
| `postcode` | Replaces with a fake postal/zip code | - |
| `text` | Replaces with random text | `length`: Maximum length |
| `randomNumber` | Replaces with a random number | `min`, `max`: Range limits |
| `date` | Replaces with a random date | `format`: Date format (default: Y-m-d) |
| `datetime` | Replaces with a random datetime | `format`: Date format (default: Y-m-d H:i:s) |
| `numerify` | Replaces with a pattern of numbers | `format`: e.g., '###-##-####' |
| `lexify` | Replaces with random letters | `format`: e.g., '????' |
| `bothify` | Mix of numbers and letters | `format`: e.g., '##??' |
| `regexify` | Based on regex pattern | `regex`: Regular expression pattern |
| `creditCardNumber` | Fake credit card number | - |
| `company` | Fake company name | - |
| `url` | Fake URL | - |
| `ipv4` | Fake IPv4 address | - |
| `ipv6` | Fake IPv6 address | - |
| `uuid` | Random UUID | - |
| `password` | Bcrypt hash of random password | - |

## Usage

### Single Database

#### Creating a Masked Database Dump

To create a masked database dump:

```bash
php artisan db:mask-dump
```

By default, the dump will be saved to `storage/app/masked_database.sql`. You can specify a custom output file:

```bash
php artisan db:mask-dump --output=/path/to/output.sql
```

#### Creating and Restoring a Masked Database in One Step

To create a masked database dump and immediately restore it to your local database:

```bash
php artisan db:mask-restore
```

This will create a masked dump and then restore it to your database, effectively replacing your current database with the masked version.

You can also restore an existing masked dump:

```bash
php artisan db:mask-restore --no-dump --input=/path/to/masked_dump.sql
```

To skip the confirmation prompt, use the `--force` option:

```bash
php artisan db:mask-restore --force
```

### Multiple Databases

#### Creating Masked Database Dumps for All Configured Connections

To create masked dumps for all configured database connections:

```bash
php artisan db:mask-dump
```

This will generate a separate SQL file for each connection, with the filenames specified in your configuration.

#### Creating a Masked Database Dump for a Specific Connection

To create a masked dump for a specific database connection:

```bash
php artisan db:mask-dump --connection=customer_db
```

#### Specifying an Output Directory

You can specify a custom output directory for all dump files:

```bash
php artisan db:mask-dump --output-path=/path/to/directory
```

#### Restoring a Masked Database to a Specific Connection

To restore a masked dump to a specific connection:

```bash
php artisan db:mask-restore --connection=customer_db
```

### Using a Custom Configuration File

You can specify a custom configuration file for one-off operations:

```bash
php artisan db:mask-dump --config=/path/to/custom-config.php
```

## Extending the Package

### Adding Custom Mask Types

You can extend the package with your own masking strategies:

```php
use Hristijans\DatabaseMasker\Contracts\ValueMaskerInterface;
use Faker\Factory as FakerFactory;

class MyCustomMasker implements ValueMaskerInterface
{
    private $faker;
    
    public function __construct()
    {
        $this->faker = FakerFactory::create();
    }
    
    public function canHandle(string $type): bool
    {
        return $type === 'my_custom_type';
    }
    
    public function mask(mixed $originalValue, array $columnConfig): mixed
    {
        // Your custom masking logic here
        return "masked_{$originalValue}";
    }
}
```

Then register it with the MaskerStrategyFactory:

```php
// In a service provider
use Hristijans\DatabaseMasker\Services\Factories\MaskerStrategyFactory;

public function boot(): void
{
    $this->app->make(MaskerStrategyFactory::class)
        ->registerMasker(new MyCustomMasker());
}
```

### Supporting Additional Database Types

To add support for additional database types, implement the `DatabaseDriverInterface` and update the `DatabaseDriverFactory`.

## Example Workflow for New Developers

When a new developer joins the team:

1. The senior developer runs `php artisan db:mask-dump` on production (or a copy)
2. The masked SQL dump(s) are provided to the new developer
3. The new developer imports the masked dump(s) into their local environment
4. The new developer can now work with realistic data without seeing confidential information

## Using with Large Databases

For large databases, you may want to adjust the `batch_size` in the configuration to process records in smaller batches:

```php
'batch_size' => 500, // Process 500 records at a time
```

This helps to reduce memory usage when processing large tables.

## Testing

```bash
composer test
```

## Security

If you discover any security issues, please email kde.chris@gmail.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
