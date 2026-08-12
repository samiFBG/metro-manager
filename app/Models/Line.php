<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Line extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'color_hex',
        'geo_json_path',
        'train_model_id',
        'min_cars_per_train',
        'max_cars_per_train',
    ];

    /**
     * Casts pour convertir automatiquement le GeoJSON en tableau PHP/JSON
     */
    protected $casts = [
        'geo_json_path' => 'array',
        'min_cars_per_train' => 'integer',
        'max_cars_per_train' => 'integer',
    ];

    public function dedicatedTrainModel(): BelongsTo
    {
        return $this->belongsTo(TrainModel::class, 'train_model_id');
    }

    public function stations(): HasMany
    {
        return $this->hasMany(Station::class)->orderBy('order');
    }

    public function depots(): HasMany
    {
        return $this->hasMany(Depot::class);
    }

    public function activeTrains(): HasMany
    {
        return $this->hasMany(Train::class, 'current_line_id');
    }
}
