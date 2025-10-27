<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponseTrait
{
    /**
     * Retourner une réponse JSON paginée standardisée.
     *
     * @param LengthAwarePaginator $paginator
     * @param string $resourceClass
     * @return JsonResponse
     */
    protected function paginatedResponse(LengthAwarePaginator $paginator, string $resourceClass): JsonResponse
    {
        $data = $resourceClass::collection($paginator->items());

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'currentPage' => $paginator->currentPage(),
                'totalPages' => $paginator->lastPage(),
                'totalItems' => $paginator->total(),
                'itemsPerPage' => $paginator->perPage(),
                'hasNext' => $paginator->hasMorePages(),
                'hasPrevious' => $paginator->onFirstPage() ? false : true,
            ],
            'links' => [
                'self' => $paginator->url($paginator->currentPage()),
                'next' => $paginator->nextPageUrl(),
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
            ],
        ]);
    }

    /**
     * Retourner une réponse JSON de succès simple.
     *
     * @param mixed $data
     * @param string $message
     * @param int $status
     * @return JsonResponse
     */
    protected function successResponse($data = null, string $message = 'Opération réussie', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Retourner une réponse JSON d'erreur.
     *
     * @param string $message
     * @param int $status
     * @param mixed $errors
     * @return JsonResponse
     */
    protected function errorResponse(string $message, int $status = 400, $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}