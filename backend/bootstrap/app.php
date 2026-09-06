<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(append: [
            \App\Http\Middleware\JsonReadableUnicode::class,
        ]);

        // Rate limiters key on $request->ip(). Behind a load balancer / CDN,
        // that's the proxy address unless TrustProxies rewrites it from
        // X-Forwarded-For. Set TRUSTED_PROXIES to the exact IPs/CIDRs of your
        // ALB / Cloudflare / nginx layer — "*" means "trust any upstream" and
        // is ONLY safe when the app can't be reached directly from the
        // internet. Blank = trust nothing (Laravel default).
        $trusted = env('TRUSTED_PROXIES');
        if (! empty($trusted)) {
            $middleware->trustProxies(
                at: $trusted === '*' ? '*' : array_map('trim', explode(',', $trusted)),
                headers: Request::HEADER_X_FORWARDED_FOR
                    | Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PORT
                    | Request::HEADER_X_FORWARDED_PROTO,
            );
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // RFC-7807-ish error envelope for JSON clients (mobile + web).
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            $status = 500;
            $type = 'server_error';
            $message = 'Server error';
            $errors = null;

            if ($e instanceof ValidationException) {
                $status = 422;
                $type = 'validation_error';
                $message = $e->getMessage();
                $errors = $e->errors();
            } elseif (method_exists($e, 'getStatusCode')) {
                $status = $e->getStatusCode();
                $type = match ($status) {
                    401 => 'unauthenticated',
                    403 => 'forbidden',
                    404 => 'not_found',
                    405 => 'method_not_allowed',
                    409 => 'conflict',
                    422 => 'validation_error',
                    429 => 'too_many_requests',
                    default => 'http_error',
                };
                $message = $e->getMessage() ?: $type;
            } elseif ($e instanceof \Illuminate\Auth\AuthenticationException) {
                $status = 401;
                $type = 'unauthenticated';
                $message = 'Unauthenticated';
            } elseif ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                $status = 403;
                $type = 'forbidden';
                $message = $e->getMessage() ?: 'Forbidden';
            } elseif ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                $status = 404;
                $type = 'not_found';
                $message = 'Resource not found';
            }

            $body = [
                'error' => [
                    'type' => $type,
                    'message' => $message,
                    'trace_id' => $request->headers->get('X-Request-Id') ?? bin2hex(random_bytes(8)),
                ],
            ];
            if ($errors !== null) {
                $body['error']['errors'] = $errors;
            }
            // Debug detail: local dev gets full class+file+line; production
            // gets ONLY the class name for 5xx so we can diagnose from the
            // response alone without needing shell access to Render.
            if (config('app.debug') && ! in_array($status, [401, 403, 404, 422, 429], true)) {
                $body['error']['debug'] = [
                    'exception' => $e::class,
                    'file' => $e->getFile() . ':' . $e->getLine(),
                    'message' => $e->getMessage(),
                ];
            } elseif ($status >= 500) {
                $body['error']['exception_class'] = $e::class;
                // Truncate to prevent leaking full query bodies etc.
                $body['error']['reason'] = substr($e->getMessage(), 0, 200);
            }

            return response()->json($body, $status);
        });
    })->create();
