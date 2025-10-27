<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index()
    {
        return response()->json(Transaction::with('compte')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'compte_id' => 'required|exists:comptes,id',
            'type' => 'required|string|in:Dépôt,Retrait,Transfert',
            'montant' => 'required|numeric|min:1',
        ]);

        $transaction = Transaction::create($validated);

        return response()->json([
            'message' => 'Transaction créée avec succès',
            'data' => $transaction
        ], 201);
    }

    public function show(Transaction $transaction)
    {
        return response()->json($transaction->load('compte'));
    }

    public function destroy(Transaction $transaction)
    {
        $transaction->delete();
        return response()->json(['message' => 'Transaction supprimée']);
    }
}
