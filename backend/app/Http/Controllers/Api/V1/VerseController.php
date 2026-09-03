<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Poetry\Models\Verse;
use App\Enums\PoemStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\VerseResource;

class VerseController extends Controller
{
    public function show(Verse $verse)
    {
        $verse->load(['poem.poet', 'poem.era']);

        // Verses inherit visibility from their parent poem. Never leak a verse
        // that belongs to a hidden / quarantined / soft-deleted poem, even if
        // the UUID is known.
        abort_unless(
            $verse->poem !== null && $verse->poem->status === PoemStatus::Published,
            404
        );

        return VerseResource::make($verse);
    }
}
