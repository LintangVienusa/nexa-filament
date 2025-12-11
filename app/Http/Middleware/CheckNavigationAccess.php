<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckNavigationAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log::info('Middleware CheckNavigationAccess triggered for route: ' . $request->path());
        
        if (! str_starts_with($request->path(), 'admin')) {
            return $next($request);
        }

        
        if (preg_match('/login|logout|password/', $request->path())) {
            return $next($request);
        }

        
        if (! Auth::check()) {
            return redirect('/admin/login');
        }

        $user = Auth::user();
        $currentRoute = $request->route();
        $controller = $currentRoute?->getController();

        

       
        if ($controller && method_exists($controller, 'getResource')) {
            $resourceClass = $controller::getResource();
            // if (method_exists($resourceClass, 'shouldRegisterNavigation')) {
            //     $hasAccess = $resourceClass::shouldRegisterNavigation();
            // } else {
                $hasAccess = true;
            // }
        } else {
            $hasAccess = true;
        }

        if (! $hasAccess) {
            session()->flash('access_denied', true);
            return redirect('/admin');
        }



        return $next($request);
    }
}
