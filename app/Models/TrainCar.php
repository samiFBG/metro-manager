<?php

namespace App\Models;

use App\Enums\Trains\TrainCarStatus;
use App\Enums\Trains\TrainCarType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainCar extends Model
{
    protected $fillable = [
        'company_id',
        'train_model_id',
        'train_id',
        'current_depot_id',
        'number_code',
        'type',
        'position',
        'wear_percentage',
        'status',
    ];

    protected $casts = [
        'wear_percentage' => 'float',
        'position' => 'integer',
        'status' => TrainCarStatus::class,
        'type' => TrainCarType::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function trainModel(): BelongsTo
    {
        return $this->belongsTo(TrainModel::class);
    }

    public function train(): BelongsTo
    {
        return $this->belongsTo(Train::class);
    }

    public function currentDepot(): BelongsTo
    {
        return $this->belongsTo(Depot::class, 'current_depot_id');
    }
}
