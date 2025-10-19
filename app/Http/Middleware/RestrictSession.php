<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RestrictSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user) {
            if ($user->current_session_token !== session('current_session_token')) {
                Auth::logout();

                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Session expired, please log in again.'], 401);
                }

                return redirect()->route('filament.auth.login')
                    ->withErrors(['email' => 'Session expired, please login again.']);
            }
        }

        return $next($request);
    }
}
