<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperatorPrefix extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator',
        'service',
        'prefix',
        'status',
    ];

    // ========== ACCESSORS ==========

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'actif';
    }

    // ========== SCOPES ==========

    public function scopeActive($query)
    {
        return $query->where('status', 'actif');
    }

    public function scopeByService($query, $service)
    {
        return $query->where('service', $service);
    }

    public function scopeByOperator($query, $operator)
    {
        return $query->where('operator', $operator);
    }

    // ========== HELPERS ==========

    public static function detectService(string $phoneNumber): ?array
    {
        // Accepte un numéro brut (« +509 3712-3456 ») ou déjà normalisé (8 chiffres).
        // Un numéro qui n'est pas un numéro haïtien valide n'a pas de préfixe.
        $phone = \App\Support\PhoneNumber::analyse($phoneNumber);
        if (!$phone['ok'] || $phone['kind'] !== \App\Support\PhoneNumber::KIND_HAITI) {
            return null;
        }
        $prefix = substr($phone['core'], 0, 2);

        $prefixData = self::where('prefix', $prefix)
            ->where('status', 'actif')
            ->first();

        if (!$prefixData) {
            return null;
        }

        return [
            'operator' => $prefixData->operator,
            'service' => $prefixData->service,
            'prefix' => $prefixData->prefix,
        ];
    }

    public static function getPrefixesByService(string $service): array
    {
        return self::where('service', $service)
            ->where('status', 'actif')
            ->pluck('prefix')
            ->toArray();
    }
}
