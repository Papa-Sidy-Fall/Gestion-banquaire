<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\RateLimitExceededException;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response)  $next
     */
    public function handle(Request $request, Closure $next, $maxRequests = 100, $windowMinutes = 1)
    {
        $key = 'rate_limit:' . $request->ip();
        $now = now();

        $requests = Cache::get($key, []);

        // Supprimer les requêtes hors de la fenêtre
        $requests = array_filter($requests, function ($timestamp) use ($now, $windowMinutes) {
            return $timestamp > $now->subMinutes($windowMinutes)->timestamp;
        });

        if (count($requests) >= $maxRequests) {
            throw new RateLimitExceededException('Trop de requêtes. Limite atteinte.');
        }

        $requests[] = $now->timestamp;
        Cache::put($key, $requests, $windowMinutes * 60);

        return $next($request);
    }
}
