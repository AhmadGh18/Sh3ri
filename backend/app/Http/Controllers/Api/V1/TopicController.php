<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Poetry\Models\Topic;
use App\Http\Controllers\Controller;
use App\Http\Resources\TopicResource;
use Illuminate\Support\Facades\Cache;

class TopicController extends Controller
{
    public function index()
    {
        $topics = Cache::remember('taxonomy:topics', now()->addHour(), fn () => Topic::orderBy('name_ar')->get());

        return TopicResource::collection($topics);
    }
}
