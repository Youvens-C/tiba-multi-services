<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'service',
        'movement_type',
        'min_amount',
        'max_amount',
        'fee',
        'status',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'fee' => 'decimal:2',
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

    public function scopeByMovementType($query, $movementType)
    {
        return $query->where('movement_type', $movementType);
    }

    // ========== HELPERS ==========

    public static function findFee(string $service, string $movementType, float $amount): ?float
    {
        $fee = self::where('service', $service)
            ->where('movement_type', $movementType)
            ->where('status', 'actif')
            ->where('min_amount', '<=', $amount)
            ->where('max_amount', '>=', $amount)
            ->first();

        return $fee ? $fee->fee : null;
    }
}
