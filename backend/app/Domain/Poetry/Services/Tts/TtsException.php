<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Services\Tts;

use RuntimeException;

class TtsException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusHint = 502,
        public readonly ?string $providerError = null,
        /**
         * Classified reason so the controller can pick the right client-
         * facing error type + set a circuit-breaker. Values:
         *   - "quota_exceeded"    → provider account is out of credits
         *   - "auth"              → API key invalid / unauthorized
         *   - "rate_limited"      → HTTP 429 from provider
         *   - "bad_input"         → text rejected (too long, banned, empty)
         *   - "config"            → local config missing (no key/voice)
         *   - "unavailable"       → everything else
         */
        public readonly string $errorKind = 'unavailable',
    ) {
        parent::__construct($message);
    }
}
