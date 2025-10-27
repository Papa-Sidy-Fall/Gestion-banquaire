<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompteRequest;
use App\Models\Compte;
use Illuminate\Http\Request;

class CompteController extends Controller
{
    public function index()
    {
        return response()->json(Compte::with('client')->get());
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
