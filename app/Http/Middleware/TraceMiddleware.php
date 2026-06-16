<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TraceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $traceId = (string) Str::uuid();
        $request->attributes->set('trace_id', $traceId);

        $start = hrtime(true);

        Log::info("TRACE START", [
            'trace_id' => $traceId,
            'path' => $request->path()
        ]);

        $response = $next($request);

        $total = (hrtime(true) - $start) / 1e6;

        Log::info("TRACE END", [
            'trace_id' => $traceId,
            'total_ms' => $total
        ]);

        return $response;
    }
}
