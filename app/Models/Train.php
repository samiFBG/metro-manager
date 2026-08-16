<?php

namespace App\Models;

use App\Enums\Trains\TrainStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Train extends Model
{
    protected $fillable = [
        'company_id',
        'train_model_id',
        'current_line_id',
        'current_depot_id',
        'name',
        'status',
        'total_kilometers',
        'delivery_arrival_at',
    ];

    protected $casts = [
        'delivery_arrival_at' => 'datetime',
        'status' => TrainStatus::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(TrainModel::class, 'train_model_id');
    }

    public function currentLine(): BelongsTo
    {
        return $this->belongsTo(Line::class, 'current_line_id');
    }

    public function currentDepot(): BelongsTo
    {
        return $this->belongsTo(Depot::class, 'current_depot_id');
    }

    // Relation avec les voitures de la rame (triées par position)
    public function cars(): HasMany
    {
        return $this->hasMany(TrainCar::class)->orderBy('position');
    }

    // Usure moyenne calculée à partir des voitures
    public function getWearPercentageAttribute(): float
    {
        $avg = $this->cars()->avg('wear_percentage');
        return $avg !== null ? (float) round($avg, 2) : 0.0;
    }

    // Nombre de voitures dans la rame
    public function getCarsCountAttribute(): int
    {
        return $this->cars()->count();
    }
}
