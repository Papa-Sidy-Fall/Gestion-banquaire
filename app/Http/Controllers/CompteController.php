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
 *     url="http://localhost:8000/api/v1",
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

    /**
     * @OA\Post(
     *     path="/comptes",
     *     summary="Créer un nouveau compte",
     *     description="Créer un nouveau compte bancaire avec validation des données",
     *     tags={"Comptes"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"client_id", "type_compte"},
     *             @OA\Property(property="client_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="type_compte", type="string", enum={"epargne", "cheque"}, example="epargne"),
     *             @OA\Property(property="solde", type="number", example=0),
     *             @OA\Property(property="devise", type="string", example="FCFA"),
     *             @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, example="actif")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte créé avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
     */
    /**
     * @OA\Put(
     *     path="/comptes/{compteId}",
     *     summary="Modifier un compte",
     *     description="Modifier les informations d'un compte bancaire existant",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="ID du compte à modifier",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="type_compte", type="string", enum={"epargne", "cheque"}, example="epargne"),
     *             @OA\Property(property="solde", type="number", example=150000),
     *             @OA\Property(property="devise", type="string", example="FCFA"),
     *             @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, example="actif"),
     *             @OA\Property(property="motifBlocage", type="string", example="Raison du blocage")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte modifié avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte modifié avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé"
     *     )
     * )
     */
    public function update(Request $request, Compte $compte)
    {
        $user = $request->user();
        $isAdmin = $user && $user->role === 'admin';

        // Vérifier les permissions
        if (!$isAdmin && $user) {
            // Client ne peut modifier que ses propres comptes
            if (!$compte->client || $compte->client->telephone !== $user->telephone) {
                return $this->errorResponse('Accès refusé à ce compte', 403);
            }
        }

        $validated = $request->validate([
            'type_compte' => 'sometimes|in:epargne,cheque',
            'solde' => 'sometimes|numeric|min:0',
            'devise' => 'sometimes|string|max:10',
            'statut' => 'sometimes|in:actif,bloque,ferme',
            'motifBlocage' => 'nullable|string'
        ]);

        $compte->update($validated);
        return $this->successResponse(new CompteResource($compte), 'Compte modifié avec succès');
    }

    public function store(StoreCompteRequest $request)
    {
        $compte = Compte::create($request->validated());
        return $this->successResponse(new CompteResource($compte), 'Compte créé avec succès', 201);
    }

    /**
     * @OA\Get(
     *     path="/comptes/{compteId}",
     *     summary="Afficher un compte spécifique",
     *     description="Admin peut récupérer un compte par ID, Client peut récupérer un de ses comptes par ID. Recherche locale par défaut, serverless si non trouvé.",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="ID du compte",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du compte",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="COMPTE_NOT_FOUND"),
     *                 @OA\Property(property="message", type="string", example="Le compte avec l'ID spécifié n'existe pas"),
     *                 @OA\Property(property="details", type="object", @OA\Property(property="compteId", type="string"))
     *             )
     *         )
     *     )
     * )
     */
    public function show(Request $request, $compteId)
    {
        // Validation de l'UUID
        if (!\Illuminate\Support\Str::isUuid($compteId)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'COMPTE_NOT_FOUND',
                    'message' => 'Le compte avec l\'ID spécifié n\'existe pas',
                    'details' => [
                        'compteId' => $compteId
                    ]
                ]
            ], 404);
        }

        try {
            $compte = Compte::with('client')->findOrFail($compteId);

            $user = $request->user();
            $isAdmin = $user && $user->role === 'admin';

            // Vérifier les permissions
            if (!$isAdmin && $user) {
                // Client ne peut voir que ses propres comptes
                if (!$compte->client || $compte->client->telephone !== $user->telephone) {
                    return $this->errorResponse('Accès refusé à ce compte', 403);
                }
            }

            return $this->successResponse(new CompteResource($compte));

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'COMPTE_NOT_FOUND',
                    'message' => 'Le compte avec l\'ID spécifié n\'existe pas',
                    'details' => [
                        'compteId' => $compteId
                    ]
                ]
            ], 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/comptes/{compteId}",
     *     summary="Supprimer un compte",
     *     description="Supprimer un compte bancaire (soft delete)",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="ID du compte à supprimer",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte supprimé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte supprimé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé"
     *     )
     * )
     */
    public function destroy(Request $request, Compte $compte)
    {
        $user = $request->user();
        $isAdmin = $user && $user->role === 'admin';

        // Vérifier les permissions
        if (!$isAdmin && $user) {
            // Client ne peut supprimer que ses propres comptes
            if (!$compte->client || $compte->client->telephone !== $user->telephone) {
                return $this->errorResponse('Accès refusé à ce compte', 403);
            }
        }

        $compte->delete();
        return $this->successResponse(null, 'Compte supprimé avec succès');
    }
}
