<?php

return [
    'model' => \Webwizo\ShortcodesFilament\Models\Shortcode::class,

    /*
    |--------------------------------------------------------------------------
    | Table Name
    |--------------------------------------------------------------------------
    | The table name used to store shortcodes. Change this if you have a
    | naming convention or conflict with an existing table.
    */
    'table_name' => 'shortcodes',

    'excluded_tables' => [
        'migrations',
        'failed_jobs',
        'password_resets',
        'password_reset_tokens',
        'personal_access_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'shortcodes', // keep in sync with table_name if you change it
    ],

    'cache_ttl' => 600,

    'tenant' => [
        'foreign_key'  => 'vendor_id',
        'relationship' => 'vendor',
        'model'        => null,

        /*
        | The primary-key type of your tenant model. This determines the column
        | type used for the foreign key on the shortcodes table.
        |
        | Supported: 'int', 'uuid', 'ulid', 'string'
        |
        | Leave null to auto-detect from the tenant model (recommended).
        | Auto-detection checks for HasUuids / HasUlids traits and falls back
        | to 'int' when neither is present.
        */
        'key_type' => 'ulid',
    ],
];