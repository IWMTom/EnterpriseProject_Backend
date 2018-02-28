<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;

class SetExpiresHeader
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        $response->header('Expires', Carbon::now()->toRfc1123String());

        return $response;
    }
}