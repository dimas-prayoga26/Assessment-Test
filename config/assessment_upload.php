<?php

return [
    'connection' => env('ASSESSMENT_UPLOAD_DB_CONNECTION', env('DB_CONNECTION', 'mysql')),

    'default_brand' => 'rnb',

    'document_type' => 'assessment_test',

    'storage_disk' => env('ASSESSMENT_UPLOAD_STORAGE_DISK', 'public'),

    'storage_directory' => 'uploaded-images',

    'host_brands' => [
        'technical-test.rnb.co.id' => 'rnb',
        'technical-test.trah.co.id' => 'trah',
        'technical-test.karpetmerah.id' => 'kma',
        'technical-test.rne.co.id' => 'rne',
        'technical-test.coffeeniskala.com' => 'niskala',
        'technical-test.tims.co.id' => 'tms',
    ],
];
