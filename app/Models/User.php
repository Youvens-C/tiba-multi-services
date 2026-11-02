<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'password',
        'role',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ========== RELATIONS ==========
    
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }

    public function lastUpdatedTransactions()
    {
        return $this->hasMany(Transaction::class, 'last_updated_by');
    }

    public function cancelledTransactions()
    {
        return $this->hasMany(Transaction::class, 'cancelled_by');
    }

    public function dailyOpeningBalances()
    {
        return $this->hasMany(DailyOpeningBalance::class, 'entered_by');
    }

    public function accessories()
    {
        return $this->hasMany(Accessory::class, 'created_by');
    }

    public function accessorySales()
    {
        return $this->hasMany(AccessorySale::class, 'sold_by');
    }

    public function debts()
    {
        return $this->hasMany(Debt::class, 'created_by');
    }

    public function debtPayments()
    {
        return $this->hasMany(DebtPayment::class, 'created_by');
    }

    public function cancelledDebtPayments()
    {
        return $this->hasMany(DebtPayment::class, 'cancelled_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    // ========== ACCESSORS & MUTATORS ==========

    public function getIsAdminAttribute(): bool
    {
        return $this->role === 'directeur';
    }

    public function getIsEmployeeAttribute(): bool
    {
        return $this->role === 'employe';
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'actif';
    }

    // ========== SCOPES ==========

    public function scopeActive($query)
    {
        return $query->where('status', 'actif');
    }

    public function scopeDirecteurs($query)
    {
        return $query->where('role', 'directeur');
    }

    public function scopeEmployes($query)
    {
        return $query->where('role', 'employe');
    }
}
