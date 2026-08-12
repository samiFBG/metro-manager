<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Station extends Model
{
    protected $fillable = [
        'line_id',
        'name',
        'latitude',
        'longitude',
        'passenger_flow_weight',
        'order',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'passenger_flow_weight' => 'float',
        'order' => 'integer',
    ];

    public function line(): BelongsTo
    {
        return $this->belongsTo(Line::class);
    }
}
