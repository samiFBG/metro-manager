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
        'operating_speed',
        'composition_pattern', // ["M", "R", "M"]
        'car_width',          // Ex: 2.89
        'max_coupled_units', // 1 = US, 2 = UM2, etc.
    ];

    protected $casts = [
        'composition_pattern' => 'array',
        'car_width' => 'float',
        'max_coupled_units' => 'integer',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(Line::class);
    }

    public function trains(): HasMany
    {
        return $this->hasMany(Train::class);
    }

    public function cars(): HasMany
    {
        return $this->hasMany(TrainCar::class);
    }
}
