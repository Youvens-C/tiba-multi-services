<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AccessorySale extends Model
{
    use HasFactory;

    // Modifiable (correction de quantité) : updated_at est géré normalement
    // par Eloquent — plus de const UPDATED_AT = null.

    protected $fillable = [
        'accessory_id',
        'quantity',
        'unit_price',
        'total_amount',
        'sold_by',
        'created_at',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'quantity' => 'integer',
        'created_at' => 'datetime',
    ];

    // ========== RELATIONS ==========

    public function accessory()
    {
        return $this->belongsTo(Accessory::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    // ========== BOOT ==========

    protected static function booted()
    {
        static::creating(function ($sale) {
            $sale->total_amount = $sale->quantity * $sale->unit_price;
        });
    }

    // ========== SCOPES ==========

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('sold_by', $userId);
    }

    // ========== HELPERS ==========

    public static function createSale(int $accessoryId, int $quantity, float $unitPrice, int $userId): self
    {
        return DB::transaction(function () use ($accessoryId, $quantity, $unitPrice, $userId) {
            $accessory = Accessory::where('id', $accessoryId)->lockForUpdate()->first();

            if (!$accessory->hasStock($quantity)) {
                throw new \Exception('Stock insuffisant');
            }

            $sale = self::create([
                'accessory_id' => $accessoryId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'sold_by' => $userId,
            ]);

            $accessory->decrementStock($quantity);

            return $sale;
        });
    }

    /**
     * Corrige la quantité d'une vente déjà enregistrée et réajuste le stock
     * de la différence (augmentation → décrémente le stock ; réduction →
     * restitue la différence au stock).
     */
    public function updateQuantity(int $newQuantity): void
    {
        DB::transaction(function () use ($newQuantity) {
            $accessory = Accessory::where('id', $this->accessory_id)->lockForUpdate()->first();
            $delta = $newQuantity - $this->quantity;

            if ($delta > 0) {
                if ($accessory->stock_quantity < $delta) {
                    throw new \Exception('Stock insuffisant pour augmenter cette vente.');
                }
                $accessory->decrement('stock_quantity', $delta);
            } elseif ($delta < 0) {
                $accessory->increment('stock_quantity', abs($delta));
            }

            $this->quantity = $newQuantity;
            $this->total_amount = $newQuantity * (float) $this->unit_price;
            $this->save();
        });
    }

    public function canBeModifiedBy(User $user): bool
    {
        if ($user->role === 'directeur') {
            return true;
        }

        return $this->sold_by === $user->id && $this->created_at->isToday();
    }
}
