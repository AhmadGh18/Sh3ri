<?php

declare(strict_types=1);

// Origins allowed to hit /api/* from a browser. Comma-separated env var; blank
// falls back to the localhost dev origins ONLY — never `*` in this project.
$origins = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('SH3RI_CORS_ALLOWED_ORIGINS', ''))
)));
if ($origins === []) {
    $origins = [
        'http://localhost',
        'http://localhost:3000',
        'http://localhost:5173',
        'http://127.0.0.1',
        'http://127.0.0.1:8000',
    ];
}

return [
    'paths' => ['api/*', 'docs/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PATCH', 'PUT', 'DELETE', 'OPTIONS'],

    // NEVER `*` — even for read-only endpoints. See SH3RI_CORS_ALLOWED_ORIGINS.
    'allowed_origins' => $origins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With', 'Origin', 'X-Request-Id'],

    'exposed_headers' => ['ETag'],

    'max_age' => 3600,

    // We use Bearer tokens (Sanctum PAT) — no cookies on the API. Setting this
    // false is safer AND lets us keep a strict origin list without the browser
    // preflighting every credentialed request.
    'supports_credentials' => false,
];
