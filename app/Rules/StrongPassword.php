<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Politique de mot de passe (CDC 6.5) : 8 caractères minimum, au moins une
 * majuscule, un chiffre et un caractère spécial. Un seul endroit à modifier.
 * (À garder synchronisé avec public/js/users.js.)
 */
class StrongPassword implements ValidationRule
{
    public const MIN_LENGTH = 8;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $v = (string) $value;

        if (mb_strlen($v) < self::MIN_LENGTH) {
            $fail('Le mot de passe doit contenir au moins ' . self::MIN_LENGTH . ' caractères.');
            return;
        }
        if (!preg_match('/\p{Lu}/u', $v)) {
            $fail('Le mot de passe doit contenir au moins une majuscule.');
            return;
        }
        if (!preg_match('/[0-9]/', $v)) {
            $fail('Le mot de passe doit contenir au moins un chiffre.');
            return;
        }
        if (!preg_match('/[^\p{L}\p{N}]/u', $v)) {
            $fail('Le mot de passe doit contenir au moins un caractère spécial (ex. @ # $ ! %).');
        }
    }
}
