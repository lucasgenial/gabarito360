<?php

return [
    'auth' => [
        'login_max_attempts_per_minute' => (int) env('GABARITO360_LOGIN_MAX_ATTEMPTS_PER_MINUTE', 5),
    ],

    'mobile' => [
        'minimum_app_version' => env('GABARITO360_MOBILE_MINIMUM_APP_VERSION', '1.0.0'),
        'token_expiration_days' => (int) env('GABARITO360_MOBILE_TOKEN_EXPIRATION_DAYS', 30),
    ],

    'imports' => [
        'students' => [
            'max_file_kilobytes' => (int) env('GABARITO360_STUDENT_IMPORT_MAX_FILE_KILOBYTES', 2048),
            'max_rows' => (int) env('GABARITO360_STUDENT_IMPORT_MAX_ROWS', 1000),
        ],
    ],
];
