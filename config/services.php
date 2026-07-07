<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
        'from' => env('RESEND_FROM_EMAIL', 'onboarding@resend.dev'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5'),
        'chat_model' => env('ANTHROPIC_CHAT_MODEL', env('ANTHROPIC_MODEL', 'claude-haiku-4-5')),
        'timeout' => (int) env('ANTHROPIC_TIMEOUT', 300),
    ],

    'twilio' => [
        'sid' => env('TWILIO_ACCOUNT_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM_NUMBER'),
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'assistant_id' => env('OPENAI_ASSISTANT_ID', ''),
        'user_chat_assistant_id' => env('OPENAI_USER_CHAT_ASSISTANT_ID', ''),
        'vector_store_id' => env('OPENAI_VECTOR_STORE_ID'),
    ],

    'sppt' => [
        'system_url' => env('SPPT_SYSTEM_URL', env('APP_URL', 'http://localhost')),
    ],

];
