<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Renders Arabic (and any non-ASCII) characters as literal UTF-8 in JSON
 * responses instead of \uNNNN escapes. Mobile/web JSON parsers handle both,
 * but literals make responses grep-able in logs and readable in tools like
 * curl / Postman.
 */
class JsonReadableUnicode
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $response->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $response;
    }
}
