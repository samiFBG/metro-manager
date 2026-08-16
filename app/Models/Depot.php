<?php

namespace App\Models;

use App\Enums\Company\DepotType;
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

    protected $casts = [
        'type' => DepotType::class,
    ];

    /**
     * Les lignes raccordées à ce dépôt
     */
    public function lines(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Line::class);
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
