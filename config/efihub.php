<?php

return [
    'client_id' => env('EFIHUB_CLIENT_ID'),
    'client_secret' => env('EFIHUB_CLIENT_SECRET'),
    'token_url' => env('EFIHUB_TOKEN_URL', 'https://efihub.morefurniture.id/oauth/token'),
    'api_base_url' => env('EFIHUB_API_URL', 'https://efihub.morefurniture.id/api'),
];
