<?php

namespace App\Models;

use App\Enums\Company\BankTransactionCategory;
use App\Enums\Company\BankTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransaction extends Model
{
    use HasFactory;
    public $timestamps = false; // Géré par created_at nativement

    protected $fillable = [
        'bank_account_id',
        'type',
        'category',
        'amount',
        'description',
        'created_at',
    ];

    protected $casts = [
        'type' => BankTransactionType::class,
        'category' => BankTransactionCategory::class,
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
}
