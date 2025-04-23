<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tables Configuration
    |--------------------------------------------------------------------------
    |
    | Define which tables and columns contain confidential data that should be
    | masked when creating a database dump for development or testing purposes.
    |
    | For each table, specify the columns that need masking and the type of
    | masking to apply.
    |
    */
    'tables' => [
        'users' => [
            'columns' => [
                'email' => ['type' => 'email'],
                'name' => ['type' => 'name'],
                'phone' => ['type' => 'phone'],
                'address' => ['type' => 'address'],
                'password' => ['type' => 'password'],
            ],
        ],
        
        // Example for customers table
        'customers' => [
            'columns' => [
                'email' => ['type' => 'email'],
                'first_name' => ['type' => 'firstName'],
                'last_name' => ['type' => 'lastName'],
                'phone_number' => ['type' => 'phone'],
                'address' => ['type' => 'address'],
                'city' => ['type' => 'city'],
                'country' => ['type' => 'country'],
                'postal_code' => ['type' => 'postcode'],
                'notes' => ['type' => 'text', 'length' => 200],
            ],
        ],
        
        // Example for payments table
        'payments' => [
            'columns' => [
                'card_number' => ['type' => 'creditCardNumber'],
                'card_holder' => ['type' => 'name'],
                'amount' => ['type' => 'randomNumber', 'min' => 10, 'max' => 1000],
                'transaction_id' => ['type' => 'bothify', 'format' => '??####??####'],
            ],
        ],
        
        // Add more tables as needed
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Excluded Tables
    |--------------------------------------------------------------------------
    |
    | List of tables that should be excluded from the masked database dump.
    | Usually system tables, migrations, or tables without sensitive data.
    |
    */
    'exclude_tables' => [
        'migrations',
        'failed_jobs',
        'password_resets',
        'personal_access_tokens',
        'jobs',
        'sessions',
        'telescope_entries',
        'telescope_entries_tags',
        'telescope_monitoring',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Global Configuration
    |--------------------------------------------------------------------------
    |
    | Global settings for the database masking process.
    |
    */
    'preserve_primary_keys' => true,  // Keep the original primary key values
    'preserve_foreign_keys' => true,  // Keep foreign key relationships intact
    'batch_size' => 1000,            // Number of records to process at once
    
    /*
    |--------------------------------------------------------------------------
    | Available Mask Types
    |--------------------------------------------------------------------------
    |
    | Here's a list of all available mask types you can use in your configuration:
    |
    | - email: Replaces with a fake email address
    | - name: Replaces with a fake name
    | - firstName: Replaces with a fake first name
    | - lastName: Replaces with a fake last name
    | - phone: Replaces with a fake phone number
    | - address: Replaces with a fake address
    | - city: Replaces with a fake city
    | - country: Replaces with a fake country
    | - postcode: Replaces with a fake postcode/zip
    | - text: Replaces with random text (specify length)
    | - number/randomNumber: Replaces with random number (specify min/max)
    | - date: Replaces with random date (specify format)
    | - datetime: Replaces with random datetime (specify format)
    | - numerify: Replaces with a pattern (e.g., '###-##-####')
    | - lexify: Replaces with random letters (e.g., '????')
    | - bothify: Replaces with mix of numbers and letters (e.g., '##??')
    | - regexify: Replaces with a regex pattern
    | - creditCardNumber: Replaces with fake credit card number
    | - company: Replaces with fake company name
    | - url: Replaces with fake URL
    | - ipv4: Replaces with fake IPv4 address
    | - ipv6: Replaces with fake IPv6 address
    | - uuid: Replaces with UUID
    | - password: Replaces with a bcrypt hash of a random password
    |
    */
    
    /*
    |--------------------------------------------------------------------------
    | Column Type Mapping
    |--------------------------------------------------------------------------
    |
    | Default mapping for database column types to faker methods.
    | If a column type is not specified in a table config, the package
    | will try to use these mappings based on the actual column type.
    |
    */
    'column_type_mapping' => [
        'varchar' => 'text',
        'char' => 'text',
        'text' => 'text',
        'longtext' => 'text',
        'mediumtext' => 'text',
        'tinytext' => 'text',
        'int' => 'randomNumber',
        'integer' => 'randomNumber',
        'bigint' => 'randomNumber',
        'tinyint' => 'randomNumber',
        'smallint' => 'randomNumber',
        'decimal' => 'randomNumber',
        'float' => 'randomNumber',
        'double' => 'randomNumber',
        'date' => 'date',
        'datetime' => 'datetime',
        'timestamp' => 'datetime',
        'time' => 'time',
        'year' => 'date',
        'enum' => 'randomElement',
        'json' => 'json',
        'binary' => 'sha256',
        'varbinary' => 'sha256',
        'blob' => 'sha256',
    ],
]