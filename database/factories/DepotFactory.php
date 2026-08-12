<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Depot;
use App\Models\Line;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepotFactory extends Factory
{
    protected $model = Depot::class;

    public function definition(): array
    {
        return [
            'name' => 'Atelier '.$this->faker->city(),
            'line_id' => Line::factory(),
            'type' => 'workshop',
            'latitude' => $this->faker->latitude(45.7, 45.8), // Autour de Lyon
            'longitude' => $this->faker->longitude(4.8, 4.9),
            'max_capacity' => $this->faker->numberBetween(10, 25),
            'maintenance_slots' => $this->faker->numberBetween(2, 5),
            'company_id' => Company::factory(),
        ];
    }
}
