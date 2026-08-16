<?php

namespace Database\Factories;

use App\Enums\Lines\LineAutomationLevel;
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
            'automation_level' => $this->faker->randomElement(LineAutomationLevel::cases()),
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

    /**
     * État pour une ligne 100% automatique (GoA4)
     */
    public function automated(): static
    {
        return $this->state(fn (array $attributes) => [
            'automation_level' => LineAutomationLevel::AUTOMATED,
        ]);
    }

    /**
     * État pour une ligne semi-automatique (GoA2)
     */
    public function semiAutomated(): static
    {
        return $this->state(fn (array $attributes) => [
            'automation_level' => LineAutomationLevel::SEMI_AUTOMATED,
        ]);
    }

    /**
     * État pour une ligne à conduite manuelle (GoA1)
     */
    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'automation_level' => LineAutomationLevel::MANUAL,
        ]);
    }
}
