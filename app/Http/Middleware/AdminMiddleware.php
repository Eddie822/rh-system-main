<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
         if (!auth()->check() || auth()->user()->role !== 'admin' 
         && auth()->user()->role !== 'rh' 
         && auth()->user()->role !== 'hr_manager' 
         && auth()->user()->role !== 'it') {
            abort(403, 'Acceso denegado.');
        }

        return $next($request);
    }
}
