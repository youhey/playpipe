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
];
