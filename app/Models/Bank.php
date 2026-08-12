<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{
    protected $fillable = ['name', 'interest_rate', 'description', 'max_loan_amount', 'repayment_duration_days'];

    public function accounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }
}
