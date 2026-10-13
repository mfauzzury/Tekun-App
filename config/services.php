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
        // Untouched .env key; 'key' may be overridden from Integrasi AI > Penyedia AI (AiProviderService).
        'env_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5'),
        'chat_model' => env('ANTHROPIC_CHAT_MODEL', env('ANTHROPIC_MODEL', 'claude-haiku-4-5')),
        // Model for image/document work (MyKad OCR, face check, documents); falls back to 'model'.
        'vision_model' => env('ANTHROPIC_VISION_MODEL'),
        'timeout' => (int) env('ANTHROPIC_TIMEOUT', 300),
    ],

    // Integrasi AI > Penyedia AI — provider used by the POC 1 AI services (AiClient). Values saved on the
    // page override these at boot (AiProviderService). OpenAI, DeepSeek and on-premise servers
    // (Ollama, vLLM, LM Studio…) are called through the OpenAI-compatible chat completions API.
    'ai' => [
        'active' => env('AI_PROVIDER', 'anthropic'),
        // Provider for image/document tasks; null = same as 'active' when it supports images, else Anthropic.
        'active_vision' => env('AI_PROVIDER_VISION'),
        'openai' => [
            'key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'vision' => true,
        ],
        'deepseek' => [
            'key' => env('DEEPSEEK_API_KEY'),
            'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
            'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
            'vision' => false,
        ],
        'onprem' => [
            'key' => env('AI_ONPREM_API_KEY'),
            'model' => env('AI_ONPREM_MODEL'),
            'base_url' => env('AI_ONPREM_BASE_URL'),
            'vision' => (bool) env('AI_ONPREM_VISION', false),
        ],
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
