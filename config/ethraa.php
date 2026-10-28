<?php

return [
    'special_profile_email' => env('ETHRAA_SPECIAL_PROFILE_EMAIL'),
    'signup_notification_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ETHRAA_SIGNUP_NOTIFICATION_EMAILS', '')),
    ))),
];
