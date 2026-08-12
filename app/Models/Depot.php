<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Depot extends Model
{
    use HasFactory;

    protected $fillable = [
        'line_id',
        'company_id',
        'name',
        'type',
        'latitude',
        'longitude',
        'max_capacity',
        'maintenance_slots',
    ];

    public function line(): BelongsTo
    {
        return $this->belongsTo(Line::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parkedTrains(): HasMany
    {
        return $this->hasMany(Train::class, 'current_depot_id');
    }
}
