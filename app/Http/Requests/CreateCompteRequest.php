<?php

namespace App\Http\Requests;

use App\Rules\SenegaleseNciRule;
use App\Rules\SenegalesePhoneRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Autoriser pour le moment
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Champs du compte
            'type' => 'required|in:cheque,epargne',
            'soldeInitial' => 'required|numeric|min:10000',
            'devise' => 'required|string|in:FCFA,EUR,USD',
            'solde' => 'sometimes|numeric|min:0',

            // Champs du client
            'client' => 'required|array',
            'client.id' => 'nullable|uuid|exists:clients,id',
            'client.titulaire' => 'required_if:client.id,null|string|min:2|max:255',
            'client.nci' => ['required_if:client.id,null', 'nullable', new SenegaleseNciRule()],
            'client.email' => 'required_if:client.id,null|nullable|email|unique:clients,email',
            'client.telephone' => ['required_if:client.id,null', 'nullable', new SenegalesePhoneRule(), 'unique:clients,telephone'],
            'client.adresse' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Le type de compte est obligatoire.',
            'type.in' => 'Le type de compte doit être cheque ou epargne.',
            'soldeInitial.required' => 'Le solde initial est obligatoire.',
            'soldeInitial.numeric' => 'Le solde initial doit être un nombre.',
            'soldeInitial.min' => 'Le solde initial doit être d\'au moins 10 000 FCFA.',
            'devise.required' => 'La devise est obligatoire.',
            'devise.in' => 'La devise doit être FCFA, EUR ou USD.',
            'client.required' => 'Les informations du client sont obligatoires.',
            'client.array' => 'Les informations du client doivent être un objet.',
            'client.id.uuid' => 'L\'ID du client doit être un UUID valide.',
            'client.id.exists' => 'Le client spécifié n\'existe pas.',
            'client.titulaire.required_if' => 'Le nom du titulaire est obligatoire pour un nouveau client.',
            'client.titulaire.min' => 'Le nom du titulaire doit contenir au moins 2 caractères.',
            'client.titulaire.max' => 'Le nom du titulaire ne peut pas dépasser 255 caractères.',
            'client.nci.required_if' => 'Le numéro NCI est obligatoire pour un nouveau client.',
            'client.email.required_if' => 'L\'email est obligatoire pour un nouveau client.',
            'client.email.email' => 'L\'email doit être une adresse email valide.',
            'client.email.unique' => 'Cet email est déjà utilisé.',
            'client.telephone.required_if' => 'Le numéro de téléphone est obligatoire pour un nouveau client.',
            'client.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'client.adresse.max' => 'L\'adresse ne peut pas dépasser 500 caractères.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'type' => 'type de compte',
            'soldeInitial' => 'solde initial',
            'devise' => 'devise',
            'client.titulaire' => 'nom du titulaire',
            'client.nci' => 'numéro NCI',
            'client.email' => 'email',
            'client.telephone' => 'numéro de téléphone',
            'client.adresse' => 'adresse',
        ];
    }
}
