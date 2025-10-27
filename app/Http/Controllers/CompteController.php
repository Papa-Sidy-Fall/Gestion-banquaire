<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompteRequest;
use App\Models\Compte;
use App\Http\Resources\CompteResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @OA\Info(
 *     title="API Banque",
 *     version="1.0.0",
 *     description="API pour la gestion des comptes bancaires"
 * )
 *
 * @OA\Server(
 *     url="http://api.banque.example.com/api/v1",
 *     description="Serveur API"
 * )
 *
 * @OA\Schema(
 *     schema="Compte",
 *     type="object",
 *     @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="numeroCompte", type="string", example="C00123456"),
 *     @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
 *     @OA\Property(property="type", type="string", example="epargne"),
 *     @OA\Property(property="solde", type="number", example=1250000),
 *     @OA\Property(property="devise", type="string", example="FCFA"),
 *     @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
 *     @OA\Property(property="statut", type="string", example="bloque"),
 *     @OA\Property(property="motifBlocage", type="string", example="Inactivité de 30+ jours"),
 *     @OA\Property(property="metadata", type="object", @OA\Property(property="derniereModification", type="string", format="date-time"), @OA\Property(property="version", type="integer", example=1))
 * )
 *
 * @OA\Schema(
 *     schema="Pagination",
 *     type="object",
 *     @OA\Property(property="currentPage", type="integer", example=1),
 *     @OA\Property(property="totalPages", type="integer", example=3),
 *     @OA\Property(property="totalItems", type="integer", example=25),
 *     @OA\Property(property="itemsPerPage", type="integer", example=10),
 *     @OA\Property(property="hasNext", type="boolean", example=true),
 *     @OA\Property(property="hasPrevious", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="Links",
 *     type="object",
 *     @OA\Property(property="self", type="string", example="/api/v1/comptes?page=1&limit=10"),
 *     @OA\Property(property="next", type="string", example="/api/v1/comptes?page=2&limit=10"),
 *     @OA\Property(property="first", type="string", example="/api/v1/comptes?page=1&limit=10"),
 *     @OA\Property(property="last", type="string", example="/api/v1/comptes?page=3&limit=10")
 * )
 */
class CompteController extends Controller
{
    use ApiResponseTrait;
    /**
     * @OA\Get(
     *     path="/comptes",
     *     summary="Lister tous les comptes",
     *     description="Admin peut récupérer la liste de tous les comptes, Client peut récupérer la liste de ses comptes. Liste des comptes non supprimés, type cheque ou epargne, actifs.",
     *     tags={"Comptes"},
     *     security={{"passport":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"epargne", "cheque"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"actif", "bloque", "ferme"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche par titulaire ou numéro",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Champ de tri",
     *         required=false,
     *         @OA\Schema(type="string", default="created_at")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Ordre de tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Compte")),
     *             @OA\Property(property="pagination", ref="#/components/schemas/Pagination"),
     *             @OA\Property(property="links", ref="#/components/schemas/Links")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user && $user->role === 'admin'; // Assumer un champ role

        $query = Compte::with('client')->whereNotNull('id'); // Base query

        // Permissions
        if (!$isAdmin && $user) {
            // Client voit seulement ses comptes
            $query->whereHas('client', function ($q) use ($user) {
                $q->where('telephone', $user->telephone); // Assumer user a telephone
            });
        }

        // Filtres
        if ($request->has('type')) {
            $query->byType($request->type);
        }
        if ($request->has('statut')) {
            $query->byStatut($request->statut);
        }
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Tri
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        $query->orderBy($sort, $order);

        // Pagination
        $perPage = min($request->get('limit', 10), 100);
        $paginator = $query->paginate($perPage, ['*'], 'page', $request->get('page', 1));

        return $this->paginatedResponse($paginator, CompteResource::class);
    }

    public function store(StoreCompteRequest $request)
    {
        $compte = Compte::create($request->validated());
        return response()->json([
            'message' => 'Compte créé avec succès',
            'data' => $compte
        ], 201);
    }

    public function show(Compte $compte)
    {
        return response()->json($compte->load('client'));
    }

    public function destroy(Compte $compte)
    {
        $compte->delete();
        return response()->json(['message' => 'Compte supprimé avec succès']);
    }
}
