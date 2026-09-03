<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

/**
 * Public runtime config for the SPA. Only ever exposes safe-to-publish values
 * (client-side identifiers, feature flags) — never secrets.
 */
class ConfigController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'data' => [
                'google_client_id' => (string) config('services.google.client_id', ''),
                'features' => [
                    'google_signin' => (string) config('services.google.client_id', '') !== '',
                    'email_verification_required' => (bool) config('sh3ri.security.require_verified_email', false),
                ],
            ],
        ]);
    }
}
