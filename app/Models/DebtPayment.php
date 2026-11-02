<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DebtPayment extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'debt_id',
        'amount_paid',
        'amount_applied',
        'change_given',
        'status',
        'created_by',
        'cancelled_by',
        'cancelled_at',
        'created_at',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'amount_applied' => 'decimal:2',
        'change_given' => 'decimal:2',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // ========== RELATIONS ==========

    public function debt()
    {
        return $this->belongsTo(Debt::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    // ========== ACCESSORS ==========

    public function getIsValidAttribute(): bool
    {
        return $this->status === 'valide';
    }

    public function getIsCancelledAttribute(): bool
    {
        return $this->status === 'annule';
    }

    // ========== SCOPES ==========

    public function scopeValid($query)
    {
        return $query->where('status', 'valide');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'annule');
    }

    // ========== HELPERS ==========

    public function cancel(int $userId): void
    {
        if ($this->status === 'annule') {
            throw new \Exception('Ce paiement est déjà annulé');
        }

        DB::transaction(function () use ($userId) {
            $debt = $this->debt;
            $debt->remaining_balance += $this->amount_applied;

            if ($debt->status === 'soldee') {
                $debt->status = 'active';
                $debt->closed_at = null;
            }

            $this->status = 'annule';
            $this->cancelled_by = $userId;
            $this->cancelled_at = now();

            $debt->save();
            $this->save();
        });
    }
}
