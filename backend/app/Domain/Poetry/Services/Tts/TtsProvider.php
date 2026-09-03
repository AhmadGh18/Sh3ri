<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Services\Tts;

/**
 * TTS abstraction. Current implementation is ElevenLabs; future adapters
 * (Google, Azure, ElevenLabs Turbo, on-prem Piper) implement this same
 * contract so the controller/cache layer never changes.
 */
interface TtsProvider
{
    /**
     * Synthesize $text into an MP3 byte string.
     *
     * @throws TtsException on provider misconfiguration or upstream failure.
     */
    public function synthesizeMp3(string $text): string;

    /** Short, stable name written into cache metadata for debugging. */
    public function name(): string;
}
