<?php

return [
    'special_profile_email' => env('ETHRAA_SPECIAL_PROFILE_EMAIL'),
    // The admin's WhatsApp (international format, digits only): «نسيت كلمة المرور» links to it for a reset link by hand.
    'admin_whatsapp' => preg_replace('/\D+/', '', (string) env('MIDAD_ADMIN_WHATSAPP', '966595167827')),
    'signup_notification_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ETHRAA_SIGNUP_NOTIFICATION_EMAILS', '')),
    ))),
];
