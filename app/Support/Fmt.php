<?php

namespace App\Support;

class Fmt
{
    /** 12 345,50 */
    public static function num($value, int $decimals = 2): string
    {
        return number_format((float) $value, $decimals, ',', ' ');
    }

    /** 12 345,50 HTG */
    public static function money($value, string $currency = 'HTG'): string
    {
        return self::num($value) . ' ' . $currency;
    }

    /** Logo embarqué en base64 pour les PDF (DomPDF n'a pas besoin d'accès réseau). */
    public static function logo(): ?string
    {
        foreach (['images/logo_tiba.jpg', 'img/logo_tiba.jpg', 'assets/logo_tiba.jpg', 'logo_tiba.jpg'] as $relative) {
            $path = public_path($relative);
            if (is_file($path)) {
                return 'data:image/jpeg;base64,' . base64_encode(file_get_contents($path));
            }
        }

        return null;
    }
}
