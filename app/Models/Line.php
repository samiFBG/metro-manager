<?php

namespace App\Models;

use App\Enums\Lines\LineAutomationLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Line extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'color_hex',
        'automation_level', // 'automated', 'semi_automated', 'manual'
        'geo_json_path',
        'train_model_id',
        'min_cars_per_train',
        'max_cars_per_train',
    ];

    /**
     * Casts pour convertir automatiquement les types de colonnes
     */
    protected $casts = [
        'geo_json_path' => 'array',
        'min_cars_per_train' => 'integer',
        'max_cars_per_train' => 'integer',
        'automation_level' => LineAutomationLevel::class,
    ];

    public function dedicatedTrainModel(): BelongsTo
    {
        return $this->belongsTo(TrainModel::class, 'train_model_id');
    }

    public function stations(): HasMany
    {
        return $this->hasMany(Station::class)->orderBy('order');
    }

    public function depots(): BelongsToMany
    {
        return $this->belongsToMany(Depot::class);
    }

    public function activeTrains(): HasMany
    {
        return $this->hasMany(Train::class, 'current_line_id');
    }
}
