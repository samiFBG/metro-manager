<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Train extends Model
{
    protected $fillable = [
        'company_id',
        'train_model_id',
        'current_line_id',
        'current_depot_id',
        'cars_count',
        'status',
        'wear_percentage',
        'total_kilometers',
        'delivery_arrival_at',
    ];

    protected $casts = [
        'delivery_arrival_at' => 'datetime',
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
}
