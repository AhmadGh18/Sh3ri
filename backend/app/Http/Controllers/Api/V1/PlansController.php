<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Entitlements\Models\Plan;
use App\Http\Controllers\Controller;

class PlansController extends Controller
{
    public function __invoke()
    {
        $plans = Plan::query()
            ->where('is_public', true)
            ->orderBy('sort')
            ->get()
            ->map(fn (Plan $p) => [
                'code'              => $p->code,
                'name_ar'           => $p->name_ar,
                'name_en'           => $p->name_en,
                'tagline_ar'        => $p->tagline_ar,
                'price_cents'       => $p->price_cents,
                'currency'          => $p->currency,
                'daily_audio_plays' => $p->daily_audio_plays,
                'allow_download'    => $p->allow_download,
                'is_free'           => $p->isFree(),
                'is_unlimited'      => $p->isUnlimited(),
            ]);

        return response()->json(['data' => $plans]);
    }
}
