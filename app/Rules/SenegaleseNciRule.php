<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SenegaleseNciRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Nettoyer le NCI (supprimer espaces, tirets, etc.)
        $cleaned = preg_replace('/\s+|-+|\.+/', '', $value);

        // Format NCI sénégalais : 13 chiffres ou 14 avec lettre
        // Exemples valides : 1234567890123, 12345678901234, 1A234567890123
        $patterns = [
            '/^\d{13}$/',           // 13 chiffres
            '/^\d{14}$/',           // 14 chiffres
            '/^\d{1}[A-Z]\d{12}$/', // 1 chiffre + 1 lettre + 12 chiffres
        ];

        $isValid = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $cleaned)) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            $fail('Le numéro NCI doit être un numéro sénégalais valide (13 ou 14 chiffres, ou 1 chiffre + 1 lettre + 12 chiffres).');
        }
    }
}
