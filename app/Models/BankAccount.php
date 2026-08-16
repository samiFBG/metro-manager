<?php

namespace App\Models;

use App\Enums\Company\BankAccountType;
use App\Enums\Company\BankTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model

{
    use HasFactory;
    protected $fillable = ['company_id', 'bank_id', 'account_number', 'type'];

    protected $casts = [
        'type' => BankAccountType::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    // Calcul dynamique du solde du compte
    public function getBalanceAttribute(): float
    {
        $credits = $this->transactions()->where('type', BankTransactionType::CREDIT)->sum('amount');
        $debits = $this->transactions()->where('type', BankTransactionType::DEBIT)->sum('amount');

        return (float) ($credits - $debits);
    }
}
