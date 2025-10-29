<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $operation = null): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // en millisecondes

        // Déterminer l'opération si non spécifiée
        if (!$operation) {
            $operation = $this->determineOperation($request);
        }

        // Logger les informations
        \Illuminate\Support\Facades\Log::info('API Operation Log', [
            'timestamp' => now()->toISOString(),
            'host' => $request->getHost(),
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'operation' => $operation,
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'user_id' => $request->user() ? $request->user()->id : null,
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'resource' => $this->getResourceFromUri($request->getRequestUri()),
        ]);

        return $response;
    }

    /**
     * Déterminer l'opération basée sur la requête
     */
    private function determineOperation(Request $request): string
    {
        $method = $request->getMethod();
        $uri = $request->getRequestUri();

        if (str_contains($uri, '/comptes')) {
            switch ($method) {
                case 'POST':
                    return 'CREATION_COMPTE';
                case 'GET':
                    return str_contains($uri, '/comptes/') ? 'CONSULTATION_COMPTE' : 'LISTE_COMPTES';
                case 'PUT':
                    return 'MODIFICATION_COMPTE';
                case 'DELETE':
                    return 'SUPPRESSION_COMPTE';
            }
        }

        return 'OPERATION_INCONNUE';
    }

    /**
     * Extraire la ressource depuis l'URI
     */
    private function getResourceFromUri(string $uri): ?string
    {
        $segments = explode('/', trim($uri, '/'));
        return $segments[1] ?? null; // ex: 'comptes', 'clients', etc.
    }
}
