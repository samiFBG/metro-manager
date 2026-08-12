<?php

namespace Database\Factories;

use App\Models\Line;
use App\Models\TrainModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class LineFactory extends Factory
{
    protected $model = Line::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->bothify('L#?')),
            'name' => 'Ligne '.$this->faker->words(2, true),
            'color_hex' => $this->faker->hexColor(),
            'geo_json_path' => [
                'type' => 'LineString',
                'coordinates' => [
                    [4.8357, 45.7640],
                    [4.8500, 45.7700],
                ],
            ],
            'train_model_id' => TrainModel::factory(),
            'min_cars_per_train' => 2,
            'max_cars_per_train' => 4,
        ];
    }
}
