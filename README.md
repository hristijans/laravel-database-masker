# Laravel Database Masker


A Laravel package for creating masked database dumps with sensitive data obfuscated. Perfect for providing realistic data to developers without exposing confidential information.

## Features

- **Selective Column Masking**: Configure which columns in which tables contain sensitive data
- **Type-Aware Masking**: Automatically generates appropriate fake data based on column types
- **Relationship Preservation**: Maintains database relationships and integrity
- **Configurable**: Highly customizable with simple configuration
- **Performance Optimized**: Processes large databases efficiently with batching
- **Developer-Friendly**: Easy to use Artisan commands for creating and restoring masked dumps

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

Edit the published configuration file at `config/database-masker.php` to define which tables and columns contain sensitive data that should be masked:

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

## Available Mask Types

The package supports various mask types for different kinds of data:

| Mask Type | Description | Additional Options |
|-----------|-------------|-------------------|
| `email` | Replaces with a fake email address | - |
| `name` | Replaces with a fake full name | - |
| `firstName` | Replaces with a fake first name | - |
| `lastName` | Replaces with a fake last name | - |
| `phone` | Replaces with a fake phone number | - |
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

### Creating a Masked Database Dump

To create a masked database dump:

```bash
php artisan db:mask-dump
```

By default, the dump will be saved to `storage/app/masked_database.sql`. You can specify a custom output file:

```bash
php artisan db:mask-dump --output=/path/to/output.sql
```

### Creating and Restoring a Masked Database in One Step

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

### Using a Custom Configuration File

You can specify a custom configuration file for one-off operations:

```bash
php artisan db:mask-dump --config=/path/to/custom-config.php
```

## Example Workflow for New Developers

When a new developer joins the team:

1. The senior developer runs `php artisan db:mask-dump` on production (or a copy)
2. The masked SQL dump is provided to the new developer
3. The new developer imports the masked dump into their local environment
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

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Security

If you discover any security-related issues, please email kde.chris@gmail.com instead of using the issue tracker.

## Credits

- [Hristijan Stojanoski](https://github.com/hristijans)


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
