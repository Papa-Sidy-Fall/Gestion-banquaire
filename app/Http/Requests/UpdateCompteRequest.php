<?php

namespace App\Http\Requests;

use App\Rules\SenegaleseNciRule;
use App\Rules\SenegalesePhoneRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Champs du compte (optionnels)
            'titulaire' => 'sometimes|string|min:2|max:255',
            'type' => 'sometimes|in:cheque,epargne',
            'solde' => 'sometimes|numeric|min:0',
            'devise' => 'sometimes|string|in:FCFA,EUR,USD',
            'statut' => 'sometimes|in:actif,bloque,ferme',
            'motifBlocage' => 'sometimes|string|max:500',

            // Champs du client (optionnels)
            'informationsClient' => 'sometimes|array',
            'informationsClient.telephone' => ['sometimes', 'nullable', new SenegalesePhoneRule(), 'unique:clients,telephone,' . $this->route('compte')->client_id],
            'informationsClient.email' => 'sometimes|nullable|email|unique:clients,email,' . $this->route('compte')->client_id,
            'informationsClient.password' => 'sometimes|nullable|string|min:8',
            'informationsClient.nci' => ['sometimes', 'nullable', new SenegaleseNciRule()],
            'informationsClient.adresse' => 'sometimes|nullable|string|max:500',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Vérifier qu'au moins un champ est fourni
            $compteFields = ['titulaire', 'type', 'solde', 'devise', 'statut', 'motifBlocage'];
            $clientFields = ['informationsClient'];

            $hasCompteField = false;
            foreach ($compteFields as $field) {
                if ($this->has($field)) {
                    $hasCompteField = true;
                    break;
                }
            }

            $hasClientField = $this->has('informationsClient') &&
                             !empty(array_filter($this->input('informationsClient', [])));

            if (!$hasCompteField && !$hasClientField) {
                $validator->errors()->add('general', 'Au moins un champ doit être fourni pour la mise à jour.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'titulaire.min' => 'Le nom du titulaire doit contenir au moins 2 caractères.',
            'titulaire.max' => 'Le nom du titulaire ne peut pas dépasser 255 caractères.',
            'type.in' => 'Le type de compte doit être cheque ou epargne.',
            'solde.numeric' => 'Le solde doit être un nombre.',
            'solde.min' => 'Le solde ne peut pas être négatif.',
            'devise.in' => 'La devise doit être FCFA, EUR ou USD.',
            'statut.in' => 'Le statut doit être actif, bloque ou ferme.',
            'motifBlocage.max' => 'Le motif de blocage ne peut pas dépasser 500 caractères.',
            'informationsClient.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'informationsClient.email.email' => 'L\'email doit être une adresse email valide.',
            'informationsClient.email.unique' => 'Cet email est déjà utilisé.',
            'informationsClient.password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'informationsClient.adresse.max' => 'L\'adresse ne peut pas dépasser 500 caractères.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'titulaire' => 'nom du titulaire',
            'type' => 'type de compte',
            'solde' => 'solde',
            'devise' => 'devise',
            'statut' => 'statut',
            'motifBlocage' => 'motif de blocage',
            'informationsClient.telephone' => 'numéro de téléphone',
            'informationsClient.email' => 'email',
            'informationsClient.password' => 'mot de passe',
            'informationsClient.nci' => 'numéro NCI',
            'informationsClient.adresse' => 'adresse',
        ];
    }
}
