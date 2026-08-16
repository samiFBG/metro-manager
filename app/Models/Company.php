<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'logo_url'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Un seul compte courant par entreprise
    public function bankAccount(): HasOne
    {
        return $this->hasOne(BankAccount::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function trains(): HasMany
    {
        return $this->hasMany(Train::class);
    }

    public function depots(): HasMany
    {
        return $this->hasMany(Depot::class);
    }

    // Récupère le solde du compte principal
    public function getTotalBalanceAttribute(): float
    {
        return (float) ($this->bankAccount?->balance ?? 0.00);
    }

    public function trainCars(): HasMany
    {
        return $this->hasMany(TrainCar::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Company $company) {
            if ($company->logo_url) {
                Storage::disk('public')->delete($company->logo_url);
            }

            // Libérer les dépôts
            $company->depots()->update(['company_id' => null]);

            // Nettoyage du compte unique et de ses transactions
            if ($company->bankAccount) {
                $company->bankAccount->transactions()->delete();
                $company->bankAccount->delete();
            }

            $company->loans()->delete();
            $company->trains()->delete();
        });
    }
}
