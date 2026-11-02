<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'service',
        'movement_type',
        'client_phone',
        'amount',
        'fee_client',
        'fee_net',
        'currency',
        'status',
        'detected_prefix',
        'created_by',
        'last_updated_by',
        'cancelled_by',
        'last_updated_at',
        'cancelled_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee_client' => 'decimal:2',
        'fee_net' => 'decimal:2',
        'last_updated_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Libellés d'affichage courts pour les mouvements sans frais.
    private const MOVEMENT_LABELS = [
        'retrait' => 'Retrait',
        'transfert' => 'Transfert',
        'depot' => 'Dépôt',
        'pap_padap' => 'Pap Padap',
        'lapoula' => 'Lapoula',
        'depot_paryaj_pam' => 'Paryaj Pam',
        'depot_paryaj_lakay' => 'Paryaj Lakay',
        'depot_lotto_mobile' => 'Lotto Mobile',
    ];

    // Mouvements qui DIMINUENT le solde mobile de l'agent (Moncash/Natcash
    // uniquement) — utilisés pour le calcul du solde théorique (CDC 4.2).
    // Le seul mouvement qui AUGMENTE le solde est 'retrait'.
    public const BALANCE_DECREASE_MOVEMENTS = [
        'transfert', 'depot', 'pap_padap', 'lapoula',
        'depot_paryaj_pam', 'depot_paryaj_lakay', 'depot_lotto_mobile',
    ];

    // ========== RELATIONS ==========

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lastUpdatedBy()
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    // ========== ACCESSORS ==========

    public function getIsMoncashAttribute(): bool
    {
        return $this->service === 'moncash';
    }

    public function getIsNatcashAttribute(): bool
    {
        return $this->service === 'natcash';
    }

    public function getIsZelleAttribute(): bool
    {
        return $this->service === 'zelle';
    }

    public function getIsValidAttribute(): bool
    {
        return $this->status === 'validee';
    }

    public function getIsCancelledAttribute(): bool
    {
        return $this->status === 'annulee';
    }

    public function getIsRetraitAttribute(): bool
    {
        return $this->movement_type === 'retrait';
    }

    public function getIsTransfertAttribute(): bool
    {
        return $this->movement_type === 'transfert';
    }

    public function getIsDepotAttribute(): bool
    {
        return $this->movement_type === 'depot';
    }

    public function getMovementLabelAttribute(): string
    {
        return self::MOVEMENT_LABELS[$this->movement_type] ?? ucfirst($this->movement_type);
    }

    // ========== MUTATORS ==========

    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = round($value, 2);
    }

    public function setFeeClientAttribute($value)
    {
        $this->attributes['fee_client'] = round($value, 2);
    }

    public function setFeeNetAttribute($value)
    {
        $this->attributes['fee_net'] = round($value, 2);
    }

    // ========== SCOPES ==========

    public function scopeValid($query)
    {
        return $query->where('status', 'validee');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'annulee');
    }

    public function scopeByService($query, $service)
    {
        return $query->where('service', $service);
    }

    public function scopeByCurrency($query, $currency)
    {
        return $query->where('currency', $currency);
    }

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
        return $query->where('created_by', $userId);
    }

    // ========== HELPERS ==========

    public function cancel(User $user): void
    {
        $this->status = 'annulee';
        $this->cancelled_by = $user->id;
        $this->cancelled_at = now();
        $this->save();
    }

    public function canBeModifiedBy(User $user): bool
    {
        if ($this->status !== 'validee') {
            return false;
        }

        if ($user->role === 'directeur') {
            return true;
        }

        return $this->created_by === $user->id && $this->created_at->isToday();
    }

    public function canBeCancelledBy(User $user): bool
    {
        return $user->role === 'directeur' && $this->status === 'validee';
    }
}
