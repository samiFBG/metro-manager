<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'base_price',
        'capacity_per_car',
        'electricity_cost_per_km',
        'maintenance_cost_factor',
        'max_speed',
        'operating_speed', // Added for real commercial operating speed
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(Line::class);
    }

    public function trains(): HasMany
    {
        return $this->hasMany(Train::class);
    }
}
