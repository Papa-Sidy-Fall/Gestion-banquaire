<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Models\Client;

class ClientController extends Controller
{
    public function index()
    {
        return response()->json(Client::with('comptes')->get());
    }

    public function store(StoreClientRequest $request)
    {
        $client = Client::create($request->validated());

        return response()->json([
            'message' => 'Client créé avec succès',
            'data' => $client
        ], 201);
    }

    public function show(Client $client)
    {
        return response()->json($client->load('comptes'));
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return response()->json(['message' => 'Client supprimé avec succès']);
    }
}
