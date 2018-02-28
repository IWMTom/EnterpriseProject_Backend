<?php

namespace App\Http\Middleware;

use Closure;

class SetExpiresHeader
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        $response->header('Expires', 0);

        return $response;
    }
}