<?php

namespace Database\Factories;

use App\Models\TrainModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainModelFactory extends Factory
{
    protected $model = TrainModel::class;

    public function definition(): array
    {
        return [
            'name' => 'MP '.$this->faker->numberBetween(14, 89), // Inversé min et max
            'base_price' => 1500000.00,
            'capacity_per_car' => $this->faker->numberBetween(100, 180),
            'electricity_cost_per_km' => 1.25,
            'maintenance_cost_factor' => 1.10,
            'max_speed' => 80,
            'operating_speed' => 60,
        ];
    }
}
