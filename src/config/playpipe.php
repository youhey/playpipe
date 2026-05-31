<?php

$adminAllowedEmails = env('PLAYPIPE_ADMIN_ALLOWED_EMAILS', '');

return [
    'admin' => [
        'allowed_emails' => array_values(array_filter(
            array_map('trim', explode(',', is_string($adminAllowedEmails) ? $adminAllowedEmails : '')),
            static fn (string $email): bool => $email !== '',
        )),
        'dev_login' => [
            'enabled' => (bool) env('PLAYPIPE_ADMIN_DEV_LOGIN_ENABLED', false),
            'email' => env('PLAYPIPE_ADMIN_DEV_LOGIN_EMAIL'),
        ],
    ],

    'api_tokens' => [
        'default_name' => 'playpipe-api',
        'allowed_abilities' => [
            'episodes:write',
            'episodes:read',
            'feedback:write',
            'feedback:sync',
        ],
        'default_abilities' => [
            'episodes:write',
        ],
    ],

    'upload' => [
        'audio_max_kb' => (int) env('PLAYPIPE_UPLOAD_AUDIO_MAX_KB', 102400),
        'storage_disk' => env('PLAYPIPE_AUDIO_DISK', env('FILESYSTEM_DISK', 's3')),
    ],

    'listen' => [
        'operator_portraits' => [
            'images/listen/operators/nyozomi/default/sumashi.png',
            'images/listen/operators/nyozomi/default/smile_soft.png',
            'images/listen/operators/nyozomi/default/angry_open_mouth.png',
            'images/listen/operators/nyozomi/default/sad_sulking.png',
            'images/listen/operators/nyozomi/default/big_brassy_laugh.png',
            'images/listen/operators/nyozomi/default/surprised_arms_up.png',
        ],
    ],
];
