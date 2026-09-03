<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Poetry\Models\Country;
use App\Http\Controllers\Controller;
use App\Http\Resources\CountryResource;
use Illuminate\Support\Facades\Cache;

class CountryController extends Controller
{
    public function index()
    {
        $countries = Cache::remember('taxonomy:countries', now()->addHour(), fn () => Country::orderBy('name_ar')->get());

        return CountryResource::collection($countries);
    }
}
