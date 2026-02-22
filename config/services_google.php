<?php

return [
    'ai_studio' => [
        'api_key' => env('GOOGLE_AI_STUDIO_API_KEY', ''),
        'base_url' => 'https://generativelanguage.googleapis.com',
        'model' => env('GOOGLE_AI_STUDIO_MODEL', 'veo-3.1-generate-preview'),
    ],
];
