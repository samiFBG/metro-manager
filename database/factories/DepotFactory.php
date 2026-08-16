<?php

namespace Database\Factories;

use App\Enums\Company\DepotType;
use App\Models\Company;
use App\Models\Depot;
use App\Models\Line;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepotFactory extends Factory
{
    protected $model = Depot::class;

    // database/factories/DepotFactory.php

    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' Depot',
            'type' => DepotType::WORKSHOP,
            'latitude' => 45.7626979,
            'longitude' => 4.921482,
            'max_capacity' => 25,
            'maintenance_slots' => 5,
            'company_id' => null,
        ];
    }
}
