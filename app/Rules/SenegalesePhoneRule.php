<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SenegalesePhoneRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Nettoyer le numéro (supprimer espaces, tirets, etc.)
        $cleaned = preg_replace('/\s+|-+|\.+/', '', $value);

        // Formats acceptés pour les numéros sénégalais
        // +221771234567, 00221771234567, 771234567, 221771234567
        $patterns = [
            '/^\+221(77|78|76|70|75|33)\d{7}$/',  // +221 + indicatif + 7 chiffres
            '/^00221(77|78|76|70|75|33)\d{7}$/', // 00221 + indicatif + 7 chiffres
            '/^(77|78|76|70|75|33)\d{7}$/',       // indicatif + 7 chiffres
            '/^221(77|78|76|70|75|33)\d{7}$/',    // 221 + indicatif + 7 chiffres
        ];

        $isValid = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $cleaned)) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            $fail('Le numéro de téléphone doit être un numéro sénégalais valide (ex: +221771234567, 771234567).');
        }
    }
}
