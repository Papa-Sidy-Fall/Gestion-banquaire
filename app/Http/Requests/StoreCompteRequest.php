<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'type_compte' => 'required|string|in:Courant,Épargne,Business',
            'solde' => 'nullable|numeric|min:0',
        ];
    }
}
