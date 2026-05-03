<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class AOPMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {

    $start = microtime(true);
    Log::info("START: " . $request->path());
    try {
        $response = $next($request);
    } catch (\Exception $e) {
        Log::error("ERROR: " . $e->getMessage());
        throw $e;
    }
    $end = microtime(true);
    $time = ($end - $start) * 1000;
    Log::info("END: " . $request->path() . " - {$time} ms");

    return $response;
    }
}
