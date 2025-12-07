<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('admin/login') || $request->is('admin/logout')) {
            return $next($request);
        }

        $response = $next($request);

        try {
            $user = auth()->user(); 
            activity('http-access')
                ->causedBy($user) 
                ->withProperties([
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                ])
                ->log('Mengakses: ' . $request->path());
        } catch (\Exception $e) {
            \Log::error('[ActivityLogMiddleware] Gagal log: ' . $e->getMessage());
        }

        return $response;
    }
}
