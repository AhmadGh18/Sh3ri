<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Poetry\Models\Era;
use App\Http\Controllers\Controller;
use App\Http\Resources\EraResource;
use Illuminate\Support\Facades\Cache;

class EraController extends Controller
{
    public function index()
    {
        $eras = Cache::remember('taxonomy:eras', now()->addHour(), fn () => Era::orderBy('display_order')->get());

        return EraResource::collection($eras);
    }
}
