<?php

namespace App\Http\Middleware;

use Closure;

class EnforceHttps
{
    public function handle($request, Closure $next)
    {
        if (!$request->secure())
        {
            return response()->json(['error' => 'Insecure connection!'], 400);
        }

        return $next($request); 
    }
}