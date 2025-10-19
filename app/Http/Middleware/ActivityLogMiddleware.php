<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Skip login & logout route supaya login tetap aman
        if ($request->is('admin/login') || $request->is('admin/logout')) {
            return $next($request);
        }

        $response = $next($request);

        try {
            $user = auth()->user(); // bisa null
            activity('http-access')
                ->causedBy($user) // jika null, Spatie handle otomatis
                ->withProperties([
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                ])
                ->log('Mengakses: ' . $request->path());
        } catch (\Exception $e) {
            // jangan hentikan request jika log gagal
            \Log::error('[ActivityLogMiddleware] Gagal log: ' . $e->getMessage());
        }

        return $response;
    }
}
