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
        // Ne garder que les chiffres, puis retirer l'indicatif pays (509)
        // si le numéro a été saisi avec (ex. "+509 3712-3456").
        $cleaned = preg_replace('/\D/', '', $phoneNumber);
        if (strlen($cleaned) >= 10 && str_starts_with($cleaned, '509')) {
            $cleaned = substr($cleaned, 3);
        }
        $prefix = substr($cleaned, 0, 2);

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
