<?php

declare(strict_types=1);

return [
    'search' => [
        'driver' => env('SH3RI_SEARCH_DRIVER', 'postgres'),
    ],

    'import' => [
        'quarantine_modern' => (bool) env('SH3RI_IMPORT_QUARANTINE_MODERN', true),
    ],

    'entitlements' => [
        // While true, every authenticated user is treated as entitled to
        // every product (open access). Flip to false when paywalls turn on.
        'open_access' => (bool) env('SH3RI_ENTITLEMENTS_OPEN_ACCESS', true),
    ],

    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 50,
    ],

    'tts' => [
        // The provider is fixed to ElevenLabs for now; kept as a config value
        // so a future Azure/Google adapter can slot in without touching the
        // controller.
        'provider' => env('SH3RI_TTS_PROVIDER', 'elevenlabs'),

        'elevenlabs' => [
            'api_key'  => env('ELEVENLABS_API_KEY'),
            // Voice id — the ElevenLabs voice used for Arabic. Choose one
            // from https://elevenlabs.io/app/voice-library that supports
            // Arabic (any voice does with the multilingual_v2 model).
            'voice_id' => env('ELEVENLABS_VOICE_ID', 'ErXwobaYiN019PkySvjV'), // "Antoni" — placeholder
            'model_id' => env('ELEVENLABS_MODEL_ID', 'eleven_multilingual_v2'),
            'stability'        => (float) env('ELEVENLABS_STABILITY', 0.5),
            'similarity_boost' => (float) env('ELEVENLABS_SIMILARITY_BOOST', 0.75),
            'style'            => (float) env('ELEVENLABS_STYLE', 0.0),
            'use_speaker_boost' => (bool) env('ELEVENLABS_SPEAKER_BOOST', true),
            'timeout_seconds'  => (int) env('ELEVENLABS_TIMEOUT', 30),
        ],

        // Where per-verse MP3 caches live. Filenames are {verse_uuid}.mp3.
        // Storage is idempotent — same verse text always yields the same file.
        'cache_disk' => env('SH3RI_TTS_CACHE_DISK', 'local'),
        'cache_path' => env('SH3RI_TTS_CACHE_PATH', 'verse_audio'),

        // LRU-ish eviction targets used by `php artisan sh3ri:prune-verse-audio`.
        // Whichever ceiling is hit first triggers deletion, oldest-first
        // (by last-accessed mtime).
        'cache_max_files' => (int) env('SH3RI_TTS_CACHE_MAX_FILES', 100000),
        'cache_max_bytes' => (int) env('SH3RI_TTS_CACHE_MAX_BYTES', 5 * 1024 * 1024 * 1024), // 5 GB

        // Hard daily character budget across all callers. Once the counter
        // (tracked in the cache store) exceeds this in a UTC calendar day,
        // the audio endpoint returns 503 until midnight. Zero disables the
        // circuit-breaker. Guards against runaway ElevenLabs cost from a
        // cache-miss attack rotating through fresh verse UUIDs.
        'daily_char_budget' => (int) env('SH3RI_TTS_DAILY_CHAR_BUDGET', 500000),
    ],

    'security' => [
        // Gate on the built-in `verified` middleware for write endpoints.
        // Off in MVP (open access); flip on to require email verification
        // before submissions / reports / user-poem writes are accepted.
        'require_verified_email' => (bool) env('SH3RI_REQUIRE_VERIFIED_EMAIL', false),
    ],
];
