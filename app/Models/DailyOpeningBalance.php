<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyOpeningBalance extends Model
{
    use HasFactory;

    // Modifiable pendant 5 minutes après la saisie (décision Option B) :
    // on laisse donc Eloquent gérer updated_at normalement (plus de
    // const UPDATED_AT = null).

    protected $fillable = [
        'service',
        'balance_date',
        'opening_balance',
        'entered_by',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'balance_date' => 'date',
    ];

    // Fenêtre de modification autorisée après la saisie initiale.
    public const EDIT_WINDOW_MINUTES = 5;

    // ========== RELATIONS ==========

    public function enteredBy()
    {
        return $this->belongsTo(User::class, 'entered_by');
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

    // ========== SCOPES ==========

    public function scopeByService($query, $service)
    {
        return $query->where('service', $service);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('balance_date', $date);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('balance_date', today());
    }

    // ========== HELPERS ==========

    public static function getTodayBalance(string $service): ?float
    {
        $balance = self::where('service', $service)
            ->whereDate('balance_date', today())
            ->first();

        return $balance ? (float) $balance->opening_balance : null;
    }

    public function isEditable(): bool
    {
        return $this->created_at->diffInMinutes(now()) <= self::EDIT_WINDOW_MINUTES;
    }
}
