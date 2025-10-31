<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'services' => []
        ];

        // Vérifier la base de données
        try {
            DB::connection()->getPdo();
            $health['services']['database'] = [
                'status' => 'healthy',
                'response_time' => now()->diffInMilliseconds(now())
            ];
        } catch (\Throwable $e) {
            $health['services']['database'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
            $health['status'] = 'unhealthy';
        }

        // Vérifier le cache
        try {
            $testKey = 'health_check_' . time();
            Cache::put($testKey, 'test', 10);
            $value = Cache::get($testKey);
            Cache::forget($testKey);

            $health['services']['cache'] = [
                'status' => $value === 'test' ? 'healthy' : 'unhealthy'
            ];
        } catch (\Exception $e) {
            $health['services']['cache'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
            $health['status'] = 'unhealthy';
        }

        // Vérifier le stockage
        try {
            $testFile = 'health_check_' . time() . '.txt';
            Storage::put($testFile, 'test');
            $exists = Storage::exists($testFile);
            Storage::delete($testFile);

            $health['services']['storage'] = [
                'status' => $exists ? 'healthy' : 'unhealthy'
            ];
        } catch (\Exception $e) {
            $health['services']['storage'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
            $health['status'] = 'unhealthy';
        }

        // Métriques système
        $health['system'] = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'memory_usage' => memory_get_peak_usage(true),
            'uptime' => time() - ($_SERVER['REQUEST_TIME'] ?? time())
        ];

        $statusCode = $health['status'] === 'healthy' ? 200 : 503;

        return response()->json($health, $statusCode);
    }
}