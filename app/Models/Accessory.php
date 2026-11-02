<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Accessory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'unit_price',
        'stock_quantity',
        'low_stock_threshold',
        'status',
        'created_by',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
    ];

    // ========== RELATIONS ==========

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sales()
    {
        return $this->hasMany(AccessorySale::class);
    }

    // ========== ACCESSORS ==========

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'actif';
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold && $this->stock_quantity > 0;
    }

    public function getIsOutOfStockAttribute(): bool
    {
        return $this->stock_quantity <= 0;
    }

    // ========== MUTATORS ==========

    public function setUnitPriceAttribute($value)
    {
        $this->attributes['unit_price'] = round($value, 2);
    }

    // ========== SCOPES ==========

    public function scopeActive($query)
    {
        return $query->where('status', 'actif');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactif');
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('stock_quantity', '>', 0);
    }

    // ========== HELPERS ==========

    public function hasStock(int $quantity): bool
    {
        return $this->stock_quantity >= $quantity && $this->status === 'actif';
    }

    public function decrementStock(int $quantity): void
    {
        if (!$this->hasStock($quantity)) {
            throw new \Exception('Stock insuffisant ou produit inactif');
        }

        $this->decrement('stock_quantity', $quantity);
    }

    public function incrementStock(int $quantity): void
    {
        $this->increment('stock_quantity', $quantity);
    }

    public function deactivate(): void
    {
        $this->status = 'inactif';
        $this->save();
    }

    public function activate(): void
    {
        $this->status = 'actif';
        $this->save();
    }
}
