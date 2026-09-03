<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Entitlements\Services\AudioQuotaService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Reports the caller's current audio-quota state. Works for both signed-in
 * users (counts from DB) and guests (counts from cache, keyed by IP + a
 * per-browser cookie so multiple people behind one CGNAT don't share quota).
 */
class MeAudioUsageController extends Controller
{
    public function __invoke(Request $request, AudioQuotaService $quota)
    {
        $user = $request->user();
        $guestKey = $user ? null : $this->guestKey($request);
        return response()->json(['data' => $quota->summary($user, $guestKey)]);
    }

    private function guestKey(Request $r): string
    {
        return ($r->cookie('sh3ri_guest') ?: '') . '|' . $r->ip();
    }
}
