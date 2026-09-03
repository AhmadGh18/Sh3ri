<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Poetry\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Cache::remember('taxonomy:categories', now()->addHour(), fn () => Category::orderBy('display_order')->get());

        return CategoryResource::collection($categories);
    }
}
