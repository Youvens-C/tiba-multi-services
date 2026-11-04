<?php

namespace App\Support;

/**
 * Analyse et validation des numéros de téléphone saisis par les employés.
 *
 *  - Haïti (Moncash / Natcash) : 8 chiffres, indicatif +509 toléré.
 *  - États-Unis (Zelle)        : 10 chiffres, indicatif +1 toléré.
 *
 * Un numéro à 10 chiffres est donc automatiquement un numéro Zelle.
 * La même logique existe côté navigateur (public/js/transactions.js) pour
 * l'aide à la saisie ; le serveur reste la source de vérité.
 */
final class PhoneNumber
{
    public const KIND_HAITI = 'haiti';
    public const KIND_US = 'us';

    /**
     * Plan de numérotation nord-américain : l'indicatif régional et le code
     * du central ne commencent jamais par 0 ou 1. Mettre à false pour ne
     * contrôler que la longueur.
     */
    public const STRICT_NANP = true;

    /**
     * @return array{ok: bool, kind: ?string, core: ?string, error: ?string}
     */
    public static function analyse(?string $raw): array
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return self::fail('Le numéro de téléphone est obligatoire.');
        }

        // Seuls chiffres, espaces, points, tirets et parenthèses sont tolérés ;
        // un « + » n'est accepté qu'au tout début, et le numéro doit commencer
        // par un chiffre (ou « + », « ( » suivis d'un chiffre).
        if (! preg_match('/^\+?\(?\d[\d\s().\-]*$/', $raw)) {
            if (preg_match('/^[\-.]/', $raw)) {
                return self::fail('Un numéro ne peut pas commencer par un tiret ou un point.');
            }

            return self::fail('Numéro invalide : chiffres uniquement (espaces, tirets, points et parenthèses tolérés).');
        }

        $digits = preg_replace('/\D/', '', $raw);
        $len = strlen($digits);

        if ($len === 8) {
            return self::ok(self::KIND_HAITI, $digits);
        }
        if ($len === 11 && str_starts_with($digits, '509')) {
            return self::ok(self::KIND_HAITI, substr($digits, 3));
        }

        $us = null;
        if ($len === 10) {
            $us = $digits;
        } elseif ($len === 11 && str_starts_with($digits, '1')) {
            $us = substr($digits, 1);
        }

        if ($us !== null) {
            if (self::STRICT_NANP && ! preg_match('/^[2-9]\d{2}[2-9]\d{6}$/', $us)) {
                return self::fail("Numéro américain invalide : l'indicatif régional et les 3 chiffres suivants ne commencent ni par 0 ni par 1.");
            }

            return self::ok(self::KIND_US, $us);
        }

        return self::fail('Numéro invalide : 8 chiffres pour Moncash / Natcash, 10 chiffres pour Zelle.');
    }

    /**
     * Valide un numéro pour un service donné et renvoie le numéro normalisé
     * (sans indicatif, chiffres seulement).
     *
     * @return array{ok: bool, kind: ?string, core: ?string, error: ?string}
     */
    public static function forService(?string $raw, string $service): array
    {
        $result = self::analyse($raw);
        if (! $result['ok']) {
            return $result;
        }

        if ($service === 'zelle' && $result['kind'] !== self::KIND_US) {
            return self::fail('Le numéro Zelle doit comporter 10 chiffres (hors indicatif +1).');
        }
        if ($service !== 'zelle' && $result['kind'] !== self::KIND_HAITI) {
            return self::fail('Un numéro à 10 chiffres correspond à Zelle. Pour ' . ucfirst($service) . ', saisissez 8 chiffres (hors indicatif +509).');
        }

        return $result;
    }

    private static function ok(string $kind, string $core): array
    {
        return ['ok' => true, 'kind' => $kind, 'core' => $core, 'error' => null];
    }

    private static function fail(string $message): array
    {
        return ['ok' => false, 'kind' => null, 'core' => null, 'error' => $message];
    }
}
