<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Debt extends Model
{
    use HasFactory;

    protected $fillable = [
        'debtor_name',
        'reason',
        'initial_amount',
        'currency',
        'remaining_balance',
        'status',
        'created_by',
        'closed_at',
    ];

    protected $casts = [
        'initial_amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    // ========== RELATIONS ==========

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments()
    {
        return $this->hasMany(DebtPayment::class);
    }

    public function validPayments()
    {
        return $this->hasMany(DebtPayment::class)->where('status', 'valide');
    }

    // ========== ACCESSORS ==========

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getIsSettledAttribute(): bool
    {
        return $this->status === 'soldee';
    }

    public function getIsAbandonedAttribute(): bool
    {
        return $this->status === 'abandonnee';
    }

    public function getIsCancelledAttribute(): bool
    {
        return $this->status === 'annulee';
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->validPayments()->sum('amount_applied');
    }

    public function getIsFullyPaidAttribute(): bool
    {
        return $this->remaining_balance <= 0;
    }

    // ========== MUTATORS ==========

    public function setInitialAmountAttribute($value)
    {
        $this->attributes['initial_amount'] = round($value, 2);
    }

    public function setRemainingBalanceAttribute($value)
    {
        $this->attributes['remaining_balance'] = round($value, 2);
    }

    // ========== SCOPES ==========

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSettled($query)
    {
        return $query->where('status', 'soldee');
    }

    public function scopeAbandoned($query)
    {
        return $query->where('status', 'abandonnee');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'annulee');
    }

    public function scopeByCurrency($query, $currency)
    {
        return $query->where('currency', $currency);
    }

    // ========== HELPERS ==========

    public function applyPayment(float $amountPaid, int $userId): DebtPayment
    {
        if ($this->status !== 'active') {
            throw new \Exception('Cette dette n\'est plus active');
        }

        return DB::transaction(function () use ($amountPaid, $userId) {
            $amountApplied = min($amountPaid, $this->remaining_balance);
            $changeGiven = max(0, $amountPaid - $amountApplied);

            $payment = DebtPayment::create([
                'debt_id' => $this->id,
                'amount_paid' => $amountPaid,
                'amount_applied' => $amountApplied,
                'change_given' => $changeGiven,
                'created_by' => $userId,
            ]);

            $this->remaining_balance -= $amountApplied;

            if ($this->remaining_balance <= 0) {
                $this->status = 'soldee';
                $this->closed_at = now();
            }

            $this->save();

            return $payment;
        });
    }

    public function modifyInitialAmount(float $newAmount, int $userId): void
    {
        // Recalculer le solde en tenant compte des paiements déjà effectués
        $totalPaid = $this->total_paid;
        $this->initial_amount = round($newAmount, 2);
        $this->remaining_balance = round($newAmount - $totalPaid, 2);
        
        if ($this->remaining_balance <= 0) {
            $this->status = 'soldee';
            $this->closed_at = now();
        }
        elseif ($this->status === 'soldee') {
            $this->status = 'active';
            $this->closed_at = null;
         }

        $this->save();
    }

    public function abandon(int $userId): void
    {
        $this->status = 'abandonnee';
        $this->remaining_balance = 0;
        $this->closed_at = now();
        $this->save();
    }

    public function cancel(int $userId): void
    {
        $this->status = 'annulee';
        $this->remaining_balance = 0;
        $this->closed_at = now();
        $this->save();
    }

    public function canBeModifiedBy(User $user): bool
    {
        return $user->role === 'directeur';
    }
}
