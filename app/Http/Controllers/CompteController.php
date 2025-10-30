<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompteRequest;
use App\Http\Requests\CreateCompteRequest;
use App\Http\Requests\UpdateCompteRequest;
use App\Http\Requests\BloquerCompteRequest;
use App\Models\Compte;
use App\Http\Resources\CompteResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Info(
 *     title="API Banque",
 *     version="1.0.0",
 *     description="API pour la gestion des comptes bancaires"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api/v1",
 *     description="Serveur de développement local"
 * )
 *
 * @OA\Server(
 *     url="https://gestion-banquaire.onrender.com/api/v1",
 *     description="Serveur de production"
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
     *     description="Créer un nouveau compte bancaire. Si le client n'existe pas, il sera créé automatiquement avec génération de mot de passe et code de vérification.",
     *     tags={"Comptes"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "soldeInitial", "devise", "client"},
     *             @OA\Property(property="type", type="string", enum={"cheque", "epargne"}, example="cheque"),
     *             @OA\Property(property="soldeInitial", type="number", minimum=10000, example=500000),
     *             @OA\Property(property="devise", type="string", enum={"FCFA", "EUR", "USD"}, example="FCFA"),
     *             @OA\Property(property="solde", type="number", example=10000),
     *             @OA\Property(
     *                 property="client",
     *                 type="object",
     *                 required={"titulaire", "nci", "email", "telephone"},
     *                 @OA\Property(property="id", type="string", format="uuid", description="ID du client existant (optionnel)"),
     *                 @OA\Property(property="titulaire", type="string", example="Hawa BB Wane"),
     *                 @OA\Property(property="nci", type="string", example="1234567890123", description="Numéro NCI sénégalais"),
     *                 @OA\Property(property="email", type="string", format="email", example="cheikh.sy@example.com"),
     *                 @OA\Property(property="telephone", type="string", example="+221771234567", description="Numéro téléphone sénégalais"),
     *                 @OA\Property(property="adresse", type="string", example="Dakar, Sénégal")
     *             )
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
     *         response=400,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="VALIDATION_ERROR"),
     *                 @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
     *                 @OA\Property(property="details", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation des données"
     *     )
     * )
     */
    /**
     * @OA\Patch(
     *     path="/comptes/{compteId}",
     *     summary="Mettre à jour les informations du client",
     *     description="Mettre à jour les informations du client. Tous les champs sont optionnels mais au moins un champ doit être fourni.",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="ID du compte",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="titulaire", type="string", example="Amadou Diallo Junior"),
     *             @OA\Property(property="type", type="string", enum={"cheque", "epargne"}, example="epargne"),
     *             @OA\Property(property="solde", type="number", example=1500000),
     *             @OA\Property(property="devise", type="string", enum={"FCFA", "EUR", "USD"}, example="FCFA"),
     *             @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, example="actif"),
     *             @OA\Property(property="motifBlocage", type="string", example="Raison du blocage"),
     *             @OA\Property(
     *                 property="informationsClient",
     *                 type="object",
     *                 @OA\Property(property="telephone", type="string", example="+221771234568"),
     *                 @OA\Property(property="email", type="string", format="email", example="nouveau@email.com"),
     *                 @OA\Property(property="password", type="string", example="nouveauMotDePasse"),
     *                 @OA\Property(property="nci", type="string", example="1234567890124"),
     *                 @OA\Property(property="adresse", type="string", example="Nouvelle adresse")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Informations mises à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte mis à jour avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de validation - Aucun champ fourni",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Au moins un champ doit être fourni pour la mise à jour."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation des données"
     *     )
     * )
     */
    public function update(UpdateCompteRequest $request, Compte $compte)
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

        // Utiliser une transaction pour assurer l'intégrité des données
        return DB::transaction(function () use ($request, $compte) {
            // Mise à jour des informations du compte
            $compteData = $request->only(['titulaire', 'type', 'solde', 'devise', 'statut', 'motifBlocage']);

            if (!empty($compteData)) {
                // Si titulaire est fourni, mettre à jour le client
                if (isset($compteData['titulaire'])) {
                    $nomPrenom = explode(' ', $compteData['titulaire'], 2);
                    $compte->client->update([
                        'nom' => $nomPrenom[1] ?? $compteData['titulaire'],
                        'prenom' => $nomPrenom[0] ?? '',
                    ]);
                    unset($compteData['titulaire']); // Retirer du tableau compte
                }

                // Mettre à jour le type_compte si fourni
                if (isset($compteData['type'])) {
                    $compteData['type_compte'] = $compteData['type'];
                    unset($compteData['type']);
                }

                // Mettre à jour le compte
                $compte->update($compteData);
            }

            // Mise à jour des informations du client
            $clientData = $request->input('informationsClient', []);
            if (!empty($clientData)) {
                // Hash le mot de passe si fourni
                if (isset($clientData['password']) && !empty($clientData['password'])) {
                    $clientData['password'] = bcrypt($clientData['password']);
                } else {
                    unset($clientData['password']); // Ne pas mettre à jour si vide
                }

                $compte->client->update($clientData);
            }

            // Recharger les relations
            $compte->load('client');

            return $this->successResponse(new CompteResource($compte), 'Compte mis à jour avec succès', 200);
        });
    }

    public function store(CreateCompteRequest $request)
    {
        // Utiliser une transaction pour assurer l'intégrité des données
        return DB::transaction(function () use ($request) {
            $clientData = $request->input('client');
            $compteData = $request->except('client');

            // Vérifier si le client existe ou le créer
            $client = $this->findOrCreateClient($clientData);

            // Créer le compte
            $compteData['client_id'] = $client->id;
            $compteData['solde'] = $request->input('soldeInitial', 0); // Le solde initial devient le solde
            $compteData['type_compte'] = $request->input('type');
            $compteData['statut'] = 'actif';

            $compte = Compte::create($compteData);

            // Charger la relation client pour la réponse
            $compte->load('client');

            return $this->successResponse(new CompteResource($compte), 'Compte créé avec succès', 201);
        });
    }

    /**
     * Trouver un client existant ou en créer un nouveau
     */
    private function findOrCreateClient(array $clientData): \App\Models\Client
    {
        // Si un ID de client est fourni, vérifier qu'il existe
        if (isset($clientData['id']) && $clientData['id']) {
            $client = \App\Models\Client::find($clientData['id']);
            if (!$client) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'client.id' => 'Le client spécifié n\'existe pas.'
                ]);
            }
            return $client;
        }

        // Créer un nouveau client
        $nomPrenom = explode(' ', $clientData['titulaire'], 2);
        return \App\Models\Client::create([
            'nom' => $nomPrenom[1] ?? $clientData['titulaire'],
            'prenom' => $nomPrenom[0] ?? '',
            'email' => $clientData['email'],
            'telephone' => $clientData['telephone'],
            'nci' => $clientData['nci'],
            'adresse' => $clientData['adresse'] ?? null,
        ]);
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

    /**
     * @OA\Post(
     *     path="/comptes/{compteId}/bloquer",
     *     summary="Bloquer un compte",
     *     description="Bloquer un compte bancaire pour une durée déterminée avec archivage automatique",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="ID du compte à bloquer",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"motifBlocage", "dureeBlocage"},
     *             @OA\Property(property="motifBlocage", type="string", example="Inactivité de 30+ jours"),
     *             @OA\Property(property="dureeBlocage", type="integer", minimum=1, maximum=365, example=30, description="Durée en jours")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte bloqué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte bloqué avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
     */
    public function bloquer(BloquerCompteRequest $request, Compte $compte)
    {
        $user = $request->user();
        $isAdmin = $user && $user->role === 'admin';

        // Vérifier les permissions
        if (!$isAdmin) {
            return $this->errorResponse('Seul un administrateur peut bloquer un compte', 403);
        }

        // Vérifier que le compte n'est pas déjà bloqué
        if ($compte->statut === 'bloque') {
            return $this->errorResponse('Le compte est déjà bloqué', 422);
        }

        // Utiliser une transaction pour assurer l'intégrité des données
        return DB::transaction(function () use ($request, $compte) {
            $now = now();
            $dureeBlocage = $request->input('dureeBlocage');

            $compte->update([
                'statut' => 'bloque',
                'motifBlocage' => $request->input('motifBlocage'),
                'date_debut_blocage' => $now,
                'date_fin_blocage' => $now->copy()->addDays($dureeBlocage),
            ]);

            // Recharger les relations
            $compte->load('client');

            return $this->successResponse(new CompteResource($compte), 'Compte bloqué avec succès');
        });
    }
}
