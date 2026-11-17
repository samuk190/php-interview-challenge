<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceJsonResponse
{
    /**
     * Force every API request to be treated as a JSON request.
     *
     * This guarantees that both successful responses and framework error
     * responses (404, 422, 500, ...) are rendered as JSON, no matter which
     * Accept header the client sent.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
