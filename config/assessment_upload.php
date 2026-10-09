<?php

return [
    'connection' => env('ASSESSMENT_UPLOAD_DB_CONNECTION', 'tms'),

    'brand_connections' => [
        'rnb' => env('ASSESSMENT_UPLOAD_RNB_DB_CONNECTION', 'rnb'),
        'trah' => env('ASSESSMENT_UPLOAD_TRAH_DB_CONNECTION', 'trah'),
        'kma' => env('ASSESSMENT_UPLOAD_KMA_DB_CONNECTION', 'kma'),
        'rne' => env('ASSESSMENT_UPLOAD_RNE_DB_CONNECTION', 'rne'),
        'niskala' => env('ASSESSMENT_UPLOAD_NISKALA_DB_CONNECTION', 'niskala'),
        'tms' => env('ASSESSMENT_UPLOAD_TMS_DB_CONNECTION', env('ASSESSMENT_UPLOAD_DB_CONNECTION', 'tms')),
    ],

    'default_brand' => 'rnb',

    'document_type' => 'assessment_test',

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
