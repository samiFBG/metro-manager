<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransaction extends Model
{
    public $timestamps = false; // Géré par created_at nativement

    protected $fillable = [
        'bank_account_id',
        'type',
        'category',
        'amount',
        'description',
        'created_at',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
}
