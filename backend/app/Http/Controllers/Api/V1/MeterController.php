<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Poetry\Models\Meter;
use App\Http\Controllers\Controller;
use App\Http\Resources\MeterResource;
use Illuminate\Support\Facades\Cache;

class MeterController extends Controller
{
    public function index()
    {
        $meters = Cache::remember('taxonomy:meters', now()->addHour(), fn () => Meter::orderBy('name_ar')->get());

        return MeterResource::collection($meters);
    }
}
