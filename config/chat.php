<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Chat Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for chat messages to prevent abuse and ensure
    | fair usage of the AI service. Rate limits are enforced per session.
    |
    */

    'rate_limit' => [
        /*
         * Maximum number of messages allowed within the time window.
         * Default: 5 messages
         */
        'max_messages' => env('CHAT_RATE_LIMIT_MAX', 5),

        /*
         * Time window in seconds for rate limiting.
         * Default: 60 seconds (1 minute)
         */
        'window_seconds' => env('CHAT_RATE_LIMIT_WINDOW', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Persona
    |--------------------------------------------------------------------------
    |
    | The default AI persona to use when starting a new chat session.
    | Options: makcik, gymbro, atas
    |
    */

    'default_persona' => env('CHAT_DEFAULT_PERSONA', 'makcik'),

    /*
    |--------------------------------------------------------------------------
    | Default AI Model
    |--------------------------------------------------------------------------
    |
    | The default AI model/provider to use when starting a new chat session.
    | Options: gemini, groq-openai, groq-meta
    |
    */

    'default_model' => env('CHAT_DEFAULT_MODEL', 'gemini'),

];
