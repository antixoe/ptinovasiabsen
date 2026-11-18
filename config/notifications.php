<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Important notification types
    |--------------------------------------------------------------------------
    |
    | Only these in-app notifications are mirrored to the external channels
    | below. Remove a type if you do not want it leaving the workspace.
    |
    */

    'important_types' => [
        'report.submitted',
        'report.reviewed',
        'report.revision',
        'report.reminder',
        'agenda.created',
        'agenda.updated',
    ],

    /*
    |--------------------------------------------------------------------------
    | Email (Gmail SMTP)
    |--------------------------------------------------------------------------
    |
    | Configure the standard MAIL_* variables for Gmail:
    | MAIL_MAILER=smtp, MAIL_HOST=smtp.gmail.com, MAIL_PORT=587,
    | MAIL_USERNAME=your@gmail.com, MAIL_PASSWORD=<16-char app password>.
    | Then set NOTIFY_EMAIL=true to mirror important notifications by email.
    |
    */

    'email' => [
        'enabled' => (bool) env('NOTIFY_EMAIL', false),

        // Optional: send every notification to one inbox (e.g. your Gmail)
        // instead of each recipient's address.
        'to' => env('NOTIFY_EMAIL_TO'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Discord webhook
    |--------------------------------------------------------------------------
    |
    | Paste a channel webhook URL (Server Settings > Integrations > Webhooks)
    | into DISCORD_WEBHOOK_URL. Leave it empty to disable Discord delivery.
    |
    */

    'discord' => [
        'webhook_url' => env('DISCORD_WEBHOOK_URL'),
        'username' => env('DISCORD_NOTIFY_USERNAME', 'Ruang Kerja'),
    ],

];
