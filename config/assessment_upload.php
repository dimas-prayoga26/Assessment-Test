<?php

return [
    'connection' => env('ASSESSMENT_UPLOAD_DB_CONNECTION', env('DB_CONNECTION', 'mysql')),

    'default_brand' => 'rnb',

    'document_type' => 'assessment_test',

    'storage_disk' => env('ASSESSMENT_UPLOAD_STORAGE_DISK', 'public'),

    'storage_directory' => 'uploaded-images',

    'host_brands' => [
        'assessment-test.rnb.co.id' => 'rnb',
        'assessment-test.trah.co.id' => 'trah',
        'assessment-test.karpetmerah.id' => 'kma',
        'assessment-test.rne.co.id' => 'rne',
        'assessment-test.coffeeniskala.com' => 'niskala',
        'assessment-test.tims.co.id' => 'tms',
    ],
];
