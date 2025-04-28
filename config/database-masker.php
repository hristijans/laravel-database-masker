<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Connections Configuration
    |--------------------------------------------------------------------------
    |
    | Define which database connections should be processed and which tables
    | and columns in each connection contain confidential data.
    |
    | If 'connections' is empty, the default connection will be used with the
    | tables configuration defined at the root level.
    |
    */
    'connections' => [
        // Examples of multiple database connections
        // 'mysql' => [
        //     'tables' => [
        //         'users' => [
        //             'columns' => [
        //                 'email' => ['type' => 'email'],
        //                 'name' => ['type' => 'name'],
        //             ],
        //         ],
        //     ],
        //     'exclude_tables' => [
        //         'migrations',
        //         'failed_jobs',
        //     ],
        //     'output_file' => 'masked_mysql.sql',
        // ],
        // 'second_db' => [
        //     'tables' => [
        //         'customers' => [
        //             'columns' => [
        //                 'email' => ['type' => 'email'],
        //                 'first_name' => ['type' => 'firstName'],
        //                 'last_name' => ['type' => 'lastName'],
        //             ],
        //         ],
        //     ],
        //     'exclude_tables' => [
        //         'migrations',
        //     ],
        //     'output_file' => 'masked_second_db.sql',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tables Configuration (for default connection)
    |--------------------------------------------------------------------------
    |
    | Define which tables and columns contain confidential data that should be
    | masked when creating a database dump for development or testing purposes.
    |
    | This configuration is used when no specific connections are defined or
    | for backward compatibility.
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
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Tables (for default connection)
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
    'output_path' => null,           // Default output path (null = storage_path('app'))

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
];
